<?php

namespace App\Notifications;

use App\Models\Tender;
use App\Models\TenderApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every active Purchase Committee member when a bid value is read off a physical quote
 * and keyed in at the opening table. For a postal tender this is the moment the price enters
 * the system at all, so the whole committee is told — in their own login and at their own
 * email address — including what the figure was changed from, if it was already set.
 */
class BidValueRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tender $tender,
        public TenderApplication $application,
        public ?float $newValue,
        public ?float $previousValue = null,
        public ?string $recordedByName = null
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

    private function money(?float $value): string
    {
        return $value === null ? 'N/A' : '₹' . number_format($value, 2);
    }

    private function wasChanged(): bool
    {
        return $this->previousValue !== null
            && abs((float) $this->previousValue - (float) $this->newValue) > 0.001;
    }

    private function headline(): string
    {
        return $this->wasChanged()
            ? 'Bid value for ' . $this->vendorName() . ' changed from ' . $this->money($this->previousValue) . ' to ' . $this->money($this->newValue)
            : 'Bid value for ' . $this->vendorName() . ' recorded as ' . $this->money($this->newValue);
    }

    public function toMail($notifiable): MailMessage
    {
        $by = $this->recordedByName ? " by {$this->recordedByName}" : '';

        $mail = (new MailMessage)
            ->subject("Bid value recorded – Tender #{$this->tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A bid value has been read off the physical quote and recorded{$by}:")
            ->line("**{$this->tender->title}** (#{$this->tender->tender_number})")
            ->line("Vendor: **{$this->vendorName()}** ({$this->application->application_number})")
            ->line("Bid value: **{$this->money($this->newValue)}**");

        if ($this->wasChanged()) {
            $mail->line('This replaced an earlier figure of ' . $this->money($this->previousValue) . '.');
        }

        return $mail
            ->action('Compare Bids', route('purchase-committee.review.compare-bids', $this->tender->id))
            ->line('Please verify it against the quote in the envelope.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'               => 'bid_value_recorded',
            'tender_id'          => $this->tender->id,
            'tender_title'       => $this->tender->title,
            'tender_number'      => $this->tender->tender_number,
            'submission_mode'    => $this->tender->tender_type,
            'application_id'     => $this->application->id,
            'application_number' => $this->application->application_number,
            'vendor_name'        => $this->vendorName(),
            'bid_amount'         => $this->newValue,
            'previous_amount'    => $this->previousValue,
            'was_changed'        => $this->wasChanged(),
            'recorded_by'        => $this->recordedByName,
            'message_preview'    => $this->headline(),
        ];
    }
}
