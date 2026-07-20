<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetUserPasswords extends Command
{
    protected $signature = 'users:reset-password {password=1234}';

    protected $description = '모든 사용자 비밀번호를 지정 값(기본 1234)으로 재설정';

    public function handle(): int
    {
        $password = (string) $this->argument('password');
        $count = 0;

        User::query()->orderBy('id')->each(function (User $user) use ($password, &$count) {
            $user->password = $password; // 모델의 'hashed' 캐스트로 자동 해시
            $user->save();
            $count++;
            $this->line("  · {$user->email} ({$user->role})");
        });

        $this->info("완료: {$count}명 비밀번호를 '{$password}'(으)로 재설정했습니다.");

        return self::SUCCESS;
    }
}
