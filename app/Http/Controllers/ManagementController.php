<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;

class ManagementController extends Controller
{
    use HasDashboardWidgets;

    /**
     * Tenders visible to the Management (oversight) role: published or closed only.
     * Draft tenders are not yet public and stay hidden from this role too.
     */
    private function visibleTendersQuery()
    {
        return Tender::whereIn('status', ['published', 'closed']);
    }

    public function dashboard()
    {
        $stats = [
            'total'     => $this->visibleTendersQuery()->count(),
            'published' => (clone $this->visibleTendersQuery())->where('status', 'published')->count(),
            'closed'    => (clone $this->visibleTendersQuery())->where('status', 'closed')->count(),
            'online'    => (clone $this->visibleTendersQuery())->where('tender_type', 'online')->count(),
            'postal'    => (clone $this->visibleTendersQuery())->where('tender_type', 'postal')->count(),
            'hybrid'    => (clone $this->visibleTendersQuery())->where('tender_type', 'hybrid')->count(),
        ];

        $recentTenders = $this->visibleTendersQuery()
            ->with(['owner', 'categories'])
            ->withCount('applications')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        $widgetData = $this->getDashboardWidgetData();

        return view('management.dashboard', compact('stats', 'recentTenders', 'widgetData'));
    }

    public function tenders(Request $request)
    {
        $query = $this->visibleTendersQuery()->with(['owner', 'categories'])->withCount('applications');

        if ($request->tender_type && in_array($request->tender_type, ['online', 'postal', 'hybrid'])) {
            $query->where('tender_type', $request->tender_type);
        }

        if ($request->status && in_array($request->status, ['published', 'closed'])) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('tender_number', 'like', "%{$search}%");
            });
        }

        $tenders = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('management.tenders.index', compact('tenders'));
    }

    public function show($id)
    {
        $tender = $this->visibleTendersQuery()
            ->with(['owner', 'creator', 'categories', 'committeeMembers', 'queries.sender'])
            ->withCount('applications')
            ->findOrFail($id);

        return view('management.tenders.show', compact('tender'));
    }
}
