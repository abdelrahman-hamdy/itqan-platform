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
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Remove the completion_certificate field
            $table->dropColumn('completion_certificate');

            // Remove the currency field - will be managed globally at academy level
            $table->dropColumn('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recorded_courses', function (Blueprint $table) {
            // Add back the completion_certificate field
            $table->boolean('completion_certificate')->default(true)->after('enrollment_deadline');

            // Add back the currency field
            $table->string('currency', 3)->default('SAR')->after('discount_price');
        });
    }
};
