<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bug #5 — generic FQCN/alias dedup + write-side normalization for teacher_earnings.
 *
 * Two equivalent session_type values ('App\\Models\\QuranSession' vs the
 * morph alias 'quran_session') were able to coexist for the same session,
 * doubling teacher earnings. EarningsFixBug5KnownTuplesCommand cleaned up
 * the two known sessions; this migration generalises the same semantics:
 *
 *   1. Detect every (session_id, teacher_type, teacher_id) tuple that has
 *      both an FQCN-form and an alias-form earning row alive. Soft-delete
 *      the FQCN duplicate and append a dedup audit entry onto the surviving
 *      alias row's `calculation_metadata.dedup_history` array via JSON_SET.
 *      No reversal row is inserted: the composite unique index in step 4
 *      makes that physically impossible, and any negative reversal would
 *      be over-correction once the FQCN row is already soft-deleted (the
 *      teacher's displayed total drops by `amount` from the soft-delete
 *      alone; a paired reversal would drop it by another `amount`).
 *   2. Bulk-update the remaining FQCN rows to their alias form so the
 *      existing unique index on (session_type, session_id) protects them.
 *   3. Install BEFORE INSERT/UPDATE triggers that normalise FQCN → alias
 *      at the database boundary. Defends against a third-party caller
 *      (raw DB::table inserts, console scripts, fixtures) that hasn't
 *      adopted the service-layer normalizer.
 *   4. Add an additional covering composite unique index on
 *      (session_type, session_id, teacher_type, teacher_id) to make the
 *      teacher dimension explicit in the schema (the existing
 *      `unique_session_earning` index already enforces uniqueness on
 *      (session_type, session_id); the composite is recorded for query
 *      planning + future intent).
 *
 * Gate-1 dependency (2026-05-11 deploy): run
 * `php artisan earnings:audit-fqcn-alias-pairs` before applying this
 * migration on prod. Per-teacher inflation must be confirmed safe with the
 * academy admin (paths (a) or (c) in the plan) before step 1 runs — step 1
 * does mutate aggregates teachers/supervisors see.
 */
