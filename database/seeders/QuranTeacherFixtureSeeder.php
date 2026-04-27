<?php

namespace Database\Seeders;

use App\Enums\SessionStatus;
use App\Models\Academy;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\QuranTrialRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotent fixture seeder for the Quran-teacher mobile UI audit.
 *
 * Ensures `quran1@itqan.com` on `itqan-academy` has one group circle, one
 * individual circle, one upcoming session, and one pending trial request —
 * the detail-screen tests in the teacher e2e suite need at least these to
 * stop skipping with "no … discovered". Each `ensure*` helper is a
 * find-or-create gated on `(academy_id, quran_teacher_id)` so re-runs are no-ops.
 *
 * Usage: php artisan db:seed --class=QuranTeacherFixtureSeeder --force
 */
class QuranTeacherFixtureSeeder extends Seeder
{
    private const MARKER = '[UI-FIXTURE]';

    private const ACADEMY_SUBDOMAIN = 'itqan-academy';

    private const TEACHER_EMAIL = 'quran1@itqan.com';

    public function run(): void
    {
        $academy = Academy::where('subdomain', self::ACADEMY_SUBDOMAIN)->first();
        if ($academy === null) {
            $this->command->error(
                'Academy "'.self::ACADEMY_SUBDOMAIN.'" not found — aborting.'
            );

            return;
        }

        $teacher = User::where('email', self::TEACHER_EMAIL)
            ->where('academy_id', $academy->id)
            ->first();
        if ($teacher === null) {
            $this->command->error(
                'Teacher "'.self::TEACHER_EMAIL.'" not found in academy '
                .self::ACADEMY_SUBDOMAIN.' — aborting.'
            );

            return;
        }

        $this->command->info("Seeding fixtures for {$teacher->email} on {$academy->subdomain}…");

        $student = $this->findStudent($academy);
        $this->ensureGroupCircle($academy, $teacher);
        $this->ensureIndividualCircle($academy, $teacher, $student);
        $this->ensureUpcomingSession($academy, $teacher, $student);
        $this->ensureTrialRequest($academy, $teacher);

        $this->command->info('Done.');
    }

    private function ensureGroupCircle(Academy $academy, User $teacher): QuranCircle
    {
        $existing = QuranCircle::where('academy_id', $academy->id)
            ->where('quran_teacher_id', $teacher->id)
            ->first();
        if ($existing !== null) {
            $this->command->line("  group circle exists: #{$existing->id}");

            return $existing;
        }

        $circle = QuranCircle::factory()->create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'name' => self::MARKER.' حلقة الاختبار',
            'description' => self::MARKER.' fixture circle for UI audit',
        ]);
        $this->command->line("  created group circle #{$circle->id}");

        return $circle;
    }

    private function ensureIndividualCircle(
        Academy $academy,
        User $teacher,
        ?User $student,
    ): ?QuranIndividualCircle {
        $existing = QuranIndividualCircle::where('academy_id', $academy->id)
            ->where('quran_teacher_id', $teacher->id)
            ->first();
        if ($existing !== null) {
            $this->command->line("  individual circle exists: #{$existing->id}");

            return $existing;
        }

        if ($student === null) {
            $this->command->warn(
                '  no student found in academy — skipping individual circle.'
            );

            return null;
        }

        $circle = QuranIndividualCircle::factory()->create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);
        $this->command->line("  created individual circle #{$circle->id}");

        return $circle;
    }

    private function ensureUpcomingSession(
        Academy $academy,
        User $teacher,
        ?User $student,
    ): ?QuranSession {
        $existing = QuranSession::where('academy_id', $academy->id)
            ->where('quran_teacher_id', $teacher->id)
            ->where('status', SessionStatus::SCHEDULED->value)
            ->where('scheduled_at', '>', now())
            ->first();
        if ($existing !== null) {
            $this->command->line("  upcoming session exists: #{$existing->id}");

            return $existing;
        }

        $session = QuranSession::factory()->scheduled()->create([
            'academy_id' => $academy->id,
            'quran_teacher_id' => $teacher->id,
            'student_id' => $student?->id,
            'session_type' => $student !== null ? 'individual' : 'group',
            'title' => self::MARKER.' Upcoming session',
            'scheduled_at' => now()->addDay()->setTime(10, 0),
        ]);
        $this->command->line("  created upcoming session #{$session->id}");

        return $session;
    }

    private function ensureTrialRequest(
        Academy $academy,
        User $teacher,
    ): QuranTrialRequest {
        $teacherProfile = QuranTeacherProfile::where('user_id', $teacher->id)
            ->first();

        $query = QuranTrialRequest::where('academy_id', $academy->id)
            ->where('status', 'pending');
        if ($teacherProfile !== null) {
            $query->where(function ($q) use ($teacherProfile) {
                $q->whereNull('teacher_id')
                    ->orWhere('teacher_id', $teacherProfile->id);
            });
        }
        $existing = $query->first();
        if ($existing !== null) {
            $this->command->line("  trial request exists: #{$existing->id}");

            return $existing;
        }

        $request = QuranTrialRequest::factory()->pending()->create([
            'academy_id' => $academy->id,
            'teacher_id' => $teacherProfile?->id,
            'notes' => self::MARKER.' fixture trial request',
        ]);
        $this->command->line("  created trial request #{$request->id}");

        return $request;
    }

    private function findStudent(Academy $academy): ?User
    {
        return User::where('academy_id', $academy->id)
            ->where('user_type', 'student')
            ->first();
    }
}
