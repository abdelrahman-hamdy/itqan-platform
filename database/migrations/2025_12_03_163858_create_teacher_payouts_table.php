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
        Schema::create('teacher_payouts', function (Blueprint $table) {
            $table->id();

            // Multi-tenancy
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');

            // Teacher (polymorphic)
            $table->string('teacher_type'); // 'quran_teacher' | 'academic_teacher'
            $table->unsignedBigInteger('teacher_id');

            // Payout identification
            $table->string('payout_code')->unique(); // PO-{academyId}-{YYYYMM}-{sequence}
            $table->date('payout_month'); // YYYY-MM-01

            // Financial details
            $table->decimal('total_amount', 10, 2);
            $table->integer('sessions_count');
            $table->json('breakdown')->nullable(); // Session type breakdown

            // Status workflow: pending -> approved -> paid or rejected
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');

            // Approval tracking
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Payment tracking
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // 'bank_transfer' | 'cash' | 'check'
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();

            // Rejection tracking
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['teacher_type', 'teacher_id', 'payout_month'], 'idx_teacher_payout_month');
            $table->index(['academy_id', 'payout_month'], 'idx_academy_payout_month');
            $table->index('status');

            // Unique constraint: one payout per teacher per month
            $table->unique(['teacher_type', 'teacher_id', 'payout_month', 'academy_id'], 'unique_teacher_monthly_payout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_payouts');
    }
};
