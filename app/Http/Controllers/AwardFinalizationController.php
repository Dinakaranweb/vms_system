<?php

namespace App\Http\Controllers;

use App\Models\ApplicationReviewComment;
use App\Models\AwardFinalization;
use App\Models\AwardRecommendation;
use App\Models\PurchaseCommitteeMember;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Notifications\AwardFinalizedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The Purchase Officer's award decision.
 *
 * Everything the decision rests on is put on one screen: each applicant's bid value (and who
 * keyed it in, for a postal quote), the documents received against them, and the committee's
 * recommendations. The committee's input is advisory — members may support, oppose or just
 * remark, and some may say nothing at all — so the tally is shown but never binding.
 *
 * The officer then records the decision: which applicant, that the committee was consulted,
 * whether that happened online or orally, which members took part, and why. That record is
 * what AwardRecommendation::gateBlocksAward() requires before any contract can be issued, so
 * an award can never be quietly made without it. Issuing the actual contract and WO/PO stays
 * where it already lived, in ApplicationController / TenderOwnerApplicationController.
 *
 * A finalization can be revoked while no contract exists yet; once a contract has been issued
 * the decision is locked and the contract must be dealt with on its own terms.
 */
class AwardFinalizationController extends Controller
{
    // ─── Screen ─────────────────────────────────────────────────────────────

    public function index($tenderId)
    {
        $tender = Tender::with('owner')->findOrFail($tenderId);

        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $applications = TenderApplication::where('tender_id', $tender->id)
            ->with(['vendor', 'bidRecordedBy', 'receivedDocuments', 'reviewComments.committeeMember'])
            ->get()
            // Cheapest first, but applications with no price yet sort last rather than first —
            // a missing value is not a low bid.
            ->sortBy(fn($a) => $a->bid_amount === null ? PHP_INT_MAX : (float) $a->bid_amount)
            ->values();

        $candidates = $applications->reject(fn($a) => in_array($a->status, ['rejected', 'withdrawn'], true))->values();

        $members = PurchaseCommitteeMember::active()->ordered()->with('user')->get();
        $finalization = AwardFinalization::activeFor($tender->id);
        $revoked = AwardFinalization::where('tender_id', $tender->id)->whereNotNull('revoked_at')
            ->with(['application.vendor', 'finalizedBy', 'revokedBy'])->latest('id')->get();

        // The older sequential chain still runs for anyone using it; surface its outcome so the
        // officer can see it, without it gating anything.
        $recommendations = AwardRecommendation::where('tender_id', $tender->id)
            ->with(['application.vendor', 'approvals.committeeMember', 'initiator'])
            ->get()
            ->keyBy('application_id');

        $alreadyAwarded = TenderApplication::where('tender_id', $tender->id)
            ->where('status', 'awarded')->with('vendor')->first();

        $stats = [
            'applications'  => $applications->count(),
            'priced'        => $applications->filter(fn($a) => $a->bid_amount !== null)->count(),
            'unpriced'      => $applications->filter(fn($a) => $a->bid_amount === null)->count(),
            'documents'     => $applications->sum(fn($a) => $a->receivedDocuments->count()),
            'recommendations' => $applications->sum(fn($a) => $a->reviewComments->count()),
        ];

        return view('purchase-committee.award.finalize', compact(
            'tender', 'applications', 'candidates', 'members', 'finalization',
            'revoked', 'recommendations', 'alreadyAwarded', 'stats'
        ));
    }

    // ─── Actions ────────────────────────────────────────────────────────────

    public function store(Request $request, $tenderId)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $validated = $request->validate([
            'application_id'     => 'required|integer',
            'discussion_mode'    => 'required|in:online,oral',
            'discussion_held_at' => 'nullable|date|before_or_equal:now',
            'participants'       => 'nullable|array',
            'participants.*'     => 'integer',
            'decision_note'      => 'required|string|min:10|max:5000',
            'awarded_value'      => 'nullable|numeric|min:0|max:9999999999999',
        ], [
            'application_id.required'  => 'Choose the applicant the contract is being awarded to.',
            'discussion_mode.required' => 'Record whether the committee was consulted online or orally.',
            'decision_note.required'   => 'Record why this applicant was chosen.',
            'decision_note.min'        => 'Please give a fuller reason for the decision (at least 10 characters).',
            'discussion_held_at.before_or_equal' => 'A discussion cannot be recorded as happening in the future.',
        ]);

        if (AwardFinalization::activeFor($tender->id)) {
            return back()->withInput()->withErrors([
                'general' => 'This tender already has a finalized award. Withdraw it first if the decision has changed.',
            ]);
        }

        $application = TenderApplication::where('tender_id', $tender->id)
            ->with('vendor')
            ->findOrFail($validated['application_id']);

        if (in_array($application->status, ['rejected', 'withdrawn'], true)) {
            return back()->withInput()->withErrors([
                'general' => 'That application was ' . $application->status . ' and cannot be awarded.',
            ]);
        }

