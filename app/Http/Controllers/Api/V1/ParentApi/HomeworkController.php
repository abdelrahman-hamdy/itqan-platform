<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseHomework;
use App\Models\ParentStudentRelationship;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    use ApiResponses;

    /**
     * Get linked children StudentProfile IDs for the parent.
     * These are StudentProfile.id values (NOT User.id values).
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
     * Get linked children User IDs for the parent.
     * These are users.id values, required for AcademicSession.student_id and QuranSession.student_id queries.
     */
    private function getLinkedChildUserIds(int $parentProfileId): array
    {
        return ParentStudentRelationship::where('parent_id', $parentProfileId)
            ->with('student.user')
            ->get()
            ->map(fn ($r) => $r->student->user?->id)
            ->filter()
            ->values()
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
        // Resolve User IDs from StudentProfile IDs (AcademicSession.student_id = users.id)
        $childUserIds = $this->getLinkedChildUserIds($parentProfile->id);
        $selectedChildId = $request->get('child_id');
        $status = $request->get('status'); // pending, submitted, graded
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        // Filter by specific child if provided
        if ($selectedChildId && in_array($selectedChildId, $childIds)) {
            // Resolve user_id for the selected child
            $selectedProfile = ParentStudentRelationship::where('parent_id', $parentProfile->id)
                ->where('student_id', $selectedChildId)
                ->with('student.user')
                ->first();
            $childUserIds = $selectedProfile?->student?->user?->id
                ? [$selectedProfile->student->user->id]
                : [];
            $childIds = [$selectedChildId];
        }

        if (empty($childIds) || empty($childUserIds)) {
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

        $typeFilter = $request->get('type'); // quran, academic, interactive
        $homework = collect();

        // Academic homework
        if (! $typeFilter || $typeFilter === 'academic') {
            $academicSessions = AcademicSession::whereIn('student_id', $childUserIds)
                ->whereNotNull('homework_description')
                ->where('homework_description', '!=', '')
                ->with(['academicTeacher.user', 'academicSubscription', 'student', 'homeworkSubmissions'])
                ->orderBy('scheduled_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($academicSessions as $session) {
                $submission = $session->homeworkSubmissions->first();
                $currentStatus = $this->resolveStatus($submission);

                if ($status && $currentStatus !== $status) {
                    continue;
                }

                $homework->push([
                    'id' => $session->id,
                    'type' => 'academic',
                    'session_id' => $session->id,
                    'title' => $session->title ?? __('واجب منزلي'),
                    'subject' => $session->academicSubscription?->subject_name,
                    'description' => $session->homework_description,
                    'file' => $session->homework_file ? asset('storage/'.$session->homework_file) : null,
                    'is_assigned' => (bool) $session->homework_assigned,
                    'status' => $currentStatus,
                    'can_submit' => true,
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
                ]);
            }
        }

        // Quran homework (view-only)
        if (! $typeFilter || $typeFilter === 'quran') {
            $quranSessions = QuranSession::whereIn('student_id', $childUserIds)
                ->whereHas('sessionHomework')
                ->with(['quranTeacher', 'sessionHomework', 'student'])
                ->orderBy('scheduled_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($quranSessions as $session) {
                $hw = $session->sessionHomework;
                if (! $hw) {
                    continue;
                }
                if ($status && $status !== 'pending') {
                    continue;
                }

                $homework->push([
                    'id' => $hw->id,
                    'type' => 'quran',
                    'session_id' => $session->id,
                    'title' => $session->title ?? __('واجب قرآني'),
                    'subject' => __('القرآن الكريم'),
                    'description' => $hw->additional_instructions,
                    'is_assigned' => true,
                    'status' => 'pending',
                    'can_submit' => false,
                    'child' => [
                        'id' => $session->student_id,
                        'name' => $session->student?->full_name ?? $session->student?->name,
                    ],
                    'teacher' => $session->quranTeacher ? [
                        'id' => $session->quranTeacher->id,
                        'name' => $session->quranTeacher->name,
                    ] : null,
                    'submission' => null,
                    'session_date' => $session->scheduled_at?->toISOString(),
                    'quran_details' => [
                        'has_new_memorization' => $hw->has_new_memorization,
                        'new_memorization_surah' => $hw->new_memorization_surah,
                        'new_memorization_pages' => $hw->new_memorization_pages,
                        'has_review' => $hw->has_review,
                        'review_surah' => $hw->review_surah,
                        'review_pages' => $hw->review_pages,
                        'due_date' => $hw->due_date?->toDateString(),
                    ],
                ]);
            }
        }

        // Interactive course homework
        if (! $typeFilter || $typeFilter === 'interactive') {
            $interactiveHomework = InteractiveCourseHomework::whereHas('session.course.enrollments', function ($q) use ($childIds) {
                $q->whereIn('student_id', $childIds);
            })
                ->with(['session.course.assignedTeacher.user', 'submissions'])
                ->where('is_active', true)
                ->orderBy('due_date', 'asc')
                ->limit(100)
                ->get();

            foreach ($interactiveHomework as $hw) {
                $submission = $hw->submissions->first();
                $currentStatus = $this->resolveStatus($submission);

                if ($status && $currentStatus !== $status) {
                    continue;
                }

                $teacher = $hw->session?->course?->assignedTeacher?->user;
                $homework->push([
                    'id' => $hw->id,
                    'type' => 'interactive',
                    'session_id' => $hw->interactive_course_session_id,
                    'title' => $hw->title ?? __('واجب منزلي'),
                    'subject' => $hw->session?->course?->title,
                    'description' => $hw->description,
                    'is_assigned' => true,
                    'status' => $currentStatus,
                    'can_submit' => true,
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
                    'due_date' => $hw->due_date?->toISOString(),
                    'session_date' => $hw->session?->scheduled_at?->toISOString(),
                ]);
            }
        }

        // Sort by session date descending
        $sorted = $homework->sortByDesc('session_date')->values();

        // Paginate
        $page = (int) $request->get('page', 1);
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

    private function resolveStatus($submission): string
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
    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (! $parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        if (! in_array($type, ['academic', 'quran', 'interactive'])) {
            return $this->error(__('Invalid homework type.'), 400, 'INVALID_TYPE');
        }

        $childUserIds = $this->getLinkedChildUserIds($parentProfile->id);

        if ($type === 'quran') {
            $session = QuranSession::where('id', $id)
                ->whereIn('student_id', $childUserIds)
                ->whereHas('sessionHomework')
                ->with(['quranTeacher', 'sessionHomework', 'student'])
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
                    'title' => $session->title ?? __('واجب قرآني'),
                    'subject' => __('القرآن الكريم'),
                    'description' => $hw->additional_instructions,
                    'is_assigned' => true,
                    'status' => 'pending',
                    'can_submit' => false,
                    'child' => [
                        'id' => $session->student_id,
                        'name' => $session->student?->full_name ?? $session->student?->name,
                    ],
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
                ],
            ], __('Homework retrieved successfully'));
        }

        if ($type === 'interactive') {
            $childIds = $this->getLinkedChildIds($request);
            $hw = InteractiveCourseHomework::where('id', $id)
                ->whereHas('session.course.enrollments', function ($q) use ($childIds) {
                    $q->whereIn('student_id', $childIds);
                })
                ->with(['session.course.assignedTeacher.user', 'submissions'])
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
                    'title' => $hw->title ?? __('واجب منزلي'),
                    'subject' => $hw->session?->course?->title,
                    'description' => $hw->description,
                    'instructions' => $hw->instructions,
                    'is_assigned' => true,
                    'status' => $this->resolveStatus($submission),
                    'can_submit' => true,
                    'due_date' => $hw->due_date?->toISOString(),
                    'max_score' => $hw->max_score,
                    'teacher' => $teacher ? [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
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

        // Academic
        $session = AcademicSession::where('id', $id)
            ->whereIn('student_id', $childUserIds)
            ->whereNotNull('homework_description')
            ->with(['academicTeacher.user', 'academicSubscription', 'student', 'homeworkSubmissions'])
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
                'title' => $session->title ?? __('واجب منزلي'),
                'subject' => $session->academicSubscription?->subject_name,
                'description' => $session->homework_description,
                'file' => $session->homework_file ? asset('storage/'.$session->homework_file) : null,
                'is_assigned' => (bool) $session->homework_assigned,
                'status' => $this->resolveStatus($submission),
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

        // Resolve the User ID for this specific child (AcademicSession.student_id = users.id)
        $childRelationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $childId)
            ->with('student.user')
            ->first();
        $childUserId = $childRelationship?->student?->user?->id;

        if (! $childUserId) {
            return $this->error(__('Child not found.'), 404, 'CHILD_NOT_FOUND');
        }

        $status = $request->get('status');
        $perPage = min(
            (int) $request->get('per_page', config('api.pagination.default_per_page', 15)),
            config('api.pagination.max_per_page', 50)
        );

        // AcademicSession.student_id = users.id, so use $childUserId
        $query = AcademicSession::where('student_id', $childUserId)
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
