<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderReviewOtp extends Model
{
    protected $fillable = [
        // holder_role is 'main', 'officer' or 'add-on' — why this person holds a code, so the
        // opening screen can say who each one is rather than listing bare names.
        'tender_id', 'committee_member_id', 'holder_role', 'otp_code', 'otp_expires_at',
        'otp_sent_at', 'otp_verified_at', 'otp_collected_by',
        // What the mail server said when this code was sent. See OpeningCodeMailer.
        'mail_status', 'mail_response', 'mail_message_id', 'mail_attempted_at',
    ];

    protected $casts = [
        'otp_expires_at'    => 'datetime',
        'otp_sent_at'       => 'datetime',
        'otp_verified_at'   => 'datetime',
        'mail_attempted_at' => 'datetime',
    ];

    protected $hidden = ['otp_code'];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function committeeMember()
    {
        return $this->belongsTo(User::class, 'committee_member_id');
    }

    /**
     * The Purchase Officer who keyed this member's code in on the opening screen.
     */
    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'otp_collected_by');
    }

    public function isOtpValid(string $code): bool
    {
        return $this->otp_code === $code
            && $this->otp_expires_at !== null
            && $this->otp_expires_at->isFuture()
            && $this->otp_verified_at === null;
    }

    public function isVerified(): bool
    {
        return $this->otp_verified_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->otp_verified_at === null
            && $this->otp_expires_at !== null
            && $this->otp_expires_at->isPast();
    }

    /**
     * True only where the mail server refused the code outright. Those are the holders who
     * certainly have nothing in their inbox and must be chased another way.
     */
    public function mailFailed(): bool
    {
        return in_array($this->mail_status, ['rejected', 'error', 'no-address'], true);
    }

    /**
     * How the send should be described on screen.
     *
     * "Accepted by the mail server" is as strong as this can honestly be put: a relay can take
     * an address and bounce it minutes later, out of band, so nothing here may claim delivery.
     */
    public function mailLabel(): string
    {
        return match ($this->mail_status) {
            'accepted'   => 'Accepted by the mail server',
            'rejected'   => 'Refused by the mail server',
            'error'      => 'Send failed',
            'no-address' => 'No email address',
            default      => 'Not reported',
        };
    }

    /** success | danger | muted — drives the badge colour wherever this is shown. */
    public function mailTone(): string
    {
        return match ($this->mail_status) {
            'accepted'                      => 'success',
            'rejected', 'error', 'no-address' => 'danger',
            default                         => 'muted',
        };
    }
}
