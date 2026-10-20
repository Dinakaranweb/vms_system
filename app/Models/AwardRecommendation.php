<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardRecommendation extends Model
{
    protected $fillable = [
        'tender_id', 'application_id', 'initiated_by', 'status', 'initiation_note', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'application_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvals()
    {
        return $this->hasMany(AwardRecommendationApproval::class)->orderBy('approval_order');
    }

    public function currentStepApproval()
    {
        return $this->approvals()->where('status', 'pending')->orderBy('approval_order')->first();
    }

    public function isFullyApprovedByCommittee(): bool
    {
        return $this->approvals()->where('status', '!=', 'approved')->doesntExist();
    }

    public function isFullyOtpVerified(): bool
    {
        return $this->approvals()->whereNull('otp_verified_at')->doesntExist();
    }

    /**
     * Returns a human-readable reason the award is blocked, or null if it's clear to proceed.
     *
     * The gate is the Purchase Officer's finalization, not the committee's sign-off. The
     * committee's recommendations are advisory: a member may support, oppose or simply remark,
     * and the officer weighs that alongside the bid values and the documents received before
     * deciding. What the officer cannot do is award without putting the decision on record —
     * which applicant, that the committee was consulted, whether online or orally, who took
     * part, and why. AwardFinalization is that record.
     *
     * The older sequential sign-off chain (this model and its approvals) still works for
     * anyone who wants formal committee approval, and the officer sees its outcome when
     * finalizing, but it no longer blocks an award on its own. A committee that formally
     * REJECTED a recommendation is the one case still treated as blocking, since letting an
     * award through over an explicit recorded rejection would make that step meaningless.
     */
    public static function gateBlocksAward(TenderApplication $application): ?string
    {
        $recommendation = static::where('application_id', $application->id)->first();

        if ($recommendation && $recommendation->status === 'rejected') {
            return 'The Purchase Committee formally rejected the award recommendation for this application. Withdraw or revisit that rejection before awarding.';
        }

        $finalization = AwardFinalization::activeForApplication($application->id);

        if (!$finalization) {
            return 'This award has not been finalized yet. Open "Finalize Award" for the tender to record the chosen applicant and the discussion held with the Purchase Committee before issuing a contract.';
        }

        return null;
    }
}
