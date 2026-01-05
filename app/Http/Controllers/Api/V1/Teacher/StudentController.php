<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\StudentProfile;
use App\Models\StudentSessionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Enums\SessionStatus;

class StudentController extends Controller
{
    use ApiResponses;

    /**
     * Get all students for the teacher.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $students = collect();

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if ($quranTeacherId) {
                // Get students from Quran sessions
                $quranStudentIds = QuranSession::where('quran_teacher_id', $quranTeacherId)
                    ->distinct()
                    ->pluck('student_id');

                $quranStudents = StudentProfile::whereIn('user_id', $quranStudentIds)
                    ->orWhereIn('id', $quranStudentIds)
                    ->with(['user', 'gradeLevel'])
                    ->get()
                    ->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'user_id' => $student->user?->id,
                            'name' => $student->user?->name ?? $student->full_name,
                            'avatar' => $student->user?->avatar
                                ? asset('storage/' . $student->user->avatar)
                                : null,
                            'grade_level' => $student->gradeLevel?->name,
                            'phone' => $student->phone ?? $student->user?->phone,
                            'type' => 'quran',
                        ];
                    });

                $students = $students->merge($quranStudents);
            }
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                // Get students from Academic sessions
                $academicStudentIds = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->distinct()
                    ->pluck('student_id');

                $academicStudents = StudentProfile::whereIn('user_id', $academicStudentIds)
                    ->orWhereIn('id', $academicStudentIds)
                    ->with(['user', 'gradeLevel'])
                    ->get()
                    ->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'user_id' => $student->user?->id,
                            'name' => $student->user?->name ?? $student->full_name,
                            'avatar' => $student->user?->avatar
                                ? asset('storage/' . $student->user->avatar)
                                : null,
                            'grade_level' => $student->gradeLevel?->name,
                            'phone' => $student->phone ?? $student->user?->phone,
                            'type' => 'academic',
                        ];
                    });

                $students = $students->merge($academicStudents);
            }
        }

        // Remove duplicates
        $students = $students->unique('id');

        // Search
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $students = $students->filter(function ($student) use ($search) {
                return str_contains(strtolower($student['name'] ?? ''), $search);
            });
        }

        // Paginate
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $total = $students->count();
        $students = $students->slice(($page - 1) * $perPage, $perPage)->values();

        return $this->success([
            'students' => $students->toArray(),
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ], __('Students retrieved successfully'));
    }

    /**
     * Get student detail.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $student = StudentProfile::where('id', $id)
            ->orWhere('user_id', $id)
            ->with(['user', 'gradeLevel'])
            ->first();

        if (!$student) {
            return $this->notFound(__('Student not found.'));
        }

        // Verify teacher has access to this student
        $hasAccess = false;
        $studentUserId = $student->user?->id ?? $student->id;

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;
            $hasAccess = QuranSession::where('quran_teacher_id', $quranTeacherId)
                ->where('student_id', $studentUserId)
                ->exists();
        }

        if (!$hasAccess && $user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;
            $hasAccess = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                ->where('student_id', $studentUserId)
                ->exists();
        }

        if (!$hasAccess) {
            return $this->error(__('You do not have access to this student.'), 403, 'FORBIDDEN');
        }

        // Get session stats
        $quranStats = null;
        $academicStats = null;

        if ($user->isQuranTeacher()) {
            $quranTeacherId = $user->quranTeacherProfile?->id;
            $quranSessions = QuranSession::where('quran_teacher_id', $quranTeacherId)
                ->where('student_id', $studentUserId)
                ->get();

            // Get evaluation averages from StudentSessionReport
            $sessionIds = $quranSessions->pluck('id');
            $reports = StudentSessionReport::whereIn('session_id', $sessionIds)->get();

            $quranStats = [
                'total_sessions' => $quranSessions->count(),
                'completed_sessions' => $quranSessions->where('status', SessionStatus::COMPLETED->value)->count(),
                'average_memorization_degree' => round($reports->avg('new_memorization_degree') ?? 0, 1),
                'average_revision_degree' => round($reports->avg('reservation_degree') ?? 0, 1),
            ];
        }

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;
            $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                ->where('student_id', $studentUserId)
                ->get();

            $academicStats = [
                'total_sessions' => $academicSessions->count(),
                'completed_sessions' => $academicSessions->where('status', SessionStatus::COMPLETED->value)->count(),
            ];
        }

        return $this->success([
            'student' => [
                'id' => $student->id,
                'user_id' => $student->user?->id,
                'name' => $student->user?->name ?? $student->full_name,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'email' => $student->email ?? $student->user?->email,
                'phone' => $student->phone ?? $student->user?->phone,
                'avatar' => $student->user?->avatar
                    ? asset('storage/' . $student->user->avatar)
                    : ($student->avatar ? asset('storage/' . $student->avatar) : null),
                'grade_level' => $student->gradeLevel ? [
                    'id' => $student->gradeLevel->id,
                    'name' => $student->gradeLevel->getDisplayName(),
                ] : null,
                'birth_date' => $student->birth_date?->toDateString(),
                'age' => $student->birth_date?->age,
                'gender' => $student->gender,
            ],
            'quran_stats' => $quranStats,
            'academic_stats' => $academicStats,
        ], __('Student retrieved successfully'));
    }

    /**
     * Create a report for a student.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function createReport(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $student = StudentProfile::where('id', $id)
            ->orWhere('user_id', $id)
            ->first();

        if (!$student) {
            return $this->notFound(__('Student not found.'));
        }

        $validator = Validator::make($request->all(), [
            'session_type' => ['required', 'in:quran,academic'],
            'session_id' => ['required', 'integer'],
            // Quran uses 0-10 scale, Academic uses 1-5 scale
            'memorization_degree' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10'],
            'revision_degree' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10'],
            'rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'], // For Academic
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $studentUserId = $student->user?->id ?? $student->id;

        if ($request->session_type === 'quran') {
            $quranTeacherId = $user->quranTeacherProfile?->id;

            if (!$quranTeacherId) {
                return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
            }

            $session = QuranSession::where('id', $request->session_id)
                ->where('quran_teacher_id', $quranTeacherId)
                ->where('student_id', $studentUserId)
                ->first();

            if (!$session) {
                return $this->notFound(__('Session not found.'));
            }

            // Create or update StudentSessionReport for Quran evaluations
            StudentSessionReport::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'student_id' => $studentUserId,
                ],
                [
                    'teacher_id' => $user->id,
                    'academy_id' => $session->academy_id,
                    'new_memorization_degree' => $request->memorization_degree,
                    'reservation_degree' => $request->revision_degree,
                    'notes' => $request->notes ?? $request->feedback,
                    'evaluated_at' => now(),
                    'manually_evaluated' => true,
                ]
            );
        } else {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if (!$academicTeacherId) {
                return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
            }

            $session = AcademicSession::where('id', $request->session_id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->where('student_id', $studentUserId)
                ->first();

            if (!$session) {
                return $this->notFound(__('Session not found.'));
            }

            // Create or update report
            \App\Models\AcademicSessionReport::updateOrCreate(
                ['academic_session_id' => $session->id],
                [
                    'rating' => $request->rating,
                    'notes' => $request->notes,
                    'teacher_feedback' => $request->feedback,
                ]
            );
        }

        return $this->created([
            'message' => __('Report created successfully'),
        ], __('Report created successfully'));
    }
}
