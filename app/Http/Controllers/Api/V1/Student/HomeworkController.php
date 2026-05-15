<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\HomeworkSubmissionStatus;
use App\Enums\SubscriptionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Homework\AcademicHomeworkDetailResource;
use App\Http\Resources\Api\V1\Homework\HomeworkSummaryResource;
use App\Http\Resources\Api\V1\Homework\InteractiveHomeworkDetailResource;
use App\Http\Resources\Api\V1\Homework\QuranHomeworkDetailResource;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseHomework;
use App\Models\InteractiveCourseHomeworkSubmission;
use App\Models\QuranSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Student homework API.
 *
 * The list endpoint paginates a heterogeneous union of three homework sources
 * (academic / quran / interactive) at the database level. Each item is shaped
 * by HomeworkSummaryResource using the `homework_type` discriminator set
 * before resource construction. The detail endpoint dispatches to one of
 * three Resource classes per type.
 */
class HomeworkController extends Controller
{
    use ApiResponses;

    private const TAB_UPCOMING = 'upcoming';

    private const TAB_PAST = 'past';

    private const TAB_ALL = 'all';

    /**
     * GET /api/v1/student/homework
     *
     * Query params:
     * - type=academic|quran|interactive (optional single-type filter)
     * - status=upcoming|past|all|<HomeworkSubmissionStatus value> (default: all)
     * - page, per_page (per_page clamped to [1, 50], default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfileId = $user->studentProfile?->id;
        $typeFilter = $request->query('type');
        $statusFilter = (string) $request->query('status', self::TAB_ALL);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));

        $unionQuery = $this->unionIndexQuery($user->id, $studentProfileId, $typeFilter);

        if ($unionQuery === null) {
            return $this->success([
                'homework' => [],
                'pagination' => $this->paginationPayload(0, $page, $perPage),
                'stats' => ['upcoming' => 0, 'past' => 0, 'total' => 0],
            ], __('api.success'));
        }

        $stats = $this->computeStats($user->id, $studentProfileId, $typeFilter);

        $tabFilter = $this->normaliseTabFilter($statusFilter);
        $rowsCount = $this->countForTab($user->id, $studentProfileId, $typeFilter, $tabFilter, $statusFilter);

        $rows = $this->fetchPage($user->id, $studentProfileId, $typeFilter, $tabFilter, $statusFilter, $perPage, $page);
        $items = $this->hydrateRows($rows, $user->id);

        $resources = $items->map(fn ($item) => (new HomeworkSummaryResource($item))->resolve($request));

        return $this->success([
            'homework' => $resources->values()->all(),
            'pagination' => $this->paginationPayload($rowsCount, $page, $perPage),
            'stats' => $stats,
        ], __('api.success'));
    }

    /**
     * GET /api/v1/student/homework/{type}/{id}
     */
    public function show(Request $request, string $type, string $id): JsonResponse
    {
        $user = $request->user();

        return match ($type) {
            'academic' => $this->showAcademic($request, $user, $id),
            'quran' => $this->showQuran($request, $user, $id),
            'interactive' => $this->showInteractive($request, $user, $id),
            default => $this->error(__('Invalid homework type.'), 400, 'INVALID_TYPE'),
        };
    }

    private function showAcademic(Request $request, $user, string $id): JsonResponse
    {
        $session = AcademicSession::query()
            ->where('id', $id)
            ->where('student_id', $user->id)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->with(['academicTeacher.user', 'academicSubscription'])
            ->first();

        if (! $session) {
            return $this->notFound(__('Homework not found.'));
        }

        $this->attachAcademicSubmissions(collect([$session]), $user->id);

        return $this->success([
            'homework' => (new AcademicHomeworkDetailResource($session))->resolve($request),
        ], __('api.success'));
    }

