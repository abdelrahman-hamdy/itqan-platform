<?php

namespace App\Http\Controllers\Api\V1\Teacher\Calendar;

use App\Enums\SessionDuration;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Teacher\Calendar\BatchScheduleSessionRequest;
use App\Http\Requests\Api\V1\Teacher\Calendar\CheckConflictsRequest;
use App\Http\Requests\Api\V1\Teacher\Calendar\RescheduleSessionRequest;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\AcademySettings;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\User;
use App\Services\AcademyContextService;
use App\Services\Calendar\SessionStrategyFactory;
use App\Services\CalendarService;
use App\Services\SessionSettingsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Teacher calendar API endpoints.
 *
 * All endpoints operate on the "effective teacher" resolved by the
 * ResolveCalendarContext middleware. Teachers always act on their own
 * calendar. Supervisors/admins (future scope) act on any teacher they
 * are authorized to manage via the teacher_id param.
 */
class CalendarController extends Controller
{
    use ApiResponses;

    public function __construct(
        private CalendarService $calendarService,
        private SessionStrategyFactory $strategyFactory,
        private SessionSettingsService $sessionSettings,
    ) {}

    /**
     * Per-request cache of the academy's teacher-reschedule deadline — the
     * same value for every event in a month fetch, so we resolve it once.
     */
    private ?int $cachedDeadlineHours = null;

    /**
     * GET /calendar/events?start=&end=
     *
     * Returns calendar events in the given date range for the effective teacher.
     * Response shape mirrors EventFormattingService output with two added
     * extended props used by mobile focus mode and quick actions:
     *   - can_delete (bool)
     *   - course_id (int|null)  // for course sessions only
     */
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $teacher = $this->effectiveTeacher($request);
        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end']);

        $deadlineHours = $this->resolveRescheduleDeadlineHours($teacher);

        $events = $this->calendarService
            ->getUserCalendar($teacher, $start, $end)
            ->map(fn ($event) => $this->enrichEventForMobile($event, $deadlineHours))
            ->values();

