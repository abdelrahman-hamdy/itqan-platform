<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\StudentProfile;
use App\Models\QuranPackage;
use App\Models\Academy;
use Illuminate\Support\Facades\DB;

class QuranCircleTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Get the first academy
            $academy = Academy::first();
            if (!$academy) {
                $this->command->error('No academy found. Please create an academy first.');
                return;
            }

            // Create or get a test teacher
            $teacher = User::where('email', 'teacher@test.com')->first();
            if (!$teacher) {
                $teacher = User::create([
                    'first_name' => 'معلم',
                    'last_name' => 'تجريبي',
                    'email' => 'teacher@test.com',
                    'phone' => '01234567890',
                    'password' => bcrypt('password'),
                    'user_type' => 'quran_teacher',
                    'academy_id' => $academy->id,
                    'status' => 'active',
                ]);
            }

            // Create teacher profile if not exists
            $teacherProfile = QuranTeacherProfile::where('user_id', $teacher->id)->first();
            if (!$teacherProfile) {
                $teacherProfile = QuranTeacherProfile::create([
                    'user_id' => $teacher->id,
                    'academy_id' => $academy->id,
                    'first_name' => 'معلم',
                    'last_name' => 'تجريبي',
                    'email' => 'teacher@test.com',
                    'phone' => '01234567890',
                    'bio_arabic' => 'معلم قرآن تجريبي',
                    'teaching_experience_years' => 5,
                    'approval_status' => 'approved',
                    'is_active' => true,
                    'offers_trial_sessions' => true,
                ]);
            }

            // Create test group circles (simplified)
            $groupCircles = [
                [
                    'name_ar' => 'حلقة تحفيظ الأطفال',
                    'age_group' => 'children',
                    'gender_type' => 'mixed',
                    'max_students' => 10,
                    'session_duration_minutes' => 60,
                    'monthly_sessions_count' => 8,
                ],
                [
                    'name_ar' => 'حلقة تجويد الشباب',
                    'age_group' => 'youth', 
                    'gender_type' => 'male',
                    'max_students' => 8,
                    'session_duration_minutes' => 60,
                    'monthly_sessions_count' => 12,
                ],
                [
                    'name_ar' => 'حلقة النساء المسائية',
                    'age_group' => 'adults',
                    'gender_type' => 'female',
                    'max_students' => 6,
                    'session_duration_minutes' => 60,
                    'monthly_sessions_count' => 16,
                    'schedule_days' => ['sunday', 'tuesday', 'thursday'],
                    'schedule_time' => '19:00:00',
                ],
            ];

            foreach ($groupCircles as $circleData) {
                QuranCircle::updateOrCreate(
                    [
                        'name_ar' => $circleData['name_ar'],
                        'quran_teacher_id' => $teacher->id,
                        'academy_id' => $academy->id,
                    ],
                    array_merge($circleData, [
                        'quran_teacher_id' => $teacher->id,
                        'academy_id' => $academy->id,
                    ])
                );
            }

            // Create test student for individual circles
            $student = User::where('email', 'student@test.com')->first();
            if (!$student) {
                $student = User::create([
                    'first_name' => 'طالب',
                    'last_name' => 'تجريبي',
                    'email' => 'student@test.com',
                    'phone' => '01234567891',
                    'password' => bcrypt('password'),
                    'user_type' => 'student',
                    'academy_id' => $academy->id,
                    'status' => 'active',
                ]);
            }

            // Create student profile if not exists
            $studentProfile = StudentProfile::where('user_id', $student->id)->first();
            if (!$studentProfile) {
                $studentProfile = StudentProfile::create([
                    'user_id' => $student->id,
                    'academy_id' => $academy->id,
                    'grade_level' => 'grade_5',
                    'date_of_birth' => '2010-01-01',
                    'gender' => 'male',
                    'status' => 'active',
                ]);
            }

            // Create test package if not exists
            $package = QuranPackage::where('name_ar', 'باقة تجريبية')->first();
            if (!$package) {
                $package = QuranPackage::create([
                    'name_ar' => 'باقة تجريبية',
                    'name_en' => 'Test Package',
                    'description' => 'باقة تجريبية للاختبار',
                    'sessions_count' => 8,
                    'price' => 200,
                    'duration_days' => 30,
                    'academy_id' => $academy->id,
                ]);
            }

            // Create test subscription and individual circle
            $subscription = QuranSubscription::where('student_id', $student->id)->first();
            if (!$subscription) {
                $subscription = QuranSubscription::create([
                    'student_id' => $student->id,
                    'quran_teacher_id' => $teacher->id,
                    'package_id' => $package->id,
                    'academy_id' => $academy->id,
                    'start_date' => now(),
                    'end_date' => now()->addDays(30),
                    'status' => 'active',
                    'total_amount' => 200,
                    'sessions_remaining' => 8,
                ]);

                // Create individual circle for this subscription
                QuranIndividualCircle::create([
                    'subscription_id' => $subscription->id,
                    'quran_teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'academy_id' => $academy->id,
                    'status' => 'active',
                ]);
            }

            // Create another student and subscription
            $student2 = User::where('email', 'student2@test.com')->first();
            if (!$student2) {
                $student2 = User::create([
                    'first_name' => 'طالبة',
                    'last_name' => 'تجريبية',
                    'email' => 'student2@test.com',
                    'phone' => '01234567892',
                    'password' => bcrypt('password'),
                    'user_type' => 'student',
                    'academy_id' => $academy->id,
                    'status' => 'active',
                ]);

                $studentProfile2 = StudentProfile::create([
                    'user_id' => $student2->id,
                    'academy_id' => $academy->id,
                    'grade_level' => 'grade_3',
                    'date_of_birth' => '2012-01-01',
                    'gender' => 'female',
                    'status' => 'active',
                ]);

                $subscription2 = QuranSubscription::create([
                    'student_id' => $student2->id,
                    'quran_teacher_id' => $teacher->id,
                    'package_id' => $package->id,
                    'academy_id' => $academy->id,
                    'start_date' => now(),
                    'end_date' => now()->addDays(30),
                    'status' => 'active',
                    'total_amount' => 200,
                    'sessions_remaining' => 6,
                ]);

                QuranIndividualCircle::create([
                    'subscription_id' => $subscription2->id,
                    'quran_teacher_id' => $teacher->id,
                    'student_id' => $student2->id,
                    'academy_id' => $academy->id,
                    'status' => 'active',
                ]);
            }

            $this->command->info('Test Quran circles and data created successfully!');
            $this->command->info('Teacher Email: teacher@test.com');
            $this->command->info('Password: password');
        });
    }
}