<?php

namespace App\Console\Commands\Backfill;

use Illuminate\Console\Command;

/**
 * Shared scaffolding for read-only audit commands that emit a CSV report.
 *
 * Subclasses declare their own `--out=` option in their signature and call
 * `writeCsv($bugId, $headers, $rows)` once their candidate-collection step
 * is done. The helper resolves the output path (option override or default
 * `storage/logs/{bugId}-audit-{timestamp}.csv`) and handles the fopen /
 * fputcsv / fclose dance.
 */
abstract class BaseAuditCommand extends Command
{
    /**
     * Write rows to a CSV at the configured `--out` path (or the default).
     *
     * @param  iterable<array<int|string, mixed>>  $rows
     * @return string The absolute path the CSV was written to.
     */
    protected function writeCsv(string $bugId, array $headers, iterable $rows): string
    {
        $path = $this->option('out')
            ?: storage_path(sprintf('logs/%s-audit-%s.csv', $bugId, now()->format('Ymd-His')));

        $handle = fopen($path, 'wb');
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }
}
