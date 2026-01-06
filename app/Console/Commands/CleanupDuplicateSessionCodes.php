<?php

namespace App\Console\Commands;

use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateSessionCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quran:cleanup-duplicate-session-codes {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate session codes in quran_sessions table';

    /**
     * Hide this command in production - one-time cleanup only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Searching for duplicate session codes...');

        // Find sessions with duplicate codes
        $duplicates = DB::table('quran_sessions')
            ->select('academy_id', 'session_code', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->whereNull('deleted_at')
            ->groupBy('academy_id', 'session_code')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate session codes found!');

            return 0;
        }

        $this->warn("Found {$duplicates->count()} sets of duplicate session codes:");

        $totalFixed = 0;

        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            $this->line("Academy {$duplicate->academy_id}, Code '{$duplicate->session_code}': ".count($ids).' sessions');

            // Keep the first session (oldest) and update the rest
            $firstId = array_shift($ids);

            foreach ($ids as $sessionId) {
                $session = QuranSession::find($sessionId);
                if (! $session) {
                    continue;
                }

                $newCode = $this->generateUniqueSessionCode($session->academy_id, $session->session_code);

                if ($isDryRun) {
                    $this->line("  Would update session {$sessionId}: {$session->session_code} -> {$newCode}");
                } else {
                    $session->update(['session_code' => $newCode]);
                    $this->line("  Updated session {$sessionId}: {$session->session_code} -> {$newCode}");
                    $totalFixed++;
                }
            }
        }

        if ($isDryRun) {
            $this->info('Dry run completed. No changes were made.');
            $this->info('Run without --dry-run to apply the changes.');
        } else {
            $this->info("Successfully fixed {$totalFixed} duplicate session codes!");
        }

        return 0;
    }

    private function generateUniqueSessionCode(int $academyId, string $originalCode): string
    {
        $attempt = 1;
        do {
            $newCode = $originalCode.'-FIX'.str_pad($attempt, 3, '0', STR_PAD_LEFT);
            $attempt++;
        } while (
            QuranSession::where('academy_id', $academyId)
                ->where('session_code', $newCode)
                ->exists() && $attempt < 1000
        );

        return $newCode;
    }
}
