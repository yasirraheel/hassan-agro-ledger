<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('user_limit')->nullable()->after('subdomain_limit');
            $table->integer('product_limit')->nullable()->after('user_limit');
            $table->integer('customer_limit')->nullable()->after('product_limit');
            $table->integer('supplier_limit')->nullable()->after('customer_limit');
            $table->integer('warehouse_limit')->nullable()->after('supplier_limit');
            $table->integer('branch_limit')->nullable()->after('warehouse_limit');
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->integer('user_limit')->nullable()->after('subdomain_limit');
            $table->integer('product_limit')->nullable()->after('user_limit');
            $table->integer('customer_limit')->nullable()->after('product_limit');
            $table->integer('supplier_limit')->nullable()->after('customer_limit');
            $table->integer('warehouse_limit')->nullable()->after('supplier_limit');
            $table->integer('branch_limit')->nullable()->after('warehouse_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'user_limit', 'product_limit',
                'customer_limit', 'supplier_limit',
                'warehouse_limit', 'branch_limit',
            ]);
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->dropColumn([
                'user_limit', 'product_limit',
                'customer_limit', 'supplier_limit',
                'warehouse_limit', 'branch_limit',
            ]);
        });
    }
};
