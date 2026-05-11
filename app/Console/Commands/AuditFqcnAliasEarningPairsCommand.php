<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit of FQCN/alias duplicate teacher_earnings rows, run BEFORE
 * the 2026-05-11 normalize migration is applied on prod.
 *
 * Purpose: surface the exact financial impact for academy admin review so
 * Gate 1 of the sub-772 follow-up deploy can branch the right way:
 *
 *   (a) Paid teachers based on pre-mid-April totals (no over-pay)
 *       → proceed with the simple soft-delete + JSON audit migration.
 *   (b) Paid teachers based on post-mid-April totals (over-pay happened)
 *       → STOP; a separate teacher_adjustments feature is required.
 *   (c) Haven't paid yet → admin uses the corrected total as the basis.
 *
 * This command NEVER mutates data. Three output sections:
 *   1. Per-teacher summary  — name, # affected sessions, sum-with-dups vs
 *                             sum-without-dups, earning_month(s).
 *   2. Per-session detail   — both row ids, both created_at, session
 *                             scheduled_at/ended_at for cross-check.
 *   3. Standalone-FQCN tally — count + sample of rows the migration's
 *                              step-2 bulk-rename will touch (no money
 *                              impact, display-form change only).
 *
 * Usage:
 *   php artisan earnings:audit-fqcn-alias-pairs
 */
class AuditFqcnAliasEarningPairsCommand extends Command
{
    protected $signature = 'earnings:audit-fqcn-alias-pairs';

    protected $description = 'Read-only audit of FQCN/alias duplicate teacher_earnings rows for academy admin review (Gate 1 of 2026-05-11 deploy)';

    public function handle(): int
    {
        $fqcnList = $this->fqcnList();

        $this->info('=== teacher_earnings FQCN/alias audit ===');
        $this->newLine();

        $pairs = $this->loadDuplicatePairs($fqcnList);

        if ($pairs->isEmpty()) {
            $this->info('No FQCN+alias duplicate pairs found. Migration step 1 is a no-op.');
        } else {
            $this->renderPerTeacherSummary($pairs);
            $this->newLine();
            $this->renderPerSessionDetail($pairs);
            $this->newLine();
        }

        $this->renderStandaloneFqcnSummary($fqcnList, $pairs->pluck('fqcn_id')->all());

        return self::SUCCESS;
    }

    /**
     * Canonical FQCN forms recognised by the morph map. Mirrors the
     * migration's `fqcnToAliasMap()`.
     *
     * @return list<string>
     */
    private function fqcnList(): array
    {
        $aliases = ['quran_session', 'academic_session', 'interactive_course_session'];
        $list = [];
        foreach ($aliases as $alias) {
            $fqcn = Relation::getMorphedModel($alias);
            if ($fqcn !== null) {
                $list[] = $fqcn;
            }
        }
        $list = array_unique(array_merge($list, [
            'App\\Models\\QuranSession',
            'App\\Models\\AcademicSession',
            'App\\Models\\InteractiveCourseSession',
        ]));

        return array_values($list);
    }

    /**
     * Self-join teacher_earnings to find every alive (session_id, teacher)
     * tuple that has both an FQCN-form row and a different-form row alive.
     */
    private function loadDuplicatePairs(array $fqcnList): \Illuminate\Support\Collection
    {
        return DB::table('teacher_earnings as fqcn')
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
                'fqcn.created_at as fqcn_created_at',
                'alias.id as alias_id',
                'alias.session_type as alias_session_type',
                'alias.amount as alias_amount',
                'alias.created_at as alias_created_at',
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
            ->orderBy('fqcn.teacher_id')
            ->orderBy('fqcn.session_id')
            ->get();
    }

    /**
     * Section 1: per-teacher rollup. Critical for the admin signoff call.
     */
    private function renderPerTeacherSummary(\Illuminate\Support\Collection $pairs): void
    {
        $this->info('SECTION 1: Per-teacher summary');
        $this->line(str_repeat('-', 72));

        $byTeacher = $pairs->groupBy(fn ($row) => $row->teacher_type.'|'.$row->teacher_id);

        $rows = [];
        foreach ($byTeacher as $key => $teacherRows) {
            [$teacherType, $teacherId] = explode('|', $key);
            $teacherName = $this->resolveTeacherName($teacherType, (int) $teacherId);

            $sessionCount = $teacherRows->count();
            $inflatedAmount = $teacherRows->sum(fn ($r) => (float) $r->fqcn_amount);
            $months = $teacherRows
                ->pluck('earning_month')
                ->map(fn ($m) => substr((string) $m, 0, 7))
                ->unique()
                ->sort()
                ->implode(', ');

            $currentTotal = $this->teacherMonthTotalWithDups($teacherType, (int) $teacherId, $teacherRows);
            $correctedTotal = $this->teacherMonthTotalWithoutDups($teacherType, (int) $teacherId, $teacherRows);

            $rows[] = [
                'teacher_type' => class_basename($teacherType),
                'teacher_id' => $teacherId,
                'name' => $teacherName ?? '(unknown)',
                'sessions' => $sessionCount,
                'inflated_by' => number_format($inflatedAmount, 2),
                'months' => $months,
                'displayed' => number_format($currentTotal, 2),
                'corrected' => number_format($correctedTotal, 2),
            ];
        }

        $this->table(
            ['teacher_type', 'teacher_id', 'name', 'sessions', 'inflated_by', 'months', 'displayed_total', 'corrected_total'],
            $rows,
        );
    }

