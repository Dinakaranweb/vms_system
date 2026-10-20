<?php

namespace App\Notifications;

use App\Models\ApplicationQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationQueryNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ApplicationQuery $query,
        public string $type = 'query' // 'query' or 'reply'
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $application = $this->query->application;
        $tender      = $application->tender;
        $sender      = $this->query->sender;
        $label       = $this->type === 'reply' ? 'Reply to your query' : 'New query on your application';

        return (new MailMessage)
            ->subject("{$label} – Tender #{$tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$sender->name} has sent a " . ($this->type === 'reply' ? 'reply' : 'query') . " regarding your application for:")
            ->line("**{$tender->title}** (#{$tender->tender_number})")
            ->line(strip_tags($this->query->message))
            ->action('View Application', url("/applications/{$application->id}"))
            ->line('Please log in to the VMS portal to respond.');
    }

    public function toArray($notifiable): array
    {
        $application = $this->query->application;
        $tender      = $application->tender;
        $sender      = $this->query->sender;

        return [
            'query_id'          => $this->query->id,
            'application_id'    => $application->id,
            'application_number'=> $application->application_number,
            'tender_id'         => $tender->id,
            'tender_title'      => $tender->title,
            'tender_number'     => $tender->tender_number,
            'sender_name'       => $sender->name,
            'sender_role'       => $this->query->sender_role,
            'type'              => $this->type,
            'message_preview'   => strip_tags(substr($this->query->message, 0, 120)),
        ];
    }
}
