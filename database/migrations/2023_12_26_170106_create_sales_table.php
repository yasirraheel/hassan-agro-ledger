<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->double('discountAmount')->default(0);
            $table->double('dueAmount')->default(0);
            $table->boolean('isPaid')->default(0);
            $table->double('vat_amount')->default(0);
            $table->double('vat_percent')->default(0);
            $table->double('paidAmount')->default(0);
            $table->double('totalAmount')->default(0);
            $table->double('lossProfit')->default(0);
            $table->string('paymentType')->nullable();
            $table->string('invoiceNumber')->nullable();
            $table->timestamp('saleDate')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
