<?php

namespace App\Notifications;

use App\Models\TenderCommitteeApproval;
use App\Models\TenderCommitteeMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells one main Purchase Committee member that somebody has been put forward for a seat on
 * one tender's committee, and that it is their turn to decide.
 *
 * Sent only to the member whose step is current. Telling the whole committee at once would
 * make the Super Admin's ordering meaningless.
 */
class TenderCommitteeRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public TenderCommitteeMember $seat,
        public TenderCommitteeApproval $step,
        public bool $isFinalStep = false,
        public ?string $requestedBy = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $tender    = $this->seat->tender;
        $candidate = $this->seat->user;
        $url       = route('purchase-committee.committee-requests.show', $this->seat->id);

        $mail = (new MailMessage)
            ->subject('Committee addition needs your approval: ' . ($tender->title ?? 'a tender'))
            ->greeting("Hello {$notifiable->name},")
            ->line('Somebody has been put forward to join the committee for a tender, and the request has reached your step in the approval order.')
            ->line('**' . ($candidate->name ?? 'A user') . '**'
                . ($this->seat->designation ? ' — ' . $this->seat->designation : '')
                . ' as ' . str_replace('_', ' ', (string) $this->seat->role))
            ->line('Tender: **' . ($tender->title ?? '—') . '** (#' . ($tender->tender_number ?? '—') . ')');

        if ($this->requestedBy) {
            $mail->line("Put forward by {$this->requestedBy}.");
        }

        $mail->line("Your position in the order: step {$this->step->approval_order}"
            . ($this->step->designation ? " ({$this->step->designation})" : ''));

        if ($this->isFinalStep) {
            $mail->line('**You are the final approver.** Approving adds them to the tender, tells them so, and gives them an opening code if that tender is mid-opening.');
        } else {
            $mail->line('Once you approve, the request passes to the next member in the order. Nothing reaches the person put forward until the final approver has signed off.');
        }

        return $mail
            ->action('Review this request', $url)
            ->line('A rejection by any one approver ends the request, and no later member is asked.');
    }

    public function toArray($notifiable): array
    {
        $tender    = $this->seat->tender;
        $candidate = $this->seat->user;

        return [
            'type'            => 'tender_committee_request',
            'seat_id'         => $this->seat->id,
            'tender_id'       => $this->seat->tender_id,
            'tender_title'    => $tender->title ?? null,
            'tender_number'   => $tender->tender_number ?? null,
            'candidate_id'    => $this->seat->user_id,
            'candidate_name'  => $candidate->name ?? null,
            'committee_role'  => $this->seat->role,
            'approval_order'  => $this->step->approval_order,
            'is_final_step'   => $this->isFinalStep,
            'requested_by'    => $this->requestedBy,
            'url'             => route('purchase-committee.committee-requests.show', $this->seat->id),
            'message_preview' => ($candidate->name ?? 'Someone')
                . ' has been put forward for the committee on ' . ($tender->title ?? 'a tender')
                . ($this->isFinalStep ? ' — you are the final approver.' : '.'),
        ];
    }
}
