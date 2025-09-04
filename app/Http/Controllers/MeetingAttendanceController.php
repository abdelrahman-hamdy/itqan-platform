<?php

namespace App\Http\Controllers;

use App\Models\QuranSession;
use App\Services\UnifiedAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MeetingAttendanceController extends Controller
{
    private UnifiedAttendanceService $attendanceService;

    public function __construct(UnifiedAttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Record user joining the meeting
     */
    public function recordJoin(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:quran_sessions,id',
                'room_name' => 'required|string',
            ]);

            $session = QuranSession::findOrFail($request->session_id);
            $user = Auth::user();

            // Verify user has access to this session
            if (! $this->userCanAccessSession($session, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول لهذه الجلسة',
                ], 403);
            }

            // Record the join event
            $success = $this->attendanceService->handleUserJoin($session, $user);

            if ($success) {
                // Get updated attendance status
                $attendanceStatus = $this->getAttendanceStatus($session, $user);

                Log::info('Meeting join recorded successfully', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'attendance_status' => $attendanceStatus,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل دخولك للجلسة بنجاح',
                    'attendance_status' => $attendanceStatus,
                    'user_name' => $user->first_name.' '.$user->last_name,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تسجيل دخولك للجلسة',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to record meeting join', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تسجيل الحضور',
            ], 500);
        }
    }

    /**
     * Record user leaving the meeting
     */
    public function recordLeave(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:quran_sessions,id',
                'room_name' => 'required|string',
            ]);

            $session = QuranSession::findOrFail($request->session_id);
            $user = Auth::user();

            // Record the leave event
            $success = $this->attendanceService->handleUserLeave($session, $user);

            if ($success) {
                // Get updated attendance status
                $attendanceStatus = $this->getAttendanceStatus($session, $user);

                Log::info('Meeting leave recorded successfully', [
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'final_attendance_status' => $attendanceStatus,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل خروجك من الجلسة بنجاح',
                    'attendance_status' => $attendanceStatus,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في تسجيل خروجك من الجلسة',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to record meeting leave', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تسجيل الحضور',
            ], 500);
        }
    }

    /**
     * Get current attendance status for a user in a session
     */
    public function getStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:quran_sessions,id',
            ]);

            $session = QuranSession::findOrFail($request->session_id);
            $user = Auth::user();

            $attendanceStatus = $this->getAttendanceStatus($session, $user);

            return response()->json([
                'success' => true,
                'attendance_status' => $attendanceStatus,
                'session_id' => $session->id,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get attendance status', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في الحصول على حالة الحضور',
            ], 500);
        }
    }

    /**
     * Check if user can access the session
     */
    private function userCanAccessSession(QuranSession $session, $user): bool
    {
        Log::info('Checking user access to session', [
            'session_id' => $session->id,
            'session_type' => $session->session_type,
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'session_teacher_id' => $session->quran_teacher_id,
            'session_student_id' => $session->student_id ?? 'null',
        ]);

        // Teachers can access if they're the session teacher
        if ($session->quran_teacher_id === $user->id) {
            Log::info('Access granted: User is the session teacher');

            return true;
        }

        // Students can access if they're enrolled in the session
        if ($session->session_type === 'individual') {
            // CRITICAL FIX: student_id field contains user_id, not student_profile_id
            if ($session->student_id === $user->id) {
                Log::info('Access granted: User is the individual session student (by user_id)');

                return true;
            }

            // Fallback: also check against student_profile_id for backward compatibility
            if ($session->student_id === $user->student_profile?->id) {
                Log::info('Access granted: User is the individual session student (by profile_id)');

                return true;
            }
        }

        if ($session->session_type === 'group' && $session->circle && $session->circle->students()->where('student_profiles.user_id', $user->id)->exists()) {
            Log::info('Access granted: User is enrolled in the group session circle');

            return true;
        }

        Log::warning('Access denied: User cannot access this session', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'session_type' => $session->session_type,
            'session_student_id' => $session->student_id ?? 'null',
            'user_student_profile_id' => $user->student_profile?->id ?? 'null',
        ]);

        return false;
    }

    /**
     * Get attendance status for a user in a session
     */
    private function getAttendanceStatus(QuranSession $session, $user): array
    {
        $attendance = $session->meetingAttendances()->where('user_id', $user->id)->first();

        if (! $attendance) {
            return [
                'status' => 'absent',
                'status_ar' => 'غائب',
                'class' => 'bg-red-100 text-red-800',
                'icon' => 'ri-user-unfollow-line',
                'join_time' => null,
                'total_duration' => 0,
            ];
        }

        // Calculate real-time attendance status
        $status = $this->calculateAttendanceStatus($session, $attendance);

        return [
            'status' => $status,
            'status_ar' => $this->getStatusLabel($status),
            'class' => $this->getStatusClass($status),
            'icon' => $this->getStatusIcon($status),
            'join_time' => $attendance->first_join_time?->format('H:i'),
            'total_duration' => $attendance->total_duration_minutes ?? 0,
            'percentage' => $attendance->attendance_percentage ?? 0,
        ];
    }

    /**
     * Calculate attendance status based on timing
     */
    private function calculateAttendanceStatus(QuranSession $session, $attendance): string
    {
        if (! $attendance->first_join_time) {
            return 'absent';
        }

        $scheduledStart = $session->scheduled_at;
        $firstJoin = $attendance->first_join_time;
        $graceMinutes = 15; // Default grace period

        // Late if joined more than grace minutes after scheduled start
        if ($firstJoin->gt($scheduledStart->addMinutes($graceMinutes))) {
            return 'late';
        }

        // Check if sufficient attendance percentage
        if ($attendance->attendance_percentage && $attendance->attendance_percentage < 75) {
            return 'partial';
        }

        return 'present';
    }

    /**
     * Get Arabic label for status
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'present' => 'حاضر',
            'late' => 'متأخر',
            'partial' => 'حضور جزئي',
            'absent' => 'غائب',
            default => 'غير محدد',
        };
    }

    /**
     * Get CSS class for status
     */
    private function getStatusClass(string $status): string
    {
        return match ($status) {
            'present' => 'bg-green-100 text-green-800',
            'late' => 'bg-yellow-100 text-yellow-800',
            'partial' => 'bg-orange-100 text-orange-800',
            'absent' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get icon for status
     */
    private function getStatusIcon(string $status): string
    {
        return match ($status) {
            'present' => 'ri-user-follow-line',
            'late' => 'ri-user-clock-line',
            'partial' => 'ri-user-2-line',
            'absent' => 'ri-user-unfollow-line',
            default => 'ri-user-line',
        };
    }
}
