<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    use ApiResponses;

    /**
     * Get linked children IDs for the parent.
     */
    private function getLinkedChildIds(Request $request): array
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return [];
        }

        return ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->pluck('student_id')
            ->toArray();
    }

    /**
     * Get all homework assignments for the parent's children.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $childIds = $this->getLinkedChildIds($request);
        $selectedChildId = $request->get('child_id');
        $status = $request->get('status'); // pending, submitted, graded
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        // Filter by specific child if provided
        if ($selectedChildId && in_array($selectedChildId, $childIds)) {
            $childIds = [$selectedChildId];
        }

        if (empty($childIds)) {
            return $this->success([
                'homework' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'has_more' => false,
                ],
                'stats' => [
                    'pending' => 0,
                    'submitted' => 0,
                    'graded' => 0,
                ],
            ], __('Homework retrieved successfully'));
        }

        // Get academic sessions with homework for children
        $query = AcademicSession::whereIn('student_id', $childIds)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->with([
                'academicTeacher.user',
                'academicSubscription',
                'student',
                'homeworkSubmissions',
            ]);

        // Paginate
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
                'file' => $session->homework_file ? asset('storage/'.$session->homework_file) : null,
                'is_assigned' => (bool) $session->homework_assigned,
                'status' => $currentStatus,
                'child' => [
                    'id' => $session->student_id,
                    'name' => $session->student?->full_name ?? $session->student?->name,
                ],
                'teacher' => $session->academicTeacher?->user ? [
                    'id' => $session->academicTeacher->user->id,
                    'name' => $session->academicTeacher->user->name,
                    'avatar' => $session->academicTeacher->user->avatar
                        ? asset('storage/'.$session->academicTeacher->user->avatar)
                        : null,
                ] : null,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submitted_at' => $submission->created_at?->toISOString(),
                    'grade' => $submission->grade,
                    'max_grade' => 100,
                    'feedback' => $submission->feedback,
                    'status' => $submission->submission_status?->value ?? $submission->status,
                ] : null,
                'due_date' => $session->homework_due_date?->toISOString(),
                'session_date' => $session->scheduled_at?->toISOString(),
            ];
        })->filter()->values()->toArray();

        // Calculate stats from all homework (not just current page)
        $allHomeworkQuery = AcademicSession::whereIn('student_id', $childIds)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->with('homeworkSubmissions')
            ->get();

        $stats = [
            'pending' => 0,
            'submitted' => 0,
            'graded' => 0,
        ];

        foreach ($allHomeworkQuery as $session) {
            $submission = $session->homeworkSubmissions->first();
            $isSubmitted = $submission !== null;
            $isGraded = $submission && $submission->grade !== null;

            if ($isGraded) {
                $stats['graded']++;
            } elseif ($isSubmitted) {
                $stats['submitted']++;
            } else {
                $stats['pending']++;
            }
        }

        return $this->success([
            'homework' => $homework,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'stats' => $stats,
        ], __('Homework retrieved successfully'));
    }

    /**
     * Get a specific homework assignment.
     */
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        if ($type !== 'academic') {
            return $this->error(
                __('Invalid homework type.'),
                400,
                'INVALID_TYPE'
            );
        }

        $childIds = $this->getLinkedChildIds($request);

        $session = AcademicSession::where('id', $id)
            ->whereIn('student_id', $childIds)
            ->whereNotNull('homework_description')
            ->with([
                'academicTeacher.user',
                'academicSubscription',
                'student',
                'homeworkSubmissions',
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
                'file' => $session->homework_file ? asset('storage/'.$session->homework_file) : null,
                'is_assigned' => (bool) $session->homework_assigned,
                'status' => match (true) {
                    $isGraded => 'graded',
                    $isSubmitted => 'submitted',
                    default => 'pending',
                },
                'child' => [
                    'id' => $session->student_id,
                    'name' => $session->student?->full_name ?? $session->student?->name,
                    'avatar' => $session->student?->avatar
                        ? asset('storage/'.$session->student->avatar)
                        : null,
                ],
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
                    'attachments' => $submission->student_files ?? [],
                    'submitted_at' => $submission->created_at?->toISOString(),
                    'grade' => $submission->grade,
                    'max_grade' => 100,
                    'feedback' => $submission->feedback,
                    'status' => $submission->submission_status?->value ?? $submission->status,
                ] : null,
                'due_date' => $session->homework_due_date?->toISOString(),
                'session_date' => $session->scheduled_at?->toISOString(),
            ],
        ], __('Homework retrieved successfully'));
    }

    /**
     * Get homework for a specific child.
     */
    public function childHomework(Request $request, int $childId): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        $childIds = $this->getLinkedChildIds($request);

        if (! in_array($childId, $childIds)) {
            return $this->error(__('Child not found.'), 404, 'CHILD_NOT_FOUND');
        }

        $status = $request->get('status');
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        $query = AcademicSession::where('student_id', $childId)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->with([
                'academicTeacher.user',
                'academicSubscription',
                'homeworkSubmissions',
            ]);

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
                'file' => $session->homework_file ? asset('storage/'.$session->homework_file) : null,
                'is_assigned' => (bool) $session->homework_assigned,
                'status' => $currentStatus,
                'teacher' => $session->academicTeacher?->user ? [
                    'id' => $session->academicTeacher->user->id,
                    'name' => $session->academicTeacher->user->name,
                ] : null,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submitted_at' => $submission->created_at?->toISOString(),
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                    'status' => $submission->submission_status?->value ?? $submission->status,
                ] : null,
                'due_date' => $session->homework_due_date?->toISOString(),
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
        ], __('Homework retrieved successfully'));
    }
}
