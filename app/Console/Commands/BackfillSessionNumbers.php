<?php

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\QuranSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSessionNumbers extends Command
{
    protected $signature = 'sessions:backfill-numbers {--dry-run : Preview changes without saving}';

    protected $description = 'Backfill session_number for existing Quran and Academic sessions';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no changes will be saved.');
        }

        $this->backfillQuranIndividual($dryRun);
        $this->backfillQuranGroup($dryRun);
        $this->backfillAcademic($dryRun);

        $this->info('Backfill complete.');

        return self::SUCCESS;
    }

    private function backfillQuranIndividual(bool $dryRun): void
    {
        $this->info('Backfilling Quran individual sessions...');

        $circleIds = QuranSession::withTrashed()
            ->whereNotNull('individual_circle_id')
            ->distinct()
            ->pluck('individual_circle_id');

        foreach ($circleIds as $circleId) {
            $this->backfillGroup(
                model: QuranSession::class,
                column: 'individual_circle_id',
                groupId: $circleId,
                dryRun: $dryRun,
                label: "individual circle {$circleId}",
            );
        }
    }

    private function backfillQuranGroup(bool $dryRun): void
    {
        $this->info('Backfilling Quran group sessions...');

        $circleIds = QuranSession::withTrashed()
            ->whereNotNull('circle_id')
            ->distinct()
            ->pluck('circle_id');

        foreach ($circleIds as $circleId) {
            $this->backfillGroup(
                model: QuranSession::class,
                column: 'circle_id',
                groupId: $circleId,
                dryRun: $dryRun,
                label: "group circle {$circleId}",
            );
        }
    }

    private function backfillAcademic(bool $dryRun): void
    {
        $this->info('Backfilling Academic sessions...');

        $subscriptionIds = AcademicSession::withTrashed()
            ->whereNotNull('academic_subscription_id')
            ->distinct()
            ->pluck('academic_subscription_id');

        foreach ($subscriptionIds as $subscriptionId) {
            $this->backfillGroup(
                model: AcademicSession::class,
                column: 'academic_subscription_id',
                groupId: $subscriptionId,
                dryRun: $dryRun,
                label: "academic subscription {$subscriptionId}",
            );
        }
    }

    private function backfillGroup(string $model, string $column, int $groupId, bool $dryRun, string $label): void
    {
        $callback = function () use ($model, $column, $groupId, $dryRun, $label) {
            $sessions = $model::withTrashed()
                ->where($column, $groupId)
                ->lockForUpdate()
                ->orderBy('scheduled_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($sessions->isEmpty()) {
                return;
            }

            $number = 0;
            $updated = 0;

            foreach ($sessions as $session) {
                $number++;

                if ($session->session_number === $number) {
                    continue;
                }

                if (! $dryRun) {
                    $session->update([
                        'session_number' => $number,
                        'title' => $this->generateTitle($session, $number),
                    ]);
                }

                $updated++;
            }

            $action = $dryRun ? 'Would update' : 'Backfilled';
            $this->line("  {$action} {$label}: {$number} sessions, {$updated} changed");
        };

        if ($dryRun) {
            // Still need the query but no transaction needed
            $callback();
        } else {
            DB::transaction($callback);
        }
    }

    private function generateTitle(QuranSession|AcademicSession $session, int $number): string
    {
        if ($session instanceof QuranSession) {
            if ($session->session_type === 'trial') {
                return $session->title; // Don't rename trial sessions
            }

            if ($session->individual_circle_id) {
                $studentName = $session->student?->name
                    ?? $session->individualCircle?->student?->name
                    ?? __('sessions.naming.default_student');

                return __('sessions.naming.individual_circle_session', ['n' => $number, 'student' => $studentName]);
            }

            if ($session->circle_id) {
                $circleName = $session->circle?->name ?? __('sessions.naming.default_circle');

                return __('sessions.naming.group_circle_session', ['n' => $number, 'circle' => $circleName]);
            }

            return $session->title;
        }

        // AcademicSession
        $subjectName = $session->academicIndividualLesson?->subscription?->subject_name
            ?? $session->academicSubscription?->subject_name
            ?? __('sessions.naming.default_subject');

        return __('sessions.naming.academic_session', ['n' => $number, 'subject' => $subjectName]);
    }
}
