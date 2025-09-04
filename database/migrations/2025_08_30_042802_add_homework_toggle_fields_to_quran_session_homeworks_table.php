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
        Schema::table('quran_session_homeworks', function (Blueprint $table) {
            // Toggle fields for each homework type
            $table->boolean('has_new_memorization')->default(false)->after('created_by');
            $table->boolean('has_review')->default(false)->after('has_new_memorization');
            $table->boolean('has_comprehensive_review')->default(false)->after('has_review');

            // Comprehensive review surahs as JSON array
            $table->json('comprehensive_review_surahs')->nullable()->after('review_notes');

            // Add indexes for better performance
            $table->index(['has_new_memorization', 'has_review', 'has_comprehensive_review'], 'homework_types_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_session_homeworks', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('homework_types_idx');

            // Drop columns
            $table->dropColumn([
                'has_new_memorization',
                'has_review',
                'has_comprehensive_review',
                'comprehensive_review_surahs',
            ]);
        });
    }
};
