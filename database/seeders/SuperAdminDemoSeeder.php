<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Academy;
use App\Models\User;
use App\Models\Subject;
use App\Models\GradeLevel;
use Illuminate\Support\Facades\Hash;

class SuperAdminDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@itqan-platform.test'],
            [
                'academy_id' => null, // Super admin doesn't belong to any specific academy
                'first_name' => 'مدير',
                'last_name' => 'النظام',
                'email' => 'admin@itqan-platform.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
                'phone' => '+966501234567',
                'bio' => 'مدير النظام الرئيسي لمنصة إتقان',
            ]
        );

        // Create default Itqan Academy
        $itqanAcademy = Academy::updateOrCreate(
            ['subdomain' => 'itqan-academy'],
            [
                'name' => 'أكاديمية إتقان',
                'subdomain' => 'itqan-academy',
                'description' => 'أكاديمية إتقان الرئيسية لتعليم القرآن الكريم والعلوم الأكاديمية',
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
                'description' => 'أكاديمية متخصصة في العلوم الأكاديمية',
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
        $academyAdmin = User::where('email', 'academy.admin@itqan-platform.test')->first();
        if ($academyAdmin) {
            $itqanAcademy->update(['admin_id' => $academyAdmin->id]);
        }

        $this->command->info('Super Admin demo data seeded successfully!');
        $this->command->info('Super Admin Login: admin@itqan-platform.test');
        $this->command->info('Password: password');
    }

    private function createDemoUsers(Academy $academy): void
    {
        // Academy Admin
        User::create([
            'first_name' => 'أحمد',
            'last_name' => 'المدير',
            'email' => 'academy.admin@itqan-platform.test',
            'password' => Hash::make('password'),
            'role' => 'academy_admin',
            'status' => 'active',
            'academy_id' => $academy->id,
            'phone' => '+966502345678',
            'bio' => 'مدير أكاديمية إتقان',
        ]);

        // Teachers
        $teachers = [
            ['name' => 'محمد الحافظ', 'email' => 'teacher1@itqan-platform.test', 'type' => 'quran'],
            ['name' => 'فاطمة القارئة', 'email' => 'teacher2@itqan-platform.test', 'type' => 'quran'],
            ['name' => 'عبدالله العالم', 'email' => 'teacher3@itqan-platform.test', 'type' => 'academic'],
            ['name' => 'مريم المعلمة', 'email' => 'teacher4@itqan-platform.test', 'type' => 'academic'],
        ];

        foreach ($teachers as $index => $teacher) {
            User::create([
                'first_name' => explode(' ', $teacher['name'])[0],
                'last_name' => explode(' ', $teacher['name'])[1],
                'email' => $teacher['email'],
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'status' => $index < 3 ? 'active' : 'pending',
                'academy_id' => $academy->id,
                'phone' => '+96650' . (3456789 + $index),
                'teacher_type' => $teacher['type'],
                'has_ijazah' => $teacher['type'] === 'quran',
                'years_experience' => rand(2, 10),
                'student_session_price' => rand(50, 150),
                'teacher_session_price' => rand(100, 250),
                'bio' => 'معلم متخصص في ' . ($teacher['type'] === 'quran' ? 'القرآن الكريم' : 'العلوم الأكاديمية'),
            ]);
        }

        // Students
        for ($i = 1; $i <= 20; $i++) {
            $firstName = ['أحمد', 'محمد', 'فاطمة', 'عائشة', 'علي', 'خديجة', 'يوسف', 'مريم'][$i % 8];
            $lastName = ['الطالب', 'المتعلم', 'القارئ', 'الدارس'][$i % 4];
            
            User::create([
                'first_name' => $firstName,
                'last_name' => $lastName . ' ' . $i,
                'email' => "student{$i}@itqan-platform.test",
                'password' => Hash::make('password'),
                'role' => 'student',
                'status' => 'active',
                'academy_id' => $academy->id,
                'phone' => '+96655' . str_pad($i, 7, '0', STR_PAD_LEFT),
                'parent_phone' => '+96656' . str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);
        }

        // Parents (auto-created for students)
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'first_name' => 'ولي أمر',
                'last_name' => 'الطالب ' . $i,
                'email' => "parent{$i}@itqan-platform.test",
                'password' => Hash::make('password'),
                'role' => 'parent',
                'status' => 'active',
                'academy_id' => $academy->id,
                'phone' => '+96656' . str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);
        }

        // Supervisors
        User::create([
            'first_name' => 'سارة',
            'last_name' => 'المشرفة',
            'email' => 'supervisor@itqan-platform.test',
            'password' => Hash::make('password'),
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
            ['name' => 'ابتدائي', 'name_en' => 'Primary', 'level' => 1, 'min_age' => 6, 'max_age' => 12],
            ['name' => 'إعدادي', 'name_en' => 'Preparatory', 'level' => 2, 'min_age' => 12, 'max_age' => 15],
            ['name' => 'ثانوي', 'name_en' => 'Secondary', 'level' => 3, 'min_age' => 15, 'max_age' => 18],
            ['name' => 'جامعي', 'name_en' => 'University', 'level' => 4, 'min_age' => 18, 'max_age' => 25],
        ];

        foreach ($academies as $academy) {
            foreach ($gradeLevels as $gradeLevel) {
                GradeLevel::create(array_merge($gradeLevel, [
                    'academy_id' => $academy->id,
                    'description' => 'المرحلة ' . $gradeLevel['name'],
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
                Subject::create(array_merge($subject, [
                    'academy_id' => $academy->id,
                    'description' => 'مادة ' . $subject['name'] . ' في أكاديمية ' . $academy->name,
                ]));
            }
        }
    }
} 