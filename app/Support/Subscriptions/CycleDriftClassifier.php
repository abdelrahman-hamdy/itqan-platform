<?php

declare(strict_types=1);

namespace App\Support\Subscriptions;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Deterministic classifier for the 2026-05-12 forensic re-analysis of
 * subscription_cycles drift (replacing the "fix all 334" broad sweep).
 *
 * Each input row is one drifted SubscriptionCycle enriched with the signals
 * needed to decide WHY it is drifted. Rules are evaluated in order;
 * first match wins. Only `RE_DRIFT` and `CONFIRMED_BUG` are eligible for
 * per-subscription apply — every other class is "do not auto-fix" with a
 * stated, queryable reason.
 *
 * The classifier is pure: no DB, no globals, no time-of-day dependence.
 * The console command + the local HTML renderer + the unit tests all share it.
 */
final class CycleDriftClassifier
{
    public const CLASS_RE_DRIFT = 'RE_DRIFT';

    public const CLASS_SOFT_DELETED_EXPLAINED = 'SOFT_DELETED_EXPLAINED';

    public const CLASS_PRESET_SUSPECT = 'PRESET_SUSPECT';

    public const CLASS_CONFIRMED_BUG = 'CONFIRMED_BUG';

    public const CLASS_FORGIVING_UNDERCOUNT = 'FORGIVING_UNDERCOUNT';

    public const CLASS_PRE_REFACTOR_AMBIGUOUS = 'PRE_REFACTOR_AMBIGUOUS';

    public const CLASS_ARCHIVED_NOISE = 'ARCHIVED_NOISE';

    public const CLASS_NEEDS_REVIEW = 'NEEDS_REVIEW';

    /**
     * The cycle-anchored counting refactor — `subscription_cycle_id` FK
     * landed on this date (migration 2026_05_04_120000) and was backfilled
     * heuristically. Cycles created strictly before this date that show
     * cycle-1 drift are legitimately ambiguous, not bugs.
     */
    public const REFACTOR_BOUNDARY = '2026-05-04';

