<?php

use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function runUpdateForV6_2()
{
    DB::statement('SET SESSION sql_big_selects=1');
    DB::statement('SET SESSION innodb_lock_wait_timeout=600');

    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('languages')->delete();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    DB::transaction(function () {

        DB::statement("
            UPDATE stocks s
            INNER JOIN products p ON p.id = s.product_id
            LEFT JOIN vats v ON v.id = p.vat_id
            SET 
                s.exclusive_price = CASE
                    WHEN p.vat_type = 'inclusive' AND v.rate > 0 THEN ROUND(s.productPurchasePrice / (1 + (v.rate / 100)), 6)
                    ELSE s.productPurchasePrice
                END,
                s.productPurchasePrice = CASE
                    WHEN p.vat_type = 'exclusive' AND v.rate > 0 THEN ROUND(s.productPurchasePrice * (1 + (v.rate / 100)), 6)
                    ELSE s.productPurchasePrice
                END
            WHERE p.product_type != 'combo'
        ");

        DB::statement("
            UPDATE products p
            LEFT JOIN vats v ON v.id = p.vat_id
            SET 
                p.price_without_tax = CASE
                    WHEN p.vat_type = 'inclusive' AND v.rate > 0 THEN ROUND(p.productSalePrice / (1 + (v.rate / 100)), 6)
                    ELSE p.productSalePrice
                END,
                p.productSalePrice = CASE
                    WHEN p.vat_type = 'exclusive' AND v.rate > 0 THEN ROUND(p.productSalePrice * (1 + (v.rate / 100)), 6)
                    ELSE p.productSalePrice
                END
            WHERE p.product_type = 'combo'
        ");

        Artisan::call('db:seed', [
            '--class' => LanguageSeeder::class,
            '--force' => true
        ]);
    });
}