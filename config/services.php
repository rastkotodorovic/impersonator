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

    'ai' => [
        'default_chat_provider' => env('AI_DEFAULT_CHAT_PROVIDER', 'openai'),
        'default_embedding_provider' => env('AI_DEFAULT_EMBEDDING_PROVIDER', 'openai'),
    ],

    'openai' => [
        'client_id' => env('OPENAI_CLIENT_ID'),
        'client_secret' => env('OPENAI_CLIENT_SECRET'),
        'redirect' => env('OPENAI_REDIRECT_URI', '/openai/callback'),
        'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o'),
        'api_key' => env('OPENAI_API_KEY'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-7-sonnet-latest'),
    ],

    'voyage' => [
        'api_key' => env('VOYAGE_API_KEY'),
        'embedding_model' => env('VOYAGE_EMBEDDING_MODEL', 'voyage-3-lite'),
    ],

];
