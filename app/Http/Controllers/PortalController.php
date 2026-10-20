<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;

/**
 * Generic, permission-gated module for roles created dynamically via the Super Admin
 * Role Builder. Any brand-new role automatically gets a working dashboard (composed of
 * whichever widgets Super Admin assigned it) and a read-only tender oversight view —
 * the same capability set the "Management" role uses, made reusable for any future role.
 */
class PortalController extends Controller
{
    use HasDashboardWidgets;

    public function dashboard()
    {
        $widgetData = $this->getDashboardWidgetData();

        return view('portal.dashboard', compact('widgetData'));
    }

    public function tenders(Request $request)
    {
        $query = Tender::whereIn('status', ['published', 'closed'])->with(['owner'])->withCount('applications');

        if ($request->tender_type && in_array($request->tender_type, ['online', 'postal', 'hybrid'])) {
            $query->where('tender_type', $request->tender_type);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$search}%")->orWhere('tender_number', 'like', "%{$search}%"));
        }

        $tenders = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('portal.tenders.index', compact('tenders'));
    }

    public function show($id)
    {
        $tender = Tender::whereIn('status', ['published', 'closed'])
            ->with(['owner', 'creator', 'categories', 'queries.sender'])
            ->withCount('applications')
            ->findOrFail($id);

        return view('portal.tenders.show', compact('tender'));
    }
}