        // Only members who were actually on the committee may be recorded as taking part.
        $activeMemberIds = PurchaseCommitteeMember::active()->pluck('user_id')->all();
        $participants = array_values(array_intersect(
            array_map('intval', (array) ($validated['participants'] ?? [])),
            array_map('intval', $activeMemberIds)
        ));

        $finalization = DB::transaction(function () use ($tender, $application, $validated, $participants) {
            return AwardFinalization::create([
                'tender_id'          => $tender->id,
                'application_id'     => $application->id,
                'finalized_by'       => auth()->id(),
                'discussion_mode'    => $validated['discussion_mode'],
                'discussion_held_at' => $validated['discussion_held_at'] ?? now(),
                'participants'       => $participants,
                'committee_summary'  => $this->committeeSummary($application),
                'decision_note'      => $validated['decision_note'],
                'awarded_value'      => $validated['awarded_value'] ?? $application->bid_amount,
            ]);
        });

        $this->notifyCommittee($finalization);

        return redirect()->route('purchase-committee.award.finalize', $tender->id)->with('success',
            'Award finalized for ' . ($application->vendor->company_name ?? 'the selected applicant')
            . '. The Purchase Committee has been notified — you can now issue the contract.');
    }

    /**
     * Withdraws a finalization so a different applicant can be chosen. Refused once a contract
     * exists against it, since by then the award has left the system and has to be unwound
     * through the contract itself rather than by rewriting the decision.
     */
    public function revoke(Request $request, $tenderId, $id)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $validated = $request->validate([
            'revoked_reason' => 'required|string|min:5|max:2000',
        ], [
            'revoked_reason.required' => 'Record why the finalized award is being withdrawn.',
        ]);

        $finalization = AwardFinalization::active()->where('tender_id', $tender->id)
            ->with(['application.vendor', 'tender', 'finalizedBy'])
            ->findOrFail($id);

        if ($tender->contract) {
            return back()->withErrors([
                'general' => 'A contract has already been issued for this tender, so the award can no longer be withdrawn here. Cancel or terminate the contract instead.',
            ]);
        }

        if ($finalization->application->status === 'awarded') {
            return back()->withErrors([
                'general' => 'This application has already been awarded. Cancel the award on the application before withdrawing the decision.',
            ]);
        }

        $finalization->update([
            'revoked_at'     => now(),
            'revoked_by'     => auth()->id(),
            'revoked_reason' => $validated['revoked_reason'],
        ]);

        $this->notifyCommittee($finalization->fresh()->load(['application.vendor', 'tender', 'finalizedBy']), true);

        return back()->with('success', 'The finalized award has been withdrawn and the Purchase Committee notified. You can finalize a different applicant now.');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Finalizing is the Purchase Officer's job: Admin, Super Admin, or the tender's own owner.
     * Committee members advise and can read the screen, but cannot make the decision.
     */
    private function guard(Tender $tender)
    {
        $user = auth()->user();

        $isOfficer = $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || ($user->hasRole('tender-owner') && $tender->owner_id === $user->id);

        if (!$isOfficer) {
            abort(403, 'Only the Purchase Officer, an Administrator, or this tender\'s owner can finalize an award.');
        }

        if (!$tender->isBidOpen()) {
            return redirect()->route($this->backRoute(), $tender->id)->withErrors([
                'general' => 'An award can only be finalized after the tender has been opened with the Purchase Committee\'s codes.',
            ]);
        }

        return null;
    }

    private function backRoute(): string
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin'))  return 'super-admin.tenders.show';
        if ($user->hasRole('admin'))        return 'admin.tenders.show';

        return 'tender-owner.tenders.show';
    }

    /**
     * Freezes the advisory tally as it stood when the decision was made.
     */
    private function committeeSummary(TenderApplication $application): array
    {
        $comments = ApplicationReviewComment::where('application_id', $application->id)
            ->with('committeeMember')->get();

        return [
            'support'  => $comments->where('verdict', 'support')->count(),
            'oppose'   => $comments->where('verdict', 'oppose')->count(),
            'neutral'  => $comments->where('verdict', 'neutral')->count(),
            'remarks'  => $comments->whereNull('verdict')->count(),
            'total'    => $comments->count(),
            'members'  => $comments->map(fn($c) => [
                'name'    => $c->committeeMember->name ?? null,
                'verdict' => $c->verdict,
            ])->values()->all(),
        ];
    }

    /**
     * Each member separately, in their own login and at their own email address, with one
     * failure never silencing the rest.
     */
    private function notifyCommittee(AwardFinalization $finalization, bool $revoked = false): void
    {
        foreach (PurchaseCommitteeMember::active()->with('user')->get() as $member) {
            if (!$member->user) continue;

            try {
                $member->user->notify(new AwardFinalizedNotification($finalization, $revoked));
            } catch (\Exception $e) {
                \Log::error('AwardFinalizedNotification failed for user ' . $member->user_id . ': ' . $e->getMessage());
            }
        }
    }
}