return new class extends Migration
{
    /**
     * Map FQCN → alias for every session model recorded in app's morph map.
     * Mirrors AppServiceProvider::boot()'s Relation::morphMap() entries.
     *
     * @return array<string, string>
     */
    private function fqcnToAliasMap(): array
    {
        $sessionAliases = [
            'quran_session',
            'academic_session',
            'interactive_course_session',
        ];

        $map = [];
        foreach ($sessionAliases as $alias) {
            $fqcn = Relation::getMorphedModel($alias);
            if ($fqcn !== null) {
                $map[$fqcn] = $alias;
            }
        }

        // Hard-code the canonical mapping in case the morph map isn't
        // available at migration time (e.g. running migrate:fresh from a
        // half-booted container). Idempotent overwrite.
        $map += [
            'App\\Models\\QuranSession' => 'quran_session',
            'App\\Models\\AcademicSession' => 'academic_session',
            'App\\Models\\InteractiveCourseSession' => 'interactive_course_session',
        ];

        return $map;
    }

    public function up(): void
    {
        if (! Schema::hasTable('teacher_earnings')) {
            return;
        }

        $map = $this->fqcnToAliasMap();
        $fqcnList = array_keys($map);

        // ------------------------------------------------------------------
        // 1. Dedup: FQCN+alias pairs alive for the same (session_id, teacher).
        // ------------------------------------------------------------------
        $duplicateRows = DB::table('teacher_earnings as fqcn')
            ->select(
                'fqcn.id as fqcn_id',
                'fqcn.session_type as fqcn_session_type',
                'fqcn.session_id',
                'fqcn.teacher_type',
                'fqcn.teacher_id',
                'fqcn.amount as fqcn_amount',
                'fqcn.academy_id',
                'fqcn.earning_month',
                'fqcn.session_completed_at',
                'alias.id as alias_id',
                'alias.session_type as alias_session_type',
            )
            ->join('teacher_earnings as alias', function ($join) {
                $join->on('alias.session_id', '=', 'fqcn.session_id')
                    ->on('alias.teacher_id', '=', 'fqcn.teacher_id')
                    ->on('alias.teacher_type', '=', 'fqcn.teacher_type')
                    ->whereColumn('alias.session_type', '!=', 'fqcn.session_type')
                    ->whereNull('alias.deleted_at');
            })
            ->whereIn('fqcn.session_type', $fqcnList)
            ->whereNull('fqcn.deleted_at')
            ->get();

        $now = now();
        $nowIso = $now->toIso8601String();
        foreach ($duplicateRows as $dup) {
            DB::transaction(function () use ($dup, $now, $nowIso) {
                // Soft-delete the FQCN-form duplicate. The surviving alias
                // row already carries the correct `amount`; the soft-delete
                // alone restores the teacher's monthly total to the right
                // figure without any reversal row.
                DB::table('teacher_earnings')
                    ->where('id', $dup->fqcn_id)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $now]);

                // Append an entry to the surviving alias row's
                // `calculation_metadata.dedup_history` so the dedup history
                // is preserved at the row level. JSON_ARRAY_APPEND keeps the
                // operation idempotent on re-runs (a second invocation just
                // appends another entry — no PK collisions, no double-charge).
                DB::table('teacher_earnings')->where('id', $dup->alias_id)->update([
                    'calculation_metadata' => DB::raw(sprintf(
                        "JSON_SET(
                            COALESCE(calculation_metadata, JSON_OBJECT()),
                            '$.dedup_history',
                            JSON_ARRAY_APPEND(
                                COALESCE(JSON_EXTRACT(calculation_metadata, '$.dedup_history'), JSON_ARRAY()),
                                '$',
                                JSON_OBJECT(
                                    'bug_id', 'bug_5',
                                    'reversed_row_id', %d,
                                    'reversed_amount', %s,
                                    'migration', '2026_05_11_120000_normalize_teacher_earnings_session_type',
                                    'reversed_at', %s
                                )
                            )
                        )",
                        (int) $dup->fqcn_id,
                        $this->sqlFloat((float) $dup->fqcn_amount),
                        $this->sqlString($nowIso),
                    )),
                    'updated_at' => $now,
                ]);
            });
        }

        // ------------------------------------------------------------------
        // 2. Bulk-normalize remaining FQCN rows to alias. Single transaction
        //    so the table lock acquires/releases once rather than per FQCN.
        // ------------------------------------------------------------------
        DB::transaction(function () use ($map, $now) {
            foreach ($map as $fqcn => $alias) {
                DB::table('teacher_earnings')
                    ->where('session_type', $fqcn)
                    ->whereNull('deleted_at')
                    ->update(['session_type' => $alias, 'updated_at' => $now]);
            }
        });

        // ------------------------------------------------------------------
        // 3. Triggers: normalise FQCN → alias at the DB boundary on writes.
        // ------------------------------------------------------------------
        $caseSql = $this->buildCaseStatement($map, 'NEW.session_type');

        DB::unprepared('DROP TRIGGER IF EXISTS teacher_earnings_normalize_session_type_insert');
        DB::unprepared(<<<SQL
            CREATE TRIGGER teacher_earnings_normalize_session_type_insert
            BEFORE INSERT ON teacher_earnings
            FOR EACH ROW
            BEGIN
                SET NEW.session_type = {$caseSql};
            END
        SQL);

        DB::unprepared('DROP TRIGGER IF EXISTS teacher_earnings_normalize_session_type_update');
        DB::unprepared(<<<SQL
            CREATE TRIGGER teacher_earnings_normalize_session_type_update
            BEFORE UPDATE ON teacher_earnings
            FOR EACH ROW
            BEGIN
                SET NEW.session_type = {$caseSql};
            END
        SQL);

        // ------------------------------------------------------------------
        // 4. Composite covering unique on (session_type, session_id, teacher_type, teacher_id).
        // ------------------------------------------------------------------
        $existingIndexes = collect(DB::select('SHOW INDEX FROM teacher_earnings'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (! in_array('unique_session_earning_full', $existingIndexes, true)) {
            Schema::table('teacher_earnings', function ($table) {
                $table->unique(
                    ['session_type', 'session_id', 'teacher_type', 'teacher_id'],
                    'unique_session_earning_full'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_earnings')) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS teacher_earnings_normalize_session_type_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS teacher_earnings_normalize_session_type_update');

        $existingIndexes = collect(DB::select('SHOW INDEX FROM teacher_earnings'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (in_array('unique_session_earning_full', $existingIndexes, true)) {
            Schema::table('teacher_earnings', function ($table) {
                $table->dropUnique('unique_session_earning_full');
            });
        }

        // Intentionally do NOT restore FQCN values or revive reversal rows —
        // alias is the canonical form going forward. Rolling back this
        // migration leaves data in the post-normalization shape.
    }

    /**
     * Render a float as a SQL numeric literal. Used inside DB::raw bodies for
     * the JSON_SET dedup-history audit so PHP-formatted floats land cleanly
     * without locale-dependent decimal separators.
     */
    private function sqlFloat(float $value): string
    {
        return number_format($value, 4, '.', '');
    }

    /**
     * Render a value as a single-quoted SQL string literal with apostrophes
     * doubled, for inline use inside DB::raw bodies.
     */
    private function sqlString(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    /**
     * Build a SQL CASE expression that maps known FQCN values to their alias
     * for use inside a trigger body.
     *
     * Escaping note: MySQL treats `\` as an escape character inside string
     * literals (NO_BACKSLASH_ESCAPES is off by default). The FQCN
     * `App\Models\QuranSession` therefore needs `App\\\\Models\\\\QuranSession`
     * inside the SQL so that MySQL sees a literal `App\\Models\\QuranSession`
     * after parsing — which matches what PHP and Eloquent send on insert.
     */
    private function buildCaseStatement(array $map, string $column): string
    {
        $cases = [];
        foreach ($map as $fqcn => $alias) {
            $escapedFqcn = str_replace(['\\', "'"], ['\\\\', "''"], $fqcn);
            $escapedAlias = str_replace("'", "''", $alias);
            $cases[] = "WHEN '{$escapedFqcn}' THEN '{$escapedAlias}'";
        }
        $cases = implode("\n                    ", $cases);

        return <<<SQL
        CASE {$column}
                    {$cases}
                    ELSE {$column}
                END
        SQL;
    }
};
