<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Check existing columns
        $columns = DB::select('DESCRIBE quran_sessions');
        $existingColumns = array_column($columns, 'Field');
        
        Schema::table('quran_sessions', function (Blueprint $table) use ($existingColumns) {
            // Add clear business fields only if they don't exist
            if (!in_array('monthly_session_number', $existingColumns)) {
                $table->integer('monthly_session_number')->nullable()->after('session_type')
                    ->comment('Session number within the month (1, 2, 3, etc.)');
            }
            
            if (!in_array('session_month', $existingColumns)) {
                $table->date('session_month')->nullable()->after('monthly_session_number')
                    ->comment('The month this session belongs to (YYYY-MM-01)');
            }
            
            if (!in_array('counts_toward_subscription', $existingColumns)) {
                $table->boolean('counts_toward_subscription')->default(true)->after('session_month')
                    ->comment('Whether this session counts toward student subscription');
            }
            
            // Improve status management
            if (!in_array('cancellation_type', $existingColumns)) {
                $table->string('cancellation_type')->nullable()->after('cancellation_reason')
                    ->comment('teacher_cancelled, student_cancelled, system_cancelled');
            }
            
            if (!in_array('rescheduling_note', $existingColumns)) {
                $table->text('rescheduling_note')->nullable()->after('rescheduled_to');
            }
        });
        
        // Add indexes if they don't exist
        try {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->index(['session_month', 'monthly_session_number'], 'idx_session_month_number');
                $table->index(['individual_circle_id', 'counts_toward_subscription'], 'idx_ind_circle_counts');
                $table->index(['circle_id', 'session_month'], 'idx_circle_month');
            });
        } catch (\Exception $e) {
            // Indexes might already exist
        }
    }

    public function down()
    {
        Schema::table('quran_sessions', function (Blueprint $table) {
            // Drop new indexes first
            $table->dropIndex('idx_session_month_number');
            $table->dropIndex('idx_ind_circle_counts');
            $table->dropIndex('idx_circle_month');
            
            // Remove new fields
            $table->dropColumn([
                'monthly_session_number',
                'session_month', 
                'counts_toward_subscription',
                'cancellation_type',
                'rescheduling_note'
            ]);
            
            // Restore old fields if needed (Note: Foreign keys would need to be recreated)
            $table->boolean('is_template')->default(false);
            $table->boolean('is_scheduled')->default(false);
            $table->boolean('is_generated')->default(false);
            $table->timestamp('teacher_scheduled_at')->nullable();
            $table->integer('session_sequence')->nullable();
        });
    }
};
