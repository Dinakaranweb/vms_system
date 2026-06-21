<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\TermsCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\WorkOrderMail;

class WorkOrderController extends Controller
{
    /**
     * Build preset terms from the masters DB table.
     * Falls back to hardcoded defaults if the table is empty/unavailable.
     */
    public static function presetTerms(): array
    {
        try {
            return [
                'work_order'     => TermsCondition::presetsFor('work_order'),
                'purchase_order' => TermsCondition::presetsFor('purchase_order'),
            ];
        } catch (\Exception $e) {
            // Table may not exist yet on remote server
            return [
                'purchase_order' => [
                    'Price'        => 'Rates are firm and inclusive of packing, forwarding charges.',
                    'Taxes'        => 'GST @ 18% is included in the quoted rates. No additional tax shall be payable.',
                    'Delivery Mode'=> 'Immediate delivery upon receipt of the Purchase Order.',
                    'Transportation'=> 'Included in VMRF-DU Scope.',
                    'Payment Mode' => '100% advance payment against the Purchase Order.',
                    'Quality'      => 'The Supplied product should be as per given specification. If any quality complaint arises, it should be replaced immediately by the supplier.',
                    'Cancellation' => 'The VMRF-DU reserves the right to cancel the purchase order in case of non-compliance with the agreed specifications, delivery schedule, or other terms and conditions.',
                    'Documents Required at Despatch' => 'Original Invoice with GST details, E-way Bill and warranty details.',
                ],
                'work_order' => [
                    'Duration'         => 'The work shall be completed within 30 days from the date of issuance of the work order as per discussion. If the work is not completed by the committed date, 0.5% of the total amount will be deducted.',
                    'Pricing'          => 'Inclusive of any additional work that may be performed related with the work order.',
                    'Payment Mode'     => '50% advance payment against Work order. 40% Advance payment against payment request. Remaining 10% balance payment after work completion.',
                    'Quality & Service'=> 'Should be superior. If any quality complaint arises, it should be rectified immediately.',
                    'Safety'           => 'Workers must adhere to safety measures (helmet/safety jacket/safety rope) at the work place. The contractor is responsible for the workers safety at the workplace.',
                    'Work Completion'  => 'Work completion report should be approved by Property Management department.',
                ],
            ];
        }
    }

    /**
     * Show the work order / purchase order form.
     */
    public function showForm($contractId)
    {
        $contract = Contract::with(['tender', 'vendor', 'application'])->findOrFail($contractId);
        $presets  = self::presetTerms();
        return view('admin.contracts.work_order_form', compact('contract', 'presets'));
    }

    /**
     * Save order data and download as PDF.
     */
    public function downloadPdf(Request $request, $contractId)
    {
        $contract = Contract::with(['tender', 'vendor'])->findOrFail($contractId);

        $request->validate([
            'order_type'    => 'required|in:purchase_order,work_order',
            'order_date'    => 'required|date',
            'gst_rate'      => 'required|numeric|min:0|max:100',
            'items'         => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'   => 'required|numeric|min:0',
            'items.*.uom'   => 'required|string',
            'items.*.rate'  => 'required|numeric|min:0',
        ]);

        // Build items array with computed amounts
        $items    = [];
        $subtotal = 0;
        foreach ($request->items as $item) {
            $amount    = round((float) $item['qty'] * (float) $item['rate'], 2);
            $subtotal += $amount;
            $items[]   = [
                'description'   => $item['description'],
                'qty'           => $item['qty'],
                'uom'           => $item['uom'],
                'rate'          => $item['rate'],
                'amount'        => $amount,
                'delivery_date' => !empty($item['delivery_date']) ? $item['delivery_date'] : null,
            ];
        }

        $discount  = (float) ($request->order_discount ?? 0);
        $gstRate   = (float) $request->gst_rate;
        $afterDisc = $subtotal - $discount;
        $gstAmount = round($afterDisc * $gstRate / 100, 2);
        $nettTotal = round($afterDisc + $gstAmount, 2);

        // Use custom order number if provided, otherwise auto-generate
        $orderType   = $request->order_type;
        $orderPrefix = $orderType === 'purchase_order' ? 'PO' : 'WO';
        if ($request->filled('custom_order_number')) {
            $orderNumber = trim($request->custom_order_number);
        } else {
            $year      = date('Y');
            $shortYear = date('y', strtotime('+1 year'));
            $seq       = Contract::whereNotNull('order_number')->count() + 1;
            $orderNumber = 'VMRF-DU/' . str_pad($seq, 3, '0', STR_PAD_LEFT) . '/' . $year . '-' . $shortYear;
        }

        // Persist to contract
        $contract->update([
            'order_type'          => $orderType,
            'order_number'        => $orderNumber,
            'order_date'          => $request->order_date,
            'vmrfdu_ref'          => $request->vmrfdu_ref,
            'supplier_ref'        => $request->supplier_ref,
            'order_items'         => $items,
            'gst_rate'            => $gstRate,
            'order_discount'      => $discount,
            'order_terms'         => $request->terms ?? [],
            'order_notes'         => $request->order_notes,
            'order_generated_at'  => now(),
        ]);

        $data = [
            'contract'      => $contract,
            'items'         => $items,
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'gst_rate'      => $gstRate,
            'gst_amount'    => $gstAmount,
            'nett_total'    => $nettTotal,
            'order_number'  => $orderNumber,
            'order_type'    => $orderType,
            'order_date'    => $request->order_date,
            'terms'         => $request->terms ?? [],
            'order_notes'   => $request->order_notes,
            'vmrfdu_ref'    => $request->vmrfdu_ref,
            'supplier_ref'  => $request->supplier_ref,
            'amount_words'  => $this->numberToWords($nettTotal),
        ];

        $pdf      = Pdf::loadView('pdf.work_order', $data)
                       ->setPaper('A4', 'portrait');
        $filename = $orderPrefix . '_' . str_replace('/', '-', $orderNumber) . '.pdf';

        // Auto-send email to vendor with the PDF attached
        try {
            $contract->load('vendor');
            $pdfContent = $pdf->output();
            Mail::to($contract->vendor->email)
                ->send(new WorkOrderMail($contract, $pdfContent, $filename));
        } catch (\Exception $e) {
            \Log::error('WO/PO auto-email failed: ' . $e->getMessage());
        }

        return $pdf->download($filename);
    }

