<?php

namespace Database\Seeders;

use App\Enums\PayoutStatus;
use App\Enums\SessionStatus;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Database\Seeder;

class AmerSolimanEarningsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Adding comprehensive earnings test data for Amer Soliman...');

        // Get academy
        $academy = Academy::where('subdomain', 'itqan-academy')->first();
        if (! $academy) {
            $this->command->error('Default academy not found!');

            return;
        }

        // Get existing Amer Soliman teachers
        $amerQuran = User::where('email', 'amer.quran@itqan.test')->first();
        $amerAcademic = User::where('email', 'amer.academic@itqan.test')->first();

        if (! $amerQuran || ! $amerQuran->quranTeacherProfile) {
            $this->command->error('Quran teacher Amer Soliman not found!');

            return;
        }

        if (! $amerAcademic || ! $amerAcademic->academicTeacherProfile) {
            $this->command->error('Academic teacher Amer Soliman not found!');

            return;
        }

        $quranProfile = $amerQuran->quranTeacherProfile;
        $academicProfile = $amerAcademic->academicTeacherProfile;

        // Get admin for approvals
        $admin = User::where('user_type', 'super_admin')->first();

        $this->command->info('Found teachers - creating comprehensive earnings...');

        // Get some sessions for each teacher
        $quranSessions = QuranSession::where('quran_teacher_id', $quranProfile->id)
            ->where('status', SessionStatus::COMPLETED)
            ->whereNotNull('ended_at')
            ->take(30)
            ->get();

        $academicSessions = AcademicSession::where('academic_teacher_id', $academicProfile->id)
            ->where('status', SessionStatus::COMPLETED)
            ->whereNotNull('ended_at')
            ->take(30)
            ->get();

        // Create diverse earnings for Quran sessions
        $quranEarningsCount = 0;
        foreach ($quranSessions as $index => $session) {
            // Skip if earning already exists
            if (TeacherEarning::where('session_type', QuranSession::class)
                ->where('session_id', $session->id)->exists()) {
                continue;
            }

            // Use different calculation methods
            $methods = [
                ['method' => 'individual_rate', 'amount' => 120],
                ['method' => 'group_rate', 'amount' => 80],
                ['method' => 'per_session', 'amount' => 110],
                ['method' => 'per_student', 'amount' => 100],
                ['method' => 'fixed', 'amount' => 105],
            ];

            $methodData = $methods[$index % count($methods)];

            TeacherEarning::create([
                'academy_id' => $academy->id,
                'teacher_type' => QuranTeacherProfile::class,
                'teacher_id' => $quranProfile->id,
                'session_type' => QuranSession::class,
                'session_id' => $session->id,
                'amount' => $methodData['amount'],
                'calculation_method' => $methodData['method'],
                'rate_snapshot' => [
                    'individual_rate' => $quranProfile->session_price_individual ?? 120,
                    'group_rate' => $quranProfile->session_price_group ?? 80,
                ],
                'calculation_metadata' => [
                    'test_data' => true,
                    'calculation_notes' => "Test: {$methodData['method']}",
                ],
                'earning_month' => $session->ended_at?->startOfMonth() ?? now()->startOfMonth(),
                'session_completed_at' => $session->ended_at ?? now(),
                'calculated_at' => now(),
                'is_finalized' => $index < 20, // Finalize first 20
                'is_disputed' => $index === 5, // Make one disputed
                'dispute_notes' => $index === 5 ? 'نزاع تجريبي - للاختبار فقط' : null,
            ]);
            $quranEarningsCount++;
        }

        $this->command->info("Created {$quranEarningsCount} Quran earnings");

        // Create diverse earnings for Academic sessions
        $academicEarningsCount = 0;
        foreach ($academicSessions as $index => $session) {
            // Skip if earning already exists
            if (TeacherEarning::where('session_type', AcademicSession::class)
                ->where('session_id', $session->id)->exists()) {
                continue;
            }

            $methods = [
                ['method' => 'individual_rate', 'amount' => 150],
                ['method' => 'per_session', 'amount' => 145],
                ['method' => 'per_student', 'amount' => 140],
                ['method' => 'fixed', 'amount' => 130],
            ];

            $methodData = $methods[$index % count($methods)];

            TeacherEarning::create([
                'academy_id' => $academy->id,
                'teacher_type' => AcademicTeacherProfile::class,
                'teacher_id' => $academicProfile->id,
                'session_type' => AcademicSession::class,
                'session_id' => $session->id,
                'amount' => $methodData['amount'],
                'calculation_method' => $methodData['method'],
                'rate_snapshot' => [
                    'individual_rate' => $academicProfile->session_price_individual ?? 150,
                ],
                'calculation_metadata' => [
                    'test_data' => true,
                    'calculation_notes' => "Test: {$methodData['method']}",
                ],
                'earning_month' => $session->ended_at?->startOfMonth() ?? now()->startOfMonth(),
                'session_completed_at' => $session->ended_at ?? now(),
                'calculated_at' => now(),
                'is_finalized' => $index < 20,
                'is_disputed' => false,
            ]);
            $academicEarningsCount++;
        }

        $this->command->info("Created {$academicEarningsCount} Academic earnings");

        // Create comprehensive payouts for last 3 months
        $this->command->info('Creating monthly payouts...');
        $months = [
            now()->subMonths(2)->startOfMonth(),
            now()->subMonth()->startOfMonth(),
            now()->startOfMonth(),
        ];

        $payoutsCreated = 0;
        $teachers = [
            ['type' => QuranTeacherProfile::class, 'id' => $quranProfile->id, 'name' => 'Amer Soliman (Quran)'],
            ['type' => AcademicTeacherProfile::class, 'id' => $academicProfile->id, 'name' => 'Amer Soliman (Academic)'],
        ];

        foreach ($months as $index => $monthDate) {
            foreach ($teachers as $teacher) {
                // Skip if payout exists
                if (TeacherPayout::where('teacher_type', $teacher['type'])
                    ->where('teacher_id', $teacher['id'])
                    ->whereYear('payout_month', $monthDate->year)
                    ->whereMonth('payout_month', $monthDate->month)
                    ->exists()) {
                    continue;
                }

                // Get earnings for this month
                $earnings = TeacherEarning::where('teacher_type', $teacher['type'])
                    ->where('teacher_id', $teacher['id'])
                    ->whereYear('earning_month', $monthDate->year)
                    ->whereMonth('earning_month', $monthDate->month)
                    ->get();

                if ($earnings->isEmpty()) {
                    continue;
                }

                // Calculate breakdown
                $breakdown = [];
                foreach ($earnings as $earning) {
                    $method = $earning->calculation_method;
                    if (! isset($breakdown[$method])) {
                        $breakdown[$method] = ['count' => 0, 'amount' => 0];
                    }
                    $breakdown[$method]['count']++;
                    $breakdown[$method]['amount'] += $earning->amount;
                }

                // Add bonus/deductions for testing
                if ($index === 0 && $teacher['type'] === QuranTeacherProfile::class) {
                    $breakdown['bonus'] = 500;
                }
                if ($index === 1 && $teacher['type'] === AcademicTeacherProfile::class) {
                    $breakdown['deductions'] = -200;
                }

                $totalAmount = $earnings->sum('amount') + ($breakdown['bonus'] ?? 0) + ($breakdown['deductions'] ?? 0);

                // Status based on month
                $status = PayoutStatus::PENDING;
                $approvedAt = null;
                $approvedBy = null;

                if ($index === 0) {
                    $status = PayoutStatus::APPROVED;
                    $approvedAt = $monthDate->copy()->addDays(10);
                    $approvedBy = $admin?->id;
                } elseif ($index === 1) {
                    $status = PayoutStatus::APPROVED;
                    $approvedAt = $monthDate->copy()->addDays(8);
                    $approvedBy = $admin?->id;
                }

                $payout = TeacherPayout::create([
                    'academy_id' => $academy->id,
                    'teacher_type' => $teacher['type'],
                    'teacher_id' => $teacher['id'],
                    'payout_month' => $monthDate,
                    'total_amount' => $totalAmount,
                    'sessions_count' => $earnings->count(),
                    'breakdown' => $breakdown,
                    'status' => $status,
                    'approved_by' => $approvedBy,
                    'approved_at' => $approvedAt,
                    'approval_notes' => $status === PayoutStatus::APPROVED ? 'Test approval' : null,
                ]);

                // Link earnings if approved
                if ($status === PayoutStatus::APPROVED) {
                    $earnings->each(fn ($e) => $e->update(['payout_id' => $payout->id, 'is_finalized' => true]));
                }

                $payoutsCreated++;
                $this->command->info("  Payout: {$teacher['name']} - {$monthDate->format('Y-m')} ({$status->value})");
            }
        }

        $this->command->info("\n✅ Test data created successfully!");
        $this->command->info("Quran Earnings: {$quranEarningsCount}");
        $this->command->info("Academic Earnings: {$academicEarningsCount}");
        $this->command->info("Payouts: {$payoutsCreated}");
        $this->command->info("\nCalculation methods tested:");
        $this->command->info('  ✓ individual_rate, group_rate, per_session, per_student, fixed');
        $this->command->info('  ✓ bonus, deductions, disputed');
    }
}
