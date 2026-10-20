<?php

namespace App\Notifications;

use App\Models\AwardFinalization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every active Purchase Committee member when the Purchase Officer finalizes an award,
 * and again if a finalization is revoked. The committee's recommendations are advisory, so the
 * members do not sign the decision off — but they are told what was decided, on what value,
 * how the discussion was held and who took part, in their own login and at their own email.
 */
class AwardFinalizedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public AwardFinalization $finalization,
        public bool $revoked = false
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    private function vendorName(): string
    {
        return $this->finalization->application->vendor->company_name
            ?? $this->finalization->application->vendor->name
            ?? 'Unknown vendor';
    }

    private function money(): string
    {
        return $this->finalization->awarded_value === null
            ? 'N/A'
            : '₹' . number_format((float) $this->finalization->awarded_value, 2);
    }

    public function toMail($notifiable): MailMessage
    {
        $tender = $this->finalization->tender;

        if ($this->revoked) {
            return (new MailMessage)
                ->subject("Award finalization withdrawn – Tender #{$tender->tender_number}")
                ->greeting("Hello {$notifiable->name},")
                ->line("The award finalization for **{$tender->title}** (#{$tender->tender_number}) has been withdrawn.")
                ->line("It had selected **{$this->vendorName()}** at {$this->money()}.")
                ->line($this->finalization->revoked_reason
                    ? 'Reason: ' . $this->finalization->revoked_reason
                    : 'No reason was recorded.')
                ->line('No contract will be issued against it unless the award is finalized again.');
        }

        $participants = $this->finalization->participantUsers();

        $mail = (new MailMessage)
            ->subject("Award finalized – Tender #{$tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The Purchase Officer has finalized the award for:")
            ->line("**{$tender->title}** (#{$tender->tender_number})")
            ->line("Awarded to: **{$this->vendorName()}** at {$this->money()}")
            ->line("Consulted by: {$this->finalization->getDiscussionModeLabel()}"
                . ($this->finalization->discussion_held_at
                    ? ' on ' . $this->finalization->discussion_held_at->format('d M Y, h:i A')
                    : ''))
            ->line('Finalized by: ' . ($this->finalization->finalizedBy->name ?? 'the Purchase Officer'));

        if ($participants->isNotEmpty()) {
            $mail->line('Committee members recorded as taking part: ' . $participants->pluck('name')->implode(', '));
        }

        return $mail
            ->line('Decision note: ' . $this->finalization->decision_note)
            ->action('View Tender Documents', route('purchase-committee.review.documents', $tender->id))
            ->line('The contract will be issued to the selected applicant from here.');
    }

    public function toArray($notifiable): array
    {
        $tender = $this->finalization->tender;

        return [
            'type'               => $this->revoked ? 'award_finalization_revoked' : 'award_finalized',
            'tender_id'          => $tender->id,
            'tender_title'       => $tender->title,
            'tender_number'      => $tender->tender_number,
            'application_id'     => $this->finalization->application_id,
            'application_number' => $this->finalization->application->application_number ?? null,
            'vendor_name'        => $this->vendorName(),
            'awarded_value'      => $this->finalization->awarded_value,
            'discussion_mode'    => $this->finalization->discussion_mode,
            'finalized_by'       => $this->finalization->finalizedBy->name ?? null,
            'participants'       => $this->finalization->participantUsers()->pluck('name')->all(),
            'revoked'            => $this->revoked,
            'message_preview'    => $this->revoked
                ? 'Award finalization for ' . $this->vendorName() . ' was withdrawn'
                : 'Awarded to ' . $this->vendorName() . ' at ' . $this->money()
                  . ' after ' . strtolower($this->finalization->getDiscussionModeLabel()),
        ];
    }
}
