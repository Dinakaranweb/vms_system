<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardRecommendationApproval extends Model
{
    protected $table = 'award_recommendation_approvals';

    protected $fillable = [
        'award_recommendation_id', 'committee_member_id', 'approval_order', 'status',
        'comments', 'decided_at', 'otp_code', 'otp_expires_at', 'otp_verified_at',
    ];

    protected $casts = [
        'decided_at'      => 'datetime',
        'otp_expires_at'  => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    protected $hidden = ['otp_code'];

    public function recommendation()
    {
        return $this->belongsTo(AwardRecommendation::class, 'award_recommendation_id');
    }

    public function committeeMember()
    {
        return $this->belongsTo(User::class, 'committee_member_id');
    }

    public function isOtpValid(string $code): bool
    {
        return $this->otp_code === $code
            && $this->otp_expires_at !== null
            && $this->otp_expires_at->isFuture()
            && $this->otp_verified_at === null;
    }
}
