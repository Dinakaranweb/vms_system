<?php

namespace App\Mail;

use App\Models\TenderApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationShortlistedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenderApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Application Has Been Shortlisted – ' . $this->application->tender->tender_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.application_shortlisted');
    }
}
