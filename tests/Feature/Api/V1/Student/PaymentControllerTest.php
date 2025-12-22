<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    $this->gradeLevel = AcademicGradeLevel::create([
        'academy_id' => $this->academy->id,
        'name' => 'Grade 1',
        'is_active' => true,
    ]);

    $this->student = User::factory()
        ->student()
        ->forAcademy($this->academy)
        ->create();

    $this->teacher = User::factory()
        ->quranTeacher()
        ->forAcademy($this->academy)
        ->create();

    $this->student->refresh();
});

describe('Payment Index', function () {
    it('returns all payments for student', function () {
        Sanctum::actingAs($this->student, ['*']);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 500,
            'currency' => 'SAR',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/student/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payments',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'total_pages',
                        'has_more',
                    ],
                    'summary' => [
                        'total_paid',
                        'total_pending',
                    ],
                ],
            ]);
    });

    it('filters payments by status', function () {
        Sanctum::actingAs($this->student, ['*']);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 500,
            'status' => 'completed',
        ]);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 300,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/student/payments?status=completed', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $payments = $response->json('data.payments');
        foreach ($payments as $payment) {
            expect($payment['status'])->toBe('completed');
        }
    });

    it('paginates payment results', function () {
        Sanctum::actingAs($this->student, ['*']);

        Payment::factory()->count(25)->create([
            'user_id' => $this->student->id,
        ]);

        $response = $this->getJson('/api/v1/student/payments?per_page=10&page=1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonPath('data.pagination.current_page', 1);

        $payments = $response->json('data.payments');
        expect(count($payments))->toBe(10);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Payment', function () {
    it('returns payment details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $this->student->id,
            'subscription_id' => $subscription->id,
            'subscription_type' => get_class($subscription),
            'amount' => 500,
            'currency' => 'SAR',
            'status' => 'completed',
            'payment_number' => 'PAY-2025-001',
            'transaction_id' => 'TXN-12345',
        ]);

        $response = $this->getJson("/api/v1/student/payments/{$payment->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment' => [
                        'id',
                        'payment_number',
                        'amount',
                        'currency',
                        'status',
                        'payment_method',
                        'transaction_id',
                        'created_at',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent payment', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/payments/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents accessing another student payment', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        $payment = Payment::factory()->create([
            'user_id' => $otherStudent->id,
            'amount' => 500,
            'status' => 'completed',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson("/api/v1/student/payments/{$payment->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Payment Receipt', function () {
    it('returns receipt for completed payment', function () {
        Sanctum::actingAs($this->student, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 500,
            'currency' => 'SAR',
            'status' => 'completed',
            'payment_number' => 'PAY-2025-001',
            'paid_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/student/payments/{$payment->id}/receipt", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'receipt' => [
                        'receipt_number',
                        'payment_number',
                        'date',
                        'amount',
                        'currency',
                        'customer',
                        'issuer',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-completed payment', function () {
        Sanctum::actingAs($this->student, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 500,
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/student/payments/{$payment->id}/receipt", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('includes academy information in receipt', function () {
        Sanctum::actingAs($this->student, ['*']);

        $payment = Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 500,
            'currency' => 'SAR',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/student/payments/{$payment->id}/receipt", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.receipt.issuer.name', $this->academy->name);
    });

    it('prevents accessing another student receipt', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        $payment = Payment::factory()->create([
            'user_id' => $otherStudent->id,
            'amount' => 500,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson("/api/v1/student/payments/{$payment->id}/receipt", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Payment Summary', function () {
    it('calculates total paid correctly', function () {
        Sanctum::actingAs($this->student, ['*']);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 500,
            'status' => 'completed',
        ]);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 300,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/student/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_paid', 800);
    });

    it('calculates total pending correctly', function () {
        Sanctum::actingAs($this->student, ['*']);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 200,
            'status' => 'pending',
        ]);

        Payment::factory()->create([
            'user_id' => $this->student->id,
            'amount' => 150,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/student/payments', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_pending', 350);
    });
});
