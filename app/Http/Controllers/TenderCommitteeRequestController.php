<?php

namespace App\Http\Controllers;

use App\Models\TenderCommitteeApproval;
use App\Models\TenderCommitteeMember;
use App\Services\TenderCommitteeApprovalChain;
use Illuminate\Http\Request;

/**
 * The main Purchase Committee's side of approving an extra member onto one tender.
 *
 * A member sees the requests standing against their own name and may act on the one the
 * chain is waiting on. Whether it is their turn is settled in TenderCommitteeApprovalChain,
 * not here, so reaching a URL directly cannot jump the order; this controller only
 * establishes that the member is on the chain at all.
 */
class TenderCommitteeRequestController extends Controller
{
    public function __construct(private TenderCommitteeApprovalChain $chain) {}

    public function index()
    {
        $steps = TenderCommitteeApproval::with(['request.tender', 'request.user', 'request.requestedBy'])
            ->where('committee_member_id', auth()->id())
            ->whereHas('request')
            ->orderByDesc('id')
            ->get();

        $awaitingMe = $steps->filter(fn ($s) => $s->isCurrentStep())->values();

        $queued = $steps->filter(
            fn ($s) => $s->isPending() && !$s->isCurrentStep() && $s->request->isPending()
        )->values();

        $decided = $steps->filter(fn ($s) => !$s->isPending())->values();

        return view('purchase-committee.committee-requests.index', compact('awaitingMe', 'queued', 'decided'));
    }

    public function show($seatId)
    {
        $seat = TenderCommitteeMember::with([
            'tender.owner', 'user', 'requestedBy', 'approvals.committeeMember',
        ])->findOrFail($seatId);

        $myStep = $seat->approvals->firstWhere('committee_member_id', auth()->id());

        abort_unless($myStep !== null, 403, 'You are not on the approval order for this request.');

        return view('purchase-committee.committee-requests.show', [
            'seat'     => $seat,
            'myStep'   => $myStep,
            'isMyTurn' => $myStep->isCurrentStep(),
            'current'  => $seat->currentApprovalStep(),
            'isFinal'  => $myStep->isPending() && $seat->isPending()
                          && $seat->isFinalApprovalStep($myStep),
        ]);
    }

    public function approve(Request $request, $seatId)
    {
        $request->validate(['comments' => 'nullable|string|max:2000']);

        $seat   = TenderCommitteeMember::findOrFail($seatId);
        $result = $this->chain->approveStep($seat, auth()->user(), $request->input('comments'));

        if (!$result['ok']) {
            return back()->withErrors(['approval' => $result['message']]);
        }

        return redirect()
            ->route('purchase-committee.committee-requests.index')
            ->with('success', $result['message']);
    }

    public function reject(Request $request, $seatId)
    {
        $request->validate(
            ['reason' => 'required|string|max:2000'],
            ['reason.required' => 'Please say why this addition is being refused — whoever asked for it is told the reason.']
        );

        $seat   = TenderCommitteeMember::findOrFail($seatId);
        $result = $this->chain->rejectStep($seat, auth()->user(), $request->input('reason'));

        if (!$result['ok']) {
            return back()->withErrors(['approval' => $result['message']]);
        }

        return redirect()
            ->route('purchase-committee.committee-requests.index')
            ->with('success', $result['message']);
    }
}
