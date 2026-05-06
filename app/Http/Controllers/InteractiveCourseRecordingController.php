<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Http\Requests\StartRecordingRequest;
use App\Http\Requests\StopRecordingRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\InteractiveCourseSession;
use App\Models\SessionRecording;
use App\Services\RecordingService;
use Exception;
use Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function downloadRecording(Request $request, $recordingId): StreamedResponse|BinaryFileResponse|RedirectResponse
    {
        $user = Auth::user();

        // Find SessionRecording
        $recording = SessionRecording::find($recordingId);

        if (! $recording) {
            abort(404, __('errors.recording_not_found'));
        }

        // Get the session
        $courseSession = $recording->recordable;

        if (! $courseSession instanceof \App\Contracts\RecordingCapable) {
            abort(400, __('errors.recording_type_invalid'));
        }

        // Authorize downloading the recording
        $this->authorize('download', $recording);

        // Check recording is available
        if (! $recording->isAvailable()) {
            abort(404, __('errors.recording_file_not_available'));
        }

        $extension = $recording->file_format ?? 'm4a';
        $filename = $recording->file_name ?? "recording-{$recording->id}.{$extension}";

        // Handle remote recordings (stored on LiveKit server)
        // Stream through Laravel with Content-Disposition: attachment so the browser saves
        // the file instead of opening it inline. The cross-origin redirect approach with
        // ?download=1 doesn't work because nginx on the LiveKit VPS doesn't translate the
        // query string into an attachment header.
        if ($recording->isRemoteFile()) {
            $remoteUrl = $recording->getRemoteUrl();

            if (! $remoteUrl) {
                abort(404, __('errors.recording_url_not_available'));
            }

            return response()->streamDownload(function () use ($remoteUrl, $recording) {
                if (ini_get('allow_url_fopen')) {
                    $stream = @fopen($remoteUrl, 'rb');
                    if ($stream !== false) {
                        while (! feof($stream)) {
                            echo fread($stream, 8192);
                            flush();
                        }
                        fclose($stream);

                        return;
                    }
                }

                try {
                    $body = Http::withOptions(['stream' => true, 'timeout' => 0])
                        ->get($remoteUrl)
                        ->toPsrResponse()
                        ->getBody();
                    while (! $body->eof()) {
                        echo $body->read(8192);
                        flush();
                    }
                } catch (Exception $e) {
                    Log::error('Failed to stream remote recording download', [
                        'recording_id' => $recording->id,
                        'remote_url' => $remoteUrl,
                        'error' => $e->getMessage(),
                    ]);
                }
            }, $filename, [
                'Content-Type' => 'application/octet-stream',
            ]);
        }

        // Handle local recordings (legacy/fallback)
        if (! Storage::exists($recording->file_path)) {
            abort(404, __('errors.recording_file_not_found'));
        }

        return Storage::download($recording->file_path, $filename);
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

        if (! $courseSession instanceof \App\Contracts\RecordingCapable) {
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
            // ?inline=1 forces same-origin proxy. Used by the audio player's waveform decoder
            // (Web Audio decodeAudioData), which fails when fetch() follows a cross-origin
            // redirect to a server that doesn't send CORS headers.
            $forceInline = $request->boolean('inline');

            if ($accessMode === 'redirect' && ! $forceInline) {
                return redirect()->away($remoteUrl);
            }

            $contentType = match ($recording->file_format) {
                'ogg' => 'audio/ogg',
                'm4a' => 'audio/mp4',
                default => 'video/mp4',
            };

            return response()->stream(function () use ($remoteUrl, $request, $recording) {
                $clientHeaders = [];
                if ($range = $request->header('Range')) {
                    $clientHeaders['Range'] = $range;
                }
                try {
                    if (ini_get('allow_url_fopen')) {
                        $context = stream_context_create([
                            'http' => [
                                'method' => 'GET',
                                'header' => collect($clientHeaders)
                                    ->map(fn ($v, $k) => "{$k}: {$v}")
                                    ->implode("\r\n"),
                            ],
                        ]);
                        $stream = @fopen($remoteUrl, 'rb', false, $context);
                        if ($stream !== false) {
                            while (! feof($stream) && ! connection_aborted()) {
                                echo fread($stream, 8192);
                                flush();
                            }
                            fclose($stream);

                            return;
                        }
                    }
                    $body = Http::withHeaders($clientHeaders)
                        ->withOptions(['stream' => true, 'timeout' => 0])
                        ->get($remoteUrl)
                        ->toPsrResponse()
                        ->getBody();
                    while (! $body->eof() && ! connection_aborted()) {
                        echo $body->read(8192);
                        flush();
                    }
                } catch (Exception $e) {
                    Log::error('Failed to proxy remote recording', [
                        'recording_id' => $recording->id,
                        'remote_url' => $remoteUrl,
                        'error' => $e->getMessage(),
                    ]);
                }
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="'.($recording->file_name ?? 'recording.mp4').'"',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        // Handle local recordings (legacy/fallback)
        if (! Storage::exists($recording->file_path)) {
            abort(404, __('errors.recording_file_not_found'));
        }

        $contentType = $recording->file_format === 'ogg' ? 'audio/ogg' : 'video/mp4';

        return Storage::response($recording->file_path, $recording->file_name ?? 'recording.mp4', [
            'Content-Type' => $contentType,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * Return the pre-computed peaks JSON (~10 KB) for a recording's waveform.
     * Generated by /root/faststart-recordings.sh on the LiveKit VPS, served
     * here as a small same-origin JSON so the audio player can paint the
     * waveform without fetching+decoding the whole audio file client-side.
     */
    public function peaksRecording(Request $request, $recordingId): \Illuminate\Http\Response|RedirectResponse
    {
        $recording = SessionRecording::find($recordingId);

        if (! $recording) {
            abort(404, __('errors.recording_not_found'));
        }

        $courseSession = $recording->recordable;
        if (! $courseSession instanceof \App\Contracts\RecordingCapable) {
            abort(400, __('errors.recording_type_invalid'));
        }

        $this->authorize('view', $recording);

        if (! $recording->isAvailable()) {
            abort(404, __('errors.recording_file_not_available'));
        }

        if ($recording->isRemoteFile()) {
            $remoteUrl = $recording->getRemoteUrl();
            if (! $remoteUrl) {
                abort(404, __('errors.recording_url_not_available'));
            }
            // The peaks file lives next to the recording with the audio extension
            // (.mp4/.m4a/.ogg) replaced by .peaks.json
            $peaksUrl = preg_replace('/\.(mp4|m4a|ogg)$/i', '.peaks.json', $remoteUrl);
            try {
                $response = Http::timeout(5)->get($peaksUrl);
                if ($response->successful()) {
                    return response($response->body(), 200, [
                        'Content-Type' => 'application/json',
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch peaks JSON', [
                    'recording_id' => $recording->id,
                    'peaks_url' => $peaksUrl,
                    'error' => $e->getMessage(),
                ]);
            }
            abort(404, 'Peaks not yet generated');
        }

        abort(404, 'Peaks not available for local recordings');
    }
}
