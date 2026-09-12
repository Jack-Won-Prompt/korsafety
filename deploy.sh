#!/usr/bin/env bash
# korsafety 운영 배포 스크립트 — 사용법: ~/www/korsafety 에서 `bash deploy.sh`
#
# 전체 본문을 main() 안에 두고 마지막 줄에서 호출·종료합니다.
# bash 는 스크립트를 읽어 가며 실행하므로, git pull 이 이 파일 자체를 바꿔도
# 이미 파싱된 함수만 실행되도록 하기 위함입니다.

set -euo pipefail

# 전역으로 둡니다 — EXIT trap 은 main() 이 끝난 뒤에도 실행되므로 local 이면 참조할 수 없습니다.
PHP_BIN="php"
COMPOSER_BIN="/usr/bin/composer"

main() {
    # 1. 스크립트가 있는 위치로 이동
    cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")"

    # 2. 실패·중단 시에도 점검 모드가 남지 않도록 보장
    trap '"$PHP_BIN" artisan up || true' EXIT
    trap 'exit 129' HUP
    trap 'exit 130' INT
    trap 'exit 143' TERM

    local BEFORE
    BEFORE="$(git rev-parse --short HEAD)"

    # 3. 점검 모드 켜기
    echo "==> [1/6] 점검 모드 켜기 (php artisan down)"
    "$PHP_BIN" artisan down

    # 4. 코드 받기
    echo "==> [2/6] 코드 받기 (git pull origin main)"
    git pull origin main

    # 5. 의존성 설치 (--no-dev 사용 안 함)
    echo "==> [3/6] 의존성 설치 (composer install)"
    "$COMPOSER_BIN" install --no-interaction --prefer-dist --optimize-autoloader

    # 6. 캐시 비우기 (route:cache 는 클로저 라우트 때문에 사용하지 않음)
    echo "==> [4/6] 캐시 비우기 (config:clear, route:clear, view:clear)"
    "$PHP_BIN" artisan config:clear
    "$PHP_BIN" artisan route:clear
    "$PHP_BIN" artisan view:clear

    # 7. 마이그레이션
    echo "==> [5/6] 마이그레이션 (php artisan migrate --force)"
    "$PHP_BIN" artisan migrate --force

    # 8. 점검 모드 해제 및 결과 출력
    echo "==> [6/6] 점검 모드 해제 (php artisan up)"
    "$PHP_BIN" artisan up

    local AFTER
    AFTER="$(git rev-parse --short HEAD)"

    if [ "$BEFORE" = "$AFTER" ]; then
        echo "배포 완료: ${AFTER} (변경 없음) — 롤백 불필요"
    else
        echo "배포 완료: ${BEFORE} -> ${AFTER} — 롤백: (새 마이그레이션이 있었다면 먼저 php artisan migrate:rollback) git reset --hard ${BEFORE} && ${COMPOSER_BIN} install --no-interaction --prefer-dist --optimize-autoloader && php artisan config:clear && php artisan route:clear && php artisan view:clear"
    fi
}

main "$@"
exit
