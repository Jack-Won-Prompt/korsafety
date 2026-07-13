# KOR SAFETY — 산업안전용품 마켓플레이스

Laravel 11 기반의 산업안전용품 쇼핑몰 + 멀티벤더(본사·입점 판매점·협력사) 관리 시스템입니다.

## 주요 기능
- **스토어프론트**: 상품 3,060개(9개 카테고리), 히어로 슬라이더, 카테고리/검색/상품상세, 장바구니
- **본사(Super Admin) 콘솔** `/admin`: 매출·주문 대시보드, 판매점 관리, 협력사 관리, 커미션 정산, 상품 CRUD, 사이트 설정
- **입점 판매점 콘솔** `/seller`: 자사 매출 대시보드, 상품 CRUD(이미지 업로드)
- **협력사(영업대행) 콘솔** `/agent`: 거래처(기업·병원) 관리, 주문 등록(실시간 상품검색), 커미션 현황 — 판매대금의 수수료율(기본 10%)을 결제완료 시 적립

## 기술 스택
- Laravel 11 / PHP 8.2 / MySQL
- 프런트엔드: Blade + 커스텀 CSS/JS (빌드 불필요)
- XAMPP Apache 서브폴더 호스팅(`localhost/korsafety`) — 루트 `index.php` + `.htaccess`

## 설치
```bash
composer install
cp .env.example .env
php artisan key:generate
# .env 에 MySQL(korsafety) 접속정보 설정 후
php artisan migrate
php artisan db:seed        # 카탈로그 + 마켓플레이스 + 협력사 시드
php artisan config:cache   # XAMPP 다중앱 환경변수 격리(필수)
```

> XAMPP mod_php 는 여러 Laravel 앱이 프로세스를 공유하므로, `.env` 변경 후에는 반드시 `php artisan config:cache` 를 다시 실행하세요.

## 상품 이미지 (별도 생성)
용량 문제로 상품 이미지(약 2.6GB)는 저장소에 포함하지 않습니다. `public/shop/img/` 는 스크래퍼로 재생성합니다.

```bash
node scripts/scrape.mjs          # 카탈로그 + 이미지 다운로드
node scripts/refresh-main.mjs    # 대표 이미지 보정
python scripts/dewatermark.py    # (선택) 배경 워터마크 정규화  ※ pip install pillow numpy scipy
php artisan db:seed --class=CatalogSeeder
```

## 관리 콘솔 데모 계정
| 역할 | 계정 |
|---|---|
| 본사 관리자 | `admin@korsafety.kr` / `korsafety2013` |
| 입점 판매점 | `delta@partner.kr` / `seller123` |
| 협력사 | `agent@partner.kr` / `agent123` |
