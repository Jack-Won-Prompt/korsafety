<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agent 는 주기 실행으로 돌지 않는다.
// 오류·SR이 접수되는 그 자리에서 웹훅(/agent/hook)을 보내 바로 처리하고,
// 웹훅을 놓친 건은 다음 웹훅이 올 때 함께 따라잡는다.
// 손으로 돌려야 할 때만 `php artisan agent:work` 를 쓴다.
