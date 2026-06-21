<?php

namespace App\Mail;

use App\Models\Tender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenderClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tender $tender,
        public string $reason = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tender Closed: ' . $this->tender->tender_number . ' – ' . $this->tender->title
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tender-closed',
            with: [
                'tender' => $this->tender,
                'reason' => $this->reason,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
