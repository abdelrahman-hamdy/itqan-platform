<?php

namespace App\Jobs;

use App\Models\QuranSession;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SessionStartingSoonNotification;
use Carbon\Carbon;

class PrepareUpcomingSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    private GoogleCalendarService $googleCalendarService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->googleCalendarService = app(GoogleCalendarService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        $endTime = now()->addHour(); // Prepare sessions 1 hour ahead

        Log::info('Starting session preparation job', [
            'start_time' => $startTime->toISOString(),
            'end_time' => $endTime->toISOString()
        ]);

        // Get sessions that need preparation
        $sessionsToPrep = QuranSession::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$startTime, $endTime])
            ->whereNull('preparation_completed_at')
            ->with([
                'quranTeacher.user',
                'student',
                'circle.students',
                'subscription'
            ])
            ->get();

        $results = [
            'total' => $sessionsToPrep->count(),
            'prepared' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($sessionsToPrep as $session) {
            try {
                $this->prepareSession($session);
                $results['prepared']++;
                
                Log::info('Session prepared successfully', [
                    'session_id' => $session->id,
                    'scheduled_at' => $session->scheduled_at
                ]);
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to prepare session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Update session with error
                $session->update([
                    'meeting_creation_error' => $e->getMessage(),
                    'last_error_at' => now(),
                    'retry_count' => ($session->retry_count ?? 0) + 1
                ]);
            }
        }

        Log::info('Session preparation job completed', $results);
    }

    /**
     * Prepare individual session
     */
    private function prepareSession(QuranSession $session): void
    {
        // 1. Create Google Meet link if not exists
        if (!$session->google_meet_url) {
            $this->createMeetingLink($session);
        }

        // 2. Send notifications to participants
        $this->sendSessionNotifications($session);

        // 3. Mark as prepared
        $session->update([
            'preparation_completed_at' => now(),
            'meeting_creation_error' => null,
            'last_error_at' => null
        ]);
    }

    /**
     * Create Google Meet link for session
     */
    private function createMeetingLink(QuranSession $session): void
    {
        try {
            $result = $this->googleCalendarService->createSessionMeeting($session);
            
            if (!$result['success']) {
                throw new \Exception('Failed to create meeting: ' . ($result['error'] ?? 'Unknown error'));
            }
            
            Log::info('Meeting link created', [
                'session_id' => $session->id,
                'meet_url' => $result['meet_url'],
                'fallback_used' => $result['fallback_used'] ?? false
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create meeting link', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            
            // Try to create a basic meeting link as last resort
            $this->createFallbackMeetingLink($session);
        }
    }

    /**
     * Create fallback meeting link
     */
    private function createFallbackMeetingLink(QuranSession $session): void
    {
        // Generate a simple meeting room link
        $meetingId = 'session-' . $session->id . '-' . uniqid();
        $meetingLink = "https://meet.google.com/{$meetingId}";
        
        $session->update([
            'meeting_link' => $meetingLink,
            'meeting_id' => $meetingId,
            'meeting_source' => 'manual',
            'meeting_created_at' => now()
        ]);
        
        Log::warning('Created fallback meeting link', [
            'session_id' => $session->id,
            'meeting_link' => $meetingLink
        ]);
    }

    /**
     * Send notifications to session participants
     */
    private function sendSessionNotifications(QuranSession $session): void
    {
        $notifications = [];

        try {
            // Send to teacher
            if ($session->quranTeacher && $session->quranTeacher->user) {
                Notification::send(
                    $session->quranTeacher->user,
                    new SessionStartingSoonNotification($session, 'teacher')
                );
                $notifications[] = 'teacher';
            }

            // Send to student (individual session)
            if ($session->student) {
                Notification::send(
                    $session->student,
                    new SessionStartingSoonNotification($session, 'student')
                );
                $notifications[] = 'student';
            }

            // Send to circle students (group session)
            if ($session->circle && $session->circle->students) {
                $students = $session->circle->students;
                Notification::send(
                    $students,
                    new SessionStartingSoonNotification($session, 'student')
                );
                $notifications[] = 'circle_students_' . $students->count();
            }

            // Log notification details
            $session->update([
                'notification_log' => [
                    'sent_at' => now(),
                    'recipients' => $notifications,
                    'total_sent' => count($notifications)
                ],
                'reminder_sent_at' => now()
            ]);

            Log::info('Session notifications sent', [
                'session_id' => $session->id,
                'recipients' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send session notifications', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PrepareUpcomingSessions job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}