<?php

namespace App\Console\Commands\Archived;

use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrates existing session codes to the new unified format.
 *
 * Old formats:
 * - QuranSession: QSE-{academyId}-{seq:6} (e.g., QSE-1-000042)
 * - AcademicSession: AS-{academyId:2}-{seq:6} (e.g., AS-01-000123)
 * - InteractiveCourseSession: None
 *
 * New formats:
 * - Quran Individual: QI-{YYMM}-{seq:4} (e.g., QI-2601-0042)
 * - Quran Group: QG-{YYMM}-{seq:4} (e.g., QG-2601-0023)
 * - Quran Trial: QT-{YYMM}-{seq:4} (e.g., QT-2601-0015)
 * - Academic Private: AP-{YYMM}-{seq:4} (e.g., AP-2601-0087)
 * - Interactive Course: IC-{YYMM}-{seq:4} (e.g., IC-2601-0005)
 */
class MigrateSessionCodes extends Command
{
    protected $signature = 'sessions:migrate-codes
                            {--dry-run : Show what would be changed without making changes}
                            {--type= : Only migrate a specific type (quran, academic, interactive)}';

    protected $description = 'Migrate existing session codes to the new unified format';

    /**
     * Hide this command in production - one-time migration only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    private array $typeCounters = [];

    public function handle(): int
    {
        $this->info('Session Code Migration');
        $this->info('======================');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $type = $this->option('type');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $stats = [
            'quran' => ['total' => 0, 'migrated' => 0, 'skipped' => 0],
            'academic' => ['total' => 0, 'migrated' => 0, 'skipped' => 0],
            'interactive' => ['total' => 0, 'migrated' => 0, 'skipped' => 0],
        ];

        // Migrate Quran sessions
        if (! $type || $type === 'quran') {
            $this->info('Migrating Quran Sessions...');
            $stats['quran'] = $this->migrateQuranSessions($dryRun);
        }

        // Migrate Academic sessions
        if (! $type || $type === 'academic') {
            $this->info('Migrating Academic Sessions...');
            $stats['academic'] = $this->migrateAcademicSessions($dryRun);
        }

        // Migrate Interactive Course sessions
        if (! $type || $type === 'interactive') {
            $this->info('Migrating Interactive Course Sessions...');
            $stats['interactive'] = $this->migrateInteractiveCourseSessions($dryRun);
        }

        $this->newLine();
        $this->info('Migration Summary');
        $this->info('-----------------');
        $this->table(
            ['Type', 'Total', 'Migrated', 'Skipped'],
            [
                ['Quran', $stats['quran']['total'], $stats['quran']['migrated'], $stats['quran']['skipped']],
                ['Academic', $stats['academic']['total'], $stats['academic']['migrated'], $stats['academic']['skipped']],
                ['Interactive', $stats['interactive']['total'], $stats['interactive']['migrated'], $stats['interactive']['skipped']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    private function migrateQuranSessions(bool $dryRun): array
    {
        $stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0];

        // Process in chunks to avoid memory issues
        QuranSession::withTrashed()
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->chunk(100, function ($sessions) use (&$stats, $dryRun) {
                foreach ($sessions as $session) {
                    $stats['total']++;

                    // Skip if already in new format
                    if ($this->isNewFormat($session->session_code)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $newCode = $this->generateNewCodeForQuranSession($session);

                    if ($dryRun) {
                        $this->line("  [{$session->id}] {$session->session_code} -> {$newCode}");
                    } else {
                        DB::table('quran_sessions')
                            ->where('id', $session->id)
                            ->update(['session_code' => $newCode]);
                    }

                    $stats['migrated']++;
                }
            });

        return $stats;
    }

    private function migrateAcademicSessions(bool $dryRun): array
    {
        $stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0];

        AcademicSession::withTrashed()
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->chunk(100, function ($sessions) use (&$stats, $dryRun) {
                foreach ($sessions as $session) {
                    $stats['total']++;

                    // Skip if already in new format
                    if ($this->isNewFormat($session->session_code)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $newCode = $this->generateNewCodeForAcademicSession($session);

                    if ($dryRun) {
                        $this->line("  [{$session->id}] {$session->session_code} -> {$newCode}");
                    } else {
                        DB::table('academic_sessions')
                            ->where('id', $session->id)
                            ->update(['session_code' => $newCode]);
                    }

                    $stats['migrated']++;
                }
            });

        return $stats;
    }

    private function migrateInteractiveCourseSessions(bool $dryRun): array
    {
        $stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0];

        InteractiveCourseSession::withTrashed()
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->chunk(100, function ($sessions) use (&$stats, $dryRun) {
                foreach ($sessions as $session) {
                    $stats['total']++;

                    // Skip if already has a code in new format
                    if ($session->session_code && $this->isNewFormat($session->session_code)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $newCode = $this->generateNewCodeForInteractiveCourseSession($session);

                    if ($dryRun) {
                        $oldCode = $session->session_code ?: '(none)';
                        $this->line("  [{$session->id}] {$oldCode} -> {$newCode}");
                    } else {
                        DB::table('interactive_course_sessions')
                            ->where('id', $session->id)
                            ->update(['session_code' => $newCode]);
                    }

                    $stats['migrated']++;
                }
            });

        return $stats;
    }

    private function isNewFormat(?string $code): bool
    {
        if (empty($code)) {
            return false;
        }

        // New format: XX-YYMM-XXXX (12 chars, specific pattern)
        return preg_match('/^(QI|QG|QT|AP|IC)-\d{4}-\d{4}$/', $code) === 1;
    }

    private function generateNewCodeForQuranSession(QuranSession $session): string
    {
        $typeKey = $session->getSessionTypeKey();
        $prefix = config('session-naming.type_prefixes.'.$typeKey, 'QI');

        // Use scheduled_at if available, otherwise created_at
        $date = $session->scheduled_at ?? $session->created_at ?? now();
        $yearMonth = $date->format('ym');

        return $this->getNextSequenceCode($prefix, $yearMonth);
    }

    private function generateNewCodeForAcademicSession(AcademicSession $session): string
    {
        $prefix = config('session-naming.type_prefixes.academic_private', 'AP');

        // Use scheduled_at if available, otherwise created_at
        $date = $session->scheduled_at ?? $session->created_at ?? now();
        $yearMonth = $date->format('ym');

        return $this->getNextSequenceCode($prefix, $yearMonth);
    }

    private function generateNewCodeForInteractiveCourseSession(InteractiveCourseSession $session): string
    {
        $prefix = config('session-naming.type_prefixes.interactive_course', 'IC');

        // Use scheduled_at if available, otherwise created_at
        $date = $session->scheduled_at ?? $session->created_at ?? now();
        $yearMonth = $date->format('ym');

        return $this->getNextSequenceCode($prefix, $yearMonth);
    }

    private function getNextSequenceCode(string $prefix, string $yearMonth): string
    {
        $key = "{$prefix}-{$yearMonth}";

        if (! isset($this->typeCounters[$key])) {
            $this->typeCounters[$key] = 0;
        }

        $this->typeCounters[$key]++;
        $sequence = str_pad($this->typeCounters[$key], 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$yearMonth}-{$sequence}";
    }
}
