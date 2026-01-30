<?php

namespace App\Services\Calendar;

use App\Enums\InteractiveCourseStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Filament\Shared\Traits\ValidatesConflicts;
use App\Filament\Shared\Widgets\CalendarColorLegendWidget;
use App\Filament\Shared\Widgets\UnifiedCalendarWidget;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Services\AcademyContextService;
use App\Services\Calendar\Traits\GeneratesSessionDates;
use App\Services\Scheduling\Validators\AcademicLessonValidator;
use App\Services\Scheduling\Validators\InteractiveCourseValidator;
use App\Services\Scheduling\Validators\ScheduleValidatorInterface;
use App\Services\SessionManagementService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Academic teacher session strategy
 *
 * Handles calendar operations for Academic teachers:
 * - Private lessons (academic subscriptions)
 * - Interactive courses
 */
class AcademicSessionStrategy extends AbstractSessionStrategy
{
    use GeneratesSessionDates;
    use ValidatesConflicts;

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
            'private_lessons' => [
                'label' => 'الدروس الفردية',
                'icon' => 'heroicon-m-user',
                'items_method' => 'getPrivateLessons',
            ],
            'interactive_courses' => [
                'label' => 'الدورات التفاعلية',
                'icon' => 'heroicon-m-user-group',
                'items_method' => 'getInteractiveCourses',
            ],
        ];
    }

    /**
     * Get private lessons (academic subscriptions) for the teacher
     */
    public function getPrivateLessons(): Collection
    {
        $user = $this->getTargetUser();
        $teacherProfile = $user?->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return AcademicSubscription::where('teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->where('status', SessionSubscriptionStatus::ACTIVE->value)
            ->with(['student', 'subject', 'sessions'])
            ->get()
            ->map(function ($subscription) {
                $allSessions = $subscription->sessions;
                $totalSessions = $allSessions->count();
                $scheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === SessionStatus::SCHEDULED->value && ! is_null($session->scheduled_at);
                })->count();
                $unscheduledSessions = $allSessions->filter(function ($session) {
                    return $session->status->value === SessionStatus::UNSCHEDULED->value || is_null($session->scheduled_at);
                })->count();

                $status = 'not_scheduled';
                if ($scheduledSessions > 0) {
                    if ($unscheduledSessions > 0) {
                        $status = 'partially_scheduled';
                    } else {
                        $status = 'fully_scheduled';
                    }
                }

                return [
                    'id' => $subscription->id,
                    'type' => 'private_lesson',
                    'name' => 'درس خاص - '.($subscription->subject_name ?? 'مادة أكاديمية'),
                    'status' => $status,
                    'total_sessions' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $unscheduledSessions,
                    'student_name' => $subscription->student?->name ?? 'غير محدد',
                    'subject_name' => $subscription->subject_name ?? 'مادة أكاديمية',
                    'can_schedule' => $unscheduledSessions > 0,
                ];
            });
    }

    /**
     * Get interactive courses for the teacher
     */
    public function getInteractiveCourses(): Collection
    {
        $user = $this->getTargetUser();
        $teacherProfile = $user?->academicTeacherProfile;

        if (! $teacherProfile) {
            return collect();
        }

        return InteractiveCourse::where('assigned_teacher_id', $teacherProfile->id)
            ->where('academy_id', $user->academy_id)
            ->whereIn('status', [InteractiveCourseStatus::ACTIVE->value, InteractiveCourseStatus::PUBLISHED->value])
            ->with(['subject', 'sessions', 'enrollments'])
            ->get()
            ->map(function ($course) {
                $scheduledSessions = $course->sessions()->whereIn('status', [SessionStatus::SCHEDULED->value, SessionStatus::ONGOING->value, SessionStatus::COMPLETED->value])->count();
                $totalSessions = $course->total_sessions;
                $remainingSessions = max(0, $totalSessions - $scheduledSessions);
                $enrolledStudents = $course->enrollments()->where('enrollment_status', 'enrolled')->count();

                return [
                    'id' => $course->id,
                    'type' => 'interactive_course',
                    'title' => $course->title,
                    'name' => $course->title, // For consistency
                    'status' => $course->status->value,
                    'status_arabic' => $course->status->label(),
                    'status_color' => $course->status->hexColor(),
                    'total_sessions' => $totalSessions,
                    'sessions_scheduled' => $scheduledSessions,
                    'sessions_remaining' => $remainingSessions,
                    'start_date' => $course->start_date?->format('Y/m/d'),
                    'end_date' => $course->end_date?->format('Y/m/d'),
                    'subject_name' => $course->subject?->name ?? 'مادة أكاديمية',
                    'enrolled_students' => $enrolledStudents,
                    'max_students' => $course->max_students ?? 20,
                    'can_schedule' => $remainingSessions > 0,
                ];
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(string $itemType, $item): ScheduleValidatorInterface
    {
        return match ($itemType) {
            'private_lesson' => new AcademicLessonValidator(AcademicSubscription::find($item['id'])),
            'interactive_course' => new InteractiveCourseValidator(InteractiveCourse::find($item['id'])),
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
            throw new \Exception('معلومات العنصر غير مكتملة');
        }

        match ($itemType) {
            'private_lesson' => $this->createPrivateLessonSchedule($itemId, $data),
            'interactive_course' => $this->createInteractiveCourseSchedule($itemId, $data),
            default => throw new \InvalidArgumentException("Unknown item type: {$itemType}"),
        };
    }

    /**
     * Create schedule for private lesson (academic subscription)
     */
    private function createPrivateLessonSchedule(int $subscriptionId, array $data): int
    {
        $subscription = AcademicSubscription::findOrFail($subscriptionId);

        if (! $subscription->student) {
            throw new \Exception('لا يمكن جدولة جلسات لدرس بدون طالب مسجل');
        }

        // Get unscheduled sessions
        $unscheduledSessions = $subscription->sessions()
            ->where(function ($query) {
                $query->where('status', SessionStatus::UNSCHEDULED->value)
                    ->orWhereNull('scheduled_at');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        if ($unscheduledSessions->isEmpty()) {
            throw new \Exception('لا توجد جلسات غير مجدولة لهذا الدرس');
        }

        $requestedSessionCount = $data['session_count'];
        $sessionsToSchedule = $unscheduledSessions->take($requestedSessionCount);

        // Generate session dates
        $sessionDates = $this->generateSessionDates(
            $data['schedule_days'],
            $data['schedule_time'],
            $data['schedule_start_date'] ?? AcademyContextService::nowInAcademyTimezone()->toDateString(),
            $sessionsToSchedule->count()
        );

        // Schedule the sessions with conflict checking
        $scheduledCount = 0;
        $skippedDates = [];

        foreach ($sessionsToSchedule as $index => $session) {
            if (isset($sessionDates[$index])) {
                $scheduledAt = $sessionDates[$index];
                $duration = $subscription->session_duration_minutes ?? 60;

                // Check for conflicts before scheduling
                try {
                    $this->validateSessionConflicts([
                        'scheduled_at' => $scheduledAt,
                        'duration_minutes' => $duration,
                        'teacher_id' => $this->getTargetUserId(),
                    ], $session->id, 'academic');

                    $session->update([
                        'scheduled_at' => $scheduledAt,
                        'status' => SessionStatus::SCHEDULED,
                    ]);
                    $scheduledCount++;
                } catch (\Exception $e) {
                    // Skip this time slot due to conflict, try next available
                    $skippedDates[] = $scheduledAt->format('Y/m/d H:i');

                    continue;
                }
            }
        }

        if (! empty($skippedDates) && $scheduledCount === 0) {
            throw new \Exception('جميع الأوقات المختارة تتعارض مع جلسات أخرى. يرجى اختيار أوقات مختلفة.');
        }

        return $scheduledCount;
    }

    /**
     * Create schedule for interactive course
     */
    private function createInteractiveCourseSchedule(int $courseId, array $data): int
    {
        $course = InteractiveCourse::findOrFail($courseId);

        $requestedSessionCount = $data['session_count'];
        $remainingSessions = max(0, $course->total_sessions - $course->sessions()->count());

        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية لجدولتها في هذه الدورة');
        }

        $sessionsToCreate = min($requestedSessionCount, $remainingSessions);

        // Generate session dates
        $sessionDates = $this->generateSessionDates(
            $data['schedule_days'],
            $data['schedule_time'],
            $data['schedule_start_date'] ?? AcademyContextService::nowInAcademyTimezone()->toDateString(),
            $sessionsToCreate
        );

        // Get the maximum existing session number for this course
        // Use max() instead of count() to handle gaps in session numbers and avoid duplicates
        $maxSessionNumber = $course->sessions()->max('session_number') ?? 0;

        // Create the sessions with conflict checking
        $createdCount = 0;
        $skippedDates = [];
        $teacherUserId = $course->assignedTeacher?->user_id ?? $this->getTargetUserId();

        foreach ($sessionDates as $index => $sessionDate) {
            $newSessionNumber = $maxSessionNumber + $index + 1;
            $duration = $course->session_duration_minutes ?? 60;

            // Check for conflicts before creating
            try {
                $this->validateSessionConflicts([
                    'scheduled_at' => $sessionDate,
                    'duration_minutes' => $duration,
                    'teacher_id' => $teacherUserId,
                ], null, 'academic');

                InteractiveCourseSession::create([
                    'academy_id' => $course->academy_id,
                    'course_id' => $course->id,
                    'session_number' => $newSessionNumber,
                    'title' => $course->title.' - جلسة '.$newSessionNumber,
                    'scheduled_at' => $sessionDate,
                    'duration_minutes' => $duration,
                    'status' => SessionStatus::SCHEDULED,
                ]);
                $createdCount++;
            } catch (\Exception $e) {
                // Skip this time slot due to conflict
                $skippedDates[] = $sessionDate->format('Y/m/d H:i');

                continue;
            }
        }

        if (! empty($skippedDates) && $createdCount === 0) {
            throw new \Exception('جميع الأوقات المختارة تتعارض مع جلسات أخرى. يرجى اختيار أوقات مختلفة.');
        }

        return $createdCount;
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
        return ['private_lesson', 'interactive_course'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionHeading(): string
    {
        return 'إدارة الجلسات الأكاديمية';
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionDescription(): string
    {
        return 'اختر درس أو دورة لجدولة جلساتها على التقويم';
    }

    /**
     * {@inheritdoc}
     */
    public function getTabsLabel(): string
    {
        return 'أنواع الجلسات الأكاديمية';
    }
}
