<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\SessionStatus;

class StudentReportController extends Controller
{
    /**
     * Store a new student report
     */
    public function store(Request $request, $subdomain, $type)
    {
        try {
            // Validate report type
            if (!in_array($type, ['academic', 'quran', 'interactive'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع التقرير غير صحيح',
                ], 400);
            }

            // Validate request data using the AttendanceStatus enum
            $validated = $request->validate([
                'session_id' => 'required|integer',
                'student_id' => 'required|integer',
                'attendance_status' => 'nullable|string|in:' . implode(',', AttendanceStatus::values()),
                'notes' => 'nullable|string',
                // Quran-specific fields
                'new_memorization_degree' => 'nullable|numeric|min:0|max:10',
                'reservation_degree' => 'nullable|numeric|min:0|max:10',
                // Academic & Interactive unified fields (only homework_degree)
                'homework_degree' => 'nullable|numeric|min:0|max:10',
            ]);

            DB::beginTransaction();

            // Determine model class based on type
            $modelClass = match ($type) {
                'quran' => StudentSessionReport::class,
                'interactive' => InteractiveSessionReport::class,
                default => AcademicSessionReport::class,
            };

            // Verify teacher has permission to create report for this session
            $session = $this->getSession($type, $validated['session_id']);
            if (!$this->canManageReport($session)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإنشاء تقرير لهذه الجلسة',
                ], 403);
            }

            // Prepare report data based on session type
            $reportData = [
                'session_id' => $validated['session_id'],
                'teacher_id' => auth()->id(),
                'academy_id' => $type === 'interactive'
                    ? ($session->course?->academy_id ?? auth()->user()->academy_id)
                    : auth()->user()->academy_id,
                'student_id' => $validated['student_id'],
                'notes' => $validated['notes'] ?? null,
            ];

            // Add type-specific fields
            if ($type === 'quran') {
                $reportData['new_memorization_degree'] = $validated['new_memorization_degree'] ?? null;
                $reportData['reservation_degree'] = $validated['reservation_degree'] ?? null;
            } elseif ($type === 'academic') {
                // Academic: simplified to homework_degree only
                $reportData['homework_degree'] = $validated['homework_degree'] ?? null;
            } elseif ($type === 'interactive') {
                // Interactive: unified with Academic (homework_degree only)
                $reportData['homework_degree'] = $validated['homework_degree'] ?? null;
            }

            // Only set attendance_status if provided (otherwise keep auto-calculated)
            if (!empty($validated['attendance_status'])) {
                $reportData['attendance_status'] = $validated['attendance_status'];
                $reportData['manually_evaluated'] = true;
                $reportData['is_calculated'] = true;
            }

            // Create report
            $report = $modelClass::create($reportData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التقرير بنجاح',
                'report' => $report,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating student report: ' . $e->getMessage(), [
                'type' => $type,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التقرير: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing student report
     */
    public function update(Request $request, $subdomain, $type, $reportId)
    {
        try {
            // Validate report type
            if (!in_array($type, ['academic', 'quran', 'interactive'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع التقرير غير صحيح',
                ], 400);
            }

            // Validate request data using the AttendanceStatus enum
            $validated = $request->validate([
                'attendance_status' => 'nullable|string|in:' . implode(',', AttendanceStatus::values()),
                'notes' => 'nullable|string',
                // Quran-specific fields
                'new_memorization_degree' => 'nullable|numeric|min:0|max:10',
                'reservation_degree' => 'nullable|numeric|min:0|max:10',
                // Academic & Interactive unified fields (only homework_degree)
                'homework_degree' => 'nullable|numeric|min:0|max:10',
            ]);

            DB::beginTransaction();

            // Determine model class based on type
            $modelClass = match ($type) {
                'quran' => StudentSessionReport::class,
                'interactive' => InteractiveSessionReport::class,
                default => AcademicSessionReport::class,
            };

            // Find report
            $report = $modelClass::findOrFail($reportId);

            // Check authorization - teacher can only edit their own session's reports
            if (!$this->canManageReport($report->session)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا التقرير',
                ], 403);
            }

            // Prepare update data based on type
            $updateData = [
                'notes' => $validated['notes'] ?? null,
            ];

            // Add type-specific fields
            if ($type === 'quran') {
                $updateData['new_memorization_degree'] = $validated['new_memorization_degree'] ?? $report->new_memorization_degree;
                $updateData['reservation_degree'] = $validated['reservation_degree'] ?? $report->reservation_degree;
            } elseif ($type === 'academic') {
                // Academic: simplified to homework_degree only
                $updateData['homework_degree'] = $validated['homework_degree'] ?? $report->homework_degree;
            } elseif ($type === 'interactive') {
                // Interactive: unified with Academic (homework_degree only)
                $updateData['homework_degree'] = $validated['homework_degree'] ?? $report->homework_degree;
            }

            // Only update attendance_status if provided (otherwise keep auto-calculated)
            if (!empty($validated['attendance_status'])) {
                $updateData['attendance_status'] = $validated['attendance_status'];
                $updateData['manually_evaluated'] = true;
            } else {
                // If teacher clears the manual override, reset to auto-calculated
                $updateData['manually_evaluated'] = false;
            }

            // Update report
            $report->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التقرير بنجاح',
                'report' => $report->fresh(),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'التقرير غير موجود',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating student report: ' . $e->getMessage(), [
                'type' => $type,
                'report_id' => $reportId,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث التقرير: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get session by type and ID
     */
    private function getSession(string $type, int $sessionId)
    {
        if ($type === 'quran') {
            return QuranSession::findOrFail($sessionId);
        } elseif ($type === 'interactive') {
            return InteractiveCourseSession::findOrFail($sessionId);
        } else {
            return AcademicSession::findOrFail($sessionId);
        }
    }

    /**
     * Check if the authenticated teacher can manage reports for this session
     */
    private function canManageReport($session): bool
    {
        $teacher = auth()->user();

        // For InteractiveCourseSession, teacher is on the course
        if ($session instanceof InteractiveCourseSession) {
            return $session->course->assignedTeacher
                && $session->course->assignedTeacher->user_id === $teacher->id;
        }

        // For AcademicSession, teacher is accessed through academicTeacher profile
        if ($session instanceof \App\Models\AcademicSession) {
            return $session->academicTeacher
                && $session->academicTeacher->user_id === $teacher->id;
        }

        // For QuranSession, quranTeacher returns User model directly (not a profile)
        if ($session instanceof \App\Models\QuranSession) {
            return $session->quranTeacher
                && $session->quranTeacher->id === $teacher->id;
        }

        return false;
    }
}
