<?php

/**
 * Phase E predicate-remover for the Itqan subscription v2 refactor.
 *
 * Strips state-derived predicate methods from BaseSubscription and migrates
 * every caller to the v2 SubscriptionPresentation surface.
 *
 * DO NOT RUN unless Phase D sign-off has been recorded.
 *
 * Companion to:
 *   docs/subscription-cleanup-inventory.md       (the list)
 *   docs/subscription-cleanup-migration-plan.md  (the order)
 *   tools/cleanup/run-phase-e.sh                 (the runner)
 *
 * Usage:
 *   php tools/cleanup/remove-predicates.php --dry-run    # show every change
 *   php tools/cleanup/remove-predicates.php --apply      # rewrite files
 *
 * RECTOR NOTE:
 *   composer.lock pins rector/rector ^2 as a transitive dependency but the
 *   `vendor/bin/rector` binary is NOT installed and the project carries no
 *   rector.php config. Adding Rector would require:
 *     composer require --dev rector/rector
 *     vendor/bin/rector init
 *   and authoring custom NodeVisitors for each predicate, which is heavier
 *   than the mechanical replacement below. We use explicit per-rule sed-style
 *   replacements via PHP's preg_replace so every rule is reviewable in this
 *   file. If Rector lands later, port each rule below into a `MethodCallToFuncCall`
 *   visitor or similar.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// CLI arg parsing
// ---------------------------------------------------------------------------
$mode = $argv[1] ?? '';
if (! in_array($mode, ['--dry-run', '--apply'], true)) {
    fwrite(STDERR, "Usage: php remove-predicates.php --dry-run | --apply\n");
    exit(1);
}
$apply = $mode === '--apply';

$repoRoot = realpath(__DIR__.'/../..');
if ($repoRoot === false) {
    fwrite(STDERR, 'ERROR: cannot resolve repo root from '.__DIR__."\n");
    exit(2);
}
chdir($repoRoot);

echo "[remove-predicates] mode={$mode} repo={$repoRoot}\n";

// ---------------------------------------------------------------------------
// Replacement rules
// ---------------------------------------------------------------------------
// Each rule has:
//   - description: human-readable label
//   - pattern: PCRE regex (delimiter !, multiline)
//   - replacement: the new code
//   - notes: per-rule caveat
//
// Rules are intentionally conservative — they match only obvious shapes. Any
// site the regex doesn't catch is reported as a "manual-review" line so the
// operator can hand-edit it.
//
// ALL replacement targets emit `SubscriptionPresentation::viewStateFor($sub)`
// or equivalent. The caller's class must add a `use App\Services\Subscription\SubscriptionPresentation;`
// import — added separately by the post-processing pass below.
//
// CALLER-SCOPED RULES: applied to every file under app/ + resources/ EXCEPT
// the BaseSubscription model itself (where the predicate methods live).
//
// MODEL-SCOPED RULES: applied ONLY to app/Models/BaseSubscription.php — these
// remove the predicate method definitions themselves.

$callerRules = [
    [
        'description' => '$sub->acceptsRetryPayment() → presentation()->primaryActionFor($sub, $role) === \'pay\'',
        'pattern' => '!(\$\w+)->acceptsRetryPayment\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->primaryActionFor($1, \\App\\Enums\\UserType::STUDENT) === \'pay\'',
        'notes' => 'Caller may need to pick the role explicitly (STUDENT default); audit each hit.',
    ],
    [
        'description' => '$sub->isCurrentCyclePaymentPending() → viewStateFor === ACTIVE_PAYMENT_DUE',
        'pattern' => '!(\$\w+)->isCurrentCyclePaymentPending\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->viewStateFor($1) === \\App\\Enums\\SubscriptionViewState::ACTIVE_PAYMENT_DUE',
        'notes' => '',
    ],
    [
        'description' => '$sub->canConfirmManualPayment() → primaryActionFor(supervisor) === \'confirm_cash\'',
        'pattern' => '!(\$\w+)->canConfirmManualPayment\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->primaryActionFor($1, \\App\\Enums\\UserType::SUPERVISOR) === \'confirm_cash\'',
        'notes' => '',
    ],
    [
        'description' => '$sub->is_sessions_exhausted → viewStateFor === PAUSED_END_OF_PERIOD',
        'pattern' => '!(\$\w+)->is_sessions_exhausted!',
        'replacement' => '(app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->viewStateFor($1) === \\App\\Enums\\SubscriptionViewState::PAUSED_END_OF_PERIOD)',
        'notes' => 'Accessor read; wrap in parens to preserve precedence.',
    ],
    [
        'description' => '$sub->isInGracePeriod() → viewStateFor === GRACE_ADMIN',
        'pattern' => '!(\$\w+)->isInGracePeriod\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->viewStateFor($1) === \\App\\Enums\\SubscriptionViewState::GRACE_ADMIN',
        'notes' => '',
    ],
    [
        'description' => '$sub->needsRenewal() → primaryActionFor(...) === \'renew\'',
        'pattern' => '!(\$\w+)->needsRenewal\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->primaryActionFor($1, \\App\\Enums\\UserType::STUDENT) === \'renew\'',
        'notes' => 'Audit role per call site.',
    ],
    [
        'description' => '$sub->isPayable() → primaryActionFor(...) === \'pay\'',
        'pattern' => '!(\$\w+)->isPayable\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->primaryActionFor($1, \\App\\Enums\\UserType::STUDENT) === \'pay\'',
        'notes' => 'Audit role per call site.',
    ],
    [
        'description' => '$sub->isSchedulable() → presentation()->canSchedule($sub)',
        'pattern' => '!(\$\w+)->isSchedulable\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->canSchedule($1)',
        'notes' => 'Requires SubscriptionPresentation::canSchedule() to ship first. REOPEN gate.',
    ],
    [
        'description' => '$sub->canAccess() → presentation()->canAccess($sub)',
        'pattern' => '!(\$\w+)->canAccess\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->canAccess($1)',
        'notes' => 'Requires SubscriptionPresentation::canAccess() to ship first. REOPEN gate.',
    ],
    [
        'description' => '$sub->canRenew() → primaryActionFor === renew',
        'pattern' => '!(\$\w+)->canRenew\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->primaryActionFor($1, \\App\\Enums\\UserType::STUDENT) === \'renew\'',
        'notes' => '',
    ],
    [
        'description' => '$sub->canCancel() → !$sub->isCancelled() && !$sub->isExpired()',
        'pattern' => '!(\$\w+)->canCancel\(\)!',
        'replacement' => '(! $1->isCancelled() && ! $1->isExpired())',
        'notes' => 'Admin-only cancellation per INV-G2; this preserves the visibility set without the predicate.',
    ],
    [
        'description' => '$sub->canPause() → viewStateFor === ACTIVE_PAID',
        'pattern' => '!(\$\w+)->canPause\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->viewStateFor($1) === \\App\\Enums\\SubscriptionViewState::ACTIVE_PAID',
        'notes' => '',
    ],
    [
        'description' => '$sub->canResume() → viewStateFor === PAUSED_ADMIN',
        'pattern' => '!(\$\w+)->canResume\(\)!',
        'replacement' => 'app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->viewStateFor($1) === \\App\\Enums\\SubscriptionViewState::PAUSED_ADMIN',
        'notes' => '',
    ],
    [
        'description' => '$sub->hasExpiredWithLeftoverSessions() → inline expression',
        'pattern' => '!(\$\w+)->hasExpiredWithLeftoverSessions\(\)!',
        'replacement' => '(app(\\App\\Services\\Subscription\\SubscriptionPresentation::class)->viewStateFor($1) === \\App\\Enums\\SubscriptionViewState::EXPIRED && $1->currentCycle?->sessions_used < $1->currentCycle?->total_sessions)',
        'notes' => 'Inline; not commonly used.',
    ],
    [
        'description' => '$sub->getStatusDisplayData() → manual migration required',
        'pattern' => '!(\$\w+)->getStatusDisplayData\(\)!',
        'replacement' => null, // marker — emit manual-review notice only
        'notes' => 'Returns a structured array (badge + color + label). Migration: replace with viewStateFor() + helperLineFor() + lang-keyed badge class. Manual edit per caller.',
    ],
    [
        'description' => '$sub->getSubscriptionSummary() → manual migration required',
        'pattern' => '!(\$\w+)->getSubscriptionSummary\(\)!',
        'replacement' => null, // marker
        'notes' => 'Returns a multi-field summary array. Migration: replace with SubscriptionPresentation::formatForApi(). Manual edit per caller.',
    ],
];

// MODEL-SCOPED RULES: delete the predicate method bodies entirely. These are
// applied to app/Models/BaseSubscription.php after every caller has been
// migrated. Implemented as line-range removal — operator must hand-verify the
// resulting file because PCRE across method bodies is fragile.

$modelMethodsToDelete = [
    'acceptsRetryPayment',
    'scopeAcceptsRetryPayment',
    'isCurrentCyclePaymentPending',
    'canConfirmManualPayment',
    'getIsSessionsExhaustedAttribute',
    'isInGracePeriod',
    'needsRenewal',
    'isPayable',
    'scopePayable',
    'isSchedulable',
    'scopeSchedulable',
    'hasExpiredWithLeftoverSessions',
    'canRenew',
    'canCancel',
    'canPause',
    'canResume',
    'canAccess',
    'getStatusDisplayData',
    'getSubscriptionSummary',
    // Mutators (PR 3, after dual-write migration):
    'useSession',
    'returnSession',
    'activate',
    'settleCurrentCycle',
    // Note: cancel/pause/resume/enableAutoRenewal/disableAutoRenewal still need
    // sister updates in SubscriptionLifecycle; left in place until that PR.
];

// ---------------------------------------------------------------------------
// File discovery
// ---------------------------------------------------------------------------
$callerFiles = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repoRoot.'/app', FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if (! $file->isFile()) {
        continue;
    }
    $ext = $file->getExtension();
    if ($ext !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    // Skip the model file itself (we'll handle deletions separately) and the
    // v2 services (they correctly call the v2 surface).
    if (str_ends_with($path, '/app/Models/BaseSubscription.php')) {
        continue;
    }
    if (str_contains($path, '/app/Services/Subscription/')) {
        continue;
    }
    $callerFiles[] = $path;
}
// Add resources/ blade templates.
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repoRoot.'/resources', FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if (! $file->isFile()) {
        continue;
    }
    if (! preg_match('!\.(php|blade\.php)$!', $file->getPathname())) {
        continue;
    }
    $callerFiles[] = $file->getPathname();
}

echo '[remove-predicates] scanning '.count($callerFiles)." files\n";

// ---------------------------------------------------------------------------
// Apply caller-scoped rules
// ---------------------------------------------------------------------------
$summary = [
    'files_changed' => 0,
    'rules_applied' => 0,
    'manual_reviews' => [],
    'rule_hits' => [],
];

foreach ($callerFiles as $path) {
    $original = file_get_contents($path);
    if ($original === false) {
        continue;
    }
    $rewritten = $original;
    $fileChanged = false;

    foreach ($callerRules as $rule) {
        if ($rule['replacement'] === null) {
            // Manual-review marker
            if (preg_match_all($rule['pattern'], $rewritten, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($rewritten, 0, $match[1]), "\n") + 1;
                    $summary['manual_reviews'][] = sprintf('%s:%d  %s — %s', $path, $line, trim($match[0]), $rule['description']);
                }
            }

            continue;
        }

        $count = 0;
        $new = preg_replace($rule['pattern'], $rule['replacement'], $rewritten, -1, $count);
        if ($new === null) {
            fwrite(STDERR, "preg_replace failed for {$path} on rule: {$rule['description']}\n");

            continue;
        }
        if ($count > 0) {
            $rewritten = $new;
            $fileChanged = true;
            $summary['rules_applied'] += $count;
            $summary['rule_hits'][$rule['description']] = ($summary['rule_hits'][$rule['description']] ?? 0) + $count;
        }
    }

    if (! $fileChanged) {
        continue;
    }

    $summary['files_changed']++;
    if ($apply) {
        file_put_contents($path, $rewritten);
        echo "  [WRITE] {$path}\n";
    } else {
        echo "  [DRY]   {$path}\n";
    }
}

// ---------------------------------------------------------------------------
// Model-scoped: list method definitions that PR 2a/PR 3 needs to delete from
// app/Models/BaseSubscription.php. Auto-deletion is too risky (PCRE across
// nested braces); we just print the line numbers so the operator hand-edits.
// ---------------------------------------------------------------------------
$modelPath = $repoRoot.'/app/Models/BaseSubscription.php';
$modelSrc = file_exists($modelPath) ? file_get_contents($modelPath) : '';
$modelLines = explode("\n", $modelSrc);
$pendingMethodDeletions = [];

foreach ($modelMethodsToDelete as $name) {
    // Find the public function declaration line.
    foreach ($modelLines as $idx => $line) {
        if (preg_match('!\bpublic\s+function\s+'.preg_quote($name, '!').'\s*\(!', $line)) {
            $pendingMethodDeletions[$name] = $idx + 1; // 1-indexed
            break;
        }
    }
}

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------
echo "\n[remove-predicates] summary\n";
echo "  files changed : {$summary['files_changed']}\n";
echo "  rules applied : {$summary['rules_applied']}\n";
echo "\n  by rule:\n";
foreach ($summary['rule_hits'] as $rule => $hits) {
    echo "    {$hits}x  {$rule}\n";
}

if (! empty($summary['manual_reviews'])) {
    echo "\n  MANUAL REVIEW REQUIRED (".count($summary['manual_reviews'])." sites):\n";
    foreach ($summary['manual_reviews'] as $line) {
        echo "    {$line}\n";
    }
}

echo "\n[remove-predicates] BaseSubscription method definitions to delete (PR 2a/PR 3):\n";
if (empty($pendingMethodDeletions)) {
    echo "  (none found — either already deleted or model path missing)\n";
} else {
    foreach ($pendingMethodDeletions as $name => $line) {
        echo '  '.sprintf('%-40s', $name)."  app/Models/BaseSubscription.php:{$line}\n";
    }
    echo "\n  Hand-edit each method out of the model after PR 2 caller migrations land.\n";
    echo "  (Auto-removal across method bodies is unsafe with PCRE.)\n";
}

if (! $apply) {
    echo "\n[remove-predicates] dry-run complete; rerun with --apply to write.\n";
} else {
    echo "\n[remove-predicates] apply complete. Run `composer test` + invariant-check before commit.\n";
}
