<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\HomeworkSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeworkController extends Controller
{
    use ApiResponses;

    /**
     * Get all homework assignments for the student.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->get('status'); // pending, submitted, graded, overdue

        // Get academic sessions with homework
        $query = AcademicSession::where('student_id', $user->id)
            ->whereNotNull('homework')
            ->where('homework', '!=', '')
            ->with(['academicTeacher.user', 'academicSubscription', 'homeworkSubmissions' => function ($q) use ($user) {
                $q->where('student_id', $user->id);
            }]);

        $sessions = $query->orderBy('homework_due_date', 'asc')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        $homework = $sessions->map(function ($session) use ($user, $status) {
            $submission = $session->homeworkSubmissions->first();
            $isSubmitted = $submission !== null;
            $isGraded = $submission && $submission->grade !== null;
            $isOverdue = !$isSubmitted && $session->homework_due_date && $session->homework_due_date->isPast();

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
            'total' => count($homework),
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
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
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

        if (!$session) {
            return $this->notFound(__('Homework not found.'));
        }

        $submission = $session->homeworkSubmissions->first();
        $isSubmitted = $submission !== null;
        $isGraded = $submission && $submission->grade !== null;
        $isOverdue = !$isSubmitted && $session->homework_due_date && $session->homework_due_date->isPast();

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
                        ? asset('storage/' . $session->academicTeacher->user->avatar)
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
                'can_submit' => !$isSubmitted && !$isOverdue,
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Submit homework.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
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

        if (!$session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Check if already submitted
        $existingSubmission = HomeworkSubmission::where('session_id', $session->id)
            ->where('session_type', AcademicSession::class)
            ->where('student_id', $user->id)
            ->first();

        if ($existingSubmission) {
            return $this->error(
                __('Homework already submitted.'),
                400,
                'ALREADY_SUBMITTED'
            );
        }

        // Check if overdue
        if ($session->homework_due_date && $session->homework_due_date->isPast()) {
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
                $path = $file->store('homework-submissions/' . $user->id, 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Create submission
        $submission = HomeworkSubmission::create([
            'session_id' => $session->id,
            'session_type' => AcademicSession::class,
            'student_id' => $user->id,
            'content' => $request->content,
            'attachments' => $attachments,
            'status' => 'submitted',
        ]);

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
     * Save homework draft.
     *
     * @param Request $request
     * @param string $type
     * @param int $id
     * @return JsonResponse
     */
    public function saveDraft(Request $request, string $type, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => ['nullable', 'string', 'max:10000'],
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

        if (!$session) {
            return $this->notFound(__('Homework not found.'));
        }

        // Find or create draft
        $submission = HomeworkSubmission::updateOrCreate(
            [
                'session_id' => $session->id,
                'session_type' => AcademicSession::class,
                'student_id' => $user->id,
                'status' => 'draft',
            ],
            [
                'content' => $request->content,
            ]
        );

        return $this->success([
            'draft' => [
                'id' => $submission->id,
                'content' => $submission->content,
                'saved_at' => $submission->updated_at->toISOString(),
            ],
        ], __('Draft saved successfully'));
    }
}
