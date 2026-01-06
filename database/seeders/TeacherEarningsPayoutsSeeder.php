<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TeacherEarningsPayoutsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Teacher Earnings and Payouts...');

        // Get default academy
        $academy = Academy::where('subdomain', 'itqan-academy')->first();
        if (! $academy) {
            $this->command->warn('Default academy not found. Skipping earnings seeder.');

            return;
        }

        // Get teachers
        $quranTeachers = QuranTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->take(5)
            ->get();

        $academicTeachers = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->take(5)
            ->get();

        if ($quranTeachers->isEmpty() && $academicTeachers->isEmpty()) {
            $this->command->warn('No active teachers found. Skipping earnings seeder.');

            return;
        }

        // Get completed sessions (ended_at is used for completion time)
        $quranSessions = QuranSession::where('academy_id', $academy->id)
            ->where('status', 'completed')
            ->whereNotNull('ended_at')
            ->take(50)
            ->get();

        $academicSessions = AcademicSession::where('academy_id', $academy->id)
            ->where('status', 'completed')
            ->whereNotNull('ended_at')
            ->take(50)
            ->get();

        // Get admin user for approvals
        $adminUser = User::first();

        $earningsCreated = 0;
        $payoutsCreated = 0;

        // Create earnings for Quran sessions
        foreach ($quranSessions as $session) {
            if (! $session->quranTeacher) {
                continue;
            }

            $existing = TeacherEarning::where('session_type', 'quran_session')
                ->where('session_id', $session->id)
                ->exists();

            if ($existing) {
                continue;
            }

            $amount = $session->session_type === 'individual'
                ? ($session->quranTeacher->session_price_individual ?? rand(80, 150))
                : ($session->quranTeacher->session_price_group ?? rand(50, 100));

            TeacherEarning::create([
                'academy_id' => $academy->id,
                'teacher_type' => QuranTeacherProfile::class,
                'teacher_id' => $session->quranTeacher->id,
                'session_type' => 'quran_session',
                'session_id' => $session->id,
                'amount' => $amount,
                'calculation_method' => $session->session_type === 'individual' ? 'individual_rate' : 'group_rate',
                'rate_snapshot' => [
                    'individual_rate' => $session->quranTeacher->session_price_individual ?? 100,
                    'group_rate' => $session->quranTeacher->session_price_group ?? 70,
                ],
                'calculation_metadata' => [
                    'session_type' => $session->session_type,
                    'duration_minutes' => $session->duration_minutes ?? 45,
                ],
                'earning_month' => Carbon::parse($session->ended_at)->startOfMonth()->format('Y-m-d'),
                'session_completed_at' => $session->ended_at,
                'calculated_at' => now(),
                'is_finalized' => rand(0, 1) == 1,
                'is_disputed' => false,
            ]);
            $earningsCreated++;
        }

        // Create earnings for Academic sessions
        foreach ($academicSessions as $session) {
            if (! $session->academicTeacher) {
                continue;
            }

            $existing = TeacherEarning::where('session_type', 'academic_session')
                ->where('session_id', $session->id)
                ->exists();

            if ($existing) {
                continue;
            }

            $amount = $session->academicTeacher->session_price_individual ?? rand(100, 200);

            TeacherEarning::create([
                'academy_id' => $academy->id,
                'teacher_type' => AcademicTeacherProfile::class,
                'teacher_id' => $session->academicTeacher->id,
                'session_type' => 'academic_session',
                'session_id' => $session->id,
                'amount' => $amount,
                'calculation_method' => 'individual_rate',
                'rate_snapshot' => [
                    'individual_rate' => $session->academicTeacher->session_price_individual ?? 150,
                ],
                'calculation_metadata' => [
                    'subject' => $session->academicIndividualLesson?->subject?->name ?? 'Unknown',
                    'duration_minutes' => $session->duration_minutes ?? 60,
                ],
                'earning_month' => Carbon::parse($session->ended_at)->startOfMonth()->format('Y-m-d'),
                'session_completed_at' => $session->ended_at,
                'calculated_at' => now(),
                'is_finalized' => rand(0, 1) == 1,
                'is_disputed' => false,
            ]);
            $earningsCreated++;
        }

        $this->command->info("Created {$earningsCreated} teacher earnings.");

        // Create sample payouts for last 3 months
        $months = [
            Carbon::now()->subMonths(2)->startOfMonth(),
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->startOfMonth(),
        ];

        $allTeachers = collect();
        foreach ($quranTeachers as $t) {
            $allTeachers->push(['type' => QuranTeacherProfile::class, 'id' => $t->id, 'profile' => $t]);
        }
        foreach ($academicTeachers as $t) {
            $allTeachers->push(['type' => AcademicTeacherProfile::class, 'id' => $t->id, 'profile' => $t]);
        }

        foreach ($months as $monthDate) {
            foreach ($allTeachers as $teacher) {
                // Check if payout exists
                $existing = TeacherPayout::where('teacher_type', $teacher['type'])
                    ->where('teacher_id', $teacher['id'])
                    ->whereYear('payout_month', $monthDate->year)
                    ->whereMonth('payout_month', $monthDate->month)
                    ->exists();

                if ($existing) {
                    continue;
                }

                // Get earnings for this teacher/month
                $earnings = TeacherEarning::where('teacher_type', $teacher['type'])
                    ->where('teacher_id', $teacher['id'])
                    ->whereYear('earning_month', $monthDate->year)
                    ->whereMonth('earning_month', $monthDate->month)
                    ->get();

                // Create a payout even if no real earnings (for demo)
                $totalAmount = $earnings->sum('amount');
                if ($totalAmount == 0) {
                    $totalAmount = rand(500, 3000);
                }

                $sessionsCount = $earnings->count() ?: rand(5, 20);

                // Determine status based on month
                $status = 'pending';
                $approvedAt = null;
                $approvedBy = null;

                if ($monthDate->lt(Carbon::now()->subMonth())) {
                    // Older months - mostly approved
                    $status = 'approved';
                    $approvedAt = $monthDate->copy()->addDays(rand(5, 15));
                    $approvedBy = $adminUser?->id;
                } elseif ($monthDate->eq(Carbon::now()->subMonth()->startOfMonth())) {
                    // Last month - mix of statuses
                    $rand = rand(1, 10);
                    if ($rand <= 7) {
                        $status = 'approved';
                        $approvedAt = $monthDate->copy()->addDays(rand(5, 15));
                        $approvedBy = $adminUser?->id;
                    }
                    // Otherwise stays as pending
                }

                TeacherPayout::create([
                    'academy_id' => $academy->id,
                    'teacher_type' => $teacher['type'],
                    'teacher_id' => $teacher['id'],
                    'payout_month' => $monthDate->format('Y-m-d'),
                    'total_amount' => $totalAmount,
                    'sessions_count' => $sessionsCount,
                    'breakdown' => [
                        'individual_rate' => ['count' => (int) ($sessionsCount * 0.7), 'amount' => $totalAmount * 0.7],
                        'group_rate' => ['count' => (int) ($sessionsCount * 0.3), 'amount' => $totalAmount * 0.3],
                    ],
                    'status' => $status,
                    'approved_by' => $approvedBy,
                    'approved_at' => $approvedAt,
                ]);
                $payoutsCreated++;

                // Link earnings to payout if status is approved
                if ($status === 'approved') {
                    $payout = TeacherPayout::where('teacher_type', $teacher['type'])
                        ->where('teacher_id', $teacher['id'])
                        ->whereYear('payout_month', $monthDate->year)
                        ->whereMonth('payout_month', $monthDate->month)
                        ->first();

                    if ($payout) {
                        $earnings->each(function ($earning) use ($payout) {
                            $earning->update([
                                'payout_id' => $payout->id,
                                'is_finalized' => true,
                            ]);
                        });
                    }
                }
            }
        }

        $this->command->info("Created {$payoutsCreated} teacher payouts.");
        $this->command->info('✅ Teacher Earnings and Payouts seeding completed!');
    }
}
