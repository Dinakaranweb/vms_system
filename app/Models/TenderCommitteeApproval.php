<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One main Purchase Committee member's step in approving one add-on seat on one tender.
 *
 * Walked strictly in approval_order, exactly as VendorApproval is: only the lowest-ordered
 * outstanding step is actionable, and any single rejection ends the request.
 */
class TenderCommitteeApproval extends Model
{
    protected $fillable = [
        'tender_committee_id', 'committee_member_id', 'approval_order', 'designation',
        'status', 'comments', 'notified_at', 'decided_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'decided_at'  => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(TenderCommitteeMember::class, 'tender_committee_id');
    }

    public function committeeMember()
    {
        return $this->belongsTo(User::class, 'committee_member_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * True only for the one step the committee is actually waiting on.
     *
     * False once the request itself has been settled, which is what stops a stale screen or
     * a bookmarked link from recording a decision on a seat already granted or refused.
     */
    public function isCurrentStep(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $request = $this->request;
        if (!$request || !$request->isPending()) {
            return false;
        }

        return !static::where('tender_committee_id', $this->tender_committee_id)
            ->where('status', 'pending')
            ->where('approval_order', '<', $this->approval_order)
            ->exists();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
