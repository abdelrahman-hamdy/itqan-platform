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

        if (! $teacherType || ! $teacherId) {
            return $this->success($this->emptyPayload($perPage), __('earnings.earnings_calculated'));
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
            ->map(fn (TeacherEarning $earning) => $this->transformEarning($earning, $user))
            ->all();

        $availableMonths = $this->earningsDisplayService->getAvailableMonths($teacherType, $teacherId, $academyId);
        $availableSources = $this->earningsDisplayService->buildSourcesList($teacherType, $teacherId, $academyId, $user);

        return $this->success([
            'stats' => $stats,
            'earnings' => $earnings,
            'pagination' => PaginationHelper::fromPaginator($paginated),
            'filters' => [
                'available_months' => $availableMonths,
                'available_sources' => $availableSources,
            ],
            'currency' => getCurrencyCode(),
        ], __('earnings.earnings_calculated'));
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

        if (! $teacherType || ! $teacherId) {
            return $this->success([
                'payouts' => [],
                'total_finalized' => 0,
                'total_pending' => 0,
                'currency' => getCurrencyCode(),
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
            'currency' => getCurrencyCode(),
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

    /**
     * Empty payload returned when the user has no teacher profile.
     */
    private function emptyPayload(int $perPage): array
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
            'currency' => getCurrencyCode(),
        ];
    }

    /**
     * Map a single TeacherEarning row into the API response shape consumed by
     * the mobile teacher earnings screen.
     */
    private function transformEarning(TeacherEarning $earning, $user): array
    {
        $sourceType = $this->earningsDisplayService->resolveApiSourceType($earning);
        $sourceLabel = $sourceType
            ? __('earnings.source_types.'.$this->sourceTypeToLangKey($sourceType))
            : __('earnings.source_other');

        $internal = $this->earningsDisplayService->determineEarningSource($earning, $user);
        $sourceName = $internal['name'] ?? null;

        $durationMinutes = null;
        if (is_array($earning->calculation_metadata) && isset($earning->calculation_metadata['duration_minutes'])) {
            $durationMinutes = (int) $earning->calculation_metadata['duration_minutes'];
        }

        $status = $earning->is_disputed
            ? 'disputed'
            : ($earning->is_finalized ? 'finalized' : 'pending');

        return [
            'id' => $earning->id,
            'source_type' => $sourceType,
            'source_label' => $sourceLabel,
            'source_name' => $sourceName,
            'amount' => (float) $earning->amount,
            'formatted_amount' => $earning->formatted_amount,
            'currency' => getCurrencyCode(),
            'calculation_method' => $earning->calculation_method,
            'calculation_method_label' => $earning->calculation_method_label,
            'duration_minutes' => $durationMinutes,
            'session_completed_at' => $earning->session_completed_at?->toISOString(),
            'earning_month' => $earning->earning_month?->format('Y-m'),
            'is_finalized' => (bool) $earning->is_finalized,
            'is_disputed' => (bool) $earning->is_disputed,
            'status' => $status,
        ];
    }

    /**
     * Map the API source-type to the existing `earnings.source_types.*` lang
     * key (which uses the older `individual_circle` / `group_circle` naming).
     */
    private function sourceTypeToLangKey(string $apiType): string
    {
        return match ($apiType) {
            'quran_individual' => 'individual_circle',
            'quran_group' => 'group_circle',
            default => $apiType,
        };
    }
}
