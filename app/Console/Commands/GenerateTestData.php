<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\HomeworkSubmissionStatus;
use App\Enums\InteractiveCourseStatus;
use App\Enums\LessonStatus;
use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicPackage;
use App\Models\AcademicSession;
use App\Models\AcademicSessionReport;
use App\Models\AcademicSubject;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Academy;
use App\Models\AcademySettings;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseEnrollment;
use App\Models\InteractiveCourseSession;
use App\Models\ParentProfile;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\StudentSessionReport;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GenerateTestData extends Command
{
    protected $signature = 'app:generate-test-data
                            {--fresh : Delete all existing test data first}
                            {--academy= : Academy subdomain (default: test-academy)}';

    protected $description = 'Generate comprehensive test data for all user roles and features';

    /**
     * Hide this command in production environments.
     */
    public function isHidden(): bool
    {
        return app()->environment('production');
    }

    protected array $testUsers = [];

    protected ?Academy $academy = null;

    protected array $userCredentials = [];

    protected function getUserCredentials(): array
    {
        $domain = config('seeding.test_email_domain');

        return [
            'super_admin' => ['email' => 'super@'.$domain, 'name' => 'Super Admin'],
            'admin' => ['email' => 'admin@'.$domain, 'name' => 'Academy Admin'],
            'quran_teacher' => ['email' => 'quran.teacher@'.$domain, 'name' => 'Quran Teacher'],
            'academic_teacher' => ['email' => 'academic.teacher@'.$domain, 'name' => 'Academic Teacher'],
            'supervisor' => ['email' => 'supervisor@'.$domain, 'name' => 'Supervisor'],
            'student' => ['email' => 'student@'.$domain, 'name' => 'Test Student'],
            'parent' => ['email' => 'parent@'.$domain, 'name' => 'Test Parent'],
        ];
    }

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command cannot be run in production.');

            return self::FAILURE;
        }

        $this->userCredentials = $this->getUserCredentials();

        $this->newLine();
        $this->info('🚀 COMPREHENSIVE TEST DATA GENERATOR');
        $this->info('=====================================');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->warn('⚠️  Deleting existing test data...');
            $this->deleteTestData();
            $this->info('✅ Old test data deleted');
        }

        try {
            DB::beginTransaction();

            $this->createAcademy();
            $this->createTestUsers();
            $this->createAcademicStructure();
            $this->createQuranStructure();
            $this->createSessions();
            $this->createSubscriptions();
            $this->createPayments();
            $this->createQuizzes();
            $this->createHomework();
            $this->createCertificates();
            $this->createReports();

            DB::commit();

            $this->displaySummary();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error generating test data: '.$e->getMessage());
            $this->error($e->getFile().':'.$e->getLine());

            return Command::FAILURE;
        }
    }

    protected function deleteTestData(): void
    {
        // Delete users with test emails
        $testEmails = array_column($this->userCredentials, 'email');

        // Find user IDs for test emails (for teacher profiles that store personal info on User)
        $testUserIds = User::whereIn('email', $testEmails)->pluck('id')->toArray();

        // Delete related profiles first
        StudentProfile::whereIn('email', $testEmails)->forceDelete();
        ParentProfile::whereIn('email', $testEmails)->forceDelete();
        // Teacher profiles don't have email column - delete by user_id
        QuranTeacherProfile::whereIn('user_id', $testUserIds)->forceDelete();
        AcademicTeacherProfile::whereIn('user_id', $testUserIds)->forceDelete();
        SupervisorProfile::whereIn('email', $testEmails)->forceDelete();

        // Delete users
        User::whereIn('email', $testEmails)->forceDelete();

        // Delete test academy and all related data
        $subdomain = $this->option('academy') ?? 'test-academy';
        $academy = Academy::where('subdomain', $subdomain)->first();

        if ($academy) {
            // Delete academy-related data (order matters for foreign keys)
            // Delete subscriptions first
            QuranSubscription::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();
            AcademicSubscription::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();
            CourseSubscription::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();

            // Delete sessions
            QuranSession::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();
            AcademicSession::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();

            // Delete interactive course sessions (through course)
            $courseIds = InteractiveCourse::withoutGlobalScopes()->where('academy_id', $academy->id)->pluck('id');
            InteractiveCourseSession::withoutGlobalScopes()->whereIn('course_id', $courseIds)->forceDelete();
            InteractiveCourseEnrollment::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();
            InteractiveCourse::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();

            // Delete individual lessons
            AcademicIndividualLesson::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();

            // Delete other data
            AcademySettings::where('academy_id', $academy->id)->delete();
            AcademicGradeLevel::withoutGlobalScopes()->where('academy_id', $academy->id)->delete();
            AcademicSubject::withoutGlobalScopes()->where('academy_id', $academy->id)->delete();
            AcademicPackage::withoutGlobalScopes()->where('academy_id', $academy->id)->delete();
            QuranCircle::withoutGlobalScopes()->where('academy_id', $academy->id)->forceDelete();
            Quiz::where('academy_id', $academy->id)->delete();

            $academy->forceDelete();
        }
    }

    protected function createAcademy(): void
    {
        $this->info('📚 Creating Test Academy...');

        $subdomain = $this->option('academy') ?? 'test-academy';

        $this->academy = Academy::firstOrCreate(
            ['subdomain' => $subdomain],
            [
                'name' => 'Test Academy',
                'is_active' => true,
                'maintenance_mode' => false,
                'logo' => null,
                'primary_color' => 'blue',
                'secondary_color' => 'green',
            ]
        );

        // Create academy settings
        AcademySettings::firstOrCreate(
            ['academy_id' => $this->academy->id],
            [
                'default_session_duration' => 45,
                'max_session_duration' => 120,
                'buffer_minutes' => 5,
                'auto_generate_meetings' => true,
                'meeting_platform' => 'livekit',
                'timezone' => 'Asia/Riyadh',
            ]
        );

        $this->line("   ✅ Academy: {$this->academy->name} ({$subdomain})");
    }

    protected function createTestUsers(): void
    {
        $this->info('👥 Creating Test Users...');

        $testPassword = config('seeding.test_password');
        $password = Hash::make($testPassword);

        foreach ($this->userCredentials as $role => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => explode(' ', $data['name'])[0],
                    'last_name' => explode(' ', $data['name'])[1] ?? 'User',
                    'password' => $password,
                    'user_type' => $role,
                    'email_verified_at' => now(),
                    'active_status' => true,
                    'academy_id' => $role === 'super_admin' ? null : $this->academy->id,
                    'phone' => '05'.rand(10000000, 99999999),
                ]
            );

            $this->testUsers[$role] = $user;

            // Create associated profiles
            $this->createUserProfile($user, $role);

            $this->line("   ✅ {$role}: {$data['email']} / {$testPassword}");
        }
    }

    protected function createUserProfile(User $user, string $role): void
    {
        switch ($role) {
            case 'quran_teacher':
                QuranTeacherProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'academy_id' => $this->academy->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'bio' => 'Experienced Quran teacher with ijazah in Hafs.',
                        'specializations' => ['memorization', 'tajweed'],
                        'teaching_experience_years' => 5,
                        'offers_trial_sessions' => true,
                        'individual_session_price' => 50,
                        'group_session_price' => 30,
                    ]
                );
                break;

            case 'academic_teacher':
                AcademicTeacherProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'academy_id' => $this->academy->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'bio' => 'Experienced academic teacher specializing in mathematics.',
                        'teaching_experience_years' => 7,
                        'teaching_style' => 'Interactive and student-centered approach.',
                        'individual_session_price' => 75,
                    ]
                );
                break;

            case 'supervisor':
                SupervisorProfile::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'academy_id' => $this->academy->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'performance_rating' => 4.5,
                        'notes' => 'Test supervisor profile',
                    ]
                );
                break;

            case 'student':
                // Student profile is auto-created by User model's created event
                // Just update the grade level if needed
                $gradeLevel = AcademicGradeLevel::firstOrCreate(
                    ['academy_id' => $this->academy->id, 'name' => 'Grade 6'],
                    ['order' => 6]
                );

                $studentProfile = StudentProfile::where('user_id', $user->id)->first();
                if ($studentProfile) {
                    $studentProfile->update([
                        'grade_level_id' => $gradeLevel->id,
                        'nationality' => 'SA',
                    ]);
                }
                break;

            case 'parent':
                // Parent profile is auto-created by User model's created event
                // Just update additional fields if needed
                $parentProfile = ParentProfile::where('user_id', $user->id)->first();
                if ($parentProfile) {
                    $parentProfile->update([
                        'relationship_type' => 'father',
                    ]);
                }
                break;
        }
    }

    protected function createAcademicStructure(): void
    {
        $this->info('🎓 Creating Academic Structure...');

        // Create grade levels
        $gradeLevels = [];
        for ($i = 1; $i <= 12; $i++) {
            $gradeLevels[$i] = AcademicGradeLevel::firstOrCreate(
                ['academy_id' => $this->academy->id, 'name' => "Grade $i"],
                ['order' => $i]
            );
        }
        $this->line('   ✅ Created 12 grade levels');

        // Create subjects
        $subjectNames = ['Mathematics', 'Science', 'English', 'Arabic', 'Physics', 'Chemistry'];
        $subjects = [];
        foreach ($subjectNames as $name) {
            $subjects[] = AcademicSubject::firstOrCreate(
                ['academy_id' => $this->academy->id, 'name' => $name],
                ['name_ar' => $this->getArabicSubjectName($name), 'is_active' => true]
            );
        }
        $this->line('   ✅ Created '.count($subjects).' subjects');

        // Create academic packages
        $packages = [
            ['name_ar' => 'الأساسي', 'name_en' => 'Basic', 'sessions_per_month' => 4, 'price' => 200],
            ['name_ar' => 'القياسي', 'name_en' => 'Standard', 'sessions_per_month' => 8, 'price' => 350],
            ['name_ar' => 'المميز', 'name_en' => 'Premium', 'sessions_per_month' => 12, 'price' => 500],
        ];

        foreach ($packages as $pkg) {
            AcademicPackage::firstOrCreate(
                ['academy_id' => $this->academy->id, 'name_ar' => $pkg['name_ar']],
                [
                    'name_en' => $pkg['name_en'],
                    'sessions_per_month' => $pkg['sessions_per_month'],
                    'session_duration_minutes' => 60,
                    'monthly_price' => $pkg['price'],
                    'quarterly_price' => $pkg['price'] * 2.8,
                    'yearly_price' => $pkg['price'] * 10,
                    'currency' => 'SAR',
                    'is_active' => true,
                    'features' => ['online_sessions', 'homework', 'reports'],
                ]
            );
        }
        $this->line('   ✅ Created '.count($packages).' academic packages');
    }

    protected function getArabicSubjectName(string $name): string
    {
        return match ($name) {
            'Mathematics' => 'الرياضيات',
            'Science' => 'العلوم',
            'English' => 'اللغة الإنجليزية',
            'Arabic' => 'اللغة العربية',
            'Physics' => 'الفيزياء',
            'Chemistry' => 'الكيمياء',
            default => $name,
        };
    }

    protected function createQuranStructure(): void
    {
        $this->info('📖 Creating Quran Structure...');

        $quranTeacher = $this->testUsers['quran_teacher'];
        $quranTeacherProfile = QuranTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $quranTeacher->id)
            ->first();

        if (! $quranTeacherProfile) {
            $this->warn('   ⚠️  Quran teacher profile not found - skipping circles');

            return;
        }

        // Create Quran circles
        $circles = [
            ['name' => 'حلقة الحفظ الأساسية', 'type' => 'memorization', 'max' => 10],
            ['name' => 'حلقة التجويد المتقدمة', 'type' => 'recitation', 'max' => 8],
            ['name' => 'حلقة المتقدمين', 'type' => 'advanced', 'max' => 12],
        ];

        foreach ($circles as $circle) {
            QuranCircle::firstOrCreate(
                ['academy_id' => $this->academy->id, 'name_ar' => $circle['name']],
                [
                    'quran_teacher_id' => $quranTeacherProfile->id,
                    'specialization' => $circle['type'] === 'advanced' ? 'memorization' : 'recitation',
                    'memorization_level' => $circle['type'] === 'advanced' ? 'advanced' : 'intermediate',
                    'max_students' => $circle['max'],
                    'enrolled_students' => 0,
                    'status' => 'active',
                    'circle_code' => 'QC-'.rand(10000, 99999),
                ]
            );
        }

        $this->line('   ✅ Created '.count($circles).' Quran circles');
    }

    protected function createSessions(): void
    {
        $this->info('📅 Creating Sessions...');

        $student = $this->testUsers['student'];
        $quranTeacher = $this->testUsers['quran_teacher'];
        $academicTeacher = $this->testUsers['academic_teacher'];

        $quranTeacherProfile = QuranTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $quranTeacher->id)->first();
        $academicTeacherProfile = AcademicTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $academicTeacher->id)->first();

        if (! $quranTeacherProfile || ! $academicTeacherProfile) {
            $this->warn('   ⚠️  Teacher profiles not found - skipping sessions');

            return;
        }

        // Create Quran Sessions with various statuses
        $quranSessions = [
            ['status' => SessionStatus::SCHEDULED, 'days' => 1, 'title' => 'Upcoming Quran Session'],
            ['status' => SessionStatus::SCHEDULED, 'days' => 3, 'title' => 'Next Week Quran Session'],
            ['status' => SessionStatus::COMPLETED, 'days' => -1, 'title' => 'Completed Quran Session'],
            ['status' => SessionStatus::COMPLETED, 'days' => -3, 'title' => 'Past Quran Session'],
            ['status' => SessionStatus::CANCELLED, 'days' => -2, 'title' => 'Cancelled Quran Session'],
        ];

        foreach ($quranSessions as $session) {
            QuranSession::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $quranTeacherProfile->id,
                'student_id' => $student->id,
                'session_type' => 'individual',
                'status' => $session['status'],
                'scheduled_at' => now()->addDays($session['days'])->setTime(14, 0),
                'duration_minutes' => 45,
                'title' => $session['title'],
                'session_code' => 'QS-'.rand(10000, 99999),
            ]);
        }
        $this->line('   ✅ Created '.count($quranSessions).' Quran sessions');

        // Create an individual lesson for academic sessions
        $subject = AcademicSubject::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)->first();
        $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()
            ->where('academy_id', $this->academy->id)->first();

        if (! $subject || ! $gradeLevel) {
            $this->warn('   ⚠️  Subject or grade level not found - skipping academic sessions');

            return;
        }

        $individualLesson = AcademicIndividualLesson::firstOrCreate(
            [
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'academic_teacher_id' => $academicTeacherProfile->id,
            ],
            [
                'name' => 'Private Mathematics Tutoring',
                'academic_subject_id' => $subject->id,
                'academic_grade_level_id' => $gradeLevel->id,
                'status' => LessonStatus::ACTIVE->value,
                'notes' => 'Regular tutoring sessions',
            ]
        );

        // Create Academic Sessions
        $academicSessions = [
            ['status' => SessionStatus::SCHEDULED, 'days' => 2, 'title' => 'Math Tutoring'],
            ['status' => SessionStatus::COMPLETED, 'days' => -2, 'title' => 'Completed Math Session'],
            ['status' => SessionStatus::SCHEDULED, 'days' => 5, 'title' => 'Science Review'],
        ];

        foreach ($academicSessions as $session) {
            AcademicSession::create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $academicTeacherProfile->id,
                'student_id' => $student->id,
                'academic_individual_lesson_id' => $individualLesson->id,
                'session_type' => 'individual',
                'status' => $session['status'],
                'scheduled_at' => now()->addDays($session['days'])->setTime(16, 0),
                'duration_minutes' => 60,
                'title' => $session['title'],
                'session_code' => 'AS-'.$this->academy->id.'-'.rand(1000, 9999),
            ]);
        }
        $this->line('   ✅ Created '.count($academicSessions).' academic sessions');

        // Create Interactive Course
        $course = InteractiveCourse::firstOrCreate(
            ['academy_id' => $this->academy->id, 'title' => 'Advanced Mathematics Course'],
            [
                'title_en' => 'Advanced Mathematics Course',
                'description' => 'Comprehensive mathematics course covering algebra and geometry.',
                'description_en' => 'Comprehensive mathematics course covering algebra and geometry.',
                'assigned_teacher_id' => $academicTeacherProfile->id,
                'subject_id' => $subject->id,
                'grade_level_id' => $gradeLevel->id,
                'course_type' => 'regular',
                'difficulty_level' => 'intermediate',
                'max_students' => 20,
                'duration_weeks' => 8,
                'sessions_per_week' => 2,
                'session_duration_minutes' => 60,
                'total_sessions' => 16,
                'student_price' => 800,
                'teacher_payment' => 200,
                'payment_type' => 'fixed_amount',
                'start_date' => now()->addDays(7),
                'enrollment_deadline' => now()->addDays(5),
                'schedule' => [
                    ['day' => 'sunday', 'start_time' => '18:00', 'end_time' => '19:00'],
                    ['day' => 'wednesday', 'start_time' => '18:00', 'end_time' => '19:00'],
                ],
                'status' => InteractiveCourseStatus::PUBLISHED,
                'is_published' => true,
                'certificate_enabled' => true,
                'created_by' => $this->testUsers['admin']->id,
            ]
        );

        // Create course sessions
        for ($i = 0; $i < 4; $i++) {
            InteractiveCourseSession::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'session_number' => $i + 1,
                ],
                [
                    'title' => 'Session '.($i + 1).': '.($i < 2 ? 'Algebra Basics' : 'Geometry Introduction'),
                    'scheduled_date' => now()->addDays(7 + ($i * 3)),
                    'scheduled_time' => '18:00',
                    'duration_minutes' => 60,
                    'status' => $i < 1 ? SessionStatus::COMPLETED->value : SessionStatus::SCHEDULED->value,
                ]
            );
        }

        // Enroll student in course - need StudentProfile ID, not User ID
        $studentProfile = StudentProfile::withoutGlobalScopes()
            ->where('user_id', $student->id)->first();

        if ($studentProfile) {
            InteractiveCourseEnrollment::firstOrCreate(
                ['course_id' => $course->id, 'student_id' => $studentProfile->id],
                [
                    'academy_id' => $this->academy->id,
                    'enrollment_date' => now(),
                    'enrollment_status' => EnrollmentStatus::ENROLLED->value,
                    'payment_status' => PaymentStatus::COMPLETED->value,
                    'payment_amount' => $course->student_price ?? 800,
                    'completion_percentage' => 0,
                    'attendance_count' => 0,
                    'total_possible_attendance' => $course->total_sessions ?? 16,
                ]
            );
        }

        $this->line('   ✅ Created 1 interactive course with 4 sessions');
    }

    protected function createSubscriptions(): void
    {
        $this->info('💳 Creating Subscriptions...');

        $student = $this->testUsers['student'];
        $quranTeacher = $this->testUsers['quran_teacher'];
        $academicTeacher = $this->testUsers['academic_teacher'];

        $quranTeacherProfile = QuranTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $quranTeacher->id)->first();
        $academicTeacherProfile = AcademicTeacherProfile::withoutGlobalScopes()
            ->where('user_id', $academicTeacher->id)->first();

        if (! $quranTeacherProfile || ! $academicTeacherProfile) {
            $this->warn('   ⚠️  Teacher profiles not found - skipping subscriptions');

            return;
        }

        // Create Quran Subscriptions
        // Note: Only one active individual subscription allowed per student+teacher+academy
        $quranSubscriptions = [
            ['status' => SessionSubscriptionStatus::ACTIVE, 'name' => 'Premium Quran Package', 'type' => 'individual'],
            ['status' => SessionSubscriptionStatus::CANCELLED, 'name' => 'Basic Quran Package', 'type' => 'group'], // Use group to avoid validation
        ];

        foreach ($quranSubscriptions as $sub) {
            $isActive = $sub['status'] === SessionSubscriptionStatus::ACTIVE;
            QuranSubscription::firstOrCreate(
                [
                    'academy_id' => $this->academy->id,
                    'student_id' => $student->id,
                    'package_name_ar' => $sub['name'],
                ],
                [
                    'quran_teacher_id' => $quranTeacher->id, // Use User ID, not profile ID
                    'subscription_type' => $sub['type'], // individual or group
                    'package_name_en' => $sub['name'],
                    'package_sessions_per_week' => 3,
                    'package_session_duration_minutes' => 45,
                    'total_sessions' => 12,
                    'total_price' => 400,
                    'final_price' => 400,
                    'currency' => 'SAR',
                    'status' => $sub['status'],
                    'starts_at' => $isActive ? now()->subWeek() : now()->subMonths(2),
                    'ends_at' => $isActive ? now()->addMonth() : now()->subMonth(),
                    'sessions_used' => $isActive ? 4 : 12,
                    'sessions_remaining' => $isActive ? 8 : 0,
                    'auto_renew' => $isActive,
                ]
            );
        }
        $this->line('   ✅ Created '.count($quranSubscriptions).' Quran subscriptions');

        // Create Academic Subscription
        $subject = AcademicSubject::withoutGlobalScopes()->where('academy_id', $this->academy->id)->first();
        $gradeLevel = AcademicGradeLevel::withoutGlobalScopes()->where('academy_id', $this->academy->id)->first();
        $package = AcademicPackage::withoutGlobalScopes()->where('academy_id', $this->academy->id)->first();

        AcademicSubscription::firstOrCreate(
            [
                'academy_id' => $this->academy->id,
                'student_id' => $student->id,
                'teacher_id' => $academicTeacherProfile->id, // Use teacher_id, not academic_teacher_id
            ],
            [
                'subject_id' => $subject?->id,
                'grade_level_id' => $gradeLevel?->id,
                'academic_package_id' => $package?->id, // Correct field name
                'package_name_ar' => $package?->name ?? 'Basic Package',
                'package_name_en' => $package?->name ?? 'Basic Package',
                'sessions_per_week' => $package?->sessions_per_month ?? 8,
                'session_duration_minutes' => 60,
                'monthly_price' => 350,
                'final_price' => 350,
                'monthly_amount' => 350,
                'final_monthly_amount' => 350, // Required fields
                'currency' => 'SAR',
                'status' => SessionSubscriptionStatus::ACTIVE,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
                'auto_renew' => true,
            ]
        );
        $this->line('   ✅ Created 1 academic subscription');
    }

    protected function createPayments(): void
    {
        $this->info('💰 Creating Payments...');

        $student = $this->testUsers['student'];

        $payments = [
            ['status' => PaymentStatus::COMPLETED->value, 'amount' => 400, 'type' => 'subscription'],
            ['status' => PaymentStatus::COMPLETED->value, 'amount' => 350, 'type' => 'subscription'],
            ['status' => PaymentStatus::PENDING->value, 'amount' => 800, 'type' => 'course'],
            ['status' => PaymentStatus::CANCELLED->value, 'amount' => 200, 'type' => 'subscription'],
        ];

        foreach ($payments as $payment) {
            $amount = $payment['amount'];
            Payment::create([
                'academy_id' => $this->academy->id,
                'user_id' => $student->id,
                'amount' => $amount,
                'net_amount' => $amount, // Required
                'currency' => 'SAR',
                'status' => $payment['status'],
                'payment_method' => 'credit_card', // Valid enum value
                'payment_gateway' => 'tap', // Valid enum value
                'payment_type' => $payment['type'],
                'gateway_transaction_id' => 'TXN-'.rand(100000, 999999),
                'payment_code' => 'PAY-'.strtoupper(substr(md5(rand()), 0, 8)),
                'payment_date' => now()->subDays(rand(1, 30)),
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        $this->line('   ✅ Created '.count($payments).' payments');
    }

    protected function createQuizzes(): void
    {
        $this->info('📝 Creating Quizzes...');

        $student = $this->testUsers['student'];
        $academicTeacher = $this->testUsers['academic_teacher'];
        $quranTeacher = $this->testUsers['quran_teacher'];

        // Create quizzes
        $quizzes = [
            ['title' => 'Mathematics Quiz 1', 'passing' => 70, 'duration' => 30],
            ['title' => 'Quran Memorization Test', 'passing' => 80, 'duration' => 45],
            ['title' => 'Science Assessment', 'passing' => 60, 'duration' => 20],
        ];

        foreach ($quizzes as $idx => $quizData) {
            $quiz = Quiz::firstOrCreate(
                ['academy_id' => $this->academy->id, 'title' => $quizData['title']],
                [
                    'description' => 'Test quiz for '.$quizData['title'],
                    'duration_minutes' => $quizData['duration'],
                    'passing_score' => $quizData['passing'],
                    'is_active' => true,
                ]
            );

            // Create questions
            for ($q = 1; $q <= 5; $q++) {
                QuizQuestion::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'question_text' => "Question $q for {$quizData['title']}"], // Correct field name
                    [
                        'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                        'correct_option' => 0, // Index of correct answer
                        'order' => $q,
                    ]
                );
            }

            // Assign quiz to student (polymorphic relationship)
            $studentProfile = StudentProfile::withoutGlobalScopes()->where('user_id', $student->id)->first();
            if ($studentProfile) {
                QuizAssignment::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'assignable_type' => StudentProfile::class, 'assignable_id' => $studentProfile->id],
                    [
                        'is_visible' => true,
                        'available_from' => now(),
                        'available_until' => now()->addDays(7),
                        'max_attempts' => 3,
                    ]
                );
            }

            // Create attempt for first quiz
            if ($idx === 0 && $studentProfile) {
                // Get the assignment we just created
                $assignment = QuizAssignment::where('quiz_id', $quiz->id)
                    ->where('assignable_type', StudentProfile::class)
                    ->where('assignable_id', $studentProfile->id)
                    ->first();

                if ($assignment) {
                    QuizAttempt::firstOrCreate(
                        ['quiz_assignment_id' => $assignment->id, 'student_id' => $studentProfile->id],
                        [
                            'started_at' => now()->subHour(),
                            'submitted_at' => now()->subMinutes(30),
                            'score' => 80,
                            'passed' => true,
                            'answers' => [
                                ['question_id' => 1, 'answer' => 'Option A', 'correct' => true],
                                ['question_id' => 2, 'answer' => 'Option A', 'correct' => true],
                                ['question_id' => 3, 'answer' => 'Option B', 'correct' => false],
                                ['question_id' => 4, 'answer' => 'Option A', 'correct' => true],
                                ['question_id' => 5, 'answer' => 'Option A', 'correct' => true],
                            ],
                        ]
                    );
                }
            }
        }

        $this->line('   ✅ Created '.count($quizzes).' quizzes with questions');
    }

    protected function createHomework(): void
    {
        $this->info('📚 Creating Homework...');

        $student = $this->testUsers['student'];
        $academicTeacher = $this->testUsers['academic_teacher'];

        // Get an academic session to attach homework to
        $academicSession = AcademicSession::where('academy_id', $this->academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->first();

        if ($academicSession) {
            // Update session with homework
            $academicSession->update([
                'homework_assigned' => true,
                'homework_description' => 'Complete exercises 1-10 from chapter 3.',
            ]);

            // Create AcademicHomework
            $academicTeacher = $academicSession->academicTeacher;
            $homework = AcademicHomework::firstOrCreate(
                [
                    'academic_session_id' => $academicSession->id,
                ],
                [
                    'academy_id' => $this->academy->id,
                    'academic_subscription_id' => $academicSession->academic_subscription_id,
                    'teacher_id' => $academicTeacher?->user_id,
                    'title' => 'واجب الجلسة الأكاديمية',
                    'description' => 'Complete exercises 1-10 from chapter 3.',
                    'due_date' => now()->addDays(3),
                    'max_score' => 100,
                    'status' => 'published',
                    'is_active' => true,
                ]
            );

            // Create submission
            $studentProfile = StudentProfile::withoutGlobalScopes()->where('user_id', $student->id)->first();
            if ($studentProfile && $homework) {
                AcademicHomeworkSubmission::firstOrCreate(
                    [
                        'academic_homework_id' => $homework->id,
                        'student_id' => $studentProfile->id,
                    ],
                    [
                        'academy_id' => $this->academy->id,
                        'content' => 'Here are my completed exercises.',
                        'submitted_at' => now(),
                        'submission_status' => HomeworkSubmissionStatus::SUBMITTED,
                    ]
                );
            }

            $this->line('   ✅ Created 1 homework with submission');
        } else {
            $this->line('   ⚠️  No completed session found for homework');
        }
    }

    protected function createCertificates(): void
    {
        $this->info('🎓 Creating Certificates...');

        $student = $this->testUsers['student'];
        $quranTeacher = $this->testUsers['quran_teacher'];
        $admin = $this->testUsers['admin'];

        // Get a completed subscription for certificate
        $subscription = QuranSubscription::where('academy_id', $this->academy->id)
            ->where('status', SessionSubscriptionStatus::CANCELLED)
            ->first();

        if ($subscription) {
            $studentProfile = StudentProfile::withoutGlobalScopes()->where('user_id', $student->id)->first();
            if ($studentProfile) {
                Certificate::firstOrCreate(
                    [
                        'academy_id' => $this->academy->id,
                        'student_id' => $studentProfile->id, // StudentProfile ID
                        'certificate_type' => 'quran_subscription', // Valid enum value
                    ],
                    [
                        'teacher_id' => $quranTeacher->id,
                        'certificateable_type' => QuranSubscription::class,
                        'certificateable_id' => $subscription->id,
                        'certificate_number' => 'CERT-'.strtoupper(substr(md5(uniqid()), 0, 10)),
                        'template_style' => 'template_1', // Valid enum value
                        'certificate_text' => 'This is to certify that Test Student has successfully completed the Quran memorization program.',
                        'file_path' => 'certificates/test-certificate.pdf', // Required
                        'issued_at' => now(),
                        'issued_by' => $admin->id,
                        'is_manual' => false,
                    ]
                );
            }

            $this->line('   ✅ Created 1 certificate');
        } else {
            $this->line('   ⚠️  No expired subscription found for certificate');
        }
    }

    protected function createReports(): void
    {
        $this->info('📊 Creating Reports...');

        $student = $this->testUsers['student'];

        // Get a completed Quran session for report
        $quranSession = QuranSession::where('academy_id', $this->academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->first();

        if ($quranSession) {
            $studentProfile = StudentProfile::withoutGlobalScopes()->where('user_id', $student->id)->first();
            if ($studentProfile) {
                StudentSessionReport::firstOrCreate(
                    [
                        'session_id' => $quranSession->id,
                        'student_id' => $studentProfile->id,
                    ],
                    [
                        'academy_id' => $this->academy->id,
                        'teacher_id' => $quranSession->quran_teacher_id,
                        'attendance_status' => AttendanceStatus::ATTENDED->value,
                        'new_memorization_degree' => 4,
                        'reservation_degree' => 4,
                        'notes' => 'Excellent progress in memorization. Keep up the good work!',
                    ]
                );

                $this->line('   ✅ Created 1 student session report');
            }
        }

        // Get a completed academic session for report
        $academicSession = AcademicSession::where('academy_id', $this->academy->id)
            ->where('status', SessionStatus::COMPLETED)
            ->first();

        if ($academicSession) {
            $studentProfile = StudentProfile::withoutGlobalScopes()->where('user_id', $student->id)->first();
            if ($studentProfile) {
                AcademicSessionReport::firstOrCreate(
                    ['session_id' => $academicSession->id, 'student_id' => $studentProfile->id],
                    [
                        'academy_id' => $this->academy->id,
                        'teacher_id' => $academicSession->academic_teacher_id,
                        'attendance_status' => AttendanceStatus::ATTENDED->value,
                        'lesson_understanding_degree' => 8, // 0-10 scale
                        'homework_completion_degree' => 9, // 0-10 scale
                        'notes' => 'Good understanding of the material.',
                    ]
                );

                $this->line('   ✅ Created 1 academic session report');
            }
        }
    }

    protected function displaySummary(): void
    {
        $testPassword = config('seeding.test_password');
        $domain = config('seeding.test_email_domain');

        $this->newLine(2);
        $this->info('🎉 TEST DATA GENERATION COMPLETE!');
        $this->info('==================================');
        $this->newLine();

        $this->info("📋 Test User Credentials (Password: {$testPassword})");
        $this->info('---------------------------------------------');

        $this->table(
            ['Role', 'Email', 'Panel/Routes'],
            [
                ['super_admin', 'super@'.$domain, '/admin'],
                ['admin', 'admin@'.$domain, '/panel'],
                ['quran_teacher', 'quran.teacher@'.$domain, '/teacher-panel'],
                ['academic_teacher', 'academic.teacher@'.$domain, '/academic-teacher-panel'],
                ['supervisor', 'supervisor@'.$domain, '/supervisor-panel'],
                ['student', 'student@'.$domain, '/student/*'],
                ['parent', 'parent@'.$domain, '/parent/*'],
            ]
        );

        $this->newLine();
        $this->info("📚 Academy: {$this->academy->name}");
        $this->info("🌐 Subdomain: {$this->academy->subdomain}");
        $this->newLine();

        $this->warn('⚠️  Remember to link the parent to the student profile in the admin panel!');
    }
}
