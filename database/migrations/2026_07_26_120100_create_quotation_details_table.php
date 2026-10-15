<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_no')->nullable();
            $table->string('unit_name')->nullable();
            $table->double('price')->default(0);
            $table->double('discount')->default(0);
            $table->double('quantities')->default(0);
            $table->double('sub_total')->default(0);
            $table->double('thickness')->default(0);
            $table->double('length')->default(0);
            $table->double('height')->default(0);
            $table->double('width')->default(0);
            $table->json('serial_numbers')->nullable();
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_details');
    }
};
