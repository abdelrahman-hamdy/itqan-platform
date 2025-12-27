<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicSession;
use App\Models\TeacherPayout;
use App\Models\QuranSession;
use App\Models\TeacherEarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Enums\SessionStatus;

class EarningsController extends Controller
{
    use ApiResponses;

    /**
     * Get earnings summary.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $summary = [
            'total_earnings' => 0,
            'current_month_earnings' => 0,
            'last_month_earnings' => 0,
            'pending_payout' => 0,
            'total_paid_out' => 0,
            'by_type' => [
                'quran' => 0,
                'academic' => 0,
            ],
        ];

        // Get earnings from TeacherEarning model if exists
        $earnings = TeacherEarning::where('user_id', $user->id)->get();

        if ($earnings->isNotEmpty()) {
            $summary['total_earnings'] = $earnings->sum('amount');
            $summary['current_month_earnings'] = $earnings
                ->where('created_at', '>=', $currentMonth)
                ->sum('amount');
            $summary['last_month_earnings'] = $earnings
                ->where('created_at', '>=', $lastMonth)
                ->where('created_at', '<', $currentMonth)
                ->sum('amount');
            $summary['pending_payout'] = $earnings
                ->where('status', 'pending')
                ->sum('amount');
            $summary['total_paid_out'] = $earnings
                ->where('status', 'paid')
                ->sum('amount');

            // By type
            $summary['by_type']['quran'] = $earnings
                ->where('type', 'quran')
                ->sum('amount');
            $summary['by_type']['academic'] = $earnings
                ->where('type', 'academic')
                ->sum('amount');
        } else {
            // Calculate from sessions if no earnings model
            if ($user->isQuranTeacher()) {
                $quranTeacherId = $user->quranTeacherProfile?->id;

                if ($quranTeacherId) {
                    $quranSessions = QuranSession::where('quran_teacher_id', $quranTeacherId)
                        ->where('status', SessionStatus::COMPLETED->value)
                        ->get();

                    $quranEarnings = $quranSessions->count() * ($user->quranTeacherProfile?->hourly_rate ?? 50);
                    $summary['by_type']['quran'] = $quranEarnings;
                    $summary['total_earnings'] += $quranEarnings;

                    $summary['current_month_earnings'] += $quranSessions
                        ->where('scheduled_at', '>=', $currentMonth)
                        ->count() * ($user->quranTeacherProfile?->hourly_rate ?? 50);

                    $summary['last_month_earnings'] += $quranSessions
                        ->where('scheduled_at', '>=', $lastMonth)
                        ->where('scheduled_at', '<', $currentMonth)
                        ->count() * ($user->quranTeacherProfile?->hourly_rate ?? 50);
                }
            }

            if ($user->isAcademicTeacher()) {
                $academicTeacherId = $user->academicTeacherProfile?->id;

                if ($academicTeacherId) {
                    $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                        ->where('status', SessionStatus::COMPLETED->value)
                        ->get();

                    $academicEarnings = $academicSessions->count() * ($user->academicTeacherProfile?->hourly_rate ?? 60);
                    $summary['by_type']['academic'] = $academicEarnings;
                    $summary['total_earnings'] += $academicEarnings;

                    $summary['current_month_earnings'] += $academicSessions
                        ->where('scheduled_at', '>=', $currentMonth)
                        ->count() * ($user->academicTeacherProfile?->hourly_rate ?? 60);

                    $summary['last_month_earnings'] += $academicSessions
                        ->where('scheduled_at', '>=', $lastMonth)
                        ->where('scheduled_at', '<', $currentMonth)
                        ->count() * ($user->academicTeacherProfile?->hourly_rate ?? 60);
                }
            }
        }

        return $this->success([
            'summary' => $summary,
            'currency' => 'SAR',
        ], __('Earnings summary retrieved successfully'));
    }

    /**
     * Get earnings history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $earnings = [];

        // Date range
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : now()->subMonths(3);
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now();

        // Get from TeacherEarning model
        $teacherEarnings = TeacherEarning::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($teacherEarnings->isNotEmpty()) {
            foreach ($teacherEarnings as $earning) {
                $earnings[] = [
                    'id' => $earning->id,
                    'type' => $earning->type,
                    'description' => $earning->description,
                    'amount' => $earning->amount,
                    'currency' => $earning->currency ?? 'SAR',
                    'status' => $earning->status,
                    'session_id' => $earning->session_id,
                    'date' => $earning->created_at->toDateString(),
                    'created_at' => $earning->created_at->toISOString(),
                ];
            }
        } else {
            // Calculate from completed sessions
            if ($user->isQuranTeacher()) {
                $quranTeacherId = $user->quranTeacherProfile?->id;
                $hourlyRate = $user->quranTeacherProfile?->hourly_rate ?? 50;

                if ($quranTeacherId) {
                    $quranSessions = QuranSession::where('quran_teacher_id', $quranTeacherId)
                        ->where('status', SessionStatus::COMPLETED->value)
                        ->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->with(['student.user'])
                        ->orderBy('scheduled_at', 'desc')
                        ->get();

                    foreach ($quranSessions as $session) {
                        $earnings[] = [
                            'id' => 'quran-' . $session->id,
                            'type' => 'quran',
                            'description' => 'جلسة قرآنية - ' . ($session->student?->user?->name ?? 'طالب'),
                            'amount' => $hourlyRate,
                            'currency' => 'SAR',
                            'status' => SessionStatus::COMPLETED,
                            'session_id' => $session->id,
                            'date' => $session->scheduled_at?->toDateString(),
                            'created_at' => $session->scheduled_at?->toISOString(),
                        ];
                    }
                }
            }

            if ($user->isAcademicTeacher()) {
                $academicTeacherId = $user->academicTeacherProfile?->id;
                $hourlyRate = $user->academicTeacherProfile?->hourly_rate ?? 60;

                if ($academicTeacherId) {
                    $academicSessions = AcademicSession::where('academic_teacher_id', $academicTeacherId)
                        ->where('status', SessionStatus::COMPLETED->value)
                        ->whereBetween('scheduled_at', [$startDate, $endDate])
                        ->with(['student.user', 'academicSubscription'])
                        ->orderBy('scheduled_at', 'desc')
                        ->get();

                    foreach ($academicSessions as $session) {
                        $earnings[] = [
                            'id' => 'academic-' . $session->id,
                            'type' => 'academic',
                            'description' => 'جلسة أكاديمية - ' . ($session->academicSubscription?->subject_name ?? '') .
                                ' - ' . ($session->student?->user?->name ?? 'طالب'),
                            'amount' => $hourlyRate,
                            'currency' => 'SAR',
                            'status' => SessionStatus::COMPLETED,
                            'session_id' => $session->id,
                            'date' => $session->scheduled_at?->toDateString(),
                            'created_at' => $session->scheduled_at?->toISOString(),
                        ];
                    }
                }
            }

            // Sort by date
            usort($earnings, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
        }

        // Paginate
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $total = count($earnings);
        $earnings = array_slice($earnings, ($page - 1) * $perPage, $perPage);

        // Calculate totals for period
        $totalAmount = array_sum(array_column($earnings, 'amount'));

        return $this->success([
            'earnings' => array_values($earnings),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'total_for_period' => $totalAmount,
            'currency' => 'SAR',
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ], __('Earnings history retrieved successfully'));
    }

    /**
     * Get payouts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function payouts(Request $request): JsonResponse
    {
        $user = $request->user();

        $payouts = TeacherPayout::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'payouts' => collect($payouts->items())->map(fn($payout) => [
                'id' => $payout->id,
                'amount' => $payout->amount,
                'currency' => $payout->currency ?? 'SAR',
                'status' => $payout->status,
                'payment_method' => $payout->payment_method,
                'reference' => $payout->reference,
                'notes' => $payout->notes,
                'period_start' => $payout->period_start?->toDateString(),
                'period_end' => $payout->period_end?->toDateString(),
                'processed_at' => $payout->processed_at?->toISOString(),
                'created_at' => $payout->created_at->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $payouts->currentPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
                'total_pages' => $payouts->lastPage(),
                'has_more' => $payouts->hasMorePages(),
            ],
        ], __('Payouts retrieved successfully'));
    }
}
