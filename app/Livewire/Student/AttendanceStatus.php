<?php

namespace App\Livewire\Student;

use App\Enums\AttendanceStatus as AttendanceStatusEnum;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AttendanceStatus extends Component
{
    public $sessionId;

    public $sessionType;

    public $userId;

    // Attendance data
    public $status = 'loading'; // loading, waiting, preparation, in_meeting, completed (these are UI states, not enum values)

    public $attendanceText = '';

    public $attendanceTime = '--';

    public $duration = 0;

    public $firstJoin = null;

    public $lastLeave = null;

    public $dotColor = 'bg-gray-400';

    public $showProgress = false;

    public $attendancePercentage = 0;

    // Session timing
    public $preparationStart = null;

    public $sessionStart = null;

    public $sessionEnd = null;

    public $now = null;

    public function mount($sessionId, $sessionType, $userId = null)
    {
        $this->sessionId = $sessionId;
        $this->sessionType = $sessionType;
        $this->userId = $userId ?? Auth::id();
        $this->attendanceText = __('components.attendance.loading');

        $this->loadSessionTiming();
        $this->updateAttendanceStatus();
    }

    #[On('attendance-updated')]
    public function refreshAttendance()
    {
        $this->updateAttendanceStatus();
    }

    public function loadSessionTiming()
    {
        $session = $this->getSession();

        if (! $session) {
            return;
        }

        $this->sessionStart = $session->scheduled_at;
        $this->sessionEnd = $session->scheduled_at->copy()->addMinutes($session->duration_minutes ?? 60);

        // Preparation time is 10 minutes before session start
        $this->preparationStart = $session->scheduled_at->copy()->subMinutes(10);
    }

    public function updateAttendanceStatus()
    {
        $this->now = now();
        $session = $this->getSession();

        if (! $session) {
            $this->status = 'loading';
            $this->attendanceText = __('components.attendance.session_not_found');

            return;
        }

        // Get attendance record
        $attendance = MeetingAttendance::where('session_id', $this->sessionId)
            ->where('user_id', $this->userId)
            ->first();

        // Determine current state based on time
        if ($this->now->lt($this->preparationStart)) {
            // Before preparation time
            $this->setWaitingState();
        } elseif ($this->now->gte($this->preparationStart) && $this->now->lt($this->sessionStart)) {
            // In preparation time
            $this->setPreparationState();
        } elseif ($this->now->gte($this->sessionStart) && $this->now->lt($this->sessionEnd)) {
            // Session is live
            $this->setLiveState($attendance);
        } else {
            // Session has ended
            $this->setCompletedState($attendance, $session);
        }
    }

    private function setWaitingState()
    {
        $this->status = 'waiting';
        $this->attendanceText = __('components.attendance.waiting_for_session');
        $this->attendanceTime = __('components.attendance.session_starts_at', ['time' => $this->sessionStart->format('h:i A')]);
        $this->dotColor = 'bg-blue-400';
        $this->showProgress = false;
    }

    private function setPreparationState()
    {
        $this->status = 'preparation';
        $this->attendanceText = __('components.attendance.preparation_time');
        $minutesUntilStart = $this->now->diffInMinutes($this->sessionStart, false);
        $this->attendanceTime = __('components.attendance.session_starts_in', ['minutes' => abs($minutesUntilStart)]);
        $this->dotColor = 'bg-yellow-400 animate-pulse';
        $this->showProgress = false;
    }

    private function setLiveState($attendance)
    {
        $this->status = 'in_meeting';

        // Show clear, non-confusing messages during live session
        if ($attendance && $attendance->first_join_time) {
            // Student has joined at some point
            // Check if currently in meeting or has left
            $currentlyInMeeting = $attendance->isCurrentlyInMeeting();

            if ($currentlyInMeeting) {
                // Student is currently in meeting RIGHT NOW
                $this->attendanceText = __('components.attendance.currently_in_session');
                $this->dotColor = 'bg-green-500 animate-pulse';
                $this->attendanceTime = __('components.attendance.final_attendance_after_session');
            } else {
                // Student has left (not currently in meeting)
                $this->attendanceText = __('components.attendance.not_connected');
                $this->dotColor = 'bg-orange-400';
                $this->attendanceTime = __('components.attendance.final_attendance_can_rejoin');
            }
        } else {
            // Student hasn't joined yet
            $this->attendanceText = __('components.attendance.session_ongoing_not_joined');
            $this->dotColor = 'bg-red-400 animate-pulse';
            $this->attendanceTime = __('components.attendance.join_now');
        }
    }

    private function setCompletedState($attendance, $session)
    {
        $this->status = 'completed';

        if ($attendance && $attendance->is_calculated) {
            // Attendance has been calculated - show final status
            $this->duration = $attendance->total_duration_minutes ?? 0;
            $sessionDuration = $session->duration_minutes ?? 60;
            $this->attendancePercentage = $attendance->attendance_percentage ?? 0;

            // Get status enum
            try {
                $statusEnum = AttendanceStatusEnum::from($attendance->attendance_status);

                // Use enum label for display
                $this->attendanceText = $statusEnum->label();

                // Set dot color based on status
                $dotColors = [
                    AttendanceStatusEnum::ATTENDED->value => 'bg-green-500',
                    AttendanceStatusEnum::LATE->value => 'bg-yellow-500',
                    AttendanceStatusEnum::LEFT->value => 'bg-orange-500',
                    AttendanceStatusEnum::ABSENT->value => 'bg-red-500',
                ];
                $this->dotColor = $dotColors[$statusEnum->value] ?? 'bg-gray-500';

                // Show more detailed time information
                $this->attendanceTime = __('components.attendance.attendance_summary', [
                    'attended' => $this->duration,
                    'total' => $sessionDuration,
                    'percentage' => round($this->attendancePercentage),
                ]);
                $this->showProgress = true;

            } catch (\ValueError $e) {
                // Invalid status value - log error
                \Log::error('Invalid attendance status', [
                    'status' => $attendance->attendance_status,
                    'session_id' => $this->sessionId,
                    'user_id' => $this->userId,
                ]);

                $this->attendanceText = __('components.attendance.calculation_error');
                $this->dotColor = 'bg-gray-500';
                $this->attendanceTime = __('components.attendance.contact_support');
                $this->showProgress = false;
            }

            $this->firstJoin = $attendance->first_join_time;
            $this->lastLeave = $attendance->last_leave_time;
        } elseif ($attendance && ! $attendance->is_calculated) {
            // Attendance record exists but not calculated yet
            // This is normal - calculation happens within minutes after session ends
            $this->attendanceText = __('components.attendance.calculating_attendance');
            $this->dotColor = 'bg-blue-400 animate-pulse';
            $this->attendanceTime = __('components.attendance.refresh_page');
            $this->showProgress = false;
        } else {
            // No attendance record at all - student never joined
            $this->attendanceText = __('components.attendance.did_not_attend');
            $this->dotColor = 'bg-red-500';
            $this->attendanceTime = __('components.attendance.no_attendance_recorded');
            $this->showProgress = false;
        }
    }

    private function getSession()
    {
        return match ($this->sessionType) {
            'quran' => QuranSession::find($this->sessionId),
            'academic' => AcademicSession::find($this->sessionId),
            'interactive' => InteractiveCourseSession::find($this->sessionId),
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.student.attendance-status');
    }
}
