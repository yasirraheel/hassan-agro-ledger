<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRODUCTS
        Schema::table('products', function (Blueprint $table) {

            $table->double('price_without_tax')->after('productPurchasePrice')->nullable();

            safeDropForeignByName('products', 'products_vat_id_foreign');
            safeDropForeignByName('products', 'fk_products_vat_id');

            $table->foreign('vat_id', 'fk_products_vat_id')->references('id')->on('vats')->nullOnDelete();

            $table->string('hsn_code')->after('productCode')->nullable();
        });

        // STOCKS
        Schema::table('stocks', function (Blueprint $table) {

            $table->double('exclusive_price')->after('productPurchasePrice')->nullable();

            safeDropForeignByName('stocks', 'stocks_warehouse_id_foreign');
            safeDropForeignByName('stocks', 'fk_stocks_warehouse_id');

            $table->foreign('warehouse_id', 'fk_stocks_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        // TRANSFERS
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('user_id')->after('business_id')->nullable();
        });

        // TRANSFER PRODUCTS
        Schema::table('transfer_products', function (Blueprint $table) {
            $table->longText('serial_numbers')->after('tax')->nullable();
        });

        // SALE DETAILS
        Schema::table('sale_details', function (Blueprint $table) {
            $table->longText('serial_numbers')->after('stock_id')->nullable();
            $table->double('price_without_tax')->after('product_id')->default(0);
        });

        // PURCHASE DETAILS
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->longText('serial_numbers')->after('stock_id')->nullable();
            $table->double('price_without_tax')->after('product_id')->default(0);
        });

        // SALE RETURN DETAILS
        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->longText('serial_numbers')->after('return_qty')->nullable();
        });

        // PURCHASE RETURN DETAILS
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->longText('serial_numbers')->after('return_qty')->nullable();
        });

        // VATS
        Schema::table('vats', function (Blueprint $table) {
            $table->boolean('manage_state')->after('rate')->default(0);
        });

        // PARTIES
        Schema::table('parties', function (Blueprint $table) {
            $table->string('tax_no')->after('billing_address')->nullable();
            $table->foreignId('country_id')->after('tax_no')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->after('country_id')->nullable()->constrained('states')->nullOnDelete();
        });

        // BUSINESSES
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('country_id')->after('shopOpeningBalance')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->after('country_id')->nullable()->constrained('states')->nullOnDelete();
        });

        // BRANCHES
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('country_id')->after('branchRemainingBalance')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->after('country_id')->nullable()->constrained('states')->nullOnDelete();
        });

        // LANGUAGES
        Schema::table('languages', function (Blueprint $table) {
            $table->string('code')->after('icon')->nullable();
            $table->integer('order')->after('code')->default(0);
            $table->boolean('is_default')->after('order')->default(0);
        });
    }

    public function down(): void
    {
        // PRODUCTS
        Schema::table('products', function (Blueprint $table) {

            $table->dropColumn(['price_without_tax', 'hsn_code']);

            safeDropForeignByName('products', 'fk_products_vat_id');
            safeDropForeignByName('products', 'products_vat_id_foreign');

            $table->foreign('vat_id')->references('id')->on('vats')->cascadeOnDelete();
        });


        // STOCKS
        Schema::table('stocks', function (Blueprint $table) {

            $table->dropColumn('exclusive_price');

            safeDropForeignByName('stocks', 'fk_stocks_warehouse_id');
            safeDropForeignByName('stocks', 'stocks_warehouse_id_foreign');

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });


        // TRANSFERS
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });


        // TRANSFER PRODUCTS
        Schema::table('transfer_products', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });


        // SALE DETAILS
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['serial_numbers', 'price_without_tax']);
        });


        // PURCHASE DETAILS
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropColumn(['serial_numbers', 'price_without_tax']);
        });


        // SALE RETURN DETAILS
        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });


        // PURCHASE RETURN DETAILS
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });


        // VATS
        Schema::table('vats', function (Blueprint $table) {
            $table->dropColumn('manage_state');
        });


        // PARTIES
        Schema::table('parties', function (Blueprint $table) {

            safeDropForeignByName('parties', 'parties_country_id_foreign');
            safeDropForeignByName('parties', 'parties_state_id_foreign');

            $table->dropColumn(['tax_no', 'country_id', 'state_id']);
        });


        // BUSINESSES
        Schema::table('businesses', function (Blueprint $table) {

            safeDropForeignByName('businesses', 'businesses_country_id_foreign');
            safeDropForeignByName('businesses', 'businesses_state_id_foreign');

            $table->dropColumn(['country_id', 'state_id']);
        });

        // BRANCHES
        Schema::table('branches', function (Blueprint $table) {

            safeDropForeignByName('branches', 'branches_country_id_foreign');
            safeDropForeignByName('branches', 'branches_state_id_foreign');

            $table->dropColumn(['country_id', 'state_id']);
        });

        // LANGUAGES
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn(['order', 'code', 'is_default']);
        });
    }
};