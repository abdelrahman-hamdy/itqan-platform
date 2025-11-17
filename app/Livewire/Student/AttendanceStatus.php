<?php

namespace App\Livewire\Student;

use App\Models\MeetingAttendance;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Enums\AttendanceStatus as AttendanceStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AttendanceStatus extends Component
{
    public $sessionId;
    public $sessionType;
    public $userId;

    // Attendance data
    public $status = 'loading'; // loading, waiting, preparation, in_meeting, completed
    public $attendanceText = 'جاري التحميل...';
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

        if (!$session) {
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

        if (!$session) {
            $this->status = 'loading';
            $this->attendanceText = 'الجلسة غير موجودة';
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
        $this->attendanceText = 'في انتظار بدء الجلسة';
        $this->attendanceTime = 'الجلسة تبدأ في ' . $this->sessionStart->format('h:i A');
        $this->dotColor = 'bg-blue-400';
        $this->showProgress = false;
    }

    private function setPreparationState()
    {
        $this->status = 'preparation';
        $this->attendanceText = 'وقت التحضير - يمكنك الدخول الآن';
        $minutesUntilStart = $this->now->diffInMinutes($this->sessionStart, false);
        $this->attendanceTime = 'الجلسة تبدأ خلال ' . abs($minutesUntilStart) . ' دقيقة';
        $this->dotColor = 'bg-yellow-400 animate-pulse';
        $this->showProgress = false;
    }

    private function setLiveState($attendance)
    {
        $this->status = 'in_meeting';

        // 🔥 IMPROVED: Show clear, non-confusing messages during live session
        if ($attendance && $attendance->first_join_time) {
            // Student has joined at some point
            // Check if currently in meeting or has left
            $currentlyInMeeting = $attendance->isCurrentlyInMeeting();

            if ($currentlyInMeeting) {
                // Student is currently in meeting RIGHT NOW
                $this->attendanceText = 'أنت في الجلسة الآن';
                $this->dotColor = 'bg-green-500 animate-pulse';
                $this->attendanceTime = 'سيتم حساب الحضور النهائي بعد انتهاء الجلسة';
            } else {
                // Student has left (not currently in meeting)
                $this->attendanceText = 'غير متصل حالياً';
                $this->dotColor = 'bg-orange-400';
                $this->attendanceTime = 'سيتم حساب الحضور النهائي بعد انتهاء الجلسة - يمكنك إعادة الانضمام';
            }
        } else {
            // Student hasn't joined yet
            $this->attendanceText = 'الجلسة جارية - لم تنضم بعد';
            $this->dotColor = 'bg-red-400 animate-pulse';
            $this->attendanceTime = 'انضم الآن لتسجيل حضورك';
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
                    'attended' => 'bg-green-500',
                    'late' => 'bg-yellow-500',
                    'leaved' => 'bg-orange-500',
                    'absent' => 'bg-red-500',
                ];
                $this->dotColor = $dotColors[$statusEnum->value] ?? 'bg-gray-500';

                // 🔥 IMPROVED: Show more detailed time information
                $this->attendanceTime = sprintf(
                    'الحضور: %d من %d دقيقة (%d%%)',
                    $this->duration,
                    $sessionDuration,
                    round($this->attendancePercentage)
                );
                $this->showProgress = true;

            } catch (\ValueError $e) {
                // Invalid status value - log error
                \Log::error('Invalid attendance status', [
                    'status' => $attendance->attendance_status,
                    'session_id' => $this->sessionId,
                    'user_id' => $this->userId,
                ]);

                $this->attendanceText = 'خطأ في حساب الحضور';
                $this->dotColor = 'bg-gray-500';
                $this->attendanceTime = 'يرجى الاتصال بالدعم الفني';
                $this->showProgress = false;
            }

            $this->firstJoin = $attendance->first_join_time;
            $this->lastLeave = $attendance->last_leave_time;
        } elseif ($attendance && !$attendance->is_calculated) {
            // Attendance record exists but not calculated yet
            // This is normal - calculation happens within minutes after session ends
            $this->attendanceText = 'جاري حساب الحضور النهائي...';
            $this->dotColor = 'bg-blue-400 animate-pulse';
            $this->attendanceTime = 'سيتم الحساب خلال دقائق - يرجى تحديث الصفحة';
            $this->showProgress = false;
        } else {
            // No attendance record at all - student never joined
            $this->attendanceText = 'لم تحضر الجلسة';
            $this->dotColor = 'bg-red-500';
            $this->attendanceTime = 'لم يتم تسجيل أي حضور في هذه الجلسة';
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
