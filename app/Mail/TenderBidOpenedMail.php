<?php

namespace App\Mail;

use App\Models\Tender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenderBidOpenedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tender $tender) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bids Opened: ' . $this->tender->tender_number . ' – ' . $this->tender->title
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tender-bid-opened', with: ['tender' => $this->tender]);
    }
}
