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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Harvest prediction thresholds and model defaults
    'harvest' => [
        'min_dbh_cm' => env('HARVEST_MIN_DBH_CM', 10),
        'min_height_m' => env('HARVEST_MIN_HEIGHT_M', 2),
        'sarima_order' => env('HARVEST_SARIMA_ORDER', '4,1,4'),
        'sarima_seasonal' => env('HARVEST_SARIMA_SEASONAL', '0,1,0,12'),
        'harvest_months' => env('HARVEST_MONTHS', '1,2,3'),
    ],

    'semaphore' => [
        'semaphore.api_key' => env('SEMAPHORE_API_KEY'),
        'semaphore.sender_name' => env('SEMAPHORE_SENDER_NAME'),
    ]

];
