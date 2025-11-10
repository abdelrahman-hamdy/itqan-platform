<?php

namespace App\Observers;

use App\Models\AcademicSessionAttendance;
use App\Services\AcademicProgressService;
use Illuminate\Support\Facades\Log;

class AcademicSessionAttendanceObserver
{
    protected AcademicProgressService $progressService;

    public function __construct(AcademicProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Handle the AcademicSessionAttendance "created" event.
     */
    public function created(AcademicSessionAttendance $attendance): void
    {
        $this->updateProgress($attendance);
    }

    /**
     * Handle the AcademicSessionAttendance "updated" event.
     */
    public function updated(AcademicSessionAttendance $attendance): void
    {
        // Only update if status changed
        if ($attendance->isDirty('status')) {
            $this->updateProgress($attendance);
        }
    }

    /**
     * Update progress from attendance record
     */
    private function updateProgress(AcademicSessionAttendance $attendance): void
    {
        try {
            $session = $attendance->academicSession;
            if ($session && $attendance->status) {
                $this->progressService->updateFromAttendance($session, $attendance->status);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update progress from attendance', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
