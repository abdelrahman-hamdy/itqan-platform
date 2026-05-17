<?php

namespace App\Console\Commands;

use App\Helpers\CountryList;
use App\Helpers\PhonePrefixResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only audit of phone-country data on users / student_profiles /
 * parent_profiles. Emits a CSV for human review; never mutates data.
 * Suggestions go into `suggested_iso` / `suggested_dial_code` columns
 * which `app:apply-phone-corrections` reads back inside a transaction.
 */
class AuditPhoneDataCommand extends Command
{
    protected $signature = 'app:audit-phone-data
        {--csv=storage/app/phone-audit.csv : Output CSV path (relative to project root)}
        {--academy= : Restrict to a single academy_id}';

    protected $description = 'Audit phone-country fields for misclassification and emit a review CSV (read-only).';

    public function handle(): int
    {
        $csvPath = $this->option('csv');
        if (! str_starts_with($csvPath, '/')) {
            $csvPath = base_path($csvPath);
        }

        $dir = dirname($csvPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $academyFilter = $this->option('academy');

        $handle = fopen($csvPath, 'w');
        if ($handle === false) {
            $this->error("Cannot open {$csvPath} for writing.");

            return self::FAILURE;
        }

        fputcsv($handle, [
            'table', 'row_id', 'user_id', 'email', 'phone',
            'phone_country_code', 'phone_country', 'nationality', 'academy_id',
            'flag_reason', 'suggested_iso', 'suggested_dial_code',
        ]);

        $flagged = 0;
        $flagged += $this->auditTable('users', $handle, $academyFilter, withNationality: false);
        $flagged += $this->auditTable('student_profiles', $handle, $academyFilter, withNationality: true);
        $flagged += $this->auditTable('parent_profiles', $handle, $academyFilter, withNationality: false);

        fclose($handle);

        $this->info("Flagged {$flagged} rows. Review and edit suggestions, then run:");
        $this->line("  php artisan app:apply-phone-corrections --csv={$csvPath}");

        return self::SUCCESS;
    }

    private function auditTable(string $table, $handle, ?string $academyFilter, bool $withNationality): int
    {
        $hasIsoCol = Schema::hasColumn($table, 'phone_country');
        $columns = ['id', 'user_id', 'email', 'phone', 'phone_country_code', 'academy_id'];
        if ($table === 'users') {
            $columns = array_diff($columns, ['user_id']);
        }
        if ($withNationality) {
            $columns[] = 'nationality';
        }
        if ($hasIsoCol) {
            $columns[] = 'phone_country';
        }

        $query = DB::table($table)->select($columns)->whereNull('deleted_at');

        if ($academyFilter !== null) {
            $query->where('academy_id', $academyFilter);
        }

        $count = 0;
        $query->orderBy('id')->chunk(500, function ($rows) use ($table, $handle, $withNationality, &$count) {
            foreach ($rows as $row) {
                $iso = $row->phone_country ?? null;
                $nationality = $withNationality ? ($row->nationality ?? null) : null;

                $flags = $this->detectFlags($row->phone, $row->phone_country_code, $iso, $nationality);
                if ($flags === []) {
                    continue;
                }

                [$suggestedIso, $suggestedDial] = $this->suggestFromPhone($row->phone, $row->phone_country_code);

                fputcsv($handle, [
                    $table,
                    $row->id,
                    $table === 'users' ? $row->id : ($row->user_id ?? ''),
                    $row->email,
                    $row->phone,
                    $row->phone_country_code,
                    $iso ?? '',
                    $nationality ?? '',
                    $row->academy_id,
                    implode('; ', $flags),
                    $suggestedIso ?? '',
                    $suggestedDial ?? '',
                ]);
                $count++;
            }
        });

        return $count;
    }

    /**
     * @return string[]
     */
    private function detectFlags(?string $phone, ?string $dialCode, ?string $iso, ?string $nationality): array
    {
        $flags = [];
        $phoneIsoFromPrefix = PhonePrefixResolver::isoFromExplicitPrefix($phone);

        // 1. Stored dial code is the old '+966' default but the phone carries
        //    an explicit international prefix that resolves to a different ISO.
        //    Saudi mobile numbers are typically 9 digits beginning with `5`,
        //    e.g. `509150788` = `+966 50 915 0788` — we must NOT mistake those
        //    for Haiti (+509) just because the leading digits collide.
        if ($dialCode === '+966' && $phoneIsoFromPrefix !== null && $phoneIsoFromPrefix !== 'SA') {
            $flags[] = "phone_starts_with_{$phoneIsoFromPrefix}_but_country_code_is_+966";
        }

        if ($dialCode === '+966' && $nationality !== null && $nationality !== '' && strtoupper($nationality) !== 'SA') {
            $flags[] = 'phone_country_code_+966_but_nationality_'.strtoupper($nationality);
        }

        if ($dialCode !== null && $dialCode !== '' && ($iso === null || $iso === '')) {
            if (CountryList::dialCodeToIso($dialCode) === null) {
                $flags[] = "dial_code_{$dialCode}_not_in_iso_map";
            }
        }

        if ($phone !== null && $phone !== '' && preg_match('/[^\d+\-() ]/', $phone)) {
            $flags[] = 'phone_has_non_digit_chars';
        }

        if ($phone !== null && $phone !== '') {
            $len = strlen(preg_replace('/\D/', '', $phone) ?? '');
            if ($len > 0 && ($len < 7 || $len > 15)) {
                $flags[] = "phone_length_{$len}_outside_E164";
            }
        }

        return $flags;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function suggestFromPhone(?string $phone, ?string $dialCode): array
    {
        $iso = PhonePrefixResolver::isoFromExplicitPrefix($phone) ?? CountryList::dialCodeToIso($dialCode);
        if ($iso === null) {
            return [null, null];
        }

        $dial = CountryList::isoToDialCode($iso);

        return [$iso, $dial !== null ? '+'.$dial : $dialCode];
    }
}
