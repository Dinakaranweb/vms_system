<?php

namespace App\Mail;

use App\Models\AwardRecommendation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AwardApprovalStepMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AwardRecommendation $recommendation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Award Approval Needed – ' . $this->recommendation->tender->tender_number
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.award-approval-step', with: ['recommendation' => $this->recommendation]);
    }
}
