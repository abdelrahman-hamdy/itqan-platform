<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Enums\HomeworkSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\QuranSessionHomework;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeworkController extends Controller
{
    use ApiResponses;

    /**
     * Get homework list.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $homework = [];
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if ($academicTeacherId) {
            // Academic sessions with homework
            $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                ->whereNotNull('homework_description')
                ->where('homework_description', '!=', '')
                ->with(['student', 'academicSubscription', 'homeworkSubmissions'])
                ->orderBy('scheduled_at', 'desc')
                ->limit(50)
                ->get();

            foreach ($academicSessions as $session) {
                $homework[] = [
                    'id' => $session->id,
                    'type' => 'academic',
                    'title' => $session->academicSubscription?->subject_name ?? 'واجب أكاديمي',
                    'description' => $session->homework_description,
                    'student_name' => $session->student?->name ?? 'طالب',
                    'session_date' => $session->scheduled_at?->toDateString(),
                    'due_date' => null,
                    'submissions_count' => $session->homeworkSubmissions?->count() ?? 0,
                    'pending_submissions' => $session->homeworkSubmissions?->where('submission_status', HomeworkSubmissionStatus::SUBMITTED->value)->count() ?? 0,
                    'created_at' => $session->created_at?->toISOString(),
                ];
            }

            // Interactive course homework assignments
            $courseIds = $user->academicTeacherProfile?->assignedCourses()
                ?->pluck('id') ?? collect();

            $interactiveHomework = InteractiveCourseHomework::query()
                ->whereHas('session', fn ($q) => $q->whereIn('course_id', $courseIds))
                ->with(['session.course', 'submissions'])
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            foreach ($interactiveHomework as $hw) {
                $homework[] = [
                    'id' => $hw->id,
                    'type' => 'interactive',
                    'title' => $hw->title ?? $hw->session?->course?->title ?? 'واجب دورة',
                    'description' => $hw->description,
                    'course_name' => $hw->session?->course?->title,
                    'session_number' => $hw->session?->session_number,
                    'session_date' => $hw->session?->scheduled_at?->toDateString(),
                    'due_date' => $hw->due_date?->toDateString(),
                    'submissions_count' => $hw->submissions?->count() ?? 0,
                    'pending_submissions' => $hw->submissions?->whereIn('submission_status', [HomeworkSubmissionStatus::SUBMITTED->value, HomeworkSubmissionStatus::LATE->value])->count() ?? 0,
                    'created_at' => $hw->created_at?->toISOString(),
                ];
            }
        }

        // Sort by date
        usort($homework, fn ($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        // Paginate
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($homework);
        $homework = array_slice($homework, ($page - 1) * $perPage, $perPage);

        return $this->success([
            'homework' => array_values($homework),
            'pagination' => PaginationHelper::fromArray($total, $page, $perPage),
        ], __('Homework retrieved successfully'));
    }

    /**
     * Get homework detail.
     */
    public function show(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();

        if ($type === 'quran') {
            return $this->showQuranHomework($user, (int) $id);
        }

        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->with(['student', 'academicSubscription', 'homeworkSubmissions.student'])
                ->first();

            if (! $session) {
                return $this->notFound(__('Session not found.'));
            }

            return $this->success([
                'homework' => [
                    'id' => $session->id,
                    'type' => 'academic',
                    'title' => $session->academicSubscription?->subject_name ?? 'واجب أكاديمي',
                    'description' => $session->homework_description,
                    'student' => $session->student ? [
                        'id' => $session->student->id,
                        'name' => $session->student->name,
                    ] : null,
                    'session_date' => $session->scheduled_at?->toDateString(),
                    'due_date' => null,
                    'submissions' => $session->homeworkSubmissions->map(fn ($sub) => [
                        'id' => $sub->id,
                        'student_name' => $sub->student?->name,
                        'status' => $sub->submission_status,
                        'content' => $sub->submission_text,
                        'file_path' => $sub->submission_files[0]['path'] ?? null,
                        'grade' => $sub->score,
                        'feedback' => $sub->teacher_feedback,
                        'submitted_at' => $sub->submitted_at?->toISOString(),
                        'graded_at' => $sub->graded_at?->toISOString(),
                    ])->toArray(),
                ],
            ], __('Homework retrieved successfully'));
        }

        // Interactive - get homework assignment with submissions
        $courseIds = $user->academicTeacherProfile?->assignedCourses()
            ?->pluck('id') ?? collect();

        $homework = InteractiveCourseHomework::where('id', $id)
            ->whereHas('session', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->with(['session.course', 'submissions.student'])
            ->first();

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        return $this->success([
            'homework' => [
                'id' => $homework->id,
                'type' => 'interactive',
                'title' => $homework->title ?? $homework->session?->course?->title ?? 'واجب دورة',
                'description' => $homework->description,
                'instructions' => $homework->instructions,
                'course' => $homework->session?->course ? [
                    'id' => $homework->session->course->id,
                    'title' => $homework->session->course->title,
                ] : null,
                'session_number' => $homework->session?->session_number,
                'session_date' => $homework->session?->scheduled_at?->toDateString(),
                'due_date' => $homework->due_date?->toDateString(),
                'submissions' => $homework->submissions->map(fn ($sub) => [
                    'id' => $sub->id,
                    'student_name' => $sub->student?->name,
                    'status' => $sub->submission_status,
                    'content' => $sub->submission_text,
                    'file_path' => $sub->submission_files[0]['path'] ?? null,
                    'grade' => $sub->score,
                    'feedback' => $sub->teacher_feedback,
                    'submitted_at' => $sub->submitted_at?->toISOString(),
                    'graded_at' => $sub->graded_at?->toISOString(),
                ])->toArray(),
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Assign homework.
     */
    public function assign(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'session_type' => ['required', 'in:academic,interactive,quran'],
            'session_id' => ['required', 'integer'],
            'homework' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            // Quran-specific (only used when session_type=quran)
            'has_new_memorization' => ['sometimes', 'boolean'],
            'has_review' => ['sometimes', 'boolean'],
            'has_comprehensive_review' => ['sometimes', 'boolean'],
            'new_memorization_pages' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'new_memorization_surah' => ['sometimes', 'nullable', 'string', 'max:120'],
            'new_memorization_from_verse' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'new_memorization_to_verse' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'review_pages' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'review_surah' => ['sometimes', 'nullable', 'string', 'max:120'],
            'review_from_verse' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'review_to_verse' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'comprehensive_review_surahs' => ['sometimes', 'array'],
            'comprehensive_review_surahs.*' => ['string', 'max:120'],
            'additional_instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'difficulty_level' => ['sometimes', 'nullable', 'in:easy,medium,hard'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        if ($request->session_type === 'quran') {
            return $this->assignQuranHomework($user, $request);
        }

        if (! $request->filled('homework')) {
            return $this->validationError(['homework' => [__('The homework field is required.')]]);
        }

        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($request->session_type === 'academic') {
            $session = AcademicSession::where('id', $request->session_id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();

            if (! $session) {
                return $this->notFound(__('Session not found.'));
            }

            $session->update([
                'homework_description' => $request->homework,
                'homework_assigned' => true,
            ]);

            return $this->success([
                'session_id' => $session->id,
                'homework' => $session->homework_description,
                'due_date' => null,
            ], __('Homework assigned successfully'));
        }

        // Interactive: Create InteractiveCourseHomework assignment
        $courseIds = $user->academicTeacherProfile?->assignedCourses()
            ?->pluck('id') ?? collect();

        $session = InteractiveCourseSession::where('id', $request->session_id)
            ->whereIn('course_id', $courseIds)
            ->first();

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        // Create homework assignment
        $homework = InteractiveCourseHomework::create([
            'academy_id' => $session->academy_id ?? $session->course?->academy_id,
            'interactive_course_session_id' => $session->id,
            'teacher_id' => $user->id,
            'title' => $request->title ?? 'واجب الجلسة '.$session->session_number,
            'description' => $request->homework,
            'due_date' => $request->due_date,
            'created_by' => $user->id,
        ]);

        // Mark session as having homework
        $session->update(['homework_assigned' => true]);

        return $this->success([
            'homework_id' => $homework->id,
            'session_id' => $session->id,
            'title' => $homework->title,
            'description' => $homework->description,
            'due_date' => $homework->due_date?->toDateString(),
        ], __('Homework assigned successfully'));
    }

    /**
     * Update homework.
     */
    public function update(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();

        if ($type === 'quran') {
            return $this->updateQuranHomework($user, $request, (int) $id);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'string', 'max:255'],
            'homework' => ['sometimes', 'string', 'max:5000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();

            if (! $session) {
                return $this->notFound(__('Session not found.'));
            }

            $session->update([
                'homework_description' => $request->homework ?? $session->homework_description,
            ]);

            return $this->success([
                'session_id' => $session->id,
                'homework' => $session->homework_description,
                'due_date' => null,
            ], __('Homework updated successfully'));
        }

        // Interactive: Update InteractiveCourseHomework assignment
        $courseIds = $user->academicTeacherProfile?->assignedCourses()
            ?->pluck('id') ?? collect();

        $homework = InteractiveCourseHomework::where('id', $id)
            ->whereHas('session', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->first();

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        $homework->update([
            'title' => $request->title ?? $homework->title,
            'description' => $request->homework ?? $homework->description,
            'due_date' => $request->has('due_date') ? $request->due_date : $homework->due_date,
            'updated_by' => $user->id,
        ]);

        return $this->success([
            'homework_id' => $homework->id,
            'title' => $homework->title,
            'description' => $homework->description,
            'due_date' => $homework->due_date?->toDateString(),
        ], __('Homework updated successfully'));
    }

    /**
     * Delete homework.
     *
     * - quran: removes the QuranSessionHomework row keyed by id.
     * - academic: clears `homework_description` on the session (id = session id);
     *   AcademicSession has no separate homework row to remove.
     * - interactive: deletes the InteractiveCourseHomework row keyed by id.
     */
    public function destroy(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();

        if ($type === 'quran') {
            $homework = QuranSessionHomework::find($id);

            if (! $homework) {
                return $this->notFound(__('Homework not found.'));
            }

            $session = $this->resolveQuranSession($user, $homework->session_id);

            if (! $session) {
                return $this->notFound(__('Session not found.'));
            }

            $homework->delete();

            return $this->success([
                'session_id' => $session->id,
            ], __('Homework deleted successfully'));
        }

        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();

            if (! $session) {
                return $this->notFound(__('Session not found.'));
            }

            $session->update([
                'homework_description' => null,
                'homework_assigned' => false,
            ]);

            return $this->success([
                'session_id' => $session->id,
            ], __('Homework deleted successfully'));
        }

        $courseIds = $user->academicTeacherProfile?->assignedCourses()
            ?->pluck('id') ?? collect();

        $homework = InteractiveCourseHomework::where('id', $id)
            ->whereHas('session', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->first();

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        $sessionId = $homework->interactive_course_session_id;
        $homework->delete();

        // Clear `homework_assigned` on the session if no other homework rows remain.
        $remaining = InteractiveCourseHomework::where('interactive_course_session_id', $sessionId)
            ->exists();
        if (! $remaining) {
            InteractiveCourseSession::where('id', $sessionId)
                ->update(['homework_assigned' => false]);
        }

        return $this->success([
            'homework_id' => (int) $id,
        ], __('Homework deleted successfully'));
    }

    /**
     * Get submissions for homework.
     *
     * @param  int  $id  For academic: session_id, For interactive: homework_id
     */
    public function submissions(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $session = AcademicSession::where('id', $id)
                ->where('academic_teacher_id', $academicTeacherId)
                ->first();

            if (! $session) {
                return $this->notFound(__('Session not found.'));
            }

            // Get submissions through AcademicHomework
            $homeworkIds = AcademicHomework::where('academic_session_id', $session->id)
                ->pluck('id');

            $submissions = AcademicHomeworkSubmission::whereIn('academic_homework_id', $homeworkIds)
                ->with(['student'])
                ->orderBy('submitted_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return $this->success([
                'submissions' => collect($submissions->items())->map(fn ($sub) => [
                    'id' => $sub->id,
                    'student' => $sub->student ? [
                        'id' => $sub->student->id,
                        'name' => $sub->student->name,
                        'avatar' => $sub->student->avatar ? asset('storage/'.$sub->student->avatar) : null,
                    ] : null,
                    'status' => $sub->submission_status,
                    'content' => $sub->submission_text,
                    'files' => $sub->submission_files,
                    'grade' => $sub->score,
                    'feedback' => $sub->teacher_feedback,
                    'submitted_at' => $sub->submitted_at?->toISOString(),
                    'graded_at' => $sub->graded_at?->toISOString(),
                ])->toArray(),
                'pagination' => PaginationHelper::fromPaginator($submissions),
            ], __('Submissions retrieved successfully'));
        }

        // Interactive: Get submissions for InteractiveCourseHomework
        $courseIds = $user->academicTeacherProfile?->assignedCourses()
            ?->pluck('id') ?? collect();

        $homework = InteractiveCourseHomework::where('id', $id)
            ->whereHas('session', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->first();

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        $submissions = InteractiveCourseHomeworkSubmission::where('interactive_course_homework_id', $homework->id)
            ->with(['student'])
            ->orderBy('submitted_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success([
            'submissions' => collect($submissions->items())->map(fn ($sub) => [
                'id' => $sub->id,
                'student' => $sub->student ? [
                    'id' => $sub->student->id,
                    'name' => $sub->student->name,
                    'avatar' => $sub->student->avatar ? asset('storage/'.$sub->student->avatar) : null,
                ] : null,
                'status' => $sub->submission_status,
                'content' => $sub->submission_text,
                'files' => $sub->submission_files,
                'grade' => $sub->score,
                'feedback' => $sub->teacher_feedback,
                'submitted_at' => $sub->submitted_at?->toISOString(),
                'graded_at' => $sub->graded_at?->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($submissions),
        ], __('Submissions retrieved successfully'));
    }

    /**
     * Grade a submission.
     */
    public function grade(Request $request, int $submissionId): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'grade' => ['required', 'numeric', 'min:0', 'max:10'],
            'feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:academic,interactive'],
        ]);

        $type = $request->input('type');

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Verify teacher access
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $submission = AcademicHomeworkSubmission::with('homework.session')->find($submissionId);

            if (! $submission) {
                return $this->notFound(__('Submission not found.'));
            }

            // Check if submission belongs to teacher's homework
            $session = $submission->homework?->session;
            if (! $session || $session->academic_teacher_id !== $academicTeacherId) {
                return $this->error(__('You do not have access to this submission.'), 403, 'FORBIDDEN');
            }

            $submission->grade($request->grade, $request->feedback, $user->id);
        } else {
            // Interactive: Grade InteractiveCourseHomeworkSubmission
            $submission = InteractiveCourseHomeworkSubmission::with('homework.session.course')->find($submissionId);

            if (! $submission) {
                return $this->notFound(__('Submission not found.'));
            }

            // Check if submission belongs to teacher's course
            $courseIds = $user->academicTeacherProfile?->assignedCourses()
                ?->pluck('id')
                ?->toArray() ?? [];

            $course = $submission->homework?->session?->course;
            if (! $course || ! in_array($course->id, $courseIds)) {
                return $this->error(__('You do not have access to this submission.'), 403, 'FORBIDDEN');
            }

            $submission->grade($request->grade, $request->feedback, $user->id);
        }

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'grade' => $submission->score,
                'feedback' => $submission->teacher_feedback,
                'status' => $submission->submission_status,
                'graded_at' => $submission->graded_at?->toISOString(),
            ],
        ], __('Submission graded successfully'));
    }

    /**
     * Request revision for a homework submission.
     */
    public function requestRevision(Request $request, int $submissionId): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'feedback' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'in:academic,interactive'],
        ]);

        $type = $request->input('type');

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        // Verify teacher access
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        if ($type === 'academic') {
            $submission = AcademicHomeworkSubmission::with('homework.session')->find($submissionId);

            if (! $submission) {
                return $this->notFound(__('Submission not found.'));
            }

            // Check if submission belongs to teacher's homework
            $session = $submission->homework?->session;
            if (! $session || $session->academic_teacher_id !== $academicTeacherId) {
                return $this->error(__('You do not have access to this submission.'), 403, 'FORBIDDEN');
            }

            // Check if submission can be revised
            if ($submission->submission_status === HomeworkSubmissionStatus::GRADED) {
                return $this->error(__('Cannot request revision for a graded submission. Update the grade instead.'), 400, 'ALREADY_GRADED');
            }

            $submission->update([
                'submission_status' => HomeworkSubmissionStatus::REVISION_REQUESTED,
                'teacher_feedback' => $request->feedback,
                'revision_requested_at' => now(),
                'revision_requested_by' => $user->id,
            ]);
        } else {
            // Interactive: Request revision for InteractiveCourseHomeworkSubmission
            $submission = InteractiveCourseHomeworkSubmission::with('homework.session.course')->find($submissionId);

            if (! $submission) {
                return $this->notFound(__('Submission not found.'));
            }

            // Check if submission belongs to teacher's course
            $courseIds = $user->academicTeacherProfile?->assignedCourses()
                ?->pluck('id')
                ?->toArray() ?? [];

            $course = $submission->homework?->session?->course;
            if (! $course || ! in_array($course->id, $courseIds)) {
                return $this->error(__('You do not have access to this submission.'), 403, 'FORBIDDEN');
            }

            // Check if submission can be revised
            if ($submission->submission_status === HomeworkSubmissionStatus::GRADED) {
                return $this->error(__('Cannot request revision for a graded submission. Update the grade instead.'), 400, 'ALREADY_GRADED');
            }

            $submission->update([
                'submission_status' => HomeworkSubmissionStatus::REVISION_REQUESTED,
                'teacher_feedback' => $request->feedback,
                'revision_requested_at' => now(),
                'revision_requested_by' => $user->id,
            ]);
        }

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->submission_status,
                'feedback' => $submission->teacher_feedback,
                'revision_requested_at' => $submission->revision_requested_at?->toISOString(),
            ],
        ], __('Revision requested successfully. The student will be notified.'));
    }

    /**
     * Resolve a Quran session owned by the teacher.
     */
    protected function resolveQuranSession($user, int $sessionId): ?QuranSession
    {
        if (! $user->quranTeacherProfile) {
            return null;
        }

        return QuranSession::where('id', $sessionId)
            ->where('quran_teacher_id', $user->id)
            ->first();
    }

    /**
     * Show Quran session homework (no submissions — evaluated orally).
     */
    protected function showQuranHomework($user, int $sessionId): JsonResponse
    {
        $session = $this->resolveQuranSession($user, $sessionId);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $homework = QuranSessionHomework::where('session_id', $session->id)->first();

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        return $this->success([
            'homework' => $this->formatQuranHomework($session, $homework),
        ], __('Homework retrieved successfully'));
    }

    /**
     * Upsert Quran session homework (evaluated orally — no submissions table).
     *
     * Note: `quran_sessions.homework_assigned` is a JSON column (not the
     * boolean it is on AcademicSession), and the QuranSession model declines
     * to cast it. Trying to write `1` produces `SQLSTATE[22032] Invalid JSON
     * text`. The QuranSessionHomework row's existence is the canonical
     * "homework assigned" signal — we don't touch the column here.
     */
    protected function assignQuranHomework($user, Request $request): JsonResponse
    {
        $session = $this->resolveQuranSession($user, (int) $request->session_id);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $payload = $this->buildQuranHomeworkPayload($request, isUpdate: false);
        $payload['created_by'] = $user->id;

        $homework = QuranSessionHomework::updateOrCreate(
            ['session_id' => $session->id],
            $payload,
        );

        return $this->success([
            'homework' => $this->formatQuranHomework($session, $homework),
        ], __('Homework assigned successfully'));
    }

    /**
     * Update an existing Quran session homework row by id.
     */
    protected function updateQuranHomework($user, Request $request, int $id): JsonResponse
    {
        $homework = QuranSessionHomework::find($id);

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        $session = $this->resolveQuranSession($user, $homework->session_id);

        if (! $session) {
            return $this->notFound(__('Session not found.'));
        }

        $homework->update($this->buildQuranHomeworkPayload($request, isUpdate: true));

        return $this->success([
            'homework' => $this->formatQuranHomework($session, $homework->fresh()),
        ], __('Homework updated successfully'));
    }

    /**
     * Build a fillable payload for QuranSessionHomework from request input.
     * Only keys that were sent are returned (so update doesn't clobber unset fields).
     */
    protected function buildQuranHomeworkPayload(Request $request, bool $isUpdate): array
    {
        $keys = [
            'has_new_memorization',
            'has_review',
            'has_comprehensive_review',
            'new_memorization_pages',
            'new_memorization_surah',
            'new_memorization_from_verse',
            'new_memorization_to_verse',
            'review_pages',
            'review_surah',
            'review_from_verse',
            'review_to_verse',
            'comprehensive_review_surahs',
            'additional_instructions',
            'due_date',
            'difficulty_level',
        ];

        $payload = [];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $payload[$key] = $request->input($key);
            }
        }

        if (! $isUpdate) {
            $payload += [
                'is_active' => true,
                'has_new_memorization' => $payload['has_new_memorization'] ?? false,
                'has_review' => $payload['has_review'] ?? false,
                'has_comprehensive_review' => $payload['has_comprehensive_review'] ?? false,
            ];
        }

        return $payload;
    }

    /**
     * Format a QuranSessionHomework row for API responses.
     */
    protected function formatQuranHomework(QuranSession $session, QuranSessionHomework $homework): array
    {
        return [
            'id' => $homework->id,
            'type' => 'quran',
            'session_id' => $session->id,
            'evaluated_orally' => true,
            'has_new_memorization' => (bool) $homework->has_new_memorization,
            'has_review' => (bool) $homework->has_review,
            'has_comprehensive_review' => (bool) $homework->has_comprehensive_review,
            'new_memorization_pages' => $homework->new_memorization_pages,
            'new_memorization_surah' => $homework->new_memorization_surah,
            'new_memorization_from_verse' => $homework->new_memorization_from_verse,
            'new_memorization_to_verse' => $homework->new_memorization_to_verse,
            'review_pages' => $homework->review_pages,
            'review_surah' => $homework->review_surah,
            'review_from_verse' => $homework->review_from_verse,
            'review_to_verse' => $homework->review_to_verse,
            'comprehensive_review_surahs' => $homework->comprehensive_review_surahs ?? [],
            'additional_instructions' => $homework->additional_instructions,
            'due_date' => $homework->due_date?->toDateString(),
            'difficulty_level' => $homework->difficulty_level,
            'is_active' => (bool) $homework->is_active,
        ];
    }
}
