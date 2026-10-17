<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_id');
            $table->string('scope', 160);
            $table->uuid('operation_id');
            $table->string('fingerprint', 64);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'operation_id']);
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_operations');
    }
};
