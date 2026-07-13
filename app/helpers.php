<?php

if (! function_exists('img_url')) {
    /**
     * 상품 이미지 경로를 앱에서 접근 가능한 절대 URL로 변환.
     * 저장 형식 예: "/shop/img/493/photo.jpg"
     */
    function img_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
