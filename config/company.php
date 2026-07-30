<?php

/*
|--------------------------------------------------------------------------
| 공급자(자사) 정보 — 거래명세서·세금계산서 발행처
|--------------------------------------------------------------------------
| 회사소개(주식회사 한국안전) 기준 기본값. 필요 시 .env로 덮어쓴다.
*/

return [
    'name'      => env('COMPANY_NAME', '주식회사 한국안전'),
    'ceo'       => env('COMPANY_CEO', '임현규'),
    'biz_no'    => env('COMPANY_BIZ_NO', '101-86-83744'),      // 사업자등록번호
    'corp_no'   => env('COMPANY_CORP_NO', '110111-5230026'),   // 법인등록번호
    'biz_class' => env('COMPANY_BIZ_CLASS', '도매 및 소매업'),  // 업태
    'biz_type'  => env('COMPANY_BIZ_TYPE', '산업안전용품·청소용품·소방안전기구'), // 종목
    'address'   => env('COMPANY_ADDR', '서울특별시 종로구 돈화문로 94, 1층 (와룡동, 동원빌딩)'),
    'tel'       => env('COMPANY_TEL', '02-2273-9533'),
    'fax'       => env('COMPANY_FAX', '02-2279-1354'),
    'email'     => env('COMPANY_EMAIL', 'hks2273@naver.com'),

    // 입금 계좌 (거래명세서 하단 표기) — 필요 시 .env로 설정
    'bank'      => env('COMPANY_BANK', ''),
    'bank_acct' => env('COMPANY_BANK_ACCT', ''),
    'bank_holder' => env('COMPANY_BANK_HOLDER', '주식회사 한국안전'),
];
