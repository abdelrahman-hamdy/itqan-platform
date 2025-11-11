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
        Schema::create('student_session_reports', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');

            // Performance degrees (0-10 scale)
            $table->decimal('new_memorization_degree', 3, 1)->nullable()->comment('درجة الحفظ الجديد من 0 إلى 10');
            $table->decimal('reservation_degree', 3, 1)->nullable()->comment('درجة المراجعة من 0 إلى 10');

            // Teacher notes about student's performance
            $table->text('notes')->nullable()->comment('ملاحظات المعلم على أداء الطالب');

            // Auto-calculated attendance details
            $table->timestamp('meeting_enter_time')->nullable()->comment('وقت دخول الطالب للاجتماع');
            $table->timestamp('meeting_leave_time')->nullable()->comment('وقت خروج الطالب من الاجتماع');
            $table->integer('actual_attendance_minutes')->default(0)->comment('عدد الدقائق الفعلية للحضور');
            $table->boolean('is_late')->default(false)->comment('هل تأخر الطالب عن الموعد المحدد');
            $table->integer('late_minutes')->default(0)->comment('عدد دقائق التأخير');
            $table->enum('attendance_status', ['present', 'late', 'partial', 'absent'])->default('absent');
            $table->decimal('attendance_percentage', 5, 2)->default(0)->comment('نسبة الحضور من إجمالي وقت الجلسة');

            // Meeting quality metrics
            $table->integer('connection_quality_score')->nullable()->comment('جودة الاتصال من 1-5');
            $table->json('meeting_events')->nullable()->comment('أحداث الاجتماع (دخول، خروج، انقطاع)');

            // Teacher evaluation timestamp
            $table->timestamp('evaluated_at')->nullable()->comment('وقت تقييم المعلم');
            $table->boolean('is_auto_calculated')->default(true)->comment('هل تم حساب الحضور تلقائياً');
            $table->boolean('manually_overridden')->default(false)->comment('هل تم تعديل البيانات يدوياً');
            $table->text('override_reason')->nullable()->comment('سبب التعديل اليدوي');

            $table->timestamps();

            // Indexes for better performance
            $table->index(['session_id', 'student_id']);
            $table->index(['teacher_id', 'academy_id']);
            $table->index('attendance_status');
            $table->index('evaluated_at');

            // Unique constraint to prevent duplicate reports
            $table->unique(['session_id', 'student_id'], 'unique_student_session_report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_session_reports');
    }
};
