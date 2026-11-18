<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorRejectedMail;

class VendorManagementController extends Controller
{
    /**
     * Send a vendor-facing status email.
     *
     * Delivery must never undo the status change, so failures are caught - but they
     * must not be silent either. Previously the admin was told "Vendor approved
     * successfully!" whether or not the mail went out, so a dead SMTP config or a
     * vendor with no address on file looked exactly like success. Returns '' when the
     * message was accepted, or a sentence explaining what went wrong.
     */
    private function notifyVendorByMail(Vendor $vendor, callable $makeMailable, string $what): string
    {
        $to = $this->vendorMailAddress($vendor);

        if ($to === null) {
            \Log::warning("Vendor {$what} email skipped: vendor {$vendor->id} has no email address on file.");

            return ' However, no email was sent: this vendor has no email address on file.';
        }

        try {
            Mail::to($to)->send($makeMailable());

            \Log::info("Vendor {$what} email sent to {$to} for vendor {$vendor->id}.");

            return '';
        } catch (\Throwable $e) {
            \Log::error("Vendor {$what} email error for vendor {$vendor->id}: " . $e->getMessage());

            return ' However, the notification email to ' . $to . ' could not be sent.';
        }
    }

    /**
     * Where a vendor-facing email should actually go.
     *
     * This used to read vendors.email and nothing else, so a vendor row with that column
     * blank had its approval mail dropped on the floor - which is exactly what the log
     * recorded, repeatedly, while the admin screen still said "Vendor approved
     * successfully!". The address is never really missing in that situation: the vendor
     * signs in with users.email, so that is the address to fall back to.
     *
     * The blank column is also worth repairing while we are here, so the next message -
     * and every screen that prints the vendor's contact details - has it too.
     */
    private function vendorMailAddress(Vendor $vendor): ?string
    {
        $onVendor = trim((string) $vendor->email);

        if ($onVendor !== '') {
            return $onVendor;
        }

        $onLogin = trim((string) ($vendor->user->email ?? ''));

        if ($onLogin === '') {
            return null;
        }

        try {
            $vendor->forceFill(['email' => $onLogin])->save();
            \Log::info("Backfilled vendors.email for vendor {$vendor->id} from its login address.");
        } catch (\Throwable $e) {
            \Log::warning("Could not backfill vendors.email for vendor {$vendor->id}: " . $e->getMessage());
        }

        return $onLogin;
    }

    /**
     * Flat map of stored document path => resolved public URL.
     *
     * The admin vendor panel is rendered client-side from this JSON, so the browser
     * cannot call Storage::url() itself. Without this map the JS falls back to
     * concatenating "/storage/" + path, which is wrong the moment the public disk is
     * S3: the file is in the bucket, not under the app's document root. Worse, that
     * guessed URL is caught by the framework's storage.local serve route, which has
     * no signature and a private default visibility, so the miss surfaces as a 403
     * rather than an honest 404.
     *
     * Shapes stored by the registration form (see AuthController::register):
     *   pan_document  => "path/to/file.pdf"
     *   gst_document  => ['path' => ..., 'name' => ...]
     *   certificates  => [key => ['path' => ..., 'name' => ..., 'label' => ...], ...]
     */
    private static function documentUrlMap(array $documents): array
    {
        $paths = [];

        foreach ($documents as $value) {
            if (is_string($value)) {
                $paths[] = $value;
            } elseif (is_array($value)) {
                if (isset($value['path']) && is_string($value['path'])) {
                    $paths[] = $value['path'];
                } else {
                    foreach ($value as $entry) {
                        if (is_array($entry) && isset($entry['path']) && is_string($entry['path'])) {
                            $paths[] = $entry['path'];
                        }
                    }
                }
            }
        }

        $map = [];

        foreach (array_unique($paths) as $path) {
            if ($path !== '') {
                $map[$path] = Storage::url($path);
            }
        }

        return $map;
    }

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
            $vendorData['document_urls'] = self::documentUrlMap(is_array($vendorData['registration_documents'] ?? null)
                ? $vendorData['registration_documents']
                : []);

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

            $notice = $this->notifyVendorByMail($vendor, fn () => new VendorApprovedMail($vendor), 'approval');

            return response()->json([
                'success'    => true,
                'message'    => 'Vendor approved successfully!' . $notice,
                'email_sent' => $notice === '',
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

            $notice = $this->notifyVendorByMail($vendor, fn () => new VendorRejectedMail($vendor, $request->reason), 'rejection');

            return response()->json([
                'success'    => true,
                'message'    => 'Vendor rejected successfully!' . $notice,
                'email_sent' => $notice === '',
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