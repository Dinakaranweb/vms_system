<?php

namespace App\Notifications;

use App\Models\Tender;
use App\Models\TenderApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every active Purchase Committee member when the physical documents received against
 * a postal/hybrid application are uploaded or scanned in. Each member gets this in their own
 * login (notification bell) and at their own email address — the two channels below — so the
 * committee learns about the papers without anyone having to chase the Purchase Officer.
 */
class ReceivedDocumentsUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tender $tender,
        public TenderApplication $application,
        public int $fileCount,
        public string $source = 'upload',
        public ?string $uploaderName = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    private function vendorName(): string
    {
        return $this->application->vendor->company_name
            ?? $this->application->vendor->name
            ?? 'Unknown vendor';
    }

    private function headline(): string
    {
        $what = $this->source === 'scan'
            ? $this->fileCount . ' scanned page(s)'
            : $this->fileCount . ' received document(s)';

        return $what . ' recorded for ' . $this->vendorName();
    }

    public function toMail($notifiable): MailMessage
    {
        $by = $this->uploaderName ? " by {$this->uploaderName}" : '';

        return (new MailMessage)
            ->subject("Received documents uploaded – Tender #{$this->tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The physical documents received against a submission have been recorded{$by}:")
            ->line("**{$this->tender->title}** (#{$this->tender->tender_number})")
            ->line("Vendor: **{$this->vendorName()}** ({$this->application->application_number})")
            ->line($this->headline())
            ->action('View Received Documents', route('purchase-committee.review.documents', $this->tender->id))
            ->line('They are available in your login alongside the rest of this tender\'s submissions.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'             => 'received_documents_uploaded',
            'tender_id'        => $this->tender->id,
            'tender_title'     => $this->tender->title,
            'tender_number'    => $this->tender->tender_number,
            'submission_mode'  => $this->tender->tender_type,
            'application_id'   => $this->application->id,
            'application_number' => $this->application->application_number,
            'vendor_name'      => $this->vendorName(),
            'file_count'       => $this->fileCount,
            'source'           => $this->source,
            'uploader_name'    => $this->uploaderName,
            'message_preview'  => $this->headline(),
        ];
    }
}
