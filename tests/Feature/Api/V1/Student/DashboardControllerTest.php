<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);

    // Create a grade level for the academy
    $this->gradeLevel = AcademicGradeLevel::create([
        'academy_id' => $this->academy->id,
        'name' => 'Grade 1',
        'is_active' => true,
    ]);

    $this->student = User::factory()
        ->student()
        ->forAcademy($this->academy)
        ->create();

    // Ensure student profile exists
    $this->student->refresh();
});

describe('Dashboard Controller', function () {
    it('returns dashboard data for authenticated student', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'student' => [
                        'id',
                        'name',
                        'student_code',
                        'avatar',
                        'grade_level',
                    ],
                    'stats' => [
                        'today_sessions',
                        'upcoming_sessions',
                        'active_subscriptions',
                        'pending_homework',
                        'pending_quizzes',
                        'unread_notifications',
                    ],
                    'today_sessions',
                    'upcoming_sessions',
                ],
                'message',
            ]);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });

    it('requires student role', function () {
        $teacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create();

        Sanctum::actingAs($teacher, ['*']);

        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(403);
    });

    it('returns error when student profile not found', function () {
        $user = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        // Delete the student profile
        $user->studentProfile()->delete();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'code' => 'STUDENT_PROFILE_NOT_FOUND',
            ]);
    });

    it('includes today sessions in dashboard', function () {
        Sanctum::actingAs($this->student, ['*']);

        // Create a Quran session for today
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.today_sessions', 1);
    });

    it('includes upcoming sessions in dashboard', function () {
        Sanctum::actingAs($this->student, ['*']);

        // Create a Quran session for tomorrow
        $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.upcoming_sessions', 1);
    });

    it('returns zero counts when no data exists', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/dashboard', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.today_sessions', 0)
            ->assertJsonPath('data.stats.upcoming_sessions', 0)
            ->assertJsonPath('data.stats.active_subscriptions', 0)
            ->assertJsonPath('data.stats.pending_homework', 0)
            ->assertJsonPath('data.stats.pending_quizzes', 0);
    });
});
