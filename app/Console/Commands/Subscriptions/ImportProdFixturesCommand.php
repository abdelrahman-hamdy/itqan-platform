<?php

namespace App\Console\Commands\Subscriptions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Phase D — LOCAL-ONLY fixture importer.
 *
 * Reads a curated list of broken-shape subscription IDs from
 * tests/Fixtures/Subscription/prod-shape-manifest.json and copies the matching
 * rows (subscription + cycles + payments + owning user/student-profile) from a
 * `prod_snapshot` DB connection into the local default connection. PII is
 * sanitised on the way in.
 *
 * The operator MUST:
 *   1. Snapshot prod via mysqldump (NEVER let this command query live prod).
 *   2. Load the dump into a local DB.
 *   3. Add a `prod_snapshot` connection to their local config/database.php
 *      per docs/subscription-prod-observation-runbook.md. The connection is
 *      intentionally NOT pre-shipped in config/database.php so the importer
 *      cannot run against an unintended target.
 *
 * Safety guards baked in:
 *   - Refuses to run unless config('database.connections.prod_snapshot') is set.
 *   - --dry-run prints the plan and exits without writing.
 *   - Idempotent: skips IDs already present in the local DB.
 *   - PII sanitisation rewrites email/name/phone for every imported user.
 *   - --limit caps how many rows per category to import (default 10).
 *
 * Usage:
 *   php artisan subscriptions:import-prod-fixtures --dry-run
 *   php artisan subscriptions:import-prod-fixtures --source=prod --limit=5
 */
class ImportProdFixturesCommand extends Command
{
    protected $signature = 'subscriptions:import-prod-fixtures
                            {--source=prod : Logical source label written into the audit trail (informational only)}
                            {--dry-run : Print the import plan but do not write to the local DB}
                            {--limit=10 : Maximum rows per category to import}';

    protected $description = 'LOCAL-ONLY. Import broken-shape subscription fixtures from prod_snapshot DB with sanitised PII.';

    private const MANIFEST_PATH = 'tests/Fixtures/Subscription/prod-shape-manifest.json';

    private const SNAPSHOT_CONNECTION = 'prod_snapshot';

