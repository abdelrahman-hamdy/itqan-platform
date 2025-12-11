<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\HomeworkSubmission;
use App\Models\InteractiveCourseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeworkController extends Controller
{
    use ApiResponses;

    /**
     * Get homework list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $homework = [];

        if ($user->isAcademicTeacher()) {
            $academicTeacherId = $user->academicTeacherProfile?->id;

            if ($academicTeacherId) {
                // Academic sessions with homework
                $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                    ->whereNotNull('homework')
                    ->where('homework', '!=', '')
                    ->with(['student.user', 'academicSubscription', 'submissions'])
                    ->orderBy('scheduled_at', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($academicSessions as $session) {
                    $homework[] = [
                        'id' => $session->id,
                        'type' => 'academic',
                        'title' => $session->academicSubscription?->subject_name ?? 'واجب أكاديمي',
                        'description' => $session->homework,
                        'student_name' => $session->student?->user?->name ?? 'طالب',
                        'session_date' => $session->scheduled_at?->toDateString(),
                        'due_date' => $session->homework_due_date?->toDateString(),
                        'submissions_count' => $session->submissions?->count() ?? 0,
                        'pending_submissions' => $session->submissions?->where('status', 'submitted')->count() ?? 0,
                        'created_at' => $session->created_at->toISOString(),
                    ];
                }

                // Interactive course sessions with homework
                $courseIds = $user->academicTeacherProfile->assignedCourses()
                    ->pluck('id');

                $interactiveSessions = InteractiveCourseSession::whereIn('course_id', $courseIds)
                    ->whereNotNull('homework')
                    ->where('homework', '!=', '')
                    ->with(['course', 'submissions'])
                    ->orderBy('scheduled_date', 'desc')
                    ->limit(50)
                    ->get();

                foreach ($interactiveSessions as $session) {
                    $homework[] = [
                        'id' => $session->id,
                        'type' => 'interactive',
                        'title' => $session->title ?? $session->course?->title ?? 'واجب دورة',
                        'description' => $session->homework,
                        'course_name' => $session->course?->title,
                        'session_number' => $session->session_number,
                        'session_date' => $session->scheduled_date?->toDateString(),
                        'due_date' => $session->homework_due_date?->toDateString(),
                        'submissions_count' => $session->submissions?->count() ?? 0,
                        'pending_submissions' => $session->submissions?->where('status', 'submitted')->count() ?? 0,
                        'created_at' => $session->created_at->toISOString(),
                    ];
                }
            }
        }

        // Sort by date
        usort($homework, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        // Paginate
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($homework);
        $homework = array_slice($homework, ($page - 1) * $perPage, $perPage);

        return $this->success([
            'homework' => array_values($homework),
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Get homework detail.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->with(['student.user', 'academicSubscription', 'submissions.user'])
                ->first();

            if (!$session) {
                return $this->notFound(__('Session not found.'));
            }

            return $this->success([
                'homework' => [
                    'id' => $session->id,
                    'type' => 'academic',
                    'title' => $session->academicSubscription?->subject_name ?? 'واجب أكاديمي',
                    'description' => $session->homework,
                    'student' => $session->student?->user ? [
                        'id' => $session->student->user->id,
                        'name' => $session->student->user->name,
                    ] : null,
                    'session_date' => $session->scheduled_at?->toDateString(),
                    'due_date' => $session->homework_due_date?->toDateString(),
                    'submissions' => $session->submissions->map(fn($sub) => [
                        'id' => $sub->id,
                        'student_name' => $sub->user?->name,
                        'status' => $sub->status,
                        'content' => $sub->content,
                        'file_path' => $sub->file_path ? asset('storage/' . $sub->file_path) : null,
                        'grade' => $sub->grade,
                        'feedback' => $sub->feedback,
                        'submitted_at' => $sub->submitted_at?->toISOString(),
                        'graded_at' => $sub->graded_at?->toISOString(),
                    ])->toArray(),
                ],
            ], __('Homework retrieved successfully'));
        }

        // Interactive
        $courseIds = $user->academicTeacherProfile->assignedCourses()
            ->pluck('id');

        $session = InteractiveCourseSession::where('id', $id)
            ->whereIn('course_id', $courseIds)
            ->with(['course', 'submissions.user'])
            ->first();

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        return $this->success([
            'homework' => [
                'id' => $session->id,
                'type' => 'interactive',
                'title' => $session->title ?? $session->course?->title ?? 'واجب دورة',
                'description' => $session->homework,
                'course' => $session->course ? [
                    'id' => $session->course->id,
                    'title' => $session->course->title,
                ] : null,
                'session_number' => $session->session_number,
                'session_date' => $session->scheduled_date?->toDateString(),
                'due_date' => $session->homework_due_date?->toDateString(),
                'submissions' => $session->submissions->map(fn($sub) => [
                    'id' => $sub->id,
                    'student_name' => $sub->user?->name,
                    'status' => $sub->status,
                    'content' => $sub->content,
                    'file_path' => $sub->file_path ? asset('storage/' . $sub->file_path) : null,
                    'grade' => $sub->grade,
                    'feedback' => $sub->feedback,
                    'submitted_at' => $sub->submitted_at?->toISOString(),
                    'graded_at' => $sub->graded_at?->toISOString(),
                ])->toArray(),
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Assign homework.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assign(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'session_type' => ['required', 'in:academic,interactive'],
            'session_id' => ['required', 'integer'],
            'homework' => ['required', 'string', 'max:5000'],
            'due_date' => ['sometimes', 'date', 'after:today'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($request->session_type === 'academic') {
            $session = AcademicSession::where('id', $request->session_id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();

            if (!$session) {
                return $this->notFound(__('Session not found.'));
            }

            $session->update([
                'homework' => $request->homework,
                'homework_due_date' => $request->due_date,
            ]);
        } else {
            $courseIds = $user->academicTeacherProfile->assignedCourses()
                ->pluck('id');

            $session = InteractiveCourseSession::where('id', $request->session_id)
                ->whereIn('course_id', $courseIds)
                ->first();

            if (!$session) {
                return $this->notFound(__('Session not found.'));
            }

            $session->update([
                'homework' => $request->homework,
                'homework_due_date' => $request->due_date,
            ]);
        }

        return $this->success([
            'session_id' => $session->id,
            'homework' => $session->homework,
            'due_date' => $session->homework_due_date?->toDateString(),
        ], __('Homework assigned successfully'));
    }

    /**
     * Update homework.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'homework' => ['sometimes', 'string', 'max:5000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();
        } else {
            $courseIds = $user->academicTeacherProfile->assignedCourses()
                ->pluck('id');

            $session = InteractiveCourseSession::where('id', $id)
                ->whereIn('course_id', $courseIds)
                ->first();
        }

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        $session->update([
            'homework' => $request->homework ?? $session->homework,
            'homework_due_date' => $request->has('due_date') ? $request->due_date : $session->homework_due_date,
        ]);

        return $this->success([
            'session_id' => $session->id,
            'homework' => $session->homework,
            'due_date' => $session->homework_due_date?->toDateString(),
        ], __('Homework updated successfully'));
    }

    /**
     * Get submissions for homework.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function submissions(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();
        } else {
            $courseIds = $user->academicTeacherProfile->assignedCourses()
                ->pluck('id');

            $session = InteractiveCourseSession::where('id', $id)
                ->whereIn('course_id', $courseIds)
                ->first();
        }

        if (!$session) {
            return $this->notFound(__('Session not found.'));
        }

        $submissions = HomeworkSubmission::where('homeworkable_type', get_class($session))
            ->where('homeworkable_id', $session->id)
            ->with(['user'])
            ->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'submissions' => collect($submissions->items())->map(fn($sub) => [
                'id' => $sub->id,
                'student' => $sub->user ? [
                    'id' => $sub->user->id,
                    'name' => $sub->user->name,
                    'avatar' => $sub->user->avatar ? asset('storage/' . $sub->user->avatar) : null,
                ] : null,
                'status' => $sub->status,
                'content' => $sub->content,
                'file_path' => $sub->file_path ? asset('storage/' . $sub->file_path) : null,
                'grade' => $sub->grade,
                'feedback' => $sub->feedback,
                'submitted_at' => $sub->submitted_at?->toISOString(),
                'graded_at' => $sub->graded_at?->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $submissions->currentPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
                'total_pages' => $submissions->lastPage(),
                'has_more' => $submissions->hasMorePages(),
            ],
        ], __('Submissions retrieved successfully'));
    }

    /**
     * Grade a submission.
     *
     * @param Request $request
     * @param int $submissionId
     * @return JsonResponse
     */
    public function grade(Request $request, int $submissionId): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'grade' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $submission = HomeworkSubmission::find($submissionId);

        if (!$submission) {
            return $this->notFound(__('Submission not found.'));
        }

        // Verify teacher access
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (!$academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        // Check if submission belongs to teacher's session
        $homeworkable = $submission->homeworkable;

        if ($homeworkable instanceof AcademicSession) {
            if ($homeworkable->academic_teacher_id !== $academicTeacherId) {
                return $this->error(__('You do not have access to this submission.'), 403, 'FORBIDDEN');
            }
        } elseif ($homeworkable instanceof InteractiveCourseSession) {
            $courseIds = $user->academicTeacherProfile->assignedCourses()
                ->pluck('id')
                ->toArray();

            if (!in_array($homeworkable->course_id, $courseIds)) {
                return $this->error(__('You do not have access to this submission.'), 403, 'FORBIDDEN');
            }
        } else {
            return $this->error(__('Invalid submission.'), 400, 'INVALID_SUBMISSION');
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by' => $user->id,
        ]);

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'grade' => $submission->grade,
                'feedback' => $submission->feedback,
                'status' => $submission->status,
                'graded_at' => $submission->graded_at->toISOString(),
            ],
        ], __('Submission graded successfully'));
    }
}
