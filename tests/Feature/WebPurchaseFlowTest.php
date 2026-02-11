<?php

use App\Enums\PurchaseSource;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Academy;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\Student;
use App\Models\SubscriptionAccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create academy
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
    ]);

    // Create student
    $this->student = User::factory()->create([
        'academy_id' => $this->academy->id,
        'role' => 'student',
    ]);

    Student::factory()->create([
        'user_id' => $this->student->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create teacher
    $this->teacher = User::factory()->create([
        'academy_id' => $this->academy->id,
        'role' => 'teacher',
    ]);

    QuranTeacherProfile::factory()->create([
        'user_id' => $this->teacher->id,
        'academy_id' => $this->academy->id,
    ]);
});

test('purchase URL generation returns valid token and URL', function () {
    // Create Quran subscription pending payment
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    // Request purchase URL
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/student/purchase-url/quran_teacher/{$this->teacher->id}");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'web_url',
        'expires_at',
        'instructions',
    ]);

    $webUrl = $response->json('web_url');
    expect($webUrl)->toContain('mobile-purchase');
    expect($webUrl)->toContain('token=');

    // Verify purchase attempt was logged
    $log = SubscriptionAccessLog::where('action', 'purchase_attempted')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->student->id);
});

test('purchase URL token expires after 1 hour', function () {
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/student/purchase-url/quran_teacher/{$this->teacher->id}");

    $response->assertStatus(200);
    $expiresAt = $response->json('expires_at');

    // Parse expiry time
    $expiryTime = \Carbon\Carbon::parse($expiresAt);
    $expectedExpiry = now()->addHour();

    // Should expire approximately 1 hour from now (within 1 minute tolerance)
    expect($expiryTime->diffInMinutes($expectedExpiry))->toBeLessThan(1);
});

test('existing active subscription prevents duplicate purchase URL', function () {
    // Create active paid subscription
    QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    // Attempt to get purchase URL again
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/student/purchase-url/quran_teacher/{$this->teacher->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Already subscribed',
    ]);
});

test('mobile purchase redirect validates token', function () {
    // Create token with web-purchase ability
    $token = $this->student->createToken('mobile-purchase', ['web-purchase'], now()->addHour());

    // Access web purchase redirect
    $response = $this->get("/mobile-purchase/quran_teacher/{$this->teacher->id}?token={$token->plainTextToken}");

    // Should redirect to appropriate checkout page (not 403)
    expect($response->status())->not->toBe(403);
});

test('mobile purchase redirect rejects invalid token', function () {
    // Access with invalid token
    $response = $this->get("/mobile-purchase/quran_teacher/{$this->teacher->id}?token=invalid-token");

    // Should redirect to login with error
    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('mobile purchase redirect rejects token without web-purchase ability', function () {
    // Create token without proper ability
    $token = $this->student->createToken('general', ['read'], now()->addHour());

    $response = $this->get("/mobile-purchase/quran_teacher/{$this->teacher->id}?token={$token->plainTextToken}");

    // Should redirect with error
    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('mobile purchase redirect sets session flags', function () {
    $token = $this->student->createToken('mobile-purchase', ['web-purchase'], now()->addHour());

    $response = $this->get("/mobile-purchase/quran_teacher/{$this->teacher->id}?token={$token->plainTextToken}");

    // Should set purchase_source in session
    expect(session('purchase_source'))->toBe('mobile');
    expect(session('mobile_user_id'))->toBe($this->student->id);
});

test('payment success page detects mobile purchases', function () {
    // Create subscription
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    // Create completed payment
    $payment = Payment::factory()->create([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => QuranSubscription::class,
        'payable_id' => $subscription->id,
        'status' => 'completed',
    ]);

    // Set mobile session flag
    session(['purchase_source' => 'mobile']);

    // View success page
    $response = $this->actingAs($this->student)
        ->get(route('payments.success', ['payment' => $payment->id]));

    // Should use mobile success view
    $response->assertViewIs('payments.mobile-success');
    $response->assertViewHas('deeplink_url');
    $response->assertViewHas('auto_redirect_seconds', 5);
});

test('payment completion confirms subscription', function () {
    // Create subscription
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->teacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'purchase_source' => PurchaseSource::WEB,
    ]);

    // Call purchase completed endpoint
    $response = $this->actingAs($this->student, 'sanctum')
        ->postJson('/api/v1/student/purchase-completed', [
            'subscription_id' => $subscription->id,
        ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'message',
        'subscription' => [
            'id',
            'status',
            'payment_status',
        ],
    ]);

    // Verify last_accessed_at was updated
    $subscription->refresh();
    expect($subscription->last_accessed_at)->not->toBeNull();
    expect($subscription->last_accessed_platform)->toBe('mobile');
});

test('complete purchase flow updates subscription purchase_source', function () {
    // Create course for purchase
    $course = RecordedCourse::factory()->create([
        'academy_id' => $this->academy->id,
        'price' => 150,
        'is_free' => false,
    ]);

    // Simulate mobile session
    session(['purchase_source' => 'mobile']);

    // Create payment with Payment::createPayment() to trigger metadata tracking
    $payment = Payment::createPayment([
        'academy_id' => $this->academy->id,
        'user_id' => $this->student->id,
        'payable_type' => get_class($course),
        'payable_id' => $course->id,
        'amount' => 150,
        'currency' => 'SAR',
        'status' => 'pending',
        'payment_gateway' => 'paymob',
        'payment_method' => 'card',
    ]);

    // Verify payment has metadata with purchase_source
    $metadata = is_string($payment->metadata) ? json_decode($payment->metadata, true) : $payment->metadata;
    expect($metadata['purchase_source'])->toBe('mobile');

    // Create subscription
    $subscription = CourseSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'recorded_course_id' => $course->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    // Update payment to link to subscription
    $payment->update([
        'payable_type' => get_class($subscription),
        'payable_id' => $subscription->id,
    ]);

    // Mark payment as completed (should update subscription's purchase_source)
    $payment->markAsCompleted([
        'transaction_id' => 'TEST-TXN-123',
    ]);

    // Verify subscription's purchase_source was updated
    $subscription->refresh();
    expect($subscription->purchase_source)->toBe(PurchaseSource::WEB);
});

test('recorded course purchase flow', function () {
    $course = RecordedCourse::factory()->create([
        'academy_id' => $this->academy->id,
        'price' => 200,
        'is_free' => false,
    ]);

    // Get purchase URL
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/student/purchase-url/course/{$course->id}");

    $response->assertStatus(200);
    $webUrl = $response->json('web_url');
    expect($webUrl)->toContain('course');
    expect($webUrl)->toContain($course->id);
});

test('academic teacher subscription purchase flow', function () {
    $academicTeacher = User::factory()->create([
        'academy_id' => $this->academy->id,
        'role' => 'teacher',
    ]);

    AcademicTeacherProfile::factory()->create([
        'user_id' => $academicTeacher->id,
        'academy_id' => $this->academy->id,
    ]);

    // Get purchase URL
    $response = $this->actingAs($this->student, 'sanctum')
        ->getJson("/api/v1/student/purchase-url/academic_teacher/{$academicTeacher->id}");

    $response->assertStatus(200);
    $webUrl = $response->json('web_url');
    expect($webUrl)->toContain('academic_teacher');
});
