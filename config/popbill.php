<?php

/*
|--------------------------------------------------------------------------
| 팝빌(Popbill) 전자세금계산서 설정
|--------------------------------------------------------------------------
| simulate=true(기본)이면 팝빌 API를 호출하지 않고 내부적으로만 발행 처리한다.
| 실제 국세청 발행은 팝빌 계정·연동키 + 사업자 등록 후 POPBILL_TAXINVOICE_SIMULATE=false.
*/

return [
    // 시뮬레이트 모드 (기본 true → 실제 발행 안 함)
    'simulate' => env('POPBILL_TAXINVOICE_SIMULATE', true),

    // 팝빌 연동 인증
    'LinkID'    => env('POPBILL_ID', ''),
    'SecretKey' => env('POPBILL_SECRET_KEY', ''),

    // 팝빌 환경
    'IsTest'        => env('POPBILL_IS_TEST', true),          // 테스트베드
    'IPRestrictOnOff' => env('POPBILL_IP_RESTRICT_ON_OFF', true),
    'UseStaticIP'   => env('POPBILL_USE_STATIC_IP', false),
    'UseLocalTimeYN' => env('POPBILL_USE_LOCAL_TIME_YN', true),
    'CommMode'      => env('POPBILL_LINKHUB_COMM_MODE', 'CURL'),

    // 공급자(발행자) 사업자번호 — 하이픈 없는 10자리
    'corp_num' => env('POPBILL_CORP_NUM', '1018683744'),
    'user_id'  => env('POPBILL_USER_ID', ''),

    // 세금계산서 공급자 정보 (config('company') 재사용)
    'supplier' => [
        'corp_name' => env('COMPANY_NAME', '주식회사 한국안전'),
        'ceo_name'  => env('COMPANY_CEO', '임현규'),
        'addr'      => env('COMPANY_ADDR', '서울특별시 종로구 돈화문로 94, 1층 (와룡동, 동원빌딩)'),
        'biz_class' => env('COMPANY_BIZ_CLASS', '도매 및 소매업'),
        'biz_type'  => env('COMPANY_BIZ_TYPE', '산업안전용품·청소용품·소방안전기구'),
        'tel'       => env('COMPANY_TEL', '02-2273-9533'),
        'email'     => env('COMPANY_EMAIL', 'hks2273@naver.com'),
    ],
];
