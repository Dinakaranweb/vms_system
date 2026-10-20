<?php

namespace App\Mail;

use App\Models\AwardRecommendationApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AwardApprovalOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AwardRecommendationApproval $approval, public string $otpCode) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP for Award Confirmation – ' . $this->approval->recommendation->tender->tender_number
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.award-approval-otp', with: [
            'approval' => $this->approval,
            'otpCode'  => $this->otpCode,
        ]);
    }
}
