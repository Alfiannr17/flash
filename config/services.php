<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Tambahkan baris ini di paling bawah config/services.php
    
    'pakasir' => [
        'slug' => env('PAKASIR_SLUG'),
        'api_key' => env('PAKASIR_API_KEY'),
        'url' => env('PAKASIR_API_URL', 'https://app.pakasir.com/api/transactioncreate/qris'),
    ],

    'smsbower' => [
        'api_key' => env('SMSBOWER_API_KEY'),
        'usd_rate' => env('SMSBOWER_USD_RATE', 16000),
        'profit_margin' => env('NOKOS_PROFIT_MARGIN', 2000),
    ],

];
