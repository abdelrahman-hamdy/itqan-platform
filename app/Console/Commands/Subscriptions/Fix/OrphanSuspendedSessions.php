<?php

namespace App\Console\Commands\Subscriptions\Fix;

use App\Enums\SessionStatus;
use App\Models\BackfillLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup for orphan SUSPENDED sessions left behind by the OLD
 * ExpireActiveSubscriptions cron when admin later extended the sub into
 * a grace window.
 *
 * Three actions per orphan, decided per row:
 *   1. Soft-delete — past-dated session that has a non-deleted duplicate
 *      at the same scheduled_at slot (admin re-created the session during
 *      grace). The original SUSPENDED row is a zombie.
 *   2. Set status=CANCELLED — past-dated, no duplicate. The session never
 *      happened (student couldn't see it during suspension); preserve as
 *      audit history.
 *   3. Set status=SCHEDULED — future-dated within the grace window. Student
 *      and teacher can attend going forward.
 *
 * Plus: clear `paused_at` on any sub currently sitting in an active grace
 * extension (relic of the old auto-pause).
 *
 * Targets are hard-coded from the 2026-05-16 investigation; the command
 * re-verifies each row's state pre-write and skips drift.
 *
 * BackfillLog rows under `bug_id='orphan-suspended-extend-cleanup'`.
 */
class OrphanSuspendedSessions extends Command
{
    protected $signature = 'subscriptions:fix-orphan-suspended-sessions
                            {--apply : Actually perform the writes (default is dry-run)}';

    protected $description = 'Resolve orphan SUSPENDED sessions on extended subs (soft-delete duplicates, cancel past zombies, restore future).';

    private const BUG_ID = 'orphan-suspended-extend-cleanup';

    /** Sessions to soft-delete (past-dated with a completed duplicate at same slot). */
    private const SOFT_DELETE_SESSIONS = [
        ['session_id' => 9483, 'sub_id' => 360, 'duplicate_id' => 11073, 'scheduled_at' => '2026-05-09 14:30:00'],
        ['session_id' => 9623, 'sub_id' => 636, 'duplicate_id' => 11646, 'scheduled_at' => '2026-05-12 03:30:00'],
        ['session_id' => 10841, 'sub_id' => 796, 'duplicate_id' => 12403, 'scheduled_at' => '2026-05-13 09:30:00'],
    ];

    /** Sessions to flip to CANCELLED (past-dated, no duplicate — never happened). */
    private const CANCEL_SESSIONS = [
        ['session_id' => 7590, 'sub_id' => 360, 'scheduled_at' => '2026-05-06 14:30:00'],
        ['session_id' => 7488, 'sub_id' => 392, 'scheduled_at' => '2026-05-04 17:00:00'],
        ['session_id' => 7489, 'sub_id' => 392, 'scheduled_at' => '2026-05-05 17:00:00'],
        ['session_id' => 7490, 'sub_id' => 392, 'scheduled_at' => '2026-05-11 17:00:00'],
        ['session_id' => 8615, 'sub_id' => 392, 'scheduled_at' => '2026-05-12 17:00:00'],
        ['session_id' => 9622, 'sub_id' => 636, 'scheduled_at' => '2026-05-09 03:30:00'],
        ['session_id' => 10846, 'sub_id' => 796, 'scheduled_at' => '2026-05-09 08:00:00'],
        ['session_id' => 10840, 'sub_id' => 796, 'scheduled_at' => '2026-05-11 09:30:00'],
        ['session_id' => 10847, 'sub_id' => 796, 'scheduled_at' => '2026-05-16 08:00:00'],
    ];

    /** Sessions to restore to SCHEDULED (future-dated, within grace window). */
    private const RESTORE_SESSIONS = [
        ['session_id' => 10842, 'sub_id' => 796, 'scheduled_at' => '2026-05-18 09:30:00'],
        ['session_id' => 10843, 'sub_id' => 796, 'scheduled_at' => '2026-05-20 09:30:00'],
        ['session_id' => 10848, 'sub_id' => 796, 'scheduled_at' => '2026-05-23 08:00:00'],
        ['session_id' => 10844, 'sub_id' => 796, 'scheduled_at' => '2026-05-25 09:30:00'],
        ['session_id' => 10845, 'sub_id' => 796, 'scheduled_at' => '2026-05-27 09:30:00'],
    ];

    /** Subs to clear lingering paused_at on (all 6 currently in grace). */
    private const CLEAR_PAUSED_AT_SUBS = [360, 388, 392, 636, 777, 796];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('=== Orphan suspended sessions cleanup (extend-flow gap) ===');
        $this->newLine();

        $plans = [
            'soft_delete' => [],
            'cancel' => [],
            'restore' => [],
            'clear_paused_at' => [],
        ];
        $skips = [];

        // ----- Soft-delete plan -----
        foreach (self::SOFT_DELETE_SESSIONS as $row) {
            $sess = DB::table('quran_sessions')->where('id', $row['session_id'])->first();
            if (! $sess) { $skips[] = "session#{$row['session_id']}: missing"; continue; }
            if ($sess->status !== SessionStatus::SUSPENDED->value) { $skips[] = "session#{$row['session_id']}: status drifted ({$sess->status})"; continue; }
            if ($sess->deleted_at) { $skips[] = "session#{$row['session_id']}: already soft-deleted"; continue; }
            $dup = DB::table('quran_sessions')->where('id', $row['duplicate_id'])->whereNull('deleted_at')->first();
            if (! $dup) { $skips[] = "session#{$row['session_id']}: duplicate#{$row['duplicate_id']} missing — refusing soft-delete"; continue; }
            $plans['soft_delete'][] = $row + ['session' => $sess];
        }

        // ----- Cancel plan -----
        foreach (self::CANCEL_SESSIONS as $row) {
            $sess = DB::table('quran_sessions')->where('id', $row['session_id'])->first();
            if (! $sess) { $skips[] = "session#{$row['session_id']}: missing"; continue; }
            if ($sess->status !== SessionStatus::SUSPENDED->value) { $skips[] = "session#{$row['session_id']}: status drifted ({$sess->status})"; continue; }
            if ($sess->deleted_at) { $skips[] = "session#{$row['session_id']}: soft-deleted"; continue; }
            $plans['cancel'][] = $row + ['session' => $sess];
        }

        // ----- Restore plan -----
        foreach (self::RESTORE_SESSIONS as $row) {
            $sess = DB::table('quran_sessions')->where('id', $row['session_id'])->first();
            if (! $sess) { $skips[] = "session#{$row['session_id']}: missing"; continue; }
            if ($sess->status !== SessionStatus::SUSPENDED->value) { $skips[] = "session#{$row['session_id']}: status drifted ({$sess->status})"; continue; }
            if ($sess->deleted_at) { $skips[] = "session#{$row['session_id']}: soft-deleted"; continue; }
            // Sanity — must still be in the future.
            if (Carbon::parse($sess->scheduled_at)->isPast()) {
                $skips[] = "session#{$row['session_id']}: drifted into the past since investigation — will not restore";
                continue;
            }
            $plans['restore'][] = $row + ['session' => $sess];
        }

        // ----- Clear paused_at plan -----
        foreach (self::CLEAR_PAUSED_AT_SUBS as $sid) {
            $sub = DB::table('quran_subscriptions')->where('id', $sid)->first();
            if (! $sub) { $skips[] = "sub#{$sid}: missing"; continue; }
            if (! $sub->paused_at) { $skips[] = "sub#{$sid}: paused_at already NULL"; continue; }
            if ($sub->status !== 'active') { $skips[] = "sub#{$sid}: status={$sub->status} (not active — paused_at preserved)"; continue; }
            $plans['clear_paused_at'][] = ['sub' => $sub];
        }

        $this->info(sprintf(
            'Planned — soft-delete: %d  cancel: %d  restore: %d  clear paused_at: %d  skipped: %d',
            count($plans['soft_delete']), count($plans['cancel']), count($plans['restore']),
            count($plans['clear_paused_at']), count($skips),
        ));
        $this->newLine();

        foreach ($plans['soft_delete'] as $p) {
            $this->line("  SOFT-DELETE  session#{$p['session_id']} (sub {$p['sub_id']}, {$p['scheduled_at']}, dup #{$p['duplicate_id']})");
        }
        foreach ($plans['cancel'] as $p) {
            $this->line("  CANCEL       session#{$p['session_id']} (sub {$p['sub_id']}, {$p['scheduled_at']})");
        }
        foreach ($plans['restore'] as $p) {
            $this->line("  RESTORE      session#{$p['session_id']} (sub {$p['sub_id']}, {$p['scheduled_at']}) → SCHEDULED");
        }
        foreach ($plans['clear_paused_at'] as $p) {
            $this->line("  CLEAR_PAUSED sub#{$p['sub']->id} (paused_at was {$p['sub']->paused_at})");
        }
        if (! empty($skips)) {
            $this->newLine();
            $this->warn('Skips:');
            foreach ($skips as $s) $this->warn("  {$s}");
        }

        if (! $apply) {
            $this->newLine();
            $this->comment('DRY-RUN. Re-run with --apply.');
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $touched = 0;
        $errors = 0;

        foreach ($plans['soft_delete'] as $p) {
            try {
                DB::transaction(function () use ($p, $now) {
                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'quran_sessions',
                        'row_id' => $p['session_id'],
                        'column_name' => 'deleted_at',
                        'original_value' => json_encode((array) $p['session'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'new_value' => $now->toDateTimeString(),
                        'backfill_command' => 'subscriptions:fix-orphan-suspended-sessions',
                        'ran_at' => $now,
                    ]);
                    DB::table('quran_sessions')->where('id', $p['session_id'])->update(['deleted_at' => $now, 'updated_at' => $now]);
                });
                $touched++;
            } catch (\Throwable $e) {
                $errors++; $this->warn("soft-delete session#{$p['session_id']}: {$e->getMessage()}");
            }
        }

        foreach ($plans['cancel'] as $p) {
            try {
                DB::transaction(function () use ($p, $now) {
                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'quran_sessions',
                        'row_id' => $p['session_id'],
                        'column_name' => 'status',
                        'original_value' => SessionStatus::SUSPENDED->value,
                        'new_value' => SessionStatus::CANCELLED->value,
                        'backfill_command' => 'subscriptions:fix-orphan-suspended-sessions',
                        'ran_at' => $now,
                    ]);
                    DB::table('quran_sessions')->where('id', $p['session_id'])->update(['status' => SessionStatus::CANCELLED->value, 'updated_at' => $now]);
                });
                $touched++;
            } catch (\Throwable $e) {
                $errors++; $this->warn("cancel session#{$p['session_id']}: {$e->getMessage()}");
            }
        }

        foreach ($plans['restore'] as $p) {
            try {
                DB::transaction(function () use ($p, $now) {
                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'quran_sessions',
                        'row_id' => $p['session_id'],
                        'column_name' => 'status',
                        'original_value' => SessionStatus::SUSPENDED->value,
                        'new_value' => SessionStatus::SCHEDULED->value,
                        'backfill_command' => 'subscriptions:fix-orphan-suspended-sessions',
                        'ran_at' => $now,
                    ]);
                    DB::table('quran_sessions')->where('id', $p['session_id'])->update(['status' => SessionStatus::SCHEDULED->value, 'updated_at' => $now]);
                });
                $touched++;
            } catch (\Throwable $e) {
                $errors++; $this->warn("restore session#{$p['session_id']}: {$e->getMessage()}");
            }
        }

        foreach ($plans['clear_paused_at'] as $p) {
            try {
                DB::transaction(function () use ($p, $now) {
                    BackfillLog::create([
                        'bug_id' => self::BUG_ID,
                        'table_name' => 'quran_subscriptions',
                        'row_id' => $p['sub']->id,
                        'column_name' => 'paused_at',
                        'original_value' => (string) $p['sub']->paused_at,
                        'new_value' => 'NULL',
                        'backfill_command' => 'subscriptions:fix-orphan-suspended-sessions',
                        'ran_at' => $now,
                    ]);
                    DB::table('quran_subscriptions')->where('id', $p['sub']->id)->update(['paused_at' => null, 'updated_at' => $now]);
                });
                $touched++;
            } catch (\Throwable $e) {
                $errors++; $this->warn("clear paused_at sub#{$p['sub']->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("APPLIED: {$touched} row(s); {$errors} error(s).");
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
