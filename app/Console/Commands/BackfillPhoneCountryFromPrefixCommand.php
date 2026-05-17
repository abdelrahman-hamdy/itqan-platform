<?php

namespace App\Console\Commands;

use App\Helpers\CountryList;
use App\Helpers\PhonePrefixResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Safe one-shot backfill of `phone_country` (ISO) for rows whose `phone`
 * carries an EXPLICIT international prefix (`+` or leading `00`).
 *
 * Only writes when the phone unambiguously identifies a country. Bare local
 * numbers (`509150788`) are left alone — they could be Saudi mobile or a
 * misclassification, and we refuse to guess. The Egyptian phone stored as
 * `00201554919543` is the canonical case this fixes.
 *
 * Tables: users, student_profiles, parent_profiles.
 * Columns updated: `phone_country` (sets) + `phone_country_code` (overwrites
 * with the matching dial code when the existing one is the bad '+966' default
 * and the phone disagrees).
 */
class BackfillPhoneCountryFromPrefixCommand extends Command
{
    protected $signature = 'app:backfill-phone-country-from-prefix
        {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill phone_country (and fix bad +966 dial code) for rows whose phone has an explicit +/00 international prefix.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $totalIso = 0;
        $totalDialFix = 0;

        foreach (['users', 'student_profiles', 'parent_profiles'] as $table) {
            [$iso, $dial] = $this->backfillTable($table, $dry);
            $totalIso += $iso;
            $totalDialFix += $dial;
            $this->info(sprintf('%s%s: %d phone_country set, %d +966 → real-dial corrections', $dry ? '[dry-run] ' : '', $table, $iso, $dial));
        }

        $this->info(sprintf(
            '%sTotal: %d phone_country writes, %d dial-code corrections.',
            $dry ? '[dry-run] ' : '',
            $totalIso,
            $totalDialFix,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int} [phoneCountrySet, dialCodeOverwritten]
     */
    private function backfillTable(string $table, bool $dry): array
    {
        $isoCount = 0;
        $dialCount = 0;

        DB::table($table)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereNull('phone_country')
            ->whereNull('deleted_at')
            ->select('id', 'phone', 'phone_country_code')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $dry, &$isoCount, &$dialCount) {
                foreach ($rows as $row) {
                    $iso = PhonePrefixResolver::isoFromExplicitPrefix($row->phone);
                    if ($iso === null) {
                        continue;
                    }

                    $update = ['phone_country' => $iso];
                    $expectedDial = '+'.CountryList::isoToDialCode($iso);
                    if ($row->phone_country_code !== $expectedDial) {
                        $update['phone_country_code'] = $expectedDial;
                        $dialCount++;
                    }

                    $isoCount++;

                    if ($dry) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update($update);
                }
            });

        return [$isoCount, $dialCount];
    }
}
