<?php

namespace App\Http\Controllers;

use App\Models\InteractiveCourseSession;
use App\Models\CourseRecording;
use App\Models\SessionRecording;
use App\Services\RecordingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InteractiveCourseRecordingController extends Controller
{
    protected RecordingService $recordingService;

    public function __construct(RecordingService $recordingService)
    {
        $this->middleware('auth');
        $this->recordingService = $recordingService;
    }

    /**
     * Start recording for an interactive course session
     */
    public function startRecording(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:interactive_course_sessions,id',
        ]);

        $user = Auth::user();

        // Check if user is an academic teacher
        if (!$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالتسجيل'], 403);
        }

        $courseSession = InteractiveCourseSession::findOrFail($validated['session_id']);

        // Check if teacher is assigned to this course
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بتسجيل هذه الدورة'], 403);
        }

        // Use RecordingCapable trait method to check if recording is possible
        if (!$courseSession->canBeRecorded()) {
            return response()->json([
                'error' => 'لا يمكن تسجيل هذه الجلسة حالياً',
                'reasons' => $this->getRecordingBlockReasons($courseSession)
            ], 400);
        }

        try {
            // Use RecordingService to start recording (creates SessionRecording)
            $recording = $this->recordingService->startRecording($courseSession);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء التسجيل بنجاح',
                'recording_id' => $recording->recording_id,
                'recording' => $recording,
                'session' => $courseSession->load('course'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to start recording', [
                'session_id' => $courseSession->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'فشل بدء التسجيل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reasons why recording is blocked
     */
    private function getRecordingBlockReasons(InteractiveCourseSession $session): array
    {
        $reasons = [];

        if (!$session->isRecordingEnabled()) {
            $reasons[] = 'التسجيل غير مفعل لهذه الدورة';
        }

        if (!$session->meeting_room_name) {
            $reasons[] = 'لم يتم إنشاء غرفة الاجتماع بعد';
        }

        if (!in_array($session->status?->value, ['ready', 'ongoing'])) {
            $reasons[] = 'الجلسة غير نشطة (الحالة: ' . ($session->status?->value ?? 'غير محددة') . ')';
        }

        if ($session->isRecording()) {
            $reasons[] = 'التسجيل جاري بالفعل';
        }

        return $reasons;
    }

    /**
     * Stop recording for an interactive course session
     */
    public function stopRecording(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:interactive_course_sessions,id',
        ]);

        $user = Auth::user();

        if (!$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بإيقاف التسجيل'], 403);
        }

        $courseSession = InteractiveCourseSession::findOrFail($validated['session_id']);

        // Check if teacher is assigned to this course
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بإيقاف هذا التسجيل'], 403);
        }

        // Get active recording
        $activeRecording = $courseSession->getActiveRecording();

        if (!$activeRecording) {
            return response()->json(['error' => 'لا يوجد تسجيل نشط لهذه الجلسة'], 404);
        }

        try {
            // Use RecordingService to stop recording
            $success = $this->recordingService->stopRecording($activeRecording);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إيقاف التسجيل وسيتم معالجته قريباً',
                    'recording' => $activeRecording->fresh(),
                ]);
            } else {
                return response()->json([
                    'error' => 'فشل إيقاف التسجيل'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to stop recording', [
                'session_id' => $courseSession->id,
                'recording_id' => $activeRecording->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'فشل إيقاف التسجيل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recordings for a course session
     */
    public function getSessionRecordings(Request $request, $sessionId): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالوصول'], 403);
        }

        $courseSession = InteractiveCourseSession::findOrFail($sessionId);

        // Check permissions
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بالوصول لهذه التسجيلات'], 403);
        }

        // Use HasRecording trait method to get recordings (returns SessionRecording collection)
        $recordings = $courseSession->getRecordings();

        return response()->json([
            'success' => true,
            'recordings' => $recordings,
            'recording_stats' => $courseSession->getRecordingStats(),
            'session' => $courseSession->load('course'),
        ]);
    }

    /**
     * Delete a recording
     */
    public function deleteRecording(Request $request, $recordingId): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بالحذف'], 403);
        }

        // Find SessionRecording by ID (not recording_id)
        $recording = SessionRecording::find($recordingId);

        if (!$recording) {
            return response()->json(['error' => 'التسجيل غير موجود'], 404);
        }

        // Get the session and check permissions
        $courseSession = $recording->recordable;

        if (!$courseSession instanceof InteractiveCourseSession) {
            return response()->json(['error' => 'نوع التسجيل غير صحيح'], 400);
        }

        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $courseSession->course->assigned_teacher_id !== $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بحذف هذا التسجيل'], 403);
        }

        // Delete the file if it exists
        if ($recording->file_path && Storage::exists($recording->file_path)) {
            Storage::delete($recording->file_path);
        }

        // Mark as deleted (soft delete approach) instead of hard deleting
        $recording->markAsDeleted();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التسجيل بنجاح',
        ]);
    }

    /**
     * Download a recording
     */
    public function downloadRecording(Request $request, $recordingId)
    {
        $user = Auth::user();

        // Find SessionRecording
        $recording = SessionRecording::find($recordingId);

        if (!$recording) {
            abort(404, 'التسجيل غير موجود');
        }

        // Get the session
        $courseSession = $recording->recordable;

        if (!$courseSession instanceof InteractiveCourseSession) {
            abort(400, 'نوع التسجيل غير صحيح');
        }

        // Check permissions using RecordingCapable method
        if (!$courseSession->canUserAccessRecordings($user)) {
            abort(403, 'غير مسموح لك بتحميل هذا التسجيل');
        }

        // Check recording is available
        if (!$recording->isAvailable()) {
            abort(404, 'ملف التسجيل غير متوفر بعد');
        }

        // Handle remote recordings (stored on LiveKit server)
        if ($recording->isRemoteFile()) {
            $remoteUrl = $recording->getRemoteUrl();

            if (!$remoteUrl) {
                abort(404, 'رابط التسجيل غير متوفر');
            }

            // Redirect to remote URL with download disposition
            return redirect()->away($remoteUrl . '?download=1');
        }

        // Handle local recordings (legacy/fallback)
        if (!Storage::exists($recording->file_path)) {
            abort(404, 'ملف التسجيل غير موجود');
        }

        return Storage::download($recording->file_path, $recording->file_name ?? 'recording.mp4');
    }

    /**
     * Stream a recording (for in-browser playback)
     */
    public function streamRecording(Request $request, $recordingId)
    {
        $user = Auth::user();

        // Find SessionRecording
        $recording = SessionRecording::find($recordingId);

        if (!$recording) {
            abort(404, 'التسجيل غير موجود');
        }

        // Get the session
        $courseSession = $recording->recordable;

        if (!$courseSession instanceof InteractiveCourseSession) {
            abort(400, 'نوع التسجيل غير صحيح');
        }

        // Check permissions
        if (!$courseSession->canUserAccessRecordings($user)) {
            abort(403, 'غير مسموح لك بمشاهدة هذا التسجيل');
        }

        // Check recording is available
        if (!$recording->isAvailable()) {
            abort(404, 'ملف التسجيل غير متوفر بعد');
        }

        // Handle remote recordings (stored on LiveKit server)
        if ($recording->isRemoteFile()) {
            $remoteUrl = $recording->getRemoteUrl();

            if (!$remoteUrl) {
                abort(404, 'رابط التسجيل غير متوفر');
            }

            $accessMode = config('livekit.recordings.access_mode', 'redirect');

            if ($accessMode === 'redirect') {
                // Redirect directly to the remote file (faster, uses LiveKit server bandwidth)
                return redirect()->away($remoteUrl);
            }

            // Proxy mode: fetch from remote and stream through Laravel
            // This provides more control but uses Laravel server bandwidth
            try {
                $response = \Http::withOptions(['stream' => true])->get($remoteUrl);

                if (!$response->successful()) {
                    abort(404, 'فشل في تحميل ملف التسجيل من الخادم');
                }

                return response()->stream(function () use ($response) {
                    echo $response->body();
                }, 200, [
                    'Content-Type' => 'video/mp4',
                    'Content-Disposition' => 'inline; filename="' . ($recording->file_name ?? 'recording.mp4') . '"',
                    'Accept-Ranges' => 'bytes',
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to proxy remote recording', [
                    'recording_id' => $recording->id,
                    'remote_url' => $remoteUrl,
                    'error' => $e->getMessage(),
                ]);
                abort(500, 'فشل في تحميل ملف التسجيل');
            }
        }

        // Handle local recordings (legacy/fallback)
        if (!Storage::exists($recording->file_path)) {
            abort(404, 'ملف التسجيل غير موجود');
        }

        // Stream the file with proper headers for video playback
        return Storage::response($recording->file_path, $recording->file_name ?? 'recording.mp4', [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ]);
    }
}