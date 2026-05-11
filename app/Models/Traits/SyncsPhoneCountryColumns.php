<?php

namespace App\Models\Traits;

use App\Helpers\CountryList;

/**
 * Keeps ISO (alpha-2) and dial-code halves of a phone-country pair in
 * lockstep so downstream consumers (payment-gateway routing, SMS region
 * selection) can trust either column without re-deriving.
 *
 * Implementing models declare their column pairs via `phoneColumnPairs()`,
 * e.g. `[['phone_country', 'phone_country_code']]`.
 */
trait SyncsPhoneCountryColumns
{
    public static function bootSyncsPhoneCountryColumns(): void
    {
        static::saving(function ($model) {
            $pairs = $model->phoneColumnPairs();
            $dirty = array_filter($pairs, fn ($pair) => $model->isDirty($pair));

            if ($dirty === []) {
                return;
            }

            foreach ($dirty as [$isoCol, $dialCol]) {
                $iso = $model->{$isoCol} !== null ? strtoupper((string) $model->{$isoCol}) : null;
                $dial = $model->{$dialCol} !== null ? (string) $model->{$dialCol} : null;

                if ($iso !== null && $iso !== '' && ($dial === null || $dial === '')) {
                    $derived = CountryList::isoToDialCode($iso);
                    if ($derived !== null) {
                        $model->{$dialCol} = '+'.$derived;
                    }
                } elseif ($dial !== null && $dial !== '' && ($iso === null || $iso === '')) {
                    $derivedIso = CountryList::dialCodeToIso($dial);
                    if ($derivedIso !== null) {
                        $model->{$isoCol} = $derivedIso;
                    }
                }
            }
        });
    }

    /**
     * @return list<array{0:string,1:string}> tuples of [iso_column, dial_code_column]
     */
    abstract protected function phoneColumnPairs(): array;
}
