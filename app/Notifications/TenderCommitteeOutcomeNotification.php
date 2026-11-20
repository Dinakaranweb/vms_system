<?php

namespace App\Notifications;

use App\Models\TenderCommitteeMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells whoever put somebody forward for a tender's committee how the request ended.
 *
 * Without this, a Tender Owner who invites an expert onto their tender hears nothing further,
 * however long the main committee takes, and cannot tell a stalled request from a refused one.
 * Also goes to the main members who already decided, so everyone who put their name to it
 * learns the outcome.
 */
class TenderCommitteeOutcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TenderCommitteeMember $seat,
        public bool $approved,
        public ?string $decidedBy = null,
        public ?string $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $tender    = $this->seat->tender;
        $candidate = $this->seat->user;
        $who       = $candidate->name ?? 'The person put forward';
        $what      = $tender->title ?? 'a tender';

        $mail = (new MailMessage)
            ->subject(($this->approved ? 'Committee addition approved: ' : 'Committee addition rejected: ') . $who)
            ->greeting("Hello {$notifiable->name},");

        if ($this->approved) {
            $mail->success()
                ->line("The Purchase Committee has approved **{$who}** onto the committee for **{$what}**.")
                ->line($this->decidedBy ? "The final approval was given by {$this->decidedBy}." : 'The final approval has been given.')
                ->line('They have been emailed about the assignment and can now see this tender.');

            if ($tender && $tender->isOpeningOtpPending()) {
                $mail->line('This tender is mid-opening. That is unaffected — opening codes are held by the Purchase Committee, not by members added to a single tender — and they will see the documents once every code has been collected.');
            }
        } else {
            $mail->error()
                ->line("**{$who}** has not been added to the committee for **{$what}**.")
                ->line($this->decidedBy ? "Rejected by {$this->decidedBy}." : 'Rejected during committee review.');

            if (trim((string) $this->reason) !== '') {
                $mail->line("Reason given: {$this->reason}");
            }

            $mail->line('The approval order stops here — no later member was asked. They have not been told anything.');
        }

        if ($tender) {
            $mail->action('View the tender', route('admin.tenders.show', $tender->id));
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        $tender    = $this->seat->tender;
        $candidate = $this->seat->user;

        return [
            'type'            => 'tender_committee_outcome',
            'seat_id'         => $this->seat->id,
            'tender_id'       => $this->seat->tender_id,
            'tender_title'    => $tender->title ?? null,
            'candidate_id'    => $this->seat->user_id,
            'candidate_name'  => $candidate->name ?? null,
            'approved'        => $this->approved,
            'decided_by'      => $this->decidedBy,
            'reason'          => $this->reason,
            'message_preview' => ($candidate->name ?? 'Someone')
                . ($this->approved ? ' was approved onto the committee for ' : ' was not added to the committee for ')
                . ($tender->title ?? 'a tender') . '.',
        ];
    }
}
