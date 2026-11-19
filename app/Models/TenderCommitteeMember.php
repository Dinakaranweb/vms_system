<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One person's seat on one tender's committee — the add-on members, as distinct from the
 * standing Purchase Committee that PurchaseCommitteeMember describes.
 *
 * A seat is requested rather than simply granted: it starts pending, the main Purchase
 * Committee is asked in order, and only an approved seat carries any entitlement. Everything
 * that acts on the committee of a tender must therefore filter on approved() — a pending
 * request must not receive documents, hold an opening code, or appear as a member anywhere.
 *
 * Eloquent cannot infer the table from the class name here: tender_committee is singular by
 * history and the model is named for what a row is.
 */
class TenderCommitteeMember extends Model
{
    protected $table = 'tender_committee';

    protected $fillable = [
        'tender_id', 'user_id', 'role', 'designation', 'permissions', 'is_active',
        'added_by', 'requested_by', 'status', 'decided_at', 'decision_reason',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'decided_at' => 'datetime',
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvals()
    {
        return $this->hasMany(TenderCommitteeApproval::class, 'tender_committee_id')
                    ->orderBy('approval_order');
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

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->where('is_active', true);
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    /** The step the request is waiting on, or null when nothing is outstanding. */
    public function currentApprovalStep(): ?TenderCommitteeApproval
    {
        if (!$this->isPending()) {
            return null;
        }

        return $this->approvals()->where('status', 'pending')->orderBy('approval_order')->first();
    }

    /** True when approving the step given would complete the request. */
    public function isFinalApprovalStep(TenderCommitteeApproval $step): bool
    {
        return !$this->approvals()
            ->where('status', 'pending')
            ->where('id', '!=', $step->id)
            ->exists();
    }

    public function approvalProgress(): array
    {
        $all = $this->approvals;

        return ['decided' => $all->where('status', 'approved')->count(), 'total' => $all->count()];
    }
}
