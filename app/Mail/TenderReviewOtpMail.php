<?php

namespace App\Mail;

use App\Models\TenderReviewOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenderReviewOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenderReviewOtp $reviewOtp, public string $otpCode) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP to Review Applications – ' . $this->reviewOtp->tender->tender_number
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tender-review-otp', with: [
            'reviewOtp' => $this->reviewOtp,
            'otpCode'   => $this->otpCode,
        ]);
    }
}
