<?php

namespace App\Http\Controllers;

use App\Models\AwardRecommendation;
use App\Models\AwardRecommendationApproval;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\TenderReviewOtp;
use App\Traits\HasDashboardWidgets;

class PurchaseCommitteePortalController extends Controller
{
    use HasDashboardWidgets;

    public function dashboard()
    {
        $userId = auth()->id();

        $myPendingApprovals = AwardRecommendationApproval::where('committee_member_id', $userId)
            ->where('status', 'pending')
            ->whereHas('recommendation', fn($q) => $q->where('status', 'pending_review'))
            ->with(['recommendation.tender', 'recommendation.application.vendor'])
            ->get()
            ->filter(fn($approval) => $approval->recommendation->currentStepApproval()?->id === $approval->id);

        $myPendingOtps = AwardRecommendationApproval::where('committee_member_id', $userId)
            ->whereNull('otp_verified_at')
            ->whereHas('recommendation', fn($q) => $q->where('status', 'otp_pending'))
            ->with(['recommendation.tender', 'recommendation.application.vendor'])
            ->get();

        // Only tenders where opening hasn't even been initiated yet — once initiated, they show
        // up in $myPendingOpeningOtps instead (or nowhere, for members who already verified).
        $tendersToOpen = Tender::where('status', 'closed')
            ->whereNull('bid_opening_at')
            ->doesntHave('reviewOtps')
            ->withCount('applications')
            ->orderByDesc('submission_deadline')
            ->take(8)
            ->get();

        $myPendingOpeningOtps = TenderReviewOtp::where('committee_member_id', $userId)
            ->whereNull('otp_verified_at')
            ->whereHas('tender', fn($q) => $q->whereNull('bid_opening_at'))
            ->with('tender')
            ->take(8)
            ->get();

        $recentlyOpenedTenders = Tender::whereNotNull('bid_opening_at')
            ->orderByDesc('bid_opening_at')
            ->take(5)
            ->get();

        $postalPendingEntry = TenderApplication::whereHas('tender', function ($q) {
                $q->whereIn('tender_type', ['postal', 'hybrid'])->whereNotNull('review_opened_at');
            })
            ->whereNull('bid_amount')
            ->with(['tender', 'vendor'])
            ->take(8)
            ->get();

        // Open postal/hybrid tenders where some envelope has nothing recorded against it yet.
        $receivedPending = Tender::whereIn('tender_type', ['postal', 'hybrid'])
            ->whereNotNull('bid_opening_at')
            ->withCount([
                'applications',
                'applications as applications_without_received_count' => function ($q) {
                    $q->whereDoesntHave('receivedDocuments');
                },
            ])
            ->orderByDesc('bid_opening_at')
            ->get()
            ->filter(fn($t) => $t->applications_without_received_count > 0)
            ->take(8)
            ->values();

        $stats = [
            'pending_approvals'    => $myPendingApprovals->count(),
            'pending_otps'         => $myPendingOtps->count(),
            'tenders_to_open'      => $tendersToOpen->count(),
            'opening_otps_pending' => $myPendingOpeningOtps->count(),
            'postal_pending'       => $postalPendingEntry->count(),
            'received_pending'     => $receivedPending->sum('applications_without_received_count'),
        ];

        $widgetData = $this->getDashboardWidgetData();

        return view('purchase-committee.dashboard', compact(
            'myPendingApprovals', 'myPendingOtps', 'tendersToOpen', 'myPendingOpeningOtps',
            'recentlyOpenedTenders', 'postalPendingEntry', 'receivedPending', 'stats', 'widgetData'
        ));
    }

    /**
     * The committee member's own notification feed. This is where a member reads the opening
     * code they then give to the Purchase Officer in person, so it is reachable from the bell
     * and from the dashboard prompt.
     */
    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('purchase-committee.notifications.index', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function myRecommendations()
    {
        $userId = auth()->id();

        $recommendations = AwardRecommendation::whereHas('approvals', fn($q) => $q->where('committee_member_id', $userId))
            ->with(['tender', 'application.vendor', 'approvals'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('purchase-committee.recommendations.index', compact('recommendations'));
    }

    public function tenders()
    {
        $tenders = Tender::whereIn('status', ['published', 'closed'])
            ->with('owner')
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('purchase-committee.tenders', compact('tenders'));
    }
}
