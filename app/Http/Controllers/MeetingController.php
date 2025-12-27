<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use App\Services\SessionMeetingService;
use App\Enums\SessionStatus;

class MeetingController extends Controller
{
    private LiveKitService $livekitService;
    private SessionMeetingService $sessionMeetingService;

    public function __construct(LiveKitService $livekitService, SessionMeetingService $sessionMeetingService)
    {
        $this->livekitService = $livekitService;
        $this->sessionMeetingService = $sessionMeetingService;
    }

    // REMOVED: join method - meetings now happen inline in session pages



    /**
     * Create or get meeting for a session (API endpoint)
     */
    public function createOrGet(Request $request, int $sessionId)
    {
        try {
            $session = QuranSession::findOrFail($sessionId);
            $user = $request->user();

            // First check if user can join this session at all
            if (!$this->canJoinSession($user, $session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالانضمام إلى هذه الجلسة'
                ], 403);
            }

            // If meeting room already exists, return it (anyone who can join can access)
            if ($session->meeting_room_name && $session->isMeetingValid()) {
                // Get current subdomain from request
                $subdomain = $request->route('subdomain') ?? $session->academy->subdomain;
                
                $meetingInfo = [
                    'join_url' => route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]), // Meetings are now inline in session pages
                    'session_timing' => $this->sessionMeetingService->getSessionTiming($session),
                    'room_activity' => $this->sessionMeetingService->getRoomActivity($session),
                ];

                return response()->json([
                    'success' => true,
                    'message' => 'الاجتماع متاح للانضمام',
                    'data' => [
                        'meeting_url' => $session->meeting_link,
                        'room_name' => $session->meeting_room_name,
                        'join_url' => $meetingInfo['join_url'],
                        'session_timing' => $meetingInfo['session_timing'],
                        'room_activity' => $meetingInfo['room_activity'],
                        'exists' => true,
                    ]
                ]);
            }

            // If no meeting exists, only teachers/admins can create new ones
            if (!$this->canUserCreateMeeting($user, $session)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم إنشاء الاجتماع بعد. يرجى انتظار المعلم لبدء الجلسة.'
                ], 423); // 423 Locked - meeting not ready yet
            }

            // Generate or get existing meeting using timing service
            $meetingInfo = $this->sessionMeetingService->forceCreateMeeting($session);
            
            // Mark as persistent
            $this->sessionMeetingService->markSessionPersistent($session);

            // Get current subdomain from request
            $subdomain = $request->route('subdomain') ?? $session->academy->subdomain;

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الاجتماع بنجاح',
                'data' => [
                    'meeting_url' => $session->meeting_link,
                    'room_name' => $session->meeting_room_name,
                    'join_url' => route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]),
                    'session_timing' => $meetingInfo['session_timing'],
                    'room_activity' => $this->sessionMeetingService->getRoomActivity($session),
                    'created' => true,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create/get meeting', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحضير غرفة الاجتماع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can join the session (alias for consistency)
     */
    private function canJoinSession($user, QuranSession $session): bool
    {
        return $this->canUserJoinSession($user, $session);
    }

    /**
     * Check if user can join the session
     */
    private function canUserJoinSession($user, QuranSession $session): bool
    {
        // Super admin, admin, and teachers can join any session
        if (in_array($user->user_type, ['super_admin', 'admin', 'quran_teacher', 'academic_teacher'])) {
            return true;
        }

        if ($user->user_type === 'student') {
            // Individual session
            if ($session->student_id === $user->id) {
                return true;
            }
            
            // Group/circle session
            if ($session->circle_id && $session->circle) {
                return $session->circle->students()->where('student_id', $user->id)->exists();
            }
            
            // Subscription session
            if ($session->quran_subscription_id && $session->subscription) {
                return $session->subscription->student_id === $user->id;
            }
            
            // Individual circle session
            if ($session->individual_circle_id && $session->individualCircle) {
                return $session->individualCircle->student_id === $user->id;
            }
        }

        if ($user->user_type === 'parent') {
            $childrenIds = $user->children()->pluck('id')->toArray();
            
            // Check various session types for parent's children
            if (in_array($session->student_id, $childrenIds)) {
                return true;
            }
            
            if ($session->circle_id && $session->circle) {
                return $session->circle->students()->whereIn('student_id', $childrenIds)->exists();
            }
            
            if ($session->quran_subscription_id && $session->subscription) {
                return in_array($session->subscription->student_id, $childrenIds);
            }
            
            if ($session->individual_circle_id && $session->individualCircle) {
                return in_array($session->individualCircle->student_id, $childrenIds);
            }
        }

        return false;
    }

    /**
     * Check if user can create meeting for the session
     */
    private function canUserCreateMeeting($user, QuranSession $session): bool
    {
        // Super admin can create any meeting
        if ($user->user_type === 'super_admin') {
            return true;
        }
        
        // Academy admin can create meetings in their academy
        if ($user->user_type === 'admin' && $session->academy_id === $user->academy_id) {
            return true;
        }
        
        // Teachers can create their own session meetings
        if (in_array($user->user_type, ['quran_teacher', 'academic_teacher'])) {
            return $session->quran_teacher_id === $user->id;
        }

        return false;
    }



    /**
     * Get user permissions for the meeting
     */
    private function getUserPermissions($user, QuranSession $session): array
    {
        $isTeacher = in_array($user->user_type, ['quran_teacher', 'academic_teacher']);
        $isAdmin = in_array($user->user_type, ['admin', 'super_admin']);
        
        return [
            'can_publish' => true, // Everyone can share audio/video
            'can_subscribe' => true, // Everyone can see/hear others
            'can_update_metadata' => $isTeacher || $isAdmin,
            'can_record' => $isTeacher || $isAdmin,
            'can_share_screen' => true, // Allow screen sharing for all
        ];
    }

    /**
     * Get user role in session for display
     */
    private function getUserRoleInSession($user, QuranSession $session): string
    {
        switch ($user->user_type) {
            case 'quran_teacher':
            case 'academic_teacher':
                return 'المعلم';
            case 'student':
                return 'الطالب';
            case 'parent':
                return 'ولي الأمر';
            case 'admin':
                return 'المدير';
            case 'super_admin':
                return 'المدير العام';
            default:
                return 'مشارك';
        }
    }


}
