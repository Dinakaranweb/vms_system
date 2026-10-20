<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\Vendor;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use HasDashboardWidgets;

    public function dashboard()
    {
        $stats = [
            'total_vendors'    => Vendor::count(),
            'pending_vendors'  => Vendor::where('status', 'pending')->count(),
            'approved_vendors' => Vendor::where('status', 'approved')->count(),
            'rejected_vendors' => Vendor::where('status', 'rejected')->count(),
        ];

        $tenderStats = [
            'total'     => Tender::count(),
            'published' => Tender::where('status', 'published')->count(),
            'draft'     => Tender::where('status', 'draft')->count(),
            'closed'    => Tender::where('status', 'closed')->count(),
            'online'    => Tender::where('tender_type', 'online')->count(),
            'postal'    => Tender::where('tender_type', 'postal')->count(),
            'hybrid'    => Tender::where('tender_type', 'hybrid')->count(),
        ];

        $appStats = [
            'total'       => TenderApplication::count(),
            'submitted'   => TenderApplication::where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::where('status', 'shortlisted')->count(),
            'rejected'    => TenderApplication::where('status', 'rejected')->count(),
            'awarded'     => TenderApplication::where('status', 'awarded')->count(),
        ];

        // Last 6 months data for line charts
        $vendorMonthly = [];
        $appMonthly    = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $vendorMonthly[] = [
                'label' => $m->format('M y'),
                'count' => Vendor::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
            $appMonthly[] = [
                'label' => $m->format('M y'),
                'count' => TenderApplication::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        }

        $recentTenders = Tender::withCount('applications')->latest()->take(5)->get();
        $pendingVendors = Vendor::where('status', 'pending')->latest()->paginate(10);
        $widgetData = $this->getDashboardWidgetData();

        return view('admin.dashboard', compact(
            'stats', 'tenderStats', 'appStats',
            'vendorMonthly', 'appMonthly',
            'recentTenders', 'pendingVendors', 'widgetData'
        ));
    }
    
    public function vendors()
    {
        $vendors = Vendor::with('user')->latest()->paginate(15);
        return view('admin.vendors.index', compact('vendors'));
    }
    
    public function vendorDetails($id)
    {
        $vendor = Vendor::with('user')->findOrFail($id);
        return view('admin.vendors.show', compact('vendor'));
    }
    
    public function approveVendor($id)
    {
        DB::beginTransaction();
        
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vendor approved successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve vendor.'
            ], 500);
        }
    }
    
    public function rejectVendor(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10'
        ]);
        
        DB::beginTransaction();
        
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'approved_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Vendor rejected successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject vendor.'
            ], 500);
        }
    }
    
    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);
        return view('admin.notifications.index', compact('notifications'));
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

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'vendor_ids' => 'required|array',
            'vendor_ids.*' => 'exists:vendors,id'
        ]);
        
        DB::beginTransaction();
        
        try {
            Vendor::whereIn('id', $request->vendor_ids)->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => count($request->vendor_ids) . ' vendors approved successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve vendors.'
            ], 500);
        }
    }
}