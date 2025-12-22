<?php

use App\Models\Academy;
use App\Models\AcademicSession;
use App\Models\AcademicTeacherProfile;
use App\Models\Payout;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\TeacherEarning;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses()->group('api', 'teacher', 'earnings');

beforeEach(function () {
    $this->academy = Academy::factory()->create([
        'subdomain' => 'test-academy',
        'is_active' => true,
    ]);
});

describe('Teacher Earnings API', function () {
    describe('earnings summary', function () {
        it('returns earnings summary for teacher', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/summary', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'summary' => [
                            'total_earnings',
                            'current_month_earnings',
                            'last_month_earnings',
                            'pending_payout',
                            'total_paid_out',
                            'by_type' => [
                                'quran',
                                'academic',
                            ],
                        ],
                        'currency',
                    ],
                    'message',
                ]);

            expect($response->json('data.currency'))->toBe('SAR');
        });

        it('calculates earnings from teacher earning model', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(3)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'academic',
                'amount' => 100,
                'status' => 'pending',
            ]);

            TeacherEarning::factory()->count(2)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'academic',
                'amount' => 150,
                'status' => 'paid',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/summary', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $summary = $response->json('data.summary');
            expect($summary['total_earnings'])->toBe(600.0);
            expect($summary['pending_payout'])->toBe(300.0);
            expect($summary['total_paid_out'])->toBe(300.0);
        });

        it('separates earnings by type', function () {
            $teacher = User::factory()->academicTeacher()->forAcademy($this->academy)->create();
            $profile = AcademicTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(2)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'academic',
                'amount' => 100,
                'status' => 'pending',
            ]);

            TeacherEarning::factory()->count(3)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'quran',
                'amount' => 80,
                'status' => 'pending',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/summary', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $byType = $response->json('data.summary.by_type');
            expect($byType['academic'])->toBe(200.0);
            expect($byType['quran'])->toBe(240.0);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/earnings/summary', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('earnings history', function () {
        it('returns earnings history', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(5)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'quran',
                'amount' => 50,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/history', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'earnings',
                        'period' => [
                            'start_date',
                            'end_date',
                        ],
                        'total_for_period',
                        'currency',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.earnings')))->toBeGreaterThan(0);
        });

        it('filters earnings by date range', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'quran',
                'amount' => 100,
                'created_at' => now()->subDays(10),
            ]);

            TeacherEarning::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'quran',
                'amount' => 150,
                'created_at' => now()->subDays(100),
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/history?start_date=' . now()->subDays(30)->toDateString() . '&end_date=' . now()->toDateString(), [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $earnings = $response->json('data.earnings');
            expect(count($earnings))->toBe(1);
        });

        it('paginates earnings', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            TeacherEarning::factory()->count(25)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'type' => 'quran',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/history?per_page=10&page=1', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $pagination = $response->json('data.pagination');
            expect($pagination['current_page'])->toBe(1);
            expect($pagination['per_page'])->toBe(10);
            expect($pagination['total'])->toBeGreaterThanOrEqual(25);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/earnings/history', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('payouts', function () {
        it('returns teacher payouts', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Payout::factory()->count(3)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
                'amount' => 500,
                'status' => 'completed',
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/payouts', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'payouts',
                        'pagination',
                    ],
                ]);

            expect(count($response->json('data.payouts')))->toBe(3);
        });

        it('only shows teacher own payouts', function () {
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

            Payout::factory()->count(2)->create([
                'user_id' => $teacher1->id,
                'academy_id' => $this->academy->id,
            ]);

            Payout::factory()->count(3)->create([
                'user_id' => $teacher2->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher1, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/payouts', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            expect(count($response->json('data.payouts')))->toBe(2);
        });

        it('paginates payouts', function () {
            $teacher = User::factory()->quranTeacher()->forAcademy($this->academy)->create();
            $profile = QuranTeacherProfile::factory()->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Payout::factory()->count(20)->create([
                'user_id' => $teacher->id,
                'academy_id' => $this->academy->id,
            ]);

            Sanctum::actingAs($teacher, ['*']);

            $response = $this->getJson('/api/v1/teacher/earnings/payouts?per_page=15', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(200);

            $pagination = $response->json('data.pagination');
            expect($pagination['per_page'])->toBe(15);
            expect($pagination['total'])->toBe(20);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/teacher/earnings/payouts', [
                'X-Academy-Subdomain' => $this->academy->subdomain,
            ]);

            $response->assertStatus(401);
        });
    });
});
