<?php

namespace App\Notifications;

use App\Models\TenderQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenderQueryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TenderQuery $query,
        public string $type = 'query' // 'query' or 'reply'
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $tender = $this->query->tender;
        $sender = $this->query->sender;
        $label  = $this->type === 'reply' ? 'Reply to your query' : 'New query on a tender';

        return (new MailMessage)
            ->subject("{$label} – Tender #{$tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$sender->name} has sent a " . ($this->type === 'reply' ? 'reply' : 'query') . " regarding:")
            ->line("**{$tender->title}** (#{$tender->tender_number})")
            ->line(strip_tags($this->query->message))
            ->action('View Tender', url("/tenders/{$tender->id}"))
            ->line('Please log in to the VMS portal to respond.');
    }

    public function toArray($notifiable): array
    {
        $tender = $this->query->tender;
        $sender = $this->query->sender;

        return [
            'tender_query_id' => $this->query->id,
            'tender_id'       => $tender->id,
            'tender_title'    => $tender->title,
            'tender_number'   => $tender->tender_number,
            'sender_name'     => $sender->name,
            'sender_role'     => $this->query->sender_role,
            'type'            => $this->type,
            'message_preview' => strip_tags(substr($this->query->message, 0, 120)),
        ];
    }
}
