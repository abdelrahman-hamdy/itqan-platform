<?php

namespace App\Http\Controllers;

use Exception;
use App\Enums\UserType;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Services\LiveKitService;
use App\Services\SessionMeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller
{
    use ApiResponses;

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
    public function createOrGet(Request $request, int $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::findOrFail($sessionId);
            $user = $request->user();

            // First check if user can join this session at all
            if (! $this->canJoinSession($user, $session)) {
                return $this->forbidden(__('meetings.api.not_authorized_join'));
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

                return $this->success([
                    'message' => __('meetings.api.meeting_available'),
                    'data' => [
                        'meeting_url' => $session->meeting_link,
                        'room_name' => $session->meeting_room_name,
                        'join_url' => $meetingInfo['join_url'],
                        'session_timing' => $meetingInfo['session_timing'],
                        'room_activity' => $meetingInfo['room_activity'],
                        'exists' => true,
                    ],
                ], true, 200);
            }

            // If no meeting exists, only teachers/admins can create new ones
            if (! $this->canUserCreateMeeting($user, $session)) {
                return $this->error(__('meetings.api.meeting_not_created_wait'), 423); // 423 Locked - meeting not ready yet
            }

            // Generate or get existing meeting using timing service
            $meetingInfo = $this->sessionMeetingService->forceCreateMeeting($session);

            // Mark as persistent
            $this->sessionMeetingService->markSessionPersistent($session);

            // Get current subdomain from request
            $subdomain = $request->route('subdomain') ?? $session->academy->subdomain;

            return $this->success([
                'message' => __('meetings.api.meeting_created'),
                'data' => [
                    'meeting_url' => $session->meeting_link,
                    'room_name' => $session->meeting_room_name,
                    'join_url' => route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $session->id]),
                    'session_timing' => $meetingInfo['session_timing'],
                    'room_activity' => $this->sessionMeetingService->getRoomActivity($session),
                    'created' => true,
                ],
            ], true, 200);

        } catch (Exception $e) {
            Log::error('Failed to create/get meeting', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => $request->user()->id,
            ]);

            return $this->serverError(__('meetings.api.room_prepare_failed').': '.$e->getMessage());
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
        if (in_array($user->user_type, [UserType::SUPER_ADMIN->value, UserType::ADMIN->value, UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return true;
        }

        if ($user->user_type === UserType::STUDENT->value) {
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

        if ($user->user_type === UserType::PARENT->value) {
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
        if ($user->user_type === UserType::SUPER_ADMIN->value) {
            return true;
        }

        // Academy admin can create meetings in their academy
        if ($user->user_type === UserType::ADMIN->value && $session->academy_id === $user->academy_id) {
            return true;
        }

        // Teachers can create their own session meetings
        if (in_array($user->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value])) {
            return $session->quran_teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Get user permissions for the meeting
     */
    private function getUserPermissions($user, QuranSession $session): array
    {
        $isTeacher = in_array($user->user_type, [UserType::QURAN_TEACHER->value, UserType::ACADEMIC_TEACHER->value]);
        $isAdmin = in_array($user->user_type, [UserType::ADMIN->value, UserType::SUPER_ADMIN->value]);

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
                return __('meetings.roles.teacher');
            case 'student':
                return __('meetings.roles.student');
            case 'parent':
                return __('meetings.roles.parent');
            case 'admin':
                return __('meetings.roles.admin');
            case 'super_admin':
                return __('meetings.roles.super_admin');
            default:
                return __('meetings.roles.participant');
        }
    }
}
