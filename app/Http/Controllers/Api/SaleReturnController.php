<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetails;
use App\Models\Stock;
use App\Services\VatTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = SaleReturn::with('sale:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'sale.party:id,name', 'details')
            ->when(request()->filled('start_date') && request()->filled('end_date'), fn ($q) => $q->whereBetween('return_date', [request()->start_date, request()->end_date]))
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'products' => 'required|array|min:1',
            'products.*.sale_detail_id' => 'required|exists:sale_details,id',
            'products.*.return_qty' => 'required|integer|min:1',
            'products.*.serial_numbers' => 'nullable|array',
            'return_date' => 'nullable|date',
        ]);

        $business_id = auth()->user()->business_id;

        DB::beginTransaction();
        try {

            $sale = Sale::with(
                'details.product',
                'details.product.combo_products'
            )
                ->where('business_id', $business_id)
                ->findOrFail($request->sale_id);

            $old_sale_due = $sale->dueAmount;

            $sale_return = SaleReturn::create([
                'business_id' => $business_id,
                'sale_id'     => $sale->id,
                'invoice_no'  => $sale->invoiceNumber,
                'return_date' => $request->return_date ?? now(),
            ]);

            $total_discount = $sale->discountAmount;
            $total_sale_amount = $sale->details->sum(
                fn($d) => $d->price * $d->quantities
            );

            $discount_per_unit_factor = $total_sale_amount > 0
                ? $total_discount / $total_sale_amount
                : 0;

            $rounding_amount_per_unit = $sale->details->sum('quantities') > 0
                ? $sale->rounding_amount / $sale->details->sum('quantities')
                : 0;

            $sale_return_detail_data = [];
            $total_return_amount = 0;
            $total_return_discount = 0;
            $total_loss_profit_adjustment = 0;
            $all_return_serials = [];

            foreach ($request->products as $item) {

                $detail = SaleDetails::with('product')
                    ->where('id', $item['sale_detail_id'])
                    ->where('sale_id', $sale->id)
                    ->firstOrFail();

                $requested_qty  = (int) $item['return_qty'];
                $return_serials = $item['serial_numbers'] ?? [];

                if ($requested_qty > $detail->quantities) {
                    return response()->json([
                        'message' => "You can't return more than ordered quantity of {$detail->quantities}."
                    ], 400);
                }

                $unit_discount = $detail->price * $discount_per_unit_factor;
                $item_cart_discount = $detail->discount ?? 0;
                $total_discount_per_unit = $unit_discount + $item_cart_discount;

                $return_discount = $total_discount_per_unit * $requested_qty;
                $return_amount = (
                        $detail->price
                        - $total_discount_per_unit
                        + $rounding_amount_per_unit
                    ) * $requested_qty;

                $total_return_amount += $return_amount;
                $total_return_discount += $return_discount;

                if ($detail->product->product_type === 'combo') {

                    foreach ($detail->product->combo_products as $comboItem) {

                        $stock = Stock::find($comboItem->stock_id);
                        if (!$stock) {
                            return response()->json([
                                'message' => "Stock not found for combo item"
                            ], 400);
                        }

                        $restore_qty = $comboItem->quantity * $requested_qty;
                        $stock->increment('productStock', $restore_qty);
                    }
                }

                // single / variant product
                else {

                    $stock = Stock::find($detail->stock_id);
                    if (!$stock) {
                        return response()->json(['error' => 'Stock not found'], 404);
                    }

                    // with serial numbers
                    if ($detail->product->has_serial) {

                        if (count($return_serials) !== $requested_qty) {
                            return response()->json([
                                'message' => 'Serial count must match return quantity'
                            ], 400);
                        }

                        $sold_serials = is_array($detail->serial_numbers) ? $detail->serial_numbers : json_decode($detail->serial_numbers ?? '[]', true);

                        $invalid = array_diff($return_serials, $sold_serials);
                        if (!empty($invalid)) {
                            return response()->json([
                                'message' => 'Invalid serial detected'
                            ], 400);
                        }

                        // restore stock
                        $stock->increment('productStock', $requested_qty);

                        // restore serials to stock
                        $stock_serials = $stock->serial_numbers ?? [];
                        $stock->update([
                            'serial_numbers' => array_values(array_unique(
                                array_merge($stock_serials, $return_serials)
                            ))
                        ]);

                        // remove serials from sale detail
                        $detail->update([
                            'serial_numbers' => array_values(
                                array_diff($sold_serials, $return_serials)
                            )
                        ]);

                        $all_return_serials = array_merge($all_return_serials, $return_serials);
                    }

                    // without serial numbers
                    else {
                        $stock->increment('productStock', $requested_qty);
                    }
                }

                $loss_profit_adjustment = $detail->lossProfit * ($requested_qty / $detail->quantities);
                $total_loss_profit_adjustment += $loss_profit_adjustment;

                // Update sale details
                $detail->quantities -= $requested_qty;
                $detail->lossProfit -= $loss_profit_adjustment;
                $detail->timestamps = false;
                $detail->save();

                $returnDetails = SaleReturnDetails::create([
                    'business_id'    => $business_id,
                    'sale_detail_id' => $detail->id,
                    'sale_return_id' => $sale_return->id,
                    'return_qty'     => $requested_qty,
                    'return_amount' => $return_amount,
                    'serial_numbers'=> $return_serials ?? null,
                ]);

                $previous_returned_qty = SaleReturnDetails::where('sale_detail_id', $detail->id)->sum('return_qty');
                $totalQty = $detail->quantities + $previous_returned_qty;

                VatTransactionService::productReturnVatCalculation($detail, $requested_qty, $returnDetails, $totalQty);
            }

            if ($total_return_amount <= 0) {
                return response()->json("You cannot return an empty product.", 400);
            }

            SaleReturnDetails::insert($sale_return_detail_data);

            $remaining_refund = $total_return_amount;

            // Adjust Due
            $new_due = $sale->dueAmount;
            if ($remaining_refund > 0) {
                if ($remaining_refund >= $sale->dueAmount) {
                    $remaining_refund -= $sale->dueAmount;
                    $new_due = 0;
                } else {
                    $new_due -= $remaining_refund;
                    $remaining_refund = 0;
                }
            }

            // Adjust Paid (refund part)
            $new_paid = $sale->paidAmount;
            if ($remaining_refund > 0) {
                if ($remaining_refund >= $sale->paidAmount) {
                    $remaining_refund -= $sale->paidAmount;
                    $new_paid = 0;
                } else {
                    $new_paid -= $remaining_refund;
                    $remaining_refund = 0;
                }
            }

            // total sale amount
            $new_total_amount = max(0, $sale->totalAmount - $total_return_amount);

            $sale->update([
                'change_amount' => 0,
                'dueAmount'     => $new_due,
                'paidAmount'    => $new_paid,
                'totalAmount'   => $new_total_amount,
                'actual_total_amount' => $new_total_amount,
                'discountAmount'=> max(0, $sale->discountAmount - $total_return_discount),
                'lossProfit'    => $sale->lossProfit - $total_loss_profit_adjustment,
            ]);

            // Party Refund Logic
            $due_reduction = $old_sale_due - $new_due;

            if ($due_reduction > 0) {
                Party::where('business_id', $business_id)
                ->where('id', $sale->party_id)
                ->decrement('due', $due_reduction);
            }

            $party = Party::where('business_id', $business_id)->find($sale->party_id);

            if ($party && $remaining_refund > 0) {

                if ($party->due > 0) {

                    if ($party->due >= $remaining_refund) {
                        $party->decrement('due', $remaining_refund);
                        $remaining_refund = 0;
                    } else {
                        $remaining_refund -= $party->due;
                        $party->update(['due' => 0]);
                    }

                }

                if ($remaining_refund > 0) {
                    $party->increment('wallet', $remaining_refund);
                }
            }


            $payments = $request->payments ?? [];
            $payments = collect($payments)->map(function ($payment) use ($total_return_amount) {
                $payment['amount'] = $total_return_amount;
                return $payment;
            })->toArray();

            event(new MultiPaymentProcessed(
                $payments,
                $sale_return->id,
                'sale_return',
                $total_return_amount,
                $sale->party_id,
                $sale_return->return_date
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $sale_return->load('details'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $data = SaleReturn::with('sale:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'sale.party:id,name', 'details')->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
