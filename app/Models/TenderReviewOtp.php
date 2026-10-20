<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderReviewOtp extends Model
{
    protected $fillable = [
        'tender_id', 'committee_member_id', 'otp_code', 'otp_expires_at',
        'otp_sent_at', 'otp_verified_at', 'otp_collected_by',
    ];

    protected $casts = [
        'otp_expires_at'  => 'datetime',
        'otp_sent_at'     => 'datetime',
        'otp_verified_at' => 'datetime',
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
}
