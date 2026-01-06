<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\HomeworkSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
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
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        // Get academic sessions with homework
        $query = AcademicSession::where('student_id', $user->id)
            ->whereNotNull('homework')
            ->where('homework', '!=', '')
            ->with(['academicTeacher.user', 'academicSubscription', 'homeworkSubmissions' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }]);

        // Paginate the query
        $paginator = $query->orderBy('homework_due_date', 'asc')
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);

        $homework = collect($paginator->items())->map(function ($session) use ($status) {
            $submission = $session->homeworkSubmissions->first();
            $isSubmitted = $submission !== null;
            $isGraded = $submission && $submission->grade !== null;
            $isOverdue = ! $isSubmitted && $session->homework_due_date && $session->homework_due_date->isPast();

            $currentStatus = match (true) {
                $isGraded => 'graded',
                $isSubmitted => 'submitted',
                $isOverdue => 'overdue',
                default => 'pending',
            };

            // Filter by status if provided
            if ($status && $currentStatus !== $status) {
                return null;
            }

            return [
                'id' => $session->id,
                'type' => 'academic',
                'session_id' => $session->id,
                'title' => $session->title ?? 'واجب منزلي',
                'subject' => $session->academicSubscription?->subject_name,
                'description' => $session->homework,
                'due_date' => $session->homework_due_date?->toISOString(),
                'is_overdue' => $isOverdue,
                'status' => $currentStatus,
                'teacher' => $session->academicTeacher?->user ? [
                    'id' => $session->academicTeacher->user->id,
                    'name' => $session->academicTeacher->user->name,
                ] : null,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submitted_at' => $submission->created_at->toISOString(),
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                    'status' => $submission->status,
                ] : null,
                'session_date' => $session->scheduled_at?->toISOString(),
            ];
        })->filter()->values()->toArray();

        return $this->success([
            'homework' => $homework,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'stats' => [
                'pending' => collect($homework)->where('status', 'pending')->count(),
                'submitted' => collect($homework)->where('status', 'submitted')->count(),
                'graded' => collect($homework)->where('status', 'graded')->count(),
                'overdue' => collect($homework)->where('status', 'overdue')->count(),
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Get a specific homework assignment.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();

        if ($type !== 'academic') {
            return $this->error(
                __('Invalid homework type.'),
                400,
                'INVALID_TYPE'
            );
        }

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework')
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
        $isSubmitted = $submission !== null;
        $isGraded = $submission && $submission->grade !== null;
        $isOverdue = ! $isSubmitted && $session->homework_due_date && $session->homework_due_date->isPast();

        return $this->success([
            'homework' => [
                'id' => $session->id,
                'type' => 'academic',
                'session_id' => $session->id,
                'title' => $session->title ?? 'واجب منزلي',
                'subject' => $session->academicSubscription?->subject_name,
                'description' => $session->homework,
                'attachments' => $session->homework_attachments ?? [],
                'due_date' => $session->homework_due_date?->toISOString(),
                'is_overdue' => $isOverdue,
                'status' => match (true) {
                    $isGraded => 'graded',
                    $isSubmitted => 'submitted',
                    $isOverdue => 'overdue',
                    default => 'pending',
                },
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
                    'submitted_at' => $submission->created_at->toISOString(),
                    'grade' => $submission->grade,
                    'max_grade' => 100,
                    'feedback' => $submission->feedback,
                    'status' => $submission->status,
                ] : null,
                'session_date' => $session->scheduled_at?->toISOString(),
                'can_submit' => ! $isSubmitted && ! $isOverdue,
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Submit homework.
     */
    public function submit(Request $request, string $type, int $id): JsonResponse
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

        if ($type !== 'academic') {
            return $this->error(
                __('Invalid homework type.'),
                400,
                'INVALID_TYPE'
            );
        }

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework')
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Find the homework assignment for this session
        $homework = AcademicHomework::where('academic_session_id', $session->id)->first();

        if (! $homework) {
            return $this->error(
                __('No homework assignment found for this session.'),
                404,
                'NO_HOMEWORK'
            );
        }

        // Check if already submitted
        $existingSubmission = AcademicHomeworkSubmission::where('academic_homework_id', $homework->id)
            ->where('student_id', $user->id)
            ->first();

        if ($existingSubmission && $existingSubmission->submission_status !== HomeworkSubmissionStatus::PENDING) {
            return $this->error(
                __('Homework already submitted.'),
                400,
                'ALREADY_SUBMITTED'
            );
        }

        // Check if overdue
        if ($homework->due_date && $homework->due_date->isPast() && ! $homework->allow_late_submissions) {
            return $this->error(
                __('Cannot submit homework after due date.'),
                400,
                'HOMEWORK_OVERDUE'
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

        // Determine if this is a late submission
        $isLate = $homework->due_date && $homework->due_date->isPast();
        $status = $isLate ? HomeworkSubmissionStatus::LATE : HomeworkSubmissionStatus::SUBMITTED;

        // Create or update submission
        $submission = AcademicHomeworkSubmission::updateOrCreate(
            [
                'academic_homework_id' => $homework->id,
                'student_id' => $user->id,
            ],
            [
                'academy_id' => $homework->academy_id,
                'content' => $request->content,
                'student_files' => $attachments,
                'submission_status' => $status,
                'submitted_at' => now(),
                'is_late' => $isLate,
            ]
        );

        return $this->created([
            'submission' => [
                'id' => $submission->id,
                'content' => $submission->content,
                'attachments' => $submission->attachments,
                'submitted_at' => $submission->created_at->toISOString(),
                'status' => $submission->status,
            ],
        ], __('Homework submitted successfully'));
    }

    /**
     * Submit homework revision.
     */
    public function submitRevision(Request $request, string $type, int $id): JsonResponse
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

        if ($type !== 'academic') {
            return $this->error(
                __('Invalid homework type.'),
                400,
                'INVALID_TYPE'
            );
        }

        $session = AcademicSession::where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework')
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Find the homework assignment for this session
        $homework = AcademicHomework::where('academic_session_id', $session->id)->first();

        if (! $homework) {
            return $this->error(
                __('No homework assignment found for this session.'),
                404,
                'NO_HOMEWORK'
            );
        }

        // Check if there's a submission that needs revision
        $existingSubmission = AcademicHomeworkSubmission::where('academic_homework_id', $homework->id)
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
