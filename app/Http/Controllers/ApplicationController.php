<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\ApplicationShortlistedMail;
use App\Mail\ApplicationRejectedByAdminMail;
use App\Mail\ApplicationAwardedMail;
use App\Mail\WorkOrderMail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\WorkOrderController;

class ApplicationController extends Controller
{
    /**
     * Display a listing of applications (Admin view).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenderId = $request->tender_id;
        $statusFilter = $request->status;

        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            $tenders = Tender::orderBy('title')->get(['id', 'title', 'tender_number']);
            $query   = TenderApplication::with(['tender', 'vendor'])->orderBy('created_at', 'desc');
        } elseif ($user->hasRole('tender-owner')) {
            $tenders = $user->ownedTenders()->orderBy('title')->get(['id', 'title', 'tender_number']);
            $query   = TenderApplication::whereIn('tender_id', $tenders->pluck('id'))
                           ->with(['tender', 'vendor'])->orderBy('created_at', 'desc');
        } elseif (method_exists($user, 'committeeTenders') && $user->committeeTenders()->exists()) {
            $committeeIds = $user->committeeTenders()->wherePivot('is_active', true)->pluck('tender_id');
            $tenders = Tender::whereIn('id', $committeeIds)->orderBy('title')->get(['id', 'title', 'tender_number']);
            $query   = TenderApplication::whereIn('tender_id', $committeeIds)
                           ->with(['tender', 'vendor'])->orderBy('created_at', 'desc');
        } else {
            $tenders = collect();
            $vendor  = $user->vendor;
            $query   = $vendor
                ? TenderApplication::where('vendor_id', $vendor->id)->with(['tender', 'vendor'])->orderBy('created_at', 'desc')
                : TenderApplication::whereRaw('0=1');
        }

        if ($tenderId) {
            $query->where('tender_id', $tenderId);
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $applications = $query->paginate(15)->withQueryString();

        $stats = [
            'total'       => TenderApplication::count(),
            'submitted'   => TenderApplication::where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::where('status', 'shortlisted')->count(),
            'rejected'    => TenderApplication::where('status', 'rejected')->count(),
            'awarded'     => TenderApplication::where('status', 'awarded')->count(),
        ];

        return view('admin.applications.index', compact('applications', 'stats', 'tenders', 'tenderId', 'statusFilter'));
    }

    /**
     * Display the specified application.
     */
    public function show($id)
{
    $application = TenderApplication::with(['tender', 'vendor', 'tender.creator'])->findOrFail($id);
    
    // Check if user has access to this application
    $user = auth()->user();
    $tenderId = $application->tender_id;
    
    if (!$user->canAccessTender($tenderId)) {
        // Check if user is the vendor who submitted the application
        if ($user->hasRole('vendor') && $user->vendor && $user->vendor->id == $application->vendor_id) {
            // Vendor can view their own application
        } else {
            abort(403, 'You do not have permission to view this application.');
        }
    }
    
    $contractExists = Contract::where('application_id', $id)->exists();
    
    if (request()->ajax()) {
        return view('admin.applications.show', compact('application', 'contractExists'));
    }
    
    return view('admin.applications.show', compact('application', 'contractExists'));
}
    /**
     * Shortlist an application.
     */
   public function shortlist(Request $request, $id)
{
    $application = TenderApplication::findOrFail($id);
    $user = auth()->user();
    $tenderId = $application->tender_id;
    
    // Check permissions
    $canShortlist = false;
    
    if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
        $canShortlist = true;
    } else if ($user->hasRole('tender-owner') && $user->ownedTenders()->where('id', $tenderId)->exists()) {
        $canShortlist = true;
    } else if ($user->isCommitteeMember($tenderId) && $user->hasCommitteePermission($tenderId, 'shortlist_applications')) {
        $canShortlist = true;
    }
    
