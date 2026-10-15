<?php

use App\Models\Option;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return back()->with('success', __('Cache has been cleared.'));
});

Route::get('/update', function () {
    $version = Option::where('key', 'version')->value('value') ?? null;

    if ($version == '6.1') {
        DB::statement("
            UPDATE stocks
            SET warehouse_id = NULL
            WHERE warehouse_id IS NOT NULL
            AND warehouse_id NOT IN (SELECT id FROM warehouses)
        ");
    }

    // Run migrations if needed
    Artisan::call('migrate');

    if (file_exists(base_path('storage/installed'))) {
        touch(base_path('vendor/autoload1.php'));
    }

    if (Schema::hasTable('stocks') && !Stock::exists()) {
        Product::chunk(500, function ($products) {
            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    'business_id' => $product->business_id,
                    'product_id'  => $product->id,
                    'expire_date' => $product->expire_date ?? null,
                    'productStock' => $product->productStock,
                    'profit_percent' => $product->profit_percent,
                    'productDealerPrice' => $product->productDealerPrice,
                    'productPurchasePrice' => $product->productPurchasePrice,
                    'productSalePrice' => $product->productSalePrice,
                    'productWholeSalePrice' => $product->productWholeSalePrice,
                    'created_at'  => $product->created_at,
                    'updated_at'  => $product->updated_at,
                ];
            }
            Stock::insert($data);
        });
    }

    // Run update file if version is not 5.5
    if ($version == 5.4) {
        $updateFile = base_path('updates/v5_5_update.php');
        if (file_exists($updateFile)) {
            require $updateFile;
            if (function_exists('runUpdate')) {
                runUpdate();
            }
        }
    }

    // Run update file for version 6.0 only
    if ($version == '5.6.2') {
        $v6_0_file = base_path('updates/v6_0_update.php');
        if (file_exists($v6_0_file)) {
            require $v6_0_file;
            if (function_exists('runUpdateForV6')) {
                runUpdateForV6();
            }
        }
    }

    if ($version == '6.1') {
        $v6_2_file = base_path('updates/v6_2_update.php');
        if (file_exists($v6_2_file)) {
            require $v6_2_file;
            if (function_exists('runUpdateForV6_2')) {
                runUpdateForV6_2();
            }
        }
    }

    // Update version
    Option::updateOrCreate(
        ['key' => 'version'],
        ['value' => '6.9']
    );

    // Clear caches
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return redirect('/')->with('message', __('System updated successfully.'));
});
