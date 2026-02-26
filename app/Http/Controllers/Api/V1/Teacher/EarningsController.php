<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranSession;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\TeacherEarning;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    use ApiResponses;

    /**
     * Get earnings summary with month breakdown and source grouping.
     *
     * Accepts optional `month` query param in 'YYYY-MM' format (defaults to current month).
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        // Resolve teacher type and profile ID based on user type
        $teacherType = null;
        $teacherId = null;

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $teacherType = QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = AcademicTeacherProfile::class;
            $teacherId = $user->academicTeacherProfile->id;
        }

        // Parse the requested month (defaults to current month)
        $monthParam = $request->get('month');
        try {
            $date = $monthParam
                ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception) {
            $date = Carbon::now()->startOfMonth();
        }

        $prevDate = (clone $date)->subMonth();

        // If no teacher profile found, return empty summary
        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'month' => $date->format('Y-m'),
                'month_label' => $date->locale('ar')->translatedFormat('F Y'),
                'period_earnings' => 0,
                'previous_period_earnings' => 0,
                'all_time_earnings' => 0,
                'pending_earnings' => 0,
                'sessions_count' => 0,
                'sources' => [],
                'currency' => getCurrencyCode(),
            ], __('Earnings summary retrieved successfully'));
        }

        $baseQuery = fn () => TeacherEarning::forTeacher($teacherType, $teacherId);

        // Period stats for selected month
        $periodEarnings = $baseQuery()->forMonth($date->year, $date->month)->sum('amount');
        $previousPeriodEarnings = $baseQuery()->forMonth($prevDate->year, $prevDate->month)->sum('amount');
        $periodSessionsCount = $baseQuery()->forMonth($date->year, $date->month)->count();

        // All-time stats
        $allTimeEarnings = $baseQuery()->sum('amount');
        $pendingEarnings = $baseQuery()->unpaid()->sum('amount');

        // Earnings for selected month with related session data for source breakdown
        $earningsForMonth = $baseQuery()
            ->forMonth($date->year, $date->month)
            ->with([
                'session' => function ($morphTo) {
                    $morphTo->morphWith([
                        QuranSession::class => ['individualCircle', 'circle', 'student'],
                        AcademicSession::class => ['academicIndividualLesson.subject', 'student'],
                        InteractiveCourseSession::class => ['course'],
                    ]);
                },
            ])
            ->get();

        $sources = $this->buildSourceBreakdown($earningsForMonth);

        return $this->success([
            'month' => $date->format('Y-m'),
            'month_label' => $date->locale('ar')->translatedFormat('F Y'),
            'period_earnings' => (float) $periodEarnings,
            'previous_period_earnings' => (float) $previousPeriodEarnings,
            'all_time_earnings' => (float) $allTimeEarnings,
            'pending_earnings' => (float) $pendingEarnings,
            'sessions_count' => $periodSessionsCount,
            'sources' => $sources,
            'currency' => getCurrencyCode(),
        ], __('Earnings summary retrieved successfully'));
    }

    /**
     * Get earnings history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        // Resolve teacher type and profile ID based on user type
        $teacherType = null;
        $teacherId = null;

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $teacherType = QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = AcademicTeacherProfile::class;
            $teacherId = $user->academicTeacherProfile->id;
        }

        // If no teacher profile found, return empty history
        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'earnings' => [],
                'period' => [
                    'start_date' => now()->subMonths(3)->toDateString(),
                    'end_date' => now()->toDateString(),
                ],
                'total_for_period' => 0,
                'currency' => getCurrencyCode(),
                'pagination' => PaginationHelper::fromArray(0, 1, 20),
            ], __('Earnings history retrieved successfully'));
        }

        // Date range
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->subMonths(3)->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfDay();

        $perPage = min((int) $request->get('per_page', 20), 100);

        // Get earnings from TeacherEarning model with proper polymorphic query
        $earningsQuery = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereBetween('session_completed_at', [$startDate, $endDate])
            ->with(['session'])
            ->orderBy('session_completed_at', 'desc');

        $paginatedEarnings = $earningsQuery->paginate($perPage);

        // Calculate total for the period
        $totalForPeriod = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereBetween('session_completed_at', [$startDate, $endDate])
            ->sum('amount');

        // Transform earnings data
        $earnings = collect($paginatedEarnings->items())->map(function ($earning) {
            // Determine session type label
            $sessionTypeLabel = match ($earning->session_type) {
                QuranSession::class => 'جلسة قرآنية',
                AcademicSession::class => 'جلسة أكاديمية',
                InteractiveCourseSession::class => 'جلسة دورة تفاعلية',
                default => 'جلسة',
            };

            // Build description from metadata if available
            $description = $sessionTypeLabel;
            if (! empty($earning->calculation_metadata)) {
                $metadata = $earning->calculation_metadata;
                if (isset($metadata['subject'])) {
                    $description .= ' - '.$metadata['subject'];
                }
                if (isset($metadata['session_type'])) {
                    $typeLabel = match ($metadata['session_type']) {
                        'individual' => 'فردية',
                        'group' => 'جماعية',
                        'trial' => 'تجريبية',
                        'circle' => 'حلقة',
                        default => '',
                    };
                    if ($typeLabel) {
                        $description .= ' ('.$typeLabel.')';
                    }
                }
            }

            return [
                'id' => $earning->id,
                'session_type' => $earning->session_type,
                'session_id' => $earning->session_id,
                'description' => $description,
                'amount' => (float) $earning->amount,
                'formatted_amount' => $earning->formatted_amount,
                'currency' => getCurrencyCode(),
                'calculation_method' => $earning->calculation_method,
                'calculation_method_label' => $earning->calculation_method_label,
                'is_finalized' => $earning->is_finalized,
                'is_disputed' => $earning->is_disputed,
                'session_completed_at' => $earning->session_completed_at?->toISOString(),
                'date' => $earning->session_completed_at?->toDateString(),
                'created_at' => $earning->created_at->toISOString(),
            ];
        })->toArray();

        return $this->success([
            'earnings' => $earnings,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'total_for_period' => (float) $totalForPeriod,
            'currency' => getCurrencyCode(),
            'pagination' => PaginationHelper::fromPaginator($paginatedEarnings),
        ], __('Earnings history retrieved successfully'));
    }

    /**
     * Get payouts list.
     * Returns teacher payout requests with their approval status.
     */
    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();

        // Resolve teacher type for filtering
        $teacherType = null;
        $teacherId = null;

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $teacherType = QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = AcademicTeacherProfile::class;
            $teacherId = $user->academicTeacherProfile->id;
        }

        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'payouts' => [],
                'total_finalized' => 0,
                'total_pending' => 0,
                'currency' => getCurrencyCode(),
            ], __('Payouts retrieved successfully'));
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $finalizedEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->finalized()
            ->orderBy('session_completed_at', 'desc')
            ->paginate($perPage);

        $totalFinalized = TeacherEarning::forTeacher($teacherType, $teacherId)->finalized()->sum('amount');
        $totalPending = TeacherEarning::forTeacher($teacherType, $teacherId)->unpaid()->sum('amount');

        return $this->success([
            'payouts' => collect($finalizedEarnings->items())->map(fn ($e) => [
                'id' => $e->id,
                'amount' => (float) $e->amount,
                'formatted_amount' => $e->formatted_amount,
                'calculation_method' => $e->calculation_method,
                'session_completed_at' => $e->session_completed_at?->toDateString(),
                'earning_month' => $e->earning_month?->format('Y-m'),
                'created_at' => $e->created_at->toISOString(),
            ])->toArray(),
            'total_finalized' => (float) $totalFinalized,
            'total_pending' => (float) $totalPending,
            'currency' => getCurrencyCode(),
            'pagination' => PaginationHelper::fromPaginator($finalizedEarnings),
        ], __('Payouts retrieved successfully'));
    }

    /**
     * Build source breakdown array from a collection of TeacherEarning records.
     *
     * Groups earnings by session source type (quran_individual, quran_group,
     * academic_lesson, interactive_course), then by individual circle/lesson/course.
     */
    private function buildSourceBreakdown($earnings): array
    {
        // Group type order for consistent display
        $typeOrder = ['quran_individual', 'quran_group', 'academic_lesson', 'interactive_course'];

        // grouped[sourceType][itemKey] = { id, name, sessions_count, amount, student_name }
        $grouped = [];

        foreach ($earnings as $earning) {
            $session = $earning->session;
            if (! $session) {
                continue;
            }

            [$sourceType, $itemKey, $itemName, $studentName] = $this->resolveSourceInfo($session);

            if (! isset($grouped[$sourceType])) {
                $grouped[$sourceType] = [
                    'type' => $sourceType,
                    'sessions_count' => 0,
                    'total_amount' => 0.0,
                    'items_map' => [],
                ];
            }

            $grouped[$sourceType]['sessions_count']++;
            $grouped[$sourceType]['total_amount'] += $earning->amount;

            if (! isset($grouped[$sourceType]['items_map'][$itemKey])) {
                $grouped[$sourceType]['items_map'][$itemKey] = [
                    'id' => $itemKey,
                    'name' => $itemName,
                    'sessions_count' => 0,
                    'amount' => 0.0,
                    'student_name' => $studentName,
                ];
            }

            $grouped[$sourceType]['items_map'][$itemKey]['sessions_count']++;
            $grouped[$sourceType]['items_map'][$itemKey]['amount'] += $earning->amount;
        }

        // Convert to final array format, ordered by type order then by total amount desc
        $result = [];
        foreach ($typeOrder as $type) {
            if (! isset($grouped[$type])) {
                continue;
            }
            $group = $grouped[$type];
            $result[] = [
                'type' => $group['type'],
                'sessions_count' => $group['sessions_count'],
                'total_amount' => (float) $group['total_amount'],
                'items' => array_values($group['items_map']),
            ];
        }

        return $result;
    }

    /**
     * Resolve source type, item key, item name, and student name for a session.
     *
     * @return array{string, string, string, string|null}
     */
    private function resolveSourceInfo($session): array
    {
        if ($session instanceof QuranSession) {
            if ($session->individualCircle) {
                return [
                    'quran_individual',
                    'individual_circle_'.$session->individualCircle->id,
                    $session->individualCircle->name ?? ('حلقة فردية'),
                    $session->student?->name,
                ];
            }

            if ($session->circle) {
                return [
                    'quran_group',
                    'group_circle_'.$session->circle->id,
                    $session->circle->name ?? 'حلقة جماعية',
                    null,
                ];
            }

            // Fallback for quran session without circle relationship
            return [
                'quran_individual',
                'quran_session_'.$session->id,
                'جلسة قرآن',
                $session->student?->name,
            ];
        }

        if ($session instanceof AcademicSession) {
            $lessonId = $session->academic_individual_lesson_id ?? $session->id;
            $subjectName = $session->academicIndividualLesson?->subject?->name ?? 'درس أكاديمي';
            $studentName = $session->student?->name;
            $itemName = $studentName ? "{$subjectName} - {$studentName}" : $subjectName;

            return [
                'academic_lesson',
                'lesson_'.$lessonId,
                $itemName,
                $studentName,
            ];
        }

        if ($session instanceof InteractiveCourseSession) {
            $courseId = $session->course?->id ?? $session->id;
            $courseName = $session->course?->title ?? 'دورة تفاعلية';

            return [
                'interactive_course',
                'course_'.$courseId,
                $courseName,
                null,
            ];
        }

        return [
            'quran_individual',
            'other_'.$session->id,
            'جلسة',
            null,
        ];
    }
}
