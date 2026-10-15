<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use App\Services\PurchaseVatTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = PurchaseReturn::with('purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'purchase.party:id,name', 'details')
            ->whereBetween('return_date', [request()->start_date, request()->end_date])
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
            'purchase_id' => 'required|exists:purchases,id',
            'products' => 'required|array|min:1',
            'products.*.purchase_detail_id' => 'required|exists:purchase_details,id',
            'products.*.return_qty' => 'required|numeric|min:1',
            'products.*.serial_numbers' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {

            $business_id = auth()->user()->business_id;

            $purchase = Purchase::with('details.stock')
                ->where('business_id', $business_id)
                ->findOrFail($request->purchase_id);

            /* ==========================================================
               DISCOUNT CALCULATION (UNCHANGED)
            ========================================================== */
            $total_discount = $purchase->discountAmount;
            $total_purchase_amount = $purchase->details->sum(fn ($d) =>
                $d->productPurchasePrice * $d->quantities
            );

            $discount_per_unit_factor = $total_purchase_amount > 0
                ? $total_discount / $total_purchase_amount
                : 0;

            $purchase_return = PurchaseReturn::create([
                'business_id' => $business_id,
                'purchase_id' => $purchase->id,
                'invoice_no' => $purchase->invoiceNumber,
                'return_date' => now(),
            ]);

            $returnDetails = [];
            $total_return_amount = 0;
            $total_return_discount = 0;

            /* ==========================================================
               LOOP THROUGH RETURN PRODUCTS
            ========================================================== */
            foreach ($request->products as $item) {

                $detail = $purchase->details
                    ->where('id', $item['purchase_detail_id'])
                    ->first();

                if (!$detail) {
                    return response()->json([
                        'message' => 'Invalid purchase detail.'
                    ], 400);
                }

                $requested_qty = (int) $item['return_qty'];
                $serials = $item['serial_numbers'] ?? [];

                /* ======================================================
                   SERIAL ADDON VALIDATION (OPTIONAL)
                ====================================================== */
                if (!empty($serials)) {

                    if (count($serials) !== $requested_qty) {
                        return response()->json([
                            'message' => 'Serial count must match return quantity.'
                        ], 400);
                    }

                    $stockSerials = $detail->stock->serial_numbers ?? [];

                    $invalid = array_diff($serials, $stockSerials);
                    if (!empty($invalid)) {
                        return response()->json([
                            'message' => 'Invalid serial numbers detected.',
                            'invalid_serials' => array_values($invalid),
                        ], 400);
                    }
                }

                if ($requested_qty > $detail->quantities) {
                    return response()->json([
                        'message' => "You can't return more than purchased quantity ({$detail->quantities})."
                    ], 400);
                }

                /* ======================================================
                   RETURN AMOUNT CALCULATION (UNCHANGED)
                ====================================================== */
                $unit_discount = $detail->productPurchasePrice * $discount_per_unit_factor;
                $return_discount = $unit_discount * $requested_qty;
                $return_amount = ($detail->productPurchasePrice - $unit_discount) * $requested_qty;

                $total_return_amount += $return_amount;
                $total_return_discount += $return_discount;

                /* ======================================================
                   STOCK UPDATE
                ====================================================== */
                $stock = $detail->stock;
                $stock->decrement('productStock', $requested_qty);

                /* ======================================================
                   REMOVE SERIALS FROM STOCK (ONLY IF SERIAL PRODUCT)
                ====================================================== */
                if (!empty($serials)) {
                    $remainingSerials = array_values(
                        array_diff($stock->serial_numbers ?? [], $serials)
                    );

                    $stock->update([
                        'serial_numbers' => $remainingSerials
                    ]);
                }

                /* ======================================================
                   UPDATE PURCHASE DETAIL
                ====================================================== */
                $detail->quantities -= $requested_qty;
                $detail->timestamps = false;
                $detail->save();

                // ===== REMOVE SERIALS FROM PURCHASE DETAIL (NEW)
                if (!empty($serials) && !empty($detail->serial_numbers)) {
                    $remainingDetailSerials = array_values(
                        array_diff($detail->serial_numbers ?? [], $serials)
                    );

                    $detail->update([
                        'serial_numbers' => $remainingDetailSerials
                    ]);
                }

                /* ======================================================
                   COLLECT RETURN DETAILS
                ====================================================== */
                $returnDetail = PurchaseReturnDetail::create([
                    'business_id' => $business_id,
                    'purchase_return_id' => $purchase_return->id,
                    'purchase_detail_id' => $detail->id,
                    'return_qty' => $requested_qty,
                    'return_amount' => $return_amount,
                    'serial_numbers' => $serials ?? null,
                ]);

                $previous_returned_qty = PurchaseReturnDetail::where('purchase_detail_id', $detail->id)->sum('return_qty');
                $totalQty = $detail->quantities + $previous_returned_qty;

                PurchaseVatTransaction::productReturnVatCalculation($detail, $requested_qty, $returnDetail, $totalQty);
            }

            if ($total_return_amount <= 0) {
                return response()->json('You cannot return empty product.', 400);
            }

            /* ==========================================================
               INSERT RETURN DETAILS
            ========================================================== */
            PurchaseReturnDetail::insert($returnDetails);

            /* ==========================================================
               PARTY & PURCHASE ADJUSTMENT (UNCHANGED)
            ========================================================== */
            $party = Party::find($purchase->party_id);
            if ($party) {
                $refund = $total_return_amount;

                if ($party->due > 0) {
                    $deduct = min($party->due, $refund);
                    $party->decrement('due', $deduct);
                    $refund -= $deduct;
                }

                if ($refund > 0 && $party->wallet > 0) {
                    $party->decrement('wallet', min($party->wallet, $refund));
                }
            }

            $purchase->update([
                'change_amount' => 0,
                'dueAmount' => max(0, $purchase->dueAmount - $total_return_amount),
                'paidAmount' => max(0, $purchase->paidAmount - $total_return_amount),
                'totalAmount' => max(0, $purchase->totalAmount - $total_return_amount),
                'discountAmount' => max(0, $purchase->discountAmount - $total_return_discount),
            ]);

            /* ==========================================================
               PAYMENT EVENT (UNCHANGED)
            ========================================================== */
            $payments = collect($request->payments ?? [])
                ->map(fn ($p) => array_merge($p, ['amount' => $total_return_amount]))
                ->toArray();

            event(new MultiPaymentProcessed(
                $payments,
                $purchase_return->id,
                'purchase_return',
                $total_return_amount
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $purchase_return->load('details'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $data = PurchaseReturn::with('purchase:id,party_id,isPaid,totalAmount,dueAmount,paidAmount,invoiceNumber', 'purchase.party:id,name', 'details')->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
