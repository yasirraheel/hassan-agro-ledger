<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->json('available_modules')->nullable()->after('visibility');
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->json('available_modules')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('available_modules');
        });

        Schema::table('plan_subscribes', function (Blueprint $table) {
            $table->dropColumn('available_modules');
        });
    }
};
