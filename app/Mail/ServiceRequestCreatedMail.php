<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** SR 신규 접수 알림 — 담당 부서로 발송 */
class ServiceRequestCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceRequest $sr)
    {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');
        $this->sr->loadMissing('user');
        $urgent = in_array($this->sr->priority, ['high', 'urgent'], true) ? '['.$this->sr->priority_label.'] ' : '';

        return $this->subject('['.$company.'] '.$urgent.'SR 접수 · '.$this->sr->sr_no.' — '.$this->sr->title)
            ->view('emails.sr-created');
    }
}
