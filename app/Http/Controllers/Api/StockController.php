<?php

namespace App\Http\Controllers\Api;

use App\Models\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'product_id' => 'required|exists:products,id',
            'productStock' => 'required|integer|min:1',
            'serial_numbers' => 'nullable|array',
            'serial_numbers.*' => 'string'
        ]);

        $stock = Stock::where('business_id', auth()->user()->business_id)
            ->where('id', $request->stock_id)
            ->where('product_id', $request->product_id)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $incomingSerials = $request->serial_numbers ?? [];

            // SERIAL NUMBER
            if (!empty($incomingSerials)) {

                // Check serial uniqueness
                $exists = Stock::where('product_id', $request->product_id)
                    ->whereNotNull('serial_numbers')
                    ->get()
                    ->pluck('serial_numbers')
                    ->flatten()
                    ->intersect($incomingSerials);

                if ($exists->isNotEmpty()) {
                    return response()->json([
                        'message' => 'These serial numbers already exist: ' . $exists->implode(', ')
                    ], 422);
                }

                // Merge serials
                $existingSerials = $stock->serial_numbers ?? [];
                $mergedSerials = array_values(array_unique(
                    array_merge($existingSerials, $incomingSerials)
                ));

                // Increment stock by serial count
                $stock->increment('productStock', count($incomingSerials));

                $stock->serial_numbers = $mergedSerials;
                $stock->save();

            } else {
                // NORMAL STOCK
                $stock->increment('productStock', $request->productStock);
            }

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */

    //stock update
    public function update(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_no' => 'nullable|string',
            'productStock' => 'nullable|numeric|min:0',
            'productPurchasePrice' => 'nullable|numeric|min:0',
            'profit_percent' => 'nullable|numeric|min:0',
            'productSalePrice' => 'nullable|numeric|min:0',
            'productWholeSalePrice' => 'nullable|numeric|min:0',
            'productDealerPrice' => 'nullable|numeric|min:0',
            'mfg_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'serial_numbers' => 'nullable|array',
            'serial_numbers.*' => 'string',
        ]);

        $stock = Stock::where('id', $id)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$stock) {
            return response()->json([
                'message' => 'Stock not found for this product.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $updateData = [
                'batch_no' => $request->batch_no ?? $stock->batch_no,
                'warehouse_id' => $request->warehouse_id ?? $stock->warehouse_id,
                'productStock' => $request->productStock ?? $stock->productStock,
                'productPurchasePrice' => $request->productPurchasePrice ?? $stock->productPurchasePrice,
                'profit_percent' => $request->profit_percent ?? $stock->profit_percent,
                'productSalePrice' => $request->productSalePrice ?? $stock->productSalePrice,
                'productWholeSalePrice' => $request->productWholeSalePrice ?? $stock->productWholeSalePrice,
                'productDealerPrice' => $request->productDealerPrice ?? $stock->productDealerPrice,
                'mfg_date' => $request->mfg_date ?? $stock->mfg_date,
                'expire_date' => $request->expire_date ?? $stock->expire_date,
            ];

            $incomingSerials = $request->serial_numbers ?? [];

            // Handle serial numbers if provided
            if (!empty($incomingSerials)) {

                // Check for duplicates across all stocks of this product
                $existingSerialsAll = Stock::where('product_id', $request->product_id)
                    ->where('id', '!=', $stock->id)
                    ->whereNotNull('serial_numbers')
                    ->get()
                    ->pluck('serial_numbers')
                    ->flatten()
                    ->toArray();

                $duplicates = array_intersect($incomingSerials, $existingSerialsAll);

                if (!empty($duplicates)) {
                    return response()->json([
                        'message' => 'These serial numbers already exist for this product',
                        'duplicates' => array_values($duplicates),
                    ], 422);
                }

                // Merge with existing serials
                $existingSerials = $stock->serial_numbers ?? [];
                $existingSerials = is_array($existingSerials) ? $existingSerials : json_decode($existingSerials, true);

                $mergedSerials = array_values(array_unique(array_merge($existingSerials, $incomingSerials)));

                $updateData['serial_numbers'] = $mergedSerials;
                $updateData['productStock'] = count($mergedSerials);
            }

            $stock->update($updateData);

            DB::commit();

            return response()->json([
                'message' => __('Stock updated successfully.'),
                'data' => $stock->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Stock::where('id', $id)->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
