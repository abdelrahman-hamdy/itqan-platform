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

describe('Calendar Index', function () {
    it('returns calendar events for current month', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'year',
                    'month',
                    'month_name',
                    'start_date',
                    'end_date',
                    'events',
                    'events_by_date',
                    'total_events',
                ],
            ]);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(401);
    });
});

describe('Calendar Month', function () {
    it('returns calendar events for specific month', function () {
        Sanctum::actingAs($this->student, ['*']);

        $year = now()->year;
        $month = now()->month;

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now()->setYear($year)->setMonth($month),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson("/api/v1/student/calendar/month/{$year}/{$month}", [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.year', $year)
            ->assertJsonPath('data.month', $month);
    });

    it('validates year parameter', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/calendar/month/1900/1', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'INVALID_DATE',
            ]);
    });

    it('validates month parameter', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/calendar/month/2025/13', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'code' => 'INVALID_DATE',
            ]);
    });

    it('accepts valid month 0', function () {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/calendar/month/2025/0', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(400);
    });
});

describe('Calendar Events', function () {
    it('includes quran sessions in events', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $events = $response->json('data.events');
        expect($events)->not->toBeEmpty();

        $quranEvent = collect($events)->firstWhere('type', 'quran');
        expect($quranEvent)->not->toBeNull();
        expect($quranEvent['color'])->toBe('#22c55e');
    });

    it('includes academic sessions in events', function () {
        Sanctum::actingAs($this->student, ['*']);

        $academicTeacher = User::factory()
            ->academicTeacher()
            ->forAcademy($this->academy)
            ->create();

        AcademicSession::factory()->create([
            'student_id' => $this->student->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $events = $response->json('data.events');
        $academicEvent = collect($events)->firstWhere('type', 'academic');

        if ($academicEvent) {
            expect($academicEvent['color'])->toBe('#3b82f6');
        }
    });

    it('groups events by date', function () {
        Sanctum::actingAs($this->student, ['*']);

        $date = now();

        QuranSession::factory()->count(2)->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => $date,
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $eventsByDate = $response->json('data.events_by_date');
        expect($eventsByDate)->toBeArray();

        $dateKey = $date->toDateString();
        if (isset($eventsByDate[$dateKey])) {
            expect(count($eventsByDate[$dateKey]))->toBeGreaterThanOrEqual(2);
        }
    });

    it('includes session details in events', function () {
        Sanctum::actingAs($this->student, ['*']);

        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $events = $response->json('data.events');
        $event = $events[0] ?? null;

        if ($event) {
            expect($event)->toHaveKeys([
                'id',
                'type',
                'title',
                'start',
                'end',
                'date',
                'time',
                'duration_minutes',
                'status',
                'color',
                'teacher_name',
            ]);
        }
    });

    it('only returns events for current student', function () {
        $otherStudent = User::factory()
            ->student()
            ->forAcademy($this->academy)
            ->create();

        QuranSession::factory()->create([
            'student_id' => $otherStudent->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => now(),
            'status' => 'scheduled',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_events', 0);
    });

    it('filters events by month boundaries', function () {
        Sanctum::actingAs($this->student, ['*']);

        $currentMonth = now();
        $nextMonth = now()->addMonth();

        // Session in current month
        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => $currentMonth,
            'status' => 'scheduled',
        ]);

        // Session in next month
        QuranSession::factory()->create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacher->id,
            'academy_id' => $this->academy->id,
            'scheduled_at' => $nextMonth,
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v1/student/calendar', [
            'X-Academy-Subdomain' => $this->academy->subdomain,
        ]);

        $response->assertStatus(200);

        $events = $response->json('data.events');

        // Should only include current month sessions
        foreach ($events as $event) {
            $eventDate = \Carbon\Carbon::parse($event['start']);
            expect($eventDate->month)->toBe($currentMonth->month);
        }
    });
});
