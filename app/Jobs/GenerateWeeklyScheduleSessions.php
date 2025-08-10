<?php

namespace App\Jobs;

use App\Models\SessionSchedule;
use App\Models\QuranCircle;
use App\Services\SessionSchedulingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateWeeklyScheduleSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    private SessionSchedulingService $schedulingService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->schedulingService = app(SessionSchedulingService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting weekly session generation job');

        $results = [
            'schedules_processed' => 0,
            'sessions_generated' => 0,
            'circles_processed' => 0,
            'errors' => []
        ];

        try {
            // Generate sessions for existing schedules
            $scheduleResults = $this->generateScheduledSessions();
            $results['schedules_processed'] = $scheduleResults['processed'];
            $results['sessions_generated'] += $scheduleResults['generated'];
            $results['errors'] = array_merge($results['errors'], $scheduleResults['errors']);

            // Generate sessions for circles without schedules
            $circleResults = $this->generateCircleSessions();
            $results['circles_processed'] = $circleResults['processed'];
            $results['sessions_generated'] += $circleResults['generated'];
            $results['errors'] = array_merge($results['errors'], $circleResults['errors']);

            Log::info('Weekly session generation completed', $results);

        } catch (\Exception $e) {
            Log::error('Weekly session generation job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generate sessions for existing schedules
     */
    private function generateScheduledSessions(): array
    {
        $results = [
            'processed' => 0,
            'generated' => 0,
            'errors' => []
        ];

        // Get active schedules that need session generation
        $activeSchedules = SessionSchedule::active()
            ->autoGenerate()
            ->with(['quranTeacher', 'subscription', 'circle'])
            ->get();

        foreach ($activeSchedules as $schedule) {
            try {
                $generated = $schedule->generateSessions(4); // Generate 4 weeks ahead
                $results['processed']++;
                $results['generated'] += $generated;

                Log::info('Generated sessions for schedule', [
                    'schedule_id' => $schedule->id,
                    'schedule_type' => $schedule->schedule_type,
                    'sessions_generated' => $generated
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'schedule',
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage()
                ];

                Log::error('Failed to generate sessions for schedule', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate sessions for circles without formal schedules
     */
    private function generateCircleSessions(): array
    {
        $results = [
            'processed' => 0,
            'generated' => 0,
            'errors' => []
        ];

        // Get active circles that don't have a formal schedule
        $circlesWithoutSchedule = QuranCircle::where('status', 'active')
            ->where('enrollment_status', 'open')
            ->whereNotNull('schedule_days')
            ->whereNotNull('schedule_time')
            ->whereDoesntHave('sessionSchedules', function ($query) {
                $query->where('status', SessionSchedule::STATUS_ACTIVE);
            })
            ->with(['quranTeacher', 'students'])
            ->get();

        foreach ($circlesWithoutSchedule as $circle) {
            try {
                $generated = $this->generateCircleSessionsDirectly($circle);
                $results['processed']++;
                $results['generated'] += $generated;

                Log::info('Generated sessions for circle without schedule', [
                    'circle_id' => $circle->id,
                    'circle_name' => $circle->name_ar,
                    'sessions_generated' => $generated
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'type' => 'circle',
                    'circle_id' => $circle->id,
                    'error' => $e->getMessage()
                ];

                Log::error('Failed to generate sessions for circle', [
                    'circle_id' => $circle->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate sessions directly for a circle
     */
    private function generateCircleSessionsDirectly(QuranCircle $circle): int
    {
        $generatedCount = 0;
        $startDate = now()->startOfWeek();
        $endDate = now()->addWeeks(4)->endOfWeek(); // Generate 4 weeks ahead

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            foreach ($circle->schedule_days as $day) {
                if (strtolower($currentDate->format('l')) === $day) {
                    $sessionDateTime = $currentDate->copy()
                        ->setTimeFromTimeString($circle->schedule_time);

                    // Skip past dates
                    if ($sessionDateTime->isPast()) {
                        continue;
                    }

                    // Check if session already exists
                    $existingSession = $circle->sessions()
                        ->where('scheduled_at', $sessionDateTime)
                        ->exists();

                    if ($existingSession) {
                        continue;
                    }

                    // Create session
                    $circle->sessions()->create([
                        'academy_id' => $circle->academy_id,
                        'quran_teacher_id' => $circle->quran_teacher_id,
                        'session_code' => $this->generateCircleSessionCode($circle, $sessionDateTime),
                        'session_type' => 'circle',
                        'status' => 'scheduled',
                        'title' => $circle->name_ar . ' - جلسة جماعية',
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $circle->session_duration_minutes,
                        'is_auto_generated' => true,
                        'created_by_user_id' => null, // System generated
                    ]);

                    $generatedCount++;

                    // Update circle's next session time
                    if (!$circle->next_session_at || $sessionDateTime < $circle->next_session_at) {
                        $circle->update(['next_session_at' => $sessionDateTime]);
                    }
                }
            }
            $currentDate->addDay();
        }

        return $generatedCount;
    }

    /**
     * Generate session code for circle
     */
    private function generateCircleSessionCode(QuranCircle $circle, Carbon $dateTime): string
    {
        return 'CIR-' . $circle->id . '-' . $dateTime->format('YmdHi') . '-' . uniqid();
    }

    /**
     * Clean up completed schedules
     */
    private function cleanupCompletedSchedules(): void
    {
        $completedSchedules = SessionSchedule::where('status', SessionSchedule::STATUS_ACTIVE)
            ->where(function ($query) {
                // Schedules that have reached their end date
                $query->where('end_date', '<', now())
                      // Or subscriptions that have no remaining sessions
                      ->orWhereHas('subscription', function ($q) {
                          $q->where('sessions_remaining', '<=', 0);
                      })
                      // Or schedules that have reached max sessions
                      ->orWhere(function ($q) {
                          $q->whereNotNull('max_sessions')
                            ->whereRaw('sessions_completed >= max_sessions');
                      });
            })
            ->get();

        foreach ($completedSchedules as $schedule) {
            $schedule->complete();
            
            Log::info('Auto-completed schedule', [
                'schedule_id' => $schedule->id,
                'schedule_type' => $schedule->schedule_type,
                'reason' => $this->getCompletionReason($schedule)
            ]);
        }
    }

    /**
     * Get completion reason for schedule
     */
    private function getCompletionReason(SessionSchedule $schedule): string
    {
        if ($schedule->end_date && $schedule->end_date->isPast()) {
            return 'end_date_reached';
        }
        
        if ($schedule->subscription && $schedule->subscription->sessions_remaining <= 0) {
            return 'no_remaining_sessions';
        }
        
        if ($schedule->max_sessions && $schedule->sessions_completed >= $schedule->max_sessions) {
            return 'max_sessions_reached';
        }
        
        return 'unknown';
    }

    /**
     * Update circle statistics
     */
    private function updateCircleStatistics(): void
    {
        $activeCircles = QuranCircle::where('status', 'active')->get();
        
        foreach ($activeCircles as $circle) {
            try {
                $circle->calculateStatistics()->save();
            } catch (\Exception $e) {
                Log::warning('Failed to update circle statistics', [
                    'circle_id' => $circle->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateWeeklyScheduleSessions job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}