<?php

/**
 * Create a test interactive course with 8 sessions for testing LiveKit recording
 *
 * This script:
 * 1. Creates an interactive course that has already started
 * 2. Creates 8 sessions (one scheduled 15 minutes from now)
 * 3. Enrolls the current student in the course
 * 4. Enables recording for the course
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveCourseEnrollment;
use App\Models\StudentProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use Carbon\Carbon;

DB::beginTransaction();

try {
    echo "🚀 Creating test interactive course for LiveKit recording...\n\n";

    // Get existing data
    $academy = Academy::find(1);
    $teacher = AcademicTeacherProfile::find(1);
    $student = StudentProfile::find(1);

    if (!$academy || !$teacher || !$student) {
        throw new Exception("Required data not found. Academy ID: {$academy?->id}, Teacher ID: {$teacher?->id}, Student ID: {$student?->id}");
    }

    echo "✅ Found Academy: {$academy->name}\n";
    echo "✅ Found Teacher ID: {$teacher->id}\n";
    echo "✅ Found Student: {$student->first_name} {$student->last_name}\n\n";

    // Create Interactive Course
    $course = InteractiveCourse::create([
        'academy_id' => $academy->id,
        'assigned_teacher_id' => $teacher->id,
        'created_by' => $teacher->user_id,
        'title' => 'دورة الرياضيات المتقدمة - اختبار التسجيل',
        'title_en' => 'Advanced Mathematics Course - Recording Test',
        'description' => 'دورة تفاعلية لاختبار نظام التسجيل باستخدام LiveKit. تحتوي على 8 جلسات مع جلسة قادمة بعد 15 دقيقة.',
        'description_en' => 'Interactive course to test LiveKit recording system. Contains 8 sessions with one session scheduled in 15 minutes.',
        'subject_id' => 1, // الرياضيات
        'grade_level_id' => 1, // الصف الأول الإعدادي
        'course_type' => 'intensive',
        'difficulty_level' => 'intermediate',
        'max_students' => 20,
        'sessions_per_week' => 2,
        'session_duration_minutes' => 60,
        'total_sessions' => 8,
        'student_price' => 500.00,
        'enrollment_fee' => 0.00,
        'is_enrollment_fee_required' => false,
        'teacher_payment' => 1000.00,
        'payment_type' => 'fixed_amount',
        'teacher_fixed_amount' => 1000.00,
        'start_date' => Carbon::now()->subDays(10), // Started 10 days ago
        'enrollment_deadline' => Carbon::now()->addDays(5),
        'schedule' => [
            'الأحد' => '10:00 - 11:00',
            'الأربعاء' => '10:00 - 11:00',
        ],
        'learning_outcomes' => [
            'فهم المفاهيم الرياضية المتقدمة',
            'حل المسائل الرياضية المعقدة',
            'تطبيق النظريات الرياضية',
        ],
        'prerequisites' => [
            'إتقان أساسيات الرياضيات',
            'القدرة على التفكير المنطقي',
        ],
        'course_outline' => "الجلسة 1: مقدمة في الجبر\nالجلسة 2: المعادلات الخطية\nالجلسة 3: الهندسة التحليلية\nالجلسة 4: الإحصاء\nالجلسة 5: الاحتمالات\nالجلسة 6: التكامل\nالجلسة 7: المصفوفات\nالجلسة 8: المراجعة النهائية",
        'status' => 'active',
        'is_published' => true,
        'publication_date' => Carbon::now()->subDays(15),
        'certificate_enabled' => true,
        'certificate_template_style' => 'template_1',
        'recording_enabled' => true, // ✅ Enable recording for LiveKit
        'preparation_minutes' => 10,
        'buffer_minutes' => 5,
        'late_tolerance_minutes' => 15,
        'attendance_threshold_percentage' => 75.00,
    ]);

    echo "✅ Created Interactive Course: {$course->title}\n";
    echo "   Course Code: {$course->course_code}\n";
    echo "   Recording Enabled: " . ($course->recording_enabled ? 'YES' : 'NO') . "\n";
    echo "   Start Date: {$course->start_date->format('Y-m-d')}\n";
    echo "   Total Sessions: {$course->total_sessions}\n\n";

    // Create 8 sessions
    echo "📅 Creating 8 sessions...\n\n";

    $sessions = [];
    $now = Carbon::now();

    // Session times (3 past, 1 in progress, 1 upcoming in 15 min, 3 future)
    $sessionTimes = [
        Carbon::now()->subDays(8)->setTime(10, 0),  // Session 1 - 8 days ago (completed)
        Carbon::now()->subDays(5)->setTime(10, 0),  // Session 2 - 5 days ago (completed)
        Carbon::now()->subDays(2)->setTime(10, 0),  // Session 3 - 2 days ago (completed)
        Carbon::now()->subMinutes(10),               // Session 4 - 10 minutes ago (ongoing)
        Carbon::now()->addMinutes(15),               // Session 5 - 15 minutes from now (scheduled) 🎯
        Carbon::now()->addDays(2)->setTime(10, 0),  // Session 6 - 2 days future
        Carbon::now()->addDays(5)->setTime(10, 0),  // Session 7 - 5 days future
        Carbon::now()->addDays(8)->setTime(10, 0),  // Session 8 - 8 days future
    ];

    $sessionTitles = [
        'مقدمة في الجبر',
        'المعادلات الخطية',
        'الهندسة التحليلية',
        'الإحصاء والبيانات',
        'نظريات الاحتمالات',
        'التكامل والتفاضل',
        'المصفوفات والمحددات',
        'المراجعة النهائية',
    ];

    $sessionDescriptions = [
        'Introduction to Algebra',
        'Linear Equations',
        'Analytical Geometry',
        'Statistics and Data',
        'Probability Theories',
        'Integration and Differentiation',
        'Matrices and Determinants',
        'Final Review',
    ];

    foreach ($sessionTimes as $index => $scheduledAt) {
        $sessionNumber = $index + 1;

        // Determine status based on time
        $status = 'scheduled';
        if ($scheduledAt->isPast()) {
            if ($scheduledAt->diffInMinutes($now) <= 60 && $scheduledAt->isBefore($now) && $now->isBefore($scheduledAt->copy()->addHour())) {
                $status = 'ongoing'; // Session is currently happening
            } else {
                $status = 'completed'; // Session is in the past
            }
        }

        $session = InteractiveCourseSession::create([
            'course_id' => $course->id,
            'academy_id' => $academy->id,
            'session_number' => $sessionNumber,
            'scheduled_at' => $scheduledAt,
            'title' => $sessionTitles[$index],
            'description' => $sessionDescriptions[$index],
            'lesson_content' => "محتوى الجلسة {$sessionNumber}: {$sessionTitles[$index]}",
            'duration_minutes' => 60,
            'status' => $status,
            'attendance_count' => $status === 'completed' ? 1 : 0,
            'homework_assigned' => false,
        ]);

        $sessions[] = $session;

        $emoji = $sessionNumber === 5 ? '🎯' : ($status === 'completed' ? '✅' : ($status === 'ongoing' ? '🔴' : '📅'));
        $timeLabel = $scheduledAt->diffForHumans();

        echo "{$emoji} Session {$sessionNumber}: {$session->title}\n";
        echo "   Scheduled: {$scheduledAt->format('Y-m-d H:i')} ({$timeLabel})\n";
        echo "   Status: {$status}\n";
        echo "   Duration: {$session->duration_minutes} minutes\n\n";
    }

    // Enroll the student in the course
    echo "👨‍🎓 Enrolling student in the course...\n";

    $enrollment = InteractiveCourseEnrollment::create([
        'academy_id' => $academy->id,
        'course_id' => $course->id,
        'student_id' => $student->id,
        'enrolled_by' => $teacher->user_id,
        'enrollment_date' => Carbon::now()->subDays(12),
        'payment_status' => 'paid',
        'payment_amount' => $course->student_price,
        'discount_applied' => 0,
        'enrollment_status' => 'enrolled',
        'completion_percentage' => 37.5, // 3 out of 8 sessions completed
        'attendance_count' => 3,
        'total_possible_attendance' => 8,
        'certificate_issued' => false,
    ]);

    echo "✅ Student enrolled successfully!\n";
    echo "   Enrollment ID: {$enrollment->id}\n";
    echo "   Payment Status: {$enrollment->payment_status}\n";
    echo "   Progress: {$enrollment->completion_percentage}%\n\n";

    DB::commit();

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✨ SUCCESS! Test interactive course created successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    echo "📊 SUMMARY:\n";
    echo "   Course ID: {$course->id}\n";
    echo "   Course Code: {$course->course_code}\n";
    echo "   Course Title: {$course->title}\n";
    echo "   Recording Enabled: ✅ YES\n";
    echo "   Total Sessions: {$course->total_sessions}\n";
    echo "   Student Enrolled: {$student->first_name} {$student->last_name}\n";
    echo "   Teacher: Academic Teacher ID {$teacher->id}\n\n";

    echo "🎯 UPCOMING SESSION (in 15 minutes):\n";
    $upcomingSession = $sessions[4]; // Session 5
    echo "   Session ID: {$upcomingSession->id}\n";
    echo "   Session Number: {$upcomingSession->session_number}\n";
    echo "   Title: {$upcomingSession->title}\n";
    echo "   Scheduled At: {$upcomingSession->scheduled_at->format('Y-m-d H:i:s')}\n";
    echo "   Time Until Start: " . $upcomingSession->scheduled_at->diffForHumans() . "\n\n";

    echo "🔗 TEST THE SESSION:\n";
    echo "   You can now test the LiveKit recording feature by joining the session\n";
    echo "   The session will be available for joining in 15 minutes.\n\n";

    echo "✅ All data inserted successfully!\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
