<?php

namespace App\Http\Controllers;

use App\Mail\TenderBidOpenedMail;
use App\Mail\TenderBidSummaryMail;
use App\Models\ApplicationReviewComment;
use App\Models\PurchaseCommitteeMember;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\TenderReviewOtp;
use App\Models\Vendor;
use App\Notifications\TenderDocumentsSharedNotification;
use App\Notifications\TenderOpeningOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * A once-per-tender OTP gate the whole Purchase Committee clears together before a tender
 * actually opens.
 *
 * The flow, in order:
 *   1. The Purchase Officer clicks Open — TenderController::openBidding() issues one code per
 *      active committee member and delivers it to that member's own login (notification bell)
 *      and inbox. The officer never sees those codes.
 *   2. The officer lands on openingOtpPanel(): one screen listing every committee member with
 *      a box for their code. Each member reads their own code out to the officer in person.
 *   3. verifyOpeningOtps() accepts whatever codes the officer has keyed in so far; partial
 *      entry is fine and the screen keeps the ones already accepted.
 *   4. The moment the last outstanding code is accepted, finalizeOpening() opens the tender
 *      (bid_opening_at + review_opened_at together), notifies vendors, and shares the
 *      submitted documents into every committee member's login — online uploads, scanned
 *      postal submissions, or both for a hybrid tender.
 *
 * Members do NOT enter codes in their own login; that earlier flow was replaced because it let
 * a single mailbox stand in for a member's physical presence at the opening.
 *
 * This gate also controls viewing/commenting on applications afterward — separate from (and a
 * prerequisite to) the formal sequential award-recommendation approval, which happens later
 * once a specific vendor is proposed for award.
 */
class TenderReviewController extends Controller
{
    private function isCommitteeMember(): bool
    {
        return auth()->user()->hasRole('purchase-committee');
    }

