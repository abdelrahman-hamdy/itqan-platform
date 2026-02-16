<?php

namespace App\Console\Commands;

use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Console\Command;

class AnalyzeCircleDiscrepancies extends Command
{
    protected $signature = 'analyze:circle {circle_id} {--fix : Fix the discrepancies}';
    protected $description = 'Analyze and optionally fix session count discrepancies for an individual circle';

    public function handle()
    {
        $circleId = $this->argument('circle_id');
        $fix = $this->option('fix');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("  Individual Circle ID {$circleId} - Discrepancy Analysis");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Find circle (including soft deleted)
        $circle = QuranIndividualCircle::withTrashed()->find($circleId);

        if (!$circle) {
            $this->error("❌ Individual Circle {$circleId} not found");
            return self::FAILURE;
        }

        $this->line("Circle Information:");
        $this->line("  ID: {$circle->id}");
        $this->line("  Name: {$circle->name}");
        $this->line("  Deleted: " . ($circle->deleted_at ? "YES ({$circle->deleted_at})" : 'NO'));
        $this->line("  sessions_completed: " . ($circle->sessions_completed ?? 0));
        $this->newLine();

        // Find subscription through sessions (since the relationship is polymorphic via education_unit_id)
        $session = QuranSession::where('individual_circle_id', $circleId)->first();

        if (!$session || !$session->quran_subscription_id) {
            $this->warn("⚠️  No subscription found for this circle");
            return self::SUCCESS;
        }

        $subscription = QuranSubscription::find($session->quran_subscription_id);

        if (!$subscription) {
            $this->warn("⚠️  Subscription {$session->quran_subscription_id} not found");
            return self::SUCCESS;
        }

        $this->line("Subscription Information:");
        $this->line("  ID: {$subscription->id}");
        $this->line("  Student: {$subscription->student->name}");
        $this->line("  Teacher: {$subscription->quranTeacher->user->name}");
        $this->line("  Status: {$subscription->status->value}");
        $this->line("  total_sessions: {$subscription->total_sessions}");
        $this->line("  sessions_used: {$subscription->sessions_used}");
        $this->line("  sessions_remaining: {$subscription->sessions_remaining}");
        $this->newLine();

        // Get all sessions
        $sessions = QuranSession::where('individual_circle_id', $circleId)
            ->orderBy('scheduled_at')
            ->get();

        $statusCounts = [];
        $countedSessions = 0;

        foreach ($sessions as $session) {
            $status = $session->status->value;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if ($session->subscription_counted) {
                $countedSessions++;
            }
        }

        $completedCount = $statusCounts['completed'] ?? 0;

        $this->line("Sessions Breakdown (Total: {$sessions->count()}):");
        $this->line("───────────────────────────────────────────────────────────────");
        foreach ($statusCounts as $status => $count) {
            $this->line("  " . ucfirst($status) . ": {$count}");
        }
        $this->line("  ─────────────────");
        $this->line("  Counted towards subscription: {$countedSessions}");
        $this->newLine();

        // Show each session
        $this->line("Detailed Session List:");
        $this->line("───────────────────────────────────────────────────────────────");
        foreach ($sessions as $session) {
            $this->line("ID {$session->id}: {$session->scheduled_at->format('Y-m-d H:i')} | {$session->status->value} | counted=" . ($session->subscription_counted ? 'YES' : 'NO'));
        }
        $this->newLine();

        // Analyze discrepancies
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Discrepancy Analysis');
        $this->info('═══════════════════════════════════════════════════════════════');

        $issues = [];

        // Issue 1: Circle sessions_completed vs actual
        if ($circle->sessions_completed != $completedCount) {
            $issues[] = [
                'type' => 'circle_completed',
                'field' => 'Circle.sessions_completed',
                'current' => $circle->sessions_completed,
                'correct' => $completedCount,
                'description' => "Circle shows {$circle->sessions_completed} completed, but only {$completedCount} sessions are actually COMPLETED"
            ];
        }

        // Issue 2: Total scheduled vs subscription
        if ($sessions->count() != $subscription->total_sessions) {
            $issues[] = [
                'type' => 'scheduled_vs_total',
                'field' => 'Scheduled sessions vs Subscription.total_sessions',
                'current' => "Scheduled: {$sessions->count()}, Subscription: {$subscription->total_sessions}",
                'correct' => 'Should match',
                'description' => "{$sessions->count()} sessions scheduled in DB, but subscription has {$subscription->total_sessions} total_sessions"
            ];
        }

        // Issue 3: Counted vs used
        if ($countedSessions != $subscription->sessions_used) {
            $issues[] = [
                'type' => 'counted_vs_used',
                'field' => 'Subscription.sessions_used',
                'current' => $subscription->sessions_used,
                'correct' => $countedSessions,
                'description' => "{$countedSessions} sessions counted in DB (subscription_counted=true), but subscription shows {$subscription->sessions_used} sessions_used"
            ];
        }

        if (count($issues) > 0) {
            $this->warn("⚠️  Found " . count($issues) . " discrepancies:");
            $this->newLine();

            foreach ($issues as $i => $issue) {
                $this->line(($i + 1) . ". {$issue['field']}:");
                $this->warn("   {$issue['description']}");
                $this->line("   Current value: {$issue['current']}");
                $this->line("   Correct value: {$issue['correct']}");
                $this->newLine();
            }

            if ($fix) {
                $this->newLine();
                $this->info('🔧 Applying fixes...');
                $this->newLine();

                foreach ($issues as $issue) {
                    switch ($issue['type']) {
                        case 'circle_completed':
                            $circle->update(['sessions_completed' => $issue['correct']]);
                            $this->line("✓ Updated Circle.sessions_completed: {$issue['current']} → {$issue['correct']}");
                            break;

                        case 'counted_vs_used':
                            $subscription->update(['sessions_used' => $issue['correct']]);
                            $subscription->update(['sessions_remaining' => $subscription->total_sessions - $issue['correct']]);
                            $this->line("✓ Updated Subscription.sessions_used: {$issue['current']} → {$issue['correct']}");
                            $this->line("✓ Updated Subscription.sessions_remaining: " . ($subscription->total_sessions - $issue['correct']));
                            break;

                        case 'scheduled_vs_total':
                            $this->warn("⚠️  Cannot auto-fix: This requires manual review");
                            $this->line("   Either:");
                            $this->line("   - Delete extra sessions if subscription total is correct");
                            $this->line("   - Update subscription.total_sessions if scheduled count is correct");
                            break;
                    }
                }

                $this->newLine();
                $this->info('✅ Fixes applied successfully!');
            } else {
                $this->newLine();
                $this->comment('💡 Run with --fix to automatically correct these discrepancies');
                $this->comment('   Example: php artisan analyze:circle ' . $circleId . ' --fix');
            }

            return self::SUCCESS;
        } else {
            $this->info('✅ No discrepancies found - all counts match!');
            return self::SUCCESS;
        }
    }
}
