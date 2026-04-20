<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\TeacherEarning;
use App\Services\TeacherEarningsDisplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected TeacherEarningsDisplayService $earningsDisplayService
    ) {}

    /**
     * Teacher earnings page payload — mirrors the web `/teacher/earnings`
     * page so the mobile app can render the same UI.
     *
     * Query params (all optional): month (YYYY-MM), source (e.g.
     * `individual_circle_5`), start_date, end_date, page, per_page.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|string',
            'source' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        [$teacherType, $teacherId] = $this->resolveTeacherIdentity($user);
        $academyId = $user->academy_id;

        $month = $request->input('month');
        $source = $request->input('source');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        $currency = getTeacherEarningsCurrency($user?->academy)->value;

        if (! $teacherType || ! $teacherId) {
            return $this->success($this->emptyPayload($perPage, $currency), __('earnings.earnings_calculated'));
        }

        $baseQuery = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId);
        $this->earningsDisplayService->applyFilters($baseQuery, $month, $source, $startDate, $endDate);

        $stats = $this->earningsDisplayService->computeStats($baseQuery);

        $paginated = (clone $baseQuery)
            ->with(['session' => fn ($morphTo) => $morphTo->morphWith($this->earningsDisplayService->sessionMorphMap())])
            ->orderByDesc('session_completed_at')
            ->paginate($perPage);

        $earnings = collect($paginated->items())
            ->map(fn (TeacherEarning $earning) => $this->transformEarning($earning, $user, $currency))
            ->all();

        // Filter facets scan the teacher's full earning history; only return
        // them on page 1 so subsequent page loads stay cheap. The mobile
        // client caches them from the first response.
        $filters = $page === 1
            ? [
                'available_months' => $this->earningsDisplayService->getAvailableMonths($teacherType, $teacherId, $academyId),
                'available_sources' => $this->earningsDisplayService->buildSourcesList($teacherType, $teacherId, $academyId, $user),
            ]
            : null;

        $payload = [
            'stats' => $stats,
            'earnings' => $earnings,
            'pagination' => PaginationHelper::fromPaginator($paginated),
            'currency' => $currency,
        ];
        if ($filters !== null) {
            $payload['filters'] = $filters;
        }

        return $this->success($payload, __('earnings.earnings_calculated'));
    }

    /**
     * Get payouts list (finalized earnings).
     */
    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();
        [$teacherType, $teacherId] = $this->resolveTeacherIdentity($user);
        $academyId = $user->academy_id;

        $perPage = min((int) $request->input('per_page', 20), 100);
        $currency = getTeacherEarningsCurrency($user?->academy)->value;

        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'payouts' => [],
                'total_finalized' => 0,
                'total_pending' => 0,
                'currency' => $currency,
                'pagination' => PaginationHelper::fromArray(0, 1, $perPage),
            ], __('earnings.payouts'));
        }

        $finalizedEarnings = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->finalized()
            ->orderByDesc('session_completed_at')
            ->paginate($perPage);

        $totalFinalized = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->finalized()
            ->sum('amount');

        $totalPending = TeacherEarning::forTeacher($teacherType, $teacherId)
            ->where('academy_id', $academyId)
            ->unpaid()
            ->sum('amount');

        return $this->success([
            'payouts' => collect($finalizedEarnings->items())->map(fn (TeacherEarning $e) => [
                'id' => $e->id,
                'amount' => (float) $e->amount,
                'formatted_amount' => $e->formatted_amount,
                'calculation_method' => $e->calculation_method,
                'session_completed_at' => $e->session_completed_at?->toDateString(),
                'earning_month' => $e->earning_month?->format('Y-m'),
                'created_at' => $e->created_at?->toISOString(),
            ])->toArray(),
            'total_finalized' => (float) $totalFinalized,
            'total_pending' => (float) $totalPending,
            'currency' => $currency,
            'pagination' => PaginationHelper::fromPaginator($finalizedEarnings),
        ], __('earnings.payouts'));
    }

    /**
     * Resolve the teacher polymorphic identity used in `teacher_type` /
     * `teacher_id` columns. Matches the values used by the web controller and
     * `EarningsCalculationService` (see TeacherProfileController::earnings).
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function resolveTeacherIdentity($user): array
    {
        if ($user->isQuranTeacher() && $user->quranTeacherProfile) {
            return ['quran_teacher', $user->quranTeacherProfile->id];
        }

        if ($user->isAcademicTeacher() && $user->academicTeacherProfile) {
            return ['academic_teacher', $user->academicTeacherProfile->id];
        }

        return [null, null];
    }

    private function emptyPayload(int $perPage, string $currency): array
    {
        return [
            'stats' => [
                'total_earnings' => 0.0,
                'finalized_amount' => 0.0,
                'unpaid_amount' => 0.0,
                'total_duration_minutes' => 0,
            ],
            'earnings' => [],
            'pagination' => PaginationHelper::fromArray(0, 1, $perPage),
            'filters' => [
                'available_months' => [],
                'available_sources' => [],
            ],
            'currency' => $currency,
        ];
    }

    private function transformEarning(TeacherEarning $earning, $user, string $currency): array
    {
        $source = $this->earningsDisplayService->determineEarningSource($earning, $user);
        $internalType = $source['type'];

        $apiSourceType = match ($internalType) {
            'individual_circle' => 'quran_individual',
            'group_circle' => 'quran_group',
            'academic_lesson' => 'academic_lesson',
            'interactive_course' => 'interactive_course',
            default => null,
        };

        $sourceLabel = $apiSourceType !== null
            ? __('earnings.source_types.'.$internalType)
            : __('earnings.source_other');

        $duration = $earning->calculation_metadata['duration_minutes'] ?? null;
        $status = $earning->is_disputed
            ? 'disputed'
            : ($earning->is_finalized ? 'finalized' : 'pending');

        return [
            'id' => $earning->id,
            'source_type' => $apiSourceType,
            'source_label' => $sourceLabel,
            'source_name' => $source['name'] ?? null,
            'amount' => (float) $earning->amount,
            'formatted_amount' => $earning->formatted_amount,
            'currency' => $currency,
            'calculation_method' => $earning->calculation_method,
            'calculation_method_label' => $earning->calculation_method_label,
            'duration_minutes' => $duration !== null ? (int) $duration : null,
            'session_completed_at' => $earning->session_completed_at?->toISOString(),
            'earning_month' => $earning->earning_month?->format('Y-m'),
            'is_finalized' => (bool) $earning->is_finalized,
            'is_disputed' => (bool) $earning->is_disputed,
            'status' => $status,
        ];
    }
}
