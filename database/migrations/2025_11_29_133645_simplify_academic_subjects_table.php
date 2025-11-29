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
        Schema::table('academic_subjects', function (Blueprint $table) {
            // Rename notes to admin_notes first
            $table->renameColumn('notes', 'admin_notes');
        });

        Schema::table('academic_subjects', function (Blueprint $table) {
            // Drop all unnecessary fields
            $table->dropColumn([
                'category',
                'field',
                'level_scope',
                'prerequisites',
                'color_code',
                'icon',
                'is_core_subject',
                'is_elective',
                'credit_hours',
                'difficulty_level',
                'estimated_duration_weeks',
                'curriculum_framework',
                'learning_objectives',
                'assessment_methods',
                'required_materials',
                'display_order',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_subjects', function (Blueprint $table) {
            // Add back all dropped columns
            $table->string('category')->nullable();
            $table->string('field')->nullable();
            $table->json('level_scope')->nullable();
            $table->json('prerequisites')->nullable();
            $table->string('color_code', 7)->default('#3B82F6');
            $table->string('icon')->nullable();
            $table->boolean('is_core_subject')->default(true);
            $table->boolean('is_elective')->default(false);
            $table->integer('credit_hours')->default(3);
            $table->integer('difficulty_level')->default(1);
            $table->integer('estimated_duration_weeks')->default(16);
            $table->text('curriculum_framework')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('assessment_methods')->nullable();
            $table->json('required_materials')->nullable();
            $table->integer('display_order')->default(0);
        });

        Schema::table('academic_subjects', function (Blueprint $table) {
            // Rename admin_notes back to notes
            $table->renameColumn('admin_notes', 'notes');
        });
    }
};
