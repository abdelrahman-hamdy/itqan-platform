<?php

namespace App\Console\Commands;

use Exception;
use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class TestExchangeRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:test {from=SAR} {to=EGP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test exchange rate API';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $service): int
    {
        $from = $this->argument('from');
        $to = $this->argument('to');

        $this->info("Fetching exchange rate: {$from} → {$to}");

        try {
            $rate = $service->getRate($from, $to);

            $this->line("Rate: 1 {$from} = {$rate} {$to}");
            $this->line("Example: 100 {$from} = ".(100 * $rate)." {$to}");

            $this->info('✓ Success');

            return 0;

        } catch (Exception $e) {
            $this->error('✗ Failed: '.$e->getMessage());

            return 1;
        }
    }
}