    if (!$canShortlist) {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to shortlist applications.'
        ], 403);
    }
    
    // Only allow shortlisting for submitted applications
    if ($application->status !== 'submitted') {
        return response()->json([
            'success' => false,
            'message' => 'Only submitted applications can be shortlisted.'
        ], 400);
    }
    
    $application->update([
        'status'        => 'shortlisted',
        'reviewed_by'   => auth()->id(),
        'reviewed_at'   => now(),
        'admin_remarks' => $request->remarks ?? null,
    ]);

    // Notify vendor
    try {
        $application->load('tender', 'vendor');
        Mail::to($application->vendor->email)->send(new ApplicationShortlistedMail($application));
    } catch (\Exception $e) {
        \Log::error('Shortlisted email error: ' . $e->getMessage());
    }

    return response()->json([
        'success' => true,
        'message' => 'Application shortlisted successfully!'
    ]);
}
    /**
     * Reject an application.
     */
    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'reason' => 'required|string|min:10'
            ]);
            
            $application = TenderApplication::findOrFail($id);
            
            // Only allow rejection for submitted applications
            if ($application->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only submitted applications can be rejected.'
                ], 400);
            }
            
            $application->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->reason,
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
            ]);

            // Notify vendor
            try {
                $application->load('tender', 'vendor');
                Mail::to($application->vendor->email)->send(new ApplicationRejectedByAdminMail($application));
            } catch (\Exception $e) {
                \Log::error('Rejected email error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Application rejected successfully!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Reject error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the award + WO/PO creation form.
     */
    public function awardForm($id)
    {
        $application = TenderApplication::with(['tender', 'vendor'])->findOrFail($id);
        $isPartialOrder = (bool) request()->query('partial_order', false);

        // Partial orders allowed on already-awarded applications
        if (!$isPartialOrder && $application->status !== 'shortlisted') {
            return redirect()->back()->with('error', 'Only shortlisted applications can be awarded.');
        }
        if ($isPartialOrder && !in_array($application->status, ['shortlisted', 'awarded'])) {
            return redirect()->back()->with('error', 'Cannot create a partial order for this application.');
        }

        $year      = date('Y');
        $shortYear = date('y', strtotime('+1 year'));
        $seq       = Contract::whereNotNull('order_number')->count() + 1;
        $previewOrderNumber = 'VMRF-DU/' . str_pad($seq, 3, '0', STR_PAD_LEFT) . '/' . $year . '-' . $shortYear;

        $presets = WorkOrderController::presetTerms();

        return view('admin.applications.award', compact('application', 'previewOrderNumber', 'presets', 'isPartialOrder'));
    }

    /**
     * Award an application, create contract, and generate WO/PO in one step.
     */
    public function award(Request $request, $id)
    {
        $request->validate([
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',
            'contract_value' => 'nullable|numeric|min:0',
            'order_type'     => 'required|in:purchase_order,work_order',
            'order_date'     => 'required|date',
            'order_mode'     => 'required|in:details,attachment',
        ]);

        if ($request->order_mode === 'details') {
            $request->validate([
                'items'               => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.qty'         => 'required|numeric|min:0',
                'items.*.uom'         => 'required|string',
                'items.*.rate'        => 'required|numeric|min:0',
                'gst_rate'            => 'required|numeric|min:0|max:100',
            ]);
        }

        try {
            DB::beginTransaction();

            $application = TenderApplication::with(['tender', 'vendor.user'])->findOrFail($id);
            $isPartialOrder = (bool) $request->is_partial_order;

            if (!$isPartialOrder && $application->status !== 'shortlisted') {
                return back()->withInput()->withErrors(['general' => 'Only shortlisted applications can be awarded.']);
            }
            if ($isPartialOrder && !in_array($application->status, ['shortlisted', 'awarded'])) {
                return back()->withInput()->withErrors(['general' => 'Cannot create a partial order for this application.']);
            }

            // Only update application status on first award (not partial orders)
            if (!$isPartialOrder || $application->status === 'shortlisted') {
                $application->update([
                    'status'      => 'awarded',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }

            // Contract number
            $year          = date('Y');
            $contractCount = Contract::whereYear('created_at', $year)->count() + 1;
            $contractNumber = 'CTR-' . $year . '-' . str_pad($contractCount, 4, '0', STR_PAD_LEFT);

            $requireSigning = $application->tender->require_contract_signing ?? true;
            $contractStatus = $requireSigning ? 'draft' : 'active';

            // Order number
            $orderType   = $request->order_type;
            $shortYear   = date('y', strtotime('+1 year'));
            $seq         = Contract::whereNotNull('order_number')->count() + 1;
            $orderNumber = $request->filled('custom_order_number')
                ? trim($request->custom_order_number)
                : 'VMRF-DU/' . str_pad($seq, 3, '0', STR_PAD_LEFT) . '/' . $year . '-' . $shortYear;

            $contractData = [
                'contract_number' => $contractNumber,
                'tender_id'       => $application->tender_id,
                'vendor_id'       => $application->vendor_id,
                'application_id'  => $application->id,
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'contract_value'  => $request->contract_value ?? 0,
                'status'          => $contractStatus,
                'payment_terms'   => $request->payment_terms,
                'deliverables'    => $request->deliverables,
                'order_type'      => $orderType,
                'order_number'    => $orderNumber,
                'order_date'      => $request->order_date,
                'vmrfdu_ref'       => $request->vmrfdu_ref,
                'supplier_ref'     => $request->supplier_ref,
                'is_partial_order' => $isPartialOrder,
            ];

            // Auto-sign both parties when signing is not required
            if (!$requireSigning) {
                $contractData['signed_by_admin'] = auth()->id();
                $contractData['admin_signed_at'] = now();
                $vendorUser = $application->vendor->user ?? null;
                if ($vendorUser) {
                    $contractData['signed_by_vendor'] = $vendorUser->id;
                    $contractData['vendor_signed_at'] = now();
                }
            }

            // Build line-items if "details" mode
            $items = [];
            $subtotal = $discount = $gstRate = $gstAmount = $nettTotal = 0;

            if ($request->order_mode === 'details') {
                foreach ($request->items as $item) {
                    $amount    = round((float)$item['qty'] * (float)$item['rate'], 2);
                    $subtotal += $amount;
                    $items[]   = [
                        'description' => $item['description'],
                        'qty'         => $item['qty'],
                        'uom'         => $item['uom'],
                        'rate'        => $item['rate'],
                        'amount'      => $amount,
                    ];
                }
                $discount  = (float)($request->order_discount ?? 0);
                $gstRate   = (float)$request->gst_rate;
                $afterDisc = $subtotal - $discount;
                $gstAmount = round($afterDisc * $gstRate / 100, 2);
                $nettTotal = round($afterDisc + $gstAmount, 2);

                $contractData += [
                    'order_items'        => $items,
                    'gst_rate'           => $gstRate,
                    'order_discount'     => $discount,
                    'order_terms'        => $request->terms ?? [],
                    'order_notes'        => $request->order_notes,
                    'order_generated_at' => now(),
                ];
            }

            $contract = Contract::create($contractData);

            // Attachment mode: store uploaded file as supporting document
            if ($request->order_mode === 'attachment' && $request->hasFile('order_attachment')) {
                $file  = $request->file('order_attachment');
                $path  = $file->store("order-attachments/{$contract->id}", 'public');
                $contract->update([
                    'supporting_documents' => [[
                        'original_name' => $file->getClientOriginalName(),
                        'path'          => $path,
                        'size'          => $file->getSize(),
                        'mime'          => $file->getClientMimeType(),
                        'type'          => 'order_document',
                        'uploaded_by'   => auth()->id(),
                        'uploaded_at'   => now()->toDateTimeString(),
                    ]],
                    'order_generated_at' => now(),
                ]);
            }

            DB::commit();

            // Email: application awarded
            try {
                $application->load('tender', 'vendor');
                Mail::to($application->vendor->email)->send(new ApplicationAwardedMail($application));
            } catch (\Exception $e) {
                \Log::error('Award email error: ' . $e->getMessage());
            }

            // Email: WO/PO PDF (details mode)
            if ($request->order_mode === 'details' && count($items)) {
                try {
                    $contract->load('vendor');
                    $pdfData = [
                        'contract'     => $contract,
                        'items'        => $items,
                        'subtotal'     => $subtotal,
                        'discount'     => $discount,
                        'gst_rate'     => $gstRate,
                        'gst_amount'   => $gstAmount,
                        'nett_total'   => $nettTotal,
                        'order_number' => $orderNumber,
                        'order_type'   => $orderType,
                        'order_date'   => $request->order_date,
                        'terms'        => $request->terms ?? [],
                        'order_notes'  => $request->order_notes,
                        'vmrfdu_ref'   => $request->vmrfdu_ref,
                        'supplier_ref' => $request->supplier_ref,
                        'amount_words' => $this->numberToWords($nettTotal),
                    ];
                    $pdf      = Pdf::loadView('pdf.work_order', $pdfData)->setPaper('A4', 'portrait');
                    $prefix   = $orderType === 'purchase_order' ? 'PO' : 'WO';
                    $filename = $prefix . '_' . str_replace('/', '-', $orderNumber) . '.pdf';
                    Mail::to($contract->vendor->email)->send(new WorkOrderMail($contract, $pdf->output(), $filename));
                } catch (\Exception $e) {
                    \Log::error('WO/PO email error: ' . $e->getMessage());
                }
            }

            // Email: WO/PO uploaded attachment (attachment mode)
            if ($request->order_mode === 'attachment') {
                try {
                    $contract->load('vendor');
                    $fresh    = $contract->fresh();
                    $orderDoc = collect($fresh->supporting_documents ?? [])->firstWhere('type', 'order_document');
                    if ($orderDoc) {
                        $fileContent = Storage::disk('public')->get($orderDoc['path']);
                        Mail::to($contract->vendor->email)->send(
                            new WorkOrderMail($contract, $fileContent, $orderDoc['original_name'], $orderDoc['mime'] ?? 'application/octet-stream')
                        );
                    }
                } catch (\Exception $e) {
                    \Log::error('WO/PO attachment email error: ' . $e->getMessage());
                }
            }

            $rolePrefix = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin';
            $label = $orderType === 'work_order' ? 'Work Order' : 'Purchase Order';

            return redirect()->route("{$rolePrefix}.contracts.show", $contract->id)
                ->with('success', "Application awarded and {$label} ({$orderNumber}) created successfully!");

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Award error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['general' => 'Failed to award: ' . $e->getMessage()]);
        }
    }

    private function numberToWords(float $amount): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $int  = (int) floor($amount);
        $frac = (int) round(($amount - $int) * 100);
        $words = $this->numWords($int, $ones, $tens);
        if ($frac > 0) {
            $words .= ' and ' . $this->numWords($frac, $ones, $tens) . ' Paise';
        }
        return trim($words) . ' Rupees Only';
    }

    private function numWords(int $n, array $ones, array $tens): string
    {
        if ($n === 0) return 'Zero';
        $w = '';
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand', 100 => 'Hundred'] as $div => $label) {
            if ($n >= $div) { $w .= $this->numWords((int)($n / $div), $ones, $tens) . " {$label} "; $n %= $div; }
        }
        if ($n >= 20) { $w .= $tens[(int)($n / 10)] . ' '; $n %= 10; }
        if ($n > 0)   { $w .= $ones[$n] . ' '; }
        return trim($w);
    }

    /**
     * Bulk action on applications.
     */
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:shortlist,reject',
                'application_ids' => 'required|array',
                'application_ids.*' => 'exists:tender_applications,id',
                'reason' => 'required_if:action,reject|nullable|string'
            ]);
            
            $applications = TenderApplication::whereIn('id', $request->application_ids)
                ->where('status', 'submitted');
            
            foreach ($applications->get() as $application) {
                if ($request->action === 'shortlist') {
                    $application->update([
                        'status' => 'shortlisted',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);
                } elseif ($request->action === 'reject') {
                    $application->update([
                        'status' => 'rejected',
                        'rejection_reason' => $request->reason,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => count($request->application_ids) . ' application(s) ' . $request->action . 'ed successfully!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Bulk action error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get applications statistics for dashboard.
     */
    public function getStats()
    {
        return response()->json([
            'total' => TenderApplication::count(),
            'submitted' => TenderApplication::where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::where('status', 'shortlisted')->count(),
            'rejected' => TenderApplication::where('status', 'rejected')->count(),
            'awarded' => TenderApplication::where('status', 'awarded')->count(),
        ]);
    }
}