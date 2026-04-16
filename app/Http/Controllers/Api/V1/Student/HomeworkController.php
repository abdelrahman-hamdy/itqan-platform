<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\HomeworkSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeworkController extends Controller
{
    use ApiResponses;

    /**
     * Get all homework assignments for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // pending, submitted, graded, overdue
        $typeFilter = $request->get('type'); // quran, academic, interactive
        $homework = collect();

        // Academic homework
        if (! $typeFilter || $typeFilter === 'academic') {
            $academicSessions = AcademicSession::where('student_id', $user->id)
                ->whereNotNull('homework_description')
                ->where('homework_description', '!=', '')
                ->with(['academicTeacher.user', 'academicSubscription', 'homeworkSubmissions' => function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                }])
                ->orderBy('scheduled_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($academicSessions as $session) {
                $submission = $session->homeworkSubmissions->first();
                $currentStatus = $this->resolveSubmissionStatus($submission);

                if ($status && $currentStatus !== $status) {
                    continue;
                }

                $homework->push([
                    'id' => $session->id,
                    'type' => 'academic',
                    'session_id' => $session->id,
                    'title' => $session->title ?? __('homework.default_title'),
                    'subject' => $session->academicSubscription?->subject_name,
                    'description' => $session->homework_description,
                    'file' => $session->homework_file,
                    'is_assigned' => (bool) $session->homework_assigned,
                    'status' => $currentStatus,
                    'can_submit' => true,
                    'teacher' => $session->academicTeacher?->user ? [
                        'id' => $session->academicTeacher->user->id,
                        'name' => $session->academicTeacher->user->name,
                    ] : null,
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'submitted_at' => $submission->created_at?->toISOString(),
                        'grade' => $submission->grade,
                        'feedback' => $submission->feedback,
                        'status' => $submission->status,
                    ] : null,
                    'session_date' => $session->scheduled_at?->toISOString(),
                ]);
            }
        }

        // Quran homework (view-only for students — no submission)
        if (! $typeFilter || $typeFilter === 'quran') {
            $quranSessions = QuranSession::where('student_id', $user->id)
                ->whereHas('sessionHomework')
                ->with(['quranTeacher', 'sessionHomework'])
                ->orderBy('scheduled_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($quranSessions as $session) {
                $hw = $session->sessionHomework;
                if (! $hw) {
                    continue;
                }

                // Quran homework doesn't have submission status
                if ($status && $status !== 'pending') {
                    continue;
                }

                $homework->push([
                    'id' => $hw->id,
                    'type' => 'quran',
                    'session_id' => $session->id,
                    'title' => $session->title ?? __('homework.quran_homework'),
                    'subject' => __('القرآن الكريم'),
                    'description' => $hw->additional_instructions,
                    'is_assigned' => true,
                    'status' => 'pending',
                    'can_submit' => false,
                    'teacher' => $session->quranTeacher ? [
                        'id' => $session->quranTeacher->id,
                        'name' => $session->quranTeacher->name,
                    ] : null,
                    'submission' => null,
                    'session_date' => $session->scheduled_at?->toISOString(),
                    'quran_details' => [
                        'has_new_memorization' => $hw->has_new_memorization,
                        'new_memorization_surah' => $hw->new_memorization_surah,
                        'new_memorization_from_verse' => $hw->new_memorization_from_verse,
                        'new_memorization_to_verse' => $hw->new_memorization_to_verse,
                        'new_memorization_pages' => $hw->new_memorization_pages,
                        'has_review' => $hw->has_review,
                        'review_surah' => $hw->review_surah,
                        'review_from_verse' => $hw->review_from_verse,
                        'review_to_verse' => $hw->review_to_verse,
                        'review_pages' => $hw->review_pages,
                        'due_date' => $hw->due_date?->toDateString(),
                    ],
                ]);
            }
        }

        // Interactive course homework
        if (! $typeFilter || $typeFilter === 'interactive') {
            $studentProfileId = $user->studentProfile?->id;

            if ($studentProfileId) {
                $interactiveHomework = InteractiveCourseHomework::whereHas('session.course.enrollments', function ($q) use ($studentProfileId) {
                    $q->where('student_id', $studentProfileId);
                })
                    ->with(['session.course.assignedTeacher.user', 'submissions' => function ($q) use ($user) {
                        $q->where('student_id', $user->id);
                    }])
                    ->where('is_active', true)
                    ->orderBy('due_date', 'asc')
                    ->limit(100)
                    ->get();

                foreach ($interactiveHomework as $hw) {
                    $submission = $hw->submissions->first();
                    $currentStatus = $this->resolveSubmissionStatus($submission);

                    if ($status && $currentStatus !== $status) {
                        continue;
                    }

                    $teacher = $hw->session?->course?->assignedTeacher?->user;
                    $homework->push([
                        'id' => $hw->id,
                        'type' => 'interactive',
                        'session_id' => $hw->interactive_course_session_id,
                        'title' => $hw->title ?? __('homework.default_title'),
                        'subject' => $hw->session?->course?->title,
                        'description' => $hw->description,
                        'instructions' => $hw->instructions,
                        'file' => null,
                        'teacher_files' => $hw->teacher_files,
                        'is_assigned' => true,
                        'status' => $currentStatus,
                        'can_submit' => true,
                        'due_date' => $hw->due_date?->toISOString(),
                        'max_score' => $hw->max_score,
                        'teacher' => $teacher ? [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                        ] : null,
                        'submission' => $submission ? [
                            'id' => $submission->id,
                            'submitted_at' => $submission->created_at?->toISOString(),
                            'grade' => $submission->grade,
                            'feedback' => $submission->feedback,
                            'status' => $submission->submission_status,
                        ] : null,
                        'session_date' => $hw->session?->scheduled_at?->toISOString(),
                    ]);
                }
            }
        }

        // Sort by session date descending
        $sorted = $homework->sortByDesc('session_date')->values();

        // Paginate
        $page = (int) $request->get('page', 1);
        $perPage = min((int) $request->get('per_page', 15), 50);
        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return $this->success([
            'homework' => $items->toArray(),
            'pagination' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($page * $perPage) < $total,
            ],
            'stats' => [
                'pending' => $sorted->where('status', 'pending')->count(),
                'submitted' => $sorted->where('status', 'submitted')->count(),
                'graded' => $sorted->where('status', 'graded')->count(),
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Resolve homework submission status.
     */
    private function resolveSubmissionStatus($submission): string
    {
        if (! $submission) {
            return 'pending';
        }

        if ($submission->grade !== null) {
            return 'graded';
        }

        return 'submitted';
    }

    /**
     * Get a specific homework assignment.
     */
    public function show(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();

        return match ($type) {
            'academic' => $this->showAcademicHomework($user, $id),
            'quran' => $this->showQuranHomework($user, $id),
            'interactive' => $this->showInteractiveHomework($user, $id),
            default => $this->error(__('Invalid homework type.'), 400, 'INVALID_TYPE'),
        };
    }

    private function showAcademicHomework($user, string $id): JsonResponse
    {
        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework_description')
            ->with([
                'academicTeacher.user',
                'academicSubscription',
                'homeworkSubmissions' => function ($q) use ($user) {
                    $q->where('student_id', $user->id);
                },
            ])
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        $submission = $session->homeworkSubmissions->first();

        return $this->success([
            'homework' => [
                'id' => $session->id,
                'type' => 'academic',
                'session_id' => $session->id,
                'title' => $session->title ?? __('homework.default_title'),
                'subject' => $session->academicSubscription?->subject_name,
                'description' => $session->homework_description,
                'file' => $session->homework_file,
                'is_assigned' => (bool) $session->homework_assigned,
                'status' => $this->resolveSubmissionStatus($submission),
                'can_submit' => $submission === null,
                'teacher' => $session->academicTeacher?->user ? [
                    'id' => $session->academicTeacher->user->id,
                    'name' => $session->academicTeacher->user->name,
                    'avatar' => $session->academicTeacher->user->avatar
                        ? asset('storage/'.$session->academicTeacher->user->avatar)
                        : null,
                ] : null,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'content' => $submission->content,
                    'attachments' => $submission->attachments ?? [],
                    'submitted_at' => $submission->created_at?->toISOString(),
                    'grade' => $submission->grade,
                    'max_grade' => 100,
                    'feedback' => $submission->feedback,
                    'status' => $submission->status,
                ] : null,
                'session_date' => $session->scheduled_at?->toISOString(),
            ],
        ], __('Homework retrieved successfully'));
    }

    private function showQuranHomework($user, string $id): JsonResponse
    {
        $session = QuranSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereHas('sessionHomework')
            ->with(['quranTeacher', 'sessionHomework'])
            ->first();

        if (! $session || ! $session->sessionHomework) {
            return $this->notFound(__('Homework not found.'));
        }

        $hw = $session->sessionHomework;

        return $this->success([
            'homework' => [
                'id' => $hw->id,
                'type' => 'quran',
                'session_id' => $session->id,
                'title' => $session->title ?? __('homework.quran_homework'),
                'subject' => __('القرآن الكريم'),
                'description' => $hw->additional_instructions,
                'is_assigned' => true,
                'status' => 'pending',
                'can_submit' => false,
                'teacher' => $session->quranTeacher ? [
                    'id' => $session->quranTeacher->id,
                    'name' => $session->quranTeacher->name,
                    'avatar' => $session->quranTeacher->avatar
                        ? asset('storage/'.$session->quranTeacher->avatar)
                        : null,
                ] : null,
                'submission' => null,
                'session_date' => $session->scheduled_at?->toISOString(),
                'quran_details' => [
                    'has_new_memorization' => $hw->has_new_memorization,
                    'new_memorization_surah' => $hw->new_memorization_surah,
                    'new_memorization_from_verse' => $hw->new_memorization_from_verse,
                    'new_memorization_to_verse' => $hw->new_memorization_to_verse,
                    'new_memorization_pages' => $hw->new_memorization_pages,
                    'has_review' => $hw->has_review,
                    'review_surah' => $hw->review_surah,
                    'review_from_verse' => $hw->review_from_verse,
                    'review_to_verse' => $hw->review_to_verse,
                    'review_pages' => $hw->review_pages,
                    'has_comprehensive_review' => $hw->has_comprehensive_review,
                    'comprehensive_review_surahs' => $hw->comprehensive_review_surahs,
                    'due_date' => $hw->due_date?->toDateString(),
                    'difficulty_level' => $hw->difficulty_level,
                ],
            ],
        ], __('Homework retrieved successfully'));
    }

    private function showInteractiveHomework($user, string $id): JsonResponse
    {
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return $this->notFound(__('Homework not found.'));
        }

        $hw = InteractiveCourseHomework::where('id', $id)
            ->whereHas('session.course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
            ->with(['session.course.assignedTeacher.user', 'submissions' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }])
            ->first();

        if (! $hw) {
            return $this->notFound(__('Homework not found.'));
        }

        $submission = $hw->submissions->first();
        $teacher = $hw->session?->course?->assignedTeacher?->user;

        return $this->success([
            'homework' => [
                'id' => $hw->id,
                'type' => 'interactive',
                'session_id' => $hw->interactive_course_session_id,
                'title' => $hw->title ?? __('homework.default_title'),
                'subject' => $hw->session?->course?->title,
                'description' => $hw->description,
                'instructions' => $hw->instructions,
                'teacher_files' => $hw->teacher_files,
                'is_assigned' => true,
                'status' => $this->resolveSubmissionStatus($submission),
                'can_submit' => $submission === null,
                'due_date' => $hw->due_date?->toISOString(),
                'max_score' => $hw->max_score,
                'allow_late_submissions' => $hw->allow_late_submissions,
                'teacher' => $teacher ? [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'avatar' => $teacher->avatar ? asset('storage/'.$teacher->avatar) : null,
                ] : null,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'content' => $submission->content,
                    'attachments' => $submission->student_files ?? [],
                    'submitted_at' => $submission->created_at?->toISOString(),
                    'grade' => $submission->grade,
                    'max_grade' => $hw->max_score,
                    'feedback' => $submission->feedback,
                    'status' => $submission->submission_status,
                ] : null,
                'session_date' => $hw->session?->scheduled_at?->toISOString(),
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Submit homework.
     */
    public function submit(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => ['required_without:attachments', 'nullable', 'string', 'max:10000'],
            'attachments' => ['required_without:content', 'nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        if ($type === 'quran') {
            return $this->error(__('Quran homework does not support submissions.'), 400, 'NOT_SUBMITTABLE');
        }

        if (! in_array($type, ['academic', 'interactive'])) {
            return $this->error(__('Invalid homework type.'), 400, 'INVALID_TYPE');
        }

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('homework-submissions/'.$user->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        if ($type === 'interactive') {
            return $this->submitInteractiveHomework($user, $id, $request->content, $attachments);
        }

        // Academic submission
        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework_description')
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        $existingSubmission = $session->homeworkSubmissions()
            ->where('student_id', $user->id)
            ->first();

        if ($existingSubmission) {
            return $this->error(__('Homework already submitted.'), 400, 'ALREADY_SUBMITTED');
        }

        $submission = $session->homeworkSubmissions()->create([
            'academy_id' => $session->academy_id,
            'student_id' => $user->id,
            'content' => $request->content,
            'student_files' => $attachments,
            'submission_status' => HomeworkSubmissionStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        return $this->created([
            'submission' => [
                'id' => $submission->id,
                'content' => $submission->content,
                'attachments' => $submission->student_files,
                'submitted_at' => $submission->created_at?->toISOString(),
                'status' => $submission->submission_status,
            ],
        ], __('Homework submitted successfully'));
    }

    private function submitInteractiveHomework($user, string $homeworkId, ?string $content, array $attachments): JsonResponse
    {
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return $this->notFound(__('Homework not found.'));
        }

        $homework = InteractiveCourseHomework::where('id', $homeworkId)
            ->whereHas('session.course.enrollments', function ($q) use ($studentProfileId) {
                $q->where('student_id', $studentProfileId);
            })
            ->first();

        if (! $homework) {
            return $this->notFound(__('Homework not found.'));
        }

        $existing = InteractiveCourseHomeworkSubmission::where('homework_id', $homeworkId)
            ->where('student_id', $user->id)
            ->first();

        if ($existing) {
            return $this->error(__('Homework already submitted.'), 400, 'ALREADY_SUBMITTED');
        }

        $submission = InteractiveCourseHomeworkSubmission::create([
            'homework_id' => $homework->id,
            'academy_id' => $homework->academy_id,
            'student_id' => $user->id,
            'content' => $content,
            'student_files' => $attachments,
            'submission_status' => HomeworkSubmissionStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        return $this->created([
            'submission' => [
                'id' => $submission->id,
                'content' => $submission->content,
                'attachments' => $submission->student_files,
                'submitted_at' => $submission->created_at?->toISOString(),
                'status' => $submission->submission_status,
            ],
        ], __('Homework submitted successfully'));
    }

    /**
     * Save homework as draft.
     */
    public function saveDraft(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => ['nullable', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        if (! in_array($type, ['academic', 'interactive'])) {
            return $this->error(__('Invalid homework type for drafts.'), 400, 'INVALID_TYPE');
        }

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework_description')
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Check if already submitted (not a draft)
        $existingSubmission = $session->homeworkSubmissions()
            ->where('student_id', $user->id)
            ->where('submission_status', '!=', HomeworkSubmissionStatus::DRAFT)
            ->first();

        if ($existingSubmission) {
            return $this->error(__('Homework already submitted. Cannot save as draft.'), 400, 'ALREADY_SUBMITTED');
        }

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('homework-drafts/'.$user->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Find or create draft
        $draft = $session->homeworkSubmissions()
            ->where('student_id', $user->id)
            ->where('submission_status', HomeworkSubmissionStatus::DRAFT)
            ->first();

        if ($draft) {
            $draft->update([
                'content' => $request->content,
                'student_files' => ! empty($attachments) ? $attachments : $draft->student_files,
            ]);
        } else {
            $draft = $session->homeworkSubmissions()->create([
                'academy_id' => $session->academy_id,
                'student_id' => $user->id,
                'content' => $request->content,
                'student_files' => $attachments,
                'submission_status' => HomeworkSubmissionStatus::DRAFT,
            ]);
        }

        return $this->success([
            'draft' => [
                'id' => $draft->id,
                'content' => $draft->content,
                'attachments' => $draft->student_files,
                'saved_at' => $draft->updated_at?->toISOString(),
                'status' => 'draft',
            ],
        ], __('Draft saved successfully'));
    }

    /**
     * Submit homework revision.
     */
    public function submitRevision(Request $request, string $type, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => ['required_without:attachments', 'nullable', 'string', 'max:10000'],
            'attachments' => ['required_without:content', 'nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();

        if (! in_array($type, ['academic', 'interactive'])) {
            return $this->error(__('Invalid homework type for revisions.'), 400, 'INVALID_TYPE');
        }

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework_description')
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Check if there's a submission that needs revision
        $existingSubmission = $session->homeworkSubmissions()
            ->where('student_id', $user->id)
            ->first();

        if (! $existingSubmission) {
            return $this->error(
                __('No existing submission found. Please submit homework first.'),
                400,
                'NO_SUBMISSION'
            );
        }

        // Check if revision was requested
        if ($existingSubmission->submission_status !== HomeworkSubmissionStatus::REVISION_REQUESTED) {
            return $this->error(
                __('Revision not requested for this submission.'),
                400,
                'REVISION_NOT_REQUESTED'
            );
        }

        // Handle file uploads
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('homework-submissions/'.$user->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Update submission with revision
        $existingSubmission->update([
            'content' => $request->content,
            'student_files' => ! empty($attachments) ? $attachments : $existingSubmission->student_files,
            'submission_status' => HomeworkSubmissionStatus::RESUBMITTED,
            'resubmitted_at' => now(),
            'revision_count' => ($existingSubmission->revision_count ?? 0) + 1,
        ]);

        return $this->success([
            'submission' => [
                'id' => $existingSubmission->id,
                'content' => $existingSubmission->content,
                'attachments' => $existingSubmission->student_files,
                'submitted_at' => $existingSubmission->resubmitted_at->toISOString(),
                'status' => $existingSubmission->submission_status,
                'revision_count' => $existingSubmission->revision_count,
            ],
        ], __('Homework revision submitted successfully'));
    }
}
