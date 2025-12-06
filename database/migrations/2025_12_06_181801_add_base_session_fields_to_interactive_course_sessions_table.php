<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add all missing BaseSession fields to interactive_course_sessions table
     * to make it fully compatible with BaseSession model.
     */
    public function up(): void
    {
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            // Meeting fields (missing from refactor migration)
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_id')) {
                $table->string('meeting_id', 100)->nullable()->after('meeting_link');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_password')) {
                $table->string('meeting_password', 50)->nullable()->after('meeting_id');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_platform')) {
                $table->string('meeting_platform', 255)->default('livekit')->after('meeting_password');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_data')) {
                $table->json('meeting_data')->nullable()->after('meeting_platform');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_room_name')) {
                $table->string('meeting_room_name', 255)->nullable()->after('meeting_data');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_auto_generated')) {
                $table->boolean('meeting_auto_generated')->default(false)->after('meeting_room_name');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'meeting_expires_at')) {
                $table->timestamp('meeting_expires_at')->nullable()->after('meeting_auto_generated');
            }

            // Session timing fields
            if (!Schema::hasColumn('interactive_course_sessions', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'actual_duration_minutes')) {
                $table->integer('actual_duration_minutes')->nullable()->after('duration_minutes');
            }

            // Attendance fields
            if (!Schema::hasColumn('interactive_course_sessions', 'attendance_status')) {
                $table->string('attendance_status')->nullable()->after('attendance_count');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'participants_count')) {
                $table->integer('participants_count')->default(0)->after('attendance_status');
            }

            // Feedback fields
            if (!Schema::hasColumn('interactive_course_sessions', 'session_notes')) {
                $table->text('session_notes')->nullable()->after('lesson_content');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'teacher_feedback')) {
                $table->text('teacher_feedback')->nullable()->after('session_notes');
            }

            // Cancellation fields
            if (!Schema::hasColumn('interactive_course_sessions', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('teacher_feedback');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancellation_reason');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }

            // Rescheduling fields
            if (!Schema::hasColumn('interactive_course_sessions', 'reschedule_reason')) {
                $table->text('reschedule_reason')->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'rescheduled_from')) {
                $table->timestamp('rescheduled_from')->nullable()->after('reschedule_reason');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'rescheduled_to')) {
                $table->timestamp('rescheduled_to')->nullable()->after('rescheduled_from');
            }

            // Tracking fields
            if (!Schema::hasColumn('interactive_course_sessions', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('rescheduled_to');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('interactive_course_sessions', 'scheduled_by')) {
                $table->unsignedBigInteger('scheduled_by')->nullable()->after('updated_by');
            }
        });

        // Add foreign keys
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('interactive_course_sessions', 'cancelled_by')) {
                try {
                    $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('interactive_course_sessions', 'created_by')) {
                try {
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('interactive_course_sessions', 'updated_by')) {
                try {
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
            if (Schema::hasColumn('interactive_course_sessions', 'scheduled_by')) {
                try {
                    $table->foreign('scheduled_by')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interactive_course_sessions', function (Blueprint $table) {
            // Drop foreign keys first
            try {
                $table->dropForeign(['cancelled_by']);
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
                $table->dropForeign(['scheduled_by']);
            } catch (\Exception $e) {
                // Foreign keys might not exist
            }

            // Drop columns
            $columns = [
                'meeting_id', 'meeting_password', 'meeting_platform', 'meeting_data',
                'meeting_room_name', 'meeting_auto_generated', 'meeting_expires_at',
                'started_at', 'ended_at', 'actual_duration_minutes',
                'attendance_status', 'participants_count',
                'session_notes', 'teacher_feedback',
                'cancellation_reason', 'cancelled_by', 'cancelled_at',
                'reschedule_reason', 'rescheduled_from', 'rescheduled_to',
                'created_by', 'updated_by', 'scheduled_by'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('interactive_course_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
