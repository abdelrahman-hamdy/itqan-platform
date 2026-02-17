<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\SessionRecording;
use App\Models\StudentProfile;
use App\Models\StudentSessionReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds comprehensive test data in the e2e-test tenant for QA testing.
 *
 * Creates sessions, subscriptions, reports, and recordings in ALL possible
 * statuses so QA agents can test every data state in the UI.
 *
 * Usage: php artisan db:seed --class=E2ETestDataSeeder
 *
 * Idempotent: Cleans up old [E2E] data before re-creating.
 */
class E2ETestDataSeeder extends Seeder
{
    private const PREFIX = '[E2E]';

    private Academy $academy;

    private User $quranTeacher;

    private User $academicTeacher;

    private User $student;

    private ?QuranTeacherProfile $quranTeacherProfile;

    private ?AcademicTeacherProfile $academicTeacherProfile;

    private ?StudentProfile $studentProfile;

    public function run(): void
    {
        $this->command->info('Starting E2E test data seeding...');

        // 1. Find the e2e-test academy
        $this->academy = Academy::where('subdomain', 'e2e-test')->firstOrFail();
        $this->command->info("Found academy: {$this->academy->name} (ID: {$this->academy->id})");

        // 2. Find e2e test users
        $this->findUsers();

        // 3. Clean old E2E test data
        $this->cleanOldData();

        // 4. Ensure teacher/student profiles exist
        $this->ensureProfiles();

        // 5. Create prerequisite structures
        $circle = $this->createQuranCircle();
        $lesson = $this->createAcademicLesson();

        // 6. Create subscriptions in all statuses
        $quranSubs = $this->createQuranSubscriptions($circle);
        $academicSubs = $this->createAcademicSubscriptions($lesson);

        // 7. Create sessions in all statuses
        $quranSessions = $this->createQuranSessions($circle, $quranSubs['active'] ?? null);
        $academicSessions = $this->createAcademicSessions($lesson, $academicSubs['active'] ?? null);

        // 8. Create reports for completed sessions
        $this->createReports($quranSessions, $academicSessions);

        // 9. Create recordings
        $this->createRecordings();

        $this->command->info('E2E test data seeding completed!');
    }

    private function findUsers(): void
    {
        $this->quranTeacher = User::where('email', 'e2e-teacher@itqan.com')->firstOrFail();
        $this->academicTeacher = User::where('email', 'e2e-academic@itqan.com')->firstOrFail();
        $this->student = User::where('email', 'e2e-student@itqan.com')->firstOrFail();

        $this->command->info("Found users: teacher={$this->quranTeacher->id}, academic={$this->academicTeacher->id}, student={$this->student->id}");
    }

    private function cleanOldData(): void
    {
        $prefix = self::PREFIX;
        $academyId = $this->academy->id;

        // Clean in reverse dependency order
        SessionRecording::where('meeting_room', 'like', "{$prefix}%")->forceDelete();
        StudentSessionReport::where('academy_id', $academyId)->where('notes', 'like', "{$prefix}%")->forceDelete();
        AcademicSessionReport::where('academy_id', $academyId)->where('notes', 'like', "{$prefix}%")->forceDelete();
        QuranSession::withoutGlobalScopes()->where('academy_id', $academyId)->where('title', 'like', "{$prefix}%")->forceDelete();
        AcademicSession::withoutGlobalScopes()->where('academy_id', $academyId)->where('title', 'like', "{$prefix}%")->forceDelete();
        InteractiveCourseSession::withoutGlobalScopes()->where('title', 'like', "{$prefix}%")->forceDelete();
        QuranSubscription::withoutGlobalScopes()->where('academy_id', $academyId)->where('admin_notes', 'like', "{$prefix}%")->forceDelete();
        AcademicSubscription::withoutGlobalScopes()->where('academy_id', $academyId)->where('admin_notes', 'like', "{$prefix}%")->forceDelete();
        QuranIndividualCircle::withoutGlobalScopes()->where('academy_id', $academyId)->where('name', 'like', "{$prefix}%")->forceDelete();
        AcademicIndividualLesson::withoutGlobalScopes()->where('academy_id', $academyId)->where('name', 'like', "{$prefix}%")->forceDelete();
        InteractiveCourse::withoutGlobalScopes()->where('academy_id', $academyId)->where('title', 'like', "{$prefix}%")->forceDelete();

        $this->command->info('Cleaned old E2E test data.');
    }

