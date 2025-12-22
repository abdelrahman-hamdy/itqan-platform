<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSubscription;
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

describe('Subscription Index', function () {
    it('returns all subscriptions for student', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/student/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'subscriptions',
                    'total',
                ],
            ]);
    });

    it('filters subscriptions by status', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'expired',
        ]);

        $response = $this->getJson('/api/v1/student/subscriptions?status=active', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $subscriptions = $response->json('data.subscriptions');
        foreach ($subscriptions as $subscription) {
            if ($subscription['type'] === 'quran') {
                expect($subscription['status'])->toBe('active');
            }
        }
    });

    it('filters subscriptions by type', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/student/subscriptions?type=quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $subscriptions = $response->json('data.subscriptions');
        foreach ($subscriptions as $subscription) {
            expect($subscription['type'])->toBe('quran');
        }
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/subscriptions', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Subscription', function () {
    it('returns quran subscription details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/student/subscriptions/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'subscription' => [
                        'id',
                        'type',
                        'subscription_code',
                        'title',
                        'status',
                        'start_date',
                        'end_date',
                        'auto_renew',
                        'price',
                        'teacher',
                        'sessions',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/subscriptions/quran/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents accessing another student subscription', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson("/api/v1/student/subscriptions/quran/{$subscription->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Subscription Sessions', function () {
    it('returns sessions for a subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/student/subscriptions/quran/{$subscription->id}/sessions", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sessions',
                    'total',
                ],
            ]);
    });
});

describe('Toggle Auto Renew', function () {
    it('enables auto-renewal for subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
            'auto_renew' => false,
        ]);

        $response = $this->patchJson("/api/v1/student/subscriptions/quran/{$subscription->id}/toggle-auto-renew", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.auto_renew', true);

        $subscription->refresh();
        expect($subscription->auto_renew)->toBeTrue();
    });

    it('disables auto-renewal for subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $response = $this->patchJson("/api/v1/student/subscriptions/quran/{$subscription->id}/toggle-auto-renew", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.auto_renew', false);

        $subscription->refresh();
        expect($subscription->auto_renew)->toBeFalse();
    });

    it('prevents toggling auto-renew for cancelled subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'cancelled',
        ]);

        $response = $this->patchJson("/api/v1/student/subscriptions/quran/{$subscription->id}/toggle-auto-renew", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Cancel Subscription', function () {
    it('allows cancelling active subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $response = $this->patchJson("/api/v1/student/subscriptions/quran/{$subscription->id}/cancel", [
            'reason' => 'No longer needed',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.cancelled', true);

        $subscription->refresh();
        expect($subscription->status->value)->toBe('cancelled');
        expect($subscription->cancellation_reason)->toBe('No longer needed');
    });

    it('prevents cancelling already cancelled subscription', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'cancelled',
        ]);

        $response = $this->patchJson("/api/v1/student/subscriptions/quran/{$subscription->id}/cancel", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('prevents cancelling another student subscription', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        $subscription = QuranSubscription::factory()->create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->patchJson("/api/v1/student/subscriptions/quran/{$subscription->id}/cancel", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});
