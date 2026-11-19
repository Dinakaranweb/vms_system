<?php

namespace App\Services;

use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;
use App\Models\PurchaseCommitteeMember;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApproval;
use App\Notifications\VendorApprovalOutcomeNotification;
use App\Notifications\VendorApprovalStepNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Walks a registered vendor through the Purchase Committee's approval order.
 *
 * The Purchase Officer's approval no longer makes somebody a vendor; it starts this. The
 * vendor is then put to each active member of purchase_committee_members in approval_order,
 * one at a time, and only the final approval flips the vendor to approved and sends them the
 * "your account is approved" email. Any single rejection ends the chain there.
 *
 * Two rules worth stating because they are easy to lose later:
 *
 *   - Only the lowest-ordered outstanding step may act. The others are not merely hidden in
 *     the UI; approveStep() refuses them, so an ordered chain cannot be short-circuited by a
 *     stale tab or a guessed URL.
 *   - The order and designation are copied onto each step when the chain starts. Reordering
 *     the committee afterwards changes who is asked next time, never what a decision already
 *     taken is recorded against.
 */
class VendorApprovalChain
{
    /**
     * The Purchase Officer has cleared a registration. Build the chain and ask the first member.
     *
     * Where no active committee is configured there is nobody to ask, and leaving the vendor
     * in committee_review would strand them forever with no screen able to release them. So
     * the officer's approval stands on its own, exactly as it did before this chain existed.
     *
     * @return array{approved: bool, message: string}
     */
    public function start(Vendor $vendor, User $officer): array
    {
        $members = PurchaseCommitteeMember::with('user')
            ->active()->ordered()->get()
            ->filter(fn ($m) => $m->user !== null)
            ->values();

        if ($members->isEmpty()) {
            Log::warning("Vendor {$vendor->id} approved without committee review: no active Purchase Committee members are configured.");

            $this->finalize($vendor, $officer, soleApprover: true);

            return [
                'approved' => true,
                'message'  => 'Vendor approved. No Purchase Committee is configured, so your approval was final.',
            ];
        }

        DB::transaction(function () use ($vendor, $officer, $members) {
            // A re-run must not leave half an old chain behind.
            VendorApproval::where('vendor_id', $vendor->id)->delete();

            foreach ($members as $member) {
                VendorApproval::create([
                    'vendor_id'           => $vendor->id,
                    'committee_member_id' => $member->user_id,
                    'approval_order'      => $member->approval_order,
                    'designation'         => $member->designation,
                    'status'              => 'pending',
                ]);
            }

            $vendor->update([
                'status'              => 'committee_review',
                'officer_approved_by' => $officer->id,
                'officer_approved_at' => now(),
                'rejection_reason'    => null,
                'approved_by'         => null,
                'approved_at'         => null,
            ]);
        });

        $this->notifyCurrentStep($vendor->fresh(), $officer->name);

        $first = $members->first();

        return [
            'approved' => false,
            'message'  => 'Registration cleared and sent to the Purchase Committee. '
                . ($first->user->name ?? 'The first approver')
                . ' has been asked to approve first; the vendor is told only once the final approver signs off.',
        ];
    }

    /**
     * Record one member's approval and either move to the next member or finish.
     *
     * @return array{ok: bool, final: bool, message: string}
     */
    public function approveStep(Vendor $vendor, User $actor, ?string $comments = null): array
    {
        $step = $this->actionableStepFor($vendor, $actor);

        if (!$step instanceof VendorApproval) {
            return ['ok' => false, 'final' => false, 'message' => $step];
        }

        $isFinal = $vendor->isFinalApprovalStep($step);

        DB::transaction(function () use ($step, $comments) {
            $step->update([
                'status'     => 'approved',
                'comments'   => $comments,
                'decided_at' => now(),
            ]);
        });

        $vendor = $vendor->fresh();

        if ($isFinal) {
            $this->finalize($vendor, $actor);

            return [
                'ok'      => true,
                'final'   => true,
                'message' => 'Approved. You were the final approver, so ' . $vendor->company_name
                    . ' is now an active vendor and has been emailed.',
            ];
        }

        $this->notifyCurrentStep($vendor);
        $next = $vendor->currentApprovalStep();

        return [
            'ok'      => true,
            'final'   => false,
            'message' => 'Approved. The registration has moved to '
                . ($next?->committeeMember->name ?? 'the next approver') . '.',
        ];
    }

