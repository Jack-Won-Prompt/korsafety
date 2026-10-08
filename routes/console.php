<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 대기 중인 Agent 일감 처리 — 접수된 오류·SR을 5분마다 살핀다.
// 기능이 꺼져 있거나 API 키가 없으면 아무 일도 하지 않고 지나간다.
Schedule::command('agent:work --limit=3')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();
