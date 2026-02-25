<?php

namespace App\Http\Controllers;

use Exception;
use Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Http;
use App\Enums\SessionStatus;
use App\Http\Requests\StartRecordingRequest;
use App\Http\Requests\StopRecordingRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use App\Services\RecordingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InteractiveCourseRecordingController extends Controller
{
    use ApiResponses;

    protected RecordingService $recordingService;

    public function __construct(RecordingService $recordingService)
    {
        $this->middleware('auth');
        $this->recordingService = $recordingService;
    }

    /**
     * Start recording for an interactive course session
     */
    public function startRecording(StartRecordingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();

        // Check if user is an academic teacher
        if (! $user->isAcademicTeacher()) {
            return $this->forbidden('غير مسموح لك بالتسجيل');
        }

        $courseSession = InteractiveCourseSession::findOrFail($validated['session_id']);

        // Check if teacher is assigned to this course
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return $this->forbidden('غير مسموح لك بتسجيل هذه الدورة');
        }

        // Use RecordingCapable trait method to check if recording is possible
        if (! $courseSession->canBeRecorded()) {
            return $this->error('لا يمكن تسجيل هذه الجلسة حالياً', 400, [
                'reasons' => $this->getRecordingBlockReasons($courseSession),
            ]);
        }

        try {
            // Use RecordingService to start recording (creates SessionRecording)
            $recording = $this->recordingService->startRecording($courseSession);

            return $this->success([
                'recording_id' => $recording->recording_id,
                'recording' => $recording,
                'session' => $courseSession->load('course'),
            ], 'تم بدء التسجيل بنجاح');

        } catch (Exception $e) {
            Log::error('Failed to start recording', [
                'session_id' => $courseSession->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverError('فشل بدء التسجيل: '.$e->getMessage());
        }
    }

    /**
     * Get reasons why recording is blocked
     */
    private function getRecordingBlockReasons(InteractiveCourseSession $session): array
    {
        $reasons = [];

        if (! $session->isRecordingEnabled()) {
            $reasons[] = 'التسجيل غير مفعل لهذه الدورة';
        }

        if (! $session->meeting_room_name) {
            $reasons[] = 'لم يتم إنشاء غرفة الاجتماع بعد';
        }

        if (! in_array($session->status?->value, [SessionStatus::READY->value, SessionStatus::ONGOING->value])) {
            $reasons[] = 'الجلسة غير نشطة (الحالة: '.($session->status?->value ?? 'غير محددة').')';
        }

        if ($session->isRecording()) {
            $reasons[] = 'التسجيل جاري بالفعل';
        }

        return $reasons;
    }

    /**
     * Stop recording for an interactive course session
     */
    public function stopRecording(StopRecordingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();

        if (! $user->isAcademicTeacher()) {
            return $this->forbidden('غير مسموح لك بإيقاف التسجيل');
        }

        $courseSession = InteractiveCourseSession::findOrFail($validated['session_id']);

        // Check if teacher is assigned to this course
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return $this->forbidden('غير مسموح لك بإيقاف هذا التسجيل');
        }

        // Get active recording
        $activeRecording = $courseSession->getActiveRecording();

        if (! $activeRecording) {
            return $this->notFound('لا يوجد تسجيل نشط لهذه الجلسة');
        }

        try {
            // Use RecordingService to stop recording
            $success = $this->recordingService->stopRecording($activeRecording);

            if ($success) {
                return $this->success([
                    'recording' => $activeRecording->fresh(),
                ], 'تم إيقاف التسجيل وسيتم معالجته قريباً');
            } else {
                return $this->serverError('فشل إيقاف التسجيل');
            }

        } catch (Exception $e) {
            Log::error('Failed to stop recording', [
                'session_id' => $courseSession->id,
                'recording_id' => $activeRecording->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('فشل إيقاف التسجيل: '.$e->getMessage());
        }
    }

    /**
     * Get recordings for a course session
     */
    public function getSessionRecordings(Request $request, $sessionId): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher()) {
            return $this->forbidden('غير مسموح لك بالوصول');
        }

        $courseSession = InteractiveCourseSession::findOrFail($sessionId);

        // Check permissions
        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return $this->forbidden('غير مسموح لك بالوصول لهذه التسجيلات');
        }

        // Use HasRecording trait method to get recordings (returns SessionRecording collection)
        $recordings = $courseSession->getRecordings();

        return $this->success([
            'recordings' => $recordings,
            'recording_stats' => $courseSession->getRecordingStats(),
            'session' => $courseSession->load('course'),
        ]);
    }

    /**
     * Delete a recording
     *
     * Uses RecordingService to handle deletion. Storage file cleanup is handled
     * automatically by SessionRecordingObserver when status changes to 'deleted'.
     */
    public function deleteRecording(Request $request, $recordingId): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isAcademicTeacher()) {
            return $this->forbidden('غير مسموح لك بالحذف');
        }

        // Find SessionRecording by ID (not recording_id)
        $recording = SessionRecording::find($recordingId);

        if (! $recording) {
            return $this->notFound('التسجيل غير موجود');
        }

        // Get the session and check permissions
        $courseSession = $recording->recordable;

        if (! $courseSession instanceof InteractiveCourseSession) {
            return $this->error('نوع التسجيل غير صحيح', 400);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (! $teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return $this->forbidden('غير مسموح لك بحذف هذا التسجيل');
        }

        // Use RecordingService for deletion - file cleanup handled by SessionRecordingObserver
        $success = $this->recordingService->deleteRecording($recording);

        if (! $success) {
            return $this->serverError('فشل حذف التسجيل');
        }

        return $this->success(null, 'تم حذف التسجيل بنجاح');
    }

    /**
     * Download a recording
     */
    public function downloadRecording(Request $request, $recordingId): BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();

        // Find SessionRecording
        $recording = SessionRecording::find($recordingId);

        if (! $recording) {
            abort(404, __('errors.recording_not_found'));
        }

        // Get the session
        $courseSession = $recording->recordable;

        if (! $courseSession instanceof InteractiveCourseSession) {
            abort(400, __('errors.recording_type_invalid'));
        }

        // Authorize downloading the recording
        $this->authorize('download', $recording);

        // Check recording is available
        if (! $recording->isAvailable()) {
            abort(404, __('errors.recording_file_not_available'));
        }

        // Handle remote recordings (stored on LiveKit server)
        if ($recording->isRemoteFile()) {
            $remoteUrl = $recording->getRemoteUrl();

            if (! $remoteUrl) {
                abort(404, __('errors.recording_url_not_available'));
            }

            // Redirect to remote URL with download disposition
            return redirect()->away($remoteUrl.'?download=1');
        }

        // Handle local recordings (legacy/fallback)
        if (! Storage::exists($recording->file_path)) {
            abort(404, __('errors.recording_file_not_found'));
        }

        return Storage::download($recording->file_path, $recording->file_name ?? 'recording.mp4');
    }

    /**
     * Stream a recording (for in-browser playback)
     */
    public function streamRecording(Request $request, $recordingId): StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();

        // Find SessionRecording
        $recording = SessionRecording::find($recordingId);

        if (! $recording) {
            abort(404, __('errors.recording_not_found'));
        }

        // Get the session
        $courseSession = $recording->recordable;

        if (! $courseSession instanceof InteractiveCourseSession) {
            abort(400, __('errors.recording_type_invalid'));
        }

        // Authorize viewing the recording
        $this->authorize('view', $recording);

        // Check recording is available
        if (! $recording->isAvailable()) {
            abort(404, __('errors.recording_file_not_available'));
        }

        // Handle remote recordings (stored on LiveKit server)
        if ($recording->isRemoteFile()) {
            $remoteUrl = $recording->getRemoteUrl();

            if (! $remoteUrl) {
                abort(404, __('errors.recording_url_not_available'));
            }

            $accessMode = config('livekit.recordings.access_mode', 'redirect');

            if ($accessMode === 'redirect') {
                // Redirect directly to the remote file (faster, uses LiveKit server bandwidth)
                return redirect()->away($remoteUrl);
            }

            // Proxy mode: fetch from remote and stream through Laravel
            // This provides more control but uses Laravel server bandwidth
            try {
                $response = Http::withOptions(['stream' => true])->get($remoteUrl);

                if (! $response->successful()) {
                    abort(404, __('errors.recording_download_failed_server'));
                }

                return response()->stream(function () use ($response) {
                    echo $response->body();
                }, 200, [
                    'Content-Type' => 'video/mp4',
                    'Content-Disposition' => 'inline; filename="'.($recording->file_name ?? 'recording.mp4').'"',
                    'Accept-Ranges' => 'bytes',
                ]);
            } catch (Exception $e) {
                Log::error('Failed to proxy remote recording', [
                    'recording_id' => $recording->id,
                    'remote_url' => $remoteUrl,
                    'error' => $e->getMessage(),
                ]);
                abort(500, __('errors.recording_stream_failed'));
            }
        }

        // Handle local recordings (legacy/fallback)
        if (! Storage::exists($recording->file_path)) {
            abort(404, __('errors.recording_file_not_found'));
        }

        // Stream the file with proper headers for video playback
        return Storage::response($recording->file_path, $recording->file_name ?? 'recording.mp4', [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
