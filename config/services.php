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

    // 토스페이먼츠 (모바일 앱 결제). 기본값은 공개 테스트 키.
    'toss' => [
        'client_key' => env('TOSS_CLIENT_KEY', 'test_ck_D5GePWvyJnrK0W0k6q8gLzN97Eoq'),
        'secret_key' => env('TOSS_SECRET_KEY', 'test_sk_zXLkKEypNArWmo50nX3lmeaxYG5R'),
        'base_url' => env('TOSS_BASE_URL', 'https://api.tosspayments.com'),
    ],

];