        return $this->success([
            'events' => $events,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'timezone' => AcademyContextService::getTimezone(),
            ],
            'reschedule_deadline_hours' => $deadlineHours,
            'total' => $events->count(),
        ]);
    }

    /**
     * GET /calendar/schedulable-items?tab=group|individual|trials|private_lesson|interactive_course
     *
     * When tab is omitted, returns the full tab configuration (labels + items).
     */
    public function schedulableItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tab' => ['nullable', 'string'],
        ]);

        $teacher = $this->effectiveTeacher($request);
        $teacherType = $request->attributes->get('effective_teacher_type');

        $strategy = $this->strategyFactory->make($teacherType)->forUser($teacher);
        $tabs = $strategy->getTabConfiguration();

        // Return items for a specific tab
        if (! empty($validated['tab'])) {
            if (! isset($tabs[$validated['tab']])) {
                return $this->error(__('calendar.invalid_tab'), 422, 'INVALID_TAB');
            }

            $method = $tabs[$validated['tab']]['items_method'] ?? null;

            if (! $method || ! method_exists($strategy, $method)) {
                return $this->error(__('calendar.invalid_tab_method'), 422, 'INVALID_TAB_METHOD');
            }

            $items = $strategy->{$method}()
                ->map(fn ($item) => $this->enrichItemForMobile(is_array($item) ? $item : (array) $item))
                ->values();

            return $this->success([
                'tab' => $validated['tab'],
                'label' => $tabs[$validated['tab']]['label'] ?? null,
                'items' => $items,
            ]);
        }

        // No tab specified — return full tab configuration with labels
        $tabList = collect($tabs)->map(fn ($config, $key) => [
            'key' => $key,
            'label' => $config['label'] ?? $key,
            'icon' => $config['icon'] ?? null,
        ])->values();

        return $this->success([
            'teacher_type' => $teacherType,
            'tabs' => $tabList,
        ]);
    }

    /**
     * POST /calendar/check-conflicts
     *
     * Dry-run conflict check. Returns conflicts for the proposed slot.
     */
    public function checkConflicts(CheckConflictsRequest $request): JsonResponse
    {
        $teacher = $this->effectiveTeacher($request);
        $validated = $request->validated();

        $academyTz = AcademyContextService::getTimezone();
        $startTime = Carbon::parse($validated['date'], $academyTz)
            ->setTimeFromTimeString($validated['time']);
        $startTime = AcademyContextService::toUtcForStorage($startTime);
        $endTime = $startTime->copy()->addMinutes((int) $validated['duration_minutes']);

        $conflicts = $this->calendarService->checkConflicts(
            $teacher,
            $startTime,
            $endTime,
            $validated['exclude_type'] ?? null,
            isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null,
        );

        return $this->success([
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts->values(),
        ]);
    }

    /**
     * POST /calendar/schedule
     *
     * Batch-create sessions from the scheduling panel payload.
     */
    public function schedule(BatchScheduleSessionRequest $request): JsonResponse
    {
        $teacher = $this->effectiveTeacher($request);
        $teacherType = $request->attributes->get('effective_teacher_type');
        $validated = $request->validated();

        try {
            $strategy = $this->strategyFactory->make($teacherType)->forUser($teacher);

            $item = ['id' => $validated['item_id']];
            $validator = $strategy->getValidator($validated['item_type'], $item);

            $dayResult = $validator->validateDaySelection($validated['schedule_days'] ?? []);
            if (! $dayResult->isValid()) {
                return $this->error($dayResult->getMessage(), 422, 'INVALID_SCHEDULE_DAYS');
            }

            $countResult = $validator->validateSessionCount($validated['session_count']);
            if (! $countResult->isValid()) {
                return $this->error($countResult->getMessage(), 422, 'INVALID_SESSION_COUNT');
            }

            $startDate = Carbon::parse($validated['schedule_start_date']);
            $daysCount = max(count($validated['schedule_days'] ?? []), 1);
            $weeksNeeded = (int) ceil($validated['session_count'] / $daysCount);

            $dateResult = $validator->validateDateRange($startDate, $weeksNeeded);
            if (! $dateResult->isValid()) {
                return $this->error($dateResult->getMessage(), 422, 'INVALID_DATE_RANGE');
            }

            $pacingResult = $validator->validateWeeklyPacing($validated['schedule_days'] ?? [], $weeksNeeded);
            if (! $pacingResult->isValid()) {
                return $this->error($pacingResult->getMessage(), 422, 'INVALID_WEEKLY_PACING');
            }

            $strategy->createSchedule($validated, $validator);

            return $this->created(null, __('calendar.schedule_created_successfully'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422, 'INVALID_ARGUMENT');
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /calendar/sessions/{type}/{id}/reschedule
     *
     * Reschedule a single session to a new time (status + deadline gated).
     */
    public function reschedule(RescheduleSessionRequest $request, string $type, int $id): JsonResponse
    {
        $teacher = $this->effectiveTeacher($request);
        $session = $this->resolveSessionForTeacher($type, $id, $teacher);

        if (! $session) {
            return $this->notFound(__('teacher.calendar.session_not_found'));
        }

        $gateError = $this->gateSessionMutation($session);
        if ($gateError) {
            return $gateError;
        }

        $newScheduledAt = AcademyContextService::toUtcForStorage(
            AcademyContextService::parseInAcademyTimezone($request->validated()['scheduled_at'])
        );

        $oldScheduledAt = $session->scheduled_at;

        $session->update([
            'scheduled_at' => $newScheduledAt,
            'rescheduled_from' => $oldScheduledAt,
            'rescheduled_to' => $newScheduledAt,
        ]);

        return $this->success(null, __('teacher.calendar.reschedule_success'));
    }

    /**
     * PUT /calendar/sessions/{type}/{id}
     *
     * Update one or more of: scheduled_at, duration_minutes, teacher_notes.
     * Status-gated like web updateSession.
     */
    public function updateSession(Request $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'duration_minutes' => ['nullable', 'integer', Rule::in(SessionDuration::values())],
            'teacher_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacher = $this->effectiveTeacher($request);
        $session = $this->resolveSessionForTeacher($type, $id, $teacher);

        if (! $session) {
            return $this->notFound(__('teacher.calendar.session_not_found'));
        }

        $gateError = $this->gateSessionMutation($session);
        if ($gateError) {
            return $gateError;
        }

        $updateData = [];

        if (isset($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = AcademyContextService::toUtcForStorage(
                AcademyContextService::parseInAcademyTimezone($validated['scheduled_at'])
            );
        }
        if (isset($validated['duration_minutes'])) {
            $updateData['duration_minutes'] = $validated['duration_minutes'];
        }
        if (array_key_exists('teacher_notes', $validated)) {
            $updateData['teacher_notes'] = $validated['teacher_notes'];
        }

        if (! empty($updateData)) {
            $session->update($updateData);
        }

        return $this->success(null, __('teacher.calendar.session_updated'));
    }

    /**
     * GET /calendar/recommendations?item_id=&item_type=
     *
     * Delegates to the entity validator's getRecommendations() so the mobile
     * scheduling form can surface "X days/week recommended" hints inline.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['nullable', 'integer'],
            'item_id' => ['required', 'integer'],
            'item_type' => ['required', 'string', 'in:group,individual,trial,private_lesson,interactive_course'],
        ]);

        $teacher = $this->effectiveTeacher($request);
        $teacherType = $request->attributes->get('effective_teacher_type');

        try {
            $strategy = $this->strategyFactory->make($teacherType)->forUser($teacher);
            $item = ['id' => $validated['item_id']];
            $validator = $strategy->getValidator($validated['item_type'], $item);
            $recommendations = $validator->getRecommendations();

            return $this->success([
                'recommendations' => $recommendations,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422, 'INVALID_ARGUMENT');
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422, 'RECOMMENDATIONS_FAILED');
        }
    }

    /**
     * DELETE /calendar/schedulable-items/{itemType}/{itemId}/future-sessions
     *
     * Remove all future scheduled sessions for a given entity (batch).
     * Mirrors the web "remove scheduled sessions" action.
     */
    public function removeFutureSessions(Request $request, string $itemType, int $itemId): JsonResponse
    {
        $allowed = ['group', 'individual', 'trial', 'private_lesson', 'interactive_course'];

        if (! in_array($itemType, $allowed, true)) {
            return $this->error(__('calendar.invalid_item_type'), 422, 'INVALID_ITEM_TYPE');
        }

        $teacher = $this->effectiveTeacher($request);
        $teacherType = $request->attributes->get('effective_teacher_type');

        try {
            $strategy = $this->strategyFactory->make($teacherType)->forUser($teacher);
            $removedCount = $strategy->removeScheduledSessions($itemType, $itemId);

            return $this->success([
                'deleted_count' => $removedCount,
            ], __('teacher.calendar.sessions_removed_success', ['count' => $removedCount]));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422, 'INVALID_ARGUMENT');
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function effectiveTeacher(Request $request): User
    {
        return $request->attributes->get('effective_teacher');
    }

    /**
     * Resolve a session the given teacher owns.
     */
    private function resolveSessionForTeacher(string $type, int $id, User $teacher): ?BaseSession
    {
        return match ($type) {
            'quran' => QuranSession::where('id', $id)
                ->where('quran_teacher_id', $teacher->id)
                ->first(),
            'academic' => AcademicSession::where('id', $id)
                ->whereHas('academicTeacher', fn ($q) => $q->where('user_id', $teacher->id))
                ->first(),
            'interactive' => InteractiveCourseSession::where('id', $id)
                ->whereHas('course.assignedTeacher', fn ($q) => $q->where('user_id', $teacher->id))
                ->first(),
            default => null,
        };
    }

    /**
     * Status + deadline gate shared by reschedule and updateSession.
     */
    private function gateSessionMutation(BaseSession $session): ?JsonResponse
    {
        $status = $session->status instanceof SessionStatus
            ? $session->status
            : SessionStatus::tryFrom($session->status);

        if (! $status || ! in_array($status, [SessionStatus::SCHEDULED, SessionStatus::READY], true)) {
            return $this->error(__('teacher.calendar.cannot_edit_status'), 422, 'INVALID_SESSION_STATUS');
        }

        if ($this->sessionSettings->isRescheduleDeadlinePassed($session)) {
            $hours = $this->sessionSettings->getTeacherRescheduleDeadlineHours($session);

            return $this->error(
                __('scheduling.reschedule_deadline_passed', ['hours' => $hours]),
                422,
                'RESCHEDULE_DEADLINE_PASSED',
            );
        }

        return null;
    }

    /**
     * Add mobile-only extended props (can_delete, course_id, deadline hours)
     * to the event array.
     */
    private function enrichEventForMobile(array $event, int $deadlineHours): array
    {
        $source = $event['source'] ?? null;
        $metadata = $event['metadata'] ?? [];

        // Teachers can't delete sessions on the web — only admins/supervisors can.
        // For the mobile quick-actions menu we expose can_delete=false for teachers.
        $event['can_delete'] = false;
        $event['reschedule_deadline_hours'] = $deadlineHours;

        // Ensure course_id is present on course sessions (not currently surfaced)
        if ($source === 'course_session' && ! isset($metadata['course_id'])) {
            $metadata['course_id'] = $event['course_id'] ?? null;
        }

        $event['metadata'] = $metadata;

        return $event;
    }

    /**
     * Add mobile-only extended props to a schedulable item. Today we compute
     * max_sessions_allowed — the dynamic cap mobile uses to bound the session
     * stepper before submit. Mirrors the validators' runtime limits.
     */
    private function enrichItemForMobile(array $item): array
    {
        $item['max_sessions_allowed'] = $this->computeMaxSessionsAllowed($item);

        return $item;
    }

    /** Per-submission upper bound across all validators. */
    private const SCHEDULE_BATCH_HARD_CAP = 100;

    /** Group circles project forward 12 months of monthly_sessions. */
    private const GROUP_LOOKAHEAD_MONTHS = 12;

    private function computeMaxSessionsAllowed(array $item): int
    {
        $type = $item['type'] ?? null;
        $scheduled = (int) ($item['sessions_scheduled'] ?? 0);
        $total = (int) ($item['sessions_count'] ?? $item['total_sessions'] ?? 0);
        $remaining = isset($item['sessions_remaining'])
            ? (int) $item['sessions_remaining']
            : max(0, $total - $scheduled);

        return match ($type) {
            'group' => min(
                self::SCHEDULE_BATCH_HARD_CAP,
                max(1, (int) ($item['monthly_sessions'] ?? 4))
                    * self::GROUP_LOOKAHEAD_MONTHS,
            ),
            'individual', 'private_lesson' => max(0, $remaining),
            'trial' => 1,
            'interactive_course' => max(0, $total - $scheduled),
            default => self::SCHEDULE_BATCH_HARD_CAP,
        };
    }

    /**
     * Resolve the teacher-reschedule deadline hours for a given teacher's
     * academy. Falls back to the default (24) when settings are missing.
     * Memoized per-request — events() iterates many rows but every one
     * shares the same academy setting.
     */
    private function resolveRescheduleDeadlineHours(User $teacher): int
    {
        if ($this->cachedDeadlineHours !== null) {
            return $this->cachedDeadlineHours;
        }

        $academyId = $teacher->academy_id;
        if (! $academyId) {
            return $this->cachedDeadlineHours = 24;
        }

        $settings = AcademySettings::query()
            ->where('academy_id', $academyId)
            ->first();

        return $this->cachedDeadlineHours =
            (int) ($settings?->teacher_reschedule_deadline_hours ?? 24);
    }
}
