<?php

namespace Tests\Architecture\Helpers;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Filesystem helpers for Pest architecture tests.
 *
 * Scans `.php` files under `app/` (or any sub-path) and exposes regex hit
 * detection, while honoring the Phase A.8 inventory's skip list:
 *
 *  - `app/Console/Commands/Archived/**`        — already archived
 *  - `app/Console/Commands/Backfill/**`        — Phase E deletion candidates
 *  - `app/Console/Commands/Subscriptions/Bootstrap*` — one-time bootstrappers
 *  - `app/Console/Commands/AuditDataIntegrity.php`   — legacy audit
 *  - `app/Console/Commands/BackfillSessionCycles.php`
 *  - `app/Console/Commands/BackfillTeacherAttendance.php`
 *  - `app/Console/Commands/BackfillAttendanceMatrixData.php`
 *  - `app/Console/Commands/DiagnoseAttendanceHealth.php`
 *  - `app/Console/Commands/RepairAttendanceDataCommand.php`
 *  - `app/Console/Commands/AuditFqcnAliasEarningPairsCommand.php`
 *
 * Skipped paths are tracked in `docs/subscription-convention-sweep-inventory.md`.
 */
final class SourceScanner
{
    /**
     * Files (basenames or partial paths) inside `app/Console/Commands/`
     * that are excluded from the convention sweep because they're
     * scheduled for deletion or archive in Phase E.
     *
     * @var string[]
     */
    private const LEGACY_COMMAND_BASENAMES = [
        'AuditDataIntegrity.php',
        'BackfillSessionCycles.php',
        'BackfillTeacherAttendance.php',
        'BackfillAttendanceMatrixData.php',
        'DiagnoseAttendanceHealth.php',
        'RepairAttendanceDataCommand.php',
        'AuditFqcnAliasEarningPairsCommand.php',
        'AuditCycleCounts.php',
        'DiagnoseCycleDriftCommand.php',
        'FindStudentCircles.php',
        'FixPostRecoveryData.php',
        'FixPostRecoveryPaymentStatus.php',
        'FixSessionDurations.php',
        'FixAbsentSessionsWithAttendance.php',
        'RecalculateTeacherProfileCounters.php',
        'ScanApiEndpoints.php',
        'DeploySubscriptionCycles.php',
        'CleanupAbandonedQueuedCycles.php',
        'CleanupExpiredPendingSubscriptions.php',
    ];

    /**
     * Sub-paths (relative to the app/ root) whose entire subtree is skipped.
     *
     * @var string[]
     */
    private const SKIPPED_SUBPATHS = [
        'Console/Commands/Archived/',
        'Console/Commands/Backfill/',
    ];

    /**
     * Files whose names start with these prefixes inside
     * `app/Console/Commands/Subscriptions/` are skipped (bootstrap rows).
     *
     * @var string[]
     */
    private const BOOTSTRAP_PREFIXES = [
        'Bootstrap',
    ];

    /**
     * Iterate every `.php` file under the given app-relative directory,
     * honoring the skip list.
     *
     * @param  string  $appRelativeDir  e.g. '' for the whole `app/`, or 'Services/Subscription'
     * @return iterable<SplFileInfo>
     */
    public static function appFiles(string $appRelativeDir = ''): iterable
    {
        $appRoot = base_path('app');
        $start = $appRelativeDir === '' ? $appRoot : $appRoot.DIRECTORY_SEPARATOR.$appRelativeDir;

        if (! is_dir($start)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($start, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (self::isSkipped($file)) {
                continue;
            }
            yield $file;
        }
    }

    /**
     * Find regex matches across every non-skipped `app/` file.
     *
     * Returns a list of `relative_path:line` hits suitable for assertion messages.
     *
     * @return string[]
     */
    public static function findMatches(string $pcre, string $appRelativeDir = ''): array
    {
        $hits = [];
        foreach (self::appFiles($appRelativeDir) as $file) {
            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }
            $lines = explode("\n", $contents);
            foreach ($lines as $i => $line) {
                if (preg_match($pcre, $line)) {
                    $hits[] = self::relativePath($file).':'.($i + 1).' — '.trim($line);
                }
            }
        }

        return $hits;
    }

