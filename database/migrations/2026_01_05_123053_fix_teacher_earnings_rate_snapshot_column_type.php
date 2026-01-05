<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes the rate_snapshot column type from DECIMAL to JSON to match the model's cast.
     */
    public function up(): void
    {
        Schema::table('teacher_earnings', function (Blueprint $table) {
            // Change rate_snapshot from decimal to JSON (model expects array cast)
            $table->json('rate_snapshot')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_earnings', function (Blueprint $table) {
            $table->decimal('rate_snapshot', 10, 2)->nullable()->change();
        });
    }
};
