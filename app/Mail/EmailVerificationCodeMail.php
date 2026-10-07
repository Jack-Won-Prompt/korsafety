<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** 회원가입 이메일 인증번호 안내 */
class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public int $minutes = 10)
    {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');

        return $this->subject('['.$company.'] 회원가입 인증번호 '.$this->code)
            ->view('emails.verification-code');
    }
}
