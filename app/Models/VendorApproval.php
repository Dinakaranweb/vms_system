<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One Purchase Committee member's step in approving one registered vendor.
 *
 * The steps are walked strictly in approval_order: only the lowest-ordered step still marked
 * pending is actionable, so a later member cannot sign off before an earlier one has. The
 * vendor becomes approved when the last step is approved, and rejected the moment any one
 * step is rejected.
 *
 * approval_order and designation are copied from purchase_committee_members when the chain is
 * created rather than read back through the relation, so that reshuffling the committee after
 * the fact cannot rewrite a decision that has already been taken.
 */
class VendorApproval extends Model
{
    protected $fillable = [
        'vendor_id', 'committee_member_id', 'approval_order', 'designation',
        'status', 'comments', 'notified_at', 'decided_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'decided_at'  => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
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
     * Asked of a step whose vendor is no longer in committee_review this is false, which is
     * what stops a stale screen or a bookmarked link from recording a decision on a vendor
     * that has already been settled.
     */
    public function isCurrentStep(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $vendor = $this->vendor;
        if (!$vendor || !$vendor->isAwaitingCommittee()) {
            return false;
        }

        return !static::where('vendor_id', $this->vendor_id)
            ->where('status', 'pending')
            ->where('approval_order', '<', $this->approval_order)
            ->exists();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('approval_order');
    }
}
