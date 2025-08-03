<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Academy;
use App\Models\User;
use App\Models\Subject;
use App\Models\GradeLevel;
use App\Models\Course;
use App\Models\RecordedCourse;
use App\Models\InteractiveCourse;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\CourseSubscription;
use App\Models\QuranSession;
use App\Models\QuranCircle;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\QuranTeacherProfile;
use App\Models\AcademicTeacherProfile;
use App\Models\ParentProfile;
use App\Models\SupervisorProfile;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\StudentProgress;
use App\Models\QuranProgress;
use App\Models\AcademicSubject;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSettings;
use Carbon\Carbon;

class ComprehensiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting comprehensive data seeding...');

        // Create academies
        $academies = $this->createAcademies();
        
        // Create grade levels for each academy
        $gradeLevels = $this->createGradeLevels($academies);
        
        // Create subjects for each academy
        $subjects = $this->createSubjects($academies);
        
        // Create users (teachers, students, parents, supervisors)
        $users = $this->createUsers($academies, $gradeLevels);
        
        // Create academic settings
        $this->createAcademicSettings($academies);
        
        // Create Quran packages
        $quranPackages = $this->createQuranPackages($academies);
        
        // Create courses (live and recorded)
        $courses = $this->createCourses($academies, $subjects, $gradeLevels, $users['academicTeachers']);
        
        // Create recorded courses with sections and lessons
        $recordedCourses = $this->createRecordedCourses($academies, $subjects, $gradeLevels, $users['academicTeachers']);
        
        // Create interactive courses
        $interactiveCourses = $this->createInteractiveCourses($academies, $subjects, $gradeLevels);
        
        // Create Quran circles
        $quranCircles = $this->createQuranCircles($academies, $users['quranTeachers']);
        
        // Create subscriptions
        $this->createSubscriptions($academies, $users, $quranPackages, $recordedCourses);
        
        // Create sessions and progress
        $this->createSessionsAndProgress($users, $quranCircles, $recordedCourses);
        
        // Create payments
        $this->createPayments($academies);
        
        $this->command->info('✅ Comprehensive data seeding completed successfully!');
        $this->displayLoginCredentials($academies, $users);
    }

    private function createAcademies(): array
    {
        $this->command->info('Creating academies...');
        
        $academies = [];
        
        $academyData = [
            [
                'name' => 'أكاديمية إتقان',
                'name_en' => 'Itqan Academy',
                'subdomain' => 'itqan-academy',
                'description' => 'الأكاديمية الرئيسية لمنصة إتقان التعليمية',
                'brand_color' => '#0ea5e9',
                'total_revenue' => 250000.00,
                'monthly_revenue' => 45000.00,
                'active_subscriptions' => 180,
                'growth_rate' => 15.5,
            ],
            [
                'name' => 'أكاديمية النور',
                'name_en' => 'Al-Noor Academy',
                'subdomain' => 'alnoor',
                'description' => 'أكاديمية متخصصة في تحفيظ القرآن الكريم',
                'brand_color' => '#22c55e',
                'total_revenue' => 180000.00,
                'monthly_revenue' => 32000.00,
                'active_subscriptions' => 120,
                'growth_rate' => 12.3,
            ],
            [
                'name' => 'أكاديمية العلوم',
                'name_en' => 'Sciences Academy',
                'subdomain' => 'sciences',
                'description' => 'أكاديمية متخصصة في العلوم الأكاديمية',
                'brand_color' => '#f59e0b',
                'total_revenue' => 120000.00,
                'monthly_revenue' => 22000.00,
                'active_subscriptions' => 85,
                'growth_rate' => 8.7,
            ],
        ];

        foreach ($academyData as $data) {
            $academy = Academy::firstOrCreate(
                ['subdomain' => $data['subdomain']],
                array_merge($data, [
                    'is_active' => true,
                    'pending_payments' => rand(5000, 15000),
                    'email' => 'info@' . $data['subdomain'] . '.com',
                    'phone' => '+96650' . rand(1000000, 9999999),
                ])
            );
            
            $academies[] = $academy;
        }

        return $academies;
    }

    private function createGradeLevels(array $academies): array
    {
        $this->command->info('Creating grade levels...');
        
        $gradeLevels = [];
        
        $gradeData = [
            ['name' => 'الابتدائية', 'name_en' => 'Primary', 'level' => 1],
            ['name' => 'المتوسطة', 'name_en' => 'Intermediate', 'level' => 2],
            ['name' => 'الثانوية', 'name_en' => 'Secondary', 'level' => 3],
            ['name' => 'الجامعية', 'name_en' => 'University', 'level' => 4],
        ];

        foreach ($academies as $academy) {
            foreach ($gradeData as $grade) {
                $gradeLevel = GradeLevel::firstOrCreate(
                    [
                        'academy_id' => $academy->id,
                        'name' => $grade['name'],
                        'level' => $grade['level']
                    ],
                    array_merge($grade, [
                        'academy_id' => $academy->id,
                        'description' => 'المرحلة ' . $grade['name'] . ' في ' . $academy->name,
                        'is_active' => true,
                    ])
                );
                
                $gradeLevels[$academy->id][] = $gradeLevel;
            }
        }

        return $gradeLevels;
    }

    private function createSubjects(array $academies): array
    {
        $this->command->info('Creating subjects...');
        
        $subjects = [];
        
        $subjectData = [
            // Academic subjects
            ['name' => 'الرياضيات', 'name_en' => 'Mathematics'],
            ['name' => 'العلوم', 'name_en' => 'Science'],
            ['name' => 'اللغة العربية', 'name_en' => 'Arabic Language'],
            ['name' => 'اللغة الإنجليزية', 'name_en' => 'English Language'],
            ['name' => 'التاريخ', 'name_en' => 'History'],
            ['name' => 'الجغرافيا', 'name_en' => 'Geography'],
            ['name' => 'التربية الإسلامية', 'name_en' => 'Islamic Education'],
            ['name' => 'الفيزياء', 'name_en' => 'Physics'],
            ['name' => 'الكيمياء', 'name_en' => 'Chemistry'],
            ['name' => 'الأحياء', 'name_en' => 'Biology'],
        ];

        foreach ($academies as $academy) {
            foreach ($subjectData as $index => $subject) {
                $subjectModel = Subject::firstOrCreate(
                    [
                        'academy_id' => $academy->id,
                        'name' => $subject['name']
                    ],
                    array_merge($subject, [
                        'academy_id' => $academy->id,
                        'subject_code' => strtoupper(substr($subject['name_en'], 0, 3)) . str_pad($academy->id, 2, '0', STR_PAD_LEFT) . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                        'description' => 'مادة ' . $subject['name'] . ' في ' . $academy->name,
                        'hours_per_week' => rand(2, 6),
                        'is_active' => true,
                    ])
                );
                
                $subjects[$academy->id][] = $subjectModel;
            }
        }

        return $subjects;
    }

    private function createUsers(array $academies, array $gradeLevels): array
    {
        $this->command->info('Creating users...');
        
        $users = [
            'admins' => [],
            'quranTeachers' => [],
            'academicTeachers' => [],
            'students' => [],
            'parents' => [],
            'supervisors' => [],
        ];

        foreach ($academies as $academy) {
            // Create academy admin
            $admin = User::firstOrCreate(
                ['email' => 'admin@' . $academy->subdomain . '.com'],
                [
                    'academy_id' => $academy->id,
                    'first_name' => 'مدير',
                    'last_name' => $academy->name,
                    'phone' => '+96650' . rand(1000000, 9999999),
                    'user_type' => 'admin',
                    'status' => 'active',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'bio' => 'مدير ' . $academy->name,
                ]
            );
            $users['admins'][] = $admin;
            
            // Update academy with admin
            $academy->update(['admin_id' => $admin->id]);

            // Create Quran teachers
            for ($i = 1; $i <= 3; $i++) {
                $quranTeacher = User::firstOrCreate(
                    ['email' => "quran.teacher{$i}@{$academy->subdomain}.com"],
                    [
                        'academy_id' => $academy->id,
                        'first_name' => ['عبدالله', 'يوسف', 'محمد'][$i - 1],
                        'last_name' => ['الحافظ', 'القارئ', 'المقرئ'][$i - 1],
                        'phone' => '+96650' . rand(1000000, 9999999),
                        'user_type' => 'quran_teacher',
                        'status' => 'active',
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                        'bio' => 'معلم القرآن الكريم مع إجازة في القراءات',
                        'has_ijazah' => true,
                        'years_experience' => rand(5, 15),
                        'student_session_price' => rand(40, 80),
                        'teacher_session_price' => rand(30, 60),
                    ]
                );
                $users['quranTeachers'][] = $quranTeacher;
            }

            // Create Academic teachers
            for ($i = 1; $i <= 4; $i++) {
                $academicTeacher = User::firstOrCreate(
                    ['email' => "academic.teacher{$i}@{$academy->subdomain}.com"],
                    [
                        'academy_id' => $academy->id,
                        'first_name' => ['سارة', 'فاطمة', 'مريم', 'خديجة'][$i - 1],
                        'last_name' => ['الأحمد', 'العلي', 'المحمد', 'الزهراني'][$i - 1],
                        'phone' => '+96650' . rand(1000000, 9999999),
                        'user_type' => 'academic_teacher',
                        'status' => 'active',
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                        'bio' => 'معلمة متخصصة في العلوم الأكاديمية',
                        'qualification_degree' => ['bachelor', 'master', 'phd'][rand(0, 2)],
                        'qualification_text' => 'ماجستير في التربية',
                        'university' => 'جامعة الملك سعود',
                        'years_experience' => rand(3, 12),
                        'student_session_price' => rand(50, 100),
                        'teacher_session_price' => rand(35, 70),
                    ]
                );
                $users['academicTeachers'][] = $academicTeacher;
            }

            // Create Parents
            for ($i = 1; $i <= 8; $i++) {
                $parent = User::firstOrCreate(
                    ['email' => "parent{$i}@{$academy->subdomain}.com"],
                    [
                        'academy_id' => $academy->id,
                        'first_name' => ['خالد', 'أحمد', 'علي', 'محمد', 'فاطمة', 'عائشة', 'مريم', 'خديجة'][$i - 1],
                        'last_name' => ['المحمد', 'العلي', 'الزهراني', 'الأحمد', 'المحمد', 'العلي', 'الزهراني', 'الأحمد'][$i - 1],
                        'phone' => '+96650' . rand(1000000, 9999999),
                        'user_type' => 'parent',
                        'status' => 'active',
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                    ]
                );
                $users['parents'][] = $parent;
            }

            // Create Students
            for ($i = 1; $i <= 20; $i++) {
                $parent = $users['parents'][rand(0, count($users['parents']) - 1)];
                $student = User::firstOrCreate(
                    ['email' => "student{$i}@{$academy->subdomain}.com"],
                    [
                        'academy_id' => $academy->id,
                        'first_name' => ['عمر', 'أحمد', 'محمد', 'علي', 'يوسف', 'نورا', 'فاطمة', 'عائشة', 'مريم', 'خديجة'][$i % 10],
                        'last_name' => $parent->last_name,
                        'phone' => '+96650' . rand(1000000, 9999999),
                        'user_type' => 'student',
                        'status' => 'active',
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                        'parent_id' => $parent->id,
                        'parent_phone' => $parent->phone,
                    ]
                );
                $users['students'][] = $student;
            }

            // Create Supervisors
            $supervisor = User::firstOrCreate(
                ['email' => "supervisor@{$academy->subdomain}.com"],
                [
                    'academy_id' => $academy->id,
                    'first_name' => 'محمد',
                    'last_name' => 'السالم',
                    'phone' => '+96650' . rand(1000000, 9999999),
                    'user_type' => 'supervisor',
                    'status' => 'active',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'bio' => 'مشرف الجودة والمتابعة الأكاديمية',
                ]
            );
            $users['supervisors'][] = $supervisor;
        }

        return $users;
    }

    private function createAcademicSettings(array $academies): void
    {
        $this->command->info('Creating academic settings...');
        
        foreach ($academies as $academy) {
            AcademicSettings::firstOrCreate(
                ['academy_id' => $academy->id],
                [
                    'academy_id' => $academy->id,
                    'sessions_per_week_options' => [1, 2, 3, 4],
                    'default_session_duration_minutes' => 60,
                    'default_booking_fee' => 50.00,
                    'currency' => 'SAR',
                    'enable_trial_sessions' => true,
                    'trial_session_duration_minutes' => 30,
                    'trial_session_fee' => 25.00,
                    'subscription_pause_max_days' => 30,
                    'auto_renewal_reminder_days' => 7,
                    'allow_mid_month_cancellation' => true,
                    'enabled_payment_methods' => ['tab_pay', 'paymob'],
                    'auto_create_google_meet_links' => true,
                    'google_meet_account_email' => 'meet@' . $academy->subdomain . '.com',
                    'courses_start_on_schedule' => true,
                    'course_enrollment_deadline_days' => 7,
                    'allow_late_enrollment' => true,
                    'available_languages' => ['ar', 'en'],
                    'created_by' => $academy->admin_id,
                    'updated_by' => $academy->admin_id,
                ]
            );
        }
    }

    private function createQuranPackages(array $academies): array
    {
        $this->command->info('Creating Quran packages...');
        
        $packages = [];
        
        $packageData = [
            [
                'name_ar' => 'الباقة الأساسية',
                'name_en' => 'Basic Package',
                'description_ar' => 'باقة أساسية لتحفيظ القرآن الكريم',
                'description_en' => 'Basic package for Quran memorization',
                'sessions_per_month' => 8,
                'session_duration_minutes' => 30,
                'monthly_price' => 200.00,
                'quarterly_price' => 540.00,
                'yearly_price' => 1920.00,
                'features' => ['تحفيظ القرآن', 'تجويد أساسي', 'متابعة أسبوعية'],
            ],
            [
                'name_ar' => 'الباقة المتقدمة',
                'name_en' => 'Advanced Package',
                'description_ar' => 'باقة متقدمة مع إجازة في القراءات',
                'description_en' => 'Advanced package with Ijazah in Quran readings',
                'sessions_per_month' => 12,
                'session_duration_minutes' => 45,
                'monthly_price' => 350.00,
                'quarterly_price' => 945.00,
                'yearly_price' => 3360.00,
                'features' => ['تحفيظ القرآن', 'تجويد متقدم', 'إجازة في القراءات', 'متابعة يومية'],
            ],
            [
                'name_ar' => 'الباقة المميزة',
                'name_en' => 'Premium Package',
                'description_ar' => 'باقة مميزة مع دروس خاصة',
                'description_en' => 'Premium package with private lessons',
                'sessions_per_month' => 16,
                'session_duration_minutes' => 60,
                'monthly_price' => 500.00,
                'quarterly_price' => 1350.00,
                'yearly_price' => 4800.00,
                'features' => ['تحفيظ القرآن', 'تجويد متقدم', 'إجازة في القراءات', 'دروس خاصة', 'متابعة يومية', 'شهادة معتمدة'],
            ],
        ];

        foreach ($academies as $academy) {
            foreach ($packageData as $index => $package) {
                $quranPackage = QuranPackage::firstOrCreate(
                    [
                        'academy_id' => $academy->id,
                        'name_ar' => $package['name_ar']
                    ],
                    array_merge($package, [
                        'academy_id' => $academy->id,
                        'currency' => 'SAR',
                        'is_active' => true,
                        'sort_order' => $index + 1,
                        'created_by' => $academy->admin_id,
                        'updated_by' => $academy->admin_id,
                    ])
                );
                
                $packages[$academy->id][] = $quranPackage;
            }
        }

        return $packages;
    }

    private function createCourses(array $academies, array $subjects, array $gradeLevels, array $teachers): array
    {
        $this->command->info('Creating live courses...');
        
        $courses = [];
        
        foreach ($academies as $academy) {
            $academySubjects = $subjects[$academy->id] ?? [];
            $academyGradeLevels = $gradeLevels[$academy->id] ?? [];
            $academyTeachers = array_filter($teachers, fn($teacher) => $teacher->academy_id === $academy->id);
            
            foreach ($academySubjects as $subject) {
                foreach ($academyGradeLevels as $gradeLevel) {
                        $teacher = $academyTeachers[array_rand($academyTeachers)];
                        
                        $course = Course::firstOrCreate(
                            [
                                'academy_id' => $academy->id,
                                'subject_id' => $subject->id,
                                'grade_level_id' => $gradeLevel->id,
                                'teacher_id' => $teacher->id,
                            ],
                            [
                                'title' => $subject->name . ' - ' . $gradeLevel->name,
                                'description' => 'دورة في ' . $subject->name . ' للمرحلة ' . $gradeLevel->name,
                                'type' => 'group',
                                'level' => 'beginner',
                                'duration_weeks' => rand(12, 16),
                                'sessions_per_week' => rand(2, 4),
                                'session_duration_minutes' => 45,
                                'max_students' => rand(15, 25),
                                'price' => rand(800, 2000),
                                'currency' => 'SAR',
                                'is_active' => true,
                                'starts_at' => now()->addDays(rand(7, 30)),
                                'ends_at' => now()->addMonths(rand(3, 6)),
                            ]
                        );
                        
                        $courses[] = $course;
                }
            }
        }

        return $courses;
    }

    private function createRecordedCourses(array $academies, array $subjects, array $gradeLevels, array $teachers): array
    {
        $this->command->info('Creating recorded courses...');
        
        $recordedCourses = [];
        
        foreach ($academies as $academy) {
            $academySubjects = $subjects[$academy->id] ?? [];
            $academyGradeLevels = $gradeLevels[$academy->id] ?? [];
            $academyTeachers = array_filter($teachers, fn($teacher) => $teacher->academy_id === $academy->id);
            
            foreach ($academySubjects as $subjectIndex => $subject) {
                foreach ($academyGradeLevels as $gradeIndex => $gradeLevel) {
                    $teacher = $academyTeachers[array_rand($academyTeachers)];
                    // Generate a unique course_code using deterministic pattern
                    $courseCode = 'RC' . strtoupper(substr($subject->name_en, 0, 3)) . $gradeLevel->level . str_pad($academy->id, 2, '0', STR_PAD_LEFT) . str_pad($subjectIndex, 2, '0', STR_PAD_LEFT) . str_pad($gradeIndex, 2, '0', STR_PAD_LEFT);
                    
                    $recordedCourse = RecordedCourse::firstOrCreate(
                        [
                            'academy_id' => $academy->id,
                            'subject_id' => $subject->id,
                            'grade_level_id' => $gradeLevel->id,
                            'instructor_id' => $teacher->id,
                            'course_code' => $courseCode,
                        ],
                        [
                            'title' => $subject->name . ' - ' . $gradeLevel->name . ' (مسجل)',
                            'title_en' => $subject->name_en . ' - ' . $gradeLevel->name . ' (Recorded)',
                            'description' => 'دورة مسجلة في ' . $subject->name . ' للمرحلة ' . $gradeLevel->name,
                            'description_en' => 'Recorded course in ' . $subject->name_en . ' for ' . $gradeLevel->name,
                            'level' => 'beginner',
                            'duration_hours' => rand(20, 40),
                            'total_lessons' => rand(15, 30),
                            'price' => rand(300, 800),
                            'currency' => 'SAR',
                            'is_free' => rand(0, 1) === 0,
                            'is_featured' => rand(0, 1) === 1,
                            'is_published' => true,
                            'status' => 'published',
                            'thumbnail_url' => 'courses/default-thumbnail.jpg',
                            'trailer_video_url' => 'https://example.com/video.mp4',
                            'published_at' => now()->subDays(rand(1, 30)),
                        ]
                    );
                    
                    // Create sections and lessons for this course
                    $this->createCourseSectionsAndLessons($recordedCourse);
                    
                    $recordedCourses[] = $recordedCourse;
                }
            }
        }

        return $recordedCourses;
    }

    private function createCourseSectionsAndLessons(RecordedCourse $course): void
    {
        $sectionsCount = rand(3, 6);
        
        for ($sectionIndex = 1; $sectionIndex <= $sectionsCount; $sectionIndex++) {
            $section = CourseSection::firstOrCreate(
                [
                    'recorded_course_id' => $course->id,
                    'title' => 'الوحدة ' . $sectionIndex,
                ],
                [
                    'description' => 'وصف الوحدة ' . $sectionIndex,
                    'order' => $sectionIndex,
                    'duration_minutes' => rand(60, 120),
                ]
            );
            
            $lessonsCount = rand(3, 8);
            for ($lessonIndex = 1; $lessonIndex <= $lessonsCount; $lessonIndex++) {
                Lesson::firstOrCreate(
                    [
                        'course_section_id' => $section->id,
                        'title' => 'الدرس ' . $lessonIndex . ' - الوحدة ' . $sectionIndex,
                    ],
                    [
                        'recorded_course_id' => $course->id,
                        'lesson_code' => 'L' . $course->id . 'S' . $sectionIndex . 'L' . $lessonIndex,
                        'description' => 'وصف الدرس ' . $lessonIndex,
                        'order' => $lessonIndex,
                        'video_duration_seconds' => rand(900, 1800), // 15-30 minutes in seconds
                        'video_url' => 'https://example.com/lesson-' . $lessonIndex . '.mp4',
                        'is_free_preview' => $lessonIndex === 1, // First lesson is free
                        'is_published' => true,
                        'lesson_type' => 'video',
                    ]
                );
            }
        }
    }

    private function createInteractiveCourses(array $academies, array $subjects, array $gradeLevels): array
    {
        $this->command->info('Creating interactive courses...');
        
        $interactiveCourses = [];
        
        foreach ($academies as $academy) {
            $academySubjects = $subjects[$academy->id] ?? [];
            $academyGradeLevels = $gradeLevels[$academy->id] ?? [];
            $academyTeachers = User::where('academy_id', $academy->id)->where('user_type', 'academic_teacher')->get();
            
            foreach ($academySubjects as $subject) {
                foreach ($academyGradeLevels as $gradeLevel) {
                    $teacher = $academyTeachers->random();
                    $profile = $teacher->academicTeacherProfile;
                    // Skip if no profile exists
                    if (!$profile) {
                        continue;
                    }
                    $profileId = $profile->id;
                    $interactiveCourse = InteractiveCourse::firstOrCreate(
                        [
                            'academy_id' => $academy->id,
                            'subject_id' => $subject->id,
                            'grade_level_id' => $gradeLevel->id,
                        ],
                        [
                            'title' => $subject->name . ' - ' . $gradeLevel->name . ' (تفاعلي)',
                            'description' => 'دورة تفاعلية في ' . $subject->name . ' للمرحلة ' . $gradeLevel->name,
                            'course_code' => 'IC-' . str_pad($academy->id, 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                            'duration_weeks' => rand(8, 16),
                            'sessions_per_week' => rand(2, 4),
                            'session_duration_minutes' => 45,
                            'max_students' => rand(10, 25),
                            'student_price' => rand(200, 500),
                            'teacher_payment' => rand(100, 300),
                            'assigned_teacher_id' => $profileId,
                            'created_by' => $teacher->id,
                            'start_date' => now()->addDays(rand(1, 30)),
                            'end_date' => now()->addDays(rand(31, 90)),
                            'enrollment_deadline' => now()->addDays(rand(10, 30)),
                            'schedule' => [],
                            'is_published' => true,
                            'status' => 'published',
                            'publication_date' => now()->subDays(rand(1, 30)),
                        ]
                    );
                    
                    $interactiveCourses[] = $interactiveCourse;
                }
            }
        }

        return $interactiveCourses;
    }

    private function createQuranCircles(array $academies, array $quranTeachers): array
    {
        $this->command->info('Creating Quran circles...');
        
        $quranCircles = [];
        
        foreach ($academies as $academy) {
            $academyTeachers = array_filter($quranTeachers, fn($teacher) => $teacher->academy_id === $academy->id);
            
            for ($i = 1; $i <= 3; $i++) {
                $teacher = $academyTeachers[array_rand($academyTeachers)];
                
                $quranCircle = QuranCircle::firstOrCreate(
                    [
                        'academy_id' => $academy->id,
                        'name_ar' => 'حلقة القرآن ' . $i,
                    ],
                    [
                        'name_ar' => 'حلقة القرآن ' . $i,
                        'name_en' => 'Quran Circle ' . $i,
                        'description_ar' => 'حلقة لتحفيظ القرآن الكريم في ' . $academy->name,
                        'description_en' => 'Quran memorization circle in ' . $academy->name_en,
                        'quran_teacher_id' => $teacher->id,
                        'max_students' => rand(10, 20),
                        'enrolled_students' => rand(5, 15),
                        'schedule_days' => ['sunday', 'tuesday', 'thursday'],
                        'schedule_time' => '18:00',
                        'session_duration_minutes' => 60,
                        'status' => 'active',
                        'enrollment_status' => 'open',
                        'circle_code' => 'QC-' . $academy->id . '-' . $i . '-' . rand(1000,9999),
                    ]
                );
                
                $quranCircles[] = $quranCircle;
            }
        }

        return $quranCircles;
    }

    private function createSubscriptions(array $academies, array $users, array $quranPackages, array $recordedCourses): void
    {
        $this->command->info('Creating subscriptions...');
        
        foreach ($academies as $academy) {
            $academyStudents = array_filter($users['students'], fn($student) => $student->academy_id === $academy->id);
            $academyPackages = $quranPackages[$academy->id] ?? [];
            $academyRecordedCourses = array_filter($recordedCourses, fn($course) => $course->academy_id === $academy->id);
            
            // Create Quran subscriptions
            foreach ($academyStudents as $student) {
                if (rand(0, 1) === 1 && !empty($academyPackages)) {
                    $package = $academyPackages[array_rand($academyPackages)];
                    $academyTeachers = array_filter($users['quranTeachers'], fn($teacher) => $teacher->academy_id === $academy->id);
                    $teacher = $academyTeachers[array_rand($academyTeachers)];
                    
                    QuranSubscription::firstOrCreate(
                        [
                            'academy_id' => $academy->id,
                            'student_id' => $student->id,
                            'quran_teacher_id' => $teacher->id,
                            'package_id' => $package->id,
                        ],
                        [
                            'billing_cycle' => ['monthly', 'quarterly', 'yearly'][rand(0, 2)],
                            'total_sessions' => $package->sessions_per_month * 12,
                            'sessions_used' => rand(0, 20),
                            'sessions_remaining' => $package->sessions_per_month * 12 - rand(0, 20),
                            'verses_memorized' => rand(0, 50),
                            'progress_percentage' => rand(0, 100),
                            'total_price' => $package->monthly_price,
                            'final_price' => $package->monthly_price,
                            'is_trial_active' => rand(0, 1) === 1,
                            'auto_renew' => rand(0, 1) === 1,
                            'subscription_status' => ['active', 'paused', 'cancelled'][rand(0, 2)],
                            'starts_at' => now()->subDays(rand(1, 90)),
                            'expires_at' => now()->addDays(rand(30, 365)),
                            'subscription_code' => 'QS-' . $academy->id . '-' . $student->id . '-' . rand(1000,9999),
                        ]
                    );
                }
            }
            
            // Create course subscriptions
            foreach ($academyStudents as $student) {
                if (rand(0, 1) === 1 && !empty($academyRecordedCourses)) {
                    $course = $academyRecordedCourses[array_rand($academyRecordedCourses)];
                    
                    // Generate a unique subscription_code
                    do {
                        $courseSubCode = 'CS-' . $academy->id . '-' . $student->id . '-' . rand(1000,9999);
                    } while (\App\Models\CourseSubscription::where('subscription_code', $courseSubCode)->exists());

                    CourseSubscription::firstOrCreate(
                        [
                            'academy_id' => $academy->id,
                            'student_id' => $student->id,
                            'recorded_course_id' => $course->id,
                        ],
                        [
                            'price_paid' => $course->price,
                            'original_price' => $course->price,
                            'progress_percentage' => rand(0, 100),
                            'completed_lessons' => rand(0, $course->total_lessons),
                            'total_lessons' => $course->total_lessons,
                            'total_duration_minutes' => $course->duration_hours * 60,
                            'status' => ['active', 'completed', 'paused'][rand(0, 2)],
                            'enrolled_at' => now()->subDays(rand(1, 60)),
                            'expires_at' => now()->addDays(rand(30, 365)),
                            'subscription_code' => $courseSubCode,
                        ]
                    );
                }
            }
        }
    }

    private function createSessionsAndProgress(array $users, array $quranCircles, array $recordedCourses): void
    {
        $this->command->info('Creating sessions and progress...');
        
        // Create Quran sessions
        foreach ($quranCircles as $circle) {
            for ($i = 1; $i <= rand(5, 15); $i++) {
                QuranSession::firstOrCreate(
                    [
                        'circle_id' => $circle->id,
                        'session_code' => 'QS-' . $circle->id . '-' . $i . '-' . rand(1000,9999),
                    ],
                    [
                        'academy_id' => $circle->academy_id,
                        'quran_teacher_id' => $circle->quran_teacher_id,
                        'title' => 'جلسة القرآن ' . $i,
                        'description' => 'وصف جلسة القرآن ' . $i,
                        'scheduled_at' => now()->subDays(rand(1, 30)),
                        'duration_minutes' => 60,
                        'status' => ['completed', 'scheduled', 'cancelled'][rand(0, 2)],
                    ]
                );
            }
        }
        
        // Create student progress for recorded courses
        foreach ($recordedCourses as $course) {
            $courseSubscriptions = CourseSubscription::where('recorded_course_id', $course->id)->get();
            
            foreach ($courseSubscriptions as $subscription) {
                $lessons = Lesson::whereHas('section', function($query) use ($course) {
                    $query->where('recorded_course_id', $course->id);
                })->get();
                
                foreach ($lessons as $lesson) {
                    if (rand(0, 1) === 1) {
                        StudentProgress::firstOrCreate(
                            [
                                'user_id' => $subscription->student_id,
                                'recorded_course_id' => $course->id,
                                'lesson_id' => $lesson->id,
                            ],
                            [
                                'progress_percentage' => rand(0, 100),
                                'watch_time_seconds' => rand(60, 1800),
                                'is_completed' => rand(0, 1) === 1,
                                'completed_at' => rand(0, 1) === 1 ? now()->subDays(rand(1, 30)) : null,
                                'notes' => json_encode(['text' => 'ملاحظات التقدم']),
                            ]
                        );
                    }
                }
            }
        }
    }

    private function createPayments(array $academies): void
    {
        $this->command->info('Creating payments...');
        
        foreach ($academies as $academy) {
            // Create payments for Quran subscriptions
            $quranSubscriptions = QuranSubscription::where('academy_id', $academy->id)->get();
            foreach ($quranSubscriptions as $subscription) {
                Payment::firstOrCreate(
                    [
                        'academy_id' => $academy->id,
                        'subscription_id' => $subscription->id,
                        'payment_type' => 'subscription',
                    ],
                    [
                        'user_id' => $subscription->student_id,
                        'amount' => $subscription->final_price,
                        'net_amount' => $subscription->final_price,
                        'currency' => 'SAR',
                        'payment_method' => ['credit_card', 'bank_transfer', 'cash'][rand(0, 2)],
                        'status' => ['completed', 'pending', 'failed'][rand(0, 2)],
                        'payment_code' => 'PAY-' . $academy->id . '-' . rand(100000,999999),
                        'notes' => 'دفع اشتراك القرآن',
                    ]
                );
            }
            
            // Create payments for course subscriptions
            $courseSubscriptions = CourseSubscription::where('academy_id', $academy->id)->get();
            foreach ($courseSubscriptions as $subscription) {
                Payment::firstOrCreate(
                    [
                        'academy_id' => $academy->id,
                        'subscription_id' => $subscription->id,
                        'payment_type' => 'subscription',
                    ],
                    [
                        'user_id' => $subscription->student_id,
                        'amount' => $subscription->price_paid,
                        'net_amount' => $subscription->price_paid,
                        'currency' => 'SAR',
                        'payment_method' => ['credit_card', 'bank_transfer', 'cash'][rand(0, 2)],
                        'status' => ['completed', 'pending', 'failed'][rand(0, 2)],
                        'payment_code' => 'PAY-' . $academy->id . '-' . rand(100000,999999),
                        'notes' => 'دفع اشتراك الدورة',
                    ]
                );
            }
        }
    }

    private function displayLoginCredentials(array $academies, array $users): void
    {
        $this->command->info('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('All passwords: password123');
        $this->command->info('');
        
        foreach ($academies as $academy) {
            $this->command->info("🏢 {$academy->name} ({$academy->subdomain}):");
            $this->command->info("   Admin: admin@{$academy->subdomain}.com");
            $this->command->info("   URL: http://{$academy->subdomain}.itqan-platform.test");
            $this->command->info('');
        }
        
        $this->command->info('👥 Sample Users (for first academy):');
        $firstAcademy = $academies[0];
        $firstAcademyUsers = array_filter($users['quranTeachers'], fn($teacher) => $teacher->academy_id === $firstAcademy->id);
        $firstAcademyStudents = array_filter($users['students'], fn($student) => $student->academy_id === $firstAcademy->id);
        
        if (!empty($firstAcademyUsers)) {
            $this->command->info("   Quran Teacher: {$firstAcademyUsers[0]->email}");
        }
        if (!empty($firstAcademyStudents)) {
            $this->command->info("   Student: {$firstAcademyStudents[0]->email}");
        }
        $this->command->info('');
        $this->command->info('📊 Created Data Summary:');
        $this->command->info('   - ' . count($academies) . ' Academies');
        $this->command->info('   - ' . count($users['quranTeachers']) . ' Quran Teachers');
        $this->command->info('   - ' . count($users['academicTeachers']) . ' Academic Teachers');
        $this->command->info('   - ' . count($users['students']) . ' Students');
        $this->command->info('   - ' . count($users['parents']) . ' Parents');
        $this->command->info('   - Multiple courses, subscriptions, and sessions');
    }
} 