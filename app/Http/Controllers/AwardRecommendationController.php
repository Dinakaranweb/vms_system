<?php

namespace App\Http\Controllers;

use App\Mail\AwardApprovalOtpMail;
use App\Mail\AwardApprovalStepMail;
use App\Models\AwardRecommendation;
use App\Models\AwardRecommendationApproval;
use App\Models\PurchaseCommitteeMember;
use App\Models\TenderApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AwardRecommendationController extends Controller
{
    private function canInitiate(TenderApplication $application): bool
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) return true;
        if ($user->hasRole('tender-owner') && $application->tender->owner_id === $user->id) return true;
        return false;
    }

    public function create($applicationId)
    {
        $application = TenderApplication::with(['tender', 'vendor'])->findOrFail($applicationId);

        if (!$this->canInitiate($application)) {
            abort(403, 'You are not authorized to initiate an award recommendation for this application.');
        }
        if ($application->status !== 'shortlisted') {
            return back()->withErrors(['general' => 'Only shortlisted applications can be recommended for award.']);
        }
        if (!$application->tender->isBidOpen()) {
            return back()->withErrors(['general' => 'Bids must be opened before an award can be recommended.']);
        }
        if (AwardRecommendation::where('application_id', $applicationId)->exists()) {
            return redirect()->route('purchase-committee.recommendations.show', AwardRecommendation::where('application_id', $applicationId)->first()->id);
        }

        $members = PurchaseCommitteeMember::active()->ordered()->with('user')->get();

        return view('purchase-committee.recommendations.create', compact('application', 'members'));
    }

    public function store(Request $request, $applicationId)
    {
        $application = TenderApplication::with(['tender', 'vendor'])->findOrFail($applicationId);

        if (!$this->canInitiate($application)) {
            abort(403, 'You are not authorized to initiate an award recommendation for this application.');
        }

        $members = PurchaseCommitteeMember::active()->ordered()->get();
        if ($members->isEmpty()) {
            return back()->withErrors(['general' => 'There are no active Purchase Committee members to route this recommendation to.']);
        }

        $request->validate(['initiation_note' => 'nullable|string']);

        DB::beginTransaction();
        try {
            $recommendation = AwardRecommendation::create([
                'tender_id'       => $application->tender_id,
                'application_id'  => $application->id,
                'initiated_by'    => auth()->id(),
                'status'          => 'pending_review',
                'initiation_note' => $request->initiation_note,
            ]);

            foreach ($members->values() as $i => $member) {
                AwardRecommendationApproval::create([
                    'award_recommendation_id' => $recommendation->id,
                    'committee_member_id'     => $member->user_id,
                    'approval_order'          => $i + 1,
                    'status'                  => 'pending',
                ]);
            }

            DB::commit();

            $this->notifyCurrentApprover($recommendation);

            return redirect()->route('purchase-committee.recommendations.show', $recommendation->id)
                ->with('success', 'Award recommendation initiated — routed to the Purchase Committee for sequential approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to initiate recommendation: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $recommendation = AwardRecommendation::with([
            'tender', 'application.vendor', 'application.reviewComments.committeeMember', 'initiator',
            'approvals.committeeMember',
        ])->findOrFail($id);

        $user = auth()->user();
        $isAssignedMember = $recommendation->approvals->contains('committee_member_id', $user->id);
        $isPrivileged = $user->hasRole('super-admin') || $user->hasRole('admin')
            || ($user->hasRole('tender-owner') && $recommendation->tender->owner_id === $user->id);

        if (!$isAssignedMember && !$isPrivileged) {
            abort(403, 'You are not part of this award recommendation.');
        }

        $myApproval = $recommendation->approvals->firstWhere('committee_member_id', $user->id);
        $currentStep = $recommendation->currentStepApproval();
        $isMyTurn = $myApproval && $currentStep && $myApproval->id === $currentStep->id;

        return view('purchase-committee.recommendations.show', compact('recommendation', 'myApproval', 'isMyTurn'));
    }

    public function approve(Request $request, $id)
    {
        $recommendation = AwardRecommendation::with('approvals')->findOrFail($id);
        $request->validate(['comments' => 'nullable|string']);

        $step = $this->authorizeCurrentStep($recommendation);

        $step->update([
            'status'      => 'approved',
            'comments'    => $request->comments,
            'decided_at'  => now(),
        ]);

        if ($recommendation->isFullyApprovedByCommittee()) {
            $this->beginOtpStage($recommendation);
            return back()->with('success', 'You approved this recommendation. All committee members have now approved — OTP codes have been emailed to everyone for final confirmation.');
        }

        $this->notifyCurrentApprover($recommendation->fresh());

        return back()->with('success', 'Your approval has been recorded. The next committee member has been notified.');
    }

    public function reject(Request $request, $id)
    {
        $recommendation = AwardRecommendation::with('approvals')->findOrFail($id);
        $request->validate(['comments' => 'required|string|min:5']);

        $step = $this->authorizeCurrentStep($recommendation);

        DB::transaction(function () use ($step, $recommendation, $request) {
            $step->update(['status' => 'rejected', 'comments' => $request->comments, 'decided_at' => now()]);
            $recommendation->update(['status' => 'rejected']);
        });

        return back()->with('success', 'You rejected this recommendation. The workflow has been stopped.');
    }

    public function verifyOtp(Request $request, $id)
    {
        $recommendation = AwardRecommendation::with('approvals')->findOrFail($id);
        $request->validate(['otp' => 'required|string']);

        $approval = $recommendation->approvals->firstWhere('committee_member_id', auth()->id());
        if (!$approval) {
            abort(403, 'You are not part of this award recommendation.');
        }

        if (!$approval->isOtpValid(trim($request->otp))) {
            return back()->withErrors(['otp' => 'Invalid or expired code. Please request a new one or check your email.']);
        }

        $approval->update(['otp_verified_at' => now()]);

        if ($recommendation->fresh()->isFullyOtpVerified()) {
            $recommendation->update(['status' => 'completed', 'completed_at' => now()]);
            return back()->with('success', 'Code verified. All committee members have confirmed — this recommendation is now fully approved and ready to be awarded.');
        }

        return back()->with('success', 'Code verified. Waiting on the remaining committee members to confirm.');
    }

    public function resendOtp($id)
    {
        $recommendation = AwardRecommendation::with('approvals')->findOrFail($id);
        $approval = $recommendation->approvals->firstWhere('committee_member_id', auth()->id());

        if (!$approval || $recommendation->status !== 'otp_pending' || $approval->otp_verified_at !== null) {
            return back()->withErrors(['otp' => 'A new code cannot be issued right now.']);
        }

        $this->issueOtp($approval);

        return back()->with('success', 'A new code has been sent to your email.');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function authorizeCurrentStep(AwardRecommendation $recommendation): AwardRecommendationApproval
    {
        if ($recommendation->status !== 'pending_review') {
            abort(400, 'This recommendation is no longer awaiting committee review.');
        }

        $step = $recommendation->currentStepApproval();
        if (!$step || $step->committee_member_id !== auth()->id()) {
            abort(403, 'It is not your turn to act on this recommendation, or you are not assigned to it.');
        }

        return $step;
    }

    private function notifyCurrentApprover(AwardRecommendation $recommendation): void
    {
        $step = $recommendation->currentStepApproval();
        if (!$step || !$step->committeeMember) return;

        try {
            Mail::to($step->committeeMember->email)->send(new AwardApprovalStepMail($recommendation));
        } catch (\Exception $e) {
            \Log::error('AwardApprovalStepMail failed: ' . $e->getMessage());
        }
    }

    private function beginOtpStage(AwardRecommendation $recommendation): void
    {
        $recommendation->update(['status' => 'otp_pending']);

        foreach ($recommendation->approvals as $approval) {
            $this->issueOtp($approval);
        }
    }

    private function issueOtp(AwardRecommendationApproval $approval): void
    {
        $code = (string) random_int(100000, 999999);
        $approval->update([
            'otp_code'        => $code,
            'otp_expires_at'  => now()->addMinutes(30),
            'otp_verified_at' => null,
        ]);

        try {
            Mail::to($approval->committeeMember->email)->send(new AwardApprovalOtpMail($approval, $code));
        } catch (\Exception $e) {
            \Log::error('AwardApprovalOtpMail failed: ' . $e->getMessage());
        }
    }
}
