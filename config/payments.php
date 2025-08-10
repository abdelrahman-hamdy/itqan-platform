<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used by
    | the framework. You may set this to any of the gateways defined below.
    |
    */

    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'moyasar'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure the payment gateways for your application.
    | Each gateway has its own configuration options.
    |
    */

    'gateways' => [

        'paymob' => [
            'driver' => 'paymob',
            'api_key' => env('PAYMOB_API_KEY'),
            'integration_id' => env('PAYMOB_INTEGRATION_ID'),
            'iframe_id' => env('PAYMOB_IFRAME_ID'),
            'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
            'sandbox' => env('PAYMOB_SANDBOX', true),
            'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com/api'),
            'regions' => ['mena', 'egypt', 'jordan', 'uae'],
        ],

        'tapay' => [
            'driver' => 'tapay',
            'api_key' => env('TAPAY_API_KEY'),
            'secret_key' => env('TAPAY_SECRET_KEY'),
            'merchant_id' => env('TAPAY_MERCHANT_ID'),
            'sandbox' => env('TAPAY_SANDBOX', true),
            'base_url' => env('TAPAY_BASE_URL', 'https://api.tap.company/v2'),
            'regions' => ['gcc', 'kuwait', 'bahrain', 'uae', 'qatar', 'oman'],
        ],

        'moyasar' => [
            'driver' => 'moyasar',
            'api_key' => env('MOYASAR_API_KEY'),
            'secret_key' => env('MOYASAR_SECRET_KEY'),
            'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
            'sandbox' => env('MOYASAR_SANDBOX', true),
            'base_url' => env('MOYASAR_BASE_URL', 'https://api.moyasar.com/v1'),
            'regions' => ['saudi_arabia'],
        ],

        'stc_pay' => [
            'driver' => 'stc_pay',
            'merchant_id' => env('STC_PAY_MERCHANT_ID'),
            'api_key' => env('STC_PAY_API_KEY'),
            'sandbox' => env('STC_PAY_SANDBOX', true),
            'base_url' => env('STC_PAY_BASE_URL', 'https://api.stcpay.com.sa'),
            'regions' => ['saudi_arabia'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Fees Configuration
    |--------------------------------------------------------------------------
    |
    | Configure fees for different payment methods. Fees are calculated as
    | a percentage of the transaction amount.
    |
    */

    'fees' => [
        'credit_card' => 0.025,  // 2.5%
        'mada' => 0.015,         // 1.5%
        'stc_pay' => 0.02,       // 2.0%
        'paymob' => 0.028,       // 2.8%
        'tapay' => 0.024,        // 2.4%
        'bank_transfer' => 0.0,  // 0%
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    |
    | Configure tax rates for different regions.
    |
    */

    'tax' => [
        'saudi_arabia' => 0.15,  // 15% VAT
        'uae' => 0.05,           // 5% VAT
        'egypt' => 0.14,         // 14% VAT
        'jordan' => 0.16,        // 16% VAT
        'kuwait' => 0.0,         // 0% VAT
        'bahrain' => 0.05,       // 5% VAT
        'qatar' => 0.0,          // 0% VAT
        'oman' => 0.05,          // 5% VAT
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Configure supported currencies for each region.
    |
    */

    'currencies' => [
        'saudi_arabia' => 'SAR',
        'uae' => 'AED',
        'egypt' => 'EGP',
        'jordan' => 'JOD',
        'kuwait' => 'KWD',
        'bahrain' => 'BHD',
        'qatar' => 'QAR',
        'oman' => 'OMR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Security
    |--------------------------------------------------------------------------
    |
    | Configure security settings for payments.
    |
    */

    'security' => [
        'enable_3d_secure' => env('PAYMENT_3D_SECURE', true),
        'timeout_seconds' => env('PAYMENT_TIMEOUT', 1800), // 30 minutes
        'max_retry_attempts' => env('PAYMENT_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook endpoints for payment confirmations.
    |
    */

    'webhooks' => [
        'paymob_callback' => '/payments/paymob/callback',
        'tapay_callback' => '/payments/tapay/callback',
        'moyasar_callback' => '/payments/moyasar/callback',
        'stc_pay_callback' => '/payments/stc-pay/callback',
    ],

];