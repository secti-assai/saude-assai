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

    'gov_assai' => [
        'base_url' => env('GOV_ASSAI_BASE_URL'),
        'api_key' => env('GOV_ASSAI_API_KEY'),
        'timeout' => env('GOV_ASSAI_TIMEOUT', 10),
        'connect_timeout' => env('GOV_ASSAI_CONNECT_TIMEOUT', 5),
    ],

    'notifications' => [
        'base_url' => env('NOTIFICATIONS_BASE_URL', 'http://notificacoes.assai.pr.gov.br'),
        'api_key' => env('NOTIFICATIONS_API_KEY'),
        'timeout' => env('NOTIFICATIONS_TIMEOUT', 10),
        'connect_timeout' => env('NOTIFICATIONS_CONNECT_TIMEOUT', 5),
        'default_channel' => env('NOTIFICATIONS_DEFAULT_CHANNEL', 'whatsapp'),
        'public_base_url' => env('NOTIFICATIONS_PUBLIC_BASE_URL', env('APP_URL', 'http://localhost')),
        'cancel_link_ttl_hours' => env('NOTIFICATIONS_CANCEL_LINK_TTL_HOURS', 6),
        'feedback_link_ttl_hours' => env('NOTIFICATIONS_FEEDBACK_LINK_TTL_HOURS', 168),
    ],

];