    /**
     * Eager-load student submissions for a set of AcademicSessions by direct
     * query (bypasses the hasManyThrough → ScopedToAcademy ambiguity on
     * `academy_id`) and attaches them as the `homeworkSubmissions` relation
     * so resources keep their existing access pattern.
     */
    private function attachAcademicSubmissions($sessions, int $userId): void
    {
        if ($sessions->isEmpty()) {
            return;
        }

        $sessionIds = $sessions->pluck('id')->all();

        $submissionsBySession = AcademicHomeworkSubmission::query()
            ->whereIn('academic_session_id', $sessionIds)
            ->where('student_id', $userId)
            ->get()
            ->groupBy('academic_session_id');

        foreach ($sessions as $session) {
            $session->setRelation(
                'homeworkSubmissions',
                $submissionsBySession->get($session->id, collect())
            );
        }
    }

    private function showQuran(Request $request, $user, string $id): JsonResponse
    {
        // Identify the session via either the QuranSession id or its sessionHomework id —
        // mobile passes the homework id (the list returns hw.id when available, falling
        // back to session id), so accept both.
        $session = QuranSession::query()
            ->where(function (Builder $q) use ($id) {
                $q->where('id', $id)
                    ->orWhereHas('sessionHomework', fn ($qh) => $qh->where('id', $id));
            })
            ->where(function (Builder $q) use ($user) {
                $q->where('student_id', $user->id)
                    ->orWhereHas('studentReports', fn ($qr) => $qr->where('student_id', $user->id));
            })
            ->whereHas('sessionHomework')
            ->with([
                'quranTeacher',
                'sessionHomework',
                'studentReports' => fn ($q) => $q->where('student_id', $user->id),
            ])
            ->first();

        if (! $session || ! $session->sessionHomework) {
            return $this->notFound(__('Homework not found.'));
        }

        return $this->success([
            'homework' => (new QuranHomeworkDetailResource($session))->resolve($request),
        ], __('api.success'));
    }

    private function showInteractive(Request $request, $user, string $id): JsonResponse
    {
        $studentProfileId = $user->studentProfile?->id;

        if (! $studentProfileId) {
            return $this->notFound(__('Homework not found.'));
        }

        $hw = InteractiveCourseHomework::query()
            ->where('id', $id)
            ->whereHas('session.course.enrollments', fn ($q) => $q->where('student_id', $studentProfileId))
            ->with([
                'session.course.assignedTeacher.user',
                'submissions' => fn ($q) => $q->where('student_id', $user->id),
            ])
            ->first();

        if (! $hw) {
            return $this->notFound(__('Homework not found.'));
        }

        return $this->success([
            'homework' => (new InteractiveHomeworkDetailResource($hw))->resolve($request),
        ], __('api.success'));
    }

    // ----------------------------------------------------------------------
    // Union-query helpers for index()
    // ----------------------------------------------------------------------

    /**
     * Build a union query of all three homework sources, projected to
     * (type, source_id, session_date). Returns null when no source applies
     * (e.g. interactive-only filter but the user has no student profile).
     *
     * Built on the underlying Query\Builder so global scopes (multi-tenancy,
     * soft deletes) survive the union — Eloquent unions silently drop
     * scopes applied via Eloquent::Builder beyond the first part.
     */
    private function unionIndexQuery(int $userId, ?int $studentProfileId, ?string $typeFilter): ?QueryBuilder
    {
        $parts = [];

        if (! $typeFilter || $typeFilter === 'academic') {
            $parts[] = $this->academicProjection($userId)->toBase();
        }

        if (! $typeFilter || $typeFilter === 'quran') {
            $parts[] = $this->quranProjection($userId)->toBase();
        }

        if ((! $typeFilter || $typeFilter === 'interactive') && $studentProfileId) {
            $parts[] = $this->interactiveProjection($studentProfileId)->toBase();
        }

        if (empty($parts)) {
            return null;
        }

        $base = array_shift($parts);
        foreach ($parts as $part) {
            $base->unionAll($part);
        }

        return $base;
    }

