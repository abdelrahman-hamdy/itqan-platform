<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuranCircleSchedule;
use App\Models\QuranSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateGroupCircleSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quran:generate-group-sessions 
                            {--hours-ahead=1 : Generate sessions this many hours ahead}
                            {--days-ahead=7 : Look ahead this many days}';

    /**
     * The console command description.
     */
    protected $description = 'Generate group circle sessions based on their schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoursAhead = $this->option('hours-ahead');
        $daysAhead = $this->option('days-ahead');
        
        $this->info("Generating group circle sessions {$hoursAhead} hours ahead for the next {$daysAhead} days...");
        
        $sessionsCreated = 0;
        $schedules = QuranCircleSchedule::where('is_active', true)
            ->with(['circle', 'quranTeacher'])
            ->get();

        foreach ($schedules as $schedule) {
            try {
                $created = $this->generateSessionsForSchedule($schedule, $hoursAhead, $daysAhead);
                $sessionsCreated += $created;
                
                if ($created > 0) {
                    $this->line("Created {$created} sessions for circle: {$schedule->circle->name}");
                }
                
            } catch (\Exception $e) {
                $this->error("Error generating sessions for circle {$schedule->circle->name}: " . $e->getMessage());
                Log::error("Error generating group sessions: " . $e->getMessage(), [
                    'schedule_id' => $schedule->id,
                    'circle_id' => $schedule->circle_id
                ]);
            }
        }
        
        $this->info("Total sessions created: {$sessionsCreated}");
        
        return 0;
    }
    
    /**
     * Generate sessions for a specific schedule
     */
    private function generateSessionsForSchedule($schedule, $hoursAhead, $daysAhead)
    {
        $sessionsCreated = 0;
        $weeklySchedule = $schedule->weekly_schedule;
        
        if (empty($weeklySchedule)) {
            return 0;
        }
        
        $startDate = now();
        $endDate = now()->addDays($daysAhead);
        
        // Check each day in the range
        for ($currentDate = $startDate->copy(); $currentDate <= $endDate; $currentDate->addDay()) {
            $dayName = strtolower($currentDate->format('l'));
            
            // Convert to our day format
            $dayMapping = [
                'saturday' => 'saturday',
                'sunday' => 'sunday',
                'monday' => 'monday',
                'tuesday' => 'tuesday',
                'wednesday' => 'wednesday',
                'thursday' => 'thursday',
                'friday' => 'friday'
            ];
            
            $mappedDay = $dayMapping[$dayName] ?? null;
            if (!$mappedDay) continue;
            
            // Check if this day is in the schedule
            foreach ($weeklySchedule as $scheduleItem) {
                if ($scheduleItem['day'] !== $mappedDay) continue;
                
                $sessionTime = $scheduleItem['time'];
                $duration = $scheduleItem['duration_minutes'] ?? $schedule->default_duration_minutes ?? 60;
                
                $sessionDateTime = $currentDate->copy()->setTimeFromTimeString($sessionTime);
                
                // Only generate if session is within the "hours ahead" window
                $hoursUntilSession = now()->diffInHours($sessionDateTime, false);
                if ($hoursUntilSession > $hoursAhead || $hoursUntilSession < 0) {
                    continue;
                }
                
                // Check if session already exists
                $existingSession = QuranSession::where('circle_id', $schedule->circle_id)
                    ->where('scheduled_at', $sessionDateTime)
                    ->exists();
                
                if ($existingSession) {
                    continue;
                }
                
                // Check for teacher conflicts
                $teacherConflict = QuranSession::where('quran_teacher_id', $schedule->quran_teacher_id)
                    ->where('scheduled_at', '<=', $sessionDateTime)
                    ->where('scheduled_at', '>', $sessionDateTime->copy()->subMinutes($duration))
                    ->exists();
                
                if ($teacherConflict) {
                    Log::warning("Teacher conflict detected for session", [
                        'circle_id' => $schedule->circle_id,
                        'teacher_id' => $schedule->quran_teacher_id,
                        'scheduled_at' => $sessionDateTime
                    ]);
                    continue;
                }
                
                // Create the session
                QuranSession::create([
                    'academy_id' => $schedule->academy_id,
                    'quran_teacher_id' => $schedule->quran_teacher_id,
                    'circle_id' => $schedule->circle_id,
                    'session_type' => 'group',
                    'scheduled_at' => $sessionDateTime,
                    'duration_minutes' => $duration,
                    'status' => 'pending',
                    'title' => $schedule->session_title_template ?? "جلسة {$schedule->circle->name}",
                    'description' => $schedule->session_description_template ?? "جلسة جماعية",
                    'lesson_objectives' => $schedule->default_lesson_objectives ?? [],
                    'meeting_link' => $schedule->meeting_link,
                    'meeting_id' => $schedule->meeting_id,
                    'meeting_password' => $schedule->meeting_password,
                    'recording_enabled' => $schedule->recording_enabled ?? true,
                    'is_generated' => true,
                    'generated_from_schedule_id' => $schedule->id,
                    'created_by' => null, // System generated
                ]);
                
                $sessionsCreated++;
            }
        }
        
        // Update last generated timestamp
        $schedule->update(['last_generated_at' => now()]);
        
        return $sessionsCreated;
    }
}