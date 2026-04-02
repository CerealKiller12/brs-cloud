<?php

return [
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'google_admin' => [
        'client_id' => env('GOOGLE_ADMIN_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_ADMIN_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET')),
        'redirect' => env('GOOGLE_ADMIN_REDIRECT_URI', env('GOOGLE_REDIRECT_URI')),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
    ],

    'apple_admin' => [
        'client_id' => env('APPLE_ADMIN_CLIENT_ID', env('APPLE_CLIENT_ID')),
        'client_secret' => env('APPLE_ADMIN_CLIENT_SECRET', env('APPLE_CLIENT_SECRET')),
        'redirect' => env('APPLE_ADMIN_REDIRECT_URI', env('APPLE_REDIRECT_URI')),
        'team_id' => env('APPLE_ADMIN_TEAM_ID', env('APPLE_TEAM_ID')),
        'key_id' => env('APPLE_ADMIN_KEY_ID', env('APPLE_KEY_ID')),
    ],

    'downloads' => [
        'windows_url' => env('DOWNLOADS_WINDOWS_URL'),
        'mac_url' => env('DOWNLOADS_MAC_URL'),
        'app_store_url' => env('DOWNLOADS_APP_STORE_URL'),
        'google_play_url' => env('DOWNLOADS_GOOGLE_PLAY_URL'),
        'support_email' => env('DOWNLOADS_SUPPORT_EMAIL', 'hola@venpi.mx'),
    ],
];
