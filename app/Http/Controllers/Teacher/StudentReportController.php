<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use App\Models\User;
use App\Services\StudentReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentReportController extends Controller
{
    use ApiResponses;

    protected StudentReportService $studentReportService;

    public function __construct(StudentReportService $studentReportService)
    {
        $this->studentReportService = $studentReportService;
    }

    /**
     * Get a student report by ID
     */
    public function show(string $subdomain, string $reportId): JsonResponse
    {
        try {
            $report = StudentSessionReport::with(['student', 'session'])
                ->where('id', $reportId)
                ->whereHas('session', function ($query) {
                    $query->where('quran_teacher_id', Auth::id());
                })
                ->first();

            if (! $report) {
                return $this->notFound('التقرير غير موجود');
            }

            return $this->success([
                'success' => true,
                'report' => [
                    'id' => $report->id,
                    'student' => [
                        'id' => $report->student->id,
                        'name' => $report->student->name,
                    ],
                    'new_memorization_degree' => $report->new_memorization_degree,
                    'reservation_degree' => $report->reservation_degree,
                    'notes' => $report->notes,
                    'attendance_status' => $report->attendance_status,
                    'meeting_enter_time' => $report->meeting_enter_time,
                    'meeting_leave_time' => $report->meeting_leave_time,
                    'actual_attendance_minutes' => $report->actual_attendance_minutes,
                    'attendance_percentage' => $report->attendance_percentage,
                    'is_late' => $report->is_late,
                    'late_minutes' => $report->late_minutes,
                    'manually_evaluated' => $report->manually_evaluated,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching student report', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('خطأ في جلب بيانات التقرير');
        }
    }

    /**
     * Get basic student info
     */
    public function getStudentBasicInfo(string $subdomain, string $studentId): JsonResponse
    {
        try {
            $student = User::find($studentId);

            if (! $student) {
                return $this->notFound('الطالب غير موجود');
            }

            // Verify student belongs to one of this teacher's sessions
            $teacherId = Auth::id();
            $hasAccess = QuranSession::where('quran_teacher_id', $teacherId)
                ->where(function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                        ->orWhereHas('circle.enrollments', function ($q) use ($studentId) {
                            $q->where('student_id', $studentId);
                        });
                })
                ->exists();

            if (! $hasAccess) {
                return $this->forbidden('لا يمكنك الوصول إلى بيانات هذا الطالب');
            }

            return $this->success([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching student info', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('خطأ في جلب بيانات الطالب');
        }
    }

    /**
     * Update student report evaluation
     */
    public function updateEvaluation(Request $request, string $subdomain): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:users,id',
                'session_id' => 'required|exists:quran_sessions,id',
                'new_memorization_degree' => 'nullable|numeric|min:0|max:10',
                'reservation_degree' => 'nullable|numeric|min:0|max:10',
                'notes' => 'nullable|string|max:1000',
                'attendance_status' => 'nullable|in:attended,late,left,absent',
                'report_id' => 'nullable|exists:student_session_reports,id',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray(), 'بيانات غير صحيحة');
            }

            $session = QuranSession::where('id', $request->session_id)
                ->where('quran_teacher_id', Auth::id())
                ->first();

            if (! $session) {
                return $this->forbidden('الجلسة غير موجودة أو غير مسموح لك بالوصول إليها');
            }

            $student = User::find($request->student_id);

            if (! $student) {
                return $this->notFound('الطالب غير موجود');
            }

            // Verify student belongs to this session (individual or circle)
            $isStudentInSession = false;
            if ($session->session_type === 'individual' && $session->student_id === $student->id) {
                $isStudentInSession = true;
            } elseif ($session->session_type === 'circle' && $session->circle) {
                $isStudentInSession = $session->circle->enrollments()
                    ->where('student_id', $student->id)
                    ->exists();
            }

            if (! $isStudentInSession) {
                return $this->forbidden('الطالب غير مسجل في هذه الجلسة');
            }

            // Get or create student report
            if ($request->report_id) {
                $report = StudentSessionReport::find($request->report_id);
                if (! $report) {
                    return $this->notFound('التقرير غير موجود');
                }
            } else {
                // Generate new report if not exists
                $report = $this->studentReportService->generateStudentReport($session, $student);
            }

            // Update teacher evaluation
            if ($request->new_memorization_degree !== null || $request->reservation_degree !== null || $request->notes) {
                $this->studentReportService->updateTeacherEvaluation(
                    $report,
                    $request->new_memorization_degree ?? 0,
                    $request->reservation_degree ?? 0,
                    $request->notes
                );
            }

            // Update attendance status if provided
            if ($request->has('attendance_status')) {
                if ($request->attendance_status) {
                    // Manual status selected - update with chosen value
                    $report->update([
                        'attendance_status' => $request->attendance_status,
                        'manually_evaluated' => true,
                    ]);
                } else {
                    // Empty value selected - use automatic calculation
                    // Sync from MeetingAttendance to get auto-calculated status
                    $report->syncFromMeetingAttendance();
                    $report->update([
                        'manually_evaluated' => false,
                    ]);
                }
            }

            // Refresh the report to get updated values
            $report->refresh();

            return $this->success([
                'success' => true,
                'message' => 'تم حفظ التقييم بنجاح',
                'report' => [
                    'id' => $report->id,
                    'new_memorization_degree' => $report->new_memorization_degree,
                    'reservation_degree' => $report->reservation_degree,
                    'notes' => $report->notes,
                    'attendance_status' => $report->attendance_status,
                    'attendance_percentage' => $report->attendance_percentage,
                    'actual_attendance_minutes' => $report->actual_attendance_minutes,
                    'meeting_enter_time' => $report->meeting_enter_time,
                    'meeting_leave_time' => $report->meeting_leave_time,
                    'is_late' => $report->is_late,
                    'late_minutes' => $report->late_minutes,
                    'manually_evaluated' => $report->manually_evaluated,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating student evaluation', [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('خطأ في حفظ التقييم');
        }
    }

    /**
     * Generate reports for all students in a session
     */
    public function generateSessionReports(Request $request, string $subdomain, string $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', Auth::id())
                ->first();

            if (! $session) {
                return $this->forbidden('الجلسة غير موجودة أو غير مسموح لك بالوصول إليها');
            }

            $reports = $this->studentReportService->generateSessionReports($session);

            return $this->success([
                'success' => true,
                'message' => 'تم إنشاء التقارير بنجاح',
                'reports_count' => $reports->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating session reports', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('خطأ في إنشاء التقارير');
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(string $subdomain, string $sessionId): JsonResponse
    {
        try {
            $session = QuranSession::where('id', $sessionId)
                ->where('quran_teacher_id', Auth::id())
                ->first();

            if (! $session) {
                return $this->notFound('الجلسة غير موجودة');
            }

            $stats = $this->studentReportService->getSessionStats($session);

            return $this->success([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching session stats', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('خطأ في جلب إحصائيات الجلسة');
        }
    }
}
