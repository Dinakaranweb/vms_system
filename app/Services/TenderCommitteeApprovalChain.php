<?php

namespace App\Services;

use App\Mail\CommitteeAssignedMail;
use App\Models\PurchaseCommitteeMember;
use App\Models\Tender;
use App\Models\TenderCommitteeApproval;
use App\Models\TenderCommitteeMember;
use App\Models\User;
use App\Notifications\TenderCommitteeOutcomeNotification;
use App\Notifications\TenderCommitteeRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Walks a request to put an extra member on one tender's committee past the main Purchase
 * Committee, in the Super Admin's approval_order.
 *
 * Inviting someone onto a tender used to take effect the moment it was submitted. It now
 * starts pending: the main committee is asked one member at a time, and the seat carries no
 * entitlement at all until the last of them has approved. Only then is the person a member,
 * only then are they mailed about it, and only then do they hold an opening code or receive
 * documents.
 *
 * Deliberately the same shape as VendorApprovalChain — same ordering rule, same refusal
 * messages, same "any rejection ends it" behaviour — so the two read alike. They are kept as
 * separate classes rather than abstracted because what they approve, who they notify and what
 * happens at the end differ at every step; a shared base would be mostly hooks.
 */
class TenderCommitteeApprovalChain
{
    /**
     * Raise a request for a seat and ask the first main committee member.
     *
     * With no main committee configured there is nobody whose approval could be sought, and
     * leaving the request pending would strand it with no screen able to release it. The seat
     * is granted outright in that case, which is how it behaved before this chain existed.
     *
     * @return array{ok: bool, approved: bool, message: string, seat: ?TenderCommitteeMember}
     */
    public function request(
        Tender $tender,
        User $member,
        User $requester,
        string $role,
        ?string $designation = null,
        array $permissions = []
    ): array {
        $existing = TenderCommitteeMember::where('tender_id', $tender->id)
            ->where('user_id', $member->id)
            ->first();

        if ($existing && $existing->isApproved()) {
            return ['ok' => false, 'approved' => false, 'seat' => null,
                    'message' => $member->name . ' is already on this tender\'s committee.'];
        }

        if ($existing && $existing->isPending()) {
            $waiting = $existing->currentApprovalStep()?->committeeMember->name;

            return ['ok' => false, 'approved' => false, 'seat' => null,
                    'message' => $member->name . ' has already been put forward for this tender'
                        . ($waiting ? ", and is waiting on {$waiting}." : '.')];
        }

        $mains = PurchaseCommitteeMember::with('user')->active()->ordered()->get()
            ->filter(fn ($m) => $m->user !== null)
            // Nobody approves their own seat.
            ->filter(fn ($m) => (int) $m->user_id !== (int) $member->id)
            ->values();

        $seat = null;
        $granted = $mains->isEmpty();

        DB::transaction(function () use (&$seat, $existing, $tender, $member, $requester, $role, $designation, $permissions, $mains, $granted) {
            $attributes = [
                'role'            => $role,
                'designation'     => $designation,
                'permissions'     => json_encode($permissions),
                'is_active'       => true,
                'added_by'        => $requester->id,
                'requested_by'    => $requester->id,
                'status'          => $granted ? 'approved' : 'pending',
                'decided_at'      => $granted ? now() : null,
                'decision_reason' => null,
            ];

            if ($existing) {
                // A previously rejected request being put forward again: reuse the row so the
                // unique seat per tender still holds, and clear the old steps.
                $existing->update($attributes);
                $existing->approvals()->delete();
                $seat = $existing->fresh();
            } else {
                $seat = TenderCommitteeMember::create($attributes + [
                    'tender_id' => $tender->id,
                    'user_id'   => $member->id,
                ]);
            }

            foreach ($mains as $main) {
                TenderCommitteeApproval::create([
                    'tender_committee_id' => $seat->id,
                    'committee_member_id' => $main->user_id,
                    'approval_order'      => $main->approval_order,
                    'designation'         => $main->designation,
                    'status'              => 'pending',
                ]);
            }
        });

        if ($granted) {
            Log::warning("Tender committee seat granted without approval: no active Purchase Committee members are configured (tender {$tender->id}, user {$member->id}).");
            $this->announceSeat($seat->fresh(), $tender, $member);

            return ['ok' => true, 'approved' => true, 'seat' => $seat->fresh(),
                    'message' => $member->name . ' has been added. No Purchase Committee is configured, so no approval was needed.'];
        }

        $this->notifyCurrentStep($seat->fresh(), $requester->name);

        return [
            'ok' => true, 'approved' => false, 'seat' => $seat->fresh(),
            'message' => $member->name . ' has been put forward and is waiting on the Purchase Committee. '
                . ($mains->first()->user->name ?? 'The first approver')
                . ' has been asked first; nothing reaches ' . $member->name
                . ' until the final approver signs off.',
        ];
    }

