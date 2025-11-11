<?php

namespace App\Http\Controllers;

use App\Models\InteractiveCourseSession;
use App\Models\CourseRecording;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InteractiveCourseRecordingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Start recording for an interactive course session
     */
    public function startRecording(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:interactive_course_sessions,id',
            'meeting_room' => 'required|string',
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

        // Check if recording already exists for this session
        $existingRecording = CourseRecording::where('session_id', $courseSession->id)
            ->where('status', 'recording')
            ->first();

        if ($existingRecording) {
            return response()->json(['error' => 'التسجيل جاري بالفعل'], 400);
        }

        // Create recording record
        $recording = CourseRecording::create([
            'session_id' => $courseSession->id,
            'course_id' => $courseSession->course_id,
            'teacher_id' => $teacherProfile->id,
            'recording_id' => Str::uuid(),
            'meeting_room' => $validated['meeting_room'],
            'status' => 'recording',
            'started_at' => now(),
            'file_path' => null, // Will be set when recording is processed
        ]);

        // In a real implementation, you would start the actual recording here
        // For now, we'll simulate the recording process
        $this->startLiveKitRecording($recording);

        return response()->json([
            'success' => true,
            'message' => 'تم بدء التسجيل بنجاح',
            'recording_id' => $recording->recording_id,
            'session' => $courseSession->load('course'),
        ]);
    }

    /**
     * Stop recording for an interactive course session
     */
    public function stopRecording(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recording_id' => 'required|string',
            'session_id' => 'required|exists:interactive_course_sessions,id',
        ]);

        $user = Auth::user();
        
        if (!$user->isAcademicTeacher()) {
            return response()->json(['error' => 'غير مسموح لك بإيقاف التسجيل'], 403);
        }

        $recording = CourseRecording::where('recording_id', $validated['recording_id'])
            ->where('session_id', $validated['session_id'])
            ->first();

        if (!$recording) {
            return response()->json(['error' => 'التسجيل غير موجود'], 404);
        }

        // Check permissions
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $recording->teacher_id !== $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بإيقاف هذا التسجيل'], 403);
        }

        // Stop the recording
        $recording->update([
            'status' => 'processing',
            'ended_at' => now(),
            'duration' => now()->diffInSeconds($recording->started_at),
        ]);

        // In a real implementation, you would stop the actual recording here
        $this->stopLiveKitRecording($recording);

        return response()->json([
            'success' => true,
            'message' => 'تم إيقاف التسجيل وسيتم معالجته قريباً',
            'recording' => $recording->fresh(),
        ]);
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

        $recordings = CourseRecording::where('session_id', $sessionId)
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'recordings' => $recordings,
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

        $recording = CourseRecording::where('recording_id', $recordingId)->first();

        if (!$recording) {
            return response()->json(['error' => 'التسجيل غير موجود'], 404);
        }

        // Check permissions
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $recording->teacher_id !== $teacherProfile->id) {
            return response()->json(['error' => 'غير مسموح لك بحذف هذا التسجيل'], 403);
        }

        // Delete the file if it exists
        if ($recording->file_path && Storage::exists($recording->file_path)) {
            Storage::delete($recording->file_path);
        }

        // Delete the recording record
        $recording->delete();

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
        
        if (!$user->isAcademicTeacher()) {
            abort(403, 'غير مسموح لك بالتحميل');
        }

        $recording = CourseRecording::where('recording_id', $recordingId)->first();

        if (!$recording) {
            abort(404, 'التسجيل غير موجود');
        }

        // Check permissions
        $teacherProfile = $user->academicTeacherProfile;
        if (!$teacherProfile || $recording->teacher_id !== $teacherProfile->id) {
            abort(403, 'غير مسموح لك بتحميل هذا التسجيل');
        }

        if (!$recording->file_path || !Storage::exists($recording->file_path)) {
            abort(404, 'ملف التسجيل غير موجود');
        }

        return Storage::download($recording->file_path, $recording->file_name ?? 'recording.mp4');
    }

    /**
     * Start LiveKit recording (placeholder implementation)
     */
    private function startLiveKitRecording(CourseRecording $recording): void
    {
        // TODO: Implement actual LiveKit recording start
        // This would typically involve:
        // 1. Calling LiveKit API to start recording
        // 2. Setting up recording parameters (format, quality, etc.)
        // 3. Handling any errors from the recording service
        
        \Log::info('Starting recording for session: ' . $recording->session_id, [
            'recording_id' => $recording->recording_id,
            'meeting_room' => $recording->meeting_room,
        ]);
    }

    /**
     * Stop LiveKit recording (placeholder implementation)
     */
    private function stopLiveKitRecording(CourseRecording $recording): void
    {
        // TODO: Implement actual LiveKit recording stop
        // This would typically involve:
        // 1. Calling LiveKit API to stop recording
        // 2. Processing the recorded file
        // 3. Saving the file to local storage
        // 4. Updating recording status
        
        \Log::info('Stopping recording for session: ' . $recording->session_id, [
            'recording_id' => $recording->recording_id,
            'duration' => $recording->duration,
        ]);

        // Simulate file processing - in real implementation this would be done by a job
        $fileName = 'course_recording_' . $recording->session_id . '_' . date('Y-m-d_H-i-s') . '.mp4';
        $filePath = 'recordings/interactive-courses/' . $fileName;
        
        // Update recording with file information
        $recording->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => 0, // Would be actual file size
        ]);
    }
}