    private function academicProjection(int $userId): Builder
    {
        return AcademicSession::query()
            ->where('student_id', $userId)
            ->whereNotNull('homework_description')
            ->where('homework_description', '!=', '')
            ->select([
                new Expression("'academic' as homework_type"),
                'id as source_id',
                'scheduled_at as session_date',
            ]);
    }

    private function quranProjection(int $userId): Builder
    {
        return QuranSession::query()
            ->where(function (Builder $q) use ($userId) {
                $q->where('student_id', $userId)
                    ->orWhereHas('studentReports', fn ($qr) => $qr->where('student_id', $userId));
            })
            ->whereHas('sessionHomework')
            ->select([
                new Expression("'quran' as homework_type"),
                'id as source_id',
                'scheduled_at as session_date',
            ]);
    }

    private function interactiveProjection(int $studentProfileId): Builder
    {
        return InteractiveCourseHomework::query()
            ->whereHas('session.course.enrollments', fn ($q) => $q->where('student_id', $studentProfileId))
            ->where('is_active', true)
            ->join(
                'interactive_course_sessions',
                'interactive_course_homework.interactive_course_session_id',
                '=',
                'interactive_course_sessions.id'
            )
            ->select([
                new Expression("'interactive' as homework_type"),
                'interactive_course_homework.id as source_id',
                'interactive_course_sessions.scheduled_at as session_date',
            ]);
    }

    /**
     * Translate the `status` query param into a high-level tab when it maps
     * cleanly onto upcoming/past/all. Granular submission-status values
     * (`draft`, `submitted`, …) are passed through unchanged and handled by
     * fetchPage() / countForTab() via a post-hydration filter.
     */
    private function normaliseTabFilter(string $status): string
    {
        if (in_array($status, [self::TAB_UPCOMING, self::TAB_PAST, self::TAB_ALL], true)) {
            return $status;
        }

        return self::TAB_ALL;
    }

    private function countForTab(int $userId, ?int $studentProfileId, ?string $typeFilter, string $tab, string $rawStatus): int
    {
        if ($tab === self::TAB_ALL && $rawStatus === self::TAB_ALL) {
            $union = $this->unionIndexQuery($userId, $studentProfileId, $typeFilter);

            return $union === null ? 0 : DB::query()->fromSub($union, 'h')->count();
        }

        // For granular status filters or upcoming/past tabs, materialise the
        // full set and filter in PHP — accurate but only after fetching the
        // (type, id) pairs. We then count the filtered result.
        $rows = $this->loadAllRows($userId, $studentProfileId, $typeFilter);
        $filtered = $this->applyRowFilter($rows, $userId, $tab, $rawStatus);

        return $filtered->count();
    }

    private function fetchPage(
        int $userId,
        ?int $studentProfileId,
        ?string $typeFilter,
        string $tab,
        string $rawStatus,
        int $perPage,
        int $page,
    ): Collection {
        if ($tab === self::TAB_ALL && $rawStatus === self::TAB_ALL) {
            $union = $this->unionIndexQuery($userId, $studentProfileId, $typeFilter);

            if ($union === null) {
                return collect();
            }

            return DB::query()
                ->fromSub($union, 'h')
                ->orderByDesc('session_date')
                ->orderByDesc('source_id')
                ->limit($perPage)
                ->offset(($page - 1) * $perPage)
                ->get();
        }

        $rows = $this->loadAllRows($userId, $studentProfileId, $typeFilter);
        $filtered = $this->applyRowFilter($rows, $userId, $tab, $rawStatus)->values();

        return $filtered->slice(($page - 1) * $perPage, $perPage)->values();
    }

    private function loadAllRows(int $userId, ?int $studentProfileId, ?string $typeFilter): Collection
    {
        $union = $this->unionIndexQuery($userId, $studentProfileId, $typeFilter);

        if ($union === null) {
            return collect();
        }

        return DB::query()
            ->fromSub($union, 'h')
            ->orderByDesc('session_date')
            ->orderByDesc('source_id')
            ->get();
    }

