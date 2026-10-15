<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('quotation_no')->nullable();
            $table->timestamp('quotation_date')->nullable();
            $table->timestamp('expire_date')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->string('discount_type')->nullable()->default('flat');
            $table->double('discountAmount')->default(0);
            $table->double('discount_percent')->default(0);
            $table->double('vat_amount')->default(0);
            $table->double('vat_percent')->default(0);
            $table->double('shipping_charge')->default(0);
            $table->double('totalAmount')->default(0);
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->text('terms')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['party_id', 'quotation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
