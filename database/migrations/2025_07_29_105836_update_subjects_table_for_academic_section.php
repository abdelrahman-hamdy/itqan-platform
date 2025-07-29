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
        Schema::table('subjects', function (Blueprint $table) {
            // Remove category column (not needed, subject name is self-explanatory)
            $table->dropColumn('category');
            
            // Remove is_academic column (all subjects in this section are academic)
            $table->dropColumn('is_academic');
            
            // Add additional fields that might be useful for academic subjects
            $table->string('subject_code', 10)->after('name_en')->nullable()->unique();
            $table->text('prerequisites')->after('description')->nullable();
            $table->integer('hours_per_week')->after('prerequisites')->default(2);
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])->after('hours_per_week')->default('beginner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Restore the removed columns
            $table->string('category')->after('description')->nullable();
            $table->boolean('is_academic')->after('category')->default(true);
            
            // Remove the new columns
            $table->dropColumn(['subject_code', 'prerequisites', 'hours_per_week', 'difficulty_level']);
        });
    }
};
