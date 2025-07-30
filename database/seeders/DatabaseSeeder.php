<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Academy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin (no academy association)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@itqan.com'],
            [
                'academy_id' => null, // Super admin doesn't belong to any specific academy
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+966501234567',
                'role' => 'super_admin',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Create default Itqan Academy
        $itqanAcademy = Academy::firstOrCreate(
            ['subdomain' => 'itqan'],
            [
                'name' => 'أكاديمية إتقان',
                'description' => 'الأكاديمية الرئيسية لمنصة إتقان التعليمية',
                'status' => 'active',
                'is_active' => true,
                'brand_color' => '#0ea5e9',
                'total_revenue' => 25000.00,
                'monthly_revenue' => 5000.00,
                'pending_payments' => 1200.00,
                'active_subscriptions' => 45,
                'growth_rate' => 12.5,
            ]
        );

        // Create Academy Admin for Itqan Academy
        $itqanAdmin = User::firstOrCreate(
            ['email' => 'itqan.admin@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'أحمد',
                'last_name' => 'محمد',
                'phone' => '+966502345678',
                'role' => 'academy_admin',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'bio' => 'مدير أكاديمية إتقان الرئيسية',
            ]
        );

        // Update academy with admin
        $itqanAcademy->update(['admin_id' => $itqanAdmin->id]);

        // Create Sample Academy 2
        $alnoorAcademy = Academy::firstOrCreate(
            ['subdomain' => 'alnoor'],
            [
                'name' => 'أكاديمية النور',
                'description' => 'أكاديمية متخصصة في تعليم القرآن الكريم',
                'status' => 'active',
                'is_active' => true,
                'brand_color' => '#22c55e',
                'total_revenue' => 18000.00,
                'monthly_revenue' => 3500.00,
                'pending_payments' => 800.00,
                'active_subscriptions' => 32,
                'growth_rate' => 8.3,
            ]
        );

        // Create Academy Admin for Alnoor Academy
        $alnoorAdmin = User::firstOrCreate(
            ['email' => 'alnoor.admin@itqan.com'],
            [
                'academy_id' => $alnoorAcademy->id,
                'first_name' => 'فاطمة',
                'last_name' => 'العلي',
                'phone' => '+966503456789',
                'role' => 'academy_admin',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'bio' => 'مديرة أكاديمية النور المتخصصة',
            ]
        );

        // Update academy with admin
        $alnoorAcademy->update(['admin_id' => $alnoorAdmin->id]);

        // Create Sample Teachers for Itqan Academy
        $quranTeacher = User::firstOrCreate(
            ['email' => 'quran.teacher@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'عبدالله',
                'last_name' => 'الحافظ',
                'phone' => '+966504567890',
                'role' => 'teacher',
                'status' => 'active',
                'teacher_type' => 'quran',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'bio' => 'معلم القرآن الكريم مع إجازة في القراءات',
                'has_ijazah' => true,
                'years_experience' => 8,
                'student_session_price' => 50.00,
                'teacher_session_price' => 35.00,
            ]
        );

        $academicTeacher = User::firstOrCreate(
            ['email' => 'math.teacher@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'سارة',
                'last_name' => 'الأحمد',
                'phone' => '+966505678901',
                'role' => 'teacher',
                'status' => 'active',
                'teacher_type' => 'academic',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'bio' => 'معلمة الرياضيات والعلوم للمرحلة الثانوية',
                'qualification_degree' => 'master',
                'qualification_text' => 'ماجستير في الرياضيات التطبيقية',
                'university' => 'جامعة الملك سعود',
                'years_experience' => 6,
                'student_session_price' => 60.00,
                'teacher_session_price' => 42.00,
            ]
        );

        // Create Sample Students for Itqan Academy
        $parent = User::firstOrCreate(
            ['email' => 'parent@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'خالد',
                'last_name' => 'المحمد',
                'phone' => '+966506789012',
                'role' => 'parent',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $student1 = User::firstOrCreate(
            ['email' => 'student1@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'عمر',
                'last_name' => 'المحمد',
                'phone' => '+966507890123',
                'role' => 'student',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'parent_id' => $parent->id,
                'parent_phone' => $parent->phone,
            ]
        );

        $student2 = User::firstOrCreate(
            ['email' => 'student2@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'نورا',
                'last_name' => 'المحمد',
                'phone' => '+966508901234',
                'role' => 'student',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'parent_id' => $parent->id,
                'parent_phone' => $parent->phone,
            ]
        );

        // Create Supervisor for Itqan Academy
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@itqan.com'],
            [
                'academy_id' => $itqanAcademy->id,
                'first_name' => 'محمد',
                'last_name' => 'السالم',
                'phone' => '+966509012345',
                'role' => 'supervisor',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'bio' => 'مشرف الجودة والمتابعة الأكاديمية',
            ]
        );

        // Create some users for Alnoor Academy
        $alnoorTeacher = User::firstOrCreate(
            ['email' => 'hafez@alnoor.itqan.com'],
            [
                'academy_id' => $alnoorAcademy->id,
                'first_name' => 'يوسف',
                'last_name' => 'القارئ',
                'phone' => '+966510123456',
                'role' => 'teacher',
                'status' => 'active',
                'teacher_type' => 'quran',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'bio' => 'حافظ القرآن الكريم ومعلم التجويد',
                'has_ijazah' => true,
                'years_experience' => 12,
                'student_session_price' => 45.00,
                'teacher_session_price' => 32.00,
            ]
        );

        $alnoorParent = User::firstOrCreate(
            ['email' => 'parent@alnoor.itqan.com'],
            [
                'academy_id' => $alnoorAcademy->id,
                'first_name' => 'عائشة',
                'last_name' => 'الزهراني',
                'phone' => '+966511234567',
                'role' => 'parent',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $alnoorStudent = User::firstOrCreate(
            ['email' => 'student@alnoor.itqan.com'],
            [
                'academy_id' => $alnoorAcademy->id,
                'first_name' => 'زينب',
                'last_name' => 'الزهراني',
                'phone' => '+966512345678',
                'role' => 'student',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'parent_id' => $alnoorParent->id,
                'parent_phone' => $alnoorParent->phone,
            ]
        );

        // Output login credentials
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('🔐 Super Admin Login Credentials:');
        $this->command->info('Email: admin@itqan.com');
        $this->command->info('Password: password123');
        $this->command->info('URL: http://localhost:8000/admin');
        $this->command->info('');
        $this->command->info('🏢 Sample Academies Created:');
        $this->command->info('1. أكاديمية إتقان (itqan.itqan.com) - Admin: itqan.admin@itqan.com');
        $this->command->info('2. أكاديمية النور (alnoor.itqan.com) - Admin: alnoor.admin@itqan.com');
        $this->command->info('');
        $this->command->info('👥 Sample Users:');
        $this->command->info('- Quran Teacher: quran.teacher@itqan.com');
        $this->command->info('- Academic Teacher: math.teacher@itqan.com');
        $this->command->info('- Parent: parent@itqan.com');
        $this->command->info('- Students: student1@itqan.com, student2@itqan.com');
        $this->command->info('- Supervisor: supervisor@itqan.com');
        $this->command->info('');
        $this->command->info('🔑 All passwords: password123');
    }
}
