<?php

namespace App\Console\Commands;

use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Console\Command;

class FindStudentCircles extends Command
{
    protected $signature = 'find:student-circles {email}';
    protected $description = 'Find all Quran circles and subscriptions for a student by email';

    public function handle()
    {
        $email = $this->argument('email');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("  Finding Circles for Student: {$email}");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Find student
        $student = User::where('email', $email)->first();

        if (!$student) {
            $this->error("❌ Student with email '{$email}' not found");
            return self::FAILURE;
        }

        $this->line("Student: {$student->name} (ID: {$student->id})");
        $this->newLine();

        // Find all subscriptions
        $subscriptions = QuranSubscription::where('student_id', $student->id)
            ->with(['quranTeacher.user', 'individualCircle'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->warn("⚠️  No subscriptions found for this student");
            return self::SUCCESS;
        }

        $this->info("Found {$subscriptions->count()} subscription(s):");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        foreach ($subscriptions as $index => $sub) {
            $this->line(($index + 1) . ". Subscription ID: {$sub->id}");
            $this->line("   Circle ID: " . ($sub->quran_individual_circle_id ?? 'NULL'));

            $teacherName = 'N/A';
            if ($sub->quranTeacher && $sub->quranTeacher->user) {
                $teacherName = $sub->quranTeacher->user->name;
            }
            $this->line("   Teacher: {$teacherName}");
            $this->line("   Status: {$sub->status->value}");
            $this->line("   Total Sessions: {$sub->total_sessions}");
            $this->line("   Used Sessions: " . ($sub->sessions_used ?? 0));
            $this->line("   Remaining Sessions: " . ($sub->sessions_remaining ?? 0));

            // Get circle info
            if ($sub->individualCircle) {
                $circle = $sub->individualCircle;
                $this->line("   Circle Name: {$circle->name}");
                $this->line("   Circle Completed Sessions: " . ($circle->sessions_completed ?? 0));
                $this->line("   Circle Deleted: " . ($circle->deleted_at ? 'YES (' . $circle->deleted_at . ')' : 'NO'));

                // Count actual sessions
                $totalSessions = QuranSession::where('quran_individual_circle_id', $circle->id)->count();
                $completedSessions = QuranSession::where('quran_individual_circle_id', $circle->id)
                    ->where('status', 'completed')
                    ->count();
                $countedSessions = QuranSession::where('quran_individual_circle_id', $circle->id)
                    ->where('subscription_counted', true)
                    ->count();

                $this->line("   Actual Scheduled Sessions: {$totalSessions}");
                $this->line("   Actual Completed Sessions: {$completedSessions}");
                $this->line("   Actual Counted Sessions: {$countedSessions}");

                // Check for discrepancies
                $hasDiscrepancy = false;

                if ($circle->sessions_completed != $completedSessions) {
                    $this->warn("   ⚠️  Circle shows {$circle->sessions_completed} completed but actual is {$completedSessions}");
                    $hasDiscrepancy = true;
                }

                if ($totalSessions != $sub->total_sessions) {
                    $this->warn("   ⚠️  Circle has {$totalSessions} sessions but subscription expects {$sub->total_sessions}");
                    $hasDiscrepancy = true;
                }

                if ($countedSessions != $sub->sessions_used) {
                    $this->warn("   ⚠️  {$countedSessions} sessions counted but subscription shows {$sub->sessions_used} used");
                    $hasDiscrepancy = true;
                }

                if ($hasDiscrepancy) {
                    $this->error("   🔴 HAS DISCREPANCIES - Run: php artisan analyze:circle {$circle->id} --fix");
                } else {
                    $this->info("   ✅ No discrepancies");
                }
            } else {
                $this->warn("   ⚠️  No circle associated with this subscription");
            }

            $this->line('───────────────────────────────────────────────────────────────');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
