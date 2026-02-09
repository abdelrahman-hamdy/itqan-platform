<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used when no academy context is available.
    |
    */

    'default' => env('DEFAULT_CURRENCY', 'SAR'),

    /*
    |--------------------------------------------------------------------------
    | Exchange Rates (relative to SAR)
    |--------------------------------------------------------------------------
    |
    | Static exchange rates for currency conversion.
    | All rates are relative to 1 SAR (Saudi Riyal).
    | Example: 'EGP' => 13.25 means 1 SAR = 13.25 EGP.
    |
    | These are approximate rates and should be updated periodically.
    | For production use with real-time rates, integrate an exchange rate API
    | and cache the results here.
    |
    */

    'exchange_rates' => [
        'SAR' => 1.0,
        'AED' => 0.98,     // 1 SAR ≈ 0.98 AED
        'EGP' => 13.25,    // 1 SAR ≈ 13.25 EGP
        'QAR' => 0.97,     // 1 SAR ≈ 0.97 QAR
        'KWD' => 0.082,    // 1 SAR ≈ 0.082 KWD
        'BHD' => 0.10,     // 1 SAR ≈ 0.10 BHD
        'OMR' => 0.103,    // 1 SAR ≈ 0.103 OMR
        'JOD' => 0.189,    // 1 SAR ≈ 0.189 JOD
        'LBP' => 23870.0,  // 1 SAR ≈ 23870 LBP
        'IQD' => 349.5,    // 1 SAR ≈ 349.5 IQD
        'SYP' => 3466.0,   // 1 SAR ≈ 3466 SYP
        'YER' => 66.8,     // 1 SAR ≈ 66.8 YER
        'MAD' => 2.66,     // 1 SAR ≈ 2.66 MAD
        'DZD' => 35.9,     // 1 SAR ≈ 35.9 DZD
        'TND' => 0.83,     // 1 SAR ≈ 0.83 TND
        'LYD' => 1.29,     // 1 SAR ≈ 1.29 LYD
        'SDG' => 160.5,    // 1 SAR ≈ 160.5 SDG
        'SOS' => 152.0,    // 1 SAR ≈ 152 SOS
        'DJF' => 47.4,     // 1 SAR ≈ 47.4 DJF
        'KMF' => 117.0,    // 1 SAR ≈ 117 KMF
        'MRU' => 10.6,     // 1 SAR ≈ 10.6 MRU
    ],

];
