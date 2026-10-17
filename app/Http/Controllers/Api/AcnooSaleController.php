<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Party;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\Vat;
use App\Models\VatTransaction;
use App\Services\VatTransactionService;
use App\Traits\DateFilterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AcnooSaleController extends Controller
{
    use HasUploader;
    Use DateFilterTrait;

    public function index(Request $request)
    {
        $business_id = auth()->user()->business_id;

        // Build the query with all eager loads
        $query = Sale::with([
            'user:id,name,role',
            'party:id,name,email,phone,type,address',
            'details',
            'details.stock:id,batch_no,productStock,variant_name',
            'details.product:id,productName,category_id,productCode,productPurchasePrice,productStock,product_type,has_serial',
            'details.product.category:id,categoryName',
            'saleReturns.details',
            'vat:id,name,rate',
            'branch:id,name,phone,address',
            'transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta',
            'transactions.paymentType:id,name,meta'
        ])->where('business_id', $business_id);

        // Filter returned sales
        $query->when(request('returned-sales') == "true", function ($query) {
            $query->whereHas('saleReturns');
        });

        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Apply date filter
        if(request('duration')){
            $this->applyDateFilter($query, request('duration'), 'saleDate', request('from_date'), request('to_date'));
        }

        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoiceNumber', 'like', "%{$request->search}%")
                    ->orWhere('paymentType', 'like', "%{$request->search}%")
                    ->orWhereHas('party', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('payment_type', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('branch', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            });
        }

        $data = $query->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_amount' => $data->sum('totalAmount'),
            'data' => $data,
        ]);
    }

    public function show(string $id)
    {
        $business_id = auth()->user()->business_id;

        $sale = Sale::with(['user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.stock:id,batch_no,productStock,variant_name', 'details.product:id,productName,category_id,productCode,productPurchasePrice,productStock,product_type,has_serial', 'details.product.category:id,categoryName', 'saleReturns.details', 'vat:id,name,rate', 'branch:id,name,phone,address', 'transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name,meta'])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        if (!$sale) {
            return response()->json([
                'message' => __('Sale not found.'),
            ], 404);
        }

        return response()->json([
            'message' => __('Sale fetched successfully.'),
            'data' => $sale,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'vat_id' => 'nullable|exists:vats,id',
            'products' => 'required',
            'saleDate' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'rounding_option' => 'nullable|in:none,round_up,nearest_whole_number,nearest_0.05,nearest_0.1,nearest_0.5',
        ]);

        DB::beginTransaction();
        try {
            $business = Business::findOrFail(auth()->user()->business_id);
            $request_products = json_decode($request->products, true);
            $payments = json_decode($request->payments, true);

            // Always resolve the selected party so paid sales can send notifications too.
            // Previously this variable was only populated for sales with a due amount.
            $party = $request->filled('party_id')
                ? Party::where('business_id', $business->id)->find($request->party_id)
                : null;

            $paidAmount = collect($payments)
                ->reject(fn($p) => strtolower($p['type'] ?? '') === 'cheque')
                ->sum(fn($p) => $p['amount'] ?? 0);

            if ($request->dueAmount > 0) {
                $allowDueForGuest = product_setting()?->modules['allow_due_sale'] ?? false;

                // No party
                if (!$request->party_id) {
                    if (!$allowDueForGuest) {
                        return response()->json([
                            'message' => __('You have not allowed guest sales, so you cannot sell on due for a walking customer.')
                        ], 400);
                    }
                } else {
                    // Party exist
                    $party = Party::where('business_id', $business->id)->findOrFail($request->party_id);
                    $newDue = $party->due + $request->dueAmount;

                    // Check credit limit
                    if ($party->credit_limit > 0 && $newDue > $party->credit_limit) {
                        return response()->json([
                            'message' => __('Sale cannot be created. Party due will exceed credit limit!')
                        ], 400);
                    }

                    $party->update(['due' => $newDue]);
                }
            }

            updateBalance($paidAmount, 'increment');

            $sale = Sale::create($request->except('image', 'isPaid', 'lossProfit', 'payment_type_id') + [
                'user_id' => auth()->id(),
                'business_id' => $business->id,
                'isPaid' => filter_var($request->isPaid, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'image' => $request->image ? $this->upload($request, 'image') : null,
                'meta' => [
                    'note' => $request->note,
                ],
            ]);

            $avgDiscount = $request->discountAmount / max(count($request_products), 1);

            $subtotal = 0;
            $totalLossProfit = 0;
            $totalPurchaseAmount = 0;

            foreach ($request_products as $productData) {
                $productDiscount = $productData['discount'] ?? 0;

                $product = Product::findOrFail($productData['product_id']);
                $qty = $productData['quantities'] ?? 1;
                $price = $productData['price'] ?? 0;

                $lineSubtotal = ($price * $qty) - $productDiscount;
                $subtotal += $lineSubtotal;

                $actualPrice = $price - $productDiscount;

                //  Combo Products
                if ($product->product_type == 'combo') {

                    $combo_purchase_price = $product->combo_products->sum('purchase_price');

                    foreach ($product->combo_products as $comboItem) {
                        $stock = Stock::findOrFail($comboItem->stock_id);
                        $requiredQty = $comboItem->quantity * $qty;

                        if ($stock->productStock < $requiredQty) {
                            return response()->json([
                                'message' => "Combo item '{$productData['product_name']}' (Batch: {$stock->batch_no}) not available. Available stock: {$stock->productStock}"
                            ], 400);
                        }

                        // Decrement stock
                        $stock->decrement('productStock', $requiredQty);
                    }

                    // Store combo in SaleDetails
                    $detail = SaleDetails::create([
                        'sale_id' => $sale->id,
                        'stock_id' => null,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'quantities' => $qty,
                        'expire_date' => null,
                        'serial_numbers' => null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info ?? null,
                        'productPurchasePrice' => $combo_purchase_price,
                    ]);

                    $vatAmount = VatTransactionService::storeVatTransactions($business, $detail, $product, $actualPrice, $party ?? null);
                    $price_without_tax = number_format($actualPrice - ($vatAmount / max($qty, 1)), 3, '.', '');

                    $lossProfit = ($price_without_tax * $qty) - ($combo_purchase_price * $qty) - $avgDiscount;
                    $totalLossProfit += $lossProfit;

                    $detail->update([
                        'lossProfit' => $lossProfit,
                        'price_without_tax' => $price_without_tax,
                    ]);

                } // Single / Variant Products
                else {
                    $stock = Stock::findOrFail($productData['stock_id']);

                    $newSerials = $productData['serial_numbers'] ?? [];
                    $existingSerials = $stock->serial_numbers ?? [];

                    $remainingSerials = !empty($newSerials)
                        ? array_values(array_diff($existingSerials, $newSerials))
                        : $existingSerials;

                    $finalQty = count($newSerials) > 0 ? count($newSerials) : $qty;

                    if ($stock->productStock < $finalQty) {
                        return response()->json([
                            'message' => "Stock not available for product: {$productData['product_name']}. Available: {$stock->productStock}"
                        ], 400);
                    }

                    // Determine quantity to decrement stock
                    $finalQty = count($newSerials) > 0 ? count($newSerials) : $qty;

                    if ($stock->productStock < $finalQty) {
                        return response()->json([
                            'message' => "Stock not available for product: {$productData['product_name']}. Available: {$stock->productStock}"
                        ], 400);
                    }

                    // Decrement stock
                    $stock->decrement('productStock', $finalQty);

                    // Update stock serial numbers
                    $stock->update(['serial_numbers' => $remainingSerials]);

                    $purchasePrice = $stock->productPurchasePrice ?? 0;

                    // Calculate profit/loss
                    $totalPurchaseAmount += ($purchasePrice * $finalQty);

                    $detail = SaleDetails::create([
                        'sale_id' => $sale->id,
                        'stock_id' => $stock->id,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'quantities' => $finalQty,
                        'productPurchasePrice' => $purchasePrice,
                        'expire_date' => $stock->expire_date ?? null,
                        'serial_numbers' => !empty($newSerials) ? $newSerials : null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info ?? null,
                    ]);

                    $vatAmount = VatTransactionService::storeVatTransactions($business, $detail, $product, $actualPrice, $party ?? null);
                    $price_without_tax = number_format($actualPrice - ($vatAmount / $finalQty), 3, '.', '');

                    $lossProfit = ($price_without_tax * $finalQty) - ($stock->exclusive_price * $finalQty) - $avgDiscount;
                    $totalLossProfit += $lossProfit;

                    $detail->update([
                        'lossProfit' => $lossProfit,
                        'price_without_tax' => $price_without_tax,
                    ]);
                }
            }

            $sale->update([
                'lossProfit' => $totalLossProfit,
            ]);

            // MultiPaymentProcessed Event
            event(new MultiPaymentProcessed(
                $payments,
                $sale->id,
                'sale',
                $paidAmount,
                $request->party_id ?? null,
                date: $sale->saleDate
            ));

            if (moduleCheck('MarketingAddon')) {
                $this->saleMessage($business, $party ?? null, $sale);
            }

            DB::commit();
            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $sale->load('user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.stock:id,batch_no,variant_name', 'details.product:id,productName,category_id,product_type,has_serial', 'details.product.category:id,categoryName', 'saleReturns.details', 'vat:id,name,rate', 'payment_type:id,name', 'branch:id,name,phone,address'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'vat_id' => 'nullable|exists:vats,id',
            'products' => 'required',
            'saleDate' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'rounding_option' => 'nullable|in:none,round_up,nearest_whole_number,nearest_0.05,nearest_0.1,nearest_0.5',
        ]);

        $sale = Sale::findOrFail($id);
        $business = Business::findOrFail(auth()->user()->business_id);

        DB::beginTransaction();
        try {
            if ($sale->load('saleReturns')->saleReturns->count() > 0) {
                return response()->json([
                    'message' => __("You cannot update this sale because it has already been returned.")
                ], 400);
            }

            $request_products = json_decode($request->products, true);
            $payments = json_decode($request->payments, true);
            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();

            // Rollback previous stock & serial numbers
            foreach ($prevDetails as $prevItem) {
                if ($prevItem->stock_id) {
                    $stock = Stock::findOrFail($prevItem->stock_id);
                    $stock->increment('productStock', $prevItem->quantities);

                    // rollback serials
                    if (!empty($prevItem->serial_numbers)) {

                        $prevSerials = is_array($prevItem->serial_numbers) ? $prevItem->serial_numbers : json_decode($prevItem->serial_numbers, true);

                        $existingSerials = $stock->serial_numbers ?? [];

                        $stock->serial_numbers = array_values(array_diff($existingSerials, $prevSerials ?? []));
                        $stock->save();
                    }

                }
            }

            $prevDetails->each->delete();

            $avgDiscount = $request->discountAmount / max(count($request_products), 1);

            $totalLossProfit = 0;
            $totalProductWiseDiscount = 0;

            foreach ($request_products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $qty = $productData['quantities'] ?? 1;
                $price = $productData['price'] ?? 0;
                $productDiscount = $productData['discount'] ?? 0;
                $totalProductWiseDiscount += $productDiscount;

                $unitSalePrice = $price;
                $unitDiscount  = $productDiscount / max($qty, 1);
                $actualPrice = $unitSalePrice - $unitDiscount;

                // Combo product
                if ($product->product_type == 'combo') {

                    $combo_purchase_price = $product->combo_products->sum('purchase_price');

                    foreach ($product->combo_products as $comboItem) {
                        $stock = Stock::findOrFail($comboItem->stock_id);
                        $requiredQty = $comboItem->quantity * $qty;

                        if ($stock->productStock < $requiredQty) {
                            return response()->json([
                                'message' => "Combo item '{$productData['product_name']}' (Batch: {$stock->batch_no}) not available. Available stock: {$stock->productStock}"
                            ], 400);
                        }

                        // Decrement stock
                        $stock->decrement('productStock', $requiredQty);
                    }

                    $detail = SaleDetails::create([
                        'sale_id' => $sale->id,
                        'stock_id' => null,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'quantities' => $qty,
                        'expire_date' => null,
                        'serial_numbers' => null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info ?? null,
                        'productPurchasePrice' => $combo_purchase_price,
                    ]);

                    $vatAmount = VatTransactionService::storeVatTransactions($business, $detail, $product, $actualPrice, $party ?? null);
                    $price_without_tax = number_format($actualPrice - ($vatAmount / max($qty, 1)), 3, '.', '');

                    $lossProfit = ($price_without_tax * $qty) - ($combo_purchase_price * $qty) - $avgDiscount;
                    $totalLossProfit += $lossProfit;

                    $detail->update([
                        'lossProfit' => $lossProfit,
                        'price_without_tax' => $price_without_tax,
                    ]);

                } // Single / Variant product
                else {
                    $stock = Stock::findOrFail($productData['stock_id']);

                    //Serial numbers
                    $newSerials = $productData['serial_numbers'] ?? [];
                    $existingSerials = $stock->serial_numbers ?? [];

                    $mergedSerials = array_values(
                        array_unique(array_merge($existingSerials, $newSerials))
                    );

                    $finalQty = count($newSerials) > 0 ? count($newSerials) : $qty;

                    if ($stock->productStock < $finalQty) {
                        return response()->json([
                            'message' => "Stock not available for product: {$productData['product_name']}."
                        ], 400);
                    }

                    $stock->decrement('productStock', $finalQty);

                    if (!empty($newSerials)) {
                        $stock->update(['serial_numbers' => $mergedSerials]);
                    }

                    $purchaseUnit = $stock->productPurchasePrice ?? 0;

                    $detail = SaleDetails::create([
                        'sale_id' => $sale->id,
                        'stock_id' => $stock->id,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'quantities' => $finalQty,
                        'productPurchasePrice' => $purchaseUnit,
                        'expire_date' => $stock->expire_date ?? null,
                        'serial_numbers' => !empty($newSerials) ? $newSerials : null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info ?? null,
                    ]);
                    $vatAmount = VatTransactionService::storeVatTransactions($business, $detail, $product, $actualPrice, $party ?? null);
                    $price_without_tax = number_format($actualPrice - ($vatAmount / max($finalQty, 1)), 3, '.', '');

                    $lossProfit = ($price_without_tax * $finalQty) - ($stock->exclusive_price * $finalQty) - $avgDiscount;
                    $totalLossProfit += $lossProfit;

                    $detail->update([
                        'lossProfit' => $lossProfit,
                        'price_without_tax' => $price_without_tax,
                    ]);
                }
            }

            // Check if any old transaction type = deposit
            $hasDeposit = Transaction::where('business_id', $business->id)
                ->where('reference_id', $sale->id)
                ->where('platform', 'sale')
                ->where('type', 'deposit')
                ->exists();

            $paidAmount = collect($payments)
                ->reject(function ($p) use ($hasDeposit) {
                    // exclude cheque only if not deposit
                    return !$hasDeposit && strtolower($p['type'] ?? '') === 'cheque';
                })
                ->sum(fn($p) => $p['amount'] ?? 0);

            $dueAmount = max($request->totalAmount - $paidAmount, 0);

            // Handle party due
            $allowDueForGuest = product_setting()?->modules['allow_due_sale'] ?? false;
            if ($sale->dueAmount || $dueAmount) {
                // If no party in request, check if guest due is allowed
                if (!$request->party_id) {
                    if (!$allowDueForGuest) {
                        return response()->json([
                            'message' => __('You have not allowed guest sales, so you cannot sell on due for a walking customer.')
                        ], 400);
                    }
                } else {
                    $party = Party::where('business_id', $business->id)->findOrFail($request->party_id);

                    $newDue = $request->party_id == $sale->party_id
                        ? (($party->due - $sale->dueAmount) + $dueAmount)
                        : ($party->due + $dueAmount);

                    if ($party->credit_limit > 0 && $newDue > $party->credit_limit) {
                        return response()->json([
                            'message' => __("Cannot update sale. Party due will exceed credit limit!")
                        ], 400);
                    }

                    $party->update(['due' => $newDue]);

                    if ($request->party_id != $sale->party_id && $sale->party_id) {
                        $prev_party = Party::where('business_id', $business->id)->findOrFail($sale->party_id);
                        $prev_party->update([
                            'due' => $prev_party->due - $sale->dueAmount
                        ]);
                    }
                }
            }

            // Adjust business balance
            $balanceDiff = $paidAmount - $sale->paidAmount;
            updateBalance($balanceDiff, 'increment');

            $sale->update($request->except('image', 'isPaid', 'paidAmount', 'paidAmount', 'dueAmount') + [
                    'user_id' => auth()->id(),
                    'lossProfit' => $totalLossProfit,
                    'isPaid' => filter_var($request->isPaid, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                    'image' => $request->image ? $this->upload($request, 'image', $sale->image) : $sale->image,
                    'paidAmount' => $paidAmount > $request->totalAmount ? $request->totalAmount : $paidAmount,
                    'dueAmount' => $dueAmount,
                    'meta' => [
                        'note' => $request->note,
                    ],
                ]);

            // Multiple Payment Process
            $oldTransactions = Transaction::where('business_id', $business->id)
                ->where('reference_id', $sale->id)
                ->where('platform', 'sale')
                ->get();

            // Revert old transactions
            foreach ($oldTransactions as $old) {
                switch ($old->transaction_type) {
                    case 'bank_payment':
                        $paymentType = PaymentType::find($old->payment_type_id);
                        if ($paymentType) {
                            $paymentType->decrement('balance', $old->amount);
                        }
                        break;

                    case 'wallet_payment':
                        if ($request->party_id) {
                            $party = Party::find($request->party_id);
                            if ($party) {
                                $party->increment('wallet', $old->amount);
                            }
                        }
                        break;

                    case 'cash_payment':
                    case 'cheque_payment':
                        // nothing to revert for cash/cheque
                        break;
                }
            }

            // Delete old transactions
            Transaction::where('business_id', $business->id)
                ->where('reference_id', $sale->id)
                ->where('platform', 'sale')
                ->delete();

            // Process new payments
            event(new MultiPaymentProcessed(
                $payments ?? [],
                $sale->id,
                'sale',
                $paidAmount,
                $request->party_id ?? null,
                date: $sale->saleDate,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Sale updated successfully.'),
                'data' => $sale->load(
                    'details',
                    'details.product:id,productName,product_type,has_serial',
                    'details.stock:id,batch_no,variant_name',
                    'party:id,name,email,phone,address',
                    'vat:id,name,rate',
                    'branch:id,name,phone,address'
                ),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        foreach ($sale->details as $item) {
            VatTransaction::where('vatable_id', $item->id)
                ->where('vatable_type', get_class($item))
                ->delete();

            Stock::findOrFail($item->stock_id)->increment('productStock', $item->quantities);
        }

        if ($sale->party_id) {
            $party = Party::where('business_id', $sale->business_id)->findOrFail($sale->party_id);
            $party->update([
                'due' => $party->due - $sale->dueAmount
            ]);
        }

        updateBalance($sale->paidAmount, 'decrement');

        if (file_exists($sale->image)) {
            Storage::delete($sale->image);
        }

        $sale->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
