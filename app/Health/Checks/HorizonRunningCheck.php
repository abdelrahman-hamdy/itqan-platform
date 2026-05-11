<?php

namespace App\Health\Checks;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Critical: queues silently piling up because no Horizon master is
 * running is the canonical 2 AM page.
 */
class HorizonRunningCheck extends Check
{
    public function getName(): string
    {
        return 'Horizon Running';
    }

    public function run(): Result
    {
        if (! interface_exists(MasterSupervisorRepository::class)) {
            return Result::make()
                ->shortSummary('horizon package not installed')
                ->ok();
        }

        try {
            /** @var MasterSupervisorRepository $repo */
            $repo = app(MasterSupervisorRepository::class);
            $masters = $repo->all();
        } catch (\Throwable $e) {
            return Result::make()
                ->shortSummary('horizon repo error')
                ->meta(['error' => $e->getMessage()])
                ->failed('Unable to read Horizon master supervisor list: '.$e->getMessage());
        }

        $count = is_countable($masters) ? count($masters) : 0;
        $meta = [
            'master_count' => $count,
            'pids' => collect($masters)->map(fn ($m) => $m->pid ?? null)->filter()->values()->all(),
        ];

        $result = Result::make()
            ->shortSummary("masters={$count}")
            ->meta($meta);

        if ($count === 0) {
            return $result->failed('No Horizon master supervisor alive');
        }

        return $result->ok();
    }
}
