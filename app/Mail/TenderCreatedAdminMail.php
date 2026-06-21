<?php

namespace App\Mail;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenderCreatedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tender $tender,
        public User   $owner
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Tender Created — ' . $this->tender->tender_number . ': ' . $this->tender->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tender_created_admin',
        );
    }
}
