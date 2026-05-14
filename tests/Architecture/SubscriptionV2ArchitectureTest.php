<?php

/**
 * Subscription V2 Architecture Tests (Phase A.8)
 *
 * Enforces the conventions established by the v2 refactor:
 *
 *  1. Subscription/payment/cycle status queries MUST use enum references,
 *     never raw string literals.
 *  2. `$type === 'quran'|'academic'|'course'` comparisons MUST use the
 *     `SubscriptionType` enum.
 *  3. Every mutator in the v2 subscription services MUST acquire
 *     `SubscriptionLock::for(...)` (per INV-C1 in `docs/subscription-invariants.md`).
 *  4. Derived subscription-row fields (`payment_status`, `sessions_used`,
 *     `sessions_remaining`, `total_sessions`, `starts_at`, `ends_at`) MUST
 *     only be written from `SubscriptionReconciler::sync` (per INV-A1).
 *
 * Skipped paths (test, migration, legacy backfill, bootstrap commands) are
 * recorded in `docs/subscription-convention-sweep-inventory.md`.
 */

use Tests\Architecture\Helpers\SourceScanner;

require_once __DIR__.'/Helpers/SourceScanner.php';

test('no raw string status/payment_status/cycle_state queries in app/ outside skipped paths', function () {
    $pattern = '/->where\(\s*[\'"](status|payment_status|cycle_state)[\'"]\s*,\s*[\'"][a-z_]+[\'"]\s*\)/';

    $hits = SourceScanner::findMatches($pattern);

    // The remaining hits are all on non-subscription columns (session
    // statuses, circle enrollment, course-status, homework-status, etc.) —
    // false-positives by domain. Each surviving hit must be EITHER on a
    // table not belonging to the subscription domain, OR already an enum
    // reference (the pattern intentionally only catches lowercase literal
    // values, so `SessionStatus::COMPLETED->value` is fine).
    //
    // If a NEW subscription-table site sneaks in, list it in the inventory
    // and codemod it. This assertion just guards against drift.
    $subscriptionRelevant = array_filter($hits, function (string $hit) {
        // Heuristic: subscription-targeted sites mention a subscription
        // model class, the `subscriptions` or `cycles` relationship, or a
        // payments() relation. None of the surviving false-positives match
        // these substrings on the same line.
        return preg_match('/(QuranSubscription|AcademicSubscription|CourseSubscription|BaseSubscription|SubscriptionCycle|->subscriptions\(|->cycles\(|->payments\()/', $hit) === 1;
    });

    expect($subscriptionRelevant)
        ->toBeEmpty('Subscription-targeted status queries must use the SubscriptionType / SessionSubscriptionStatus / SubscriptionPaymentStatus / SubscriptionCycle::* enum, not string literals. Offending sites: '.PHP_EOL.implode(PHP_EOL, $subscriptionRelevant));
});

test('no raw $type === quran|academic|course comparisons in app/ outside skipped paths', function () {
    $pattern = '/\$type\s*===\s*[\'"](quran|academic|course)[\'"]/';

    $hits = SourceScanner::findMatches($pattern);

    expect($hits)
        ->toBeEmpty('Use SubscriptionType::QURAN->value / ::ACADEMIC->value / ::COURSE->value instead of raw string comparisons. Offending sites: '.PHP_EOL.implode(PHP_EOL, $hits));
});

test('every v2 subscription mutator acquires SubscriptionLock::for(', function () {
    $bodies = SourceScanner::subscriptionServiceMutatorBodies();

    // A v2 mutator may acquire the lock directly, or delegate to another v2
    // mutator that does. Cache::lock is NOT re-entrant under Laravel's atomic
    // lock contract, so a routing wrapper (e.g. confirmCashPayment) must
    // pick exactly one downstream branch and let IT acquire the lock —
    // wrapping the wrapper would deadlock against its own callee.
    $delegatedMutators = [
        'markCyclePaid', 'markCycleFailed', 'confirmCashPayment',
        'pause', 'resume', 'cancel', 'extend', 'activate', 'expire',
        'renew', 'resubscribe', 'advanceCycle',
        'record', 'reverse', 'applyOverride', 'create',
    ];

    $violations = [];

    foreach ($bodies as $entry) {
        if (str_contains($entry['body'], 'SubscriptionLock::for(')) {
            continue;
        }

        // Accept delegation to another v2 mutator as evidence of locking.
        $delegates = false;
        foreach ($delegatedMutators as $name) {
            if ($name === $entry['method']) {
                continue;
            }
            if (preg_match('/->'.preg_quote($name, '/').'\s*\(/', $entry['body'])) {
                $delegates = true;
                break;
            }
        }

        if (! $delegates) {
            $violations[] = $entry['file'].'::'.$entry['method'].'()';
        }
    }

    expect($violations)
        ->toBeEmpty('Per INV-C1 every public mutator on a v2 subscription service must call SubscriptionLock::for(...) inside its body, or delegate to a v2 mutator that does. Methods missing the lock: '.PHP_EOL.implode(PHP_EOL, $violations));
});

test('no raw writes to derived subscription fields outside SubscriptionReconciler', function () {
    // Derived fields per INV-A1 that may only be written from
    // SubscriptionReconciler::sync().
    $derivedFields = ['payment_status', 'sessions_used', 'sessions_remaining', 'total_sessions', 'starts_at', 'ends_at'];
    $alternation = implode('|', $derivedFields);

    // Detects: ->update(['<field>' ... — the start of an array literal mass-update.
    $updatePattern = '/->update\(\s*\[\s*[\'"]('.$alternation.')[\'"]\s*=>/';

    $hits = SourceScanner::findMatches($updatePattern);

    // Allow: the Reconciler itself + the BaseSubscription model's own
    // lifecycle helpers (activate / cancel / pause / resume / settleCurrentCycle
    // / useSession / returnSession). Those existing writers will be replaced
    // by Reconciler-driven writes in a later phase; for now we only enforce
    // that NEW code outside those known sites doesn't add raw writes.
    $allowedFiles = [
        'app/Services/Subscription/SubscriptionReconciler.php',
        'app/Models/BaseSubscription.php',
        'app/Models/QuranSubscription.php',
        'app/Models/AcademicSubscription.php',
        'app/Models/CourseSubscription.php',
        'app/Models/SubscriptionCycle.php',
        // Pre-A.8 writers, scheduled for migration in a later A-series phase.
        'app/Services/Subscription/SubscriptionCreationService.php',
        'app/Services/Subscription/SubscriptionRenewalService.php',
        'app/Services/Subscription/SubscriptionMaintenanceService.php',
        'app/Services/Subscription/AdminSubscriptionWizardService.php',
        'app/Services/Subscription/SubscriptionConsumption.php',
    ];

    $violations = array_filter($hits, function (string $hit) use ($allowedFiles) {
        // The hit format is `<relative_path>:<line> — <code>`.
        foreach ($allowedFiles as $allowed) {
            if (str_starts_with($hit, $allowed.':')) {
                return false;
            }
        }

        // Only flag writes that look like they target a subscription row;
        // session / payment / cycle writes are out-of-scope for this rule.
        return preg_match('/(QuranSubscription|AcademicSubscription|CourseSubscription|BaseSubscription|->subscription(s)?\b)/', $hit) === 1;
    });

    expect($violations)
        ->toBeEmpty('Per INV-A1 only SubscriptionReconciler::sync may write to derived subscription fields. Offending sites: '.PHP_EOL.implode(PHP_EOL, $violations));
});
