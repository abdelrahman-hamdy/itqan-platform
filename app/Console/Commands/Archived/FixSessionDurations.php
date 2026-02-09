<?php

namespace App\Console\Commands\Archived;

use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSessionDurations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sessions:fix-durations 
                          {--dry-run : Show what would be fixed without making changes}
                          {--circle-id= : Fix only sessions for specific circle ID}';

    /**
     * The console command description.
     */
    protected $description = 'Fix session durations to match their circle/subscription settings';

    /**
     * Hide this command in production - one-time fix only.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $circleId = $this->option('circle-id');

        $this->info('🔧 Starting session duration fix process...');

        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
        }

        $fixedSessions = 0;
        $errors = 0;

        try {
            DB::beginTransaction();

            // Fix group sessions
            $this->info('📊 Fixing group session durations...');
            $groupResults = $this->fixGroupSessionDurations($isDryRun, $circleId);
            $fixedSessions += $groupResults['fixed'];
            $errors += $groupResults['errors'];

            // Fix individual sessions
            $this->info('👤 Fixing individual session durations...');
            $individualResults = $this->fixIndividualSessionDurations($isDryRun, $circleId);
            $fixedSessions += $individualResults['fixed'];
            $errors += $individualResults['errors'];

            // Fix schedule defaults
            $this->info('📅 Fixing schedule default durations...');
            $scheduleResults = $this->fixScheduleDefaults($isDryRun, $circleId);
            $fixedSessions += $scheduleResults['fixed'];
            $errors += $scheduleResults['errors'];

            if (! $isDryRun) {
                DB::commit();
                $this->info('✅ Changes committed to database');
            } else {
                DB::rollBack();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('📋 Summary:');
        $this->info("  • Sessions fixed: {$fixedSessions}");

        if ($errors > 0) {
            $this->warn("  • Errors encountered: {$errors}");
        }

        if ($isDryRun) {
            $this->warn('  • No actual changes made (dry run)');
        }

        $this->info('✅ Process completed successfully');

        return self::SUCCESS;
    }

    private function fixGroupSessionDurations(bool $isDryRun, ?string $circleId): array
    {
        $query = QuranSession::whereNotNull('circle_id')
            ->whereHas('circle');

        if ($circleId) {
            $query->where('circle_id', $circleId);
        }

        $fixed = 0;
        $errors = 0;

        // Process in chunks to prevent memory issues
        $query->with('circle')->chunkById(200, function ($sessions) use (&$fixed, &$errors, $isDryRun) {
            foreach ($sessions as $session) {
                try {
                    $correctDuration = $session->circle->session_duration_minutes ?? 60;

                    if ($session->duration_minutes !== $correctDuration) {
                        $this->info("  Group Session {$session->id}: {$session->duration_minutes}min → {$correctDuration}min (Circle: {$session->circle->name})");

                        if (! $isDryRun) {
                            $session->update(['duration_minutes' => $correctDuration]);
                        }

                        $fixed++;
                    }

                } catch (\Exception $e) {
                    $this->error("  Error fixing session {$session->id}: ".$e->getMessage());
                    $errors++;
                }
            }
        });

        return ['fixed' => $fixed, 'errors' => $errors];
    }

    private function fixIndividualSessionDurations(bool $isDryRun, ?string $circleId): array
    {
        $query = QuranSession::whereNotNull('individual_circle_id')
            ->whereHas('individualCircle');

        if ($circleId) {
            $query->where('individual_circle_id', $circleId);
        }

        $fixed = 0;
        $errors = 0;

        // Process in chunks to prevent memory issues
        $query->with(['individualCircle.subscription.package'])->chunkById(200, function ($sessions) use (&$fixed, &$errors, $isDryRun) {
            foreach ($sessions as $session) {
                try {
                    $correctDuration = $session->individualCircle->subscription?->session_duration_minutes
                        ?? $session->individualCircle->subscription?->package?->session_duration_minutes
                        ?? 45;

                    if ($session->duration_minutes !== $correctDuration) {
                        $studentName = $session->individualCircle->student?->name ?? 'Unknown';
                        $this->info("  Individual Session {$session->id}: {$session->duration_minutes}min → {$correctDuration}min (Student: {$studentName})");

                        if (! $isDryRun) {
                            $session->update(['duration_minutes' => $correctDuration]);
                        }

                        $fixed++;
                    }

                } catch (\Exception $e) {
                    $this->error("  Error fixing session {$session->id}: ".$e->getMessage());
                    $errors++;
                }
            }
        });

        return ['fixed' => $fixed, 'errors' => $errors];
    }

    private function fixScheduleDefaults(bool $isDryRun, ?string $circleId): array
    {
        $query = \App\Models\QuranCircleSchedule::query();

        if ($circleId) {
            $query->where('circle_id', $circleId);
        }

        $fixed = 0;
        $errors = 0;

        // Process in chunks to prevent memory issues
        $query->with('circle')->chunkById(200, function ($schedules) use (&$fixed, &$errors, $isDryRun) {
            foreach ($schedules as $schedule) {
                try {
                    $correctDuration = $schedule->circle->session_duration_minutes ?? 60;

                    if ($schedule->default_duration_minutes !== $correctDuration) {
                        $this->info("  Schedule {$schedule->id}: {$schedule->default_duration_minutes}min → {$correctDuration}min (Circle: {$schedule->circle->name})");

                        if (! $isDryRun) {
                            $schedule->update(['default_duration_minutes' => $correctDuration]);
                        }

                        $fixed++;
                    }

                } catch (\Exception $e) {
                    $this->error("  Error fixing schedule {$schedule->id}: ".$e->getMessage());
                    $errors++;
                }
            }
        });

        return ['fixed' => $fixed, 'errors' => $errors];
    }
}