    public function openForReview($tenderId)
    {
        if (!$this->isCommitteeMember() && !auth()->user()->hasRole('super-admin') && !auth()->user()->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to open this tender for review.'], 403);
        }

        $tender = Tender::findOrFail($tenderId);

        if (!$tender->canOpenForReview()) {
            $reason = $tender->isReviewOpen()
                ? 'This tender has already been opened for committee review.'
                : 'Bids must be opened before applications can be reviewed.';
            return response()->json(['success' => false, 'message' => $reason], 400);
        }

        $members = PurchaseCommitteeMember::active()->get();
        if ($members->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'There are no active Purchase Committee members to notify.'], 400);
        }

        DB::transaction(function () use ($tender, $members) {
            $tender->update(['review_opened_at' => now(), 'review_opened_by' => auth()->id()]);

            foreach ($members as $member) {
                $reviewOtp = TenderReviewOtp::firstOrCreate(
                    ['tender_id' => $tender->id, 'committee_member_id' => $member->user_id]
                );
                $this->issueOpeningOtp($reviewOtp, auth()->user()->name);
            }
        });

        return response()->json([
            'success'  => true,
            'redirect' => route('purchase-committee.review.opening-codes', $tender->id),
            'message'  => 'A code has been sent to every Purchase Committee member\'s login. Collect each code in person and enter them together on the opening screen.',
        ]);
    }

    /**
     * The Purchase Officer's opening screen: every active committee member listed with a box
     * for the code they read out in person. Replaces the old per-member form in each member's
     * own login.
     */
    public function openingOtpPanel($tenderId)
    {
        $tender = Tender::with('bidOpenedBy')->findOrFail($tenderId);
        $this->authorizeOfficer($tender);

        if ($tender->isBidOpen()) {
            return redirect()->route($this->officerTenderRoute($tender), $tender->id)
                ->with('success', 'This tender is already open — the documents have been shared with the Purchase Committee.');
        }

        if (!$tender->isOpeningOtpPending()) {
            return redirect()->route($this->officerTenderRoute($tender), $tender->id)
                ->withErrors(['general' => 'No opening codes have been issued for this tender yet. Use "Open Tender" to issue them.']);
        }

        $otps = TenderReviewOtp::where('tender_id', $tender->id)
            ->with(['committeeMember', 'collectedBy'])
            ->get()
            ->sortBy(fn($otp) => PurchaseCommitteeMember::where('user_id', $otp->committee_member_id)->value('approval_order') ?? 999)
            ->values();

        $members = PurchaseCommitteeMember::active()->ordered()->with('user')->get()->keyBy('user_id');

        return view('purchase-committee.review.opening-codes', compact('tender', 'otps', 'members'));
    }

    /**
     * Accepts the codes the officer has keyed in — any subset of the outstanding members, so
     * the officer can enter them as members arrive rather than all at once. Each code is
     * checked against the member it was issued to, so a code read out by the wrong member
     * simply fails. When the last outstanding code is accepted the tender opens immediately.
     */
    public function verifyOpeningOtps(Request $request, $tenderId)
    {
        $tender = Tender::findOrFail($tenderId);
        $this->authorizeOfficer($tender);

        if ($tender->isBidOpen()) {
            return redirect()->route($this->officerTenderRoute($tender), $tender->id)
                ->withErrors(['general' => 'This tender has already been opened.']);
        }

        $request->validate([
            'otps'   => 'required|array',
            'otps.*' => 'nullable|string|max:10',
        ]);

        $submitted = collect($request->input('otps'))
            ->map(fn($code) => trim((string) $code))
            ->filter(fn($code) => $code !== '');

        if ($submitted->isEmpty()) {
            return back()->withErrors(['general' => 'Enter at least one committee member\'s code before confirming.']);
        }

        $accepted = [];
        $rejected = [];

        foreach ($submitted as $memberId => $code) {
            $reviewOtp = TenderReviewOtp::where('tender_id', $tender->id)
                ->where('committee_member_id', (int) $memberId)
                ->with('committeeMember')
                ->first();

            $memberName = $reviewOtp?->committeeMember?->name ?? ('Member #' . $memberId);

            if (!$reviewOtp) {
                $rejected[] = $memberName . ' — no opening code was issued to them for this tender.';
                continue;
            }

            if ($reviewOtp->isVerified()) {
                continue; // already collected earlier; nothing to do
            }

            if ($reviewOtp->isExpired()) {
                $rejected[] = $memberName . ' — their code has expired. Resend a fresh code and ask them again.';
                continue;
            }

            if (!$reviewOtp->isOtpValid($code)) {
                $rejected[] = $memberName . ' — that code is not correct.';
                continue;
            }

            $reviewOtp->update([
                'otp_verified_at'  => now(),
                'otp_collected_by' => auth()->id(),
            ]);
            $accepted[] = $memberName;
        }

        $tender = $tender->fresh();

        if ($tender->allOpeningOtpsVerified()) {
            $this->finalizeOpening($tender);

            return redirect()->route($this->officerTenderRoute($tender), $tender->id)
                ->with('success', 'All Purchase Committee codes accepted — this tender is now open. Vendors have been notified and the submitted documents are available in every committee member\'s login.');
        }

        $outstanding = $tender->openingOtpsOutstandingCount();
        $message = $accepted
            ? 'Accepted ' . count($accepted) . ' code(s): ' . implode(', ', $accepted) . '. '
            : '';
        $message .= $outstanding . ' committee member(s) still to go.';

        $redirect = back()->with('success', $message);

        return $rejected ? $redirect->withErrors(['codes' => $rejected]) : $redirect;
    }

    /**
     * Re-issues one member's code — for when a member mistypes, their code expires, or they
     * never received it. The new code goes to that member's login, not to the officer.
     */
    public function resendOpeningOtp(Request $request, $tenderId)
    {
        $tender = Tender::findOrFail($tenderId);
        $this->authorizeOfficer($tender);

        $request->validate(['committee_member_id' => 'required|integer']);

        $reviewOtp = TenderReviewOtp::where('tender_id', $tender->id)
            ->where('committee_member_id', $request->committee_member_id)
            ->with(['committeeMember', 'tender'])
            ->first();

        if (!$reviewOtp) {
            return back()->withErrors(['general' => 'No opening code exists for that committee member.']);
        }

        if ($reviewOtp->isVerified()) {
            return back()->withErrors(['general' => 'That member\'s code has already been accepted.']);
        }

        $this->issueOpeningOtp($reviewOtp, auth()->user()->name);

        return back()->with('success', 'A fresh code has been sent to ' . $reviewOtp->committeeMember->name . '\'s login. Ask them to read out the new one.');
    }

    /**
     * Members no longer enter their own code — kept so any bookmarked link explains the change
     * instead of 404ing.
     */
    public function otpForm($tenderId)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($this->isCommitteeMember() && $tender->isReviewUnlockedFor(auth()->id())) {
            return redirect()->route('purchase-committee.review.documents', $tender->id);
        }

        return redirect()->route('purchase-committee.tenders.index')->with('success',
            'Opening codes are no longer entered here. Your code is in your notifications — read it out to the Purchase Officer, who enters every member\'s code together to open the tender.');
    }

    public function applications($tenderId)
    {
        $tender = Tender::findOrFail($tenderId);
        $this->authorizeUnlocked($tender);

        $applications = TenderApplication::where('tender_id', $tenderId)
            ->with(['vendor', 'reviewComments'])
            ->orderByDesc('created_at')
            ->get();

        return view('purchase-committee.review.applications', compact('tender', 'applications'));
    }

    public function applicationShow($tenderId, $applicationId)
    {
        $tender = Tender::findOrFail($tenderId);
        $this->authorizeUnlocked($tender);

        $application = TenderApplication::where('tender_id', $tenderId)
            ->with(['vendor', 'reviewComments.committeeMember'])
            ->findOrFail($applicationId);

        return view('purchase-committee.review.application-show', compact('tender', 'application'));
    }

    public function compareBids($tenderId)
    {
        $tender = Tender::findOrFail($tenderId);
        $this->authorizeUnlocked($tender);

        $applications = TenderApplication::where('tender_id', $tenderId)
            ->with(['vendor', 'bidRecordedBy'])
            ->whereNotNull('bid_amount')
            ->orderBy('bid_amount', 'asc')
            ->get();

        // A ranking that silently omits bidders reads as complete when it is not. On a postal
        // tender the price is keyed in by hand after opening, so until that is done some
        // envelopes have no figure — name them rather than leaving them out of the comparison.
        $awaitingBid = TenderApplication::where('tender_id', $tenderId)
            ->with('vendor')
            ->whereNull('bid_amount')
            ->orderBy('application_number')
            ->get();

        return view('purchase-committee.review.compare-bids', compact('tender', 'applications', 'awaitingBid'));
    }

    /**
     * The documents shared into every committee member's login once the tender is open. What
     * shows up depends on how the tender was run:
     *   - online  → what each vendor uploaded through the portal;
     *   - postal  → the scans of the physical submissions, as they are recorded;
     *   - hybrid  → both, side by side per vendor.
     *
     * Reachable only to a member whose opening code was collected (authorizeUnlocked), which
     * is the whole point of the opening ceremony — nothing here is visible before the committee
     * opened the tender together.
     */
    public function documents($tenderId)
    {
        $tender = Tender::with('documents')->findOrFail($tenderId);
        $this->authorizeUnlocked($tender);

        $applications = TenderApplication::where('tender_id', $tenderId)
            ->with(['vendor', 'receivedDocuments'])
            ->orderBy('application_number')
            ->get();

        // Flatten each application down to the files a reviewer actually needs to open, so the
        // view does not have to know which column holds what.
        $bundles = $applications->map(function (TenderApplication $application) use ($tender) {
            $files = [];

            if ($tender->hasOnlineBids()) {
                if ($application->technical_proposal_file) {
                    $files[] = ['label' => 'Technical Proposal', 'path' => $application->technical_proposal_file, 'source' => 'online'];
                }
                if ($application->financial_proposal_file) {
                    $files[] = ['label' => 'Financial Proposal', 'path' => $application->financial_proposal_file, 'source' => 'online'];
                }
                foreach ((array) ($application->documents ?? []) as $doc) {
                    if (!empty($doc['path'])) {
                        $files[] = ['label' => $doc['name'] ?? 'Supporting Document', 'path' => $doc['path'], 'source' => 'online'];
                    }
                }
            }

            if ($tender->hasPostalBids()) {
                if ($application->postal_quote_scan) {
                    $files[] = ['label' => 'Scanned Postal Submission', 'path' => $application->postal_quote_scan, 'source' => 'postal'];
                }

                // The papers that physically arrived, recorded after the opening — uploaded
                // from disk or captured through the scan interface.
                foreach ($application->receivedDocuments as $received) {
                    $files[] = [
                        'label'  => $received->file_name,
                        'path'   => $received->file_path,
                        'source' => $received->isScan() ? 'scanned' : 'received',
                    ];
                }
            }

            // Nothing physical on record yet: neither a quote scan nor any received document.
            $nothingPhysical = !$application->postal_quote_scan && $application->receivedDocuments->isEmpty();

            return [
                'application' => $application,
                'files'       => $files,
                'awaiting_postal_entry' => $tender->hasPostalBids() && $nothingPhysical,
                'received_count' => $application->receivedDocuments->count(),
            ];
        });

        return view('purchase-committee.review.documents', compact('tender', 'bundles'));
    }

    /**
     * A committee member's recommendation on one application. The written remark is required;
     * taking a position on the award is not — a member may support, oppose, record no
     * objection, or leave the verdict blank and simply comment. The Purchase Officer sees the
     * tally when finalizing but is not bound by it.
     */
    public function storeComment(Request $request, $applicationId)
    {
        $application = TenderApplication::with('tender')->findOrFail($applicationId);
        $this->authorizeUnlocked($application->tender);

        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
            'verdict' => 'nullable|in:support,oppose,neutral',
        ]);

        ApplicationReviewComment::create([
            'application_id'      => $application->id,
            'committee_member_id' => auth()->id(),
            'comment'             => $validated['comment'],
            'verdict'             => $validated['verdict'] ?? null,
        ]);

        $said = match ($validated['verdict'] ?? null) {
            'support' => ' Your support for awarding this applicant has been recorded.',
            'oppose'  => ' Your objection to awarding this applicant has been recorded.',
            'neutral' => ' Recorded as no objection.',
            default   => '',
        };

        return back()->with('success', 'Your recommendation has been added.' . $said);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function authorizeUnlocked(Tender $tender): void
    {
        $user = auth()->user();
        $isPrivileged = $user->hasRole('super-admin') || $user->hasRole('admin')
            || ($user->hasRole('tender-owner') && $tender->owner_id === $user->id);

        if ($isPrivileged) return;

        if (!$this->isCommitteeMember() || !$tender->isReviewUnlockedFor($user->id)) {
            abort(403, 'This tender has not been opened for your review yet, or you have not verified your access code.');
        }
    }

    /**
     * Who may run an opening: the Purchase Officer / Admin / Super Admin, the tender's own
     * owner, or a committee member acting as the officer at the table. Note this is the person
     * keying codes in — it is never enough on its own, since every member's code is still
     * required before anything opens.
     */
    private function authorizeOfficer(Tender $tender): void
    {
        $user = auth()->user();

        $isOfficer = $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasRole('purchase-committee')
            || ($user->hasRole('tender-owner') && $tender->owner_id === $user->id);

        if (!$isOfficer) {
            abort(403, 'You are not authorized to collect opening codes for this tender.');
        }
    }

    /**
     * Where to send the officer once the opening screen is done with — their own role's tender
     * page, so an Admin is not bounced into the committee portal or vice versa.
     */
    private function officerTenderRoute(Tender $tender): string
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin'))        return 'super-admin.tenders.show';
        if ($user->hasRole('admin'))              return 'admin.tenders.show';
        if ($user->hasRole('tender-owner'))       return 'tender-owner.tenders.show';

        return 'purchase-committee.review.documents';
    }

    /**
     * Generates a member's code and pushes it to that member's own login (notification bell)
     * and inbox. Deliberately never returned to the caller: the officer is meant to learn the
     * code only from the member, in person.
     */
    private function issueOpeningOtp(TenderReviewOtp $reviewOtp, ?string $officerName = null): void
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(30);

        $reviewOtp->update([
            'otp_code'         => $code,
            'otp_expires_at'   => $expiresAt,
            'otp_sent_at'      => now(),
            'otp_verified_at'  => null,
            'otp_collected_by' => null,
        ]);

        try {
            $reviewOtp->committeeMember->notify(
                new TenderOpeningOtpNotification($reviewOtp->tender ?? $reviewOtp->tender()->first(), $code, $expiresAt, $officerName)
            );
        } catch (\Exception $e) {
            \Log::error('TenderOpeningOtpNotification failed for user ' . $reviewOtp->committee_member_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Called once the officer has keyed in every active committee member's opening code. Marks
     * the tender open (for vendors and for committee review at the same moment) and fires
     * everything the opening promises:
     *   - vendors are told their bids have been opened;
     *   - the submitted documents become readable in each committee member's own login, and
     *     every member is notified that they are there (online uploads, scanned postal
     *     submissions, or both for a hybrid tender);
     *   - where bid amounts are already in the system (online or hybrid), the committee also
     *     gets the ranked bid-amount spreadsheet by email. A purely postal tender has no
     *     amounts yet — those are keyed in afterward from the physical quotes — so there is
     *     nothing to summarize at this point.
     */
    private function finalizeOpening(Tender $tender): void
    {
        $tender->update([
            'bid_opening_at'   => now(),
            'review_opened_at' => now(),
            'review_opened_by' => $tender->bid_opened_by ?? auth()->id(),
        ]);

        $this->notifyVendorsBidsOpened($tender);
        $this->shareDocumentsWithCommittee($tender);

        if ($tender->hasOnlineBids()) {
            $this->emailBidSummaryToCommittee($tender);
        }
    }

    /**
     * Tells every active committee member the documents are now in their login. The access
     * itself is already granted by review_opened_at plus their verified code — this is the
     * notification that points them at it.
     */
    private function shareDocumentsWithCommittee(Tender $tender): void
    {
        $applicationCount = TenderApplication::where('tender_id', $tender->id)->count();
        $members = PurchaseCommitteeMember::active()->with('user')->get();

        foreach ($members as $member) {
            if (!$member->user) continue;

            try {
                $member->user->notify(new TenderDocumentsSharedNotification($tender, $applicationCount));
            } catch (\Exception $e) {
                \Log::error('TenderDocumentsSharedNotification failed for user ' . $member->user_id . ': ' . $e->getMessage());
            }
        }
    }

    private function notifyVendorsBidsOpened(Tender $tender): void
    {
        $vendorIds = TenderApplication::where('tender_id', $tender->id)
            ->whereNotNull('vendor_id')
            ->pluck('vendor_id')
            ->unique();
        $emails = Vendor::whereIn('id', $vendorIds)->pluck('email');

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new TenderBidOpenedMail($tender));
            } catch (\Exception $mailEx) {
                \Log::error('TenderBidOpenedMail failed to ' . $email . ': ' . $mailEx->getMessage());
            }
        }
    }

    private function emailBidSummaryToCommittee(Tender $tender): void
    {
        try {
            $path = \App\Services\TenderBidSpreadsheetBuilder::build($tender);
        } catch (\Exception $e) {
            \Log::error('TenderBidSpreadsheetBuilder failed for tender ' . $tender->id . ': ' . $e->getMessage());
            return;
        }

        $members = PurchaseCommitteeMember::active()->with('user')->get();
        foreach ($members as $member) {
            if (!$member->user?->email) continue;
            try {
                Mail::to($member->user->email)->send(new TenderBidSummaryMail($tender, $path));
            } catch (\Exception $mailEx) {
                \Log::error('TenderBidSummaryMail failed to ' . $member->user->email . ': ' . $mailEx->getMessage());
            }
        }

        @unlink($path);
    }
}
