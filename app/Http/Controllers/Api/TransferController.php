<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\TransferProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $activeBranchId = $user->active_branch_id;

        $transfers = Transfer::query()
            ->select([
                'transfers.id',
                'transfers.transfer_date',
                'transfers.invoice_no',
                'transfers.status',
                'transfers.from_branch_id',
                'transfers.to_branch_id',
                'transfers.from_warehouse_id',
                'transfers.to_warehouse_id',
            ])

            // Qty calculation
            ->selectSub(function ($q) {
                $q->from('transfer_products')
                    ->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn('transfer_products.transfer_id', 'transfers.id');
            }, 'qty')

            // Stock value calculation
            ->selectSub(function ($q) {
                $q->from('transfer_products')
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
                    ->whereColumn('transfer_products.transfer_id', 'transfers.id');
            }, 'stock_value')
            ->with([
                'user:id,name',
                'fromBranch:id,name',
                'toBranch:id,name',
                'fromWarehouse:id,name',
                'toWarehouse:id,name',
            ])
            ->where('transfers.business_id', $user->business_id)
            ->when($activeBranchId, function ($q) use ($activeBranchId) {
                $q->where(function ($query) use ($activeBranchId) {
                    $query->where('from_branch_id', $activeBranchId)
                        ->orWhere('to_branch_id', $activeBranchId);
                });
            })
            ->when(!$activeBranchId && request('branch_id'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('from_branch_id', request('branch_id'))
                        ->orWhere('to_branch_id', request('branch_id'));
                });
            })
            ->latest('transfers.id')
            ->get()
            ->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'transfer_date' => $transfer->transfer_date,
                    'invoice_no' => $transfer->invoice_no,
                    'status' => $transfer->status,
                    'from_branch' => $transfer->fromBranch->name ?? null,
                    'to_branch' => $transfer->toBranch->name ?? null,
                    'from_warehouse' => $transfer->fromWarehouse->name ?? null,
                    'to_warehouse' => $transfer->toWarehouse->name ?? null,
                    'qty' => (int)$transfer->qty,
                    'stock_value' => (float)$transfer->stock_value,
                ];
            });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $transfers,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_branch_id' => 'nullable|exists:branches,id',
            'to_branch_id' => 'nullable|exists:branches,id',
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'transfer_date' => 'required|date',
            'note' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled',
            'shipping_charge' => 'nullable|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax' => 'nullable|numeric|min:0',
            'products.*.serial_numbers' => 'nullable|array',
        ]);

        $user = auth()->user();

        $fromBranch = $request->from_branch_id ?? $user->branch_id ?? $user->active_branch_id;
        $toBranch   = $request->to_branch_id;
        $fromWh     = $request->from_warehouse_id;
        $toWh       = $request->to_warehouse_id;

        // Transfer validation
        if ($user->active_branch_id && $toBranch && $toWh) {
            return response()->json(['message' => 'You cannot transfer to another branch warehouse.'], 400);
        }

        if (!$toBranch && !$toWh) {
            return response()->json(['message' => 'Please select a destination branch or warehouse.'], 400);
        }

        if ($fromBranch && !$fromWh && $fromBranch == $toBranch) {
            return response()->json(['message' => 'Same branch transfer is not allowed.'], 400);
        }

        if ($fromWh && $fromWh == $toWh) {
            return response()->json(['message' => 'Same warehouse transfer is not allowed.'], 400);
        }

        DB::beginTransaction();

        try {
            $subTotal = $totalDiscount = $totalTax = 0;

            // Normalize products
            $products = collect($request->products)->map(function ($item, $key) {
                return array_merge($item, ['stock_id' => $item['stock_id'] ?? $key]);
            });

            // Calculate subtotal using serial count if available
            foreach ($products as $item) {
                $qty = !empty($item['serial_numbers']) ? count($item['serial_numbers']) : $item['quantity'];
                $subTotal += $qty * $item['unit_price'];
                $totalTax += $item['tax'] ?? 0;
                $totalDiscount += $item['discount'] ?? 0;
            }

            $shipping = $request->shipping_charge ?? 0;
            $grandTotal = $subTotal + $totalTax - $totalDiscount + $shipping;

            $transfer = Transfer::create([
                'business_id' => $user->business_id,
                'user_id' => auth()->id(),
                'from_branch_id' => $fromBranch,
                'from_warehouse_id' => $fromWh,
                'to_branch_id' => $toBranch,
                'to_warehouse_id' => $toWh,
                'transfer_date' => $request->transfer_date,
                'status' => $request->status,
                'note' => $request->note,
                'shipping_charge' => $shipping,
                'sub_total' => $subTotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'grand_total' => $grandTotal,
            ]);

            $transferProducts = [];

            foreach ($products as $item) {
                $stock = Stock::find($item['stock_id']);
                if (!$stock) {
                    return response()->json(['message' => "Invalid stock ID: {$item['stock_id']}"], 400);
                }

                // Ensure serial_numbers is array
                $serials = [];
                if (!empty($item['serial_numbers'])) {
                    $serials = is_array($item['serial_numbers']) ? $item['serial_numbers'] : json_decode($item['serial_numbers'], true);
                }

                $quantity = !empty($serials) ? count($serials) : $item['quantity']; // Use serial count if available

                $transferProducts[] = [
                    'transfer_id' => $transfer->id,
                    'stock_id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    // store serials as array or null
                    'serial_numbers' => !empty($serials) ? json_encode($serials) : null, // encode serials before insert
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Stock handling for completed transfers
                if ($request->status === 'completed') {
                    $fromStock = Stock::where('id', $stock->id)
                        ->when($fromBranch, fn($q) => $q->where('branch_id', $fromBranch))
                        ->when($fromWh, fn($q) => $q->where('warehouse_id', $fromWh))
                        ->first();

                    if (!$fromStock || $fromStock->productStock < $quantity) {
                        return response()->json(['message' => 'Insufficient stock for product ID: ' . $stock->product_id], 400);
                    }

                    // Remove serials from source
                    if (!empty($serials)) {
                        $existingSerials = is_array($fromStock->serial_numbers) ? $fromStock->serial_numbers : json_decode($fromStock->serial_numbers ?? '[]', true);
                        $fromStock->serial_numbers = array_values(array_diff($existingSerials, $serials));
                    }

                    $fromStock->decrement('productStock', $quantity);
                    $fromStock->save();

                    // Destination stock
                    $toStock = Stock::where('product_id', $fromStock->product_id)
                        ->when($toBranch, fn($q) => $q->where('branch_id', $toBranch))
                        ->when($toWh, fn($q) => $q->where('warehouse_id', $toWh))
                        ->where(function ($q) use ($fromStock) {
                            // if destination stock batch is NULL, skip batch compare
                            $q->whereNull('batch_no')
                                // otherwise match same batch
                                ->orWhere('batch_no', $fromStock->batch_no);
                        })
                        ->first();

                    if (!$toStock) {
                        $toStock = Stock::create([
                            'business_id' => $user->business_id,
                            'product_id' => $fromStock->product_id,
                            'branch_id' => $toBranch ?? $user->active_branch_id,
                            'warehouse_id' => $toWh,
//                            'batch_no' => $fromStock->batch_no,
                            'productStock' => 0,
                            'exclusive_price' => $fromStock->exclusive_price,
                            'productPurchasePrice' => $fromStock->productPurchasePrice,
                            'profit_percent' => $fromStock->profit_percent,
                            'productSalePrice' => $fromStock->productSalePrice,
                            'productWholeSalePrice' => $fromStock->productWholeSalePrice,
                            'productDealerPrice' => $fromStock->productDealerPrice,
                            'mfg_date' => $fromStock->mfg_date,
                            'expire_date' => $fromStock->expire_date,
                            // store serials as array or null
                            'serial_numbers' => !empty($serials) ? $serials : null,
                        ]);
                    }

                    // Increment stock
                    $toStock->increment('productStock', $quantity);

                    // Merge serials if exist
                    if (!empty($serials)) {
                        $toStockSerials = is_array($toStock->serial_numbers) ? $toStock->serial_numbers : json_decode($toStock->serial_numbers ?? '[]', true);
                        $toStock->serial_numbers = array_values(array_unique(array_merge($toStockSerials, $serials)));
                        $toStock->save();
                    }
                }
            }

            TransferProduct::insert($transferProducts);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $transfer,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'from_branch_id' => 'nullable|exists:branches,id',
            'to_branch_id' => 'nullable|exists:branches,id',
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'transfer_date' => 'required|date',
            'note' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled',
            'shipping_charge' => 'nullable|numeric|min:0',
            'products' => 'required|array|min:1',
            'products.*.quantity' => 'nullable|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.discount' => 'nullable|numeric|min:0',
            'products.*.tax' => 'nullable|numeric|min:0',
            'products.*.serial_numbers' => 'nullable|array',
        ]);

        $transfer = Transfer::findOrFail($id);

        if ($request->status === 'cancelled') {
            $transfer->update(['status' => 'cancelled']);

            return response()->json([
                'message' => __('Transfer cancelled successfully.'),
                'redirect' => route('business.transfers.index')
            ]);
        }

        $user = auth()->user();

        $fromBranch = $request->from_branch_id ?? $user->branch_id ?? $user->active_branch_id;
        $toBranch = $request->to_branch_id;
        $fromWh = $request->from_warehouse_id;
        $toWh = $request->to_warehouse_id;

        DB::beginTransaction();

        try {
            $oldStatus = $transfer->status;

            $subTotal = $totalDiscount = $totalTax = 0;

            $products = collect($request->products)->map(
                fn ($item, $key) => array_merge($item, ['stock_id' => $item['stock_id'] ?? $key])
            );

            // totals
            foreach ($products as $item) {
                $serials = !empty($item['serial_numbers'])
                    ? (is_array($item['serial_numbers']) ? $item['serial_numbers'] : json_decode($item['serial_numbers'], true))
                    : [];

                $qty = !empty($serials) ? count($serials) : $item['quantity'];

                $subTotal += $qty * $item['unit_price'];
                $totalTax += $item['tax'] ?? 0;
                $totalDiscount += $item['discount'] ?? 0;
            }

            $shipping   = $request->shipping_charge ?? 0;
            $grandTotal = $subTotal + $totalTax - $totalDiscount + $shipping;

            $transfer->update([
                'user_id' => auth()->id(),
                'from_branch_id' => $fromBranch,
                'to_branch_id' => $toBranch,
                'from_warehouse_id' => $fromWh,
                'to_warehouse_id' => $toWh,
                'transfer_date' => $request->transfer_date,
                'note' => $request->note,
                'status' => $request->status,
                'shipping_charge' => $shipping,
                'sub_total' => $subTotal,
                'total_discount' => $totalDiscount,
                'total_tax' => $totalTax,
                'grand_total' => $grandTotal,
            ]);

            TransferProduct::where('transfer_id', $transfer->id)->delete();

            $insertData = [];

            foreach ($products as $item) {
                $stock = Stock::find($item['stock_id']);
                if (!$stock) {
                    return response()->json(['message' => "Invalid stock ID: {$item['stock_id']}"], 400);
                }

                $serials = !empty($item['serial_numbers'])
                    ? (is_array($item['serial_numbers']) ? $item['serial_numbers'] : json_decode($item['serial_numbers'], true))
                    : [];

                $quantity = !empty($serials) ? count($serials) : $item['quantity'];

                $insertData[] = [
                    'transfer_id' => $transfer->id,
                    'stock_id' => $stock->id,
                    'product_id' => $stock->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'serial_numbers' => !empty($serials) ? json_encode($serials) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // move stock only pending → completed
                if ($oldStatus === 'pending' && $request->status === 'completed') {

                    $fromStock = Stock::where('id', $stock->id)
                        ->when($fromBranch, fn ($q) => $q->where('branch_id', $fromBranch))
                        ->when($fromWh, fn ($q) => $q->where('warehouse_id', $fromWh))
                        ->first();

                    if (!$fromStock || $fromStock->productStock < $quantity) {
                        return response()->json(['message' => 'Insufficient stock'], 400);
                    }

                    if (!empty($serials)) {
                        $existing = is_array($fromStock->serial_numbers)
                            ? $fromStock->serial_numbers
                            : json_decode($fromStock->serial_numbers ?? '[]', true);

                        $fromStock->serial_numbers = array_values(array_diff($existing, $serials));
                    }

                    $fromStock->decrement('productStock', $quantity);
                    $fromStock->save();

                    $toStock = Stock::where('product_id', $fromStock->product_id)
                        ->when($toBranch, fn ($q) => $q->where('branch_id', $toBranch))
                        ->when($toWh, fn ($q) => $q->where('warehouse_id', $toWh))
                        ->where(function ($q) use ($fromStock) {
                            // if destination stock batch is NULL, skip batch compare
                            $q->whereNull('batch_no')
                                // otherwise match same batch
                                ->orWhere('batch_no', $fromStock->batch_no);
                        })
                        ->first();

                    if (!$toStock) {
                        $toStock = Stock::create([
                            'business_id' => $user->business_id,
                            'product_id' => $fromStock->product_id,
                            'branch_id' => $toBranch ?? $user->active_branch_id,
                            'warehouse_id' => $toWh,
//                            'batch_no' => $fromStock->batch_no,
                            'productStock' => 0,
                            'exclusive_price' => $fromStock->exclusive_price,
                            'productPurchasePrice' => $fromStock->productPurchasePrice,
                            'profit_percent' => $fromStock->profit_percent,
                            'productSalePrice' => $fromStock->productSalePrice,
                            'productWholeSalePrice' => $fromStock->productWholeSalePrice,
                            'productDealerPrice' => $fromStock->productDealerPrice,
                            'mfg_date' => $fromStock->mfg_date,
                            'expire_date' => $fromStock->expire_date,
                            'serial_numbers' => !empty($serials) ? $serials : null,
                        ]);
                    }

                    if (!empty($serials)) {
                        $toExisting = is_array($toStock->serial_numbers)
                            ? $toStock->serial_numbers
                            : json_decode($toStock->serial_numbers ?? '[]', true);

                        $toStock->serial_numbers = array_values(
                            array_unique(array_merge($toExisting, $serials))
                        );
                    }

                    $toStock->increment('productStock', $quantity);
                    $toStock->save();
                }
            }

            TransferProduct::insert($insertData);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $transfer,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $transfer = Transfer::with([
            'fromBranch:id,name,phone,address',
            'toBranch:id,name,phone,address',
            'fromWarehouse:id,name,phone,address',
            'toWarehouse:id,name,phone,address',
            'transferProducts',
            'transferProducts.product:id,productName',
        ])
            ->where('business_id', auth()->user()->business_id)
            ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $transfer,
        ]);
    }

    public function destroy($id)
    {
        $business_id = auth()->user()->business_id;
        $transfer = Transfer::with('transferProducts')->where('business_id', $business_id)->findOrFail($id);

        DB::beginTransaction();

        try {
            if ($transfer->status == 'completed') {
                foreach ($transfer->transferProducts as $tp) {
                    $stock = Stock::find($tp->stock_id);

                    // check if destination has enough stock to rollback
                    $toStock = Stock::where('product_id', $tp->product_id)
                        ->where('warehouse_id', $transfer->to_warehouse_id)
                        ->where('branch_id', $transfer->to_branch_id)
                        ->where(function ($q) use ($stock) {
                            $q->whereNull('batch_no')
                                ->orWhere('batch_no', optional($stock)->batch_no);
                        })
                        ->first();

                    if (!$toStock || $toStock->quantity < $tp->quantity) {
                        return response()->json([
                            'message' => __('Cannot delete. Destination stock not enough to reverse this transfer'),
                        ], 422);
                    }
                }

                foreach ($transfer->transferProducts as $tp) {
                    $stock = Stock::find($tp->stock_id);

                    // decrease destination stock
                    $toStock = Stock::where('product_id', $tp->product_id)
                        ->where('warehouse_id', $transfer->to_warehouse_id)
                        ->where('branch_id', $transfer->to_branch_id)
                        ->where(function ($q) use ($stock) {
                            $q->whereNull('batch_no')
                                ->orWhere('batch_no', optional($stock)->batch_no);
                        })
                        ->first();
                    if ($toStock) {
                        $toStock->decrement('quantity', $tp->quantity);
                    }

                    // increase source stock
                    $fromStock = Stock::where('product_id', $tp->product_id)
                        ->where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('branch_id', $transfer->from_branch_id)
                        ->where(function ($q) use ($stock) {
                            $q->whereNull('batch_no')
                                ->orWhere('batch_no', optional($stock)->batch_no);
                        })
                        ->first();
                    if ($fromStock) {
                        $fromStock->increment('quantity', $tp->quantity);
                    }
                }
            }

            $transfer->delete();

            DB::commit();

            return response()->json([
                'message' => __('Data deleted successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('Error while deleting transfer: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function productsByLocation(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'branch_id'    => 'nullable|integer|exists:branches,id',
        ]);

        if (!$request->warehouse_id && !$request->branch_id) {
            return response()->json([
                'message' => 'Either warehouse_id or branch_id is required.'
            ], 422);
        }

        $products = Product::query()
            ->select([
                'products.id',
                'products.productName',
            ])

            // By warehouse
            ->when($request->warehouse_id, function ($q) use ($request) {
                $q->whereHas('stocks', function ($stockQuery) use ($request) {
                    $stockQuery->where('warehouse_id', $request->warehouse_id);
                });
            })

            // By branch
            ->when($request->branch_id, function ($q) use ($request) {
                $q->whereHas('stocks.warehouse', function ($warehouseQuery) use ($request) {
                    $warehouseQuery->where('branch_id', $request->branch_id);
                });
            })
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data'    => $products,
        ]);
    }
}
