<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix 1: Ensure the quran_circle_students pivot table exists
        if (!Schema::hasTable('quran_circle_students')) {
            Schema::create('quran_circle_students', function (Blueprint $table) {
                $table->id();
                $table->foreignId('circle_id')->constrained('quran_circles')->onDelete('cascade');
                $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('enrolled_at')->nullable();
                $table->string('status')->default('enrolled');
                $table->integer('attendance_count')->default(0);
                $table->integer('missed_sessions')->default(0);
                $table->integer('makeup_sessions_used')->default(0);
                $table->string('current_level')->nullable();
                $table->text('progress_notes')->nullable();
                $table->integer('parent_rating')->nullable();
                $table->integer('student_rating')->nullable();
                $table->timestamp('completion_date')->nullable();
                $table->boolean('certificate_issued')->default(false);
                $table->timestamps();
                
                $table->unique(['circle_id', 'student_id']);
                $table->index(['circle_id', 'status']);
                $table->index(['student_id', 'status']);
            });
        }

        // Fix 2: Update session types from 'circle' to 'group' for group sessions
        // This handles sessions that still have the old 'circle' type
        DB::table('quran_sessions')
            ->where('session_type', 'circle')
            ->whereNotNull('circle_id')
            ->whereNull('student_id')
            ->update(['session_type' => 'group']);

        // Fix 3: Update the session_type enum to include 'group' if it doesn't exist
        try {
            DB::statement("ALTER TABLE quran_sessions MODIFY COLUMN session_type ENUM('individual', 'circle', 'group', 'makeup', 'trial', 'assessment') DEFAULT 'individual'");
        } catch (\Exception $e) {
            // If the enum modification fails, try a different approach
            // This might happen if 'group' is already in the enum
        }

        // Fix 4: Ensure proper indexes exist for performance
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Only add indexes if they don't exist
            try {
                $table->index(['session_type', 'circle_id'], 'idx_session_type_circle');
                $table->index(['circle_id', 'session_type', 'status'], 'idx_circle_type_status');
            } catch (\Exception $e) {
                // Indexes might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the indexes
        Schema::table('quran_sessions', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_session_type_circle');
                $table->dropIndex('idx_circle_type_status');
            } catch (\Exception $e) {
                // Indexes might not exist
            }
        });

        // Revert session types back to 'circle'
        DB::table('quran_sessions')
            ->where('session_type', 'group')
            ->update(['session_type' => 'circle']);

        // Drop the pivot table (be careful with this in production)
        // Schema::dropIfExists('quran_circle_students');
    }
};
