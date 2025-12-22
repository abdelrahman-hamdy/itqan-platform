<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\HomeworkSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

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
        ->academicTeacher()
        ->forAcademy($this->academy)
        ->create();

    $this->student->refresh();
});

describe('Homework Index', function () {
    it('returns all homework assignments for student', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises 1-10',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/student/homework', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'homework',
                    'total',
                    'stats' => [
                        'pending',
                        'submitted',
                        'graded',
                        'overdue',
                    ],
                ],
            ]);
    });

    it('filters homework by status', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Pending homework',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/student/homework?status=pending', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/homework', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Homework', function () {
    it('returns homework details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises 1-10',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/v1/student/homework/academic/{$session->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'homework' => [
                        'id',
                        'type',
                        'session_id',
                        'title',
                        'subject',
                        'description',
                        'due_date',
                        'is_overdue',
                        'status',
                        'can_submit',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent homework', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/homework/academic/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('rejects invalid homework type', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/homework/quran/1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'INVALID_TYPE',
            ]);
    });
});

describe('Submit Homework', function () {
    it('allows submitting homework with content', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises 1-10',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/submit", [
            'content' => 'My homework solution',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('homework_submissions', [
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'content' => 'My homework solution',
            'status' => 'submitted',
        ]);
    });

    it('allows submitting homework with file attachments', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Upload your work',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $file = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/submit", [
            'content' => 'See attached file',
            'attachments' => [$file],
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(201);
    });

    it('requires either content or attachments', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/submit", [], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(422);
    });

    it('prevents duplicate submission', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        HomeworkSubmission::create([
            'session_id' => $session->id,
            'session_type' => AcademicSession::class,
            'student_id' => $this->student->id,
            'content' => 'Already submitted',
            'status' => 'submitted',
        ]);

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/submit", [
            'content' => 'Another submission',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'ALREADY_SUBMITTED',
            ]);
    });

    it('prevents submission after due date', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises',
            'homework_due_date' => now()->subDay(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/submit", [
            'content' => 'Late submission',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'HOMEWORK_OVERDUE',
            ]);
    });
});

describe('Save Draft', function () {
    it('allows saving homework draft', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/draft", [
            'content' => 'Work in progress',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('homework_submissions', [
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'content' => 'Work in progress',
            'status' => 'draft',
        ]);
    });

    it('updates existing draft', function () {
        Sanctum::actingAs($this->student, ['*']);

        $subscription = AcademicSubscription::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'status' => 'active',
        ]);

        $session = AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academic_subscription_id' => $subscription->id,
            'academy_id' => $this->academy->id,
            'homework' => 'Complete exercises',
            'homework_due_date' => now()->addWeek(),
            'status' => 'completed',
        ]);

        // Create initial draft
        HomeworkSubmission::create([
            'session_id' => $session->id,
            'session_type' => AcademicSession::class,
            'student_id' => $this->student->id,
            'content' => 'First draft',
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/v1/student/homework/academic/{$session->id}/draft", [
            'content' => 'Updated draft',
        ], [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('homework_submissions', [
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'content' => 'Updated draft',
            'status' => 'draft',
        ]);

        // Ensure only one draft exists
        $draftCount = HomeworkSubmission::where('session_id', $session->id)
            ->where('student_id', $this->student->id)
            ->where('status', 'draft')
            ->count();

        expect($draftCount)->toBe(1);
    });
});
