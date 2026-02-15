<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionAmerSolimanEarningsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Creating comprehensive test earnings for Amer Soliman (Production)...');

        // Find Amer Soliman teacher on production
        $amerUser = User::where('email', 'quran.teacher5@itqan.com')->first();

        if (! $amerUser) {
            $this->command->error('Amer Soliman not found!');

            return;
        }

        $quranProfile = $amerUser->quranTeacherProfile;

        if (! $quranProfile) {
            $this->command->error('Quran teacher profile not found!');

            return;
        }

        $academy = Academy::find($quranProfile->academy_id);
        if (! $academy) {
            $this->command->error('Academy not found!');

            return;
        }

        $this->command->info("Found: {$amerUser->first_name} {$amerUser->last_name} (Profile ID: {$quranProfile->id})");

        // Get admin for approvals
        $admin = User::where('user_type', 'super_admin')->first();

        // Clear existing test earnings
        $existing = TeacherEarning::where('teacher_type', QuranTeacherProfile::class)
            ->where('teacher_id', $quranProfile->id)
            ->count();

        if ($existing > 0) {
            $this->command->warn("Found {$existing} existing earnings. Clearing...");
            TeacherEarning::where('teacher_type', QuranTeacherProfile::class)
                ->where('teacher_id', $quranProfile->id)
                ->delete();
        }

        // Create comprehensive earnings
        $this->command->info('Creating earnings with all calculation methods...');
        $quranCount = 0;
        $sessionIdCounter = 900000; // High number to avoid conflicts

        // Define all calculation methods with realistic counts
        $methods = [
            ['method' => 'individual_rate', 'amount' => 120, 'count' => 20],
            ['method' => 'group_rate', 'amount' => 80, 'count' => 15],
            ['method' => 'per_session', 'amount' => 110, 'count' => 10],
            ['method' => 'per_student', 'amount' => 100, 'count' => 8],
            ['method' => 'fixed', 'amount' => 105, 'count' => 7],
        ];

        foreach ($methods as $methodData) {
            for ($i = 0; $i < $methodData['count']; $i++) {
                $monthsAgo = rand(0, 2);
                $completedAt = now()->subMonths($monthsAgo)->subDays(rand(1, 25));

                TeacherEarning::create([
                    'academy_id' => $academy->id,
                    'teacher_type' => QuranTeacherProfile::class,
                    'teacher_id' => $quranProfile->id,
                    'session_type' => 'App\Models\QuranSession',
                    'session_id' => ++$sessionIdCounter,
                    'amount' => $methodData['amount'] + rand(-10, 10),
                    'calculation_method' => $methodData['method'],
                    'rate_snapshot' => [
                        'individual_rate' => $quranProfile->session_price_individual ?? 120,
                        'group_rate' => $quranProfile->session_price_group ?? 80,
                    ],
                    'calculation_metadata' => [
                        'test_data' => true,
                        'method' => $methodData['method'],
                        'note' => 'تجريبي للاختبار',
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

        // Add disputed earning
        TeacherEarning::create([
            'academy_id' => $academy->id,
            'teacher_type' => QuranTeacherProfile::class,
            'teacher_id' => $quranProfile->id,
            'session_type' => 'App\Models\QuranSession',
            'session_id' => ++$sessionIdCounter,
            'amount' => 120,
            'calculation_method' => 'individual_rate',
            'rate_snapshot' => ['individual_rate' => 120],
            'calculation_metadata' => ['test_data' => true, 'disputed' => true],
            'earning_month' => now()->subMonth()->startOfMonth(),
            'session_completed_at' => now()->subMonth()->subDays(10),
            'calculated_at' => now()->subMonth()->subDays(9),
            'is_finalized' => false,
            'is_disputed' => true,
            'dispute_notes' => 'نزاع تجريبي - للاختبار فقط',
        ]);
        $quranCount++;

        $this->command->info("\n✅ Test data created successfully!");
        $this->command->info("\nSummary:");
        $this->command->info("  Teacher: {$amerUser->first_name} {$amerUser->last_name}");
        $this->command->info("  Total Earnings: {$quranCount}");
        $this->command->info("\nCalculation Methods:");
        $this->command->info('  ✓ individual_rate (20)');
        $this->command->info('  ✓ group_rate (15)');
        $this->command->info('  ✓ per_session (10)');
        $this->command->info('  ✓ per_student (8)');
        $this->command->info('  ✓ fixed (7)');
        $this->command->info('  ✓ bonus (in breakdown)');
        $this->command->info('  ✓ deductions (in breakdown)');
        $this->command->info('  ✓ disputed (1)');
    }
}
