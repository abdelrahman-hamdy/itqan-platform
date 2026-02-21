<?php

use App\Enums\EnrollmentStatus;
use App\Enums\PurchaseSource;
use App\Enums\SessionSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\AcademicTeacherProfile;
use App\Models\CourseSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Models\QuranTeacherProfile;
use App\Models\RecordedCourse;
use App\Models\StudentProfile;
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
        'user_type' => 'student',
    ]);

    StudentProfile::factory()->create([
        'user_id' => $this->student->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create Quran teacher
    $this->quranTeacher = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'quran_teacher',
    ]);

    QuranTeacherProfile::factory()->create([
        'user_id' => $this->quranTeacher->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create Academic teacher
    $this->academicTeacher = User::factory()->create([
        'academy_id' => $this->academy->id,
        'user_type' => 'academic_teacher',
    ]);

    $this->academicTeacherProfile = AcademicTeacherProfile::factory()->create([
        'user_id' => $this->academicTeacher->id,
        'academy_id' => $this->academy->id,
    ]);

    // Authenticate as student with proper Sanctum token (abilities required by API middleware)
    Sanctum::actingAs($this->student, ['*']);
});

test('unpaid quran subscription denies access to sessions', function () {
    // Create unpaid subscription
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    // Create session
    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
    ]);

    // Attempt to access session
    $response = $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    // Should be denied
    $response->assertStatus(403);
    $response->assertJson([
        'error_code' => 'ACCESS_DENIED',
        'reason' => 'payment_required',
    ]);
    $response->assertJsonStructure(['web_url']);

    // Verify access log was created
    expect(SubscriptionAccessLog::count())->toBe(1);
    $log = SubscriptionAccessLog::first();
    expect($log->action)->toBe('access_denied');
    expect($log->user_id)->toBe($this->student->id);
});

test('paused subscription denies access to sessions', function () {
    // Create paused subscription
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
        'status' => SessionSubscriptionStatus::PAUSED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
    ]);

    $response = $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    $response->assertStatus(403);
    $response->assertJson([
        'error_code' => 'ACCESS_DENIED',
        'reason' => 'subscription_paused',
    ]);
});

test('active paid subscription grants access to sessions', function () {
    // Create active paid subscription
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
        'purchase_source' => PurchaseSource::WEB,
    ]);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
    ]);

    $response = $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    // Should be allowed
    $response->assertStatus(200);

    // Verify access log created with success
    $log = SubscriptionAccessLog::where('action', 'access_granted')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->student->id);

    // Verify last_accessed_at was updated
    $subscription->refresh();
    expect($subscription->last_accessed_at)->not->toBeNull();
    expect($subscription->last_accessed_platform)->toBe('mobile');
});

test('academic subscription access control works correctly', function () {
    // Create active paid subscription
    $subscription = AcademicSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'teacher_id' => $this->academicTeacherProfile->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $session = AcademicSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'academic_teacher_id' => $this->academicTeacherProfile->id,
    ]);

    $response = $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/academic/{$session->id}");

    $response->assertStatus(200);

    // Verify subscription was updated
    $subscription->refresh();
    expect($subscription->last_accessed_at)->not->toBeNull();
});

test('recorded course subscription access control', function () {
    // Create course
    $course = RecordedCourse::factory()->create([
        'academy_id' => $this->academy->id,
        'price' => 200,
    ]);

    // Create active subscription
    $subscription = CourseSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'recorded_course_id' => $course->id,
        'status' => EnrollmentStatus::ENROLLED,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $response = $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/courses/recorded/{$course->id}");

    $response->assertStatus(200);
});

test('access logs are created for all access attempts', function () {
    // Create unpaid subscription
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
    ]);

    // First attempt - denied
    $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    expect(SubscriptionAccessLog::count())->toBe(1);

    // Pay subscription
    $subscription->update([
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    // Second attempt - granted
    $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    expect(SubscriptionAccessLog::count())->toBe(2);

    // Verify log entries
    $logs = SubscriptionAccessLog::orderBy('created_at')->get();
    expect($logs[0]->action)->toBe('access_denied');
    expect($logs[1]->action)->toBe('access_granted');
});

test('platform detection works correctly', function () {
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
        'status' => SessionSubscriptionStatus::ACTIVE,
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
    ]);

    // Access from mobile
    $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->withHeader('X-Platform', 'mobile')
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    $subscription->refresh();
    expect($subscription->last_accessed_platform)->toBe('mobile');

    // Access from web (explicit web platform header)
    $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->withHeader('X-Platform', 'web')
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    $subscription->refresh();
    expect($subscription->last_accessed_platform)->toBe('web');
});

test('access denial includes correct web purchase URL', function () {
    $subscription = QuranSubscription::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
        'status' => SessionSubscriptionStatus::PENDING,
        'payment_status' => SubscriptionPaymentStatus::PENDING,
    ]);

    $session = QuranSession::factory()->create([
        'academy_id' => $this->academy->id,
        'student_id' => $this->student->id,
        'quran_teacher_id' => $this->quranTeacher->id,
    ]);

    $response = $this->withHeader('X-Academy-Subdomain', $this->academy->subdomain)
        ->getJson("/api/v1/student/sessions/quran/{$session->id}");

    $response->assertStatus(403);
    $webUrl = $response->json('web_url');
    expect($webUrl)->toContain('mobile-purchase');
    expect($webUrl)->toContain('quran_teacher');
});
