<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->double('discountAmount')->default(0)->change();
            $table->double('dueAmount')->default(0)->change();
            $table->double('vat_amount')->default(0)->change();
            $table->double('vat_percent')->default(0)->change();
            $table->double('paidAmount')->default(0)->change();
            $table->double('totalAmount')->default(0)->change();
            $table->double('lossProfit')->default(0)->change();
            $table->double('rounding_amount')->default(0)->change();
            $table->double('actual_total_amount')->default(0)->change();
            $table->string('unique_id')->nullable()->after('id');
        });

        // sale_details table
        Schema::table('sale_details', function (Blueprint $table) {
            $table->double('price')->default(0)->change();
            $table->double('lossProfit')->default(0)->change();
        });

        // sale_return_details table
        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->double('return_amount')->default(0)->change();
            $table->double('return_qty')->default(0)->change();
        });

        // products table
        Schema::table('products', function (Blueprint $table) {
            $table->double('productDealerPrice')->default(0)->change();
            $table->double('productPurchasePrice')->default(0)->change();
            $table->double('productSalePrice')->default(0)->change();
            $table->double('productWholeSalePrice')->default(0)->change();
            $table->double('vat_amount')->default(0)->change();
        });

        // purchases table
        Schema::table('purchases', function (Blueprint $table) {
            $table->double("discountAmount")->default(0)->change();
            $table->double("dueAmount")->default(0)->change();
            $table->double("paidAmount")->default(0)->change();
            $table->double("totalAmount")->default(0)->change();
            $table->double('vat_amount')->default(0)->change();
            $table->double('vat_percent')->default(0)->change();
            $table->string('unique_id')->nullable()->after('id');
        });

        // purchase_details table
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->double('productDealerPrice')->default(0)->change();
            $table->double('productPurchasePrice')->default(0)->change();
            $table->double('productSalePrice')->default(0)->change();
            $table->double('productWholeSalePrice')->default(0)->change();
        });

        // purchase_return_details table
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->double('return_amount')->default(0)->change();
            $table->double('return_qty')->default(0)->change();
        });

        // parties table
        Schema::table('parties', function (Blueprint $table) {
            $table->double('due')->default(0)->change();
        });

        // expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->double('amount')->default(0)->change();
        });

        // incomes table
        Schema::table('incomes', function (Blueprint $table) {
            $table->double('amount')->change();
        });

        // due_collects table
        Schema::table('due_collects', function (Blueprint $table) {
            $table->double('totalDue')->default(0)->change();
            $table->double('dueAmountAfterPay')->default(0)->change();
            $table->double('payDueAmount')->default(0)->change();
            $table->string('unique_id')->nullable()->after('id');
        });

        // currencies table
        Schema::table('currencies', function (Blueprint $table) {
            $table->double('rate')->nullable()->change();
        });

        // user_currencies table
        Schema::table('user_currencies', function (Blueprint $table) {
            $table->double('rate')->nullable()->change();
        });

        // businesses table
        Schema::table('businesses', function (Blueprint $table) {
            $table->double('remainingShopBalance')->default(0)->change();
            $table->double('shopOpeningBalance')->default(0)->change();
        });

        // plans table
        Schema::table('plans', function (Blueprint $table) {
            $table->double('offerPrice')->nullable()->change();
            $table->double('subscriptionPrice')->default(0)->change();
        });

        // plan_subscribes table
        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->double('price')->default(0)->change();
        });

        // transfers table
        Schema::table('transfers', function (Blueprint $table) {
            $table->double('shipping_charge')->default(0)->change();
            $table->double('sub_total')->default(0)->change();
            $table->double('total_discount')->default(0)->change();
            $table->double('total_tax')->default(0)->change();
            $table->double('grand_total')->default(0)->change();
        });

        // transfer_products table
        Schema::table('transfer_products', function (Blueprint $table) {
            $table->double('unit_price')->default(0)->change();
            $table->double('discount')->default(0)->change();
            $table->double('tax')->default(0)->change();
        });

        // vat_transactions table
        Schema::table('vat_transactions', function (Blueprint $table) {
            $table->double('vat_rate')->change();
            $table->double('vat_amount')->change();
        });
    }

    public function down(): void
    {
        // sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->double('discountAmount', 10, 2)->default(0)->change();
            $table->double('dueAmount', 10, 2)->default(0)->change();
            $table->double('vat_amount', 10, 2)->default(0)->change();
            $table->double('vat_percent', 10, 2)->default(0)->change();
            $table->double('paidAmount', 10, 2)->default(0)->change();
            $table->double('totalAmount', 10, 2)->default(0)->change();
            $table->double('lossProfit', 10, 2)->default(0)->change();
            $table->double('rounding_amount', 10, 2)->default(0)->change();
            $table->double('actual_total_amount', 10, 2)->default(0)->change();
            $table->dropColumn('unique_id');
        });

        // sale_details table
        Schema::table('sale_details', function (Blueprint $table) {
            $table->double('price', 10, 2)->default(0)->change();
            $table->double('lossProfit', 10, 2)->default(0)->change();
        });

        // sale_return_details table
        Schema::table('sale_return_details', function (Blueprint $table) {
            $table->double('return_amount', 10, 2)->default(0)->change();
            $table->double('return_qty', 10, 2)->default(0)->change();
        });

        // products table
        Schema::table('products', function (Blueprint $table) {
            $table->double('productDealerPrice', 10, 2)->default(0)->change();
            $table->double('productPurchasePrice', 10, 2)->default(0)->change();
            $table->double('productSalePrice', 10, 2)->default(0)->change();
            $table->double('productWholeSalePrice', 10, 2)->default(0)->change();
            $table->double('vat_amount', 10, 2)->default(0)->change();
        });

        // purchases table
        Schema::table('purchases', function (Blueprint $table) {
            $table->double("discountAmount", 10, 2)->default(0)->change();
            $table->double("dueAmount", 10, 2)->default(0)->change();
            $table->double("paidAmount", 10, 2)->default(0)->change();
            $table->double("totalAmount", 10, 2)->default(0)->change();
            $table->double('vat_amount', 10, 2)->default(0)->change();
            $table->double('vat_percent', 10, 2)->default(0)->change();
            $table->dropColumn('unique_id');
        });

        // purchase_details table
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->double('productDealerPrice', 10, 2)->default(0)->change();
            $table->double('productPurchasePrice', 10, 2)->default(0)->change();
            $table->double('productSalePrice', 10, 2)->default(0)->change();
            $table->double('productWholeSalePrice', 10, 2)->default(0)->change();
        });

        // purchase_return_details table
        Schema::table('purchase_return_details', function (Blueprint $table) {
            $table->double('return_amount', 10, 2)->default(0)->change();
            $table->double('return_qty', 10, 2)->default(0)->change();
        });

        // parties table
        Schema::table('parties', function (Blueprint $table) {
            $table->double('due', 10, 2)->default(0)->change();
        });

        // expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->double('amount', 10, 2)->change();
        });

        // incomes table
        Schema::table('incomes', function (Blueprint $table) {
            $table->double('amount', 10, 2)->change();
        });

        // due_collects table
        Schema::table('due_collects', function (Blueprint $table) {
            $table->double('totalDue', 10, 2)->default(0)->change();
            $table->double('dueAmountAfterPay', 10, 2)->default(0)->change();
            $table->double('payDueAmount', 10, 2)->default(0)->change();
            $table->dropColumn('unique_id');
        });

        // currencies table
        Schema::table('currencies', function (Blueprint $table) {
            $table->double('rate', 10, 2)->nullable()->change();
        });

        // user_currencies table
        Schema::table('user_currencies', function (Blueprint $table) {
            $table->double('rate', 10, 2)->nullable()->change();
        });

        // businesses table
        Schema::table('businesses', function (Blueprint $table) {
            $table->double('remainingShopBalance', 10, 2)->default(0)->change();
            $table->double('shopOpeningBalance', 10, 2)->default(0)->change();
        });

        // plans table
        Schema::table('plans', function (Blueprint $table) {
            $table->double('offerPrice', 10, 2)->change();
            $table->double('subscriptionPrice', 10, 2)->default(0)->change();
        });

        // plan_subscribes table
        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->double('price', 10, 2)->default(0)->change();
        });

        // transfers table
        Schema::table('transfers', function (Blueprint $table) {
            $table->double('shipping_charge', 10, 2)->default(0)->change();
            $table->double('sub_total', 10, 2)->default(0)->change();
            $table->double('total_discount', 10, 2)->default(0)->change();
            $table->double('total_tax', 10, 2)->default(0)->change();
            $table->double('grand_total', 12, 2)->default(0)->change();
        });

        // transfer_products table
        Schema::table('transfer_products', function (Blueprint $table) {
            $table->double('unit_price', 10, 2)->default(0)->change();
            $table->double('discount', 10, 2)->default(0)->change();
            $table->double('tax', 10, 2)->default(0)->change();
        });

        // vat_transactions table
        Schema::table('vat_transactions', function (Blueprint $table) {
            $table->double('vat_rate', 10, 2)->change();
            $table->double('vat_amount', 10, 2)->change();
        });

    }
};