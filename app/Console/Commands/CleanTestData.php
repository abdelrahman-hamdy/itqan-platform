<?php

namespace App\Console\Commands;

use Exception;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\QuranCircle;
use App\Models\QuranCircleStudent;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTrialRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanTestData extends Command
{
    protected $signature = 'clean:test-data
        {--dry-run : Preview deletions without executing}
        {--preserve-e2e : Keep User 31 (E2E Test hamdy) and associated data}
        {--force : Skip confirmation prompts}';

    protected $description = 'Clean test data from production (itqan-academy tenant)';

    protected bool $dryRun = false;
    protected bool $preserveE2E = false;
    protected array $deletionLog = [];

    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->preserveE2E = $this->option('preserve-e2e');

        $this->showHeader();

        // Step 1: Identify test users
        $testUserIds = $this->getTestUserIds();

        // Step 2: Get confirmation if not forced
        if (!$this->option('force') && !$this->dryRun) {
            if (!$this->confirm('This will permanently delete test data. Continue?')) {
                $this->error('Operation cancelled.');
                return self::FAILURE;
            }
        }

        // Step 3: Find all dependent data
        $this->info('Identifying test data...');
        $testStudentIds = StudentProfile::whereIn('user_id', $testUserIds)->pluck('id')->toArray();
        $testParentIds = ParentProfile::whereIn('user_id', $testUserIds)->pluck('id')->toArray();

        $this->line('✓ Found ' . count($testUserIds) . ' test users');
        $this->line('✓ Found ' . count($testStudentIds) . ' test students');
        $this->line('✓ Found ' . count($testParentIds) . ' test parents');

        if ($this->preserveE2E) {
            $this->warn('✓ Preserving User 31 (E2E Test hamdy) and all associated data');
        }

        $this->newLine();

        // Step 4: Show deletion plan
        $this->showDeletionPlan($testUserIds, $testStudentIds, $testParentIds);

        // Step 5: Execute deletion (or dry-run)
        if ($this->dryRun) {
            $this->newLine();
            $this->info('DRY RUN COMPLETE - No data was deleted');
            $this->comment('Run without --dry-run to execute actual deletion');
            return self::SUCCESS;
        }

        // Step 6: Execute actual deletion with transaction
        $this->newLine();
        $this->info('Executing deletion...');

        try {
            DB::transaction(function () use ($testUserIds, $testStudentIds, $testParentIds) {
                $this->deleteHomeworkSubmissions($testStudentIds);
                $this->deleteQuizAttempts($testStudentIds);
                $this->deleteAttendanceRecords($testStudentIds);
                $this->deleteSessionMeetings($testStudentIds);
                $this->deleteSessions($testStudentIds);
                $this->deleteSubscriptions($testStudentIds);
                $this->deleteAcademicLessons($testStudentIds);
                $this->handleGroupCircles($testStudentIds);
                $this->deleteTrialRequests($testUserIds);
                $this->deletePayments($testUserIds);
                $this->deleteNotifications($testUserIds);
                $this->deleteChatConversations($testUserIds);
                $this->deleteProfiles($testStudentIds, $testParentIds);
                $this->deleteUsers($testUserIds);
            });

            $this->newLine();
            $this->info('✅ Deletion completed successfully!');
            $this->newLine();

            // Step 7: Show deletion summary
            $this->showDeletionSummary();

            // Step 8: Post-deletion verification
            $this->performPostDeletionVerification($testUserIds, $testStudentIds);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('❌ Deletion failed: ' . $e->getMessage());
            $this->error('Transaction rolled back - no data was deleted');
            return self::FAILURE;
        }
    }

    protected function showHeader(): void
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Test Data Cleanup ' . ($this->dryRun ? '(DRY RUN)' : ''));
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        if ($this->preserveE2E) {
            $this->warn('Mode: Preserving E2E Test Account (User 31)');
            $this->newLine();
        }
    }

    protected function getTestUserIds(): array
    {
        // Always delete these inactive test accounts
        $testUserIds = [4, 7, 98, 104];

        // Note: User 31 is preserved per user decision
        return $testUserIds;
    }

    protected function showDeletionPlan(array $testUserIds, array $testStudentIds, array $testParentIds): void
    {
        $this->info('Deletion Plan:');
        $this->line('───────────────────────────────────────────────────────────────');

        // Count all data to be deleted
        $counts = [
            'Academic homework submissions' => AcademicHomeworkSubmission::whereIn('student_id', $testStudentIds)->count(),
            'Course homework submissions' => InteractiveCourseHomeworkSubmission::whereIn('student_id', $testStudentIds)->count(),
            'Quiz attempts' => QuizAttempt::whereIn('student_id', $testStudentIds)->count(),
            'Quran sessions' => QuranSession::whereIn('student_id', $testStudentIds)->count(),
            'Academic sessions' => AcademicSession::whereIn('student_id', $testStudentIds)->count(),
            'Quran subscriptions' => QuranSubscription::whereIn('student_id', $testStudentIds)->count(),
            'Academic subscriptions' => AcademicSubscription::whereIn('student_id', $testStudentIds)->count(),
            'Academic lessons' => AcademicIndividualLesson::whereHas('subscription', function ($q) use ($testStudentIds) {
                $q->whereIn('student_id', $testStudentIds);
            })->count(),
            'Trial requests' => QuranTrialRequest::whereIn('student_id', $testStudentIds)->count(),
            'Payments' => Payment::whereIn('user_id', $testUserIds)->count(),
            'Student profiles' => count($testStudentIds),
            'Parent profiles' => count($testParentIds),
            'Users' => count($testUserIds),
        ];

        foreach ($counts as $label => $count) {
            $this->line('  ' . $label . ': ' . $count . ' records');
        }

        // Check Circle 2
        $this->checkCircle2($testStudentIds);

        if ($this->preserveE2E) {
            $this->newLine();
            $this->warn('⚠️  Note: User 31 (E2E Test hamdy) PRESERVED');
            $this->line('  - 22 Quran subscriptions preserved');
            $this->line('  - 1 Academic subscription preserved');
            $this->line('  - 93 Quran sessions preserved');
            $this->line('  - 8 Academic sessions preserved');
            $this->line('  - All associated data preserved');
        }
    }

    protected function checkCircle2(array $testStudentIds): void
    {
        $circle2Members = QuranCircleStudent::where('quran_circle_id', 2)->get();

        if ($circle2Members->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn('⚠️  Circle 2 (حلقة حفظ رجالي ٢) handling:');

        $productionMembers = $circle2Members->whereNotIn('student_id', $testStudentIds);

        if ($productionMembers->isEmpty()) {
            $this->line('  - Circle contains only test students');
            $this->line('  - Circle and its sessions will be deleted');
        } else {
            $this->line('  - Circle contains production students');
            $this->line('  - Will remove test students only, preserve circle');
        }
    }

    protected function deleteHomeworkSubmissions(array $testStudentIds): void
    {
        $academicCount = AcademicHomeworkSubmission::whereIn('student_id', $testStudentIds)->count();
        $courseCount = InteractiveCourseHomeworkSubmission::whereIn('student_id', $testStudentIds)->count();

        if (!$this->dryRun) {
            AcademicHomeworkSubmission::whereIn('student_id', $testStudentIds)->delete();
            InteractiveCourseHomeworkSubmission::whereIn('student_id', $testStudentIds)->delete();
        }

        $this->logDeletion('Homework submissions', $academicCount + $courseCount);
    }

    protected function deleteQuizAttempts(array $testStudentIds): void
    {
        $count = QuizAttempt::whereIn('student_id', $testStudentIds)->count();

        if (!$this->dryRun) {
            QuizAttempt::whereIn('student_id', $testStudentIds)->delete();
        }

        $this->logDeletion('Quiz attempts', $count);
    }

    protected function deleteAttendanceRecords(array $testStudentIds): void
    {
        // Attendance records are polymorphic - need to find via sessions
        $quranSessions = QuranSession::whereIn('student_id', $testStudentIds)->pluck('id');
        $academicSessions = AcademicSession::whereIn('student_id', $testStudentIds)->pluck('id');

        $count = DB::table('attendances')
            ->where(function ($q) use ($quranSessions, $academicSessions) {
                $q->where(function ($sub) use ($quranSessions) {
                    $sub->where('attendanceable_type', 'App\\Models\\QuranSession')
                        ->whereIn('attendanceable_id', $quranSessions);
                })->orWhere(function ($sub) use ($academicSessions) {
                    $sub->where('attendanceable_type', 'App\\Models\\AcademicSession')
                        ->whereIn('attendanceable_id', $academicSessions);
                });
            })->count();

        if (!$this->dryRun) {
            DB::table('attendances')
                ->where(function ($q) use ($quranSessions, $academicSessions) {
                    $q->where(function ($sub) use ($quranSessions) {
                        $sub->where('attendanceable_type', 'App\\Models\\QuranSession')
                            ->whereIn('attendanceable_id', $quranSessions);
                    })->orWhere(function ($sub) use ($academicSessions) {
                        $sub->where('attendanceable_type', 'App\\Models\\AcademicSession')
                            ->whereIn('attendanceable_id', $academicSessions);
                    });
                })->delete();
        }

        $this->logDeletion('Attendance records', $count);
    }

    protected function deleteSessionMeetings(array $testStudentIds): void
    {
        $quranSessions = QuranSession::whereIn('student_id', $testStudentIds)->pluck('id');
        $academicSessions = AcademicSession::whereIn('student_id', $testStudentIds)->pluck('id');

        $count = DB::table('base_session_meetings')
            ->where(function ($q) use ($quranSessions, $academicSessions) {
                $q->where(function ($sub) use ($quranSessions) {
                    $sub->where('meetingable_type', 'App\\Models\\QuranSession')
                        ->whereIn('meetingable_id', $quranSessions);
                })->orWhere(function ($sub) use ($academicSessions) {
                    $sub->where('meetingable_type', 'App\\Models\\AcademicSession')
                        ->whereIn('meetingable_id', $academicSessions);
                });
            })->count();

        if (!$this->dryRun) {
            DB::table('base_session_meetings')
                ->where(function ($q) use ($quranSessions, $academicSessions) {
                    $q->where(function ($sub) use ($quranSessions) {
                        $sub->where('meetingable_type', 'App\\Models\\QuranSession')
                            ->whereIn('meetingable_id', $quranSessions);
                    })->orWhere(function ($sub) use ($academicSessions) {
                        $sub->where('meetingable_type', 'App\\Models\\AcademicSession')
                            ->whereIn('meetingable_id', $academicSessions);
                    });
                })->delete();
        }

        $this->logDeletion('Session meetings', $count);
    }

    protected function deleteSessions(array $testStudentIds): void
    {
        $quranCount = QuranSession::whereIn('student_id', $testStudentIds)->count();
        $academicCount = AcademicSession::whereIn('student_id', $testStudentIds)->count();

        if (!$this->dryRun) {
            QuranSession::whereIn('student_id', $testStudentIds)->delete();
            AcademicSession::whereIn('student_id', $testStudentIds)->delete();
        }

        $this->logDeletion('Quran sessions', $quranCount);
        $this->logDeletion('Academic sessions', $academicCount);
    }

    protected function deleteSubscriptions(array $testStudentIds): void
    {
        $quranCount = QuranSubscription::whereIn('student_id', $testStudentIds)->count();
        $academicCount = AcademicSubscription::whereIn('student_id', $testStudentIds)->count();

        if (!$this->dryRun) {
            QuranSubscription::whereIn('student_id', $testStudentIds)->delete();
            AcademicSubscription::whereIn('student_id', $testStudentIds)->delete();
        }

        $this->logDeletion('Quran subscriptions', $quranCount);
        $this->logDeletion('Academic subscriptions', $academicCount);
    }

    protected function deleteAcademicLessons(array $testStudentIds): void
    {
        $count = AcademicIndividualLesson::whereHas('subscription', function ($q) use ($testStudentIds) {
            $q->whereIn('student_id', $testStudentIds);
        })->count();

        if (!$this->dryRun) {
            AcademicIndividualLesson::whereHas('subscription', function ($q) use ($testStudentIds) {
                $q->whereIn('student_id', $testStudentIds);
            })->delete();
        }

        $this->logDeletion('Academic lessons', $count);
    }

    protected function handleGroupCircles(array $testStudentIds): void
    {
        $circlesWithTestStudents = QuranCircleStudent::whereIn('student_id', $testStudentIds)
            ->get()
            ->groupBy('quran_circle_id');

        $circlesDeleted = 0;
        $studentsRemoved = 0;

        foreach ($circlesWithTestStudents as $circleId => $members) {
            $allMembers = QuranCircleStudent::where('quran_circle_id', $circleId)->get();
            $productionMembers = $allMembers->whereNotIn('student_id', $testStudentIds);

            if ($productionMembers->isEmpty()) {
                // Circle contains ONLY test students - safe to delete
                if (!$this->dryRun) {
                    QuranCircle::find($circleId)?->delete();
                }
                $circlesDeleted++;
            } else {
                // Circle contains production students - only remove test students
                if (!$this->dryRun) {
                    QuranCircleStudent::whereIn('student_id', $testStudentIds)
                        ->where('quran_circle_id', $circleId)
                        ->delete();
                }
                $studentsRemoved += $members->count();
            }
        }

        if ($circlesDeleted > 0) {
            $this->logDeletion('Group circles (test-only)', $circlesDeleted);
        }
        if ($studentsRemoved > 0) {
            $this->logDeletion('Test students from mixed circles', $studentsRemoved);
        }
    }

    protected function deleteTrialRequests(array $testUserIds): void
    {
        $testStudentIds = StudentProfile::whereIn('user_id', $testUserIds)->pluck('id');
        $count = QuranTrialRequest::whereIn('student_id', $testStudentIds)->count();

        if (!$this->dryRun) {
            QuranTrialRequest::whereIn('student_id', $testStudentIds)->delete();
        }

        $this->logDeletion('Trial requests', $count);
    }

    protected function deletePayments(array $testUserIds): void
    {
        $count = Payment::whereIn('user_id', $testUserIds)->count();

        if (!$this->dryRun) {
            Payment::whereIn('user_id', $testUserIds)->delete();
        }

        $this->logDeletion('Payments', $count);
    }

    protected function deleteNotifications(array $testUserIds): void
    {
        $count = DB::table('notifications')->whereIn('notifiable_id', $testUserIds)->count();

        if (!$this->dryRun) {
            DB::table('notifications')->whereIn('notifiable_id', $testUserIds)->delete();
        }

        $this->logDeletion('Notifications', $count);
    }

    protected function deleteChatConversations(array $testUserIds): void
    {
        // WireChat conversations - simplified for now
        $count = DB::table('conversations')
            ->whereIn('user_id', $testUserIds)
            ->orWhereIn('receiver_id', $testUserIds)
            ->count();

        if (!$this->dryRun) {
            DB::table('conversations')
                ->whereIn('user_id', $testUserIds)
                ->orWhereIn('receiver_id', $testUserIds)
                ->delete();
        }

        $this->logDeletion('Chat conversations', $count);
    }

    protected function deleteProfiles(array $testStudentIds, array $testParentIds): void
    {
        if (!$this->dryRun) {
            StudentProfile::whereIn('id', $testStudentIds)->delete();
            ParentProfile::whereIn('id', $testParentIds)->delete();
        }

        $this->logDeletion('Student profiles', count($testStudentIds));
        $this->logDeletion('Parent profiles', count($testParentIds));
    }

    protected function deleteUsers(array $testUserIds): void
    {
        if (!$this->dryRun) {
            User::whereIn('id', $testUserIds)->delete();
        }

        $this->logDeletion('Users', count($testUserIds));
    }

    protected function logDeletion(string $label, int $count): void
    {
        if ($count > 0) {
            $this->deletionLog[] = ['label' => $label, 'count' => $count];
            $this->line('  ✓ ' . $label . ': ' . $count . ' deleted');
        }
    }

    protected function showDeletionSummary(): void
    {
        $this->info('Deletion Summary:');
        $this->line('───────────────────────────────────────────────────────────────');

        $total = 0;
        foreach ($this->deletionLog as $entry) {
            $this->line('  ' . $entry['label'] . ': ' . $entry['count'] . ' records');
            $total += $entry['count'];
        }

        $this->newLine();
        $this->info('Total records deleted: ' . $total);
    }

    protected function performPostDeletionVerification(array $testUserIds, array $testStudentIds): void
    {
        $this->newLine();
        $this->info('Post-Deletion Verification:');
        $this->line('───────────────────────────────────────────────────────────────');

        $failures = [];

        // Verify users deleted
        $remainingUsers = User::whereIn('id', $testUserIds)->count();
        if ($remainingUsers === 0) {
            $this->line('  ✓ All test users deleted');
        } else {
            $failures[] = "Users: {$remainingUsers} remaining (expected 0)";
        }

        // Verify User 31 preserved
        $user31 = User::find(31);
        if ($user31 && $user31->email === 'abdelrahman260598@gmail.com') {
            $this->line('  ✓ User 31 (E2E Test hamdy) preserved');
        } else {
            $failures[] = 'User 31 not found or incorrect';
        }

        // Verify subscriptions deleted
        $remainingSubs = QuranSubscription::whereIn('student_id', $testStudentIds)->count();
        if ($remainingSubs === 0) {
            $this->line('  ✓ All test subscriptions deleted');
        } else {
            $failures[] = "Subscriptions: {$remainingSubs} remaining (expected 0)";
        }

        // Verify User 31's data preserved
        $user31Subs = QuranSubscription::where('student_id', 14)->count();
        if ($user31Subs === 22) {
            $this->line('  ✓ User 31 Quran subscriptions preserved (22)');
        } else {
            $failures[] = "User 31 subscriptions: {$user31Subs} (expected 22)";
        }

        // Check for orphaned records
        $orphanedSubs = QuranSubscription::whereDoesntHave('student')->count();
        if ($orphanedSubs === 0) {
            $this->line('  ✓ No orphaned subscriptions');
        } else {
            $failures[] = "Orphaned subscriptions: {$orphanedSubs}";
        }

        if (!empty($failures)) {
            $this->newLine();
            $this->error('⚠️  Verification issues found:');
            foreach ($failures as $failure) {
                $this->error('  - ' . $failure);
            }
        } else {
            $this->newLine();
            $this->info('✅ All verification checks passed!');
        }
    }
}
