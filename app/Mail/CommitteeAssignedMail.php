<?php

namespace App\Mail;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommitteeAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $member,
        public Tender $tender,
        public string $committeeRole,
        public ?string $designation = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You Have Been Added to a Tender Committee — ' . $this->tender->tender_number
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.committee_assigned');
    }
}