    /**
     * Subscription type → Eloquent table name mapping. Mirrors
     * QuranSubscription / AcademicSubscription / CourseSubscription $table.
     */
    private const SUBSCRIPTION_TABLES = [
        'quran' => 'quran_subscriptions',
        'academic' => 'academic_subscriptions',
        'course' => 'course_subscriptions',
    ];

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        $manifest = $this->loadManifest();
        if ($manifest === null) {
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $isDryRun = (bool) $this->option('dry-run');
        $source = (string) $this->option('source');

        $this->line(sprintf(
            'Importing prod fixtures (source=%s, limit-per-category=%d, dry-run=%s)',
            $source,
            $limit,
            $isDryRun ? 'yes' : 'no',
        ));
        $this->newLine();

        $totals = [
            'planned' => 0,
            'imported' => 0,
            'skipped_existing' => 0,
            'skipped_missing_in_snapshot' => 0,
            'errors' => 0,
        ];

        foreach ($manifest as $category => $entries) {
            if (str_starts_with((string) $category, '_')) {
                continue; // _notes etc.
            }

            if (! is_array($entries) || empty($entries)) {
                $this->line(sprintf('  [%s] (empty — populate the manifest after a prod snapshot)', $category));

                continue;
            }

            $this->info(sprintf('Category: %s (%d candidates)', $category, count($entries)));

            $sliced = array_slice($entries, 0, $limit);
            foreach ($sliced as $entry) {
                $totals['planned']++;

                try {
                    $result = $this->importEntry($entry, $category, $isDryRun);
                    $totals[$result]++;
                } catch (Throwable $e) {
                    $this->error(sprintf('    × %s', $e->getMessage()));
                    $totals['errors']++;
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            collect($totals)->map(fn ($v, $k) => [$k, (int) $v])->values()->all(),
        );

        if ($isDryRun) {
            $this->warn('Dry run — nothing was written. Re-run without --dry-run to apply.');
        }

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Hard refuse to run unless the operator has set up a prod_snapshot DB
     * connection AND we are NOT running on a production-like environment. We
     * cross-check APP_ENV to avoid the foot-gun of running this on prod even
     * if someone accidentally configured the snapshot connection there.
     */
    private function guardEnvironment(): bool
    {
        $env = strtolower((string) config('app.env'));
        if (in_array($env, ['production', 'prod'], true)) {
            $this->error('Refusing to run on a production environment (APP_ENV='.$env.').');
            $this->error('This command is LOCAL-ONLY. See docs/subscription-prod-observation-runbook.md.');

            return false;
        }

        if (config('database.connections.'.self::SNAPSHOT_CONNECTION) === null) {
            $this->error(sprintf(
                "No '%s' DB connection configured. Add one to your local config/database.php\nper docs/subscription-prod-observation-runbook.md. Aborting.",
                self::SNAPSHOT_CONNECTION,
            ));

            return false;
        }

        return true;
    }

    /**
     * Load and validate the manifest JSON.
     *
     * @return array<string, array<int, array{type: string, id: int, note?: string}>>|null
     */
    private function loadManifest(): ?array
    {
        $path = base_path(self::MANIFEST_PATH);
        if (! is_file($path)) {
            $this->error('Manifest file not found at '.$path);

            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->error('Could not read manifest at '.$path);

            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            $this->error('Manifest JSON is not a valid object at '.$path);

            return null;
        }

        return $decoded;
    }

    /**
     * Import a single manifest entry. Returns one of:
     *   'imported', 'skipped_existing', 'skipped_missing_in_snapshot'.
     *
     * Order of operations within a transaction:
     *   1. Locate the subscription row in the snapshot.
     *   2. If the local DB already has that (type, id) pair, skip.
     *   3. Copy the owning user + student_profile (sanitising PII) if absent.
     *   4. Copy the subscription row.
     *   5. Copy related subscription_cycles + payments (best-effort by FK).
     *
     * @param  array{type: string, id: int, note?: string}  $entry
     */
    private function importEntry(array $entry, string $category, bool $isDryRun): string
    {
        $type = $entry['type'] ?? null;
        $id = isset($entry['id']) ? (int) $entry['id'] : 0;
        $note = (string) ($entry['note'] ?? '');

        if (! is_string($type) || ! isset(self::SUBSCRIPTION_TABLES[$type]) || $id <= 0) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid manifest entry in [%s]: %s',
                $category,
                json_encode($entry, JSON_UNESCAPED_UNICODE),
            ));
        }

        $table = self::SUBSCRIPTION_TABLES[$type];

        // Lookup in snapshot first; if absent, surface as skipped (manifest drift).
        $snapshotRow = DB::connection(self::SNAPSHOT_CONNECTION)
            ->table($table)
            ->where('id', $id)
            ->first();

        if ($snapshotRow === null) {
            $this->line(sprintf('    - %s#%d (%s): not found in snapshot — skipping', $type, $id, $note));

            return 'skipped_missing_in_snapshot';
        }

        // Idempotency check on the local DB.
        $existsLocally = DB::table($table)->where('id', $id)->exists();
        if ($existsLocally) {
            $this->line(sprintf('    - %s#%d (%s): already present locally — skipping', $type, $id, $note));

            return 'skipped_existing';
        }

        if ($isDryRun) {
            $this->line(sprintf('    ✓ %s#%d (%s): would import', $type, $id, $note));

            return 'imported';
        }

        DB::transaction(function () use ($snapshotRow, $table, $type, $id) {
            $this->importOwningUser((array) $snapshotRow);
            DB::table($table)->insert((array) $snapshotRow);
            $this->importRelatedCycles($type, $id);
            $this->importRelatedPayments($type, $id);
        });

        $this->line(sprintf('    ✓ %s#%d (%s): imported', $type, $id, $note));

        return 'imported';
    }

