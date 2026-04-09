<?php

namespace App\Console\Commands;

use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Audit and fix subscription session counters by comparing stored counts
 * against actual counted sessions in the database.
 */
class AuditSubscriptionCounts extends Command
{
    protected $signature = 'subscriptions:audit-counts
                          {--dry-run : Show mismatches without fixing}
                          {--fix : Actually fix the mismatches}';

    protected $description = 'Audit subscription session counters and fix mismatches';

    public function handle(): int
    {
        $isDryRun = ! $this->option('fix');

        if ($isDryRun) {
            $this->warn('DRY RUN — use --fix to apply changes');
        }

        $mismatches = 0;
        $fixed = 0;

        // Quran subscriptions — individual sessions
        $this->info('Auditing Quran subscriptions (individual)...');
        $quranIndividual = QuranSubscription::withoutGlobalScopes()
            ->whereNotNull('total_sessions')
            ->where('total_sessions', '>', 0)
            ->get();

        foreach ($quranIndividual as $sub) {
            $actualUsed = DB::table('quran_sessions')
                ->where('quran_subscription_id', $sub->id)
                ->where('subscription_counted', true)
                ->count();

            // Also count via individual_circle sessions
            $circleUsed = DB::table('quran_sessions')
                ->whereIn('individual_circle_id', function ($q) use ($sub) {
                    $q->select('id')->from('quran_individual_circles')
                        ->where('subscription_id', $sub->id);
                })
                ->where('subscription_counted', true)
                ->count();

            $totalActual = max($actualUsed, $circleUsed);

            if ($totalActual !== $sub->sessions_used) {
                $mismatches++;
                $expectedRemaining = max(0, $sub->total_sessions - $totalActual);

                $this->line("  Sub {$sub->id}: stored used={$sub->sessions_used} remaining={$sub->sessions_remaining}, actual used={$totalActual} expected remaining={$expectedRemaining}");

                if (! $isDryRun) {
                    $sub->update([
                        'sessions_used' => $totalActual,
                        'sessions_remaining' => $expectedRemaining,
                    ]);
                    $fixed++;
                }
            }
        }

        // Academic subscriptions
        $this->info('Auditing Academic subscriptions...');
        $academicSubs = AcademicSubscription::withoutGlobalScopes()
            ->whereNotNull('total_sessions')
            ->where('total_sessions', '>', 0)
            ->get();

        foreach ($academicSubs as $sub) {
            $actualUsed = DB::table('academic_sessions')
                ->where('academic_subscription_id', $sub->id)
                ->where('subscription_counted', true)
                ->count();

            if ($actualUsed !== $sub->sessions_used) {
                $mismatches++;
                $expectedRemaining = max(0, $sub->total_sessions - $actualUsed);

                $this->line("  Sub {$sub->id}: stored used={$sub->sessions_used} remaining={$sub->sessions_remaining}, actual used={$actualUsed} expected remaining={$expectedRemaining}");

                if (! $isDryRun) {
                    $sub->update([
                        'sessions_used' => $actualUsed,
                        'sessions_remaining' => $expectedRemaining,
                    ]);
                    $fixed++;
                }
            }
        }

        $this->newLine();
        $verb = $isDryRun ? 'Found' : 'Fixed';
        $this->info("{$verb} {$mismatches} mismatches".(! $isDryRun ? " ({$fixed} fixed)" : ''));

        return self::SUCCESS;
    }
}
