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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Push each game offer / card category to POS as its own product (README_API.md "POS catalog sync").
    'pos_catalog' => [
        'enabled' => filter_var(env('POS_CATALOG_SYNC', true), FILTER_VALIDATE_BOOL),
        'business_id' => (int) env('POS_BUSINESS_ID', 1),
        'timeout' => (int) env('POS_CATALOG_SYNC_TIMEOUT', 20),
        // pos:sync-catalog only: waits on POS 429/409 before giving up on an item.
        'max_retries' => (int) env('POS_CATALOG_SYNC_MAX_RETRIES', 5),
        'max_retry_wait' => (int) env('POS_CATALOG_SYNC_MAX_RETRY_WAIT', 60),
    ],

    // Next Load gift card supplier (docs/NEXTLOAD.md). Credentials are server-side only.
    'nextload' => [
        'enabled' => filter_var(env('NEXTLOAD_ENABLED', false), FILTER_VALIDATE_BOOL),
        'base_url' => rtrim((string) env('NEXTLOAD_BASE_URL', 'https://stg.next-load.com/APIS/V1/'), '/').'/',
        'email' => env('NEXTLOAD_EMAIL'),
        'password' => env('NEXTLOAD_PASSWORD'),
        'security_code' => env('NEXTLOAD_SECURITY_CODE'),
        'timeout' => (int) env('NEXTLOAD_TIMEOUT', 30),
        // Balance under this triggers a low-balance warning (supplier currency, 3 decimals).
        'low_balance' => (string) env('NEXTLOAD_LOW_BALANCE', '0'),
        // Local currency units per 1 supplier unit; converts supplier prices into card cost / loss checks.
        'exchange_rate' => (string) env('NEXTLOAD_EXCHANGE_RATE', '1'),
        // When false, never buy a code whose converted supplier price is above the category sale price.
        'allow_loss' => filter_var(env('NEXTLOAD_ALLOW_LOSS', false), FILTER_VALIDATE_BOOL),
        // Secret for the login-free /nextload/diagnostics page (?key=…); empty disables the page (404).
        'diagnostics_key' => env('NEXTLOAD_DIAGNOSTICS_KEY'),
    ],

    // WooCommerce inventory webhook (App\Services\WebhookService).
    'wordpress_webhook' => [
        'url' => env('WORDPRESS_WEBHOOK_URL'),
        'secret' => env('WORDPRESS_WEBHOOK_SECRET'),
    ],

];
