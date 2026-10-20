<?php

namespace App\Http\Controllers;

use App\Models\TenderApplication;
use Illuminate\Http\Request;

/**
 * After bids are opened for a postal tender, the Purchase Officer / Tender Owner / Purchase
 * Committee physically checks the vendor's postal submission and records what was received —
 * there is no vendor-facing UI for this since postal bids never touch the system on their own.
 *
 * Entry is additionally gated behind the same review-OTP unlock as TenderReviewController: a
 * Purchase Committee member must have verified their own OTP for this tender before they can
 * open the entry screen, mirroring the sequence the committee follows on the day of opening.
 * Admin/Super Admin/Tender Owner bypass the OTP the same way they do for viewing applications.
 *
 * Entry is also restricted to the same calendar day the tender was opened — the postal
 * submissions are physically checked and recorded on opening day, not entered piecemeal later.
 */
class PostalBidController extends Controller
{
    private function isPrivileged($user, $tender): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('admin')
            || ($user->hasRole('tender-owner') && $tender->owner_id === $user->id);
    }

    /**
     * Loads the application, checks the tender is postal and open for committee review, and
     * confirms the current user is allowed in. Committee members without a verified OTP are
     * sent to the OTP form instead of a bare 403, matching the review-applications flow.
     * Returns the application, or a redirect/error response to return straight from the action.
     */
    private function loadAuthorized($id)
    {
        $application = TenderApplication::with(['tender', 'vendor'])->findOrFail($id);
        $tender = $application->tender;
        $user = auth()->user();

        if (!$tender->hasPostalBids()) {
            abort(404, 'This tender does not accept postal submissions.');
        }

        if ($this->isPrivileged($user, $tender)) {
            if (!$tender->isReviewOpen()) {
                return back()->withErrors(['general' => 'Bids must be opened for committee review before postal data can be entered.']);
            }
            if ($sameDayError = $this->sameDayError($tender)) {
                return back()->withErrors(['general' => $sameDayError]);
            }
            return $application;
        }

        if (!$user->hasRole('purchase-committee')) {
            abort(403, 'You are not authorized to enter postal bid data for this application.');
        }
        if (!$tender->isReviewOpen()) {
            return back()->withErrors(['general' => 'Bids must be opened for committee review before postal data can be entered.']);
        }
        if (!$tender->isReviewUnlockedFor($user->id)) {
            // Codes are collected by the Purchase Officer, so there is nothing for the member
            // to enter here — they just were not part of this tender's opening.
            return redirect()->route('purchase-committee.tenders.index')
                ->withErrors(['general' => 'Your opening code was not collected for this tender, so you cannot enter its postal bid data.']);
        }
        if ($sameDayError = $this->sameDayError($tender)) {
            return back()->withErrors(['general' => $sameDayError]);
        }

        return $application;
    }

    private function sameDayError($tender): ?string
    {
        if (!$tender->bid_opening_at->isToday()) {
            return 'Postal bid details can only be entered on the day the tender was opened (' . $tender->bid_opening_at->format('d M Y') . ').';
        }
        return null;
    }

    public function edit($id)
    {
        $application = $this->loadAuthorized($id);
        if (!$application instanceof TenderApplication) {
            return $application;
        }

        return view('purchase-committee.postal-bid-entry', compact('application'));
    }

    public function update(Request $request, $id)
    {
        $application = $this->loadAuthorized($id);
        if (!$application instanceof TenderApplication) {
            return $application;
        }
        $tender = $application->tender;

        $request->validate([
            'bid_amount'         => 'required|numeric|min:0',
            'technical_proposal' => 'nullable|string',
            'financial_proposal' => 'nullable|string',
            'postal_quote_scan'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = [
            'bid_amount'         => $request->bid_amount,
            'technical_proposal' => $request->technical_proposal,
            'financial_proposal' => $request->financial_proposal,
        ];

        if ($request->hasFile('postal_quote_scan')) {
            $data['postal_quote_scan'] = $request->file('postal_quote_scan')->store('postal-bid-scans/' . $tender->id, 'public');
        }

        $application->update($data);

        return back()->with('success', 'Postal bid details recorded successfully.');
    }
}
