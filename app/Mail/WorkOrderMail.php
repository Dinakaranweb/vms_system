<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class WorkOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public string $pdfContent,
        public string $pdfFilename,
        public string $pdfMime = 'application/pdf'
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->contract->order_type === 'work_order' ? 'Work Order' : 'Purchase Order';
        return new Envelope(
            subject: $label . ' ' . $this->contract->order_number . ' – VMRF-DU'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.work_order',
            with: ['contract' => $this->contract]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime($this->pdfMime),
        ];
    }
}
