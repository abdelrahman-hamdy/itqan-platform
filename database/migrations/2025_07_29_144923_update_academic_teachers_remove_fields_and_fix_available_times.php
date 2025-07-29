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
        Schema::table('academic_teachers', function (Blueprint $table) {
            // Remove unwanted fields
            $table->dropColumn([
                'specialization_field',
                'qualification_details', 
                'session_price_group',
                'preferred_teaching_methods',
                'portfolio_url',
                'cv_file_path'
            ]);
            
            // Replace available_times with start/end times
            $table->dropColumn('available_times');
            $table->time('available_time_start')->default('08:00')->after('available_days');
            $table->time('available_time_end')->default('18:00')->after('available_time_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_teachers', function (Blueprint $table) {
            // Add back removed fields
            $table->enum('specialization_field', ['mathematics', 'physics', 'chemistry', 'biology', 'arabic_language', 'english_language', 'history', 'geography', 'islamic_studies', 'computer_science', 'art', 'music', 'physical_education', 'philosophy', 'psychology', 'sociology', 'economics'])->default('mathematics');
            $table->text('qualification_details')->nullable();
            $table->decimal('session_price_group', 8, 2)->default(0);
            $table->json('preferred_teaching_methods')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('cv_file_path')->nullable();
            
            // Restore available_times
            $table->dropColumn(['available_time_start', 'available_time_end']);
            $table->json('available_times')->nullable();
        });
    }
};
