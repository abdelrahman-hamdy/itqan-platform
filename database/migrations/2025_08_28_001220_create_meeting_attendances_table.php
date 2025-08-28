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
        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            
            // Session and user identification
            $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('user_type', ['student', 'teacher', 'supervisor'])->default('student');
            $table->enum('session_type', ['individual', 'group'])->default('individual');
            
            // Attendance timing
            $table->timestamp('first_join_time')->nullable()->comment('When user first joined the meeting');
            $table->timestamp('last_leave_time')->nullable()->comment('When user last left the meeting');
            $table->integer('total_duration_minutes')->default(0)->comment('Total time spent in meeting in minutes');
            
            // Join/leave cycles tracking (JSON array)
            $table->json('join_leave_cycles')->nullable()->comment('Array of all join/leave events');
            
            // Attendance calculation
            $table->timestamp('attendance_calculated_at')->nullable()->comment('When final attendance was calculated');
            $table->enum('attendance_status', ['present', 'absent', 'late', 'partial'])->default('absent');
            $table->decimal('attendance_percentage', 5, 2)->default(0)->comment('Percentage of session attended');
            
            // Meeting metadata
            $table->integer('session_duration_minutes')->nullable()->comment('Total session duration for calculations');
            $table->timestamp('session_start_time')->nullable()->comment('Actual session start time');
            $table->timestamp('session_end_time')->nullable()->comment('Actual session end time');
            
            // Additional tracking
            $table->integer('join_count')->default(0)->comment('Number of times user joined');
            $table->integer('leave_count')->default(0)->comment('Number of times user left');
            $table->boolean('is_calculated')->default(false)->comment('Whether final attendance has been calculated');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->unique(['session_id', 'user_id'], 'meeting_attendance_session_user_unique');
            $table->index(['session_id', 'attendance_status'], 'meeting_attendance_session_status_idx');
            $table->index(['user_id', 'session_type'], 'meeting_attendance_user_type_idx');
            $table->index(['attendance_calculated_at', 'is_calculated'], 'meeting_attendance_calc_status_idx');
            $table->index(['first_join_time', 'last_leave_time'], 'meeting_attendance_timing_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
    }
};