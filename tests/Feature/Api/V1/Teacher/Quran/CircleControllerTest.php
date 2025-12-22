<?php

use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'quran', 'circles');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Quran Circle API', function () {
    describe('individual circles', function () {
        it('returns individual circles for teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranIndividualCircle::factory()->count(3)->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'active',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/individual', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'circles',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.circles')))->toBe(3);
        });

        it('filters circles by status', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranIndividualCircle::factory()->count(2)->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'active',
            ]);

            QuranIndividualCircle::factory()->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'inactive',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/individual?status=active', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.circles')))->toBe(2);
        });

        it('only shows teacher own circles', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranIndividualCircle::factory()->count(2)->create([
                'quran_teacher_id' => $profile1->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranIndividualCircle::factory()->count(3)->create([
                'quran_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/individual', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.circles')))->toBe(2);
        });

        it('paginates circles', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranIndividualCircle::factory()->count(20)->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/individual?per_page=10', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $pagination = $response->json('data.pagination');
            expect($pagination['per_page'])->toBe(10);
            expect($pagination['total'])->toBe(20);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/quran/circles/individual', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('individual circle details', function () {
        it('returns individual circle details', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranIndividualCircle::factory()->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/quran/circles/individual/{$circle->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'circle' => [
                            'id',
                            'name',
                            'description',
                            'student',
                            'status',
                            'subscription',
                            'schedule',
                            'recent_sessions',
                            'progress',
                        ],
                    ],
                ]);
        });

        it('prevents access to other teachers circles', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranIndividualCircle::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/quran/circles/individual/{$circle->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('returns 404 for non-existent circle', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/individual/99999', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/quran/circles/individual/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('group circles', function () {
        it('returns group circles for teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranCircle::factory()->count(2)->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
                'status' => 'active',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/group', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'circles',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.circles')))->toBe(2);
        });

        it('only shows teacher own group circles', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranCircle::factory()->create([
                'quran_teacher_id' => $profile1->id,
                'academy_id' => $this->academy->id,
            ]);

            QuranCircle::factory()->count(2)->create([
                'quran_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/quran/circles/group', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.circles')))->toBe(1);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/quran/circles/group', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('group circle details', function () {
        it('returns group circle details', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'quran_teacher_id' => $profile->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson("/api/v1/teacher/quran/circles/group/{$circle->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'circle' => [
                            'id',
                            'name',
                            'description',
                            'status',
                            'level',
                            'students_count',
                            'max_students',
                            'schedule',
                            'recent_sessions',
                        ],
                    ],
                ]);
        });

        it('prevents access to other teachers group circles', function () {
            $teacher1 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile1 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            $teacher2 = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile2 = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            $circle = QuranCircle::factory()->create([
                'quran_teacher_id' => $profile2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson("/api/v1/teacher/quran/circles/group/{$circle->id}", [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/quran/circles/group/1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