    /**
     * Copy the owning user (and student_profile if present) for a subscription
     * row, replacing PII with deterministic test values. Idempotent.
     *
     * The subscription tables all carry `student_id` pointing at `users.id`
     * (per MEMORY.md → DB Schema Notes). We follow that single FK.
     */
    private function importOwningUser(array $subscriptionRow): void
    {
        $userId = $subscriptionRow['student_id'] ?? null;
        if ($userId === null) {
            return;
        }
        $userId = (int) $userId;

        if (DB::table('users')->where('id', $userId)->exists()) {
            return; // already imported on a prior run
        }

        $snapshotUser = DB::connection(self::SNAPSHOT_CONNECTION)
            ->table('users')
            ->where('id', $userId)
            ->first();

        if ($snapshotUser === null) {
            return; // dangling FK in snapshot — let the importer log a warning later
        }

        $sanitised = (array) $snapshotUser;
        $sanitised['email'] = sprintf('fixture-%d@example.test', $userId);
        $sanitised['name'] = sprintf('Fixture Student %d', $userId);
        // Optional columns — clear if present.
        foreach (['phone', 'whatsapp_number', 'mobile', 'guardian_phone', 'parent_phone'] as $col) {
            if (array_key_exists($col, $sanitised)) {
                $sanitised[$col] = null;
            }
        }
        // Replace password with an unusable bcrypt placeholder so no one ever
        // logs in with a fixture account.
        if (array_key_exists('password', $sanitised)) {
            $sanitised['password'] = '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinv';
        }
        if (array_key_exists('remember_token', $sanitised)) {
            $sanitised['remember_token'] = null;
        }

        DB::table('users')->insert($sanitised);

        // Mirror the student_profile row if the schema has one (it always
        // exists on this codebase, but be defensive).
        if (! Schema::hasTable('student_profiles')) {
            return;
        }

        $snapshotProfile = DB::connection(self::SNAPSHOT_CONNECTION)
            ->table('student_profiles')
            ->where('user_id', $userId)
            ->first();

        if ($snapshotProfile === null) {
            return;
        }

        $profile = (array) $snapshotProfile;
        // The profile may carry PII as well.
        foreach (['guardian_name', 'parent_name', 'emergency_contact', 'address'] as $col) {
            if (array_key_exists($col, $profile)) {
                $profile[$col] = null;
            }
        }

        DB::table('student_profiles')->insert($profile);
    }

    /**
     * Copy the subscription_cycles rows that anchor on this subscription.
     * Polymorphic table — keyed by (subscription_type, subscription_id).
     */
    private function importRelatedCycles(string $type, int $id): void
    {
        if (! Schema::hasTable('subscription_cycles')) {
            return;
        }

        $modelClass = $this->modelClassForType($type);

        $cycles = DB::connection(self::SNAPSHOT_CONNECTION)
            ->table('subscription_cycles')
            ->where('subscription_type', $modelClass)
            ->where('subscription_id', $id)
            ->get();

        foreach ($cycles as $cycle) {
            $cycleArr = (array) $cycle;
            $cycleId = $cycleArr['id'] ?? null;
            if ($cycleId === null) {
                continue;
            }
            if (DB::table('subscription_cycles')->where('id', $cycleId)->exists()) {
                continue;
            }
            DB::table('subscription_cycles')->insert($cycleArr);
        }
    }

    /**
     * Copy related payment rows (best-effort — fragile across schema versions,
     * so we only insert if the local table accepts the payload).
     */
    private function importRelatedPayments(string $type, int $id): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $modelClass = $this->modelClassForType($type);

        $payments = DB::connection(self::SNAPSHOT_CONNECTION)
            ->table('payments')
            ->where('payable_type', $modelClass)
            ->where('payable_id', $id)
            ->get();

        foreach ($payments as $payment) {
            $arr = (array) $payment;
            $pid = $arr['id'] ?? null;
            if ($pid === null || DB::table('payments')->where('id', $pid)->exists()) {
                continue;
            }
            try {
                DB::table('payments')->insert($arr);
            } catch (Throwable $e) {
                $this->warn(sprintf(
                    '      ! payment#%d for %s#%d failed to import: %s',
                    (int) $pid,
                    $type,
                    $id,
                    $e->getMessage(),
                ));
            }
        }
    }

    private function modelClassForType(string $type): string
    {
        return match ($type) {
            'quran' => \App\Models\QuranSubscription::class,
            'academic' => \App\Models\AcademicSubscription::class,
            'course' => \App\Models\CourseSubscription::class,
            default => throw new \InvalidArgumentException("Unknown subscription type: {$type}"),
        };
    }
}