    /**
     * Classify one drifted cycle.
     *
     * @param  array<string,mixed>  $row  Forensic row keys (see CLAUDE plan §Step 1):
     *   - stored_used (int)
     *   - actual_counted (int)
     *   - soft_deleted_counted (int, default 0)
     *   - prior_repairs (int, default 0)
     *   - shown_exhausted (int|bool, default 0)  — sub.metadata->sessions_exhausted
     *   - purchase_source (string, e.g. 'web'|'admin'|'legacy')
     *   - cycle_number (int)
     *   - cycle_state (string, e.g. 'active'|'queued'|'archived')
     *   - cycle_created_at (string|DateTimeInterface|null)
     * @return array{class:string, gap:int, evidence:list<string>, reason_ar:string}
     */
    public static function classify(array $row): array
    {
        $stored = (int) ($row['stored_used'] ?? 0);
        $actual = (int) ($row['actual_counted'] ?? 0);
        $softDeleted = (int) ($row['soft_deleted_counted'] ?? 0);
        $priorRepairs = (int) ($row['prior_repairs'] ?? 0);
        $shownExhausted = self::toBool($row['shown_exhausted'] ?? false);
        $purchaseSource = strtolower((string) ($row['purchase_source'] ?? ''));
        $cycleNumber = (int) ($row['cycle_number'] ?? 0);
        $cycleState = strtolower((string) ($row['cycle_state'] ?? ''));
        $cycleCreatedAt = self::toDate($row['cycle_created_at'] ?? null);
        $refactorBoundary = new DateTimeImmutable(self::REFACTOR_BOUNDARY);

        $gap = $stored - $actual;
        $evidence = [];

        if ($softDeleted > 0) {
            $evidence[] = "👻 {$softDeleted} soft-deleted";
        }
        if ($priorRepairs >= 1) {
            $evidence[] = "🛠️ repaired ×{$priorRepairs}";
        }
        if ($purchaseSource === 'admin') {
            $evidence[] = '🛠️ admin-preset';
        }
        if ($cycleCreatedAt !== null && $cycleCreatedAt < $refactorBoundary) {
            $evidence[] = '📜 pre-refactor';
        }
        if ($cycleState === 'archived') {
            $evidence[] = '🗄️ archived';
        }
        if ($shownExhausted) {
            $evidence[] = '🔴 shown-exhausted';
        }

        // Rule 1 — RE_DRIFT: previously repaired and drifted again.
        // Top priority because the forward-only fix should have eliminated this.
        if ($priorRepairs >= 1 && $gap !== 0) {
            return [
                'class' => self::CLASS_RE_DRIFT,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => 'تم تصحيح هذه الدورة سابقاً ثم انحرفت مجدداً — مؤشر على وجود خلل نشط يحتاج تحقيقاً فورياً.',
            ];
        }

        // Rule 2 — SOFT_DELETED_EXPLAINED: the gap is fully explained by
        // sessions that were counted and later soft-deleted. Known lifecycle.
        if ($gap > 0 && $softDeleted >= $gap) {
            return [
                'class' => self::CLASS_SOFT_DELETED_EXPLAINED,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => 'الفرق ناتج عن جلسات احتُسبت ثم حُذفت لاحقاً. الحساب صحيح.',
            ];
        }

        // Rule 3 — PRESET_SUSPECT: admin-created sub, cycle 1, positive gap.
        // Drift here is almost certainly the admin's consumed_sessions preset
        // baked into cycle 1 by the legacy materializeFromSubscription code.
        if ($gap > 0 && $purchaseSource === 'admin' && $cycleNumber === 1) {
            return [
                'class' => self::CLASS_PRESET_SUSPECT,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => 'تم إنشاء الاشتراك بواسطة الإدارة مع جلسات مستهلكة سابقاً. الفرق هنا مقصود.',
            ];
        }

        // Rule 4 — CONFIRMED_BUG: high-confidence "fix me" cohort.
        // Must be: positive gap, gap not fully accounted by soft-deletes,
        // no prior repair, currently impacting a student, active cycle.
        // For cycle ≥ 2 we accept admin source too — the admin preset only
        // legitimately lives on cycle 1, so admin drift on a renewed cycle
        // is the same materializeFromSubscription bleed as the Ammar case.
        // For cycle 1 we still require non-admin source (PRESET_SUSPECT
        // already caught the admin cycle-1 case) AND post-refactor creation
        // (so the FK-backfill heuristic ambiguity does not apply).
        $postRefactor = $cycleCreatedAt !== null && $cycleCreatedAt >= $refactorBoundary;
        $isCycleOneFresh = $cycleNumber === 1 && $purchaseSource !== 'admin' && $postRefactor;
        if (
            $gap > 0
            && $softDeleted < $gap
            && $priorRepairs === 0
            && $shownExhausted
            && $cycleState === 'active'
            && ($cycleNumber >= 2 || $isCycleOneFresh)
        ) {
            $reason = $purchaseSource === 'admin'
                ? 'اشتراك إداري على دورة مُجدَّدة (≥ 2) ويظهر للطالب كمستنفد — تسرّب إعداد إداري من الدورة الأولى إلى الدورة الجديدة، نوصي بالتصحيح.'
                : 'الاشتراك مُعلَّم كمستنفد بينما حساب الدورة يفوق عدد الجلسات الفعلية — يطابق نمط حالة عمار، نوصي بالتصحيح.';

            return [
                'class' => self::CLASS_CONFIRMED_BUG,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => $reason,
            ];
        }

        // Rule 5 — FORGIVING_UNDERCOUNT: stored < actual. Student currently
        // sees more remaining sessions than package math allows. Auto-fixing
        // would strip sessions from active students.
        if ($gap < 0) {
            return [
                'class' => self::CLASS_FORGIVING_UNDERCOUNT,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => 'الطالب حالياً يستفيد من جلسات إضافية. تصحيح هذا سيقلّل عدد جلساته المتبقية.',
            ];
        }

        // Rule 6 — PRE_REFACTOR_AMBIGUOUS: cycle-1 drift on a cycle that
        // existed before the 2026-05-04 FK migration. The static snapshot
        // (`cycle.sessions_used` taken at materialization) and the dynamic
        // count via the new FK are allowed to disagree.
        if ($gap > 0 && $cycleNumber === 1 && $cycleCreatedAt !== null && $cycleCreatedAt < $refactorBoundary) {
            return [
                'class' => self::CLASS_PRE_REFACTOR_AMBIGUOUS,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => 'الدورة أُنشئت قبل تحديث 4 مايو لإصلاح ربط الجلسات بالدورات. الأرقام قد تختلف بسبب آلية الربط الجديدة، وليس بسبب خطأ.',
            ];
        }

        // Rule 7 — ARCHIVED_NOISE: anything still drifting in an archived
        // cycle has no current student impact.
        if ($cycleState === 'archived') {
            return [
                'class' => self::CLASS_ARCHIVED_NOISE,
                'gap' => $gap,
                'evidence' => $evidence,
                'reason_ar' => 'دورات قديمة منتهية. لا تأثير على أي طالب الآن.',
            ];
        }

        return [
            'class' => self::CLASS_NEEDS_REVIEW,
            'gap' => $gap,
            'evidence' => $evidence,
            'reason_ar' => 'لم تنطبق أي قاعدة معروفة. تحتاج هذه الحالة مراجعة يدوية من فريق التطوير.',
        ];
    }

    private static function toBool(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v)) {
            return $v !== 0;
        }
        if (is_string($v)) {
            $v = strtolower(trim($v));

            return $v !== '' && $v !== '0' && $v !== 'false' && $v !== 'null';
        }

        return (bool) $v;
    }

    private static function toDate(mixed $v): ?DateTimeImmutable
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof DateTimeImmutable) {
            return $v;
        }
        if ($v instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($v);
        }
        try {
            return new DateTimeImmutable((string) $v);
        } catch (\Throwable) {
            return null;
        }
    }
}
