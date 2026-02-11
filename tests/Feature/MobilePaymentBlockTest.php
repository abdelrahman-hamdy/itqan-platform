<?php

use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Payment;
use App\Models\RecordedCourse;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create academy
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
    ]);

    // Create student user
    $this->student = User::factory()->create([
        'academy_id' => $this->academy->id,
        'role' => 'student',
    ]);

    Student::factory()->create([
        'user_id' => $this->student->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create teacher for academic subscription
    $this->teacher = User::factory()->create([
        'academy_id' => $this->academy->id,
        'role' => 'teacher',
    ]);

    AcademicTeacherProfile::factory()->create([
        'user_id' => $this->teacher->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create a subscription for payment testing
    $this->subscription = AcademicSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'academic_teacher_id' => $this->teacher->id,
        'payment_status' => 'pending',
    ]);
});

test('mobile API blocks POST requests to payment endpoints', function () {
    // Create payment
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => AcademicSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => 'pending',
    ]);

    // Attempt to process payment from mobile API
    $response = $this->actingAs($this->student, 'sanctum')
        ->withHeader('X-Platform', 'mobile')
        ->postJson('/api/v1/student/payments', [
            'payment_id' => $payment->id,
            'payment_method' => 'card',
        ]);

    // Should be blocked with 403
    $response->assertStatus(403);
    $response->assertJson([
        'error_code' => 'MOBILE_PAYMENT_BLOCKED',
    ]);
    $response->assertJsonStructure([
        'message',
        'error_code',
        'web_url',
    ]);
});

test('mobile API allows GET requests to payment history', function () {
    // Create completed payment
    Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => AcademicSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => 'completed',
    ]);

    // Attempt to view payment history from mobile API
    $response = $this->actingAs($this->student, 'sanctum')
        ->withHeader('X-Platform', 'mobile')
        ->getJson('/api/v1/student/payments');

    // Should be allowed
    $response->assertStatus(200);
});

test('mobile API blocks payment creation via User-Agent detection', function () {
    // Create payment
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => AcademicSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => 'pending',
    ]);

    // Attempt to process payment with mobile User-Agent
    $response = $this->actingAs($this->student, 'sanctum')
        ->withHeader('User-Agent', 'ItqanMobileApp/1.0 (iOS)')
        ->postJson('/api/v1/student/payments', [
            'payment_id' => $payment->id,
            'payment_method' => 'card',
        ]);

    // Should be blocked
    $response->assertStatus(403);
    $response->assertJson([
        'error_code' => 'MOBILE_PAYMENT_BLOCKED',
    ]);
});

test('web API allows all payment operations', function () {
    // Create recorded course for payment
    $course = RecordedCourse::factory()->create([
        'academy_id' => $this->academy->id,
        'price' => 100,
        'is_free' => false,
    ]);

    // Create payment
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => get_class($course),
        'payable_id' => $course->id,
        'amount' => 100,
        'status' => 'pending',
    ]);

    // Web requests should work normally (no X-Platform header)
    $response = $this->actingAs($this->student)
        ->postJson("/api/v1/student/payments/{$payment->id}/process", [
            'payment_method' => 'card',
        ]);

    // Should not be blocked (might fail for other reasons, but not 403)
    $response->assertStatus(fn ($status) => $status !== 403);
});

test('blocked payment returns correct web purchase URL structure', function () {
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => AcademicSubscription::class,
        'payable_id' => $this->subscription->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->student, 'sanctum')
        ->withHeader('X-Platform', 'mobile')
        ->postJson('/api/v1/student/payments', [
            'payment_id' => $payment->id,
        ]);

    $response->assertStatus(403);
    $response->assertJsonStructure([
        'message',
        'error_code',
        'web_url',
    ]);

    // Verify web_url contains expected elements
    $webUrl = $response->json('web_url');
    expect($webUrl)->toContain('mobile-purchase');
});
