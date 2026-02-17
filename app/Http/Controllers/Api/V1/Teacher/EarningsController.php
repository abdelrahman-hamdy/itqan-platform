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
            $teacherType = QuranTeacherProfile::class;
            $teacherId = $user->quranTeacherProfile->id;
        } elseif ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            $teacherType = AcademicTeacherProfile::class;
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
            // Pending payout = not yet finalized and not disputed
            'pending_payout' => $baseQuery()->unpaid()->sum('amount'),
            // Total finalized = earnings that have been finalized
            'total_paid_out' => $baseQuery()->finalized()->sum('amount'),
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

        $perPage = $request->get('per_page', 20);

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
     * Get payouts.
     *
     * @deprecated Payout system has been removed. Returns empty array for backward compatibility.
     */
    public function payouts(Request $request): JsonResponse
    {
        // Payout system has been removed. Return empty array for backward compatibility with mobile app.
        return $this->success([
            'payouts' => [],
            'pagination' => PaginationHelper::fromArray(0, 1, 15),
        ], __('Payouts retrieved successfully'));
    }
}
