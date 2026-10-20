<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\ApplicationAwardedMail;
use App\Mail\ApplicationShortlistedMail;
use App\Mail\ApplicationRejectedByAdminMail;
use App\Mail\WorkOrderMail;
use Barryvdh\DomPDF\Facade\Pdf;

class TenderOwnerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user       = auth()->user();
        $tenderType = in_array($request->tender_type, ['online', 'postal', 'hybrid']) ? $request->tender_type : 'online';

        $allTenderIds  = $user->ownedTenders()->pluck('id');
        $typeTenderIds = $user->ownedTenders()->where('tender_type', $tenderType)->pluck('id');

        $query = TenderApplication::with(['tender', 'vendor'])
            ->whereIn('tender_id', $typeTenderIds);

        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        if ($request->tender_id && $request->tender_id != 'all') {
            $query->where('tender_id', $request->tender_id);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $typeScope = TenderApplication::whereIn('tender_id', $typeTenderIds);
        $stats = [
            'total'       => (clone $typeScope)->count(),
            'submitted'   => (clone $typeScope)->where('status', 'submitted')->count(),
            'shortlisted' => (clone $typeScope)->where('status', 'shortlisted')->count(),
            'rejected'    => (clone $typeScope)->where('status', 'rejected')->count(),
            'awarded'     => (clone $typeScope)->where('status', 'awarded')->count(),
        ];

        $tenders = $user->ownedTenders()->where('tender_type', $tenderType)->get();

        return view('tender-owner.applications.index', compact('applications', 'stats', 'tenders', 'tenderType'));
    }

    public function show($id)
    {
        $application = TenderApplication::with(['tender', 'vendor', 'tender.owner', 'eligibilityAnswers.question', 'queries.sender'])->findOrFail($id);

        if ($application->tender->owner_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this application.');
        }

        $contractExists = Contract::where('application_id', $id)->exists();

        if (request()->ajax()) {
            return view('tender-owner.applications.show', compact('application', 'contractExists'));
        }

        return view('tender-owner.applications.detail', compact('application', 'contractExists'));
    }

    public function shortlist($id)
    {
        try {
            $application = TenderApplication::findOrFail($id);

            if ($application->tender->owner_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($application->status !== 'submitted') {
                return response()->json(['success' => false, 'message' => 'Only submitted applications can be shortlisted.'], 400);
            }

            $application->update([
                'status'      => 'shortlisted',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_remarks' => request()->remarks ?? null,
            ]);

            try {
                $application->load('tender', 'vendor');
                Mail::to($application->vendor->email)->send(new ApplicationShortlistedMail($application));
            } catch (\Exception $e) {
                \Log::error('Shortlisted email error: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Application shortlisted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to shortlist: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate(['reason' => 'required|string|min:10']);

            $application = TenderApplication::findOrFail($id);

            if ($application->tender->owner_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($application->status !== 'submitted') {
                return response()->json(['success' => false, 'message' => 'Only submitted applications can be rejected.'], 400);
            }

            $application->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->reason,
                'reviewed_by'      => auth()->id(),
                'reviewed_at'      => now(),
            ]);

            try {
                $application->load('tender', 'vendor');
                Mail::to($application->vendor->email)->send(new ApplicationRejectedByAdminMail($application));
            } catch (\Exception $e) {
                \Log::error('Rejected email error: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Application rejected successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reject: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the award + WO/PO creation form.
     */
    public function awardForm($id)
    {
        $application = TenderApplication::with(['tender', 'vendor'])->findOrFail($id);

        if ($application->tender->owner_id !== auth()->id()) {
            abort(403, 'You do not have permission to award this application.');
        }

        $isPartialOrder = (bool) request()->query('partial_order', false);

        if (!$isPartialOrder && $application->status !== 'shortlisted') {
            return redirect()->back()->with('error', 'Only shortlisted applications can be awarded.');
        }
        if ($isPartialOrder && !in_array($application->status, ['shortlisted', 'awarded'])) {
            return redirect()->back()->with('error', 'Cannot create a partial order for this application.');
        }

        if (!$isPartialOrder && ($gateReason = \App\Models\AwardRecommendation::gateBlocksAward($application))) {
            return redirect()->back()->with('error', $gateReason);
        }

        // Block if the tender is already awarded to a different application
        if (!$isPartialOrder) {
            $alreadyAwarded = TenderApplication::where('tender_id', $application->tender_id)
                ->where('status', 'awarded')
                ->where('id', '!=', $application->id)
                ->exists();
            if ($alreadyAwarded) {
                return redirect()->back()->with('error', 'This tender has already been awarded to another vendor. Only one award is allowed per tender.');
            }
        }

        $year               = date('Y');
        $shortYear          = date('y', strtotime('+1 year'));
        $seq                = Contract::whereNotNull('order_number')->count() + 1;
        $previewOrderNumber = 'VMRF-DU/' . str_pad($seq, 3, '0', STR_PAD_LEFT) . '/' . $year . '-' . $shortYear;

        $presets = WorkOrderController::presetTerms();

        return view('tender-owner.applications.award', compact('application', 'previewOrderNumber', 'presets', 'isPartialOrder'));
    }

    /**
     * Award an application, create contract, generate WO/PO, send emails.
     * Auto-signs both parties when tender has require_contract_signing = false.
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

            if ($application->tender->owner_id !== auth()->id()) {
                return back()->withInput()->withErrors(['general' => 'Unauthorized.']);
            }

            $isPartialOrder = (bool) $request->is_partial_order;

            if (!$isPartialOrder && $application->status !== 'shortlisted') {
                return back()->withInput()->withErrors(['general' => 'Only shortlisted applications can be awarded.']);
            }
            if ($isPartialOrder && !in_array($application->status, ['shortlisted', 'awarded'])) {
                return back()->withInput()->withErrors(['general' => 'Cannot create a partial order for this application.']);
            }

            if (!$isPartialOrder && ($gateReason = \App\Models\AwardRecommendation::gateBlocksAward($application))) {
                DB::rollBack();
                return back()->withInput()->withErrors(['general' => $gateReason]);
            }

            // Block if the tender already has a different awarded application
            if (!$isPartialOrder) {
                $alreadyAwarded = TenderApplication::where('tender_id', $application->tender_id)
                    ->where('status', 'awarded')
                    ->where('id', '!=', $id)
                    ->exists();
                if ($alreadyAwarded) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(['general' => 'This tender has already been awarded to another vendor. Only one award is allowed per tender.']);
                }
            }

            if (!$isPartialOrder || $application->status === 'shortlisted') {
                $application->update([
                    'status'      => 'awarded',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }

            // Contract & order numbers
            $year          = date('Y');
            $contractCount = Contract::whereYear('created_at', $year)->count() + 1;
            $contractNumber = 'CTR-' . $year . '-' . str_pad($contractCount, 4, '0', STR_PAD_LEFT);

            $requireSigning = $application->tender->require_contract_signing ?? true;
            $contractStatus = $requireSigning ? 'draft' : 'active';

            $orderType = $request->order_type;
            $shortYear = date('y', strtotime('+1 year'));
            $seq       = Contract::whereNotNull('order_number')->count() + 1;
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
                'contract_value'  => $request->contract_value,
                'status'          => $contractStatus,
                'payment_terms'   => $request->payment_terms,
                'deliverables'    => $request->deliverables,
                'order_type'      => $orderType,
                'order_number'    => $orderNumber,
                'order_date'      => $request->order_date,
                'vmrfdu_ref'       => $request->vmrfdu_ref,
                'supplier_ref'     => $request->supplier_ref,
                'contract_value'   => $request->contract_value ?? 0,
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

            // Build line items for details mode
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

            // Attachment mode: store uploaded file
            if ($request->order_mode === 'attachment' && $request->hasFile('order_attachment')) {
                $file = $request->file('order_attachment');
                $path = $file->store("order-attachments/{$contract->id}", 'public');
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

            // Email: awarded notification
            try {
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

            // Email: WO/PO uploaded attachment
            if ($request->order_mode === 'attachment') {
                try {
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

            $label = $orderType === 'work_order' ? 'Work Order' : 'Purchase Order';
            return redirect()->route('tender-owner.contracts.show', $contract->id)
                ->with('success', "Application awarded and {$label} ({$orderNumber}) created successfully!");

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Owner award error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['general' => 'Failed to award: ' . $e->getMessage()]);
        }
    }

    public function getStats()
    {
        $tenderIds = auth()->user()->ownedTenders()->pluck('id');

        return response()->json([
            'total'       => TenderApplication::whereIn('tender_id', $tenderIds)->count(),
            'submitted'   => TenderApplication::whereIn('tender_id', $tenderIds)->where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::whereIn('tender_id', $tenderIds)->where('status', 'shortlisted')->count(),
            'rejected'    => TenderApplication::whereIn('tender_id', $tenderIds)->where('status', 'rejected')->count(),
            'awarded'     => TenderApplication::whereIn('tender_id', $tenderIds)->where('status', 'awarded')->count(),
        ]);
    }

    private function numberToWords(float $amount): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $intPart  = (int)floor($amount);
        $fracPart = (int)round(($amount - $intPart) * 100);

        $convert = function (int $n) use (&$convert, $ones, $tens): string {
            if ($n === 0) return '';
            if ($n < 20)  return $ones[$n] . ' ';
            if ($n < 100) return $tens[(int)($n / 10)] . ' ' . ($n % 10 ? $ones[$n % 10] . ' ' : '');
            if ($n < 1000) return $ones[(int)($n / 100)] . ' Hundred ' . $convert($n % 100);
            if ($n < 100000)  return $convert((int)($n / 1000)) . 'Thousand ' . $convert($n % 1000);
            if ($n < 10000000) return $convert((int)($n / 100000)) . 'Lakh ' . $convert($n % 100000);
            return $convert((int)($n / 10000000)) . 'Crore ' . $convert($n % 10000000);
        };

        $words = trim($convert($intPart));
        $result = $words ? 'Rupees ' . $words . 'Only' : 'Rupees Zero Only';
        if ($fracPart > 0) {
            $result = 'Rupees ' . $words . 'and ' . trim($convert($fracPart)) . 'Paise Only';
        }
        return $result;
    }
}