    /**
     * Record one member's rejection. The chain stops: no later member is asked.
     *
     * @return array{ok: bool, message: string}
     */
    public function rejectStep(Vendor $vendor, User $actor, string $reason): array
    {
        $step = $this->actionableStepFor($vendor, $actor);

        if (!$step instanceof VendorApproval) {
            return ['ok' => false, 'message' => $step];
        }

        DB::transaction(function () use ($vendor, $step, $actor, $reason) {
            $step->update([
                'status'     => 'rejected',
                'comments'   => $reason,
                'decided_at' => now(),
            ]);

            $vendor->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
                'approved_by'      => $actor->id,
                'approved_at'      => now(),
            ]);

            if ($vendor->user) {
                $vendor->user->update(['is_active' => false]);
            }
        });

        $vendor = $vendor->fresh();

        $this->mailVendor($vendor, fn () => new VendorRejectedMail($vendor, $reason), 'rejection');
        $this->notifyStakeholders($vendor, approved: false, decidedBy: $actor->name, reason: $reason);

        return [
            'ok'      => true,
            'message' => 'Rejected. The approval order stops here and ' . $vendor->company_name . ' has been told.',
        ];
    }

    // ─── Internals ──────────────────────────────────────────────────────────

    /**
     * The step this user may act on right now, or a sentence explaining why they may not.
     *
     * Returning the reason rather than a bare false is deliberate: every refusal here is
     * something the member needs explained — their turn has passed, it is not yet their turn,
     * or somebody else has already settled the vendor.
     *
     * @return VendorApproval|string
     */
    private function actionableStepFor(Vendor $vendor, User $actor)
    {
        if (!$vendor->isAwaitingCommittee()) {
            return $vendor->isApproved()
                ? 'This vendor has already been approved.'
                : ($vendor->isRejected()
                    ? 'This vendor has already been rejected.'
                    : 'This vendor is still with the Purchase Officer and has not reached the committee.');
        }

        $mine = $vendor->approvals()->where('committee_member_id', $actor->id)->first();

        if (!$mine) {
            return 'You are not on the approval order for this vendor.';
        }

        if ($mine->isApproved()) {
            return 'You have already approved this vendor.';
        }

        if ($mine->isRejected()) {
            return 'You have already rejected this vendor.';
        }

        $current = $vendor->currentApprovalStep();

        if (!$current || $current->id !== $mine->id) {
            $waitingOn = $current?->committeeMember->name ?? 'an earlier approver';

            return "It is not your turn yet — this vendor is still with {$waitingOn}.";
        }

        return $mine;
    }

    /** Email and notify whichever member the chain is now waiting on. */
    private function notifyCurrentStep(Vendor $vendor, ?string $startedBy = null): void
    {
        $step = $vendor->currentApprovalStep();

        if (!$step || !$step->committeeMember) {
            return;
        }

        try {
            $step->committeeMember->notify(
                new VendorApprovalStepNotification($vendor, $step, $vendor->isFinalApprovalStep($step), $startedBy)
            );
            $step->update(['notified_at' => now()]);
            Log::info("Vendor approval step notified: vendor {$vendor->id} to user {$step->committee_member_id}.");
        } catch (\Throwable $e) {
            Log::error("Vendor approval step notification failed for vendor {$vendor->id}, user {$step->committee_member_id}: " . $e->getMessage());
        }
    }

    /**
     * The approval is complete. This is the single place the vendor is told they are approved.
     */
    private function finalize(Vendor $vendor, User $actor, bool $soleApprover = false): void
    {
        DB::transaction(function () use ($vendor, $actor, $soleApprover) {
            $vendor->update([
                'status'      => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'officer_approved_by' => $vendor->officer_approved_by ?: ($soleApprover ? $actor->id : null),
                'officer_approved_at' => $vendor->officer_approved_at ?: ($soleApprover ? now() : null),
            ]);

            if ($vendor->user) {
                $vendor->user->update(['is_active' => true]);
            }
        });

        $vendor = $vendor->fresh();

        $this->mailVendor($vendor, fn () => new VendorApprovedMail($vendor), 'approval');
        $this->notifyStakeholders($vendor, approved: true, decidedBy: $actor->name);
    }

    /** Tell the officer, the super-admins and everyone who already approved how it ended. */
    private function notifyStakeholders(Vendor $vendor, bool $approved, ?string $decidedBy, ?string $reason = null): void
    {
        $recipients = collect();

        if ($vendor->officer_approved_by) {
            $recipients = $recipients->merge(User::whereKey($vendor->officer_approved_by)->get());
        }

        $recipients = $recipients->merge(User::role(['super-admin', 'admin'])->get());

        $decided = $vendor->approvals()->whereIn('status', ['approved', 'rejected'])->pluck('committee_member_id');
        if ($decided->isNotEmpty()) {
            $recipients = $recipients->merge(User::whereIn('id', $decided)->get());
        }

        foreach ($recipients->unique('id') as $user) {
            try {
                $user->notify(new VendorApprovalOutcomeNotification($vendor, $approved, $decidedBy, $reason));
            } catch (\Throwable $e) {
                Log::error("Vendor approval outcome notification failed for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Vendors have an address on the vendor row and another on their login; the row has been
     * blank before now, so fall back rather than drop the one mail that matters most.
     */
    private function mailVendor(Vendor $vendor, callable $makeMailable, string $what): void
    {
        $to = $vendor->resolveMailAddress();

        if ($to === null) {
            Log::warning("Vendor {$what} email skipped: vendor {$vendor->id} has no email address on file.");

            return;
        }

        try {
            Mail::to($to)->send($makeMailable());
            Log::info("Vendor {$what} email sent to {$to} for vendor {$vendor->id}.");
        } catch (\Throwable $e) {
            Log::error("Vendor {$what} email error for vendor {$vendor->id}: " . $e->getMessage());
        }
    }
}
