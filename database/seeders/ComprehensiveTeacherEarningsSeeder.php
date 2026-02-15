<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\PayoutStatus;
use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveTeacherEarningsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating comprehensive earnings test data...');

        // Get or create default academy
        $academy = Academy::where('subdomain', 'itqan-academy')->first();
        if (! $academy) {
            $this->command->warn('Default academy not found. Creating one...');
            $academy = Academy::create([
                'name' => 'أكاديمية إتقان',
                'subdomain' => 'itqan-academy',
                'status' => 'active',
                'timezone' => 'Asia/Riyadh',
            ]);
        }

        // Create or find admin user for approvals
        $adminUser = User::where('email', 'admin@itqan.test')->first();
        if (! $adminUser) {
            $adminUser = User::create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@itqan.test',
                'password' => Hash::make('password'),
                'phone' => '+966500000000',
                'gender' => Gender::MALE,
                'user_type' => UserType::SUPER_ADMIN,
            ]);
        }

        // Create Amer Soliman as Quran Teacher
        $this->command->info('Creating Amer Soliman (Quran Teacher)...');
        $amerQuranUser = User::where('email', 'amer.quran@itqan.test')->first();
        if (! $amerQuranUser) {
            $amerQuranUser = User::create([
                'first_name' => 'عامر',
                'last_name' => 'سليمان',
                'email' => 'amer.quran@itqan.test',
                'password' => Hash::make('password'),
                'phone' => '+966501234567',
                'gender' => Gender::MALE,
                'user_type' => UserType::QURAN_TEACHER,
                'active_status' => true,
            ]);
        }

        $amerQuranProfile = QuranTeacherProfile::where('user_id', $amerQuranUser->id)->first();
        if (! $amerQuranProfile) {
            $amerQuranProfile = QuranTeacherProfile::create([
                'user_id' => $amerQuranUser->id,
                'academy_id' => $academy->id,
                'bio_arabic' => 'معلم قرآن متميز مع خبرة واسعة في تحفيظ القرآن وتجويده',
                'gender' => Gender::MALE,
                'teaching_experience_years' => 10,
                'session_price_individual' => 120.00,
                'session_price_group' => 80.00,
                'offers_trial_sessions' => true,
                'rating' => 4.8,
                'total_students' => 50,
                'total_sessions' => 200,
            ]);
        }

        // Create Amer Soliman as Academic Teacher
        $this->command->info('Creating Amer Soliman (Academic Teacher)...');
        $amerAcademicUser = User::where('email', 'amer.academic@itqan.test')->first();
        if (! $amerAcademicUser) {
            $amerAcademicUser = User::create([
                'first_name' => 'عامر',
                'last_name' => 'سليمان',
                'email' => 'amer.academic@itqan.test',
                'password' => Hash::make('password'),
                'phone' => '+966501234568',
                'gender' => Gender::MALE,
                'user_type' => UserType::ACADEMIC_TEACHER,
                'active_status' => true,
            ]);
        }

        $amerAcademicProfile = AcademicTeacherProfile::where('user_id', $amerAcademicUser->id)->first();
        if (! $amerAcademicProfile) {
            $amerAcademicProfile = AcademicTeacherProfile::create([
                'user_id' => $amerAcademicUser->id,
                'academy_id' => $academy->id,
                'bio_arabic' => 'معلم أكاديمي متخصص في الرياضيات والعلوم',
                'gender' => Gender::MALE,
                'teaching_experience_years' => 8,
                'session_price_individual' => 150.00,
                'rating' => 4.7,
            ]);
        }

        // Get or create test students
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $studentUser = User::where('email', "student{$i}@itqan.test")->first();
            if (! $studentUser) {
                $studentUser = User::create([
                    'first_name' => "طالب {$i}",
                    'last_name' => 'التجربة',
                    'email' => "student{$i}@itqan.test",
                    'password' => Hash::make('password'),
                    'phone' => '+96650000000'.$i,
                    'gender' => Gender::MALE,
                    'user_type' => UserType::STUDENT,
                ]);
            }

            $student = StudentProfile::where('user_id', $studentUser->id)->first();
            if (! $student) {
                $student = StudentProfile::create([
                    'user_id' => $studentUser->id,
                    'email' => $studentUser->email,
                    'first_name' => $studentUser->first_name,
                    'last_name' => $studentUser->last_name,
                    'phone' => $studentUser->phone,
                    'gender' => $studentUser->gender,
                    'birth_date' => now()->subYears(15),
                ]);
            }
            $students[] = $student;
        }

        // Get or create academic subject and grade level
        $mathSubject = AcademicSubject::where('academy_id', $academy->id)->where('name', 'رياضيات')->first();
        if (! $mathSubject) {
            $mathSubject = AcademicSubject::create([
                'academy_id' => $academy->id,
                'name' => 'رياضيات',
                'name_en' => 'Mathematics',
                'is_active' => true,
            ]);
        }

        $gradeLevel = AcademicGradeLevel::where('academy_id', $academy->id)->first();
        if (! $gradeLevel) {
            $gradeLevel = AcademicGradeLevel::create([
                'academy_id' => $academy->id,
                'name' => 'المرحلة المتوسطة',
                'name_en' => 'Middle School',
                'level_order' => 7,
                'is_active' => true,
            ]);
        }

        // Create Quran Individual Circles and Sessions
        $this->command->info('Creating Quran sessions with various types...');
        $quranEarnings = 0;

        foreach ($students as $index => $student) {
            $circle = QuranIndividualCircle::create([
                'academy_id' => $academy->id,
                'quran_teacher_id' => $amerQuranProfile->id,
                'student_id' => $student->id,
                'session_type' => 'individual',
                'start_date' => now()->subMonths(3),
                'status' => 'active',
            ]);

            // Create different types of sessions for comprehensive testing
            $sessionTypes = [
                // Individual rate sessions (most common)
                ['calculation_method' => 'individual_rate', 'count' => 5, 'amount' => 120],
                // Group rate sessions
                ['calculation_method' => 'group_rate', 'count' => 3, 'amount' => 80],
                // Fixed amount sessions
                ['calculation_method' => 'fixed', 'count' => 2, 'amount' => 100],
                // Per session rate
                ['calculation_method' => 'per_session', 'count' => 2, 'amount' => 110],
            ];

            foreach ($sessionTypes as $sessionTypeData) {
                for ($j = 0; $j < $sessionTypeData['count']; $j++) {
                    // Create sessions across last 3 months
                    $monthsAgo = rand(0, 2);
                    $completedAt = now()->subMonths($monthsAgo)->subDays(rand(1, 28));

                    $session = QuranSession::create([
                        'academy_id' => $academy->id,
                        'quran_teacher_id' => $amerQuranProfile->id,
                        'quran_individual_circle_id' => $circle->id,
                        'student_id' => $student->id,
                        'session_type' => $sessionTypeData['calculation_method'] === 'group_rate' ? 'circle' : 'individual',
                        'scheduled_at' => $completedAt->copy()->subHour(),
                        'started_at' => $completedAt->copy()->subHour(),
                        'ended_at' => $completedAt,
                        'status' => SessionStatus::COMPLETED,
                        'duration_minutes' => 45,
                    ]);

                    // Create earning for this session
                    TeacherEarning::create([
                        'academy_id' => $academy->id,
                        'teacher_type' => QuranTeacherProfile::class,
                        'teacher_id' => $amerQuranProfile->id,
                        'session_type' => QuranSession::class,
                        'session_id' => $session->id,
                        'amount' => $sessionTypeData['amount'],
                        'calculation_method' => $sessionTypeData['calculation_method'],
                        'rate_snapshot' => [
                            'individual_rate' => $amerQuranProfile->session_price_individual,
                            'group_rate' => $amerQuranProfile->session_price_group,
                        ],
                        'calculation_metadata' => [
                            'session_type' => $session->session_type,
                            'duration_minutes' => 45,
                            'calculation_notes' => "Test data for {$sessionTypeData['calculation_method']}",
                        ],
                        'earning_month' => $completedAt->startOfMonth(),
                        'session_completed_at' => $completedAt,
                        'calculated_at' => $completedAt->copy()->addHour(),
                        'is_finalized' => $monthsAgo > 0, // Finalize older months
                        'is_disputed' => false,
                    ]);
                    $quranEarnings++;
                }
            }
        }

        $this->command->info("Created {$quranEarnings} Quran earnings for Amer Soliman.");

        // Create Academic Individual Lessons and Sessions
        $this->command->info('Creating Academic sessions with various types...');
        $academicEarnings = 0;

        foreach ($students as $index => $student) {
            $lesson = AcademicIndividualLesson::create([
                'academy_id' => $academy->id,
                'academic_teacher_id' => $amerAcademicProfile->id,
                'student_id' => $student->id,
                'subject_id' => $mathSubject->id,
                'grade_level_id' => $gradeLevel->id,
                'start_date' => now()->subMonths(3),
                'status' => 'active',
            ]);

            // Create different types of academic sessions
            $sessionTypes = [
                // Individual rate sessions
                ['calculation_method' => 'individual_rate', 'count' => 6, 'amount' => 150],
                // Per student rate
                ['calculation_method' => 'per_student', 'count' => 3, 'amount' => 140],
                // Fixed amount
                ['calculation_method' => 'fixed', 'count' => 2, 'amount' => 130],
                // Per session
                ['calculation_method' => 'per_session', 'count' => 2, 'amount' => 145],
            ];

            foreach ($sessionTypes as $sessionTypeData) {
                for ($j = 0; $j < $sessionTypeData['count']; $j++) {
                    $monthsAgo = rand(0, 2);
                    $completedAt = now()->subMonths($monthsAgo)->subDays(rand(1, 28));

                    $session = AcademicSession::create([
                        'academy_id' => $academy->id,
                        'academic_teacher_id' => $amerAcademicProfile->id,
                        'academic_individual_lesson_id' => $lesson->id,
                        'student_id' => $student->id,
                        'session_type' => 'individual',
                        'scheduled_at' => $completedAt->copy()->subHour(),
                        'started_at' => $completedAt->copy()->subHour(),
                        'ended_at' => $completedAt,
                        'status' => SessionStatus::COMPLETED,
                        'duration_minutes' => 60,
                        'topic' => 'الجبر والمعادلات',
                    ]);

                    // Create earning for this session
                    TeacherEarning::create([
                        'academy_id' => $academy->id,
                        'teacher_type' => AcademicTeacherProfile::class,
                        'teacher_id' => $amerAcademicProfile->id,
                        'session_type' => AcademicSession::class,
                        'session_id' => $session->id,
                        'amount' => $sessionTypeData['amount'],
                        'calculation_method' => $sessionTypeData['calculation_method'],
                        'rate_snapshot' => [
                            'individual_rate' => $amerAcademicProfile->session_price_individual,
                        ],
                        'calculation_metadata' => [
                            'subject' => $mathSubject->name,
                            'grade_level' => $gradeLevel->name,
                            'duration_minutes' => 60,
                            'calculation_notes' => "Test data for {$sessionTypeData['calculation_method']}",
                        ],
                        'earning_month' => $completedAt->startOfMonth(),
                        'session_completed_at' => $completedAt,
                        'calculated_at' => $completedAt->copy()->addHour(),
                        'is_finalized' => $monthsAgo > 0,
                        'is_disputed' => false,
                    ]);
                    $academicEarnings++;
                }
            }
        }

        $this->command->info("Created {$academicEarnings} Academic earnings for Amer Soliman.");

        // Add some disputed earnings for testing
        $this->command->info('Adding disputed earnings...');
        $disputedSession = QuranSession::create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $amerQuranProfile->id,
            'quran_individual_circle_id' => QuranIndividualCircle::where('quran_teacher_id', $amerQuranProfile->id)->first()->id,
            'student_id' => $students[0]->id,
            'session_type' => 'individual',
            'scheduled_at' => now()->subMonth()->subDays(15),
            'started_at' => now()->subMonth()->subDays(15),
            'ended_at' => now()->subMonth()->subDays(15)->addMinutes(45),
            'status' => SessionStatus::COMPLETED,
            'duration_minutes' => 45,
        ]);

        TeacherEarning::create([
            'academy_id' => $academy->id,
            'teacher_type' => QuranTeacherProfile::class,
            'teacher_id' => $amerQuranProfile->id,
            'session_type' => QuranSession::class,
            'session_id' => $disputedSession->id,
            'amount' => 120,
            'calculation_method' => 'individual_rate',
            'rate_snapshot' => [
                'individual_rate' => $amerQuranProfile->session_price_individual,
            ],
            'calculation_metadata' => [
                'session_type' => 'individual',
                'duration_minutes' => 45,
            ],
            'earning_month' => now()->subMonth()->startOfMonth(),
            'session_completed_at' => $disputedSession->ended_at,
            'calculated_at' => now()->subMonth()->subDays(14),
            'is_finalized' => false,
            'is_disputed' => true,
            'dispute_notes' => 'الطالب لم يحضر كامل الجلسة - نزاع حول المبلغ',
        ]);

        $this->command->info('Created 1 disputed earning.');

        // Create comprehensive payouts for last 3 months
        $this->command->info('Creating monthly payouts...');
        $months = [
            now()->subMonths(2)->startOfMonth(),
            now()->subMonth()->startOfMonth(),
            now()->startOfMonth(),
        ];

        $payoutsCreated = 0;
        $teachersData = [
            ['type' => QuranTeacherProfile::class, 'id' => $amerQuranProfile->id, 'name' => 'عامر سليمان (قرآن)'],
            ['type' => AcademicTeacherProfile::class, 'id' => $amerAcademicProfile->id, 'name' => 'عامر سليمان (أكاديمي)'],
        ];

        foreach ($months as $index => $monthDate) {
            foreach ($teachersData as $teacher) {
                // Get all earnings for this teacher/month
                $earnings = TeacherEarning::where('teacher_type', $teacher['type'])
                    ->where('teacher_id', $teacher['id'])
                    ->whereYear('earning_month', $monthDate->year)
                    ->whereMonth('earning_month', $monthDate->month)
                    ->get();

                if ($earnings->isEmpty()) {
                    continue;
                }

                // Calculate breakdown by calculation method
                $breakdown = [];
                foreach ($earnings as $earning) {
                    $method = $earning->calculation_method;
                    if (! isset($breakdown[$method])) {
                        $breakdown[$method] = ['count' => 0, 'amount' => 0];
                    }
                    $breakdown[$method]['count']++;
                    $breakdown[$method]['amount'] += $earning->amount;
                }

                // Add bonus for excellent performance (only for first teacher/month)
                if ($index === 0 && $teacher['type'] === QuranTeacherProfile::class) {
                    $breakdown['bonus'] = 500;
                }

                // Add deductions for late cancellations (only for second month)
                if ($index === 1 && $teacher['type'] === AcademicTeacherProfile::class) {
                    $breakdown['deductions'] = -200;
                }

                $totalAmount = $earnings->sum('amount');
                if (isset($breakdown['bonus'])) {
                    $totalAmount += $breakdown['bonus'];
                }
                if (isset($breakdown['deductions'])) {
                    $totalAmount += $breakdown['deductions'];
                }

                // Determine payout status based on month
                $status = PayoutStatus::PENDING;
                $approvedAt = null;
                $approvedBy = null;
                $approvalNotes = null;

                if ($index === 0) {
                    // First month (2 months ago) - approved
                    $status = PayoutStatus::APPROVED;
                    $approvedAt = $monthDate->copy()->addDays(10);
                    $approvedBy = $adminUser->id;
                    $approvalNotes = 'تمت الموافقة - أداء ممتاز';
                } elseif ($index === 1) {
                    // Second month (last month) - also approved
                    $status = PayoutStatus::APPROVED;
                    $approvedAt = $monthDate->copy()->addDays(8);
                    $approvedBy = $adminUser->id;
                    $approvalNotes = 'معتمد';
                }
                // Third month (current) stays pending

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
                    'approval_notes' => $approvalNotes,
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
                $this->command->info("  Created payout for {$teacher['name']} - {$monthDate->format('Y-m')} ({$status->value})");
            }
        }

        $this->command->info("Created {$payoutsCreated} payouts.");

        $this->command->info("\n✅ Comprehensive earnings test data created successfully!");
        $this->command->info("\nSummary:");
        $this->command->info('  - Created Quran Teacher: عامر سليمان (amer.quran@itqan.test)');
        $this->command->info('  - Created Academic Teacher: عامر سليمان (amer.academic@itqan.test)');
        $this->command->info("  - Quran Earnings: {$quranEarnings}");
        $this->command->info("  - Academic Earnings: {$academicEarnings}");
        $this->command->info('  - Disputed Earnings: 1');
        $this->command->info("  - Total Payouts: {$payoutsCreated}");
        $this->command->info("\nEarning Calculation Methods Tested:");
        $this->command->info('  ✓ individual_rate (جلسة فردية)');
        $this->command->info('  ✓ group_rate (جلسة جماعية)');
        $this->command->info('  ✓ per_session (حسب الجلسة)');
        $this->command->info('  ✓ per_student (حسب الطالب)');
        $this->command->info('  ✓ fixed (مبلغ ثابت)');
        $this->command->info('  ✓ bonus (مكافأة)');
        $this->command->info('  ✓ deductions (خصومات)');
        $this->command->info('  ✓ disputed (نزاع)');
    }
}
