<?php

namespace App\Services\Calendar;

use App\Enums\CircleEnrollmentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\TrialRequestStatus;
use App\Filament\Shared\Widgets\CalendarColorLegendWidget;
use App\Filament\Shared\Widgets\UnifiedCalendarWidget;
use App\Models\QuranCircle;
use App\Models\QuranCircleSchedule;
use App\Models\QuranIndividualCircle;
use App\Models\QuranTrialRequest;
use App\Services\AcademyContextService;
use App\Services\Scheduling\Validators\GroupCircleValidator;
use App\Services\Scheduling\Validators\IndividualCircleValidator;
use App\Services\Scheduling\Validators\ScheduleValidatorInterface;
use App\Services\Scheduling\Validators\TrialSessionValidator;
use App\Services\SessionManagementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Quran teacher session strategy
 *
 * Handles calendar operations for Quran teachers:
 * - Group circles
 * - Individual circles
 * - Trial sessions
 */
class QuranSessionStrategy extends AbstractSessionStrategy
{
    public function __construct(
        private SessionManagementService $sessionService
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getSchedulableItems(): Collection
    {
        // Returns all schedulable items across all tabs
        // Not used in this implementation - we fetch items per tab
        return collect();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabConfiguration(): array
    {
        return [
            'group' => [
                'label' => __('calendar.strategy.group_circles'),
                'icon' => 'heroicon-m-user-group',
                'items_method' => 'getGroupCircles',
            ],
            'individual' => [
                'label' => __('calendar.strategy.individual_circles'),
                'icon' => 'heroicon-m-user',
                'items_method' => 'getIndividualCircles',
            ],
            'trials' => [
                'label' => __('calendar.strategy.trial_sessions'),
                'icon' => 'heroicon-m-academic-cap',
                'items_method' => 'getTrialRequests',
            ],
        ];
    }

    /**
     * Get group circles for the teacher
     */
    public function getGroupCircles(): Collection
    {
        $userId = $this->getTargetUserId();
        if (! $userId) {
            return collect();
        }

        return QuranCircle::where('quran_teacher_id', $userId)
            ->where('status', true)
            ->with(['sessions' => function ($query) {
                $now = AcademyContextService::nowInAcademyTimezone();
                $query->where('scheduled_at', '>=', $now->startOfWeek())
                    ->where('scheduled_at', '<=', $now->copy()->addMonths(2));
            }, 'schedule'])
            ->get()
            ->map(function ($circle) {
                $schedule = $circle->schedule;

                // Use eager-loaded sessions collection to avoid N+1 queries
                $allSessions = $circle->sessions;
                $sessionsCount = $allSessions->count();

                $now = AcademyContextService::nowInAcademyTimezone();

                $upcomingSessions = $allSessions->filter(function ($session) use ($now) {
                    return $session->scheduled_at > $now &&
                        in_array($session->status->value ?? $session->status, [
                            SessionStatus::SCHEDULED->value,
                            SessionStatus::READY->value,
                            SessionStatus::ONGOING->value,
                        ]);
                })->count();

                $currentMonthSessions = $allSessions->filter(function ($session) use ($now) {
                    return $session->scheduled_at &&
                        $session->scheduled_at->year === $now->year &&
                        $session->scheduled_at->month === $now->month;
                })->count();

                $monthlyLimit = $circle->monthly_sessions_count ?? 4;
                $needsMoreSessions = $currentMonthSessions < $monthlyLimit;

                $isScheduled = $schedule &&
                              $schedule->is_active &&
                              ! empty($schedule->weekly_schedule) &&
                              ($upcomingSessions > 0 || ! $needsMoreSessions);

                $scheduleDays = [];
                $scheduleTime = null;

                if ($schedule && $schedule->weekly_schedule) {
                    foreach ($schedule->weekly_schedule as $entry) {
                        if (isset($entry['day'])) {
                            $scheduleDays[] = $entry['day'];
                        }
                        if (isset($entry['time']) && ! $scheduleTime) {
                            $scheduleTime = $entry['time'];
                        }
                    }
                }

                return [
                    'id' => $circle->id,
                    'type' => 'group',
                    'name' => $circle->name,
                    'status' => $isScheduled ? 'scheduled' : 'not_scheduled',
                    'sessions_count' => $sessionsCount,
                    'schedule_days' => $scheduleDays,
                    'schedule_time' => $scheduleTime,
                    'monthly_sessions' => $circle->monthly_sessions_count,
                    'students_count' => $circle->enrolled_students,
                    'max_students' => $circle->max_students,
                    'session_duration_minutes' => $circle->session_duration_minutes ?? 60,
                ];
            });
    }

    /**
     * Get individual circles for the teacher
     */
    public function getIndividualCircles(): Collection
    {
        $teacherId = $this->getTargetUserId();

        return QuranIndividualCircle::where('quran_teacher_id', $teacherId)
            ->with(['subscription.package', 'sessions', 'student'])
            ->whereHas('subscription', function ($query) {
                $query->whereIn('status', [
                    SessionSubscriptionStatus::PENDING->value,
                    SessionSubscriptionStatus::ACTIVE->value,
                ]);
            })
            ->whereHas('student')
            ->get()
            ->map(function ($circle) {
                $subscription = $circle->subscription;
                $scheduledSessions = $circle->sessions()->activeOrCompleted()->count();
                $totalSessions = $circle->total_sessions;
                $remainingSessions = max(0, $totalSessions - $scheduledSessions);

                $status = 'not_scheduled';
                if ($scheduledSessions > 0) {
                    if ($remainingSessions > 0) {
                        $status = 'partially_scheduled';
                    } else {
                        $status = 'fully_scheduled';
                    }
                }

                return [
                    'id' => $circle->id,
                    'type' => 'individual',
                    'name' => $circle->name,
                    'status' => $status,
                    'sessions_count' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $remainingSessions,
                    'subscription_start' => $subscription?->starts_at,
                    'subscription_end' => $subscription?->ends_at,
                    'student_name' => $circle->student->name ?? __('calendar.strategy.unspecified'),
                    'monthly_sessions' => $subscription?->package?->monthly_sessions ?? 4,
                    'can_schedule' => $remainingSessions > 0,
                    'session_duration_minutes' => $circle->default_duration_minutes ?? $subscription?->package?->session_duration_minutes ?? 60,
                ];
            });
    }

    /**
     * Get trial requests for the teacher
     */
    public function getTrialRequests(): Collection
    {
        $teacherProfileId = $this->getTargetUser()?->quranTeacherProfile?->id;
        if (! $teacherProfileId) {
            return collect();
        }

        return QuranTrialRequest::where('teacher_id', $teacherProfileId)
            ->whereIn('status', [
                TrialRequestStatus::PENDING->value,
                TrialRequestStatus::SCHEDULED->value,
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trialRequest) {
                return [
                    'id' => $trialRequest->id,
                    'type' => 'trial',
                    'name' => $trialRequest->student_name, // For consistent display in item cards
                    'student_name' => $trialRequest->student_name,
                    'phone' => $trialRequest->phone,
                    'email' => $trialRequest->email,
                    'current_level' => $trialRequest->current_level,
                    'level_label' => $trialRequest->level_label,
                    'preferred_time' => $trialRequest->preferred_time,
                    'preferred_time_label' => $trialRequest->time_label,
                    'notes' => $trialRequest->notes,
                    'status' => $trialRequest->status->value,
                    'status_arabic' => $trialRequest->status->label(),
                    'scheduled_at' => $trialRequest->scheduled_at,
                    'scheduled_at_formatted' => $trialRequest->scheduled_at ? $trialRequest->scheduled_at->format('Y/m/d H:i') : null,
                    'meeting_link' => $trialRequest->meeting_link,
                    'can_schedule' => $trialRequest->status === TrialRequestStatus::PENDING,
                ];
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(string $itemType, $item): ScheduleValidatorInterface
    {
        return match ($itemType) {
            'group' => new GroupCircleValidator(QuranCircle::find($item['id'])),
            'individual' => new IndividualCircleValidator(QuranIndividualCircle::find($item['id']), $this->sessionService),
            'trial' => new TrialSessionValidator(QuranTrialRequest::find($item['id'])),
            default => throw new \InvalidArgumentException("Unknown item type: {$itemType}"),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function createSchedule(array $data, ScheduleValidatorInterface $validator): void
    {
        $itemType = $data['item_type'] ?? null;
        $itemId = $data['item_id'] ?? null;

        if (! $itemType || ! $itemId) {
            throw new \Exception(__('calendar.strategy.item_info_incomplete'));
        }

        match ($itemType) {
            'group' => $this->createGroupCircleSchedule($itemId, $data),
            'individual' => $this->createIndividualCircleSchedule($itemId, $data),
            'trial' => $this->createTrialSessionSchedule($itemId, $data),
            default => throw new \InvalidArgumentException("Unknown item type: {$itemType}"),
        };
    }

    /**
     * Create schedule for group circle
     */
    private function createGroupCircleSchedule(int $circleId, array $data): int
    {
        $circle = QuranCircle::findOrFail($circleId);

        $weeklySchedule = [];
        foreach ($data['schedule_days'] as $day) {
            $weeklySchedule[] = [
                'day' => $day,
                'time' => $data['schedule_time'],
            ];
        }

        $teacherId = $this->getTargetUserId();
        $existingSchedule = QuranCircleSchedule::where([
            'academy_id' => $circle->academy_id,
            'circle_id' => $circle->id,
            'quran_teacher_id' => $teacherId,
            'is_active' => true,
        ])->first();

        if ($existingSchedule) {
            $sortedExisting = collect($existingSchedule->weekly_schedule ?? [])->sortBy('day')->values()->toArray();
            $sortedNew = collect($weeklySchedule)->sortBy('day')->values()->toArray();

            if ($sortedExisting !== $sortedNew) {
                $existingSchedule->update([
                    'weekly_schedule' => $weeklySchedule,
                    'updated_by' => Auth::id(),
                ]);
            }

            $schedule = $existingSchedule;
        } else {
            $schedule = QuranCircleSchedule::create([
                'academy_id' => $circle->academy_id,
                'circle_id' => $circle->id,
                'quran_teacher_id' => $teacherId,
                'weekly_schedule' => $weeklySchedule,
                'timezone' => AcademyContextService::getTimezone(),
                'default_duration_minutes' => $circle->session_duration_minutes ?? 60,
                'is_active' => true,
                'schedule_starts_at' => isset($data['schedule_start_date']) ? AcademyContextService::parseInAcademyTimezone($data['schedule_start_date'])->startOfDay() : AcademyContextService::nowInAcademyTimezone()->startOfDay(),
                'generate_ahead_days' => 30,
                'generate_before_hours' => 1,
                'session_title_template' => 'جلسة {circle_name} - {day} {time}',
                'session_description_template' => __('calendar.strategy.auto_scheduled_description'),
                'recording_enabled' => false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        $schedule->update(['is_active' => true]);
        $schedule->circle->update([
            'status' => 'active',
            'enrollment_status' => CircleEnrollmentStatus::OPEN,
            'schedule_configured' => true,
            'schedule_configured_at' => AcademyContextService::nowInAcademyTimezone(),
        ]);

        // Generate sessions using the session service
        return $this->sessionService->generateExactGroupSessions($schedule, $data['session_count']);
    }

    /**
     * Create schedule for individual circle
     */
    private function createIndividualCircleSchedule(int $circleId, array $data): int
    {
        $circle = QuranIndividualCircle::findOrFail($circleId);

        if (! $circle->subscription) {
            throw new \Exception(__('calendar.strategy.no_valid_subscription'));
        }

        if ($circle->subscription->status !== SessionSubscriptionStatus::ACTIVE) {
            throw new \Exception(__('calendar.strategy.subscription_inactive'));
        }

        $remainingSessions = $this->sessionService->getRemainingIndividualSessions($circle);

        if ($remainingSessions <= 0) {
            throw new \Exception(__('calendar.strategy.no_remaining_circle_sessions'));
        }

        // Generate sessions using the session service
        return $this->sessionService->createIndividualCircleSchedule($circle, $data);
    }

    /**
     * Create schedule for trial session
     */
    private function createTrialSessionSchedule(int $trialRequestId, array $data): int
    {
        $trialRequest = QuranTrialRequest::findOrFail($trialRequestId);

        // Generate trial session using the session service
        return $this->sessionService->createTrialSession($trialRequest, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getFooterWidgets(): array
    {
        return [
            UnifiedCalendarWidget::class,
            CalendarColorLegendWidget::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSessionTypes(): array
    {
        return ['group', 'individual', 'trial'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionHeading(): string
    {
        return __('calendar.strategy.manage_quran_sessions');
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionDescription(): string
    {
        return __('calendar.strategy.select_quran_item');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabsLabel(): string
    {
        return __('calendar.strategy.quran_session_types');
    }
}
