<?php

namespace App\Listeners;

use Exception;
use Throwable;
use App\Events\SessionCompletedEvent;
use App\Services\Attendance\QuranReportService;
use App\Services\MeetingAttendanceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener that finalizes attendance when a session completes.
 * Decouples SessionStatusService from MeetingAttendanceService.
 */
class FinalizeAttendanceListener implements ShouldQueue
{
    public function __construct(
        private MeetingAttendanceService $meetingAttendanceService,
        private QuranReportService $quranReportService
    ) {}

    /**
     * Handle the session completed event.
     */
    public function handle(SessionCompletedEvent $event): void
    {
        $session = $event->getSession();

        try {
            Log::info('FinalizeAttendanceListener: Processing session completion', [
                'session_id' => $session->id,
                'session_type' => $event->getSessionType(),
            ]);

            // Calculate final attendance for all participants
            $attendanceResults = $this->meetingAttendanceService->calculateFinalAttendance($session);

            Log::info('FinalizeAttendanceListener: Attendance finalized', [
                'session_id' => $session->id,
                'calculated_count' => $attendanceResults['calculated_count'],
                'errors' => $attendanceResults['errors'],
            ]);

            // For Quran GROUP sessions, sync attendance to student session reports
            // Uses event-log-based aggregation (server-authoritative from LiveKit webhooks)
            if ($event->getSessionType() === 'quran' && $this->isGroupSession($session)) {
                $reportsCreated = $this->quranReportService->createReportsForSession($session);

                Log::info('FinalizeAttendanceListener: Synced Quran group attendance reports', [
                    'session_id' => $session->id,
                    'reports_count' => $reportsCreated,
                ]);
            }

        } catch (Exception $e) {
            Log::error('FinalizeAttendanceListener: Failed to finalize attendance', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if the session is a group session.
     */
    private function isGroupSession($session): bool
    {
        // Check for session_type property on QuranSession
        if (property_exists($session, 'session_type') || isset($session->session_type)) {
            return $session->session_type === 'group';
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(SessionCompletedEvent $event, Throwable $exception): void
    {
        Log::error('FinalizeAttendanceListener: Job failed', [
            'session_id' => $event->getSession()->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
