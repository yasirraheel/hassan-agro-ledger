<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PARTIES TABLE
        Schema::table('parties', function (Blueprint $table) {
            $table->double('credit_limit')->default(0)->after('due');
            $table->double('loyalty_points')->default(0)->after('due');
            $table->double('wallet')->default(0)->after('due'); // advance amount
            $table->double('opening_balance')->default(0)->after('due');
            $table->string('opening_balance_type')->nullable()->after('due'); // advance / due
            $table->text('billing_address')->nullable()->after('status');
            $table->text('shipping_address')->nullable()->after('status');
            $table->text('meta')->nullable()->after('status');
        });

        // USERS TABLE
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('active_branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });

        // STOCKS TABLE
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('product_settings', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });

        // EXPENSES
        Schema::table('expenses', function (Blueprint $table) {

            safeDropForeignByName('expenses', 'expenses_user_id_foreign');
            safeDropForeignByName('expenses', 'fk_expenses_user_id');

            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('expenses', function (Blueprint $table) {

            $table->foreign('user_id', 'fk_expenses_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });


        // INCOMES
        Schema::table('incomes', function (Blueprint $table) {

            safeDropForeignByName('incomes', 'incomes_user_id_foreign');
            safeDropForeignByName('incomes', 'fk_incomes_user_id');

            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('incomes', function (Blueprint $table) {

            $table->foreign('user_id', 'fk_incomes_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });


        // SALES
        Schema::table('sales', function (Blueprint $table) {

            safeDropForeignByName('sales', 'sales_party_id_foreign');
            safeDropForeignByName('sales', 'sales_user_id_foreign');

            safeDropForeignByName('sales', 'fk_sales_party_id');
            safeDropForeignByName('sales', 'fk_sales_user_id');

            $table->unsignedBigInteger('party_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('sales', function (Blueprint $table) {

            $table->foreign('party_id', 'fk_sales_party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('user_id', 'fk_sales_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });


        // PURCHASES
        Schema::table('purchases', function (Blueprint $table) {

            safeDropForeignByName('purchases', 'purchases_party_id_foreign');
            safeDropForeignByName('purchases', 'purchases_user_id_foreign');

            safeDropForeignByName('purchases', 'fk_purchases_party_id');
            safeDropForeignByName('purchases', 'fk_purchases_user_id');

            $table->unsignedBigInteger('party_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('purchases', function (Blueprint $table) {

            $table->foreign('party_id', 'fk_purchases_party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('user_id', 'fk_purchases_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });


        // DUE COLLECTS
        Schema::table('due_collects', function (Blueprint $table) {

            safeDropForeignByName('due_collects', 'due_collects_party_id_foreign');
            safeDropForeignByName('due_collects', 'due_collects_user_id_foreign');
            safeDropForeignByName('due_collects', 'due_collects_sale_id_foreign');
            safeDropForeignByName('due_collects', 'due_collects_purchase_id_foreign');

            safeDropForeignByName('due_collects', 'fk_due_collects_party_id');
            safeDropForeignByName('due_collects', 'fk_due_collects_user_id');
            safeDropForeignByName('due_collects', 'fk_due_collects_sale_id');
            safeDropForeignByName('due_collects', 'fk_due_collects_purchase_id');

            $table->unsignedBigInteger('party_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('sale_id')->nullable()->change();
            $table->unsignedBigInteger('purchase_id')->nullable()->change();
        });

        Schema::table('due_collects', function (Blueprint $table) {

            $table->foreign('party_id', 'fk_due_collects_party_id')->references('id')->on('parties')->nullOnDelete();
            $table->foreign('user_id', 'fk_due_collects_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sale_id', 'fk_due_collects_sale_id')->references('id')->on('sales')->nullOnDelete();
            $table->foreign('purchase_id', 'fk_due_collects_purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
        });

        // Plan
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allow_multibranch')->default(0)->after('status');
            $table->integer('addon_domain_limit')->nullable()->after('allow_multibranch');
            $table->integer('subdomain_limit')->nullable()->after('addon_domain_limit');
        });

        // PLAN SUBSCRIBED
        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->boolean('allow_multibranch')->default(0)->after('price');
            $table->integer('addon_domain_limit')->default(0)->after('price');
            $table->integer('subdomain_limit')->default(0)->after('price');
        });

        // BUSINESS TABLE
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('email')->nullable()->after('address');
            $table->boolean('status')->default(1)->after('vat_name');
        });

        // TRANSFERS
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('from_branch_id')->nullable()->after('business_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->nullable()->after('from_warehouse_id')->constrained('branches')->nullOnDelete();
        });

        // TRANSFER PRODUCTS
        Schema::table('transfer_products', function (Blueprint $table) {

            safeDropForeignByName('transfer_products', 'transfer_products_warehouse_id_foreign');
            safeDropForeignByName('transfer_products', 'fk_transfer_products_warehouse_id');

            $table->foreignId('stock_id')->nullable()->after('transfer_id')->constrained('stocks')->nullOnDelete();
        });

        // Products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('rack_id')->nullable()->after('business_id')->constrained('racks')->nullOnDelete();
            $table->foreignId('shelf_id')->nullable()->after('rack_id')->constrained('shelves')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // PARTIES
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'loyalty_points', 'wallet', 'opening_balance', 'opening_balance_type', 'billing_address', 'shipping_address', 'meta']);
        });

        // USERS
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'active_branch_id']);
        });

        // STOCKS
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'warehouse_id']);
        });

        Schema::table('product_settings', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        // EXPENSES
        Schema::table('expenses', function (Blueprint $table) {

            safeDropForeignByName('expenses', 'fk_expenses_user_id');
            safeDropForeignByName('expenses', 'expenses_user_id_foreign');

            $table->dropColumn('branch_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('user_id', 'expenses_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        // INCOMES
        Schema::table('incomes', function (Blueprint $table) {

            safeDropForeignByName('incomes', 'fk_incomes_user_id');
            safeDropForeignByName('incomes', 'incomes_user_id_foreign');

            $table->dropColumn('branch_id');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('incomes', function (Blueprint $table) {

            $table->foreign('user_id', 'incomes_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        // SALES
        Schema::table('sales', function (Blueprint $table) {

            safeDropForeignByName('sales', 'fk_sales_party_id');
            safeDropForeignByName('sales', 'fk_sales_user_id');

            safeDropForeignByName('sales', 'sales_party_id_foreign');
            safeDropForeignByName('sales', 'sales_user_id_foreign');

            $table->dropColumn('branch_id');
            $table->unsignedBigInteger('party_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('party_id', 'sales_party_id_foreign')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('user_id', 'sales_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        // PURCHASES
        Schema::table('purchases', function (Blueprint $table) {

            safeDropForeignByName('purchases', 'fk_purchases_party_id');
            safeDropForeignByName('purchases', 'fk_purchases_user_id');

            safeDropForeignByName('purchases', 'purchases_party_id_foreign');
            safeDropForeignByName('purchases', 'purchases_user_id_foreign');

            $table->dropColumn('branch_id');
            $table->unsignedBigInteger('party_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreign('party_id', 'purchases_party_id_foreign')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('user_id', 'purchases_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        // DUE COLLECTS
        Schema::table('due_collects', function (Blueprint $table) {

            safeDropForeignByName('due_collects', 'fk_due_collects_party_id');
            safeDropForeignByName('due_collects', 'fk_due_collects_user_id');
            safeDropForeignByName('due_collects', 'fk_due_collects_sale_id');
            safeDropForeignByName('due_collects', 'fk_due_collects_purchase_id');

            safeDropForeignByName('due_collects', 'due_collects_party_id_foreign');
            safeDropForeignByName('due_collects', 'due_collects_user_id_foreign');
            safeDropForeignByName('due_collects', 'due_collects_sale_id_foreign');
            safeDropForeignByName('due_collects', 'due_collects_purchase_id_foreign');

            $table->dropColumn('branch_id');
            $table->unsignedBigInteger('party_id')->nullable(false)->change();
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
            $table->unsignedBigInteger('purchase_id')->nullable(false)->change();
        });

        Schema::table('due_collects', function (Blueprint $table) {
            $table->foreign('party_id', 'due_collects_party_id_foreign')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('user_id', 'due_collects_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('sale_id', 'due_collects_sale_id_foreign')->references('id')->on('sales')->cascadeOnDelete();
            $table->foreign('purchase_id', 'due_collects_purchase_id_foreign')->references('id')->on('purchases')->cascadeOnDelete();
        });

        // Plans
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['allow_multibranch', 'addon_domain_limit', 'subdomain_limit']);
        });

        // Plans
        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->dropColumn(['allow_multibranch', 'addon_domain_limit', 'subdomain_limit']);
        });

        // BUSINESS TABLE
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['email', 'status']);
        });

        // TRANSFERS
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['from_branch_id', 'to_branch_id']);
        });

        // TRANSFER PRODUCTS
        Schema::table('transfer_products', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dropColumn('stock_id');
        });

        // Products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rack_id', 'shelf_id']);
        });
    }
};