    /**
     * Record one main member's approval and either move on or grant the seat.
     *
     * @return array{ok: bool, final: bool, message: string}
     */
    public function approveStep(TenderCommitteeMember $seat, User $actor, ?string $comments = null): array
    {
        $step = $this->actionableStepFor($seat, $actor);

        if (!$step instanceof TenderCommitteeApproval) {
            return ['ok' => false, 'final' => false, 'message' => $step];
        }

        $isFinal = $seat->isFinalApprovalStep($step);

        $step->update(['status' => 'approved', 'comments' => $comments, 'decided_at' => now()]);

        if (!$isFinal) {
            $seat = $seat->fresh();
            $this->notifyCurrentStep($seat);
            $next = $seat->currentApprovalStep();

            return ['ok' => true, 'final' => false,
                    'message' => 'Approved. The request has moved to '
                        . ($next?->committeeMember->name ?? 'the next approver') . '.'];
        }

        DB::transaction(function () use ($seat) {
            $seat->update(['status' => 'approved', 'decided_at' => now()]);
        });

        $seat   = $seat->fresh();
        $tender = $seat->tender;
        $member = $seat->user;

        $this->announceSeat($seat, $tender, $member);
        $this->notifyOutcome($seat, approved: true, decidedBy: $actor->name);

        // A member added onto one tender holds no opening code, so approving them mid-opening
        // neither delays the opening nor adds a code to collect. They read the documents once
        // the standing committee has opened it.
        $openingNote = $tender && $tender->isOpeningOtpPending()
            ? ' This tender is mid-opening; that is unaffected, and ' . ($member->name ?? 'they')
              . ' will see the documents once the Purchase Committee\'s codes are all in.'
            : '';

        return ['ok' => true, 'final' => true,
                'message' => 'Approved. ' . ($member->name ?? 'The member')
                    . ' is now on this tender\'s committee and has been told.' . $openingNote];
    }

    /**
     * Record one main member's rejection. No later member is asked.
     *
     * @return array{ok: bool, message: string}
     */
    public function rejectStep(TenderCommitteeMember $seat, User $actor, string $reason): array
    {
        $step = $this->actionableStepFor($seat, $actor);

        if (!$step instanceof TenderCommitteeApproval) {
            return ['ok' => false, 'message' => $step];
        }

        DB::transaction(function () use ($seat, $step, $reason) {
            $step->update(['status' => 'rejected', 'comments' => $reason, 'decided_at' => now()]);
            $seat->update(['status' => 'rejected', 'decided_at' => now(), 'decision_reason' => $reason]);
        });

        $seat = $seat->fresh();
        $this->notifyOutcome($seat, approved: false, decidedBy: $actor->name, reason: $reason);

        return ['ok' => true,
                'message' => 'Rejected. ' . ($seat->user->name ?? 'That person')
                    . ' has not been added and the request stops here.'];
    }

    // ─── Internals ──────────────────────────────────────────────────────────

    /**
     * The step this user may act on now, or a sentence explaining why they may not.
     *
     * @return TenderCommitteeApproval|string
     */
    private function actionableStepFor(TenderCommitteeMember $seat, User $actor)
    {
        if (!$seat->isPending()) {
            return $seat->isApproved()
                ? 'That request has already been approved.'
                : 'That request has already been rejected.';
        }

        $mine = $seat->approvals()->where('committee_member_id', $actor->id)->first();

        if (!$mine) {
            return 'You are not on the approval order for this request.';
        }

        if (!$mine->isPending()) {
            return $mine->isApproved()
                ? 'You have already approved this request.'
                : 'You have already rejected this request.';
        }

        $current = $seat->currentApprovalStep();

        if (!$current || $current->id !== $mine->id) {
            return 'It is not your turn yet — this request is still with '
                . ($current?->committeeMember->name ?? 'an earlier approver') . '.';
        }

        return $mine;
    }

    private function notifyCurrentStep(TenderCommitteeMember $seat, ?string $requestedBy = null): void
    {
        $step = $seat->currentApprovalStep();

        if (!$step || !$step->committeeMember) {
            return;
        }

        try {
            $step->committeeMember->notify(new TenderCommitteeRequestNotification(
                $seat, $step, $seat->isFinalApprovalStep($step), $requestedBy
            ));
            $step->update(['notified_at' => now()]);
            Log::info("Tender committee request notified: seat {$seat->id} to user {$step->committee_member_id}.");
        } catch (\Throwable $e) {
            Log::error("Tender committee request notification failed for seat {$seat->id}, user {$step->committee_member_id}: " . $e->getMessage());
        }
    }

    /** The seat is granted: this is the only place the new member is told about it. */
    private function announceSeat(TenderCommitteeMember $seat, ?Tender $tender, ?User $member): void
    {
        if (!$tender || !$member || trim((string) $member->email) === '') {
            return;
        }

        try {
            Mail::to($member->email)->send(
                new CommitteeAssignedMail($member, $tender, $seat->role, $seat->designation)
            );
            Log::info("Committee assignment email sent to {$member->email} for tender {$tender->id}.");
        } catch (\Throwable $e) {
            Log::error("Committee assignment email failed for seat {$seat->id}: " . $e->getMessage());
        }
    }

    /** Tell the person who asked for the seat, and the main members who already decided. */
    private function notifyOutcome(TenderCommitteeMember $seat, bool $approved, ?string $decidedBy, ?string $reason = null): void
    {
        $recipients = collect();

        if ($seat->requested_by) {
            $recipients = $recipients->merge(User::whereKey($seat->requested_by)->get());
        }

        $decided = $seat->approvals()->whereIn('status', ['approved', 'rejected'])->pluck('committee_member_id');
        if ($decided->isNotEmpty()) {
            $recipients = $recipients->merge(User::whereIn('id', $decided)->get());
        }

        foreach ($recipients->unique('id') as $user) {
            try {
                $user->notify(new TenderCommitteeOutcomeNotification($seat, $approved, $decidedBy, $reason));
            } catch (\Throwable $e) {
                Log::error("Tender committee outcome notification failed for user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
