<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentReportRequest;
use App\Http\Requests\UpdateStudentReportRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\InteractiveCourseSession;
use App\Models\InteractiveSessionReport;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentReportController extends Controller
{
    use ApiResponses;

    /**
     * Store a new student report
     */
    public function store(StoreStudentReportRequest $request, $subdomain, $type): JsonResponse
    {
        try {
            // Validate report type
            if (! in_array($type, ['academic', 'quran', 'interactive'])) {
                return $this->error('نوع التقرير غير صحيح', 400);
            }

            $validated = $request->validated();

            DB::beginTransaction();

            // Determine model class based on type
            $modelClass = match ($type) {
                'quran' => StudentSessionReport::class,
                'interactive' => InteractiveSessionReport::class,
                default => AcademicSessionReport::class,
            };

            // Verify teacher has permission to create report for this session
            $session = $this->getSession($type, $validated['session_id']);
            if (! $this->canManageReport($session)) {
                DB::rollBack();

                return $this->forbidden('غير مصرح لك بإنشاء تقرير لهذه الجلسة');
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
            if (! empty($validated['attendance_status'])) {
                $reportData['attendance_status'] = $validated['attendance_status'];
                $reportData['manually_evaluated'] = true;
                $reportData['is_calculated'] = true;
            }

            // Create report
            $report = $modelClass::create($reportData);

            DB::commit();

            return $this->success([
                'success' => true,
                'message' => 'تم إنشاء التقرير بنجاح',
                'report' => $report,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'خطأ في البيانات المدخلة');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating student report: '.$e->getMessage(), [
                'type' => $type,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverError('حدث خطأ أثناء إنشاء التقرير: '.$e->getMessage());
        }
    }

    /**
     * Update an existing student report
     */
    public function update(UpdateStudentReportRequest $request, $subdomain, $type, $reportId): JsonResponse
    {
        try {
            // Validate report type
            if (! in_array($type, ['academic', 'quran', 'interactive'])) {
                return $this->error('نوع التقرير غير صحيح', 400);
            }

            $validated = $request->validated();

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
            if (! $this->canManageReport($report->session)) {
                DB::rollBack();

                return $this->forbidden('غير مصرح لك بتعديل هذا التقرير');
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
            if (! empty($validated['attendance_status'])) {
                $updateData['attendance_status'] = $validated['attendance_status'];
                $updateData['manually_evaluated'] = true;
            } else {
                // If teacher clears the manual override, reset to auto-calculated
                $updateData['manually_evaluated'] = false;
            }

            // Update report
            $report->update($updateData);

            DB::commit();

            return $this->success([
                'success' => true,
                'message' => 'تم تحديث التقرير بنجاح',
                'report' => $report->fresh(),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'خطأ في البيانات المدخلة');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('التقرير غير موجود');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating student report: '.$e->getMessage(), [
                'type' => $type,
                'report_id' => $reportId,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverError('حدث خطأ أثناء تحديث التقرير: '.$e->getMessage());
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
