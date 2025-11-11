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
        Schema::create('quran_session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('quran_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->enum('attendance_status', ['present', 'absent', 'late', 'partial', 'left_early'])->default('absent');
            $table->timestamp('join_time')->nullable();
            $table->timestamp('leave_time')->nullable();
            $table->decimal('participation_score', 3, 1)->nullable()->comment('درجة المشاركة من 0 إلى 10');
            $table->decimal('recitation_quality', 3, 1)->nullable()->comment('جودة التلاوة من 0 إلى 10');
            $table->decimal('tajweed_accuracy', 3, 1)->nullable()->comment('دقة التجويد من 0 إلى 10');
            $table->integer('verses_reviewed')->nullable()->comment('عدد الآيات المراجعة');
            $table->boolean('homework_completion')->default(false)->comment('إكمال الواجب المنزلي');
            $table->text('notes')->nullable()->comment('ملاحظات المعلم');
            $table->timestamps();

            // فهارس لتحسين الأداء
            $table->index(['session_id', 'student_id']);
            $table->index(['attendance_status']);
            $table->index(['join_time']);
            
            // فهرس مركب فريد لمنع التكرار
            $table->unique(['session_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_session_attendances');
    }
};
