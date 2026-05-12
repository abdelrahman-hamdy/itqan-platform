<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AcademicSession;
use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\QuranSession;
use App\Models\QuranSubscription;
use App\Support\Subscriptions\CycleDriftClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Per-subscription forensic tool for the cycle-counter drift incident.
 *
 * When a student says "the platform says I'm exhausted but I'm not",
 * the admin runs this command and gets a classified per-cycle table plus
 * a plain-Arabic verdict — replacing the previous "ask a developer to
 * grep logs" workflow.
 *
 * Read-only: never mutates. To apply a repair on a CONFIRMED_BUG cohort,
 * use `subscriptions:audit-cycle-counts --subscription=<id> --apply`.
 */
class DiagnoseCycleDriftCommand extends Command
{
    protected $signature = 'subscriptions:diagnose-cycle-drift
                            {subscription_id : Subscription id to inspect}
                            {--type=quran : quran|academic}';

    protected $description = 'Classify drift on every cycle of one subscription and explain whether it is a bug or a known-good variance.';

    public function handle(): int
    {
        $subscriptionId = (int) $this->argument('subscription_id');
        $type = strtolower((string) $this->option('type'));

        $config = match ($type) {
            'academic' => [
                'sub_class' => AcademicSubscription::class,
                'session_class' => AcademicSession::class,
                'sessions_table' => (new AcademicSession)->getTable(),
                'sub_table' => (new AcademicSubscription)->getTable(),
                'sub_fk' => 'academic_subscription_id',
                'morph' => (new AcademicSubscription)->getMorphClass(),
            ],
            default => [
                'sub_class' => QuranSubscription::class,
                'session_class' => QuranSession::class,
                'sessions_table' => (new QuranSession)->getTable(),
                'sub_table' => (new QuranSubscription)->getTable(),
                'sub_fk' => 'quran_subscription_id',
                'morph' => (new QuranSubscription)->getMorphClass(),
            ],
        };

        /** @var BaseSubscription|null $sub */
        $sub = $config['sub_class']::query()->find($subscriptionId);
        if ($sub === null) {
            $this->error("Subscription #{$subscriptionId} not found ({$type}).");

            return self::FAILURE;
        }

        $rows = $this->loadForensicRows($subscriptionId, $config);

        if (empty($rows)) {
            $this->info("No cycles found for subscription #{$subscriptionId}.");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf(
            '<info>Subscription #%d</info>  type=%s  source=%s  cycle_count=%d  status=%s',
            $sub->id,
            $type,
            (string) ($sub->purchase_source?->value ?? $sub->purchase_source ?? 'unknown'),
            (int) ($sub->cycle_count ?? 1),
            (string) ($sub->status?->value ?? $sub->status ?? 'unknown'),
        ));
        $this->newLine();

        $tableRows = [];
        $classCounts = [];
        $verdicts = [];
        $anyDrift = false;

        foreach ($rows as $row) {
            $stored = (int) $row['stored_used'];
            $actual = (int) $row['actual_counted'];
            $gap = $stored - $actual;

            if ($gap === 0) {
                $tableRows[] = [
                    (int) $row['cycle_number'],
                    (string) $row['cycle_state'],
                    $stored,
                    $actual,
                    '0',
                    '<fg=green>OK</>',
                    '—',
                ];

                continue;
            }

            $anyDrift = true;
            $classification = CycleDriftClassifier::classify([
                'stored_used' => $stored,
                'actual_counted' => $actual,
                'soft_deleted_counted' => (int) $row['soft_deleted_counted'],
                'prior_repairs' => (int) $row['prior_repairs'],
                'shown_exhausted' => (int) $row['shown_exhausted'],
                'purchase_source' => (string) ($sub->purchase_source?->value ?? $sub->purchase_source ?? ''),
                'cycle_number' => (int) $row['cycle_number'],
                'cycle_state' => (string) $row['cycle_state'],
                'cycle_created_at' => $row['cycle_created_at'],
            ]);

            $classCounts[$classification['class']] = ($classCounts[$classification['class']] ?? 0) + 1;
            $verdicts[] = sprintf(
                'الدورة %d (%s): %s',
                (int) $row['cycle_number'],
                $classification['class'],
                $classification['reason_ar'],
            );

            $tableRows[] = [
                (int) $row['cycle_number'],
                (string) $row['cycle_state'],
                $stored,
                $actual,
                ($gap > 0 ? '+' : '').$gap,
                $this->paintClass($classification['class']),
                implode(' ', $classification['evidence']) ?: '—',
            ];
        }

        $this->table(
            ['Cycle #', 'State', 'Stored', 'Actual', 'Gap', 'Class', 'Evidence'],
            $tableRows,
        );

        $this->newLine();

        if (! $anyDrift) {
            $this->info('No drift detected on this subscription. ✅');

            return self::SUCCESS;
        }

        $this->line('<info>الخلاصة:</info>');
        foreach ($verdicts as $line) {
            $this->line('  • '.$line);
        }
        $this->newLine();

        if (isset($classCounts[CycleDriftClassifier::CLASS_CONFIRMED_BUG]) || isset($classCounts[CycleDriftClassifier::CLASS_RE_DRIFT])) {
            $this->line('<fg=red>هذا الاشتراك مرشّح للتصحيح. للتطبيق:</>');
            $this->line(sprintf(
                '  php artisan subscriptions:audit-cycle-counts --subscription=%d --type=%s --apply',
                $sub->id,
                $type,
            ));
        } else {
            $this->line('<fg=yellow>لا ينصح بالتصحيح التلقائي لهذا الاشتراك — انحرافه ضمن قائمة الحالات المُفسَّرة.</>');
        }

        return self::SUCCESS;
    }

