<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing columns for the QuranCircleEnrollment model which uses
     * the quran_circle_students table with SoftDeletes trait.
     */
    public function up(): void
    {
        Schema::table('quran_circle_students', function (Blueprint $table) {
            // Add soft deletes column
            if (! Schema::hasColumn('quran_circle_students', 'deleted_at')) {
                $table->softDeletes();
            }

            // Add subscription_id for decoupled architecture (nullable - enrollments can exist without subscriptions)
            if (! Schema::hasColumn('quran_circle_students', 'subscription_id')) {
                $table->foreignId('subscription_id')->nullable()->after('student_id')
                    ->constrained('quran_subscriptions')->nullOnDelete();
            }

            // Add progress tracking columns if missing
            if (! Schema::hasColumn('quran_circle_students', 'parent_rating')) {
                $table->decimal('parent_rating', 2, 1)->nullable()->after('progress_notes');
            }
            if (! Schema::hasColumn('quran_circle_students', 'student_rating')) {
                $table->decimal('student_rating', 2, 1)->nullable()->after('parent_rating');
            }
            if (! Schema::hasColumn('quran_circle_students', 'completion_date')) {
                $table->timestamp('completion_date')->nullable()->after('student_rating');
            }
            if (! Schema::hasColumn('quran_circle_students', 'certificate_issued')) {
                $table->boolean('certificate_issued')->default(false)->after('completion_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quran_circle_students', function (Blueprint $table) {
            $table->dropSoftDeletes();

            if (Schema::hasColumn('quran_circle_students', 'subscription_id')) {
                $table->dropForeign(['subscription_id']);
                $table->dropColumn('subscription_id');
            }
            if (Schema::hasColumn('quran_circle_students', 'parent_rating')) {
                $table->dropColumn('parent_rating');
            }
            if (Schema::hasColumn('quran_circle_students', 'student_rating')) {
                $table->dropColumn('student_rating');
            }
            if (Schema::hasColumn('quran_circle_students', 'completion_date')) {
                $table->dropColumn('completion_date');
            }
            if (Schema::hasColumn('quran_circle_students', 'certificate_issued')) {
                $table->dropColumn('certificate_issued');
            }
        });
    }
};