    /**
     * Send the work order / purchase order email with PDF.
     */
    public function sendEmail(Request $request, $contractId)
    {
        $contract = Contract::with(['tender', 'vendor'])->findOrFail($contractId);

        if (!$contract->order_type || !$contract->order_items) {
            return response()->json([
                'success' => false,
                'message' => 'Please generate the order document first before sending the email.',
            ], 422);
        }

        $items    = $contract->order_items;
        $subtotal = array_sum(array_column($items, 'amount'));
        $discount = (float) $contract->order_discount;
        $gstRate  = (float) $contract->gst_rate;
        $afterDisc= $subtotal - $discount;
        $gstAmount= round($afterDisc * $gstRate / 100, 2);
        $nettTotal= round($afterDisc + $gstAmount, 2);

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
        $pdfContent = $pdf->output();

        $orderLabel = $contract->order_type === 'work_order' ? 'Work Order' : 'Purchase Order';
        $filename   = $orderLabel . '_' . str_replace('/', '-', $contract->order_number) . '.pdf';

        try {
            // Send to vendor
            Mail::to($contract->vendor->email)
                ->send(new WorkOrderMail($contract, $pdfContent, $filename));

            return response()->json([
                'success' => true,
                'message' => $orderLabel . ' sent to ' . $contract->vendor->email . ' successfully!',
            ]);
        } catch (\Exception $e) {
            \Log::error('Work order email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert a number to words (Indian system).
     */
    private function numberToWords(float $amount): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $intPart  = (int) floor($amount);
        $fracPart = (int) round(($amount - $intPart) * 100);

        $words = $this->convertToWords($intPart, $ones, $tens);
        if ($fracPart > 0) {
            $words .= ' and ' . $this->convertToWords($fracPart, $ones, $tens) . ' Paise';
        }
        return trim($words) . ' Rupees Only';
    }

    private function convertToWords(int $n, array $ones, array $tens): string
    {
        if ($n === 0) return 'Zero';
        if ($n < 0)   return 'Minus ' . $this->convertToWords(-$n, $ones, $tens);

        $words = '';
        if ($n >= 10000000) {
            $words .= $this->convertToWords((int) ($n / 10000000), $ones, $tens) . ' Crore ';
            $n %= 10000000;
        }
        if ($n >= 100000) {
            $words .= $this->convertToWords((int) ($n / 100000), $ones, $tens) . ' Lakh ';
            $n %= 100000;
        }
        if ($n >= 1000) {
            $words .= $this->convertToWords((int) ($n / 1000), $ones, $tens) . ' Thousand ';
            $n %= 1000;
        }
        if ($n >= 100) {
            $words .= $ones[(int) ($n / 100)] . ' Hundred ';
            $n %= 100;
        }
        if ($n >= 20) {
            $words .= $tens[(int) ($n / 10)] . ' ';
            $n %= 10;
        }
        if ($n > 0) {
            $words .= $ones[$n] . ' ';
        }
        return trim($words);
    }
}
