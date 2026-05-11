<?php

namespace App\Console\Commands;

use App\Helpers\CountryList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads back the CSV emitted by `app:audit-phone-data` and applies the
 * suggestions a human reviewer filled in.
 *
 * Skipped rows:
 *  - Rows with blank `suggested_iso` (no human review performed yet).
 *  - Rows whose `suggested_iso` doesn't validate against CountryList.
 *  - Rows where the target table/column doesn't exist.
 *
 * Every applied row gets logged to stdout. The whole batch runs inside one
 * DB transaction so partial failure rolls everything back.
 */
class ApplyPhoneCorrectionsCommand extends Command
{
    protected $signature = 'app:apply-phone-corrections
        {--csv=storage/app/phone-audit.csv : Path to the reviewed CSV}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Apply human-reviewed phone-country corrections from the audit CSV (transactional).';

    public function handle(): int
    {
        $csvPath = $this->option('csv');
        if (! str_starts_with($csvPath, '/')) {
            $csvPath = base_path($csvPath);
        }

        if (! is_file($csvPath)) {
            $this->error("CSV not found: {$csvPath}");

            return self::FAILURE;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->error("Cannot open CSV for reading: {$csvPath}");

            return self::FAILURE;
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            $this->error('Empty CSV.');

            return self::FAILURE;
        }
        $header = array_flip($headerRow);

        $required = ['table', 'row_id', 'suggested_iso', 'suggested_dial_code'];
        foreach ($required as $col) {
            if (! isset($header[$col])) {
                fclose($handle);
                $this->error("Missing required column in CSV: {$col}");

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $applied = 0;
        $skipped = 0;
        $errors = 0;

        $work = function () use ($handle, $header, $dryRun, &$applied, &$skipped, &$errors) {
            while (($row = fgetcsv($handle)) !== false) {
                $table = $row[$header['table']] ?? '';
                $rowId = $row[$header['row_id']] ?? '';
                $iso = trim((string) ($row[$header['suggested_iso']] ?? ''));
                $dial = trim((string) ($row[$header['suggested_dial_code']] ?? ''));

                if ($iso === '') {
                    $skipped++;

                    continue;
                }

                $iso = strtoupper($iso);
                if (! CountryList::isValid($iso)) {
                    $this->warn("Skipping {$table}:{$rowId} — suggested_iso '{$iso}' invalid.");
                    $errors++;

                    continue;
                }

                if (! in_array($table, ['users', 'student_profiles', 'parent_profiles'], true)) {
                    $this->warn("Skipping unknown table: {$table}");
                    $errors++;

                    continue;
                }

                if (! Schema::hasColumn($table, 'phone_country')) {
                    $this->warn("Skipping {$table}:{$rowId} — `phone_country` column not present (migration pending?).");
                    $errors++;

                    continue;
                }

                $update = ['phone_country' => $iso];

                if ($dial === '') {
                    $derived = CountryList::isoToDialCode($iso);
                    if ($derived !== null) {
                        $dial = '+'.$derived;
                    }
                }

                if ($dial !== '') {
                    if (! str_starts_with($dial, '+')) {
                        $dial = '+'.ltrim($dial, '+');
                    }
                    $update['phone_country_code'] = $dial;
                }

                if ($dryRun) {
                    $this->line(sprintf('[dry-run] %s:%s ← %s', $table, $rowId, json_encode($update)));
                    $applied++;

                    continue;
                }

                DB::table($table)->where('id', $rowId)->update($update);
                $this->line(sprintf('Applied %s:%s ← %s', $table, $rowId, json_encode($update)));
                $applied++;
            }
        };

        try {
            if ($dryRun) {
                $work();
            } else {
                DB::transaction($work);
            }
        } finally {
            fclose($handle);
        }

        $this->info(sprintf(
            '%sCorrections: %d applied, %d skipped (no suggestion), %d errors.',
            $dryRun ? '[dry-run] ' : '',
            $applied,
            $skipped,
            $errors,
        ));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
