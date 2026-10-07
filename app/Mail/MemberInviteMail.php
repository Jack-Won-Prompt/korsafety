<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** 회원 가입 초대 안내 */
class MemberInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');

        return $this->subject('['.$company.'] 회원 가입 안내 — 초대드립니다')
            ->view('emails.member-invite', [
                'invite' => $this->invitation,
                'link' => route('invite.show', $this->invitation->token),
            ]);
    }
}