    private function ensureProfiles(): void
    {
        $this->quranTeacherProfile = QuranTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $this->quranTeacher->id)
            ->where('academy_id', $this->academy->id)
            ->first();

        if (! $this->quranTeacherProfile) {
            $this->quranTeacherProfile = QuranTeacherProfile::create([
                'user_id' => $this->quranTeacher->id,
                'academy_id' => $this->academy->id,
                'teacher_code' => 'QT-E2E-'.Str::random(4),
                'specialization' => 'memorization',
                'is_active' => true,
            ]);
            $this->command->info("Created QuranTeacherProfile ID: {$this->quranTeacherProfile->id}");
        }

        $this->academicTeacherProfile = AcademicTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $this->academicTeacher->id)
            ->where('academy_id', $this->academy->id)
            ->first();

        if (! $this->academicTeacherProfile) {
            $this->academicTeacherProfile = AcademicTeacherProfile::create([
                'user_id' => $this->academicTeacher->id,
                'academy_id' => $this->academy->id,
                'teacher_code' => 'AT-E2E-'.Str::random(4),
                'is_active' => true,
            ]);
            $this->command->info("Created AcademicTeacherProfile ID: {$this->academicTeacherProfile->id}");
        }

        $this->studentProfile = StudentProfile::withoutGlobalScopes()
            ->where('user_id', $this->student->id)
            ->first();

        if (! $this->studentProfile) {
            $this->studentProfile = StudentProfile::create([
                'user_id' => $this->student->id,
                'student_code' => 'STU-E2E-'.Str::random(4),
            ]);
            $this->command->info("Created StudentProfile ID: {$this->studentProfile->id}");
        }
    }

    private function createQuranCircle(): QuranIndividualCircle
    {
        return QuranIndividualCircle::create([
            'academy_id' => $this->academy->id,
            'quran_teacher_id' => $this->quranTeacher->id,
            'student_id' => $this->student->id,
            'circle_code' => 'QIC-E2E-'.Str::random(4),
            'name' => self::PREFIX.' حلقة قرآن اختبارية',
            'specialization' => 'memorization',
            'memorization_level' => 'intermediate',
            'total_sessions' => 20,
            'sessions_scheduled' => 7,
            'sessions_completed' => 3,
            'sessions_remaining' => 10,
            'default_duration_minutes' => 45,
            'is_active' => true,
            'started_at' => now()->subDays(30),
        ]);
    }

    private function createAcademicLesson(): AcademicIndividualLesson
    {
        // Ensure a subject and grade level exist
        $subject = AcademicSubject::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->first();

        if (! $subject) {
            $subject = AcademicSubject::create([
                'academy_id' => $this->academy->id,
                'name' => self::PREFIX.' الرياضيات',
                'name_en' => 'Mathematics',
                'is_active' => true,
            ]);
        }

        $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->first();

        if (! $gradeLevel) {
            $gradeLevel = AcademicGradeLevel::create([
                'academy_id' => $this->academy->id,
                'name' => self::PREFIX.' الصف السادس',
                'name_en' => 'Grade 6',
                'is_active' => true,
            ]);
        }

        return AcademicIndividualLesson::create([
            'academy_id' => $this->academy->id,
            'academic_teacher_id' => $this->academicTeacherProfile->id,
            'student_id' => $this->student->id,
            'academic_subject_id' => $subject->id,
            'academic_grade_level_id' => $gradeLevel->id,
            'lesson_code' => 'AL-E2E-'.Str::random(4),
            'name' => self::PREFIX.' درس أكاديمي اختباري',
            'total_sessions' => 20,
            'sessions_scheduled' => 7,
            'sessions_completed' => 3,
            'sessions_remaining' => 10,
            'default_duration_minutes' => 60,
            'status' => 'active',
            'started_at' => now()->subDays(30),
        ]);
    }

    private function createQuranSubscriptions(QuranIndividualCircle $circle): array
    {
        $subs = [];
        $statuses = [
            'active' => SessionSubscriptionStatus::ACTIVE,
            'pending' => SessionSubscriptionStatus::PENDING,
            'paused' => SessionSubscriptionStatus::PAUSED,
            'cancelled' => SessionSubscriptionStatus::CANCELLED,
        ];

        // Use withoutEvents to bypass duplicate subscription validation and notification hooks
        QuranSubscription::withoutEvents(function () use ($statuses, $circle, &$subs) {
            foreach ($statuses as $key => $status) {
                $subs[$key] = QuranSubscription::create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'quran_teacher_id' => $this->quranTeacher->id,
                    'subscription_code' => 'QS-E2E-'.strtoupper($key).'-'.Str::random(4),
                    'subscription_type' => 'individual',
                    'status' => $status,
                    'total_sessions' => 12,
                    'total_sessions_scheduled' => $key === 'active' ? 5 : 0,
                    'total_sessions_completed' => $key === 'active' ? 3 : 0,
                    'total_sessions_missed' => 0,
                    'sessions_used' => $key === 'active' ? 3 : 0,
                    'sessions_remaining' => $key === 'active' ? 9 : 12,
                    'total_price' => 300.00,
                    'currency' => 'SAR',
                    'starts_at' => $key === 'active' ? now()->subDays(15) : ($key === 'pending' ? null : now()->subDays(30)),
                    'ends_at' => $key === 'active' ? now()->addDays(15) : ($key === 'pending' ? null : now()->subDays(1)),
                    'auto_renew' => true,
                    'admin_notes' => self::PREFIX." اشتراك قرآن - {$key}",
                    'education_unit_id' => $circle->id,
                    'education_unit_type' => QuranIndividualCircle::class,
                    'paused_at' => $key === 'paused' ? now()->subDays(5) : null,
                    'pause_reason' => $key === 'paused' ? 'سفر' : null,
                ]);
            }
        });

        $this->command->info('Created 4 Quran subscriptions (all statuses).');

        return $subs;
    }

    private function createAcademicSubscriptions(AcademicIndividualLesson $lesson): array
    {
        $subs = [];
        $statuses = [
            'active' => SessionSubscriptionStatus::ACTIVE,
            'pending' => SessionSubscriptionStatus::PENDING,
            'paused' => SessionSubscriptionStatus::PAUSED,
            'cancelled' => SessionSubscriptionStatus::CANCELLED,
        ];

        // Use withoutEvents to bypass validation hooks and notification observers
        AcademicSubscription::withoutEvents(function () use ($statuses, $lesson, &$subs) {
            foreach ($statuses as $key => $status) {
                $subs[$key] = AcademicSubscription::create([
                    'academy_id' => $this->academy->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->academicTeacherProfile->id,
                    'subject_id' => $lesson->academic_subject_id,
                    'grade_level_id' => $lesson->academic_grade_level_id,
                    'subject_name' => 'الرياضيات',
                    'grade_level_name' => 'الصف السادس',
                    'subscription_code' => 'ACD-E2E-'.strtoupper($key).'-'.Str::random(4),
                    'subscription_type' => 'private',
                    'status' => $status,
                    'sessions_per_week' => 3,
                    'monthly_amount' => 500.00,
                    'final_monthly_amount' => 500.00,
                    'currency' => 'SAR',
                    'billing_cycle' => 'monthly',
                    'total_sessions' => 12,
                    'total_sessions_scheduled' => $key === 'active' ? 5 : 0,
                    'total_sessions_completed' => $key === 'active' ? 3 : 0,
                    'total_sessions_missed' => 0,
                    'sessions_used' => $key === 'active' ? 3 : 0,
                    'sessions_remaining' => $key === 'active' ? 9 : 12,
                    'start_date' => $key === 'active' ? now()->subDays(15) : ($key === 'pending' ? null : now()->subDays(30)),
                    'end_date' => $key === 'active' ? now()->addDays(15) : ($key === 'pending' ? null : now()->subDays(1)),
                    'starts_at' => $key === 'active' ? now()->subDays(15) : ($key === 'pending' ? null : now()->subDays(30)),
                    'ends_at' => $key === 'active' ? now()->addDays(15) : ($key === 'pending' ? null : now()->subDays(1)),
                    'auto_renew' => true,
                    'admin_notes' => self::PREFIX." اشتراك أكاديمي - {$key}",
                ]);
            }
        });

        $this->command->info('Created 4 Academic subscriptions (all statuses).');

        return $subs;
    }

    private function createQuranSessions(QuranIndividualCircle $circle, ?QuranSubscription $activeSub): array
    {
        $sessions = [];

        $statusConfigs = [
            'unscheduled' => [
                'status' => SessionStatus::UNSCHEDULED,
                'scheduled_at' => null,
            ],
            'scheduled' => [
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(3),
            ],
            'ready' => [
                'status' => SessionStatus::READY,
                'scheduled_at' => now()->addMinutes(10),
                'meeting_room_name' => 'e2e-quran-ready-'.Str::random(6),
                'meeting_link' => 'https://meet.example.com/e2e-'.Str::random(8),
            ],
            'ongoing' => [
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subMinutes(20),
                'started_at' => now()->subMinutes(20),
                'meeting_room_name' => 'e2e-quran-ongoing-'.Str::random(6),
            ],
            'completed' => [
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(2),
                'started_at' => now()->subDays(2),
                'ended_at' => now()->subDays(2)->addMinutes(45),
                'actual_duration_minutes' => 45,
            ],
            'cancelled' => [
                'status' => SessionStatus::CANCELLED,
                'scheduled_at' => now()->subDays(1),
                'cancelled_at' => now()->subDays(1),
                'cancellation_reason' => 'ظروف طارئة - اختبار E2E',
            ],
            'absent' => [
                'status' => SessionStatus::ABSENT,
                'scheduled_at' => now()->subDays(3),
                'ended_at' => now()->subDays(3)->addMinutes(45),
            ],
        ];

        QuranSession::withoutEvents(function () use ($statusConfigs, $activeSub, &$sessions) {
            foreach ($statusConfigs as $key => $config) {
                $sessions[$key] = QuranSession::create(array_merge([
                    'academy_id' => $this->academy->id,
                    'quran_teacher_id' => $this->quranTeacher->id,
                    'student_id' => $this->student->id,
                    'session_type' => 'individual',
                    'title' => self::PREFIX." جلسة قرآن - {$key}",
                    'session_code' => 'QS-E2E-'.strtoupper(substr($key, 0, 4)).'-'.Str::random(4),
                    'duration_minutes' => 45,
                    'quran_subscription_id' => $activeSub?->id,
                ], $config));
            }
        });

        $this->command->info('Created 7 Quran sessions (all statuses).');

        return $sessions;
    }

    private function createAcademicSessions(AcademicIndividualLesson $lesson, ?AcademicSubscription $activeSub): array
    {
        $sessions = [];

        $statusConfigs = [
            'unscheduled' => [
                'status' => SessionStatus::UNSCHEDULED,
                'scheduled_at' => null,
            ],
            'scheduled' => [
                'status' => SessionStatus::SCHEDULED,
                'scheduled_at' => now()->addDays(2),
            ],
            'ready' => [
                'status' => SessionStatus::READY,
                'scheduled_at' => now()->addMinutes(5),
                'meeting_room_name' => 'e2e-academic-ready-'.Str::random(6),
                'meeting_link' => 'https://meet.example.com/e2e-'.Str::random(8),
            ],
            'ongoing' => [
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now()->subMinutes(15),
                'started_at' => now()->subMinutes(15),
                'meeting_room_name' => 'e2e-academic-ongoing-'.Str::random(6),
            ],
            'completed' => [
                'status' => SessionStatus::COMPLETED,
                'scheduled_at' => now()->subDays(1),
                'started_at' => now()->subDays(1),
                'ended_at' => now()->subDays(1)->addMinutes(60),
                'actual_duration_minutes' => 60,
            ],
            'cancelled' => [
                'status' => SessionStatus::CANCELLED,
                'scheduled_at' => now()->subHours(6),
                'cancelled_at' => now()->subHours(6),
                'cancellation_reason' => 'إلغاء اختباري - E2E',
            ],
            'absent' => [
                'status' => SessionStatus::ABSENT,
                'scheduled_at' => now()->subDays(4),
                'ended_at' => now()->subDays(4)->addMinutes(60),
            ],
        ];

        AcademicSession::withoutEvents(function () use ($statusConfigs, $lesson, $activeSub, &$sessions) {
            foreach ($statusConfigs as $key => $config) {
                $sessions[$key] = AcademicSession::create(array_merge([
                    'academy_id' => $this->academy->id,
                    'academic_teacher_id' => $this->academicTeacherProfile->id,
                    'student_id' => $this->student->id,
                    'session_type' => 'individual',
                    'title' => self::PREFIX." جلسة أكاديمية - {$key}",
                    'session_code' => 'AS-E2E-'.strtoupper(substr($key, 0, 4)).'-'.Str::random(4),
                    'duration_minutes' => 60,
                    'academic_subscription_id' => $activeSub?->id,
                    'subscription_counted' => false,
                    'recording_enabled' => false,
                    'homework_assigned' => $key === 'completed',
                ], $config));
            }
        });

        $this->command->info('Created 7 Academic sessions (all statuses).');

        return $sessions;
    }

    private function createReports(array $quranSessions, array $academicSessions): void
    {
        // Unique constraint: (session_id, student_id) — one report per student per session.
        // Create extra completed sessions so each attendance status has its own session.
        // Note: DB enum uses 'leaved' but PHP enum uses 'left'. Skip LEFT to avoid mismatch.
        $quranReportConfigs = [
            ['attendance' => AttendanceStatus::ATTENDED, 'memo' => 9.5, 'review' => 8.0, 'pct' => 100],
            ['attendance' => AttendanceStatus::LATE, 'memo' => 7.0, 'review' => 6.5, 'pct' => 85],
            ['attendance' => AttendanceStatus::ABSENT, 'memo' => null, 'review' => null, 'pct' => 0],
        ];

        QuranSession::withoutEvents(function () use ($quranReportConfigs) {
            foreach ($quranReportConfigs as $i => $config) {
                $session = QuranSession::create([
                    'academy_id' => $this->academy->id,
                    'quran_teacher_id' => $this->quranTeacher->id,
                    'student_id' => $this->student->id,
                    'session_type' => 'individual',
                    'title' => self::PREFIX." جلسة قرآن مكتملة - تقرير {$config['attendance']->value}",
                    'session_code' => 'QS-E2E-RPT'.($i + 1).'-'.Str::random(4),
                    'status' => SessionStatus::COMPLETED,
                    'scheduled_at' => now()->subDays(5 + $i),
                    'started_at' => now()->subDays(5 + $i),
                    'ended_at' => now()->subDays(5 + $i)->addMinutes(45),
                    'actual_duration_minutes' => 45,
                    'duration_minutes' => 45,
                ]);

                StudentSessionReport::create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->quranTeacher->id,
                    'academy_id' => $this->academy->id,
                    'attendance_status' => $config['attendance'],
                    'attendance_percentage' => $config['pct'],
                    'new_memorization_degree' => $config['memo'],
                    'reservation_degree' => $config['review'],
                    'is_late' => $config['attendance'] === AttendanceStatus::LATE,
                    'late_minutes' => $config['attendance'] === AttendanceStatus::LATE ? 10 : 0,
                    'notes' => self::PREFIX." تقرير قرآن - {$config['attendance']->value}",
                    'is_calculated' => true,
                    'manually_evaluated' => false,
                    'evaluated_at' => now()->subDays(5 + $i),
                ]);
            }
        });

        $this->command->info('Created 3 Quran session reports (each with own completed session).');

        $academicReportConfigs = [
            ['attendance' => AttendanceStatus::ATTENDED, 'homework_completion' => 9.0, 'understanding' => 8.5, 'pct' => 100],
            ['attendance' => AttendanceStatus::LATE, 'homework_completion' => 6.5, 'understanding' => 7.0, 'pct' => 80],
            ['attendance' => AttendanceStatus::ABSENT, 'homework_completion' => null, 'understanding' => null, 'pct' => 0],
        ];

        AcademicSession::withoutEvents(function () use ($academicReportConfigs) {
            foreach ($academicReportConfigs as $i => $config) {
                $session = AcademicSession::create([
                    'academy_id' => $this->academy->id,
                    'academic_teacher_id' => $this->academicTeacherProfile->id,
                    'student_id' => $this->student->id,
                    'session_type' => 'individual',
                    'title' => self::PREFIX." جلسة أكاديمية مكتملة - تقرير {$config['attendance']->value}",
                    'session_code' => 'AS-E2E-RPT'.($i + 1).'-'.Str::random(4),
                    'status' => SessionStatus::COMPLETED,
                    'scheduled_at' => now()->subDays(4 + $i),
                    'started_at' => now()->subDays(4 + $i),
                    'ended_at' => now()->subDays(4 + $i)->addMinutes(60),
                    'actual_duration_minutes' => 60,
                    'duration_minutes' => 60,
                    'subscription_counted' => false,
                    'recording_enabled' => false,
                    'homework_assigned' => true,
                ]);

                AcademicSessionReport::create([
                    'session_id' => $session->id,
                    'student_id' => $this->student->id,
                    'teacher_id' => $this->academicTeacher->id,
                    'academy_id' => $this->academy->id,
                    'attendance_status' => $config['attendance'],
                    'attendance_percentage' => $config['pct'],
                    'homework_completion_degree' => $config['homework_completion'],
                    'lesson_understanding_degree' => $config['understanding'],
                    'is_late' => $config['attendance'] === AttendanceStatus::LATE,
                    'late_minutes' => $config['attendance'] === AttendanceStatus::LATE ? 8 : 0,
                    'notes' => self::PREFIX." تقرير أكاديمي - {$config['attendance']->value}",
                    'is_calculated' => true,
                    'manually_evaluated' => false,
                    'evaluated_at' => now()->subDays(4 + $i),
                ]);
            }
        });

        $this->command->info('Created 3 Academic session reports (each with own completed session).');
    }

    private function createRecordings(): void
    {
        // Create an interactive course for recordings
        // Find or create a subject for the course
        $subject = AcademicSubject::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->first();

        $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)
            ->first();

        $course = InteractiveCourse::create([
            'academy_id' => $this->academy->id,
            'assigned_teacher_id' => $this->academicTeacherProfile->id,
            'subject_id' => $subject->id,
            'grade_level_id' => $gradeLevel->id,
            'course_code' => 'IC-E2E-'.Str::random(6),
            'title' => self::PREFIX.' دورة تفاعلية اختبارية',
            'description' => 'دورة اختبارية لفحص تسجيلات الجلسات',
            'difficulty_level' => 'intermediate',
            'max_students' => 30,
            'duration_weeks' => 8,
            'sessions_per_week' => 2,
            'session_duration_minutes' => 60,
            'total_sessions' => 10,
            'student_price' => 200.00,
            'teacher_payment' => 100.00,
            'payment_type' => 'fixed_amount',
            'start_date' => now()->subDays(14),
            'enrollment_deadline' => now()->subDays(16),
            'status' => 'published',
            'is_published' => true,
            'certificate_enabled' => false,
            'recording_enabled' => true,
            'schedule' => json_encode([
                ['day' => 'sunday', 'start_time' => '10:00', 'end_time' => '11:00'],
                ['day' => 'tuesday', 'start_time' => '10:00', 'end_time' => '11:00'],
            ]),
        ]);

        // Create an interactive course session
        $icsSession = InteractiveCourseSession::create([
            'course_id' => $course->id,
            'session_number' => 1,
            'title' => self::PREFIX.' جلسة تفاعلية - مكتملة',
            'status' => SessionStatus::COMPLETED,
            'scheduled_at' => now()->subDays(3),
            'started_at' => now()->subDays(3),
            'ended_at' => now()->subDays(3)->addMinutes(60),
            'duration_minutes' => 60,
        ]);

        // Create recordings in different statuses
        $recordingConfigs = [
            [
                'status' => RecordingStatus::COMPLETED,
                'started_at' => now()->subDays(3),
                'ended_at' => now()->subDays(3)->addMinutes(55),
                'duration' => 3300,
                'file_path' => '/recordings/e2e-completed.mp4',
                'file_name' => 'e2e-recording-completed.mp4',
                'file_size' => 150_000_000,
                'file_format' => 'mp4',
                'processed_at' => now()->subDays(3)->addHours(1),
                'completed_at' => now()->subDays(3)->addHours(1),
            ],
            [
                'status' => RecordingStatus::PROCESSING,
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
                'duration' => 3600,
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_format' => 'mp4',
                'processed_at' => null,
                'completed_at' => null,
            ],
            [
                'status' => RecordingStatus::FAILED,
                'started_at' => now()->subDays(1),
                'ended_at' => now()->subDays(1)->addMinutes(30),
                'duration' => 1800,
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_format' => 'mp4',
                'processed_at' => null,
                'completed_at' => null,
                'processing_error' => 'Egress processing timeout',
            ],
        ];

        foreach ($recordingConfigs as $config) {
            SessionRecording::create(array_merge([
                'recordable_type' => InteractiveCourseSession::class,
                'recordable_id' => $icsSession->id,
                'recording_id' => 'EG_e2e_'.Str::random(8),
                'meeting_room' => self::PREFIX.' room-'.Str::random(6),
                'metadata' => ['session_type' => 'interactive_course', 'e2e_test' => true],
            ], $config));
        }

        $this->command->info('Created 3 session recordings (completed, processing, failed).');
    }
}
