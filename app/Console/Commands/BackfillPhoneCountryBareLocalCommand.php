<?php

namespace App\Console\Commands;

use App\Helpers\CountryList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use libphonenumber\PhoneNumberUtil;

/**
 * Second-pass backfill for `phone_country_code='+966'` rows whose phone has
 * NO explicit `+` or `00` prefix. The first-pass command refused to touch
 * these. This one uses strict format heuristics:
 *
 *   - 9 digits starting with `5`   → SA  (Saudi mobile, the only valid shape)
 *   - 11 digits starting with 01[0125] → EG (Egyptian mobile, local form)
 *   - 10 digits starting with 1[0125]  → EG (Egyptian mobile, no leading 0)
 *   - 12 digits starting with 20…       → EG (E.164 without the leading +)
 *   - 12 digits starting with 49…       → DE (German E.164 without the +)
 *   - 12 digits starting with 90…       → TR
 *   - 10 digits NOT starting with 5, treated as `+1XXXXXXXXXX` (NANP) and
 *     validated globally via libphonenumber → US or CA depending on the
 *     resolved region.
 *
 * Any phone that doesn't match exactly one rule is left alone for manual
 * review. The NANP rule was added specifically to catch the prod incident
 * where user 917 (`6823582201`, Fort Worth TX area code 682) was stored
 * as `+966` and routed to a KSA-only gateway.
 */
class BackfillPhoneCountryBareLocalCommand extends Command
{
    protected $signature = 'app:backfill-phone-country-bare-local
        {--dry-run : Show what would change without writing}
        {--show-skipped : List the rows that didn\'t match any rule}';

    protected $description = 'Backfill phone_country for bare-local phones using strict format heuristics + libphonenumber NANP detection.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $showSkipped = (bool) $this->option('show-skipped');

        $totals = ['applied' => 0, 'skipped' => 0, 'by_iso' => []];
        $skipped = [];

        foreach (['users', 'student_profiles', 'parent_profiles'] as $table) {
            $tableStats = $this->processTable($table, $dry, $skipped);
            $totals['applied'] += $tableStats['applied'];
            $totals['skipped'] += $tableStats['skipped'];
            foreach ($tableStats['by_iso'] as $iso => $n) {
                $totals['by_iso'][$iso] = ($totals['by_iso'][$iso] ?? 0) + $n;
            }
            $this->info(sprintf(
                '%s%s: applied=%d skipped=%d',
                $dry ? '[dry-run] ' : '',
                $table,
                $tableStats['applied'],
                $tableStats['skipped'],
            ));
        }

        $this->newLine();
        $this->info(sprintf('%sTotal applied: %d  |  skipped: %d', $dry ? '[dry-run] ' : '', $totals['applied'], $totals['skipped']));
        $this->newLine();
        if ($totals['by_iso']) {
            ksort($totals['by_iso']);
            $this->info('ISO distribution:');
            foreach ($totals['by_iso'] as $iso => $n) {
                $this->line(sprintf('  %s: %d', $iso, $n));
            }
        }

        if ($showSkipped && $skipped) {
            $this->newLine();
            $this->info('Skipped rows (no rule matched):');
            foreach ($skipped as $row) {
                $this->line(sprintf('  %s:%d phone=%s', $row['table'], $row['id'], $row['phone']));
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $skipped  (out parameter)
     * @return array{applied:int,skipped:int,by_iso:array<string,int>}
     */
    private function processTable(string $table, bool $dry, array &$skipped): array
    {
        $applied = 0;
        $skipCount = 0;
        $byIso = [];

        DB::table($table)
            ->where('phone_country_code', '+966')
            ->whereNull('phone_country')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereRaw("phone NOT LIKE '+%'")
            ->whereRaw("phone NOT LIKE '00%'")
            ->whereRaw("phone NOT LIKE '966%'")
            ->whereNull('deleted_at')
            ->select('id', 'phone')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $dry, &$applied, &$skipCount, &$byIso, &$skipped) {
                foreach ($rows as $row) {
                    $iso = $this->classify((string) $row->phone);
                    if ($iso === null) {
                        $skipCount++;
                        $skipped[] = ['table' => $table, 'id' => $row->id, 'phone' => $row->phone];

                        continue;
                    }

                    $dial = CountryList::isoToDialCode($iso);
                    if ($dial === null) {
                        $skipCount++;

                        continue;
                    }

                    $applied++;
                    $byIso[$iso] = ($byIso[$iso] ?? 0) + 1;

                    if ($dry) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update([
                        'phone_country' => $iso,
                        'phone_country_code' => '+'.$dial,
                    ]);
                }
            });

        return ['applied' => $applied, 'skipped' => $skipCount, 'by_iso' => $byIso];
    }

    private function classify(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        $len = strlen($digits);

        if ($len === 9 && str_starts_with($digits, '5')) {
            return 'SA';
        }

        if ($len === 11 && preg_match('/^01[0125]/', $digits)) {
            return 'EG';
        }

        if ($len === 10 && preg_match('/^1[0125]/', $digits)) {
            return 'EG';
        }

        if ($len === 12 && str_starts_with($digits, '20')) {
            return 'EG';
        }

        if ($len === 12 && str_starts_with($digits, '49')) {
            return 'DE';
        }

        if ($len === 12 && str_starts_with($digits, '90')) {
            return 'TR';
        }

        if ($len === 10 && ! str_starts_with($digits, '5') && ! str_starts_with($digits, '1') && ! str_starts_with($digits, '0')) {
            return $this->tryNanp($digits);
        }

        return null;
    }

    /**
     * Treat the digits as a NANP local number, prepend +1, and let
     * libphonenumber tell us which NANP country owns it (US, CA, JM, etc.).
     * NANP area codes (NPA) start with 2-9 and exchange (NXX) also starts
     * with 2-9; the broader filter is already applied by the caller.
     */
    private function tryNanp(string $digits): ?string
    {
        try {
            $lib = PhoneNumberUtil::getInstance();
            $parsed = $lib->parse('+1'.$digits, null);
            if (! $lib->isValidNumber($parsed)) {
                return null;
            }
            $region = $lib->getRegionCodeForNumber($parsed);
            if ($region !== null && CountryList::isValid($region)) {
                return $region;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
