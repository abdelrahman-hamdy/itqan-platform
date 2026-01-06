<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Models\QuranTrialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MeetingLinkController extends Controller
{
    use ApiResponses;

    /**
     * Update meeting link for a session
     */
    public function updateSessionMeetingLink(Request $request, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $academy = $user->academy;

        if (! $user->isQuranTeacher()) {
            return $this->forbidden('غير مصرح لك بالوصول');
        }

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $session) {
            return $this->notFound('لم يتم العثور على الجلسة');
        }

        $validator = Validator::make($request->all(), [
            'meeting_link' => 'required|url',
            'meeting_password' => 'nullable|string|max:50',
            'meeting_id' => 'nullable|string|max:100',
        ], [
            'meeting_link.required' => 'رابط الاجتماع مطلوب',
            'meeting_link.url' => 'يجب أن يكون رابط الاجتماع صحيحاً',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
        }

        try {
            // Validate and format meeting link
            $meetingLink = $this->validateAndFormatMeetingLink($request->meeting_link);

            $session->update([
                'meeting_link' => $meetingLink,
                'meeting_password' => $request->meeting_password,
                'meeting_id' => $request->meeting_id ?: $this->extractMeetingIdFromLink($meetingLink),
            ]);

            return $this->success([
                'meeting_link' => $session->meeting_link,
                'meeting_password' => $session->meeting_password,
                'meeting_id' => $session->meeting_id,
            ], 'تم تحديث رابط الاجتماع بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث رابط الاجتماع: '.$e->getMessage());
        }
    }

    /**
     * Update meeting link for a trial request
     */
    public function updateTrialMeetingLink(Request $request, $trialRequestId): JsonResponse
    {
        $user = Auth::user();
        $academy = $user->academy;

        if (! $user->isQuranTeacher()) {
            return $this->forbidden('غير مصرح لك بالوصول');
        }

        $trialRequest = QuranTrialRequest::where('id', $trialRequestId)
            ->where('academy_id', $academy->id)
            ->where('teacher_id', $user->quranTeacherProfile->id)
            ->first();

        if (! $trialRequest) {
            return $this->notFound('لم يتم العثور على طلب الجلسة التجريبية');
        }

        $validator = Validator::make($request->all(), [
            'meeting_link' => 'required|url',
            'meeting_password' => 'nullable|string|max:50',
        ], [
            'meeting_link.required' => 'رابط الاجتماع مطلوب',
            'meeting_link.url' => 'يجب أن يكون رابط الاجتماع صحيحاً',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
        }

        try {
            // Validate and format meeting link
            $meetingLink = $this->validateAndFormatMeetingLink($request->meeting_link);

            $trialRequest->update([
                'meeting_link' => $meetingLink,
                'meeting_password' => $request->meeting_password,
            ]);

            return $this->success([
                'meeting_link' => $trialRequest->meeting_link,
                'meeting_password' => $trialRequest->meeting_password,
            ], 'تم تحديث رابط الاجتماع بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء تحديث رابط الاجتماع: '.$e->getMessage());
        }
    }

    /**
     * Generate automatic meeting link (placeholder for now)
     */
    public function generateMeetingLink(Request $request, $sessionId): JsonResponse
    {
        $user = Auth::user();
        $academy = $user->academy;

        if (! $user->isQuranTeacher()) {
            return $this->forbidden('غير مصرح لك بالوصول');
        }

        $session = QuranSession::where('id', $sessionId)
            ->where('academy_id', $academy->id)
            ->where('quran_teacher_id', $user->id)
            ->first();

        if (! $session) {
            return $this->notFound('لم يتم العثور على الجلسة');
        }

        try {
            $meetingLink = $session->generateMeetingLink();

            return $this->success([
                'meeting_link' => $meetingLink,
                'meeting_id' => $session->meeting_id,
            ], 'تم إنشاء رابط الاجتماع بنجاح');

        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء إنشاء رابط الاجتماع: '.$e->getMessage());
        }
    }

    /**
     * Get meeting platforms with their URL patterns
     */
    public function getMeetingPlatforms(): JsonResponse
    {
        $platforms = [
            'google_meet' => [
                'name' => 'Google Meet',
                'url_pattern' => 'https://meet.google.com/',
                'example' => 'https://meet.google.com/abc-defg-hij',
                'icon' => 'ri-google-line',
                'color' => 'text-blue-600',
            ],
            'zoom' => [
                'name' => 'Zoom',
                'url_pattern' => 'https://zoom.us/j/',
                'example' => 'https://zoom.us/j/1234567890',
                'icon' => 'ri-video-line',
                'color' => 'text-blue-500',
            ],
            'teams' => [
                'name' => 'Microsoft Teams',
                'url_pattern' => 'https://teams.microsoft.com/',
                'example' => 'https://teams.microsoft.com/l/meetup-join/...',
                'icon' => 'ri-microsoft-line',
                'color' => 'text-blue-700',
            ],
            'webex' => [
                'name' => 'Cisco Webex',
                'url_pattern' => 'https://webex.com/',
                'example' => 'https://company.webex.com/meet/username',
                'icon' => 'ri-vidicon-line',
                'color' => 'text-green-600',
            ],
            'jitsi' => [
                'name' => 'Jitsi Meet',
                'url_pattern' => 'https://meet.jit.si/',
                'example' => 'https://meet.jit.si/YourRoomName',
                'icon' => 'ri-video-chat-line',
                'color' => 'text-orange-600',
            ],
        ];

        return $this->success($platforms);
    }

    /**
     * Validate and format meeting link
     */
    private function validateAndFormatMeetingLink(string $url): string
    {
        // Remove any whitespace
        $url = trim($url);

        // Ensure the URL has a protocol
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://'.$url;
        }

        // Validate URL format
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('رابط الاجتماع غير صحيح');
        }

        // Additional validation for known platforms
        $knownPlatforms = [
            'meet.google.com',
            'zoom.us',
            'teams.microsoft.com',
            'webex.com',
            'meet.jit.si',
            'gotomeeting.com',
            'join.skype.com',
        ];

        $urlHost = parse_url($url, PHP_URL_HOST);
        $isKnownPlatform = false;

        foreach ($knownPlatforms as $platform) {
            if (str_contains($urlHost, $platform)) {
                $isKnownPlatform = true;
                break;
            }
        }

        if (! $isKnownPlatform) {
            // Log for monitoring but don't block - allow custom meeting platforms
            Log::info('Unknown meeting platform used', ['url' => $url, 'host' => $urlHost]);
        }

        return $url;
    }

    /**
     * Extract meeting ID from link
     */
    private function extractMeetingIdFromLink(string $url): ?string
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $urlPath = parse_url($url, PHP_URL_PATH);

        // Google Meet
        if (str_contains($urlHost, 'meet.google.com')) {
            return basename($urlPath);
        }

        // Zoom
        if (str_contains($urlHost, 'zoom.us')) {
            if (preg_match('/\/j\/(\d+)/', $urlPath, $matches)) {
                return $matches[1];
            }
        }

        // Jitsi
        if (str_contains($urlHost, 'meet.jit.si')) {
            return basename($urlPath);
        }

        // Generic fallback - use the last part of the path
        return basename($urlPath) ?: null;
    }
}
