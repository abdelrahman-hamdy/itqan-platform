<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\HomeworkSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
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
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
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

            $currentStatus = match (true) {
                $isGraded => 'graded',
                $isSubmitted => 'submitted',
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
                'description' => $session->homework_description,
                'file' => $session->homework_file,
                'is_assigned' => (bool) $session->homework_assigned,
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
        $isSubmitted = $submission !== null;
        $isGraded = $submission && $submission->grade !== null;

        return $this->success([
            'homework' => [
                'id' => $session->id,
                'type' => 'academic',
                'session_id' => $session->id,
                'title' => $session->title ?? 'واجب منزلي',
                'subject' => $session->academicSubscription?->subject_name,
                'description' => $session->homework_description,
                'file' => $session->homework_file,
                'is_assigned' => (bool) $session->homework_assigned,
                'status' => match (true) {
                    $isGraded => 'graded',
                    $isSubmitted => 'submitted',
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
                'can_submit' => ! $isSubmitted,
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
            ->whereNotNull('homework_description')
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Check if already submitted
        $existingSubmission = $session->homeworkSubmissions()
            ->where('student_id', $user->id)
            ->first();

        if ($existingSubmission) {
            return $this->error(
                __('Homework already submitted.'),
                400,
                'ALREADY_SUBMITTED'
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

        // Create submission
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
                'submitted_at' => $submission->created_at->toISOString(),
                'status' => $submission->submission_status,
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
