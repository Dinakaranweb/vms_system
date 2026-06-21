<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vendor $vendor, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Vendor Registration – Application Update');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vendor_rejected');
    }
}
