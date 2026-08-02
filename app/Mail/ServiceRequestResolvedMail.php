<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** SR 처리완료(적용 완료) 안내 — 등록자에게 발송 */
class ServiceRequestResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceRequest $sr)
    {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');
        $this->sr->loadMissing(['user', 'assignee', 'replies.user']);

        return $this->subject('['.$company.'] SR 처리완료 안내 · '.$this->sr->sr_no)
            ->view('emails.sr-resolved');
    }
}
