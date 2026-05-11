<?php

namespace App\Console\Commands\Backfill;

use App\Models\BackfillLog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Shared scaffolding for one-off backfill commands.
 *
 * Subclasses must define:
 *
 *   protected const BUG_ID = 'bug_X';
 *   protected const COMMAND_NAME = 'subscriptions:something';
 *
 * Provides:
 *   - `logChange()`   — writes one `BackfillLog` row (delegates to BackfillLog::record).
 *   - `rollbackLogged()` — generic loop that restores `original_value` into
 *     `{table_name}.{column_name}` for every row logged under this command.
 *     Subclasses with inverse-of-create semantics (e.g. soft-delete + reversal
 *     pairs) override `rollback()` directly instead of using this helper.
 */
abstract class BaseBackfillCommand extends Command
{
    /**
     * Bug identifier stamped on every `BackfillLog` row written by this
     * command. Subclasses MUST redeclare this constant — leaving the default
     * would cause `--rollback` to scan an empty/global slice of audit rows.
     */
    protected const BUG_ID = '';

    /**
     * Command name stamped on every `BackfillLog` row. Subclasses MUST
     * redeclare this constant; it MUST match the artisan signature so a
     * `--rollback` run filters to this command's own audit rows.
     */
    protected const COMMAND_NAME = '';

    /**
     * Record a planned mutation. Call BEFORE the actual write so a
     * `--rollback` run can read original_value back out.
     */
    protected function logChange(
        Model $row,
        string $column,
        mixed $originalValue,
        mixed $newValue,
    ): BackfillLog {
        return BackfillLog::record(
            static::BUG_ID,
            static::COMMAND_NAME,
            $row,
            $column,
            $originalValue,
            $newValue,
        );
    }

    /**
     * Generic rollback for column-overwrite backfills: for every BackfillLog
     * row stamped with this command's `bug_id` + `backfill_command`, write
     * `original_value` back into `{table_name}.{column_name}` and mark
     * `reversed_at`.
     *
     * Subclasses with non-overwrite semantics (e.g. row creation, soft-delete)
     * should override `rollback()` directly and not call this helper.
     */
    protected function rollbackLogged(): int
    {
        $rows = BackfillLog::query()
            ->where('bug_id', static::BUG_ID)
            ->where('backfill_command', static::COMMAND_NAME)
            ->whereNull('reversed_at')
            ->orderByDesc('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No prior --apply run logged. Nothing to roll back.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $log) {
                DB::table($log->table_name)
                    ->where('id', $log->row_id)
                    ->update([$log->column_name => $log->original_value]);
                $log->update(['reversed_at' => now()]);
            }
        });

        $this->info(sprintf('Rolled back %d backfill row(s).', $rows->count()));

        return self::SUCCESS;
    }
}
