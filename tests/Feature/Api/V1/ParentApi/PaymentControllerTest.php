<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubscription;
use App\Models\ParentProfile;
use App\Models\ParentStudentRelationship;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\StudentProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'parent-api', 'payments');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    // Create parent user with profile
    $this->parentUser = User::factory()->parent()->forAcademy($this->academy)->create();
    $this->parentProfile = ParentProfile::factory()->create([
        'user_id' => $this->parentUser->id,
        'academy_id' => $this->academy->id,
    ]);

    // Create student with user
    $this->studentUser = User::factory()->student()->forAcademy($this->academy)->create();
    $this->gradeLevel = AcademicGradeLevel::factory()->create([
        'academy_id' => $this->academy->id,
    ]);
    $this->student = StudentProfile::factory()->create([
        'user_id' => $this->studentUser->id,
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    // Link student to parent
    ParentStudentRelationship::create([
        'parent_id' => $this->parentProfile->id,
        'student_id' => $this->student->id,
        'relationship_type' => 'father',
    ]);
});

describe('index (list all payments)', function () {
    it('returns empty list when no payments exist', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payments',
                    'pagination',
                ],
            ])
            ->assertJson([
                'data' => [
                    'payments' => [],
                ],
            ]);
    });

    it('returns payments for linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'amount' => 500.00,
            'currency' => 'SAR',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/parent/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.payments')
            ->assertJsonStructure([
                'data' => [
                    'payments' => [
                        '*' => [
                            'id',
                            'child_name',
                            'amount',
                            'currency',
                            'status',
                            'payment_method',
                            'transaction_id',
                            'description',
                            'payable_type',
                            'payable_id',
                            'paid_at',
                            'created_at',
                        ],
                    ],
                ],
            ]);
    });

    it('filters payments by child_id', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        // Create another child
        $student2User = User::factory()->student()->forAcademy($this->academy)->create();
        $student2 = StudentProfile::factory()->create([
            'user_id' => $student2User->id,
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        ParentStudentRelationship::create([
            'parent_id' => $this->parentProfile->id,
            'student_id' => $student2->id,
            'relationship_type' => 'mother',
        ]);

        Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        Payment::factory()->create([
            'user_id' => $student2User->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/payments?child_id=' . $this->student->id, [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.payments');
    });

    it('filters payments by status', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'completed',
        ]);

        Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/parent/payments?status=completed', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.payments');
    });

    it('filters payments by date range', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'created_at' => now()->subDays(10),
        ]);

        Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'created_at' => now()->subDay(),
        ]);

        $fromDate = now()->subDays(5)->toDateString();

        $response = $this->getJson("/api/v1/parent/payments?from_date={$fromDate}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.payments');
    });

    it('paginates results', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        Payment::factory()->count(20)->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/payments?per_page=10', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.payments')
            ->assertJsonStructure([
                'data' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'total_pages',
                        'has_more',
                    ],
                ],
            ]);
    });

    it('does not show payments of non-linked children', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();

        Payment::factory()->create([
            'user_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson('/api/v1/parent/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.payments');
    });
});

describe('show (get specific payment)', function () {
    it('returns payment details for linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'amount' => 750.00,
            'currency' => 'SAR',
            'status' => 'completed',
            'payment_method' => 'credit_card',
        ]);

        $response = $this->getJson("/api/v1/parent/payments/{$payment->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'payment' => [
                        'id',
                        'child_name',
                        'amount',
                        'currency',
                        'status',
                        'payment_method',
                        'gateway',
                        'transaction_id',
                        'gateway_reference',
                        'description',
                        'payable_type',
                        'payable_id',
                        'payable_details',
                        'metadata',
                        'paid_at',
                        'created_at',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'amount' => 750.00,
                        'status' => 'completed',
                    ],
                ],
            ]);
    });

    it('returns 404 for payment of non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudentUser = User::factory()->student()->forAcademy($this->academy)->create();
        $payment = Payment::factory()->create([
            'user_id' => $otherStudentUser->id,
            'academy_id' => $this->academy->id,
        ]);

        $response = $this->getJson("/api/v1/parent/payments/{$payment->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for non-existent payment', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->getJson('/api/v1/parent/payments/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('initiate (initiate payment)', function () {
    it('validates required fields', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/payments/initiate', [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'child_id',
                'subscription_type',
                'subscription_id',
                'payment_method',
            ]);
    });

    it('validates subscription type enum', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/payments/initiate', [
            'child_id' => $this->student->id,
            'subscription_type' => 'invalid_type',
            'subscription_id' => 1,
            'payment_method' => 'credit_card',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subscription_type']);
    });

    it('initiates payment for Quran subscription', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'payment_status' => 'pending',
            'price' => 1000.00,
        ]);

        $response = $this->postJson('/api/v1/parent/payments/initiate', [
            'child_id' => $this->student->id,
            'subscription_type' => 'quran',
            'subscription_id' => $subscription->id,
            'payment_method' => 'credit_card',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'payment' => [
                        'subscription_type',
                        'subscription_id',
                        'amount',
                        'currency',
                        'payment_method',
                        'status',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-linked child', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $otherStudent = StudentProfile::factory()->create([
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $response = $this->postJson('/api/v1/parent/payments/initiate', [
            'child_id' => $otherStudent->id,
            'subscription_type' => 'quran',
            'subscription_id' => 1,
            'payment_method' => 'credit_card',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error_code' => 'CHILD_NOT_FOUND',
            ]);
    });

    it('returns 404 for non-existent subscription', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $response = $this->postJson('/api/v1/parent/payments/initiate', [
            'child_id' => $this->student->id,
            'subscription_type' => 'quran',
            'subscription_id' => 99999,
            'payment_method' => 'credit_card',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns error when subscription is already paid', function () {
        Sanctum::actingAs($this->parentUser, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->studentUser->id,
            'academy_id' => $this->academy->id,
            'payment_status' => 'paid',
        ]);

        $response = $this->postJson('/api/v1/parent/payments/initiate', [
            'child_id' => $this->student->id,
            'subscription_type' => 'quran',
            'subscription_id' => $subscription->id,
            'payment_method' => 'credit_card',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error_code' => 'ALREADY_PAID',
            ]);
    });
});

describe('authorization', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/parent/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when parent has no profile', function () {
        $userWithoutProfile = User::factory()->parent()->forAcademy($this->academy)->create();
        Sanctum::actingAs($userWithoutProfile, ['*']);

        $response = $this->getJson('/api/v1/parent/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error_code' => 'PARENT_PROFILE_NOT_FOUND',
            ]);
    });
});
