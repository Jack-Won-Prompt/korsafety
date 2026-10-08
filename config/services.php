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

    /*
     | FCM(Firebase Cloud Messaging) — 앱 푸시 알림.
     | credentials: Firebase 콘솔 > 프로젝트 설정 > 서비스 계정 > 새 비공개 키 생성으로 받은 JSON 파일 경로.
     | 미설정이면 푸시 발송은 조용히 건너뜁니다(기능 off).
     */
    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'credentials' => env('FCM_CREDENTIALS', storage_path('app/firebase/service-account.json')),
    ],

    /*
     | SupportWorks — 운영 예외 보고 (App\Support\SupportWorksReporter).
     | 값은 .env 에서만 읽는다. 미설정이면 보고는 조용히 건너뛴다.
     */
    'supportworks' => [
        'error_url'   => env('SW_ERROR_URL'),
        'error_token' => env('SW_ERROR_TOKEN'),
    ],

    /*
     | 자동 처리 Agent — 운영 오류 분석 · SR 답변 작성에 쓰는 Claude 키.
     | 키는 .env 에만 둔다(화면에 저장하지 않는다). 없으면 Agent 는 조용히 쉰다.
     | 켜고 끄는 스위치와 모델·상한은 관리자 › 설정에서 바꾼다.
     */
    'agent' => [
        'api_key' => env('ANTHROPIC_API_KEY', env('AGENT_API_KEY')),
        // 웹훅 주소 — 비워 두면 APP_URL 기준으로 /agent/hook 을 쓴다
        'hook_url' => env('AGENT_HOOK_URL'),
        // Agent 화면·설정을 볼 수 있는 담당자 (이 계정에게만 보인다)
        'operator_email' => env('AGENT_OPERATOR_EMAIL', 'jack@withworks.co.kr'),
    ],

];
