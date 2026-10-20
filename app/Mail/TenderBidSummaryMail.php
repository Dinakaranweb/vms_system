<?php

namespace App\Mail;

use App\Models\Tender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenderBidSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tender $tender, public string $spreadsheetPath) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bid Summary — ' . $this->tender->tender_number . ': ' . $this->tender->title
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tender-bid-summary', with: ['tender' => $this->tender]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->spreadsheetPath)
                ->as('bid-summary-' . $this->tender->tender_number . '.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
