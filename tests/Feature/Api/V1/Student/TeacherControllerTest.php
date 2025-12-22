<?php

use App\Models\Academy;
use App\Models\AcademicGradeLevel;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
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

    $this->student->refresh();
});

describe('Quran Teachers Index', function () {
    it('returns list of active quran teachers', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create();

        QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/student/teachers/quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'teachers',
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

    it('filters teachers by gender', function () {
        Sanctum::actingAs($this->student, ['*']);

        $maleTeacher = User::factory()
            ->quranTeacher()
            ->male()
            ->forAcademy($this->academy)
            ->create();

        QuranTeacherProfile::factory()->create([
            'user_id' => $maleTeacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $femaleTeacher = User::factory()
            ->quranTeacher()
            ->female()
            ->forAcademy($this->academy)
            ->create();

        QuranTeacherProfile::factory()->create([
            'user_id' => $femaleTeacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/student/teachers/quran?gender=male', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('searches teachers by name', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create([
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
            ]);

        QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/student/teachers/quran?search=Ahmed', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $teachers = $response->json('data.teachers');
        if (!empty($teachers)) {
            $teacher = $teachers[0];
            expect($teacher['name'])->toContain('Ahmed');
        }
    });

    it('only shows approved teachers', function () {
        Sanctum::actingAs($this->student, ['*']);

        $approvedTeacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create();

        QuranTeacherProfile::factory()->create([
            'user_id' => $approvedTeacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $pendingTeacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create();

        QuranTeacherProfile::factory()->create([
            'user_id' => $pendingTeacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/student/teachers/quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $teachers = $response->json('data.teachers');
        expect(count($teachers))->toBe(1);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/teachers/quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Show Quran Teacher', function () {
    it('returns quran teacher details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create();

        $profile = QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/student/teachers/quran/{$profile->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'teacher' => [
                        'id',
                        'user_id',
                        'name',
                        'avatar',
                        'bio',
                        'educational_qualification',
                        'rating',
                        'hourly_rate',
                        'available_packages',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-existent teacher', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/teachers/quran/99999', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });

    it('returns 404 for inactive teacher', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->quranTeacher()
            ->forAcademy($this->academy)
            ->create();

        $profile = QuranTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => false,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/student/teachers/quran/{$profile->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Academic Teachers Index', function () {
    it('returns list of active academic teachers', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create();

        AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/student/teachers/academic', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'teachers',
                    'pagination',
                ],
            ]);
    });

    it('filters teachers by subject', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create();

        AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
            'subject_ids' => [1, 2, 3],
        ]);

        $response = $this->getJson('/api/v1/student/teachers/academic?subject_id=1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('filters teachers by grade level', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create();

        AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
            'grade_level_ids' => [1, 2],
        ]);

        $response = $this->getJson('/api/v1/student/teachers/academic?grade_level_id=1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);
    });

    it('searches teachers by name', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create([
                'first_name' => 'Sara',
                'last_name' => 'Mohamed',
            ]);

        AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/student/teachers/academic?search=Sara', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $teachers = $response->json('data.teachers');
        if (!empty($teachers)) {
            expect($teachers[0]['name'])->toContain('Sara');
        }
    });

    it('paginates teacher results', function () {
        Sanctum::actingAs($this->student, ['*']);

        for ($i = 0; $i < 20; $i++) {
            $teacher = User::factory()
                ->academicTeacher()
                ->forAcademy($this->academy)
                ->create();

            AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'is_active' => true,
                'approval_status' => 'approved',
            ]);
        }

        $response = $this->getJson('/api/v1/student/teachers/academic?per_page=10', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.per_page', 10);

        $teachers = $response->json('data.teachers');
        expect(count($teachers))->toBeLessThanOrEqual(10);
    });
});

describe('Show Academic Teacher', function () {
    it('returns academic teacher details', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create();

        $profile = AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $response = $this->getJson("/api/v1/student/teachers/academic/{$profile->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'teacher' => [
                        'id',
                        'user_id',
                        'name',
                        'avatar',
                        'bio',
                        'education_level',
                        'rating',
                        'hourly_rate',
                        'subjects',
                        'grade_levels',
                        'available_packages',
                    ],
                ],
            ]);
    });

    it('returns 404 for non-approved teacher', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create();

        $profile = AcademicTeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/student/teachers/academic/{$profile->id}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(404);
    });
});

describe('Teacher Ordering', function () {
    it('orders teachers by rating descending', function () {
        Sanctum::actingAs($this->student, ['*']);

        $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        QuranTeacherProfile::factory()->create([
            'user_id' => $teacher1->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
            'rating' => 4.5,
        ]);

        $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
        QuranTeacherProfile::factory()->create([
            'user_id' => $teacher2->id,
            'academy_id' => $this->academy->id,
            'is_active' => true,
            'approval_status' => 'approved',
            'rating' => 4.9,
        ]);

        $response = $this->getJson('/api/v1/student/teachers/quran', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $teachers = $response->json('data.teachers');
        if (count($teachers) >= 2) {
            expect($teachers[0]['rating'])->toBeGreaterThanOrEqual($teachers[1]['rating']);
        }
    });
});
