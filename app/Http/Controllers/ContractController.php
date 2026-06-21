<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    /**
     * Display a listing of contracts.
     */
    public function index()
    {
        $contracts = Contract::with('tender', 'vendor')->orderBy('created_at', 'desc')->paginate(10);
        
        $stats = [
            'total' => Contract::count(),
            'active' => Contract::where('status', 'active')->count(),
            'expired' => Contract::where('status', 'expired')->count(),
            'terminated' => Contract::where('status', 'terminated')->count(),
            'total_value' => Contract::where('status', 'active')->sum('contract_value'),
        ];
        
        return view('admin.contracts.index', compact('contracts', 'stats'));
    }

    /**
     * Show contract details.
     */
    public function show($id)
    {
        $contract = Contract::with('tender', 'vendor', 'extensions')->findOrFail($id);
        return view('admin.contracts.show', compact('contract'));
    }

    /**
     * Generate contract from awarded application.
     */
    public function generate(Request $request, $applicationId)
    {
        $application = TenderApplication::with('tender', 'vendor')->findOrFail($applicationId);
        
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'contract_value' => 'required|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            $contract = Contract::create([
                'tender_id' => $application->tender_id,
                'vendor_id' => $application->vendor_id,
                'application_id' => $application->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'contract_value' => $validated['contract_value'],
                'status' => 'draft',
                'payment_terms' => $request->payment_terms ?? null,
                'deliverables' => $request->deliverables ?? null,
            ]);
            
            // Update application status
            $application->update(['status' => 'awarded']);
            
            DB::commit();
            
            return redirect()->route('admin.contracts.show', $contract->id)
                ->with('success', 'Contract generated successfully!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to generate contract: ' . $e->getMessage()]);
        }
    }

    /**
     * Sign contract by admin.
     */
    public function sign($id)
    {
        try {
            $contract = Contract::findOrFail($id);
            $contract->update([
                'signed_by_admin' => auth()->id(),
                'admin_signed_at' => now(),
            ]);
            
            // If both signed, activate contract
            if ($contract->signed_by_vendor) {
                $contract->update(['status' => 'active']);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Contract signed successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sign contract'
            ], 500);
        }
    }

    /**
     * Extend contract.
     */
    public function extend(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);
        
        $validated = $request->validate([
            'new_end_date' => 'required|date|after:' . $contract->end_date,
            'additional_value' => 'nullable|numeric|min:0',
            'reason' => 'required|string',
        ]);
        
        DB::beginTransaction();
        
        try {
            $extension = $contract->extend(
                $validated['new_end_date'],
                $validated['additional_value'] ?? null,
                $validated['reason']
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Contract extension requested successfully!'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend contract'
            ], 500);
        }
    }

    /**
     * Upload supporting documents to an awarded contract.
     */
    public function uploadSupportingDocs(Request $request, $id)
    {
        $request->validate([
            'supporting_docs'   => 'required|array|min:1',
            'supporting_docs.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls|max:20480',
        ]);

        try {
            $contract  = Contract::findOrFail($id);
            $existing  = $contract->supporting_documents ?? [];

            foreach ($request->file('supporting_docs') as $file) {
                $path      = $file->store('contracts/' . $contract->id . '/supporting', 'public');
                $existing[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'size'          => $file->getSize(),
                    'mime'          => $file->getClientMimeType(),
                    'uploaded_by'   => auth()->id(),
                    'uploaded_at'   => now()->toDateTimeString(),
                ];
            }

            $contract->update(['supporting_documents' => $existing]);

            return response()->json([
                'success'   => true,
                'message'   => count($request->file('supporting_docs')) . ' document(s) uploaded successfully.',
                'documents' => $existing,
            ]);
        } catch (\Exception $e) {
            \Log::error('Supporting doc upload error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a supporting document from a contract.
     */
    public function deleteSupportingDoc(Request $request, $id)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        try {
            $contract = Contract::findOrFail($id);
            $docs     = $contract->supporting_documents ?? [];
            $idx      = (int) $request->index;
            if (!isset($docs[$idx])) {
                return response()->json(['success' => false, 'message' => 'Document not found.'], 404);
            }
            Storage::disk('public')->delete($docs[$idx]['path']);
            array_splice($docs, $idx, 1);
            $contract->update(['supporting_documents' => array_values($docs)]);
            return response()->json(['success' => true, 'message' => 'Document deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Download the already-generated WO/PO PDF from saved contract data.
     */
    public function downloadOrderPdf($id)
    {
        $contract = Contract::with(['tender', 'vendor'])->findOrFail($id);

        if (empty($contract->order_items)) {
            return back()->with('error', 'This order was created with an attachment — no PDF to generate.');
        }

        $items    = $contract->order_items;
        $subtotal = 0;
        foreach ($items as &$item) {
            $amt            = round((float)($item['qty'] ?? 0) * (float)($item['rate'] ?? 0), 2);
            $item['amount'] = $amt;
            $subtotal      += $amt;
        }
        unset($item);

        $discount  = (float)($contract->order_discount ?? 0);
        $gstRate   = (float)($contract->gst_rate ?? 0);
        $afterDisc = $subtotal - $discount;
        $gstAmount = round($afterDisc * $gstRate / 100, 2);
        $nettTotal = round($afterDisc + $gstAmount, 2);

        $data = [
            'contract'     => $contract,
            'items'        => $items,
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'gst_rate'     => $gstRate,
            'gst_amount'   => $gstAmount,
            'nett_total'   => $nettTotal,
            'order_number' => $contract->order_number,
            'order_type'   => $contract->order_type,
            'order_date'   => $contract->order_date,
            'terms'        => $contract->order_terms ?? [],
            'order_notes'  => $contract->order_notes,
            'vmrfdu_ref'   => $contract->vmrfdu_ref,
            'supplier_ref' => $contract->supplier_ref,
            'amount_words' => $this->numberToWords($nettTotal),
        ];

        $pdf      = Pdf::loadView('pdf.work_order', $data)->setPaper('A4', 'portrait');
        $prefix   = $contract->order_type === 'purchase_order' ? 'PO' : 'WO';
        $filename = $prefix . '_' . str_replace('/', '-', $contract->order_number ?? $contract->contract_number) . '.pdf';

        return $pdf->download($filename);
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
            if ($n >= $div) {
                $w .= $this->numWords((int)($n / $div), $ones, $tens) . " {$label} ";
                $n %= $div;
            }
        }
        if ($n >= 20) { $w .= $tens[(int)($n / 10)] . ' '; $n %= 10; }
        if ($n > 0)   { $w .= $ones[$n] . ' '; }
        return $w;
    }

    /**
     * Terminate contract.
     */
    public function terminate(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);
        
        try {
            $contract = Contract::findOrFail($id);
            $contract->terminate($request->reason);
            
            return response()->json([
                'success' => true,
                'message' => 'Contract terminated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate contract'
            ], 500);
        }
    }
}