    /**
     * Section 2: per-session detail rows (37 expected on prod).
     */
    private function renderPerSessionDetail(\Illuminate\Support\Collection $pairs): void
    {
        $this->info('SECTION 2: Per-session pair detail');
        $this->line(str_repeat('-', 72));

        $bySessionType = $pairs->groupBy('alias_session_type');

        $rows = [];
        foreach ($bySessionType as $aliasType => $group) {
            $sessionTable = $this->sessionTableForAlias((string) $aliasType);
            $sessionIds = $group->pluck('session_id')->unique()->all();

            $sessionMeta = [];
            if ($sessionTable !== null && ! empty($sessionIds)) {
                $sessionMeta = DB::table($sessionTable)
                    ->whereIn('id', $sessionIds)
                    ->get(['id', 'scheduled_at', 'ended_at'])
                    ->keyBy('id')
                    ->all();
            }

            foreach ($group as $pair) {
                $meta = $sessionMeta[$pair->session_id] ?? null;
                $rows[] = [
                    'session' => sprintf('%s.%d', $aliasType, $pair->session_id),
                    'fqcn_id' => $pair->fqcn_id,
                    'fqcn_created' => substr((string) $pair->fqcn_created_at, 0, 19),
                    'alias_id' => $pair->alias_id,
                    'alias_created' => substr((string) $pair->alias_created_at, 0, 19),
                    'amount' => number_format((float) $pair->fqcn_amount, 2),
                    'scheduled_at' => $meta?->scheduled_at ?? '?',
                    'ended_at' => $meta?->ended_at ?? '?',
                ];
            }
        }

        $this->table(
            ['session', 'fqcn_id', 'fqcn_created', 'alias_id', 'alias_created', 'amount', 'scheduled_at', 'ended_at'],
            $rows,
        );
    }

    /**
     * Section 3: standalone FQCN rows (rows with no alias sibling). Migration
     * step 2 renames these in place — no money impact, just display form.
     */
    private function renderStandaloneFqcnSummary(array $fqcnList, array $duplicateFqcnIds): void
    {
        $this->info('SECTION 3: Standalone FQCN rows (migration step 2 rename target)');
        $this->line(str_repeat('-', 72));

        $query = DB::table('teacher_earnings')
            ->whereIn('session_type', $fqcnList)
            ->whereNull('deleted_at');
        if (! empty($duplicateFqcnIds)) {
            $query->whereNotIn('id', $duplicateFqcnIds);
        }

        $count = (clone $query)->count();
        $sample = (clone $query)->orderBy('id')->limit(5)->get([
            'id', 'session_type', 'session_id', 'teacher_type', 'teacher_id', 'amount', 'earning_month',
        ]);

        $this->line(sprintf('  Total standalone FQCN rows: %d', $count));
        if ($sample->isEmpty()) {
            return;
        }

        $rows = $sample->map(fn ($r) => [
            'id' => $r->id,
            'session_type' => $r->session_type,
            'session_id' => $r->session_id,
            'teacher' => sprintf('%s.%d', class_basename((string) $r->teacher_type), $r->teacher_id),
            'amount' => number_format((float) $r->amount, 2),
            'month' => substr((string) $r->earning_month, 0, 7),
        ])->all();

        $this->table(['id', 'session_type', 'session_id', 'teacher', 'amount', 'month'], $rows);
    }

    /**
     * Resolve teacher display name from `users` or `academic_teacher_profiles`.
     */
    private function resolveTeacherName(string $teacherType, int $teacherId): ?string
    {
        $aliasToTable = [
            'user' => 'users',
            'academic_teacher_profile' => 'academic_teacher_profiles',
        ];
        $table = $aliasToTable[$teacherType] ?? null;

        if ($table === null) {
            $fqcnToTable = [
                'App\\Models\\User' => 'users',
                'App\\Models\\AcademicTeacherProfile' => 'academic_teacher_profiles',
            ];
            $table = $fqcnToTable[$teacherType] ?? null;
        }

        if ($table === null) {
            return null;
        }

        if ($table === 'users') {
            return DB::table($table)->where('id', $teacherId)->value('name');
        }

        $userId = DB::table($table)->where('id', $teacherId)->value('user_id');

        return $userId ? DB::table('users')->where('id', $userId)->value('name') : null;
    }

    /**
     * Map alias session_type to its concrete table name.
     */
    private function sessionTableForAlias(string $alias): ?string
    {
        return match ($alias) {
            'quran_session' => 'quran_sessions',
            'academic_session' => 'academic_sessions',
            'interactive_course_session' => 'interactive_course_sessions',
            default => null,
        };
    }

    /**
     * SUM(amount) across every earning_month the affected teacher has rows in,
     * with FQCN duplicates included — matches what teachers + supervisors see
     * today via `EarningsController` / `TeacherEarningsDisplayService`.
     */
    private function teacherMonthTotalWithDups(string $teacherType, int $teacherId, \Illuminate\Support\Collection $pairs): float
    {
        $months = $pairs->pluck('earning_month')->unique()->all();
        if (empty($months)) {
            return 0.0;
        }

        return (float) DB::table('teacher_earnings')
            ->where('teacher_type', $teacherType)
            ->where('teacher_id', $teacherId)
            ->whereIn('earning_month', $months)
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    /**
     * Same window, with the FQCN duplicates excluded — what the surviving
     * alias row would aggregate to after the migration's soft-delete step.
     */
    private function teacherMonthTotalWithoutDups(string $teacherType, int $teacherId, \Illuminate\Support\Collection $pairs): float
    {
        $months = $pairs->pluck('earning_month')->unique()->all();
        if (empty($months)) {
            return 0.0;
        }

        $excludeIds = $pairs->pluck('fqcn_id')->all();

        return (float) DB::table('teacher_earnings')
            ->where('teacher_type', $teacherType)
            ->where('teacher_id', $teacherId)
            ->whereIn('earning_month', $months)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $excludeIds)
            ->sum('amount');
    }
}
