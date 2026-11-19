<?php

namespace App\Http\Controllers;

use App\Models\PurchaseCommitteeMember;
use App\Models\Vendor;
use App\Models\VendorApproval;
use App\Services\VendorApprovalChain;
use Illuminate\Http\Request;

/**
 * The Purchase Committee's side of vendor approval.
 *
 * A member sees the vendors standing against their own name and may act on the one the chain
 * is currently waiting on. Whether it is actually their turn is settled in
 * VendorApprovalChain, not here, so the ordering cannot be bypassed by reaching a URL
 * directly — this controller only establishes that the member is on the chain at all.
 */
class VendorApprovalController extends Controller
{
    public function __construct(private VendorApprovalChain $chain) {}

    /**
     * Everything with this member's name on it: what they must act on now, what they are
     * waiting for, and what they have already decided.
     */
    public function index()
    {
        $userId = auth()->id();

        $steps = VendorApproval::with(['vendor', 'committeeMember'])
            ->where('committee_member_id', $userId)
            ->whereHas('vendor')
            ->orderByDesc('id')
            ->get();

        $awaitingMe = $steps->filter(fn ($s) => $s->isCurrentStep())->values();

        $queued = $steps->filter(
            fn ($s) => $s->isPending() && !$s->isCurrentStep() && $s->vendor->isAwaitingCommittee()
        )->values();

        $decided = $steps->filter(fn ($s) => !$s->isPending())->values();

        return view('purchase-committee.vendor-approvals.index', compact('awaitingMe', 'queued', 'decided'));
    }

    /**
     * The full registration, every document filed with it, and where it stands in the order.
     */
    public function show($vendorId)
    {
        $vendor = Vendor::with(['user', 'approvals.committeeMember', 'officerApprover', 'categories'])
            ->findOrFail($vendorId);

        $myStep = $vendor->approvals->firstWhere('committee_member_id', auth()->id());

        abort_unless($myStep !== null, 403, 'You are not on the approval order for this vendor.');

        return view('purchase-committee.vendor-approvals.show', [
            'vendor'    => $vendor,
            'myStep'    => $myStep,
            'isMyTurn'  => $myStep->isCurrentStep(),
            'current'   => $vendor->currentApprovalStep(),
            'isFinal'   => $myStep->isPending() && $vendor->isAwaitingCommittee()
                            && $vendor->isFinalApprovalStep($myStep),
        ]);
    }

    public function approve(Request $request, $vendorId)
    {
        $request->validate(['comments' => 'nullable|string|max:2000']);

        $vendor = Vendor::findOrFail($vendorId);
        $result = $this->chain->approveStep($vendor, auth()->user(), $request->input('comments'));

        if (!$result['ok']) {
            return back()->withErrors(['approval' => $result['message']]);
        }

        return redirect()
            ->route('purchase-committee.vendor-approvals.index')
            ->with('success', $result['message']);
    }

    public function reject(Request $request, $vendorId)
    {
        $request->validate(
            ['reason' => 'required|string|max:2000'],
            ['reason.required' => 'Please say why this vendor is being rejected — the vendor is told the reason.']
        );

        $vendor = Vendor::findOrFail($vendorId);
        $result = $this->chain->rejectStep($vendor, auth()->user(), $request->input('reason'));

        if (!$result['ok']) {
            return back()->withErrors(['approval' => $result['message']]);
        }

        return redirect()
            ->route('purchase-committee.vendor-approvals.index')
            ->with('success', $result['message']);
    }
}
