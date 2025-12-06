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
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0)->after('status');
            $table->unsignedInteger('total_reviews')->default(0)->after('avg_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn(['avg_rating', 'total_reviews']);
        });
    }
};
