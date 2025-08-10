<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutoMeetingCreationService;
use Carbon\Carbon;

class CreateScheduledMeetingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'meetings:create-scheduled 
                          {--academy-id= : Process only specific academy ID}
                          {--dry-run : Show what would be done without actually creating meetings}';

    /**
     * The console command description.
     */
    protected $description = 'Create video meetings for scheduled sessions based on academy settings';

    private AutoMeetingCreationService $autoMeetingService;

    public function __construct(AutoMeetingCreationService $autoMeetingService)
    {
        parent::__construct();
        $this->autoMeetingService = $autoMeetingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('🎥 Starting automatic meeting creation process...');
        $this->info('📅 Current time: ' . now()->format('Y-m-d H:i:s'));

        try {
            // Check for specific academy
            $academyId = $this->option('academy-id');
            $isDryRun = $this->option('dry-run');
            $isVerbose = $this->getOutput()->isVerbose();

            if ($isDryRun) {
                $this->warn('🧪 DRY RUN MODE: No meetings will actually be created');
            }

            $results = [];

            if ($academyId) {
                // Process specific academy
                $academy = \App\Models\Academy::find($academyId);
                if (!$academy) {
                    $this->error("❌ Academy with ID {$academyId} not found");
                    return self::FAILURE;
                }

                $this->info("🏫 Processing academy: {$academy->name} (ID: {$academy->id})");
                
                if (!$isDryRun) {
                    $results = $this->autoMeetingService->createMeetingsForAcademy($academy);
                } else {
                    $results = $this->simulateAcademyProcessing($academy);
                }
                
                $this->displayAcademyResults($results, $isVerbose);

            } else {
                // Process all academies
                $this->info('🌍 Processing all active academies...');
                
                if (!$isDryRun) {
                    $results = $this->autoMeetingService->createMeetingsForAllAcademies();
                } else {
                    $results = $this->simulateAllAcademiesProcessing();
                }
                
                $this->displayOverallResults($results, $isVerbose);
            }

            // Show statistics
            if (!$isDryRun) {
                $this->displayStatistics();
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("⚡ Process completed in {$executionTime} seconds");

            // Determine exit code based on results
            if (isset($results['meetings_failed']) && $results['meetings_failed'] > 0) {
                $this->warn('⚠️  Some meetings failed to create. Check logs for details.');
                return self::INVALID;
            }

            $this->info('✅ Meeting creation process completed successfully');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during meeting creation: ' . $e->getMessage());
            
            if ($this->getOutput()->isVerbose()) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Display results for a single academy
     */
    private function displayAcademyResults(array $results, bool $verbose): void
    {
        $this->info('📊 Academy Results:');
        $this->line("  • Sessions processed: {$results['sessions_processed']}");
        $this->line("  • Meetings created: {$results['meetings_created']}");
        
        if ($results['meetings_failed'] > 0) {
            $this->error("  • Meetings failed: {$results['meetings_failed']}");
        }

        if ($verbose && !empty($results['errors'])) {
            $this->warn('  • Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->line("    - Session {$error['session_id']}: {$error['error']}");
            }
        }
    }

    /**
     * Display overall results for all academies
     */
    private function displayOverallResults(array $results, bool $verbose): void
    {
        $this->info('📊 Overall Results:');
        $this->line("  • Academies processed: {$results['total_academies_processed']}");
        $this->line("  • Total sessions processed: {$results['total_sessions_processed']}");
        $this->line("  • Total meetings created: {$results['meetings_created']}");
        
        if ($results['meetings_failed'] > 0) {
            $this->error("  • Total meetings failed: {$results['meetings_failed']}");
        }

        if ($verbose && !empty($results['errors'])) {
            $this->warn('  • Errors by academy:');
            foreach ($results['errors'] as $academyId => $errors) {
                $this->line("    Academy {$academyId}:");
                foreach ($errors as $error) {
                    if (is_array($error) && isset($error['session_id'])) {
                        $this->line("      - Session {$error['session_id']}: {$error['error']}");
                    } else {
                        $this->line("      - {$error}");
                    }
                }
            }
        }
    }

    /**
     * Display current system statistics
     */
    private function displayStatistics(): void
    {
        $stats = $this->autoMeetingService->getStatistics();
        
        $this->info('📈 System Statistics:');
        $this->line("  • Total auto-generated meetings: {$stats['total_auto_generated_meetings']}");
        $this->line("  • Active meetings: {$stats['active_meetings']}");
        $this->line("  • Meetings created today: {$stats['meetings_created_today']}");
        $this->line("  • Meetings created this week: {$stats['meetings_created_this_week']}");
        $this->line("  • Academies with auto-creation enabled: {$stats['academies_with_auto_creation_enabled']}");
    }

    /**
     * Simulate processing for dry run mode - single academy
     */
    private function simulateAcademyProcessing(\App\Models\Academy $academy): array
    {
        $videoSettings = \App\Models\VideoSettings::forAcademy($academy);
        
        if (!$videoSettings->shouldAutoCreateMeetings()) {
            $this->warn("  ⚠️  Auto meeting creation is disabled for this academy");
            return [
                'academy_id' => $academy->id,
                'academy_name' => $academy->name,
                'sessions_processed' => 0,
                'meetings_created' => 0,
                'meetings_failed' => 0,
                'errors' => [],
            ];
        }

        // Count sessions that would be processed
        $now = now();
        $endTime = $now->copy()->addHours(2);
        
        $eligibleCount = \App\Models\QuranSession::where('academy_id', $academy->id)
            ->where('status', 'scheduled')
            ->whereNull('meeting_room_name')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$now, $endTime])
            ->count();

        $this->line("  📋 Would process {$eligibleCount} eligible sessions");

        return [
            'academy_id' => $academy->id,
            'academy_name' => $academy->name,
            'sessions_processed' => $eligibleCount,
            'meetings_created' => $eligibleCount, // In dry run, assume all would succeed
            'meetings_failed' => 0,
            'errors' => [],
        ];
    }

    /**
     * Simulate processing for dry run mode - all academies
     */
    private function simulateAllAcademiesProcessing(): array
    {
        $academies = \App\Models\Academy::where('is_active', true)->get();
        $totalSessions = 0;
        $totalMeetings = 0;

        foreach ($academies as $academy) {
            $academyResults = $this->simulateAcademyProcessing($academy);
            $totalSessions += $academyResults['sessions_processed'];
            $totalMeetings += $academyResults['meetings_created'];
        }

        return [
            'total_academies_processed' => $academies->count(),
            'total_sessions_processed' => $totalSessions,
            'meetings_created' => $totalMeetings,
            'meetings_failed' => 0,
            'errors' => [],
        ];
    }
}