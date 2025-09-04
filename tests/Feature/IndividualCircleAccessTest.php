<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\QuranPackage;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndividualCircleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Academy $academy;

    private User $teacher;

    private User $student;

    private User $otherStudent;

    private QuranPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        // Create academy
        $this->academy = Academy::factory()->create([
            'subdomain' => 'test-academy',
        ]);

        // Create teacher
        $this->teacher = User::factory()->create([
            'user_type' => 'quran_teacher',
            'academy_id' => $this->academy->id,
            'email' => 'teacher@test.com',
        ]);

        // Create students
        $this->student = User::factory()->create([
            'user_type' => 'student',
            'academy_id' => $this->academy->id,
            'email' => 'student@test.com',
        ]);

        $this->otherStudent = User::factory()->create([
            'user_type' => 'student',
            'academy_id' => $this->academy->id,
            'email' => 'other@test.com',
        ]);

        // Create package
        $this->package = QuranPackage::factory()->create([
            'academy_id' => $this->academy->id,
            'total_sessions' => 8,
        ]);
    }

    /** @test */
    public function teacher_can_access_their_individual_circle()
    {
        // Create subscription and circle
        $subscription = $this->createSubscription($this->student, $this->teacher);
        $circle = $subscription->individualCircle;

        $response = $this->actingAs($this->teacher)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertStatus(200);
        $response->assertViewIs('teacher.individual-circles.show');
        $response->assertViewHas('circle');
    }

    /** @test */
    public function student_can_access_their_individual_circle()
    {
        // Create subscription and circle
        $subscription = $this->createSubscription($this->student, $this->teacher);
        $circle = $subscription->individualCircle;

        $response = $this->actingAs($this->student)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertStatus(200);
        $response->assertViewIs('student.individual-circles.show');
        $response->assertViewHas('individualCircle');
    }

    /** @test */
    public function teacher_cannot_access_other_teachers_circles()
    {
        // Create another teacher
        $otherTeacher = User::factory()->create([
            'user_type' => 'quran_teacher',
            'academy_id' => $this->academy->id,
        ]);

        // Create subscription with other teacher
        $subscription = $this->createSubscription($this->student, $otherTeacher);
        $circle = $subscription->individualCircle;

        $response = $this->actingAs($this->teacher)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_access_other_students_circles()
    {
        // Create subscription for other student
        $subscription = $this->createSubscription($this->otherStudent, $this->teacher);
        $circle = $subscription->individualCircle;

        $response = $this->actingAs($this->student)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function prevents_duplicate_active_individual_subscriptions()
    {
        // Create first subscription
        $this->createSubscription($this->student, $this->teacher, 'active');

        // Attempt to create second subscription with same student-teacher pair
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('لديك اشتراك فردي نشط بالفعل مع هذا المعلم');

        $this->createSubscription($this->student, $this->teacher, 'active');
    }

    /** @test */
    public function allows_multiple_pending_subscriptions_but_only_one_active()
    {
        // Create pending subscription
        $pendingSubscription = $this->createSubscription($this->student, $this->teacher, 'pending');

        // Can create another pending subscription
        $anotherPendingSubscription = $this->createSubscription($this->student, $this->teacher, 'pending');

        $this->assertDatabaseCount('quran_subscriptions', 2);

        // But activating one should prevent activating another
        $pendingSubscription->update(['subscription_status' => 'active']);

        $this->expectException(\Exception::class);
        $anotherPendingSubscription->update(['subscription_status' => 'active']);
    }

    /** @test */
    public function guest_users_cannot_access_individual_circles()
    {
        $subscription = $this->createSubscription($this->student, $this->teacher);
        $circle = $subscription->individualCircle;

        $response = $this->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_users_cannot_access_individual_circles()
    {
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'academy_id' => $this->academy->id,
        ]);

        $subscription = $this->createSubscription($this->student, $this->teacher);
        $circle = $subscription->individualCircle;

        $response = $this->actingAs($admin)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function circle_shows_correct_user_role_and_permissions()
    {
        $subscription = $this->createSubscription($this->student, $this->teacher);
        $circle = $subscription->individualCircle;

        // Test teacher view
        $response = $this->actingAs($this->teacher)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertViewHas('userRole', 'teacher');
        $response->assertViewHas('isTeacher', true);
        $response->assertViewHas('isStudent', false);

        // Test student view
        $response = $this->actingAs($this->student)
            ->get(route('individual-circles.show', ['circle' => $circle->id]));

        $response->assertViewHas('userRole', 'student');
        $response->assertViewHas('isTeacher', false);
        $response->assertViewHas('isStudent', true);
    }

    /** @test */
    public function returns_404_for_nonexistent_circle()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('individual-circles.show', ['circle' => 999]));

        $response->assertStatus(404);
    }

    /** @test */
    public function academy_scoping_prevents_cross_academy_access()
    {
        // Create different academy
        $otherAcademy = Academy::factory()->create(['subdomain' => 'other-academy']);

        // Create user in other academy
        $otherAcademyTeacher = User::factory()->create([
            'user_type' => 'quran_teacher',
            'academy_id' => $otherAcademy->id,
        ]);

        $otherAcademyStudent = User::factory()->create([
            'user_type' => 'student',
            'academy_id' => $otherAcademy->id,
        ]);

        // Create package in other academy
        $otherPackage = QuranPackage::factory()->create([
            'academy_id' => $otherAcademy->id,
        ]);

        // Create subscription in other academy
        $otherSubscription = QuranSubscription::create([
            'academy_id' => $otherAcademy->id,
            'student_id' => $otherAcademyStudent->id,
            'quran_teacher_id' => $otherAcademyTeacher->id,
            'package_id' => $otherPackage->id,
            'subscription_type' => 'individual',
            'subscription_status' => 'active',
            'total_sessions' => 8,
            'created_by' => $otherAcademyStudent->id,
        ]);

        $otherCircle = $otherSubscription->individualCircle;

        // User from first academy should not access circle from other academy
        $response = $this->actingAs($this->teacher)
            ->get(route('individual-circles.show', ['circle' => $otherCircle->id]));

        $response->assertStatus(404);
    }

    private function createSubscription(User $student, User $teacher, string $status = 'active'): QuranSubscription
    {
        return QuranSubscription::create([
            'academy_id' => $this->academy->id,
            'student_id' => $student->id,
            'quran_teacher_id' => $teacher->id,
            'package_id' => $this->package->id,
            'subscription_type' => 'individual',
            'subscription_status' => $status,
            'total_sessions' => 8,
            'created_by' => $student->id,
        ]);
    }
}
