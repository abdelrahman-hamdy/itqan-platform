<?php

namespace App\Console\Commands;

use App\Helpers\CountryList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit of phone-country data on users / student_profiles /
 * parent_profiles.
 *
 * Emits a CSV that surfaces rows where the stored `phone_country_code`
 * disagrees with the leading dial code on `phone`, where `phone_country`
 * (ISO) is missing or unmapped, or where the phone string looks malformed.
 *
 * Nationality ≠ phone country ≠ residence, so this command never mutates
 * data — a human reviews the CSV and edits the `suggested_iso` /
 * `suggested_dial_code` columns for rows they're confident about. The
 * `app:apply-phone-corrections` companion command then applies those
 * suggestions inside a transaction.
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

        $flaggedCount = 0;
        $flaggedCount += $this->auditUsers($handle, $academyFilter);
        $flaggedCount += $this->auditStudentProfiles($handle, $academyFilter);
        $flaggedCount += $this->auditParentProfiles($handle, $academyFilter);

        fclose($handle);

        $this->info("Flagged {$flaggedCount} rows. Review and edit suggestions, then run:");
        $this->line("  php artisan app:apply-phone-corrections --csv={$csvPath}");

        return self::SUCCESS;
    }

    private function auditUsers($handle, ?string $academyFilter): int
    {
        $query = DB::table('users')
            ->select(['id', 'academy_id', 'email', 'phone', 'phone_country_code'])
            ->selectRaw(\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone_country') ? 'phone_country' : 'NULL as phone_country')
            ->whereNull('deleted_at');

        if ($academyFilter !== null) {
            $query->where('academy_id', $academyFilter);
        }

        $count = 0;
        $query->orderBy('id')->chunk(500, function ($rows) use ($handle, &$count) {
            foreach ($rows as $row) {
                $flags = $this->detectFlags(
                    $row->phone,
                    $row->phone_country_code,
                    $row->phone_country ?? null,
                    null,
                );

                if ($flags === []) {
                    continue;
                }

                [$suggestedIso, $suggestedDial] = $this->suggestFromPhone($row->phone, $row->phone_country_code);

                fputcsv($handle, [
                    'users', $row->id, $row->id, $row->email, $row->phone,
                    $row->phone_country_code, $row->phone_country ?? '', '',
                    $row->academy_id, implode('; ', $flags),
                    $suggestedIso ?? '', $suggestedDial ?? '',
                ]);
                $count++;
            }
        });

        return $count;
    }

    private function auditStudentProfiles($handle, ?string $academyFilter): int
    {
        $query = DB::table('student_profiles')
            ->select([
                'student_profiles.id',
                'student_profiles.user_id',
                'student_profiles.email',
                'student_profiles.phone',
                'student_profiles.phone_country_code',
                'student_profiles.nationality',
                'student_profiles.academy_id',
            ])
            ->selectRaw(\Illuminate\Support\Facades\Schema::hasColumn('student_profiles', 'phone_country') ? 'student_profiles.phone_country' : 'NULL as phone_country')
            ->whereNull('student_profiles.deleted_at');

        if ($academyFilter !== null) {
            $query->where('student_profiles.academy_id', $academyFilter);
        }

        $count = 0;
        $query->orderBy('student_profiles.id')->chunk(500, function ($rows) use ($handle, &$count) {
            foreach ($rows as $row) {
                $flags = $this->detectFlags(
                    $row->phone,
                    $row->phone_country_code,
                    $row->phone_country ?? null,
                    $row->nationality,
                );

                if ($flags === []) {
                    continue;
                }

                [$suggestedIso, $suggestedDial] = $this->suggestFromPhone($row->phone, $row->phone_country_code);

                fputcsv($handle, [
                    'student_profiles', $row->id, $row->user_id, $row->email, $row->phone,
                    $row->phone_country_code, $row->phone_country ?? '', $row->nationality ?? '',
                    $row->academy_id, implode('; ', $flags),
                    $suggestedIso ?? '', $suggestedDial ?? '',
                ]);
                $count++;
            }
        });

        return $count;
    }

    private function auditParentProfiles($handle, ?string $academyFilter): int
    {
        $query = DB::table('parent_profiles')
            ->select([
                'parent_profiles.id',
                'parent_profiles.user_id',
                'parent_profiles.email',
                'parent_profiles.phone',
                'parent_profiles.phone_country_code',
                'parent_profiles.academy_id',
            ])
            ->selectRaw(\Illuminate\Support\Facades\Schema::hasColumn('parent_profiles', 'phone_country') ? 'parent_profiles.phone_country' : 'NULL as phone_country')
            ->whereNull('parent_profiles.deleted_at');

        if ($academyFilter !== null) {
            $query->where('parent_profiles.academy_id', $academyFilter);
        }

        $count = 0;
        $query->orderBy('parent_profiles.id')->chunk(500, function ($rows) use ($handle, &$count) {
            foreach ($rows as $row) {
                $flags = $this->detectFlags(
                    $row->phone,
                    $row->phone_country_code,
                    $row->phone_country ?? null,
                    null,
                );

                if ($flags === []) {
                    continue;
                }

                [$suggestedIso, $suggestedDial] = $this->suggestFromPhone($row->phone, $row->phone_country_code);

                fputcsv($handle, [
                    'parent_profiles', $row->id, $row->user_id, $row->email, $row->phone,
                    $row->phone_country_code, $row->phone_country ?? '', '',
                    $row->academy_id, implode('; ', $flags),
                    $suggestedIso ?? '', $suggestedDial ?? '',
                ]);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Return a list of human-readable reasons this row looks suspect.
     *
     * @return string[]
     */
    private function detectFlags(?string $phone, ?string $dialCode, ?string $iso, ?string $nationality): array
    {
        $flags = [];

        // 1. Stored dial code is '+966' (the old default) but the phone
        //    string actually starts with a different international prefix.
        if ($dialCode === '+966' && $phone !== null && $phone !== '') {
            $cleaned = preg_replace('/[^\d+]/', '', $phone) ?? '';
            // strip leading "00" or "+"
            $cleaned = preg_replace('/^(?:\+|00)/', '', $cleaned) ?? '';
            if (! str_starts_with($cleaned, '966') && $cleaned !== '') {
                $phoneIso = CountryList::dialCodeToIso($cleaned);
                if ($phoneIso !== null && $phoneIso !== 'SA') {
                    $flags[] = "phone_starts_with_{$phoneIso}_but_country_code_is_+966";
                }
            }
        }

        // 2. Default-marked phone but nationality says something else.
        if ($dialCode === '+966' && $nationality !== null && $nationality !== '' && strtoupper($nationality) !== 'SA') {
            $flags[] = 'phone_country_code_+966_but_nationality_'.strtoupper($nationality);
        }

        // 3. Dial code set but unmapped + ISO column missing.
        if ($dialCode !== null && $dialCode !== '' && ($iso === null || $iso === '')) {
            $derivedIso = CountryList::dialCodeToIso($dialCode);
            if ($derivedIso === null) {
                $flags[] = "dial_code_{$dialCode}_not_in_iso_map";
            }
        }

        // 4. Phone string contains characters other than digits, '+', '-', '(', ')', ' '
        if ($phone !== null && $phone !== '' && preg_match('/[^\d+\-() ]/', $phone)) {
            $flags[] = 'phone_has_non_digit_chars';
        }

        // 5. Suspicious phone length.
        if ($phone !== null && $phone !== '') {
            $digits = preg_replace('/\D/', '', $phone) ?? '';
            $len = strlen($digits);
            if ($len > 0 && ($len < 7 || $len > 15)) {
                $flags[] = "phone_length_{$len}_outside_E164";
            }
        }

        return $flags;
    }

    /**
     * Suggest an ISO + dial code from the raw phone if possible. Conservative:
     * returns null when ambiguous so the human reviewer makes the call.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function suggestFromPhone(?string $phone, ?string $dialCode): array
    {
        if ($phone !== null && $phone !== '') {
            $cleaned = preg_replace('/[^\d+]/', '', $phone) ?? '';
            $cleaned = preg_replace('/^(?:\+|00)/', '', $cleaned) ?? '';

            $iso = CountryList::dialCodeToIso($cleaned);
            if ($iso !== null) {
                $dial = CountryList::isoToDialCode($iso);

                return [$iso, $dial !== null ? '+'.$dial : null];
            }
        }

        if ($dialCode !== null && $dialCode !== '') {
            $iso = CountryList::dialCodeToIso($dialCode);
            if ($iso !== null) {
                return [$iso, $dialCode];
            }
        }

        return [null, null];
    }
}
