<?php

namespace App\Notifications;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the staff who started a vendor's approval how it ended.
 *
 * Without this the Purchase Officer approves a registration and then hears nothing further,
 * however long the committee takes — the chain would be invisible to the person who began it.
 * Goes to the officer who cleared it, the super-admins, and the committee members who already
 * approved, so everyone who put their name to it learns the outcome.
 */
class VendorApprovalOutcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Vendor $vendor,
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
        $name = $this->vendor->company_name;

        $mail = (new MailMessage)
            ->subject(($this->approved ? 'Vendor approved: ' : 'Vendor rejected: ') . $name)
            ->greeting("Hello {$notifiable->name},");

        if ($this->approved) {
            $mail->success()
                ->line("The Purchase Committee has completed its approval of **{$name}** ({$this->vendor->vendor_code}).")
                ->line($this->decidedBy
                    ? "The final approval was given by {$this->decidedBy}."
                    : 'The final approval has been given.')
                ->line('The vendor can now sign in and bid, and has been emailed to say so.');
        } else {
            $mail->error()
                ->line("**{$name}** ({$this->vendor->vendor_code}) has been rejected by the Purchase Committee.")
                ->line($this->decidedBy ? "Rejected by {$this->decidedBy}." : 'Rejected during committee review.');

            if (trim((string) $this->reason) !== '') {
                $mail->line("Reason given: {$this->reason}");
            }

            $mail->line('The approval order stops here — no later member is asked. The vendor has been told.');
        }

        return $mail->action('View vendor', route('super-admin.vendors.show', $this->vendor->id));
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'vendor_approval_outcome',
            'vendor_id'       => $this->vendor->id,
            'vendor_code'     => $this->vendor->vendor_code,
            'company_name'    => $this->vendor->company_name,
            'approved'        => $this->approved,
            'decided_by'      => $this->decidedBy,
            'reason'          => $this->reason,
            'message_preview' => $this->vendor->company_name
                . ($this->approved ? ' was approved by the committee.' : ' was rejected by the committee.'),
        ];
    }
}
