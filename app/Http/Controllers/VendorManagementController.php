<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;

class VendorManagementController extends Controller
{
    /**
     * Display a listing of vendors.
     */
   public function index()
{
    $vendors = Vendor::with('user')->orderBy('created_at', 'desc')->paginate(10);

    $totalVendors = Vendor::count();
    $pendingVendors = Vendor::where('status', 'pending')->count();
    $approvedVendors = Vendor::where('status', 'approved')->count();
    $rejectedVendors = Vendor::where('status', 'rejected')->count();
    $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'color', 'icon']);

    return view('admin.vendors.index', compact('vendors', 'totalVendors', 'pendingVendors', 'approvedVendors', 'rejectedVendors', 'categories'));
}
    /**
     * Get vendors data for DataTable.
     */
    public function getData(Request $request)
    {
        try {
            $query = Vendor::with('user')->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->status && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->search && $request->search != '') {
                $query->where(function($q) use ($request) {
                    $q->where('company_name', 'like', '%' . $request->search . '%')
                      ->orWhere('vendor_code', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('pan_number', 'like', '%' . $request->search . '%');
                });
            }

            $vendors = $query->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $vendors->items(),
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'total' => $vendors->total(),
                'from' => $vendors->firstItem(),
                'to' => $vendors->lastItem(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display vendor details.
     */
    public function show($id)
    {
        try {
            $vendor = Vendor::with(['user', 'categories:id'])->findOrFail($id);
            $vendorData = $vendor->toArray();
            $vendorData['category_ids'] = $vendor->categories->pluck('id')->toArray();

            return response()->json([
                'success' => true,
                'vendor' => $vendorData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update categories assigned to a vendor.
     */
    public function updateCategories(Request $request, $id)
    {
        try {
            $vendor = Vendor::findOrFail($id);

            if ($vendor->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Categories can only be assigned to approved vendors.'
                ], 422);
            }

            $categoryIds = $request->input('category_ids', []);
            $vendor->categories()->sync($categoryIds);

            return response()->json([
                'success' => true,
                'message' => 'Categories updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Approve vendor.
     */
    public function approve($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            
            $vendor->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            if ($vendor->user) {
                $vendor->user->update(['is_active' => true]);
            }

            // Notify vendor
            try {
                Mail::to($vendor->email)->send(new VendorApprovedMail($vendor));
            } catch (\Exception $e) {
                \Log::error('Vendor approved email error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Vendor approved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reject vendor.
     */
    public function reject(Request $request, $id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            
            $vendor->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            
            if ($vendor->user) {
                $vendor->user->update(['is_active' => false]);
            }

            // Notify vendor
            try {
                Mail::to($vendor->email)->send(new VendorRejectedMail($vendor, $request->reason));
            } catch (\Exception $e) {
                \Log::error('Vendor rejected email error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Vendor rejected successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete vendor.
     */
    public function destroy($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            
            if ($vendor->user) {
                $vendor->user->delete();
            }
            
            $vendor->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Export vendors.
     */
    public function export()
    {
        $vendors = Vendor::all();
        
        $filename = 'vendors_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://output', 'w');
        
        // Headers
        fputcsv($handle, ['Vendor Code', 'Company Name', 'Email', 'Phone', 'PAN Number', 'GST Number', 'Status', 'Registered Date']);
        
        // Data
        foreach ($vendors as $vendor) {
            fputcsv($handle, [
                $vendor->vendor_code,
                $vendor->company_name,
                $vendor->email,
                $vendor->phone,
                $vendor->pan_number,
                $vendor->gst_number ?? 'N/A',
                $vendor->status,
                $vendor->created_at->format('Y-m-d')
            ]);
        }
        
        return response()->stream(function() use ($handle) {
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}