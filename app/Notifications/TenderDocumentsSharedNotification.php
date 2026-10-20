<?php

namespace App\Notifications;

use App\Models\Tender;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every active Purchase Committee member the moment the last opening code is accepted
 * and the tender actually opens. From this point the submitted documents are readable in the
 * member's own login: online uploads for an online tender, the scanned postal submissions for
 * a postal one, and both for a hybrid tender.
 */
class TenderDocumentsSharedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tender $tender,
        public int $applicationCount = 0
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    private function scopeLine(): string
    {
        return match (true) {
            $this->tender->isHybrid() => 'Both the online submissions and the scanned postal submissions are now available to you.',
            $this->tender->isPostal() => 'The scanned postal submissions are now available to you as they are recorded.',
            default                   => 'The online submissions are now available to you.',
        };
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Documents shared – Tender #{$this->tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("All Purchase Committee opening codes have been entered, so this tender is now open:")
            ->line("**{$this->tender->title}** (#{$this->tender->tender_number})")
            ->line($this->scopeLine())
            ->line("Applications received: {$this->applicationCount}")
            ->action('View Shared Documents', route('purchase-committee.review.documents', $this->tender->id))
            ->line('You can now view the documents and record your review comments.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'              => 'tender_documents_shared',
            'tender_id'         => $this->tender->id,
            'tender_title'      => $this->tender->title,
            'tender_number'     => $this->tender->tender_number,
            'submission_mode'   => $this->tender->tender_type,
            'application_count' => $this->applicationCount,
            'message_preview'   => $this->scopeLine(),
        ];
    }
}
