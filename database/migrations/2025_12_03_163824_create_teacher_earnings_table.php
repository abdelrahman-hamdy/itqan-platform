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
        Schema::create('teacher_earnings', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');

            // Teacher (polymorphic)
            $table->string('teacher_type'); // 'quran_teacher' | 'academic_teacher'
            $table->unsignedBigInteger('teacher_id');

            // Session (polymorphic)
            $table->string('session_type'); // QuranSession | AcademicSession | InteractiveCourseSession
            $table->unsignedBigInteger('session_id');

            // Earnings calculation
            $table->decimal('amount', 10, 2);
            $table->string('calculation_method'); // 'individual_rate' | 'group_rate' | 'per_session' | 'per_student' | 'fixed'
            $table->decimal('rate_snapshot', 10, 2)->nullable(); // Teacher's rate at time of calculation
            $table->json('calculation_metadata')->nullable(); // Full calculation context for transparency

            // Time tracking
            $table->date('earning_month'); // YYYY-MM-01 format for monthly grouping
            $table->dateTime('session_completed_at');
            $table->dateTime('calculated_at');

            // Payout tracking
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->boolean('is_finalized')->default(false); // Locked after payout generation

            // Dispute handling
            $table->boolean('is_disputed')->default(false);
            $table->text('dispute_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['teacher_type', 'teacher_id', 'earning_month'], 'idx_teacher_month');
            $table->index(['session_type', 'session_id'], 'idx_session');
            $table->index(['academy_id', 'earning_month'], 'idx_academy_month');
            $table->index(['is_finalized', 'payout_id'], 'idx_payout_status');

            // Unique constraint: prevent duplicate earnings for same session
            $table->unique(['session_type', 'session_id'], 'unique_session_earning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_earnings');
    }
};
