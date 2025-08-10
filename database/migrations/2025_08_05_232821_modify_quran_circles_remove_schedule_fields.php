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
        Schema::table('quran_circles', function (Blueprint $table) {
            // Remove scheduling fields - these will be managed by QuranCircleSchedule
            $table->dropColumn([
                'schedule_days',
                'schedule_time',
                'next_session_at'
            ]);
            
            // Update status enum to reflect new workflow
            // Add 'inactive' status for circles without teacher schedule
            $table->enum('status', [
                'planning', 
                'inactive',    // New: Created by admin but no teacher schedule set
                'pending', 
                'active', 
                'ongoing', 
                'completed', 
                'cancelled', 
                'suspended'
            ])->default('planning')->change();
            
            // Update enrollment_status to prevent enrollment until activated
            $table->enum('enrollment_status', [
                'closed',      // Default: not ready for enrollment
                'open', 
                'full', 
                'waitlist'
            ])->default('closed')->change();
            
            // Add field to track if teacher has set schedule
            $table->boolean('schedule_configured')->default(false)->after('status');
            $table->datetime('schedule_configured_at')->nullable()->after('schedule_configured');
            $table->foreignId('schedule_configured_by')->nullable()->constrained('users')->onDelete('set null')->after('schedule_configured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            // Restore original scheduling fields
            $table->json('schedule_days')->nullable();
            $table->time('schedule_time')->nullable();
            $table->timestamp('next_session_at')->nullable();
            
            // Restore original status enum
            $table->enum('status', [
                'planning',
                'pending', 
                'active', 
                'ongoing', 
                'completed', 
                'cancelled', 
                'suspended'
            ])->default('planning')->change();
            
            // Restore original enrollment_status enum
            $table->enum('enrollment_status', [
                'open', 
                'closed', 
                'full', 
                'waitlist'
            ])->default('closed')->change();
            
            // Remove new fields
            $table->dropForeign(['schedule_configured_by']);
            $table->dropColumn([
                'schedule_configured',
                'schedule_configured_at',
                'schedule_configured_by'
            ]);
        });
    }
};