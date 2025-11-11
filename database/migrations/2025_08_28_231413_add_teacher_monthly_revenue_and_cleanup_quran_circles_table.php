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
            // Add teacher monthly revenue field if it doesn't exist
            if (!Schema::hasColumn('quran_circles', 'teacher_monthly_revenue')) {
                $table->decimal('teacher_monthly_revenue', 8, 2)->nullable()->after('monthly_fee');
            }
            
            // Drop foreign key constraint for supervisor_id first if it exists
            if (Schema::hasColumn('quran_circles', 'supervisor_id')) {
                $table->dropForeign(['supervisor_id']);
            }
            
            // Remove deprecated fields that are no longer used in admin/teacher dashboards
            $columnsToRemove = [];
            
            if (Schema::hasColumn('quran_circles', 'schedule_period')) {
                $columnsToRemove[] = 'schedule_period';
            }
            if (Schema::hasColumn('quran_circles', 'timezone')) {
                $columnsToRemove[] = 'timezone';
            }
            if (Schema::hasColumn('quran_circles', 'currency')) {
                $columnsToRemove[] = 'currency';
            }
            if (Schema::hasColumn('quran_circles', 'enrollment_fee')) {
                $columnsToRemove[] = 'enrollment_fee';
            }
            if (Schema::hasColumn('quran_circles', 'materials_fee')) {
                $columnsToRemove[] = 'materials_fee';
            }
            if (Schema::hasColumn('quran_circles', 'supervisor_id')) {
                $columnsToRemove[] = 'supervisor_id';
            }
            if (Schema::hasColumn('quran_circles', 'special_instructions')) {
                $columnsToRemove[] = 'special_instructions';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circles', function (Blueprint $table) {
            // Remove teacher monthly revenue field
            $table->dropColumn('teacher_monthly_revenue');
            
            // Add back deprecated fields
            $table->enum('schedule_period', ['week', 'month', 'two_months'])->default('month')->after('monthly_sessions_count');
            $table->string('timezone')->default('Asia/Riyadh')->after('schedule_time');
            $table->string('currency', 3)->default('SAR')->after('monthly_fee');
            $table->decimal('enrollment_fee', 8, 2)->default(0)->after('currency');
            $table->decimal('materials_fee', 8, 2)->default(0)->after('enrollment_fee');
            $table->unsignedBigInteger('supervisor_id')->nullable()->after('quran_teacher_id');
            $table->text('special_instructions')->nullable()->after('dropout_rate');
            
            // Re-add foreign key for supervisor_id
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