    /**
     * Run the Step-1 forensic SQL scoped to one subscription.
     *
     * @param  array{
     *   sessions_table:string,
     *   sub_table:string,
     *   sub_fk:string,
     *   morph:string,
     * } $cfg
     * @return list<array{
     *   cycle_id:int, cycle_number:int, cycle_state:string,
     *   cycle_created_at:?string, stored_used:int, actual_counted:int,
     *   soft_deleted_counted:int, prior_repairs:int, shown_exhausted:int,
     * }>
     */
    private function loadForensicRows(int $subscriptionId, array $cfg): array
    {
        $sessions = $cfg['sessions_table'];
        $subs = $cfg['sub_table'];
        $morph = $cfg['morph'];

        $rows = DB::select(
            <<<SQL
            SELECT
                c.id                                AS cycle_id,
                c.cycle_number,
                c.cycle_state,
                c.created_at                        AS cycle_created_at,
                c.sessions_used                     AS stored_used,
                (SELECT COUNT(*) FROM `{$sessions}` qs
                   WHERE qs.subscription_cycle_id = c.id
                     AND qs.status = 'completed'
                     AND qs.subscription_counted = 1
                     AND qs.deleted_at IS NULL)     AS actual_counted,
                (SELECT COUNT(*) FROM `{$sessions}` qs
                   WHERE qs.subscription_cycle_id = c.id
                     AND qs.status = 'completed'
                     AND qs.subscription_counted = 1
                     AND qs.deleted_at IS NOT NULL) AS soft_deleted_counted,
                (SELECT COUNT(*) FROM backfill_log bl
                   WHERE bl.table_name = 'subscription_cycles'
                     AND bl.row_id = c.id
                     AND bl.column_name = 'sessions_used'
                     AND bl.reversed_at IS NULL)    AS prior_repairs,
                CASE WHEN JSON_EXTRACT(s.metadata, '$.sessions_exhausted') = TRUE
                     THEN 1 ELSE 0 END               AS shown_exhausted
            FROM subscription_cycles c
            JOIN `{$subs}` s ON s.id = c.subscribable_id
            WHERE c.subscribable_type = ?
              AND c.subscribable_id = ?
            ORDER BY c.cycle_number
            SQL,
            [$morph, $subscriptionId],
        );

        return array_map(fn ($r) => (array) $r, $rows);
    }

    private function paintClass(string $class): string
    {
        return match ($class) {
            CycleDriftClassifier::CLASS_RE_DRIFT => "<fg=red;options=bold>{$class}</>",
            CycleDriftClassifier::CLASS_CONFIRMED_BUG => "<fg=red>{$class}</>",
            CycleDriftClassifier::CLASS_NEEDS_REVIEW => "<fg=magenta>{$class}</>",
            CycleDriftClassifier::CLASS_FORGIVING_UNDERCOUNT => "<fg=yellow>{$class}</>",
            CycleDriftClassifier::CLASS_PRESET_SUSPECT,
            CycleDriftClassifier::CLASS_PRE_REFACTOR_AMBIGUOUS,
            CycleDriftClassifier::CLASS_SOFT_DELETED_EXPLAINED => "<fg=cyan>{$class}</>",
            default => "<fg=gray>{$class}</>",
        };
    }
}