    private function applyRowFilter(Collection $rows, int $userId, string $tab, string $rawStatus): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $items = $this->hydrateRows($rows, $userId);

        return $items->filter(function ($item) use ($tab, $rawStatus) {
            $isUpcoming = $this->classifyTab($item) === self::TAB_UPCOMING;

            if ($tab === self::TAB_UPCOMING) {
                return $isUpcoming;
            }

            if ($tab === self::TAB_PAST) {
                return ! $isUpcoming;
            }

            // Granular status filter: match raw submission_status value.
            $status = $this->itemSubmissionStatus($item);

            return $status === $rawStatus;
        });
    }

    /**
     * Hydrate raw union rows into their source models with the relationships
     * the resource needs. Groups by type to issue one query per type, then
     * re-orders into the original union order so pagination stays stable.
     */
    private function hydrateRows(Collection $rows, int $userId): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $byType = $rows->groupBy('homework_type');
        $models = [];

        if ($byType->has('academic')) {
            $ids = $byType['academic']->pluck('source_id')->all();
            $sessions = AcademicSession::query()
                ->whereIn('id', $ids)
                ->with(['academicTeacher.user', 'academicSubscription'])
                ->get();
            $this->attachAcademicSubmissions($sessions, $userId);
            $models['academic'] = $sessions->keyBy('id');
        }

        if ($byType->has('quran')) {
            $ids = $byType['quran']->pluck('source_id')->all();
            $models['quran'] = QuranSession::query()
                ->whereIn('id', $ids)
                ->with([
                    'quranTeacher',
                    'sessionHomework',
                    'studentReports' => fn ($q) => $q->where('student_id', $userId),
                ])
                ->get()
                ->keyBy('id');
        }

        if ($byType->has('interactive')) {
            $ids = $byType['interactive']->pluck('source_id')->all();
            $models['interactive'] = InteractiveCourseHomework::query()
                ->whereIn('id', $ids)
                ->with([
                    'session.course.assignedTeacher.user',
                    'submissions' => fn ($q) => $q->where('student_id', $userId),
                ])
                ->get()
                ->keyBy('id');
        }

        return $rows->map(function ($row) use ($models) {
            $type = $row->homework_type;
            $model = $models[$type][$row->source_id] ?? null;

            if ($model === null) {
                return null;
            }

            $model->homework_type = $type;

            return $model;
        })->filter()->values();
    }

    private function classifyTab($item): string
    {
        $type = $item->homework_type;

        return match ($type) {
            'academic' => $this->classifyAcademic($item),
            'quran' => $this->classifyQuran($item),
            'interactive' => $this->classifyInteractive($item),
        };
    }

    private function classifyAcademic(AcademicSession $session): string
    {
        $submission = $session->homeworkSubmissions->first();

        if (! $submission) {
            return self::TAB_UPCOMING;
        }

        $status = $this->castStatus($submission->submission_status);

        return match ($status) {
            HomeworkSubmissionStatus::DRAFT,
            HomeworkSubmissionStatus::PENDING,
            HomeworkSubmissionStatus::REVISION_REQUESTED => self::TAB_UPCOMING,
            default => self::TAB_PAST,
        };
    }

    private function classifyQuran(QuranSession $session): string
    {
        $report = $session->studentReports->first();

        return ($report && $report->evaluated_at !== null) ? self::TAB_PAST : self::TAB_UPCOMING;
    }

    private function classifyInteractive(InteractiveCourseHomework $hw): string
    {
        $submission = $hw->submissions->first();

        if ($submission) {
            $status = $this->castStatus($submission->submission_status);

            if ($status === HomeworkSubmissionStatus::DRAFT
                || $status === HomeworkSubmissionStatus::REVISION_REQUESTED) {
                return self::TAB_UPCOMING;
            }

            return self::TAB_PAST;
        }

        if ($hw->due_date && $hw->due_date->isPast() && ! $hw->allow_late_submissions) {
            return self::TAB_PAST;
        }

        return self::TAB_UPCOMING;
    }

    private function itemSubmissionStatus($item): string
    {
        $type = $item->homework_type;

        return match ($type) {
            'academic' => $this->castStatus(
                $item->homeworkSubmissions->first()?->submission_status
            )->value,
            'quran' => $item->studentReports->first()?->evaluated_at
                ? HomeworkSubmissionStatus::GRADED->value
                : HomeworkSubmissionStatus::PENDING->value,
            'interactive' => $this->castStatus(
                $item->submissions->first()?->submission_status
            )->value,
        };
    }

    private function castStatus($raw): HomeworkSubmissionStatus
    {
        if ($raw === null) {
            return HomeworkSubmissionStatus::PENDING;
        }

        return $raw instanceof HomeworkSubmissionStatus
            ? $raw
            : HomeworkSubmissionStatus::from($raw);
    }

    private function paginationPayload(int $total, int $page, int $perPage): array
    {
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => ($page * $perPage) < $total,
        ];
    }

    /**
     * Stats are computed over the unfiltered base set (per the contract) so
     * the tab counters stay correct regardless of which tab is active.
     */
    private function computeStats(int $userId, ?int $studentProfileId, ?string $typeFilter): array
    {
        $rows = $this->loadAllRows($userId, $studentProfileId, $typeFilter);

        if ($rows->isEmpty()) {
            return ['upcoming' => 0, 'past' => 0, 'total' => 0];
        }

        $items = $this->hydrateRows($rows, $userId);
        $total = $items->count();
        $upcoming = $items->filter(fn ($item) => $this->classifyTab($item) === self::TAB_UPCOMING)->count();

        return [
            'upcoming' => $upcoming,
            'past' => $total - $upcoming,
            'total' => $total,
        ];
    }

    // ----------------------------------------------------------------------
    // Write paths (preserved from the pre-refactor flow; not in Phase 1 scope)
    // ----------------------------------------------------------------------

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

        if ($type === SubscriptionType::QURAN->value) {
            return $this->error(__('Quran homework does not support submissions.'), 400, 'NOT_SUBMITTABLE');
        }

        if (! in_array($type, ['academic', 'interactive'])) {
            return $this->error(__('Invalid homework type.'), 400, 'INVALID_TYPE');
        }

        $attachments = $this->storeAttachments($request, $user->id, 'homework-submissions');

        if ($type === 'interactive') {
            return $this->submitInteractiveHomework($user, $id, $request->content, $attachments);
        }

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
            ->whereHas('session.course.enrollments', fn ($q) => $q->where('student_id', $studentProfileId))
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

        $existingSubmission = $session->homeworkSubmissions()
            ->where('student_id', $user->id)
            ->where('submission_status', '!=', HomeworkSubmissionStatus::DRAFT)
            ->first();

        if ($existingSubmission) {
            return $this->error(__('Homework already submitted. Cannot save as draft.'), 400, 'ALREADY_SUBMITTED');
        }

        $attachments = $this->storeAttachments($request, $user->id, 'homework-drafts');

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

        if ($existingSubmission->submission_status !== HomeworkSubmissionStatus::REVISION_REQUESTED) {
            return $this->error(
                __('Revision not requested for this submission.'),
                400,
                'REVISION_NOT_REQUESTED'
            );
        }

        $attachments = $this->storeAttachments($request, $user->id, 'homework-submissions');

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

    private function storeAttachments(Request $request, int $userId, string $folder): array
    {
        if (! $request->hasFile('attachments')) {
            return [];
        }

        $attachments = [];

        foreach ($request->file('attachments') as $file) {
            $path = $file->store($folder.'/'.$userId, 'public');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return $attachments;
    }
}
