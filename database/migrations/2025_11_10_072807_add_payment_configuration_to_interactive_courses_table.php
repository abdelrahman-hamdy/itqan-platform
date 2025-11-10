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
            // Update payment_type to match implementation plan enum values
            // Existing values: 'fixed_amount', 'per_student', 'per_session'
            // These already exist but let's add detailed amount fields

            // Add specific payment amount fields
            $table->decimal('teacher_fixed_amount', 10, 2)->nullable()->after('teacher_payment')
                ->comment('Fixed amount for teacher (when payment_type = fixed)');
            $table->decimal('amount_per_student', 10, 2)->nullable()->after('teacher_fixed_amount')
                ->comment('Amount per enrolled student (when payment_type = per_student)');
            $table->decimal('amount_per_session', 10, 2)->nullable()->after('amount_per_student')
                ->comment('Amount per session conducted (when payment_type = per_session)');

            // Add enrollment fees
            $table->decimal('enrollment_fee', 10, 2)->nullable()->after('student_price')
                ->comment('One-time enrollment fee for students');
            $table->boolean('is_enrollment_fee_required')->default(false)->after('enrollment_fee')
                ->comment('Whether enrollment fee is required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_courses', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_fixed_amount',
                'amount_per_student',
                'amount_per_session',
                'enrollment_fee',
                'is_enrollment_fee_required',
            ]);
        });
    }
};
