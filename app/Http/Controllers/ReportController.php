<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user          = auth()->user();
        $isTenderOwner = $user->hasRole('tender-owner');

        $dateFrom  = $request->filled('date_from')  ? $request->date_from  : null;
        $dateTo    = $request->filled('date_to')    ? $request->date_to    : null;
        $tenderId  = $request->filled('tender_id')  ? $request->tender_id  : null;
        $vendorId  = $request->filled('vendor_id')  ? $request->vendor_id  : null;
        $status    = $request->filled('status')     ? $request->status     : null;
        $orderType = $request->filled('order_type') ? $request->order_type : null;

        $ownerTenderIds = $isTenderOwner ? $user->ownedTenders()->pluck('id') : null;

        $query = Contract::with(['tender', 'vendor', 'application.queries.sender'])
            ->orderBy('created_at', 'desc');

        if ($isTenderOwner) {
            $query->whereIn('tender_id', $ownerTenderIds);
        }
        if ($dateFrom)  { $query->whereDate('created_at', '>=', $dateFrom); }
        if ($dateTo)    { $query->whereDate('created_at', '<=', $dateTo); }
        if ($tenderId)  { $query->where('tender_id', $tenderId); }
        if ($vendorId)  { $query->where('vendor_id', $vendorId); }
        if ($status)    { $query->where('status', $status); }
        if ($orderType) { $query->where('order_type', $orderType); }

        $contracts = $query->paginate(25)->withQueryString();

        // Summary stats (unfiltered for totals)
        $base = Contract::query();
        if ($isTenderOwner) { $base->whereIn('tender_id', $ownerTenderIds); }

        $stats = [
            'total_contracts' => (clone $base)->count(),
            'total_value'     => (clone $base)->where('contract_value', '>', 0)->sum('contract_value'),
            'partial_orders'  => (clone $base)->where('is_partial_order', true)->count(),
            'work_orders'     => (clone $base)->where('order_type', 'work_order')->count(),
            'purchase_orders' => (clone $base)->where('order_type', 'purchase_order')->count(),
            'active'          => (clone $base)->where('status', 'active')->count(),
            'draft'           => (clone $base)->where('status', 'draft')->count(),
        ];

        if ($isTenderOwner) {
            $tenders = Tender::whereIn('id', $ownerTenderIds)->orderBy('title')->get();
            $vendors = Vendor::whereIn('id', Contract::whereIn('tender_id', $ownerTenderIds)->pluck('vendor_id')->unique())->orderBy('company_name')->get();
        } else {
            $tenders = Tender::orderBy('title')->get();
            $vendors = Vendor::orderBy('company_name')->get();
        }

        $rp = $user->hasRole('super-admin') ? 'super-admin' : ($isTenderOwner ? 'tender-owner' : 'admin');

        return view('reports.index', compact(
            'contracts', 'stats', 'tenders', 'vendors', 'rp',
            'dateFrom', 'dateTo', 'tenderId', 'vendorId', 'status', 'orderType'
        ));
    }
}

