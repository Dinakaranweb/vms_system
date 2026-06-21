<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_vendors' => Vendor::count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'approved_vendors' => Vendor::where('status', 'approved')->count(),
            'rejected_vendors' => Vendor::where('status', 'rejected')->count(),
        ];
        
        $pendingVendors = Vendor::where('status', 'pending')->latest()->paginate(10);
        
        return view('admin.dashboard', compact('stats', 'pendingVendors'));
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