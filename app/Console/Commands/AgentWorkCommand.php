<?php

namespace App\Console\Commands;

use App\Services\AgentWorker;
use Illuminate\Console\Command;

/** 대기 중인 Agent 일감을 처리한다 (예약 실행 · 손으로 확인할 때 공용) */
class AgentWorkCommand extends Command
{
    protected $signature = 'agent:work {--limit=3 : 한 번에 처리할 건수}';

    protected $description = '운영 오류·SR 일감을 Agent 가 처리합니다';

    public function handle(): int
    {
        $result = (new AgentWorker())->run((int) $this->option('limit'));

        foreach ($result['글'] as $line) {
            $this->line('  '.$line);
        }
        $this->info("처리 {$result['처리']}건 · 보류 {$result['건너뜀']}건");

        return self::SUCCESS;
    }
}
