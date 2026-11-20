<?php

namespace App\Notifications;

use App\Models\Tender;
use App\Services\OpeningCodeMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Delivers a Purchase Committee member their one-time opening code for a tender.
 *
 * The code lands in the member's own login (notification bell + notifications page) and in
 * their inbox. The member does NOT enter it anywhere themselves — they read it out to the
 * Purchase Officer in person, and the officer keys every member's code into a single opening
 * screen. That is what makes the opening a joint act: nobody can open a tender alone, and no
 * member can be impersonated from their mailbox alone.
 */
class TenderOpeningOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tender $tender,
        public string $code,
        public \DateTimeInterface $expiresAt,
        public ?string $officerName = null,
        /**
         * The tender_review_otps row this code belongs to, so the MessageSent listener can
         * record the mail server's reply against it. Nullable because the code itself does
         * not depend on it - without it the mail still goes, just unreported.
         */
        public ?int $codeRowId = null
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $officer = $this->officerName ? " by {$this->officerName}" : '';

        $mail = (new MailMessage)
            ->subject("Your opening code for Tender #{$this->tender->tender_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Opening has been initiated{$officer} for:")
            ->line("**{$this->tender->title}** (#{$this->tender->tender_number})")
            ->line("Your one-time opening code is: **{$this->code}**")
            ->line('Share this code with the Purchase Officer in person. The officer enters every committee member\'s code together, and the tender opens only once all of them have been entered.')
            ->line('This code expires at ' . $this->expiresAt->format('d M Y, h:i A') . '. Do not forward it by email or message.')
            ->line('Once the tender is open, the submitted documents become available in your own login.');

        // Carries the row id out and back so the reply can be matched to the right holder
        // rather than guessed from the recipient address. Metadata only - never the code.
        if ($this->codeRowId !== null) {
            $mail->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader(OpeningCodeMailer::ROW_HEADER, (string) $this->codeRowId);
            });
        }

        return $mail;
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'tender_opening_otp',
            'tender_id'       => $this->tender->id,
            'tender_title'    => $this->tender->title,
            'tender_number'   => $this->tender->tender_number,
            'submission_mode' => $this->tender->tender_type,
            'otp_code'        => $this->code,
            'expires_at'      => $this->expiresAt->format('Y-m-d H:i:s'),
            'officer_name'    => $this->officerName,
            'message_preview' => 'Your opening code is ' . $this->code . ' — read it out to the Purchase Officer.',
        ];
    }
}
