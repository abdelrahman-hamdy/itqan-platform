<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\InteractiveCourse;
use App\Models\InteractiveCourseSession;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSubscriptionConsumption extends Command
{
    protected $signature = 'test:subscription-consumption {--cleanup : Only run cleanup without tests}';
    protected $description = 'Test subscription consumption for all session types';

    private $academy;
    private $student;
    private $quranTeacher;
    private $academicTeacher;
    private $testData = [];
    private $results = [];

    public function handle()
    {
        if ($this->option('cleanup')) {
            return $this->cleanupOnly();
        }

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Subscription Consumption Test Suite');
        $this->info('═══════════════════════════════════════════════════════════════');

        try {
            $this->setupTestEnvironment();
            $this->runAllTests();
            $this->printSummary();
            $this->cleanup();

            return $this->results['failed'] === 0 ? self::SUCCESS : self::FAILURE;

        } catch (\Exception $e) {
            $this->error("Test suite failed: {$e->getMessage()}");
            $this->cleanup();
            return self::FAILURE;
        }
    }

    private function setupTestEnvironment()
    {
        $this->academy = \App\Models\Academy::first();
        if (!$this->academy) {
            throw new \Exception('No academy found');
        }

        $this->student = User::where('email', 'abdelrahman260598@gmail.com')->first();
        if (!$this->student) {
            throw new \Exception('Test student not found');
        }

        $this->quranTeacher = User::whereHas('quranTeacherProfile')->first();
        if (!$this->quranTeacher) {
            throw new \Exception('No Quran teacher found');
        }

        $this->academicTeacher = User::whereHas('academicTeacherProfile')->first();
        if (!$this->academicTeacher) {
            throw new \Exception('No academic teacher found');
        }

        $this->info("Academy: {$this->academy->name}");
        $this->info("Student: {$this->student->name} (ID: {$this->student->id})");
        $this->info("Quran Teacher: {$this->quranTeacher->name}");
        $this->info("Academic Teacher: {$this->academicTeacher->name}");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();
    }

    private function runAllTests()
    {
        $this->results = ['passed' => 0, 'failed' => 0, 'tests' => []];

        $this->testIndividualQuranSession();
        $this->testAcademicLesson();
    }

    private function testIndividualQuranSession()
    {
        $this->info('📝 Test 1: Individual Quran Session Consumption');
        $this->info('───────────────────────────────────────────────');

        DB::beginTransaction();
        try {
            // Create individual circle
            $circle = QuranCircle::create([
                'academy_id' => $this->academy->id,
                'quran_teacher_id' => $this->quranTeacher->quranTeacherProfile->id,
                'name' => 'TEST-CIRCLE-' . now()->timestamp,
                'circle_name' => 'TEST-CIRCLE-' . now()->timestamp,
                'circle_type' => 'individual',
                'total_sessions' => 5,
            ]);
            $this->testData['individual_circle'] = $circle;

            // Create subscription
            $subscription = QuranSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->quranTeacherProfile->id,
                'quran_individual_circle_id' => $circle->id,
                'subscription_type' => 'individual',
                'status' => SessionSubscriptionStatus::ACTIVE,
                'total_sessions' => 5,
                'sessions_used' => 0,
                'sessions_remaining' => 5,
            ]);
            $this->testData['individual_subscription'] = $subscription;

            $this->line("✓ Created subscription ID: {$subscription->id} (5 sessions)");

            // Create session
            $session = QuranSession::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'quran_teacher_id' => $this->quranTeacher->quranTeacherProfile->id,
                'quran_subscription_id' => $subscription->id,
                'quran_individual_circle_id' => $circle->id,
                'session_type' => 'individual',
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now(),
                'started_at' => now()->subMinutes(30),
                'duration_minutes' => 45,
            ]);
            $this->testData['individual_session'] = $session;

            $this->line("✓ Created session ID: {$session->id}");

            // Mark as completed (simulates Filament action)
            $session->markAsCompleted();

            // Refresh
            $session->refresh();
            $subscription->refresh();

            // Verify
            $this->verifyConsumption(
                'Individual Quran',
                $session,
                $subscription,
                1,
                4
            );

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->recordFailure('Individual Quran', $e->getMessage());
        }
    }

    private function testAcademicLesson()
    {
        $this->newLine();
        $this->info('📝 Test 2: Academic Individual Lesson Consumption');
        $this->info('───────────────────────────────────────────────');

        DB::beginTransaction();
        try {
            // Create lesson
            $lesson = AcademicIndividualLesson::create([
                'academy_id' => $this->academy->id,
                'academic_teacher_id' => $this->academicTeacher->academicTeacherProfile->id,
                'student_id' => $this->student->id,
                'name' => 'TEST-LESSON-' . now()->timestamp,
                'total_sessions' => 4,
            ]);
            $this->testData['academic_lesson'] = $lesson;

            // Create subscription
            $subscription = AcademicSubscription::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'academic_teacher_id' => $this->academicTeacher->academicTeacherProfile->id,
                'academic_individual_lesson_id' => $lesson->id,
                'subscription_type' => 'individual',
                'status' => SessionSubscriptionStatus::ACTIVE,
                'total_sessions' => 4,
                'sessions_used' => 0,
                'sessions_remaining' => 4,
            ]);
            $this->testData['academic_subscription'] = $subscription;

            $this->line("✓ Created subscription ID: {$subscription->id} (4 sessions)");

            // Create session
            $session = AcademicSession::create([
                'academy_id' => $this->academy->id,
                'student_id' => $this->student->id,
                'academic_teacher_id' => $this->academicTeacher->academicTeacherProfile->id,
                'academic_subscription_id' => $subscription->id,
                'academic_individual_lesson_id' => $lesson->id,
                'session_type' => 'individual',
                'status' => SessionStatus::ONGOING,
                'scheduled_at' => now(),
                'started_at' => now()->subMinutes(30),
                'duration_minutes' => 60,
            ]);
            $this->testData['academic_session'] = $session;

            $this->line("✓ Created session ID: {$session->id}");

            // Mark as completed
            $session->markAsCompleted();

            // Refresh
            $session->refresh();
            $subscription->refresh();

            // Verify
            $this->verifyConsumption(
                'Academic Lesson',
                $session,
                $subscription,
                1,
                3
            );

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->recordFailure('Academic Lesson', $e->getMessage());
        }
    }

    private function verifyConsumption($testName, $session, $subscription, $expectedUsed, $expectedRemaining)
    {
        $errors = [];

        if ($session->status !== SessionStatus::COMPLETED) {
            $errors[] = "Status is {$session->status->value}, expected COMPLETED";
        }

        if (!$session->subscription_counted) {
            $errors[] = "subscription_counted is FALSE, expected TRUE";
        }

        if ($subscription->sessions_used != $expectedUsed) {
            $errors[] = "sessions_used is {$subscription->sessions_used}, expected {$expectedUsed}";
        }

        if ($subscription->sessions_remaining != $expectedRemaining) {
            $errors[] = "sessions_remaining is {$subscription->sessions_remaining}, expected {$expectedRemaining}";
        }

        $passed = count($errors) === 0;

        $this->results['tests'][$testName] = [
            'passed' => $passed,
            'errors' => $errors,
        ];

        if ($passed) {
            $this->results['passed']++;
        } else {
            $this->results['failed']++;
        }

        if ($passed) {
            $this->info("✅ PASSED");
        } else {
            $this->error("❌ FAILED");
            foreach ($errors as $error) {
                $this->warn("   ⚠️  {$error}");
            }
        }

        $this->line("   Session: {$session->status->value}, Counted: " . ($session->subscription_counted ? 'YES' : 'NO'));
        $this->line("   Subscription: Used={$subscription->sessions_used}, Remaining={$subscription->sessions_remaining}");
    }

    private function recordFailure($testName, $message)
    {
        $this->results['tests'][$testName] = [
            'passed' => false,
            'errors' => [$message],
        ];
        $this->results['failed']++;
        $this->error("❌ FAILED: {$message}");
    }

    private function printSummary()
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Test Summary');
        $this->info('═══════════════════════════════════════════════════════════════');

        foreach ($this->results['tests'] as $name => $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            $this->line("{$status} - {$name}");

            if (!$result['passed']) {
                foreach ($result['errors'] as $error) {
                    $this->warn("       • {$error}");
                }
            }
        }

        $this->newLine();
        $this->line("Total Tests: " . count($this->results['tests']));
        $this->line("Passed: {$this->results['passed']} ✅");
        $this->line("Failed: {$this->results['failed']} " . ($this->results['failed'] > 0 ? '❌' : ''));
        $this->info('═══════════════════════════════════════════════════════════════');

        if ($this->results['failed'] === 0) {
            $this->info('🎉 All tests passed! Subscription consumption is working correctly.');
        } else {
            $this->warn('⚠️  Some tests failed. Please review the errors above.');
        }
    }

    private function cleanup()
    {
        $this->newLine();
        $this->info('🧹 Cleaning up test data...');

        DB::beginTransaction();
        try {
            $deleted = 0;

            if (isset($this->testData['academic_session'])) {
                $this->testData['academic_session']->forceDelete();
                $deleted++;
            }

            if (isset($this->testData['academic_subscription'])) {
                $this->testData['academic_subscription']->forceDelete();
                $deleted++;
            }

            if (isset($this->testData['academic_lesson'])) {
                $this->testData['academic_lesson']->forceDelete();
                $deleted++;
            }

            if (isset($this->testData['individual_session'])) {
                $this->testData['individual_session']->forceDelete();
                $deleted++;
            }

            if (isset($this->testData['individual_subscription'])) {
                $this->testData['individual_subscription']->forceDelete();
                $deleted++;
            }

            if (isset($this->testData['individual_circle'])) {
                $this->testData['individual_circle']->forceDelete();
                $deleted++;
            }

            DB::commit();
            $this->info("✅ Cleanup completed ({$deleted} records deleted)");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->warn("⚠️  Cleanup failed: {$e->getMessage()}");
        }
    }

    private function cleanupOnly()
    {
        $this->info('Running cleanup only...');

        DB::beginTransaction();
        try {
            $deleted = 0;

            // Clean up any test circles
            $circles = QuranCircle::where('circle_name', 'like', 'TEST-CIRCLE-%')->get();
            foreach ($circles as $circle) {
                QuranSession::where('quran_individual_circle_id', $circle->id)->forceDelete();
                QuranSubscription::where('quran_individual_circle_id', $circle->id)->forceDelete();
                $circle->forceDelete();
                $deleted++;
            }

            // Clean up test academic lessons
            $lessons = AcademicIndividualLesson::where('created_at', '>', now()->subHour())->get();
            foreach ($lessons as $lesson) {
                if ($lesson->sessions()->count() === 1 && $lesson->sessions()->first()->started_at > now()->subHour()) {
                    AcademicSession::where('academic_individual_lesson_id', $lesson->id)->forceDelete();
                    AcademicSubscription::where('academic_individual_lesson_id', $lesson->id)->forceDelete();
                    $lesson->forceDelete();
                    $deleted++;
                }
            }

            DB::commit();
            $this->info("✅ Cleaned up {$deleted} test records");
            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Cleanup failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
