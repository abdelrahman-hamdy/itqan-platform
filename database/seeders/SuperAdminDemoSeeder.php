<?php

namespace Database\Seeders;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubject;
use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoDomain = config('seeding.demo_email_domain');
        $defaultPassword = config('seeding.default_password');

        // Create Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@'.$demoDomain],
            [
                'academy_id' => null, // Super admin doesn't belong to any specific academy
                'first_name' => 'مدير',
                'last_name' => 'النظام',
                'email' => 'admin@'.$demoDomain,
                'email_verified_at' => now(),
                'password' => Hash::make($defaultPassword),
                'user_type' => 'super_admin',
                'status' => 'active',
                'phone' => '+966501234567',
                'bio' => 'مدير النظام الرئيسي لمنصة إتقان',
            ]
        );

        // Create default Itqan Academy
        $itqanAcademy = Academy::updateOrCreate(
            ['subdomain' => 'itqan-academy'],
            [
                'name' => 'أكاديمية مَعِين',
                'subdomain' => 'itqan-academy',
                'description' => 'أكاديمية مَعِين الرئيسية لتعليم القرآن الكريم والعلوم الدراسية',
                'logo' => 'academies/itqan-logo.png',
                'brand_color' => '#0ea5e9',
                'status' => 'active',
                'is_active' => true,
                'admin_id' => null, // Will set after creating academy admin
                'total_revenue' => 125000.00,
                'monthly_revenue' => 25000.00,
                'pending_payments' => 5000.00,
                'active_subscriptions' => 150,
                'growth_rate' => 15.5,
            ]
        );

        // Create additional demo academies
        $academies = [
            [
                'name' => 'أكاديمية النور',
                'subdomain' => 'alnoor',
                'description' => 'أكاديمية متخصصة في تحفيظ القرآن الكريم',
                'status' => 'active',
                'total_revenue' => 85000.00,
                'monthly_revenue' => 18000.00,
                'active_subscriptions' => 95,
                'growth_rate' => 12.3,
            ],
            [
                'name' => 'أكاديمية العلوم',
                'subdomain' => 'sciences',
                'description' => 'أكاديمية متخصصة في العلوم الدراسية',
                'status' => 'active',
                'total_revenue' => 65000.00,
                'monthly_revenue' => 15000.00,
                'active_subscriptions' => 75,
                'growth_rate' => 8.7,
            ],
            [
                'name' => 'أكاديمية المستقبل',
                'subdomain' => 'future',
                'description' => 'أكاديمية حديثة للتعليم التفاعلي',
                'status' => 'maintenance',
                'total_revenue' => 35000.00,
                'monthly_revenue' => 8000.00,
                'active_subscriptions' => 45,
                'growth_rate' => 5.2,
            ],
        ];

        foreach ($academies as $academyData) {
            Academy::create(array_merge($academyData, [
                'logo' => 'academies/demo-logo.png',
                'brand_color' => '#0ea5e9',
                'is_active' => true,
                'pending_payments' => rand(2000, 8000),
            ]));
        }

        // Create demo users for different roles
        $this->createDemoUsers($itqanAcademy);

        // Create grade levels for academies
        $this->createGradeLevels();

        // Create demo subjects
        $this->createDemoSubjects();

        // Set academy admin for Itqan Academy
        $academyAdmin = User::where('email', 'academy.admin@'.$demoDomain)->first();
        if ($academyAdmin) {
            $itqanAcademy->update(['admin_id' => $academyAdmin->id]);
        }

        $this->command->info('Super Admin demo data seeded successfully!');
        $this->command->info('Super Admin Login: admin@'.$demoDomain);
        $this->command->info('Password: '.$defaultPassword);
    }

    private function createDemoUsers(Academy $academy): void
    {
        $demoDomain = config('seeding.demo_email_domain');
        $defaultPassword = config('seeding.default_password');

        // Academy Admin
        User::create([
            'first_name' => 'أحمد',
            'last_name' => 'المدير',
            'email' => 'academy.admin@'.$demoDomain,
            'password' => Hash::make($defaultPassword),
            'role' => 'academy_admin',
            'status' => 'active',
            'academy_id' => $academy->id,
            'phone' => '+966502345678',
            'bio' => 'مدير أكاديمية مَعِين',
        ]);

        // Teachers
        $teachers = [
            ['name' => 'محمد الحافظ', 'email' => 'teacher1@'.$demoDomain, 'type' => 'quran'],
            ['name' => 'فاطمة القارئة', 'email' => 'teacher2@'.$demoDomain, 'type' => 'quran'],
            ['name' => 'عبدالله العالم', 'email' => 'teacher3@'.$demoDomain, 'type' => 'academic'],
            ['name' => 'مريم المعلمة', 'email' => 'teacher4@'.$demoDomain, 'type' => 'academic'],
        ];

        foreach ($teachers as $index => $teacher) {
            User::create([
                'first_name' => explode(' ', $teacher['name'])[0],
                'last_name' => explode(' ', $teacher['name'])[1],
                'email' => $teacher['email'],
                'password' => Hash::make($defaultPassword),
                'role' => 'teacher',
                'status' => $index < 3 ? 'active' : 'pending',
                'academy_id' => $academy->id,
                'phone' => '+96650'.(3456789 + $index),
                'teacher_type' => $teacher['type'],
                'years_experience' => rand(2, 10),
                'student_session_price' => rand(50, 150),
                'teacher_session_price' => rand(100, 250),
                'bio' => 'معلم متخصص في '.($teacher['type'] === 'quran' ? 'القرآن الكريم' : 'العلوم الدراسية'),
            ]);
        }

        // Students
        for ($i = 1; $i <= 20; $i++) {
            $firstName = ['أحمد', 'محمد', 'فاطمة', 'عائشة', 'علي', 'خديجة', 'يوسف', 'مريم'][$i % 8];
            $lastName = ['الطالب', 'المتعلم', 'القارئ', 'الدارس'][$i % 4];

            User::create([
                'first_name' => $firstName,
                'last_name' => $lastName.' '.$i,
                'email' => "student{$i}@{$demoDomain}",
                'password' => Hash::make($defaultPassword),
                'role' => 'student',
                'status' => 'active',
                'academy_id' => $academy->id,
                'phone' => '+96655'.str_pad($i, 7, '0', STR_PAD_LEFT),
                'parent_phone' => '+96656'.str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);
        }

        // Parents (auto-created for students)
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'first_name' => 'ولي أمر',
                'last_name' => 'الطالب '.$i,
                'email' => "parent{$i}@{$demoDomain}",
                'password' => Hash::make($defaultPassword),
                'role' => 'parent',
                'status' => 'active',
                'academy_id' => $academy->id,
                'phone' => '+96656'.str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);
        }

        // Supervisors
        User::create([
            'first_name' => 'سارة',
            'last_name' => 'المشرفة',
            'email' => 'supervisor@'.$demoDomain,
            'password' => Hash::make($defaultPassword),
            'role' => 'supervisor',
            'status' => 'active',
            'academy_id' => $academy->id,
            'phone' => '+966507890123',
            'bio' => 'مشرفة تعليمية',
        ]);
    }

    private function createGradeLevels(): void
    {
        $academies = Academy::all();

        $gradeLevels = [
            ['name' => 'ابتدائي', 'name_en' => 'Primary'],
            ['name' => 'إعدادي', 'name_en' => 'Preparatory'],
            ['name' => 'ثانوي', 'name_en' => 'Secondary'],
            ['name' => 'جامعي', 'name_en' => 'University'],
        ];

        foreach ($academies as $academy) {
            foreach ($gradeLevels as $gradeLevel) {
                AcademicGradeLevel::create(array_merge($gradeLevel, [
                    'academy_id' => $academy->id,
                    'description' => 'المرحلة '.$gradeLevel['name'],
                ]));
            }
        }
    }

    private function createDemoSubjects(): void
    {
        $academies = Academy::all();

        $subjects = [
            // Quran subjects
            ['name' => 'تحفيظ القرآن الكريم', 'name_en' => 'Quran Memorization', 'category' => 'quran', 'is_academic' => false],
            ['name' => 'تجويد القرآن الكريم', 'name_en' => 'Quran Tajweed', 'category' => 'quran', 'is_academic' => false],
            ['name' => 'تفسير القرآن الكريم', 'name_en' => 'Quran Interpretation', 'category' => 'quran', 'is_academic' => false],

            // Academic subjects
            ['name' => 'الرياضيات', 'name_en' => 'Mathematics', 'category' => 'mathematics', 'is_academic' => true],
            ['name' => 'العلوم', 'name_en' => 'Science', 'category' => 'science', 'is_academic' => true],
            ['name' => 'اللغة العربية', 'name_en' => 'Arabic Language', 'category' => 'language', 'is_academic' => true],
            ['name' => 'اللغة الإنجليزية', 'name_en' => 'English Language', 'category' => 'language', 'is_academic' => true],
            ['name' => 'التاريخ', 'name_en' => 'History', 'category' => 'social', 'is_academic' => true],
            ['name' => 'الجغرافيا', 'name_en' => 'Geography', 'category' => 'social', 'is_academic' => true],
            ['name' => 'التربية الإسلامية', 'name_en' => 'Islamic Education', 'category' => 'islamic', 'is_academic' => true],
        ];

        foreach ($academies as $academy) {
            foreach ($subjects as $subject) {
                AcademicSubject::create(array_merge($subject, [
                    'academy_id' => $academy->id,
                    'description' => 'مادة '.$subject['name'].' في أكاديمية '.$academy->name,
                ]));
            }
        }
    }
}
