<?php

namespace App\Services\Invoice;

use App\Models\Party;
use App\Models\PaymentType;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseReturn;
use App\Models\VatTransaction;

class PurchaseInvoiceService
{
    public function getInvoiceDataByUniqueId(string $unique_id)
    {
        $purchase = Purchase::with('user:id,name,role', 'party:id,name,phone,address,email', 'business:id,phoneNumber,companyName,vat_name,vat_no,address,email,meta', 'details:id,price_without_tax,productPurchasePrice,quantities,product_id,purchase_id,serial_numbers', 'details.stock:id,batch_no,variant_name', 'details.product:id,vat_id,productName,has_serial', 'payment_type:id,name')
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        $business_id = $purchase->business_id;
        $detail_ids = $purchase->details->pluck('id');

        $tax_items = VatTransaction::select('vat_amount', 'vat_rate', 'vat_id')
                        ->with('vat:id,name')
                        ->whereIn('vatable_id', $detail_ids)
                        ->where('vatable_type', PurchaseDetails::class)
                        ->get();

        $transactionTypes = $purchase->transactions
            ->map(function ($transaction) {
                if ($transaction->transaction_type === 'bank_payment' && !empty($transaction->paymentType?->name)) {
                    return $transaction->paymentType->name;
                }
                return $transaction->transaction_type ? ucfirst(explode('_', $transaction->transaction_type)[0]) : '';
            })
            ->unique()
            ->implode(', ');

        $purchase_returns = PurchaseReturn::with('purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'purchase.party:id,name,email', 'details', 'details.purchaseDetail.product:id,productName,has_serial', 'details.purchaseDetail.stock:id,batch_no')
            ->where('business_id', $business_id)
            ->where('purchase_id', $purchase->id)
            ->get();

        $returnTransaction = $purchase_returns->first()?->transactions->first();
        $returnTransactionType = '';

        if ($returnTransaction) {
            $returnTransactionType = $returnTransaction->transaction_type === 'bank_payment'
                ? ($returnTransaction->paymentType?->name)
                : ucfirst(explode('_', $returnTransaction->transaction_type)[0]);
        }

        $purchase->details = $purchase->details->map(function ($detail) use ($purchase_returns) {
            $return_qty_sum = $purchase_returns->flatMap(function ($return) use ($detail) {
                return $return->details
                    ->where('purchase_detail_id', $detail->id)
                    ->pluck('return_qty');
            })->sum();

            $detail->quantities = $detail->quantities + $return_qty_sum;

            $returned_serials = [];

            foreach ($purchase_returns as $return) {
                foreach ($return->details as $returnDetail) {
                    if ($returnDetail->purchase_detail_id == $detail->id) {
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

        $total_discount = 0;
        $product_discounts = [];

        foreach ($purchase_returns as $return) {
            foreach ($return->details as $detail) {
                if (!isset($product_discounts[$detail->purchase_detail_id])) {
                    $product_discounts[$detail->purchase_detail_id] = [
                        'return_qty' => 0,
                        'return_amount' => 0,
                        'price' => $detail->purchaseDetail->productPurchasePrice,
                    ];
                }
                $product_discounts[$detail->purchase_detail_id]['return_qty'] += $detail->return_qty;
                $product_discounts[$detail->purchase_detail_id]['return_amount'] += $detail->return_amount;
            }
        }

        foreach ($product_discounts as $data) {
            $product_price = $data['price'] * $data['return_qty'];
            $discount = $product_price - $data['return_amount'];
            $total_discount += $discount;
        }

        $bank_detail = PaymentType::where('business_id', $business_id)->where('show_in_invoice', 1)->latest()->first();

        $total_return_amount = $purchase_returns->sum(function ($return) {
            return $return->details->sum('return_amount');
        });

        return compact(
            'purchase',
            'tax_items',
            'purchase_returns',
            'total_discount',
            'transactionTypes',
            'bank_detail',
            'returnTransactionType',
            'business_id',
            'total_return_amount'
        );
    }

    public function getInvoiceData(int $purchase_id, ?string $phone = null)
    {
        $party = null;

        if ($phone) {
            $party = Party::where('phone', $phone)->first();
            if (!$party) {
                abort(404, 'Not found');
            }
        }

        $business_id = auth()->check() ? auth()->user()->business_id : null;

        $purchaseQuery = Purchase::with('user:id,name,role', 'party:id,name,phone,address,email', 'business:id,phoneNumber,companyName,vat_name,vat_no,address,email,meta', 'details:id,price_without_tax,productPurchasePrice,quantities,product_id,purchase_id,serial_numbers', 'details.stock:id,batch_no,variant_name', 'details.product:id,vat_id,productName,has_serial', 'payment_type:id,name');

        if ($phone) {
            $purchaseQuery->where('party_id', $party->id);
            $business_id = $party->business_id;
        } elseif ($business_id) {
            $purchaseQuery->where('business_id', $business_id);
        } else {
            abort(404, 'Not found');
        }

        $purchase = $purchaseQuery->findOrFail($purchase_id);

        $detail_ids = $purchase->details->pluck('id');
        $tax_items = VatTransaction::select('vat_amount', 'vat_rate', 'vat_id')
                        ->with('vat:id,name')
                        ->whereIn('vatable_id', $detail_ids)
                        ->where('vatable_type', PurchaseDetails::class)
                        ->get();

        $transactionTypes = $purchase->transactions
            ->map(function ($transaction) {
                if ($transaction->transaction_type === 'bank_payment' && !empty($transaction->paymentType?->name)) {
                    return $transaction->paymentType->name;
                }
                return $transaction->transaction_type ? ucfirst(explode('_', $transaction->transaction_type)[0]) : '';
            })
            ->unique()
            ->implode(', ');

        $purchase_returns = PurchaseReturn::with('purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'purchase.party:id,name,email', 'details', 'details.purchaseDetail.product:id,productName,has_serial', 'details.purchaseDetail.stock:id,batch_no')
            ->where('business_id', $business_id)
            ->where('purchase_id', $purchase_id)
            ->get();

        $returnTransaction = $purchase_returns->first()?->transactions->first();
        $returnTransactionType = '';

        if ($returnTransaction) {
            $returnTransactionType = $returnTransaction->transaction_type === 'bank_payment'
                ? ($returnTransaction->paymentType?->name)
                : ucfirst(explode('_', $returnTransaction->transaction_type)[0]);
        }

        // Restore Quantity + Serial Numbers From Return
        $purchase->details = $purchase->details->map(function ($detail) use ($purchase_returns) {

            // Restore Quantity
            $return_qty_sum = $purchase_returns->flatMap(function ($return) use ($detail) {
                return $return->details
                    ->where('purchase_detail_id', $detail->id)
                    ->pluck('return_qty');
            })->sum();

            $detail->quantities = $detail->quantities + $return_qty_sum;

            // Restore Serial Numbers
            $returned_serials = [];

            foreach ($purchase_returns as $return) {
                foreach ($return->details as $returnDetail) {

                    if ($returnDetail->purchase_detail_id == $detail->id) {

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


        // Calculate total discount based on return quantities and amounts
        $total_discount = 0;
        $product_discounts = [];

        foreach ($purchase_returns as $return) {
            foreach ($return->details as $detail) {
                // Initialize discount tracking for the first occurrence of each purchase_detail_id
                if (!isset($product_discounts[$detail->purchase_detail_id])) {
                    $product_discounts[$detail->purchase_detail_id] = [
                        'return_qty' => 0,
                        'return_amount' => 0,
                        'price' => $detail->purchaseDetail->productPurchasePrice,
                    ];
                }

                // Accumulate return quantities and return amounts
                $product_discounts[$detail->purchase_detail_id]['return_qty'] += $detail->return_qty;
                $product_discounts[$detail->purchase_detail_id]['return_amount'] += $detail->return_amount;
            }
        }

        // Calculate the total discount for each returned product
        foreach ($product_discounts as $data) {
            $product_price = $data['price'] * $data['return_qty'];
            $discount = $product_price - $data['return_amount'];

            $total_discount += $discount;
        }

        $bank_detail = PaymentType::where('business_id', $business_id)->where('show_in_invoice', 1)->latest()->first();

        $total_return_amount = $purchase_returns->sum(function ($return) {
            return $return->details->sum('return_amount');
        });

        return compact(
            'purchase',
            'tax_items',
            'purchase_returns',
            'total_discount',
            'transactionTypes',
            'bank_detail',
            'returnTransactionType',
            'business_id',
            'total_return_amount'
            // 'items_vat_amount',
        );
    }
}
