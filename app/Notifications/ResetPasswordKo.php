<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordKo extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('[KOR SAFETY] 비밀번호 재설정 안내')
            ->greeting('안녕하세요, KOR SAFETY입니다.')
            ->line('회원님의 계정에 대한 비밀번호 재설정 요청이 접수되었습니다.')
            ->line('아래 버튼을 눌러 새 비밀번호를 설정해 주세요.')
            ->action('비밀번호 재설정', $url)
            ->line("이 링크는 {$expire}분 후 만료됩니다.")
            ->line('본인이 요청하지 않으셨다면 이 메일을 무시하셔도 됩니다.')
            ->salutation('감사합니다. 주식회사 한국안전 드림');
    }
}
