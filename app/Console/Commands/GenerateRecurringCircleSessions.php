<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuranCircleSchedule;
use App\Models\Academy;
use Carbon\Carbon;

class GenerateRecurringCircleSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quran:generate-sessions 
                            {--academy= : Specific academy ID to process}
                            {--days=30 : Number of days ahead to generate sessions}
                            {--dry-run : Show what would be generated without creating sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate upcoming sessions for active Quran circle schedules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🕌 Starting Quran circle session generation...');
        
        $isDryRun = $this->option('dry-run');
        $daysAhead = (int) $this->option('days') ?: 30;
        $academyId = $this->option('academy');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No sessions will be created');
        }

        // Get active schedules
        $query = QuranCircleSchedule::query()
            ->with(['circle', 'quranTeacher', 'academy'])
            ->readyForGeneration();

        if ($academyId) {
            $query->where('academy_id', $academyId);
            $this->info("🎯 Processing academy ID: {$academyId}");
        }

        $schedules = $query->get();
        
        if ($schedules->isEmpty()) {
            $this->warn('⚠️  No active schedules found for generation');
            return self::SUCCESS;
        }

        $this->info("📅 Found {$schedules->count()} active schedule(s) to process");
        
        $totalGenerated = 0;
        $processedAcademies = [];

        foreach ($schedules as $schedule) {
            $academyName = $schedule->academy->name ?? "Academy {$schedule->academy_id}";
            $circleName = $schedule->circle->name_ar ?? "Circle {$schedule->circle_id}";
            $teacherName = $schedule->quranTeacher->name ?? "Teacher {$schedule->quran_teacher_id}";
            
            if (!isset($processedAcademies[$schedule->academy_id])) {
                $processedAcademies[$schedule->academy_id] = $academyName;
                $this->newLine();
                $this->info("🏫 Processing Academy: {$academyName}");
            }

            $this->line("  📚 Circle: {$circleName} (Teacher: {$teacherName})");
            
            try {
                if ($isDryRun) {
                    $sessions = $this->previewSessionGeneration($schedule, $daysAhead);
                    $count = count($sessions);
                    $this->line("    🔍 Would generate: {$count} sessions");
                    
                    if ($count > 0 && $this->option('verbose')) {
                        foreach (array_slice($sessions, 0, 3) as $session) {
                            $this->line("      - {$session['title']} at {$session['datetime']->format('Y-m-d H:i')}");
                        }
                        if ($count > 3) {
                            $this->line("      ... and " . ($count - 3) . " more");
                        }
                    }
                } else {
                    $generated = $schedule->generateUpcomingSessions();
                    $totalGenerated += $generated;
                    
                    if ($generated > 0) {
                        $this->line("    ✅ Generated: {$generated} sessions");
                    } else {
                        $this->line("    ⏭️  No new sessions needed");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("    ❌ Error processing schedule {$schedule->id}: " . $e->getMessage());
                
                if ($this->option('verbose')) {
                    $this->error("       Stack trace: " . $e->getTraceAsString());
                }
            }
        }

        $this->newLine();
        
        if ($isDryRun) {
            $this->info('🔍 Dry run completed. Use without --dry-run to actually generate sessions.');
        } else {
            $this->info("✅ Generation completed! Total sessions created: {$totalGenerated}");
            
            if ($totalGenerated > 0) {
                $this->info('💡 Sessions will be visible in teacher calendars once scheduled.');
            }
        }

        // Show summary
        $this->showSummary($processedAcademies, $totalGenerated, $isDryRun);

        return self::SUCCESS;
    }

    /**
     * Preview what sessions would be generated for a schedule
     */
    private function previewSessionGeneration(QuranCircleSchedule $schedule, int $daysAhead): array
    {
        $startDate = $schedule->last_generated_at ? 
            Carbon::parse($schedule->last_generated_at)->addDay() : 
            Carbon::parse($schedule->schedule_starts_at);
            
        $endDate = now()->addDays($daysAhead);
        
        if ($schedule->schedule_ends_at) {
            $endDate = $endDate->min(Carbon::parse($schedule->schedule_ends_at));
        }

        return $schedule->getUpcomingSessionsForRange($startDate, $endDate);
    }

    /**
     * Show generation summary
     */
    private function showSummary(array $academies, int $totalGenerated, bool $isDryRun): void
    {
        $this->newLine();
        $this->line('📊 <comment>Generation Summary</comment>');
        $this->line('─────────────────────');
        $this->line('Academies processed: ' . count($academies));
        
        foreach ($academies as $id => $name) {
            $this->line("  • {$name} (ID: {$id})");
        }
        
        if (!$isDryRun) {
            $this->line("Sessions generated: {$totalGenerated}");
            
            if ($totalGenerated > 0) {
                $this->newLine();
                $this->info('🔔 Next steps:');
                $this->line('  • Teachers can view new sessions in their calendar');
                $this->line('  • Sessions will be automatically activated closer to their time');
                $this->line('  • Students can see upcoming sessions in their dashboard');
            }
        }
    }

    /**
     * Show detailed statistics about schedules
     */
    private function showScheduleStats(): void
    {
        $stats = [
            'Total Active Schedules' => QuranCircleSchedule::active()->count(),
            'Schedules Ready for Generation' => QuranCircleSchedule::readyForGeneration()->count(),
            'Total Active Circles' => \App\Models\QuranCircle::active()->count(),
            'Circles with Schedules' => \App\Models\QuranCircle::whereHas('schedule', function($q) {
                $q->where('is_active', true);
            })->count(),
        ];

        $this->newLine();
        $this->line('📈 <comment>System Statistics</comment>');
        $this->line('─────────────────────');
        
        foreach ($stats as $label => $value) {
            $this->line("{$label}: {$value}");
        }
    }
}