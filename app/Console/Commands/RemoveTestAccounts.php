<?php

namespace App\Console\Commands;

use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\Certificate;
use App\Models\ChatGroup;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\QuranCircle;
use App\Models\QuranCircleEnrollment;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\StudentProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherReview;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveTestAccounts extends Command
{
    protected $signature = 'app:remove-test-accounts
        {--dry-run : Preview what will be deleted without executing}
        {--force : Skip confirmation prompt}';

    protected $description = 'Permanently remove specific test accounts and all linked data';

    private const EMAILS = [
        'abdelsitir2030@gmail.com',  // Teacher
        'abdelsitir2011@gmail.com',  // Student
        'abdelsitir2020@gmail.com',  // Student
        'abdelsitir20304@gmail.com', // Student
    ];

    private const TEACHER_EMAIL = 'abdelsitir2030@gmail.com';

    protected bool $dryRun = false;

    protected array $deletionLog = [];

    // Resolved IDs
    protected array $allUserIds = [];

    protected array $studentUserIds = [];

    protected ?int $teacherUserId = null;

    protected ?int $teacherProfileId = null;

    protected array $studentProfileIds = [];

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Remove Test Accounts '.($this->dryRun ? '(DRY RUN)' : ''));
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Phase 0: Resolve users
        if (! $this->resolveUsers()) {
            return self::FAILURE;
        }

        // Show deletion plan (counts)
        $this->showDeletionPlan();

        if ($this->dryRun) {
            $this->newLine();
            $this->info('DRY RUN COMPLETE - No data was deleted');
            $this->comment('Run without --dry-run to execute actual deletion');

            return self::SUCCESS;
        }

        // Confirm
        if (! $this->option('force')) {
            if (! $this->confirm('This will PERMANENTLY delete all data for these 4 accounts. Continue?')) {
                $this->error('Operation cancelled.');

                return self::FAILURE;
            }
        }

        // Execute
        $this->newLine();
        $this->info('Executing deletion...');

        try {
            DB::transaction(function () {
                $this->phase1LeafData();
                $this->phase2SessionsAndCircles();
                $this->phase3SubscriptionsAndPayments();
                $this->phase4ChatAndTokens();
                $this->phase5Users();
            });

            $this->newLine();
            $this->info('✅ Deletion completed successfully!');
            $this->newLine();
            $this->showDeletionSummary();
            $this->performVerification();

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Deletion failed: '.$e->getMessage());
            $this->error('Transaction rolled back - no data was deleted');

            return self::FAILURE;
        }
    }

    protected function resolveUsers(): bool
    {
        $users = User::withTrashed()->whereIn('email', self::EMAILS)->get();

        if ($users->isEmpty()) {
            $this->error('No users found with the specified emails.');

            return false;
        }

        $this->info('Found '.count($users).' of '.count(self::EMAILS).' accounts:');
        foreach ($users as $user) {
            $role = $user->email === self::TEACHER_EMAIL ? 'TEACHER' : 'STUDENT';
            $trashed = $user->trashed() ? ' (soft-deleted)' : '';
            $this->line("  [{$role}] {$user->email} — ID: {$user->id}, Name: {$user->first_name} {$user->last_name}{$trashed}");
        }

        // Report missing accounts
        $foundEmails = $users->pluck('email')->toArray();
        $missing = array_diff(self::EMAILS, $foundEmails);
        foreach ($missing as $email) {
            $this->warn("  [MISSING] {$email} — not found in database");
        }

        $this->allUserIds = $users->pluck('id')->toArray();

        $teacherUser = $users->firstWhere('email', self::TEACHER_EMAIL);
        $this->teacherUserId = $teacherUser?->id;

        $this->studentUserIds = $users->where('email', '!=', self::TEACHER_EMAIL)->pluck('id')->toArray();

        // Resolve teacher profile
        if ($this->teacherUserId) {
            $teacherProfile = QuranTeacherProfile::withoutGlobalScopes()
                ->withTrashed()
                ->where('user_id', $this->teacherUserId)
                ->first();
            $this->teacherProfileId = $teacherProfile?->id;
            $this->line("  Teacher profile ID: ".($this->teacherProfileId ?? 'none'));
        }

        // Resolve student profiles
        $studentProfiles = StudentProfile::withoutGlobalScopes()
            ->withTrashed()
            ->whereIn('user_id', $this->studentUserIds)
            ->get();
        $this->studentProfileIds = $studentProfiles->pluck('id')->toArray();
        $this->line("  Student profile IDs: ".implode(', ', $this->studentProfileIds ?: ['none']));

        $this->newLine();

        return true;
    }

    protected function showDeletionPlan(): void
    {
        $this->info('Deletion Plan:');
        $this->line('───────────────────────────────────────────────────────────────');

        $counts = [
            'Quiz attempts' => QuizAttempt::whereIn('student_id', $this->studentProfileIds ?: [0])->count(),
            'Academic homework submissions' => AcademicHomeworkSubmission::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            'Course homework submissions' => InteractiveCourseHomeworkSubmission::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            'Session reports' => DB::table('student_session_reports')->whereIn('student_id', $this->allUserIds)->count()
                + DB::table('academic_session_reports')->whereIn('student_id', $this->allUserIds)->count()
                + DB::table('interactive_session_reports')->whereIn('student_id', $this->allUserIds)->count(),
            'Meeting attendances' => DB::table('meeting_attendances')->whereIn('user_id', $this->allUserIds)->count(),
            'Teacher earnings' => $this->teacherProfileId
                ? TeacherEarning::withoutGlobalScopes()->withTrashed()
                    ->where('teacher_type', QuranTeacherProfile::class)
                    ->where('teacher_id', $this->teacherProfileId)->count()
                : 0,
            'Teacher reviews' => $this->teacherProfileId
                ? TeacherReview::withoutGlobalScopes()->withTrashed()
                    ->where('reviewable_type', QuranTeacherProfile::class)
                    ->where('reviewable_id', $this->teacherProfileId)->count()
                : 0,
            'Certificates' => Certificate::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            'Quran sessions' => QuranSession::withoutGlobalScopes()->withTrashed()
                ->where(fn ($q) => $q->whereIn('student_id', $this->studentUserIds)
                    ->when($this->teacherUserId, fn ($q2) => $q2->orWhere('quran_teacher_id', $this->teacherUserId))
                )->count(),
            'Academic sessions' => AcademicSession::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->count(),
            'Individual circles' => QuranIndividualCircle::withoutGlobalScopes()->withTrashed()
                ->where(fn ($q) => $q->whereIn('student_id', $this->studentUserIds)
                    ->when($this->teacherUserId, fn ($q2) => $q2->orWhere('quran_teacher_id', $this->teacherUserId))
                )->count(),
            'Group circles (teacher)' => $this->teacherUserId
                ? QuranCircle::withoutGlobalScopes()->withTrashed()->where('quran_teacher_id', $this->teacherUserId)->count()
                : 0,
            'Circle enrollments' => QuranCircleEnrollment::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            'Quran subscriptions' => QuranSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            'Academic subscriptions' => AcademicSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->count(),
            'Course subscriptions' => CourseSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->count(),
            'Course enrollments' => InteractiveCourseEnrollment::withoutGlobalScopes()->whereIn('student_id', $this->studentProfileIds ?: [0])->count(),
            'Payments' => Payment::withoutGlobalScopes()->withTrashed()->whereIn('user_id', $this->allUserIds)->count(),
            'Trial requests' => QuranTrialRequest::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            'Chat groups' => ChatGroup::withoutGlobalScopes()->withTrashed()->whereIn('owner_id', $this->allUserIds)->count(),
            'Chat memberships' => DB::table('chat_group_members')->whereIn('user_id', $this->allUserIds)->count(),
            'WireChat participants' => DB::table('wire_participants')
                ->where('participantable_type', User::class)
                ->whereIn('participantable_id', $this->allUserIds)->count(),
            'WireChat messages' => DB::table('wire_messages')
                ->whereIn('participant_id', DB::table('wire_participants')
                    ->where('participantable_type', User::class)
                    ->whereIn('participantable_id', $this->allUserIds)
                    ->pluck('id')
                )->count(),
            'Notifications' => DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $this->allUserIds)->count(),
            'Device tokens' => DB::table('device_tokens')->whereIn('user_id', $this->allUserIds)->count(),
            'Access tokens' => DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $this->allUserIds)->count(),
            'Saved payment methods' => DB::table('saved_payment_methods')->whereIn('user_id', $this->allUserIds)->count(),
            'Users' => count($this->allUserIds),
        ];

        $total = 0;
        foreach ($counts as $label => $count) {
            if ($count > 0) {
                $this->line("  {$label}: {$count}");
                $total += $count;
            }
        }

        $this->newLine();
        $this->info("Total records to delete: {$total}");

        // Safety check for teacher circles with production students
        if ($this->teacherUserId) {
            $teacherCircles = QuranCircle::withoutGlobalScopes()->withTrashed()
                ->where('quran_teacher_id', $this->teacherUserId)->get();

            foreach ($teacherCircles as $circle) {
                $prodStudents = DB::table('quran_circle_students')
                    ->where('quran_circle_id', $circle->id)
                    ->whereNotIn('student_id', $this->allUserIds)
                    ->count();

                if ($prodStudents > 0) {
                    $this->warn("  ⚠️  Circle '{$circle->name}' (ID:{$circle->id}) has {$prodStudents} production students — teacher will be unlinked, circle preserved");
                } else {
                    $this->line("  Circle '{$circle->name}' (ID:{$circle->id}) — test-only, will be deleted");
                }
            }
        }
    }

    // ─── Phase 1: Leaf data ───────────────────────────────────────────

    protected function phase1LeafData(): void
    {
        $this->info('Phase 1: Deleting leaf data...');

        // Quiz attempts (uses StudentProfile.id)
        $this->deleteAndLog('Quiz attempts',
            QuizAttempt::whereIn('student_id', $this->studentProfileIds ?: [0])->count(),
            fn () => QuizAttempt::whereIn('student_id', $this->studentProfileIds ?: [0])->delete()
        );

        // Homework submissions
        $this->deleteAndLog('Academic homework submissions',
            AcademicHomeworkSubmission::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            fn () => AcademicHomeworkSubmission::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->forceDelete()
        );

        $this->deleteAndLog('Course homework submissions',
            InteractiveCourseHomeworkSubmission::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            fn () => InteractiveCourseHomeworkSubmission::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->forceDelete()
        );

        // Session reports
        $this->deleteAndLog('Student session reports',
            DB::table('student_session_reports')->whereIn('student_id', $this->allUserIds)->count(),
            fn () => DB::table('student_session_reports')->whereIn('student_id', $this->allUserIds)->delete()
        );
        $this->deleteAndLog('Academic session reports',
            DB::table('academic_session_reports')->whereIn('student_id', $this->allUserIds)->count(),
            fn () => DB::table('academic_session_reports')->whereIn('student_id', $this->allUserIds)->delete()
        );
        $this->deleteAndLog('Interactive session reports',
            DB::table('interactive_session_reports')->whereIn('student_id', $this->allUserIds)->count(),
            fn () => DB::table('interactive_session_reports')->whereIn('student_id', $this->allUserIds)->delete()
        );

        // Meeting attendances
        $this->deleteAndLog('Meeting attendances',
            DB::table('meeting_attendances')->whereIn('user_id', $this->allUserIds)->count(),
            fn () => DB::table('meeting_attendances')->whereIn('user_id', $this->allUserIds)->delete()
        );

        // Polymorphic attendances (table may not exist on all environments)
        if (Schema::hasTable('attendances')) {
            $sessionIds = $this->getAllSessionIds();
            if ($sessionIds['quran']->isNotEmpty() || $sessionIds['academic']->isNotEmpty()) {
                $count = DB::table('attendances')
                    ->where(function ($q) use ($sessionIds) {
                        $q->where(function ($sub) use ($sessionIds) {
                            $sub->where('attendanceable_type', 'App\\Models\\QuranSession')
                                ->whereIn('attendanceable_id', $sessionIds['quran']);
                        })->orWhere(function ($sub) use ($sessionIds) {
                            $sub->where('attendanceable_type', 'App\\Models\\AcademicSession')
                                ->whereIn('attendanceable_id', $sessionIds['academic']);
                        });
                    })->count();

                $this->deleteAndLog('Polymorphic attendances', $count, function () use ($sessionIds) {
                    DB::table('attendances')
                        ->where(function ($q) use ($sessionIds) {
                            $q->where(function ($sub) use ($sessionIds) {
                                $sub->where('attendanceable_type', 'App\\Models\\QuranSession')
                                    ->whereIn('attendanceable_id', $sessionIds['quran']);
                            })->orWhere(function ($sub) use ($sessionIds) {
                                $sub->where('attendanceable_type', 'App\\Models\\AcademicSession')
                                    ->whereIn('attendanceable_id', $sessionIds['academic']);
                            });
                        })->delete();
                });
            }
        }

        // Teacher earnings (polymorphic)
        if ($this->teacherProfileId) {
            $this->deleteAndLog('Teacher earnings',
                TeacherEarning::withoutGlobalScopes()->withTrashed()
                    ->where('teacher_type', QuranTeacherProfile::class)
                    ->where('teacher_id', $this->teacherProfileId)->count(),
                fn () => TeacherEarning::withoutGlobalScopes()->withTrashed()
                    ->where('teacher_type', QuranTeacherProfile::class)
                    ->where('teacher_id', $this->teacherProfileId)->forceDelete()
            );

            $this->deleteAndLog('Teacher reviews',
                TeacherReview::withoutGlobalScopes()->withTrashed()
                    ->where('reviewable_type', QuranTeacherProfile::class)
                    ->where('reviewable_id', $this->teacherProfileId)->count(),
                fn () => TeacherReview::withoutGlobalScopes()->withTrashed()
                    ->where('reviewable_type', QuranTeacherProfile::class)
                    ->where('reviewable_id', $this->teacherProfileId)->forceDelete()
            );
        }

        // Certificates
        $this->deleteAndLog('Certificates',
            Certificate::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            fn () => Certificate::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->forceDelete()
        );
    }

    // ─── Phase 2: Sessions and circles ────────────────────────────────

    protected function phase2SessionsAndCircles(): void
    {
        $this->info('Phase 2: Deleting sessions and circles...');

        $sessionIds = $this->getAllSessionIds();

        // Session homeworks
        if ($sessionIds['quran']->isNotEmpty()) {
            $this->deleteAndLog('Quran session homeworks',
                DB::table('quran_session_homeworks')->whereIn('session_id', $sessionIds['quran'])->count(),
                fn () => DB::table('quran_session_homeworks')->whereIn('session_id', $sessionIds['quran'])->delete()
            );
        }

        // Session meetings (polymorphic, table may not exist)
        if (Schema::hasTable('base_session_meetings') && ($sessionIds['quran']->isNotEmpty() || $sessionIds['academic']->isNotEmpty())) {
            $count = DB::table('base_session_meetings')
                ->where(function ($q) use ($sessionIds) {
                    $q->where(function ($sub) use ($sessionIds) {
                        $sub->where('meetingable_type', 'App\\Models\\QuranSession')
                            ->whereIn('meetingable_id', $sessionIds['quran']);
                    })->orWhere(function ($sub) use ($sessionIds) {
                        $sub->where('meetingable_type', 'App\\Models\\AcademicSession')
                            ->whereIn('meetingable_id', $sessionIds['academic']);
                    });
                })->count();

            $this->deleteAndLog('Session meetings', $count, function () use ($sessionIds) {
                DB::table('base_session_meetings')
                    ->where(function ($q) use ($sessionIds) {
                        $q->where(function ($sub) use ($sessionIds) {
                            $sub->where('meetingable_type', 'App\\Models\\QuranSession')
                                ->whereIn('meetingable_id', $sessionIds['quran']);
                        })->orWhere(function ($sub) use ($sessionIds) {
                            $sub->where('attendanceable_type', 'App\\Models\\AcademicSession')
                                ->whereIn('meetingable_id', $sessionIds['academic']);
                        });
                    })->delete();
            });
        }

        // Quran sessions
        $quranSessionQuery = QuranSession::withoutGlobalScopes()->withTrashed()
            ->where(fn ($q) => $q->whereIn('student_id', $this->studentUserIds)
                ->when($this->teacherUserId, fn ($q2) => $q2->orWhere('quran_teacher_id', $this->teacherUserId))
            );
        $this->deleteAndLog('Quran sessions', $quranSessionQuery->count(), fn () => $quranSessionQuery->forceDelete());

        // Academic sessions
        $this->deleteAndLog('Academic sessions',
            AcademicSession::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->count(),
            fn () => AcademicSession::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->forceDelete()
        );

        // Circle schedules (for teacher's circles)
        if ($this->teacherUserId) {
            $teacherCircleIds = QuranCircle::withoutGlobalScopes()->withTrashed()
                ->where('quran_teacher_id', $this->teacherUserId)->pluck('id');

            if ($teacherCircleIds->isNotEmpty()) {
                $this->deleteAndLog('Circle schedules',
                    DB::table('quran_circle_schedules')->whereIn('quran_circle_id', $teacherCircleIds)->count(),
                    fn () => DB::table('quran_circle_schedules')->whereIn('quran_circle_id', $teacherCircleIds)->delete()
                );
            }
        }

        // Circle enrollments and pivot
        $this->deleteAndLog('Circle enrollments',
            QuranCircleEnrollment::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            fn () => QuranCircleEnrollment::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->forceDelete()
        );

        $this->deleteAndLog('Circle pivot entries',
            DB::table('quran_circle_students')->whereIn('student_id', $this->allUserIds)->count(),
            fn () => DB::table('quran_circle_students')->whereIn('student_id', $this->allUserIds)->delete()
        );

        // Individual circles
        $indivQuery = QuranIndividualCircle::withoutGlobalScopes()->withTrashed()
            ->where(fn ($q) => $q->whereIn('student_id', $this->studentUserIds)
                ->when($this->teacherUserId, fn ($q2) => $q2->orWhere('quran_teacher_id', $this->teacherUserId))
            );
        $this->deleteAndLog('Individual circles', $indivQuery->count(), fn () => $indivQuery->forceDelete());

        // Group circles (teacher's) with safety check
        if ($this->teacherUserId) {
            $teacherCircles = QuranCircle::withoutGlobalScopes()->withTrashed()
                ->where('quran_teacher_id', $this->teacherUserId)->get();

            foreach ($teacherCircles as $circle) {
                $hasProductionStudents = DB::table('quran_circle_students')
                    ->where('quran_circle_id', $circle->id)
                    ->whereNotIn('student_id', $this->allUserIds)
                    ->exists();

                if ($hasProductionStudents) {
                    $circle->update(['quran_teacher_id' => null]);
                    $this->logDeletion("Circle '{$circle->name}' teacher unlinked", 1);
                } else {
                    $circle->forceDelete();
                    $this->logDeletion("Circle '{$circle->name}' deleted", 1);
                }
            }
        }
    }

    // ─── Phase 3: Subscriptions and payments ──────────────────────────

    protected function phase3SubscriptionsAndPayments(): void
    {
        $this->info('Phase 3: Deleting subscriptions and payments...');

        $this->deleteAndLog('Quran subscriptions',
            QuranSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            fn () => QuranSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->forceDelete()
        );

        $this->deleteAndLog('Academic subscriptions',
            AcademicSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->count(),
            fn () => AcademicSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->forceDelete()
        );

        $this->deleteAndLog('Course subscriptions',
            CourseSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->count(),
            fn () => CourseSubscription::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->studentUserIds)->forceDelete()
        );

        // Interactive course enrollments (uses StudentProfile.id)
        $this->deleteAndLog('Course enrollments',
            InteractiveCourseEnrollment::withoutGlobalScopes()->whereIn('student_id', $this->studentProfileIds ?: [0])->count(),
            fn () => InteractiveCourseEnrollment::withoutGlobalScopes()->whereIn('student_id', $this->studentProfileIds ?: [0])->delete()
        );

        $this->deleteAndLog('Payments',
            Payment::withoutGlobalScopes()->withTrashed()->whereIn('user_id', $this->allUserIds)->count(),
            fn () => Payment::withoutGlobalScopes()->withTrashed()->whereIn('user_id', $this->allUserIds)->forceDelete()
        );

        $this->deleteAndLog('Trial requests',
            QuranTrialRequest::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->count(),
            fn () => QuranTrialRequest::withoutGlobalScopes()->withTrashed()->whereIn('student_id', $this->allUserIds)->forceDelete()
        );

        $this->deleteAndLog('Sponsored enrollment requests',
            DB::table('sponsored_enrollment_requests')->whereIn('student_id', $this->allUserIds)->count(),
            fn () => DB::table('sponsored_enrollment_requests')->whereIn('student_id', $this->allUserIds)->delete()
        );
    }

    // ─── Phase 4: Chat, notifications, tokens ─────────────────────────

    protected function phase4ChatAndTokens(): void
    {
        $this->info('Phase 4: Deleting chat, notifications, tokens...');

        $this->deleteAndLog('Chat memberships',
            DB::table('chat_group_members')->whereIn('user_id', $this->allUserIds)->count(),
            fn () => DB::table('chat_group_members')->whereIn('user_id', $this->allUserIds)->delete()
        );

        $this->deleteAndLog('Chat groups (owned)',
            ChatGroup::withoutGlobalScopes()->withTrashed()->whereIn('owner_id', $this->allUserIds)->count(),
            fn () => ChatGroup::withoutGlobalScopes()->withTrashed()->whereIn('owner_id', $this->allUserIds)->forceDelete()
        );

        // WireChat messages — must delete BEFORE participants (FK dependency)
        $participantIds = DB::table('wire_participants')
            ->where('participantable_type', User::class)
            ->whereIn('participantable_id', $this->allUserIds)
            ->pluck('id');

        $this->deleteAndLog('WireChat messages',
            DB::table('wire_messages')->whereIn('participant_id', $participantIds)->count(),
            fn () => DB::table('wire_messages')->whereIn('participant_id', $participantIds)->delete()
        );

        $this->deleteAndLog('WireChat participants',
            DB::table('wire_participants')
                ->where('participantable_type', User::class)
                ->whereIn('participantable_id', $this->allUserIds)->count(),
            fn () => DB::table('wire_participants')
                ->where('participantable_type', User::class)
                ->whereIn('participantable_id', $this->allUserIds)->delete()
        );

        $this->deleteAndLog('Notifications',
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $this->allUserIds)->count(),
            fn () => DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $this->allUserIds)->delete()
        );

        $this->deleteAndLog('Device tokens',
            DB::table('device_tokens')->whereIn('user_id', $this->allUserIds)->count(),
            fn () => DB::table('device_tokens')->whereIn('user_id', $this->allUserIds)->delete()
        );

        $this->deleteAndLog('Access tokens',
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $this->allUserIds)->count(),
            fn () => DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $this->allUserIds)->delete()
        );

        $this->deleteAndLog('Saved payment methods',
            DB::table('saved_payment_methods')->whereIn('user_id', $this->allUserIds)->count(),
            fn () => DB::table('saved_payment_methods')->whereIn('user_id', $this->allUserIds)->delete()
        );
    }

    // ─── Phase 5: Users ───────────────────────────────────────────────

    protected function phase5Users(): void
    {
        $this->info('Phase 5: Deleting user accounts...');

        $count = User::withTrashed()->whereIn('id', $this->allUserIds)->count();

        // Force delete users — DB CASCADE handles profiles
        User::withTrashed()->whereIn('id', $this->allUserIds)->each(function ($user) {
            $user->forceDelete();
        });

        $this->logDeletion('Users', $count);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    protected function getAllSessionIds(): array
    {
        $quranIds = QuranSession::withoutGlobalScopes()->withTrashed()
            ->where(fn ($q) => $q->whereIn('student_id', $this->studentUserIds)
                ->when($this->teacherUserId, fn ($q2) => $q2->orWhere('quran_teacher_id', $this->teacherUserId))
            )->pluck('id');

        $academicIds = AcademicSession::withoutGlobalScopes()->withTrashed()
            ->whereIn('student_id', $this->studentUserIds)->pluck('id');

        return ['quran' => $quranIds, 'academic' => $academicIds];
    }

    protected function deleteAndLog(string $label, int $count, callable $deleteCallback): void
    {
        if ($count > 0) {
            $deleteCallback();
            $this->deletionLog[] = ['label' => $label, 'count' => $count];
            $this->line("  ✓ {$label}: {$count} deleted");
        }
    }

    protected function logDeletion(string $label, int $count): void
    {
        if ($count > 0) {
            $this->deletionLog[] = ['label' => $label, 'count' => $count];
            $this->line("  ✓ {$label}: {$count}");
        }
    }

    protected function showDeletionSummary(): void
    {
        $this->info('Deletion Summary:');
        $this->line('───────────────────────────────────────────────────────────────');

        $total = 0;
        foreach ($this->deletionLog as $entry) {
            $this->line("  {$entry['label']}: {$entry['count']} records");
            $total += $entry['count'];
        }

        $this->newLine();
        $this->info("Total records deleted: {$total}");
    }

    protected function performVerification(): void
    {
        $this->newLine();
        $this->info('Post-Deletion Verification:');
        $this->line('───────────────────────────────────────────────────────────────');

        $failures = [];

        // Users gone
        $remaining = User::withTrashed()->whereIn('email', self::EMAILS)->count();
        if ($remaining === 0) {
            $this->line('  ✓ All target users deleted');
        } else {
            $failures[] = "Users: {$remaining} remaining";
        }

        // Profiles gone
        $remainingProfiles = StudentProfile::withoutGlobalScopes()->withTrashed()
            ->whereIn('user_id', $this->allUserIds)->count();
        if ($remainingProfiles === 0) {
            $this->line('  ✓ All student profiles deleted');
        } else {
            $failures[] = "Student profiles: {$remainingProfiles} remaining";
        }

        if ($this->teacherUserId) {
            $remainingTeacher = QuranTeacherProfile::withoutGlobalScopes()->withTrashed()
                ->where('user_id', $this->teacherUserId)->count();
            if ($remainingTeacher === 0) {
                $this->line('  ✓ Teacher profile deleted');
            } else {
                $failures[] = "Teacher profile: {$remainingTeacher} remaining";
            }
        }

        // Orphan check
        $orphanedSubs = QuranSubscription::withoutGlobalScopes()->withTrashed()
            ->whereIn('student_id', $this->allUserIds)->count();
        if ($orphanedSubs === 0) {
            $this->line('  ✓ No orphaned subscriptions');
        } else {
            $failures[] = "Orphaned subscriptions: {$orphanedSubs}";
        }

        $orphanedSessions = QuranSession::withoutGlobalScopes()->withTrashed()
            ->whereIn('student_id', $this->studentUserIds)->count();
        if ($orphanedSessions === 0) {
            $this->line('  ✓ No orphaned sessions');
        } else {
            $failures[] = "Orphaned sessions: {$orphanedSessions}";
        }

        if (! empty($failures)) {
            $this->newLine();
            $this->error('⚠️  Verification issues:');
            foreach ($failures as $failure) {
                $this->error("  - {$failure}");
            }
        } else {
            $this->newLine();
            $this->info('✅ All verification checks passed!');
        }
    }
}
