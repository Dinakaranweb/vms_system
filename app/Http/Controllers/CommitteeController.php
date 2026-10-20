<?php

namespace App\Http\Controllers;

use App\Models\TenderApplication;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;

class CommitteeController extends Controller
{
    use HasDashboardWidgets;

    public function dashboard()
    {
        $user = auth()->user();

        $tenders = $user->committeeTenders()
            ->withPivot('role', 'designation', 'permissions', 'is_active')
            ->with(['eligibilityQuestions', 'applications'])
            ->get();

        $activeTenderIds = $tenders->where('pivot.is_active', true)->pluck('id');

        $pendingApplications = TenderApplication::whereIn('tender_id', $activeTenderIds)
            ->where('status', 'submitted')
            ->with(['tender:id,title,tender_number,bid_opening_at', 'vendor:id,company_name,email'])
            ->latest()
            ->take(10)
            ->get();

        $stats = [
            'assigned_tenders'    => $tenders->count(),
            'active_tenders'      => $tenders->where('pivot.is_active', true)->count(),
            'pending_review'      => TenderApplication::whereIn('tender_id', $activeTenderIds)->where('status', 'submitted')->count(),
            'total_applications'  => TenderApplication::whereIn('tender_id', $activeTenderIds)->count(),
        ];

        $widgetData = $this->getDashboardWidgetData();

        return view('committee.dashboard', compact('tenders', 'pendingApplications', 'stats', 'widgetData'));
    }

    public function tenders()
    {
        $tenders = auth()->user()->committeeTenders()
            ->withPivot('role', 'designation', 'is_active')
            ->withCount('applications')
            ->latest('tender_committee.created_at')
            ->get();

        return view('committee.tenders', compact('tenders'));
    }

    public function applications(Request $request)
    {
        $tenderIds = auth()->user()->committeeTenders()
            ->wherePivot('is_active', true)
            ->pluck('tenders.id');

        $query = TenderApplication::whereIn('tender_id', $tenderIds)
            ->with(['tender:id,title,tender_number,bid_opening_at', 'vendor:id,company_name,email']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->tender_id) {
            $query->where('tender_id', $request->tender_id);
        }

        $applications = $query->latest()->paginate(20)->withQueryString();

        $myTenders = auth()->user()->committeeTenders()
            ->wherePivot('is_active', true)
            ->get(['tenders.id', 'tenders.title', 'tenders.tender_number']);

        return view('committee.applications', compact('applications', 'myTenders'));
    }

    public function showApplication($id)
    {
        $tenderIds = auth()->user()->committeeTenders()
            ->wherePivot('is_active', true)
            ->pluck('tenders.id');

        $application = TenderApplication::whereIn('tender_id', $tenderIds)
            ->with(['tender', 'vendor'])
            ->findOrFail($id);

        return view('committee.application_show', compact('application'));
    }
}
