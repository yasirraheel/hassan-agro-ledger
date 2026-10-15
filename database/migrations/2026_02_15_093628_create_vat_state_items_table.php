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
        Schema::create('vat_state_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_vat_id')->constrained('vats')->cascadeOnDelete();
            $table->foreignId('child_vat_id')->constrained('vats')->cascadeOnDelete();
            $table->enum('type', ['inner', 'outer']);
            $table->timestamps();

            $table->index(['parent_vat_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_state_items');
    }
};
