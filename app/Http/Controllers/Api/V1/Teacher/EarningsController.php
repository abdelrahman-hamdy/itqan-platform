<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EarningsController extends Controller
{
    use ApiResponses;

    /**
     * Get earnings summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        // Resolve teacher type and profile ID based on user type
        $teacherType = null;
        $teacherId = null;

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $teacherType = \App\Models\QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = \App\Models\AcademicTeacherProfile::class;
            $teacherId = $user->academicTeacherProfile->id;
        }

        // If no teacher profile found, return empty summary
        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'summary' => [
                    'total_earnings' => 0,
                    'current_month_earnings' => 0,
                    'last_month_earnings' => 0,
                    'pending_payout' => 0,
                    'total_paid_out' => 0,
                    'sessions_count' => 0,
                ],
                'currency' => getCurrencyCode(),
            ], __('Earnings summary retrieved successfully'));
        }

        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Build base query for this teacher
        $baseQuery = fn () => TeacherEarning::forTeacher($teacherType, $teacherId);

        // Calculate summary using database aggregation
        $summary = [
            'total_earnings' => $baseQuery()->sum('amount'),
            'current_month_earnings' => $baseQuery()
                ->forMonth($currentMonth->year, $currentMonth->month)
                ->sum('amount'),
            'last_month_earnings' => $baseQuery()
                ->forMonth($lastMonth->year, $lastMonth->month)
                ->sum('amount'),
            // Pending payout = not yet assigned to a payout, not finalized, not disputed
            'pending_payout' => $baseQuery()->unpaid()->sum('amount'),
            // Total paid out = assigned to a payout and finalized
            'total_paid_out' => $baseQuery()
                ->whereNotNull('payout_id')
                ->finalized()
                ->sum('amount'),
            'sessions_count' => $baseQuery()->count(),
        ];

        return $this->success([
            'summary' => $summary,
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
            $teacherType = \App\Models\QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = \App\Models\AcademicTeacherProfile::class;
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

        $perPage = $request->get('per_page', 20);

        // Get earnings from TeacherEarning model with proper polymorphic query
        $earningsQuery = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->whereBetween('session_completed_at', [$startDate, $endDate])
            ->with(['session', 'payout'])
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
                \App\Models\QuranSession::class => 'جلسة قرآنية',
                \App\Models\AcademicSession::class => 'جلسة أكاديمية',
                \App\Models\InteractiveCourseSession::class => 'جلسة دورة تفاعلية',
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
                'payout_code' => $earning->payout?->payout_code,
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
     * Get payouts.
     */
    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();

        // Determine teacher type and id based on user's profile
        $teacherType = null;
        $teacherId = null;

        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            $teacherType = \App\Models\QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = \App\Models\AcademicTeacherProfile::class;
            $teacherId = $user->academicTeacherProfile->id;
        }

        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'payouts' => [],
                'pagination' => PaginationHelper::fromArray(0, 1, 15),
            ], __('Payouts retrieved successfully'));
        }

        $payouts = TeacherPayout::where('teacher_type', $teacherType)
            ->where('teacher_id', $teacherId)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'payouts' => collect($payouts->items())->map(fn ($payout) => [
                'id' => $payout->id,
                'payout_code' => $payout->payout_code,
                'total_amount' => $payout->total_amount,
                'sessions_count' => $payout->sessions_count,
                'currency' => getCurrencyCode(),
                'status' => $payout->status->value,
                'status_label' => $payout->status->label(),
                'payout_month' => $payout->payout_month?->format('Y-m'),
                'month_name' => $payout->month_name,
                'approved_at' => $payout->approved_at?->toISOString(),
                'created_at' => $payout->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($payouts),
        ], __('Payouts retrieved successfully'));
    }
}
