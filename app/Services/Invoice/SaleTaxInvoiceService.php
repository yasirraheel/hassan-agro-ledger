<?php

namespace App\Services\Invoice;

use App\Models\Sale;
use App\Models\Party;
use App\Models\SaleReturn;
use App\Models\PaymentType;
use App\Models\SaleDetails;
use App\Models\SaleReturnDetails;
use App\Models\VatTransaction;

class SaleTaxInvoiceService
{
    public function getInvoiceData(int $sale_id, ?string $phone = null)
    {
        $party = null;

        if ($phone) {
            $party = Party::where('phone', $phone)->first();
            if (!$party) {
                abort(404, 'Not found');
            }
        }

        $business_id = auth()->check() ? auth()->user()->business_id : null;

        $saleQuery = Sale::with('user:id,name,role', 'party:id,name,phone,address,email', 'business:id,phoneNumber,companyName,vat_name,vat_no,address,email,meta,state_id', 'business.state:id,name', 'details:id,price,price_without_tax,discount,quantities,product_id,sale_id,stock_id,warranty_guarantee_info,serial_numbers', 'details.stock:id,batch_no,variant_name', 'details.product:id,productName,vat_id,has_serial', 'payment_type:id,name', 'transactions:id,reference_id,transaction_type,payment_type_id', 'transactions.paymentType:id,name');

        if ($phone) {
            $saleQuery->where('party_id', $party->id);
            $business_id = $party->business_id;
        } elseif ($business_id) {
            $saleQuery->where('business_id', $business_id);
        } else {
            abort(404, 'Not found');
        }

        $sale = $saleQuery->findOrFail($sale_id);

        $detail_ids = $sale->details->pluck('id');
        $tax_items = VatTransaction::selectRaw('vat_id, vat_rate, SUM(vat_amount) as vat_amount')
                        ->with('vat:id,name')
                        ->whereIn('vatable_id', $detail_ids)
                        ->where('vatable_type', SaleDetails::class)
                        ->groupBy('vat_id', 'vat_rate')
                        ->get();

        // Group VAT by sale_detail_id for easy lookup in blade
        $vatRowMap = VatTransaction::whereIn('vatable_id', $detail_ids)
            ->where('vatable_type', SaleDetails::class)
            ->selectRaw('vatable_id, vat_id, SUM(vat_amount) as total')
            ->groupBy('vatable_id', 'vat_id')
            ->get()
            ->groupBy('vatable_id')
            ->map(function ($items) {
                return $items->pluck('total', 'vat_id')->toArray();
            })
            ->toArray();

        $transactionTypes = $sale->transactions
            ->map(function ($transaction) {
                if ($transaction->transaction_type === 'bank_payment' && !empty($transaction->paymentType?->name)) {
                    return $transaction->paymentType->name;
                }
                return $transaction->transaction_type ? ucfirst(explode('_', $transaction->transaction_type)[0]) : '';
            })
            ->unique()
            ->implode(', ');

        $sale_returns = SaleReturn::with('sale:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'sale.party:id,name', 'details', 'details.saleDetail.product:id,productName,has_serial')
            ->where('business_id', $business_id)
            ->where('sale_id', $sale_id)
            ->get();

        // collect return detail ids
        $return_detail_ids = $sale_returns->flatMap(function ($return) {
            return $return->details->pluck('id');
        });

        // get VAT for sale return details
        $returnVatRowMap = VatTransaction::whereIn('vatable_id', $return_detail_ids)
            ->where('vatable_type', SaleReturnDetails::class)
            ->selectRaw('vatable_id, vat_id, SUM(vat_amount) as total')
            ->groupBy('vatable_id', 'vat_id')
            ->get()
            ->groupBy('vatable_id')
            ->map(function ($items) {
                return $items->pluck('total', 'vat_id')->toArray();
            })
            ->toArray();

        $returnTransaction = $sale_returns->first()?->transactions->first();
        $returnTransactionType = '';

        if ($returnTransaction) {
            $returnTransactionType = $returnTransaction->transaction_type === 'bank_payment'
                ? ($returnTransaction->paymentType?->name)
                : ucfirst(explode('_', $returnTransaction->transaction_type)[0]);
        }

        // Restore Quantity + Serial Numbers From Return
        $sale->details = $sale->details->map(function ($detail) use ($sale_returns) {

            // Restore Quantity
            $return_qty_sum = $sale_returns->flatMap(function ($return) use ($detail) {
                return $return->details
                    ->where('sale_detail_id', $detail->id)
                    ->pluck('return_qty');
            })->sum();

            $detail->quantities = $detail->quantities + $return_qty_sum;

            // Restore Serial Numbers

            $returned_serials = [];

            foreach ($sale_returns as $return) {
                foreach ($return->details as $returnDetail) {

                    if ($returnDetail->sale_detail_id == $detail->id) {

                        if (!empty($returnDetail->serial_numbers)) {

                            $serials = is_array($returnDetail->serial_numbers)
                                ? $returnDetail->serial_numbers
                                : json_decode($returnDetail->serial_numbers, true);

                            if (is_array($serials)) {
                                $returned_serials = array_merge($returned_serials, $serials);
                            }
                        }
                    }
                }
            }

            $current_serials = [];

            if (!empty($detail->serial_numbers)) {
                $current_serials = is_array($detail->serial_numbers)
                    ? $detail->serial_numbers
                    : json_decode($detail->serial_numbers, true);
            }

            $detail->serial_numbers = array_values(
                array_unique(
                    array_merge($current_serials ?? [], $returned_serials)
                )
            );

            return $detail;
        });

        // Calculate the initial discount for each product during sale returns
        $total_discount = 0;
        $product_discounts = [];

        foreach ($sale_returns as $return) {
            foreach ($return->details as $detail) {
                // Add the return quantities and return amounts for each sale_detail_id
                if (!isset($product_discounts[$detail->sale_detail_id])) {
                    // Initialize the first occurrence
                    $product_discounts[$detail->sale_detail_id] = [
                        'return_qty' => 0,
                        'return_amount' => 0,
                        'price' => $detail->saleDetail->price,
                    ];
                }

                // Accumulate quantities and return amounts for the same sale_detail_id
                $product_discounts[$detail->sale_detail_id]['return_qty'] += $detail->return_qty;
                $product_discounts[$detail->sale_detail_id]['return_amount'] += $detail->return_amount;
            }
        }

        // Calculate the total discount based on accumulated quantities and return amounts
        foreach ($product_discounts as $data) {
            $product_price = $data['price'] * $data['return_qty'];
            $discount = $product_price - $data['return_amount'];

            $total_discount += $discount;
        }

        $hasWarranty = false;
        $hasGuarantee = false;

        foreach ($sale->details as $detail) {
            $info = $detail->warranty_guarantee_info ?? [];

            if (!empty($info['warranty_duration'])) {
                $hasWarranty = true;
            }

            if (!empty($info['guarantee_duration'])) {
                $hasGuarantee = true;
            }

            if ($hasWarranty && $hasGuarantee) break;
        };

        $bank_detail = PaymentType::where('business_id', $business_id)->where('show_in_invoice', 1)->latest()->first();

        $total_return_amount = $sale_returns->sum(function ($return) {
            return $return->details->sum('return_amount');
        });

        return compact(
            'sale',
            'tax_items',
            'sale_returns',
            'total_discount',
            'hasWarranty',
            'hasGuarantee',
            'transactionTypes',
            'bank_detail',
            'returnTransactionType',
            'business_id',
            'total_return_amount',
            'vatRowMap',
            'returnVatRowMap'
        );
    }
}
