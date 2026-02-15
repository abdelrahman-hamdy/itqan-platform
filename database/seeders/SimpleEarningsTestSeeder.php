<?php

namespace Database\Seeders;

use App\Enums\PayoutStatus;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpleEarningsTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Creating comprehensive test earnings data for Amer Soliman...');

        $academy = Academy::where('subdomain', 'itqan-academy')->first();
        if (! $academy) {
            $this->command->error('Academy not found!');

            return;
        }

        $amerQuran = User::where('email', 'amer.quran@itqan.test')->first();
        $amerAcademic = User::where('email', 'amer.academic@itqan.test')->first();

        if (! $amerQuran || ! $amerAcademic) {
            $this->command->error('Teachers not found!');

            return;
        }

        $quranProfile = $amerQuran->quranTeacherProfile;
        $academicProfile = $amerAcademic->academicTeacherProfile;

        if (! $quranProfile || ! $academicProfile) {
            $this->command->error('Teacher profiles not found!');

            return;
        }

        $admin = User::where('user_type', 'super_admin')->first();

        // Clear existing test earnings for these teachers
        $this->command->info('Clearing existing earnings...');
        TeacherEarning::where('teacher_type', QuranTeacherProfile::class)
            ->where('teacher_id', $quranProfile->id)
            ->delete();
        TeacherEarning::where('teacher_type', AcademicTeacherProfile::class)
            ->where('teacher_id', $academicProfile->id)
            ->delete();
        TeacherPayout::where('teacher_type', QuranTeacherProfile::class)
            ->where('teacher_id', $quranProfile->id)
            ->delete();
        TeacherPayout::where('teacher_type', AcademicTeacherProfile::class)
            ->where('teacher_id', $academicProfile->id)
            ->delete();

        // Create comprehensive earnings for Quran teacher
        $this->command->info('Creating Quran teacher earnings...');
        $quranCount = 0;
        $sessionIdCounter = 900000; // Start from high number to avoid conflicts

        $methods = [
            ['method' => 'individual_rate', 'amount' => 120, 'count' => 15],
            ['method' => 'group_rate', 'amount' => 80, 'count' => 10],
            ['method' => 'per_session', 'amount' => 110, 'count' => 8],
            ['method' => 'per_student', 'amount' => 100, 'count' => 7],
            ['method' => 'fixed', 'amount' => 105, 'count' => 5],
        ];

        foreach ($methods as $methodData) {
            for ($i = 0; $i < $methodData['count']; $i++) {
                $monthsAgo = rand(0, 2);
                $completedAt = now()->subMonths($monthsAgo)->subDays(rand(1, 25));

                TeacherEarning::create([
                    'academy_id' => $academy->id,
                    'teacher_type' => QuranTeacherProfile::class,
                    'teacher_id' => $quranProfile->id,
                    'session_type' => 'App\Models\QuranSession', // Placeholder for test data
                    'session_id' => ++$sessionIdCounter, // Unique placeholder ID
                    'amount' => $methodData['amount'] + rand(-10, 10), // Small variation
                    'calculation_method' => $methodData['method'],
                    'rate_snapshot' => [
                        'individual_rate' => 120,
                        'group_rate' => 80,
                    ],
                    'calculation_metadata' => [
                        'test_data' => true,
                        'method' => $methodData['method'],
                        'variation' => rand(-10, 10),
                    ],
                    'earning_month' => $completedAt->copy()->startOfMonth(),
                    'session_completed_at' => $completedAt,
                    'calculated_at' => $completedAt->copy()->addHours(2),
                    'is_finalized' => $monthsAgo > 0,
                    'is_disputed' => false,
                ]);
                $quranCount++;
            }
        }

        // Add one disputed earning
        TeacherEarning::create([
            'academy_id' => $academy->id,
            'teacher_type' => QuranTeacherProfile::class,
            'teacher_id' => $quranProfile->id,
            'session_type' => 'App\Models\QuranSession',
            'session_id' => ++$sessionIdCounter, // Unique placeholder for disputed earning
            'amount' => 120,
            'calculation_method' => 'individual_rate',
            'rate_snapshot' => ['individual_rate' => 120],
            'calculation_metadata' => ['test_data' => true, 'disputed' => true],
            'earning_month' => now()->subMonth()->startOfMonth(),
            'session_completed_at' => now()->subMonth()->subDays(10),
            'calculated_at' => now()->subMonth()->subDays(9),
            'is_finalized' => false,
            'is_disputed' => true,
            'dispute_notes' => 'نزاع تجريبي - الطالب يدعي أنه لم يحضر الجلسة كاملة',
        ]);
        $quranCount++;

        $this->command->info("Created {$quranCount} Quran earnings");

        // Create comprehensive earnings for Academic teacher
        $this->command->info('Creating Academic teacher earnings...');
        $academicCount = 0;

        $methods = [
            ['method' => 'individual_rate', 'amount' => 150, 'count' => 18],
            ['method' => 'per_session', 'amount' => 145, 'count' => 10],
            ['method' => 'per_student', 'amount' => 140, 'count' => 8],
            ['method' => 'fixed', 'amount' => 130, 'count' => 6],
        ];

        foreach ($methods as $methodData) {
            for ($i = 0; $i < $methodData['count']; $i++) {
                $monthsAgo = rand(0, 2);
                $completedAt = now()->subMonths($monthsAgo)->subDays(rand(1, 25));

                TeacherEarning::create([
                    'academy_id' => $academy->id,
                    'teacher_type' => AcademicTeacherProfile::class,
                    'teacher_id' => $academicProfile->id,
                    'session_type' => 'App\Models\AcademicSession',
                    'session_id' => ++$sessionIdCounter, // Unique placeholder ID
                    'amount' => $methodData['amount'] + rand(-15, 15),
                    'calculation_method' => $methodData['method'],
                    'rate_snapshot' => [
                        'individual_rate' => 150,
                    ],
                    'calculation_metadata' => [
                        'test_data' => true,
                        'method' => $methodData['method'],
                        'subject' => 'Mathematics',
                    ],
                    'earning_month' => $completedAt->copy()->startOfMonth(),
                    'session_completed_at' => $completedAt,
                    'calculated_at' => $completedAt->copy()->addHours(2),
                    'is_finalized' => $monthsAgo > 0,
                    'is_disputed' => false,
                ]);
                $academicCount++;
            }
        }

        $this->command->info("Created {$academicCount} Academic earnings");

        // Create monthly payouts
        $this->command->info('Creating monthly payouts...');
        $months = [
            now()->subMonths(2)->startOfMonth(),
            now()->subMonth()->startOfMonth(),
            now()->startOfMonth(),
        ];

        $payoutsCreated = 0;
        $teachers = [
            ['type' => QuranTeacherProfile::class, 'id' => $quranProfile->id, 'name' => 'Quran'],
            ['type' => AcademicTeacherProfile::class, 'id' => $academicProfile->id, 'name' => 'Academic'],
        ];

        foreach ($months as $index => $monthDate) {
            foreach ($teachers as $teacher) {
                $earnings = TeacherEarning::where('teacher_type', $teacher['type'])
                    ->where('teacher_id', $teacher['id'])
                    ->whereYear('earning_month', $monthDate->year)
                    ->whereMonth('earning_month', $monthDate->month)
                    ->get();

                if ($earnings->isEmpty()) {
                    continue;
                }

                // Calculate breakdown by method
                $breakdown = [];
                foreach ($earnings as $earning) {
                    $method = $earning->calculation_method;
                    if (! isset($breakdown[$method])) {
                        $breakdown[$method] = ['count' => 0, 'amount' => 0];
                    }
                    $breakdown[$method]['count']++;
                    $breakdown[$method]['amount'] += $earning->amount;
                }

                // Add bonus for month 1 (Quran)
                if ($index === 0 && $teacher['type'] === QuranTeacherProfile::class) {
                    $breakdown['bonus'] = 500;
                }

                // Add deductions for month 2 (Academic)
                if ($index === 1 && $teacher['type'] === AcademicTeacherProfile::class) {
                    $breakdown['deductions'] = -200;
                }

                $totalAmount = $earnings->sum('amount');
                $totalAmount += $breakdown['bonus'] ?? 0;
                $totalAmount += $breakdown['deductions'] ?? 0;

                // Determine status
                $status = PayoutStatus::PENDING;
                $approvedAt = null;
                $approvedBy = null;
                $notes = null;

                if ($index === 0) {
                    // Month 1 - approved
                    $status = PayoutStatus::APPROVED;
                    $approvedAt = $monthDate->copy()->addDays(10);
                    $approvedBy = $admin?->id;
                    $notes = 'معتمد - أداء ممتاز';
                } elseif ($index === 1) {
                    // Month 2 - approved
                    $status = PayoutStatus::APPROVED;
                    $approvedAt = $monthDate->copy()->addDays(8);
                    $approvedBy = $admin?->id;
                    $notes = 'معتمد';
                }
                // Month 3 (current) - stays pending

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
                    'approval_notes' => $notes,
                ]);

                // Link earnings to payout if approved
                if ($status === PayoutStatus::APPROVED) {
                    $earnings->each(function ($earning) use ($payout) {
                        $earning->update([
                            'payout_id' => $payout->id,
                            'is_finalized' => true,
                        ]);
                    });
                }

                $payoutsCreated++;
                $statusLabel = $status === PayoutStatus::APPROVED ? '✓ Approved' : '⏳ Pending';
                $this->command->info("  {$teacher['name']} - {$monthDate->format('Y-m')} - {$statusLabel}");
            }
        }

        $this->command->info("\n✅ Comprehensive test data created successfully!");
        $this->command->info("\nSummary:");
        $this->command->info('  Teacher: Amer Soliman (عامر سليمان)');
        $this->command->info("  Quran Earnings: {$quranCount}");
        $this->command->info("  Academic Earnings: {$academicCount}");
        $this->command->info("  Total Payouts: {$payoutsCreated}");
        $this->command->info("\nEarning Types Tested:");
        $this->command->info('  ✓ individual_rate (جلسة فردية)');
        $this->command->info('  ✓ group_rate (جلسة جماعية) - Quran only');
        $this->command->info('  ✓ per_session (حسب الجلسة)');
        $this->command->info('  ✓ per_student (حسب الطالب)');
        $this->command->info('  ✓ fixed (مبلغ ثابت)');
        $this->command->info('  ✓ bonus (مكافأة) - in breakdown');
        $this->command->info('  ✓ deductions (خصومات) - in breakdown');
        $this->command->info('  ✓ disputed earnings (نزاع)');
        $this->command->info("\nYou can now test the Teacher Earnings and Payouts resources in Filament!");
    }
}
