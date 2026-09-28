<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'api.role' => \App\Http\Middleware\EnsureApiRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // api/* 는 물론, JSON을 기대하는 AJAX 요청(fetch 업로드 등)에도 JSON으로 응답한다.
        // 이게 빠지면 검증 실패가 302 리다이렉트로 나가고, fetch는 그 뒤 HTML 페이지를
        // 받아 "Unexpected token '<'" JSON 파싱 오류만 보이게 된다.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 보고 대상 예외(검증 · 인증 · 404 등 제외)를 error_logs 테이블에도 기록 — 기본 파일 로그는 그대로 유지
        $exceptions->report(function (\Throwable $e) {
            \App\Models\ErrorLog::capture($e);
        });

        // SupportWorks 로도 보낸다 — 응답 뒤에 · 2초 제한 · 실패해도 예외를 내보내지 않음
        $exceptions->report(function (\Throwable $e) {
            \App\Support\SupportWorksReporter::report($e, request());
        });
    })->create();
