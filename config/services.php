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

    'waha' => [
        'api_url' => env('WAHA_API_URL', 'http://waha:3000'),
        'api_key' => env('WAHA_API_KEY', ''),
        'session_name' => env('WAHA_SESSION_NAME', 'default'),
    ],

    'telegram' => [
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    ],

    'openai' => [
        'client_id' => env('OPENAI_CLIENT_ID'),
        'client_secret' => env('OPENAI_CLIENT_SECRET'),
        'redirect' => env('OPENAI_REDIRECT_URI', '/openai/callback'),
        'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o'),
        'api_key' => env('OPENAI_API_KEY'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY', ''),
    ],

];
