<?php

namespace App\Notifications;

use App\Models\Vendor;
use App\Models\VendorApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells one Purchase Committee member that a registered vendor is now waiting on them.
 *
 * Sent only to the member whose step is current, never to the whole committee at once: the
 * chain is walked in the order the Super Admin set, and telling everyone immediately would
 * invite approvals out of order and make the ordering meaningless.
 */
class VendorApprovalStepNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Vendor $vendor,
        public VendorApproval $step,
        public bool $isFinalStep = false,
        public ?string $startedBy = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('purchase-committee.vendor-approvals.show', $this->vendor->id);

        $mail = (new MailMessage)
            ->subject("Vendor approval needed: {$this->vendor->company_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A vendor registration has reached your step in the approval order and is waiting on you.")
            ->line("**{$this->vendor->company_name}** ({$this->vendor->vendor_code})")
            ->line("GST number: {$this->vendor->gst_number}")
            ->line("PAN number: {$this->vendor->pan_number}");

        if ($this->startedBy) {
            $mail->line("Cleared by the Purchase Officer, {$this->startedBy}, and passed to the committee.");
        }

        $mail->line("Your position in the order: step {$this->step->approval_order}"
            . ($this->step->designation ? " ({$this->step->designation})" : ''));

        if ($this->isFinalStep) {
            $mail->line('**You are the final approver.** The vendor becomes active and is told their account is approved the moment you approve.');
        } else {
            $mail->line('Once you approve, the registration passes to the next member in the order. Nothing is sent to the vendor until the final approver has signed off.');
        }

        return $mail
            ->action('Review this vendor', $url)
            ->line('You can read the company details and open every document they filed before deciding.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'vendor_approval_step',
            'vendor_id'       => $this->vendor->id,
            'vendor_code'     => $this->vendor->vendor_code,
            'company_name'    => $this->vendor->company_name,
            'approval_order'  => $this->step->approval_order,
            'designation'     => $this->step->designation,
            'is_final_step'   => $this->isFinalStep,
            'started_by'      => $this->startedBy,
            'url'             => route('purchase-committee.vendor-approvals.show', $this->vendor->id),
            'message_preview' => $this->vendor->company_name . ' is waiting for your approval'
                . ($this->isFinalStep ? ' — you are the final approver.' : '.'),
        ];
    }
}
