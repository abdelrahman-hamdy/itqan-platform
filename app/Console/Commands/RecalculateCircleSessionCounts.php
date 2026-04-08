<?php

namespace App\Console\Commands;

use App\Models\QuranIndividualCircle;
use Illuminate\Console\Command;

class RecalculateCircleSessionCounts extends Command
{
    protected $signature = 'app:recalculate-circle-session-counts';

    protected $description = 'Recalculate sessions_remaining for all individual circles from actual session records';

    public function handle(): int
    {
        $total = QuranIndividualCircle::count();
        $this->info("Recalculating session counts for {$total} individual circles...");

        $fixed = 0;

        QuranIndividualCircle::with(['sessions', 'subscription'])
            ->chunk(100, function ($circles) use (&$fixed) {
                foreach ($circles as $circle) {
                    $oldRemaining = $circle->sessions_remaining;
                    $circle->updateSessionCounts();
                    $circle->refresh();

                    if ($oldRemaining !== $circle->sessions_remaining) {
                        $fixed++;
                        $this->line("  Circle #{$circle->id}: {$oldRemaining} → {$circle->sessions_remaining}");
                    }
                }
            });

        $this->info("Done. Fixed {$fixed} circles out of {$total}.");

        return self::SUCCESS;
    }
}
