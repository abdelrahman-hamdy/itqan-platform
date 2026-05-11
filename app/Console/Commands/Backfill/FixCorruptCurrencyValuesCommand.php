<?php

namespace App\Console\Commands\Backfill;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Currency-corruption remediation discovered during the 2026-05-11 sub-772
 * cleanup: 11 alive rows across 5 tables carried `currency = "1"` or `"2"`,
 * sourced originally from a 2026-04 web-purchase form path that mapped a
 * non-string flag into the column. The backfill faithfully copied the
 * corrupt cycle currency onto its replacement payment, surfacing the
 * problem.
 *
 * Strategy: for every row with `currency NOT IN ('SAR','EGP')`, coerce to
 * the owning academy's currency. Hard-abort the run if the academy itself
 * has an invalid currency — that's a different problem and requires manual
 * triage. The model-side mutator on BaseSubscription / SubscriptionCycle /
 * Payment is the live tripwire; this command cleans up history.
 *
 * Tables in scope (incl. soft-deleted rows so a future restore doesn't
 * resurrect bad data):
 *   - quran_subscriptions
 *   - academic_subscriptions
 *   - course_subscriptions
 *   - subscription_cycles
 *   - payments
 *
 * Usage:
 *   php artisan subscriptions:fix-corrupt-currency --dry-run
 *   php artisan subscriptions:fix-corrupt-currency --apply
 *   php artisan subscriptions:fix-corrupt-currency --rollback
 */
class FixCorruptCurrencyValuesCommand extends BaseBackfillCommand
{
    protected $signature = 'subscriptions:fix-corrupt-currency
                            {--dry-run : Print what would change without mutating (default)}
                            {--apply : Coerce every bad-currency row to its academy default}
                            {--rollback : Reverse a prior --apply via the backfill_log audit trail}';

    protected $description = '2026-05-11 incident remediation — coerce currency NOT IN (SAR,EGP) to academy default across subs/cycles/payments';

    protected const BUG_ID = 'currency_corruption_2026_05_11';

    protected const COMMAND_NAME = 'subscriptions:fix-corrupt-currency';

    /**
     * Tables to scan. Order matters only for the dry-run output.
     *
     * @var list<array{table:string,model:class-string<Model>|null,with_trashed:bool}>
     */
    private const TABLES = [
        ['table' => 'quran_subscriptions', 'model' => \App\Models\QuranSubscription::class, 'with_trashed' => true],
        ['table' => 'academic_subscriptions', 'model' => \App\Models\AcademicSubscription::class, 'with_trashed' => true],
        ['table' => 'course_subscriptions', 'model' => \App\Models\CourseSubscription::class, 'with_trashed' => true],
        ['table' => 'subscription_cycles', 'model' => \App\Models\SubscriptionCycle::class, 'with_trashed' => false],
        ['table' => 'payments', 'model' => \App\Models\Payment::class, 'with_trashed' => true],
    ];

    private const ALLOWED = ['SAR', 'EGP'];

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dryRun = (bool) $this->option('dry-run') || ! $this->option('apply');

        if ($dryRun) {
            $this->warn('Dry-run mode (default). Pass --apply to mutate.');
        }

        $touched = 0;
        $aborted = 0;
        $skipped = 0;

        foreach (self::TABLES as $spec) {
            ['table' => $table, 'model' => $modelClass, 'with_trashed' => $withTrashed] = $spec;

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'currency')) {
                $this->line("  {$table}: missing column, skipping");

                continue;
            }

            $query = DB::table($table)
                ->whereNotIn(DB::raw('UPPER(currency)'), self::ALLOWED)
                ->whereNotNull('currency');

            if (! $withTrashed && Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $rows = $query->get();

            if ($rows->isEmpty()) {
                $this->info("  {$table}: clean (0 affected rows)");

                continue;
            }

            $this->line(sprintf('  %s: %d affected row(s)', $table, $rows->count()));

            foreach ($rows as $row) {
                $academyCurrency = $this->resolveAcademyCurrency($row->academy_id ?? null);

                if ($academyCurrency === null) {
                    $this->error(sprintf(
                        '    row id=%d academy_id=%s — academy currency missing or invalid, ABORTING',
                        $row->id,
                        $row->academy_id ?? 'null',
                    ));
                    $aborted++;

                    continue;
                }

                $this->line(sprintf(
                    '    %s.%d: currency="%s" -> "%s" (academy %d)',
                    $table,
                    $row->id,
                    $row->currency,
                    $academyCurrency,
                    (int) $row->academy_id,
                ));

                if ($dryRun) {
                    $touched++;

                    continue;
                }

                if ($modelClass === null) {
                    $skipped++;

                    continue;
                }

                DB::transaction(function () use ($modelClass, $row, $academyCurrency, $withTrashed) {
                    $rowModel = $this->loadRowModel($modelClass, (int) $row->id, $withTrashed);
                    if ($rowModel === null) {
                        return;
                    }

                    $this->logChange($rowModel, 'currency', $row->currency, $academyCurrency);

                    DB::table($rowModel->getTable())
                        ->where('id', $rowModel->getKey())
                        ->update(['currency' => $academyCurrency]);
                });

                $touched++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Rows %s: %d. Aborted: %d. Skipped: %d.',
            $dryRun ? 'planned' : 'updated',
            $touched,
            $aborted,
            $skipped,
        ));

        return $aborted > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resolve an academy's currency. Returns null when the academy itself
     * carries an unrecognised value — caller hard-aborts in that case.
     */
    private function resolveAcademyCurrency(?int $academyId): ?string
    {
        if ($academyId === null) {
            return null;
        }

        $value = DB::table('academies')->where('id', $academyId)->value('currency');

        if ($value === null) {
            return null;
        }

        $upper = strtoupper((string) $value);

        return in_array($upper, self::ALLOWED, true) ? $upper : null;
    }

    /**
     * Resolve the Eloquent row so `logChange()` can capture
     * `getTable()`/`getKey()` correctly. Bypasses global scopes so soft-deleted
     * and out-of-tenant rows are visible to a system-level command.
     */
    private function loadRowModel(string $modelClass, int $id, bool $withTrashed): ?Model
    {
        $query = $modelClass::query()->withoutGlobalScopes();

        $usesSoftDeletes = in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($modelClass),
            true,
        );

        if ($withTrashed && $usesSoftDeletes) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    private function rollback(): int
    {
        return $this->rollbackLogged();
    }
}
