<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshExchangeRates extends Command
{
    protected $signature = 'exchange-rates:refresh';

    protected $description = 'Fetch latest exchange rates from API and persist to database';

    public function handle(ExchangeRateService $service): int
    {
        $pairs = [
            ['SAR', 'EGP'],
        ];

        $success = 0;
        $failed = 0;

        foreach ($pairs as [$from, $to]) {
            try {
                // Clear cache to force fresh API fetch
                $service->clearCache($from, $to);

                // Fetch fresh rate (will store to DB automatically)
                $rate = $service->getRate($from, $to);

                $this->info("✓ {$from}→{$to}: {$rate}");
                $success++;

            } catch (\Exception $e) {
                $this->error("✗ {$from}→{$to}: {$e->getMessage()}");
                Log::channel('payments')->error('Failed to refresh exchange rate', [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->line("Completed: {$success} success, {$failed} failed");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