    /**
     * For every concrete PHP class file under `app/Services/Subscription/`
     * (excluding `Concerns/` and skipped service classes), return method
     * bodies that look like public mutators per the v2 naming pattern.
     *
     * @return array<string,array{file:string, method:string, body:string}>
     */
    public static function subscriptionServiceMutatorBodies(): array
    {
        $mutatorNames = [
            'create', 'activate', 'pause', 'resume', 'extend', 'cancel',
            'renew', 'resubscribe', 'expire', 'advanceCycle',
            'markCyclePaid', 'markCycleFailed', 'confirmCashPayment',
            'record', 'reverse', 'applyOverride',
        ];

        $excludedClasses = [
            'SubscriptionInvariantChecker',
            'SubscriptionPresentation', // Phase A.5 leaf, read-only derivations
            'SubscriptionPricing', // Phase A.5 leaf service
            'SubscriptionReconciler', // the reconciler itself
            'PricingResolver', // pure pricing math
            'SubscriptionQueryService', // read-only
            'SubscriptionAnalyticsService', // read-only
            'SubscriptionTypeResolver', // pure mapping
            'SubscriptionFailureCounter', // small counter helper
            'ExpiryReminderService', // notification dispatcher only
            // Pre-A.8 LEGACY mutators scheduled for migration / deletion in a
            // later A-series phase (mirrors the allowlist in the
            // "no raw writes to derived subscription fields" test). Once
            // these are folded into SubscriptionLifecycle / SubscriptionPayment
            // they get removed from this list and the lock invariant applies.
            'SubscriptionCreationService',
            'SubscriptionRenewalService',
            'SubscriptionMaintenanceService',
            'AdminSubscriptionWizardService',
        ];

        $results = [];
        foreach (self::appFiles('Services/Subscription') as $file) {
            $rel = self::relativePath($file);
            // Skip Concerns/ trait files (not concrete services).
            if (str_contains($rel, '/Concerns/')) {
                continue;
            }

            $className = $file->getBasename('.php');
            if (in_array($className, $excludedClasses, true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }

            // Skip abstract classes.
            if (preg_match('/\babstract\s+class\s+/', $contents)) {
                continue;
            }

            foreach ($mutatorNames as $name) {
                // Regex matches `public function <name>(` to find the method.
                $pattern = '/public\s+function\s+'.preg_quote($name, '/').'\s*\(/';
                if (! preg_match($pattern, $contents, $m, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                $startOffset = $m[0][1];
                $body = self::extractMethodBody($contents, $startOffset);
                if ($body === null) {
                    continue;
                }
                $results[] = [
                    'file' => $rel,
                    'method' => $name,
                    'body' => $body,
                ];
            }
        }

        return $results;
    }

    /**
     * Extract everything between the first `{` after the method signature and
     * the matching closing `}`. Returns null if no balanced brace pair is found.
     */
    private static function extractMethodBody(string $contents, int $signatureStart): ?string
    {
        $openBrace = strpos($contents, '{', $signatureStart);
        if ($openBrace === false) {
            return null;
        }

        $depth = 0;
        $len = strlen($contents);
        for ($i = $openBrace; $i < $len; $i++) {
            $ch = $contents[$i];
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($contents, $openBrace, $i - $openBrace + 1);
                }
            }
        }

        return null;
    }

    private static function isSkipped(SplFileInfo $file): bool
    {
        $relative = self::relativePath($file);

        foreach (self::SKIPPED_SUBPATHS as $sub) {
            if (str_contains($relative, $sub)) {
                return true;
            }
        }

        $basename = $file->getBasename();

        if (in_array($basename, self::LEGACY_COMMAND_BASENAMES, true)) {
            return true;
        }

        if (str_contains($relative, 'Console/Commands/Subscriptions/')) {
            foreach (self::BOOTSTRAP_PREFIXES as $prefix) {
                if (str_starts_with($basename, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return a path relative to the project root (e.g. `app/Models/BaseSubscription.php`).
     */
    public static function relativePath(SplFileInfo $file): string
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        $real = $file->getPathname();

        if (str_starts_with($real, $base)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($base)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $real);
    }
}
