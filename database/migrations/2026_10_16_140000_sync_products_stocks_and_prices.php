<?php

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sync products that have records in stocks table
        $products = Product::with(['stocks' => function ($q) {
            $q->withoutGlobalScopes();
        }])->get();

        foreach ($products as $product) {
            if ($product->stocks->isNotEmpty()) {
                $product->syncStockAndPrices();
            } elseif ($product->productStock > 0 || $product->productSalePrice > 0 || $product->productPurchasePrice > 0) {
                // If product has prices/stock in products table but missing stocks record, create default stock
                Stock::create([
                    'business_id' => $product->business_id,
                    'product_id' => $product->id,
                    'batch_no' => $product->productCode ?: str_pad($product->id, 4, '0', STR_PAD_LEFT),
                    'productStock' => $product->productStock ?? 0,
                    'exclusive_price' => $product->productPurchasePrice ?? 0,
                    'productPurchasePrice' => $product->productPurchasePrice ?? 0,
                    'productSalePrice' => $product->productSalePrice ?? 0,
                    'productWholeSalePrice' => $product->productWholeSalePrice ?? $product->productSalePrice ?? 0,
                    'productDealerPrice' => $product->productDealerPrice ?? $product->productSalePrice ?? 0,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
