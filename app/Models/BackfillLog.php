<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit log for one-off backfill commands.
 *
 * Every row mutated by a `subscriptions:backfill-*` / `earnings:fix-*` command
 * writes one BackfillLog row BEFORE the mutation, so a paired `--rollback`
 * mode can restore the original column values cleanly.
 */
class BackfillLog extends Model
{
    protected $table = 'backfill_log';

    protected $fillable = [
        'bug_id',
        'table_name',
        'row_id',
        'column_name',
        'original_value',
        'new_value',
        'backfill_command',
        'ran_at',
        'reversed_at',
    ];

    protected $casts = [
        'ran_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /**
     * Write one audit-trail row for a planned mutation. Call BEFORE the
     * actual write so a paired `--rollback` run can read original_value
     * back out and restore the column.
     *
     * Pulls `table_name` from `$row->getTable()` and `row_id` from
     * `$row->getKey()` so callers don't have to repeat the column-by-column
     * payload that previously appeared at every mutation site.
     */
    public static function record(
        string $bugId,
        string $command,
        Model $row,
        string $column,
        mixed $originalValue,
        mixed $newValue,
    ): self {
        return static::create([
            'bug_id' => $bugId,
            'backfill_command' => $command,
            'table_name' => $row->getTable(),
            'row_id' => $row->getKey(),
            'column_name' => $column,
            'original_value' => $originalValue === null ? null : (string) $originalValue,
            'new_value' => $newValue === null ? null : (string) $newValue,
        ]);
    }
}
