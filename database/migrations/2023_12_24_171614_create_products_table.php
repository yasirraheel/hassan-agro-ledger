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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('productName');
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('productCode')->unique();
            $table->string('productPicture')->nullable();
            $table->double('productDealerPrice')->default(0);
            $table->double('productPurchasePrice')->default(0);
            $table->double('productSalePrice')->default(0);
            $table->double('productWholeSalePrice')->default(0);
            $table->integer('productStock')->default(0);
            $table->string('size')->nullable();
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->string('weight')->nullable();
            $table->string('capacity')->nullable();
            $table->string('productManufacturer')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
