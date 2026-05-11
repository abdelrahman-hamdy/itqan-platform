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
 *      the FQCN row and post a negative-amount reversal pointing at the
 *      surviving alias row, mirroring the per-tuple command's audit trail.
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
        foreach ($duplicateRows as $dup) {
            DB::transaction(function () use ($dup, $map, $now) {
                $aliasForm = $map[$dup->fqcn_session_type] ?? $dup->fqcn_session_type;

                // Soft-delete the FQCN-form earning.
                DB::table('teacher_earnings')
                    ->where('id', $dup->fqcn_id)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $now]);

                // Post a negative-amount reversal pointing at the alias form so
                // monthly aggregates balance for the affected teacher.
                DB::table('teacher_earnings')->insert([
                    'academy_id' => $dup->academy_id,
                    'teacher_type' => $dup->teacher_type,
                    'teacher_id' => $dup->teacher_id,
                    'session_type' => $aliasForm,
                    'session_id' => $dup->session_id,
                    'amount' => -1 * (float) $dup->fqcn_amount,
                    'calculation_method' => 'backfill_dedup_reversal',
                    'rate_snapshot' => null,
                    'calculation_metadata' => json_encode([
                        'bug_id' => 'bug_5',
                        'migration' => '2026_05_11_120000_normalize_teacher_earnings_session_type',
                        'reason' => 'fqcn_alias_duplicate',
                        'reversed_row_id' => $dup->fqcn_id,
                        'paired_alias_row_id' => $dup->alias_id,
                    ]),
                    'earning_month' => $dup->earning_month,
                    'session_completed_at' => $dup->session_completed_at,
                    'calculated_at' => $now,
                    'is_finalized' => true,
                    'is_disputed' => false,
                    'created_at' => $now,
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
