<?php

namespace App\Console\Commands;

use App\Helpers\EarningsCycleHelper;
use App\Models\TeacherEarning;
use Illuminate\Console\Command;

/**
 * One-shot backfill — recompute `teacher_earnings.earning_month` from
 * `session_completed_at` using the 29→28 billing cycle rule. Idempotent:
 * only writes rows whose stored value differs from the cycle-derived value.
 *
 * `withoutGlobalScopes()` strips both the academy scope and the soft-delete
 * scope, so trashed rows are recomputed too — desirable here so a future
 * restore lands on the cycle-correct value.
 */
class BackfillEarningCycleMonths extends Command
{
    protected $signature = 'earnings:backfill-cycle-months
                            {--apply : Persist updates (default is dry-run)}
                            {--academy= : Limit to a single academy_id}';

    protected $description = 'Recompute teacher_earnings.earning_month using the 29-to-28 billing cycle rule';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $dryRun = ! $apply;
        $academyId = $this->option('academy') !== null ? (int) $this->option('academy') : null;

        $this->info($dryRun ? 'DRY RUN — no rows will be written. Pass --apply to persist.' : 'Applying backfill writes.');
        $this->newLine();

        $query = TeacherEarning::query()->withoutGlobalScopes();
        if ($academyId !== null) {
            $query->where('academy_id', $academyId);
        }

        $examined = 0;
        $updated = 0;
        $perAcademy = [];

        $query->orderBy('id')->chunkById(1000, function ($rows) use (&$examined, &$updated, &$perAcademy, $dryRun) {
            foreach ($rows as $row) {
                $examined++;
                $source = $row->session_completed_at ?? $row->calculated_at;
                if (! $source) {
                    continue;
                }

                $expected = EarningsCycleHelper::cycleStorageDate($source);

                $current = optional($row->earning_month)->format('Y-m-d');
                if ($current === $expected) {
                    continue;
                }

                $updated++;
                $perAcademy[$row->academy_id] = ($perAcademy[$row->academy_id] ?? 0) + 1;

                if (! $dryRun) {
                    $row->forceFill(['earning_month' => $expected])->saveQuietly();
                }
            }
        });

        $this->info("examined={$examined} updated={$updated}");

        if (! empty($perAcademy)) {
            $this->line('Per-academy breakdown:');
            ksort($perAcademy);
            foreach ($perAcademy as $id => $count) {
                $this->line("  academy_id={$id}: {$count}");
            }
        }

        return self::SUCCESS;
    }
}
