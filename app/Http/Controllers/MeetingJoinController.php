<?php

namespace App\Http\Controllers;

use App\Models\QuranSession;
use App\Models\User;
use App\Services\LiveKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MeetingJoinController extends Controller
{
    private LiveKitService $livekitService;

    public function __construct(LiveKitService $livekitService)
    {
        $this->livekitService = $livekitService;
    }

    /**
     * Join a meeting session
     */
    public function join(Request $request, QuranSession $session)
    {
        $user = Auth::user();
        
        try {
            // Verify user has access to this session
            if (!$this->userCanJoinSession($user, $session)) {
                abort(403, 'غير مسموح لك بدخول هذه الجلسة');
            }

            // Check if meeting room exists
            if (empty($session->meeting_room_name)) {
                return view('meetings.not-ready', [
                    'session' => $session,
                    'message' => 'لم يتم إنشاء الاجتماع بعد. سيتوفر الرابط قريباً.'
                ]);
            }

            // Check if session is within allowed time window
            if (!$this->isSessionTimeValid($session)) {
                return view('meetings.not-ready', [
                    'session' => $session,
                    'message' => 'الجلسة غير متاحة حالياً. يمكنك الدخول قبل 15 دقيقة من موعد البدء.'
                ]);
            }

            // Generate participant token
            $participantName = $user->name ?? $user->first_name . ' ' . $user->last_name;
            $participantIdentity = 'user_' . $user->id;
            
            // Set permissions based on user role
            $canPublish = true;
            $canSubscribe = true;
            $canUpdateMetadata = $user->user_type === 'quran_teacher';

            $token = $this->livekitService->generateParticipantToken(
                $session->meeting_room_name,
                $participantIdentity,
                $participantName,
                $canPublish,
                $canSubscribe,
                $canUpdateMetadata
            );

            if (!$token) {
                throw new \Exception('فشل في إنشاء رمز الدخول');
            }

            // Log join attempt
            Log::info('User joining meeting', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'room_name' => $session->meeting_room_name,
                'participant_identity' => $participantIdentity
            ]);

            // Return meeting join page with token
            return view('meetings.join', [
                'session' => $session,
                'token' => $token,
                'roomName' => $session->meeting_room_name,
                'participantName' => $participantName,
                'livekitServerUrl' => config('livekit.server_url'),
                'userRole' => $user->user_type
            ]);

        } catch (\Exception $e) {
            Log::error('Meeting join failed', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            return view('meetings.error', [
                'session' => $session,
                'message' => 'حدث خطأ أثناء محاولة الدخول للجلسة. يرجى المحاولة مرة أخرى.'
            ]);
        }
    }

    /**
     * Check if user can join this session
     */
    private function userCanJoinSession(User $user, QuranSession $session): bool
    {
        // Teachers can join sessions they're assigned to
        if ($user->user_type === 'quran_teacher') {
            return $session->quran_teacher_id === $user->id;
        }

        // Students can join if they're enrolled in the circle or subscription
        if ($user->user_type === 'student') {
            // For individual circle sessions
            if ($session->individual_circle_id) {
                return $session->individualCircle && 
                       $session->individualCircle->student_id === $user->id;
            }

            // For group circle sessions  
            if ($session->quran_circle_id) {
                return $session->circle && 
                       $session->circle->students()->where('users.id', $user->id)->exists();
            }

            // For subscription sessions
            if ($session->quran_subscription_id) {
                return $session->subscription && 
                       $session->subscription->student_id === $user->id;
            }
        }

        return false;
    }

    /**
     * Check if session is within valid time window for joining
     */
    private function isSessionTimeValid(QuranSession $session): bool
    {
        if (!$session->scheduled_at) {
            return false;
        }

        $now = now();
        $sessionStart = $session->scheduled_at;
        $sessionEnd = $sessionStart->copy()->addMinutes($session->duration_minutes ?? 60);

        // Allow joining 15 minutes before and during session
        $allowJoinFrom = $sessionStart->copy()->subMinutes(15);
        
        return $now->between($allowJoinFrom, $sessionEnd);
    }
}
