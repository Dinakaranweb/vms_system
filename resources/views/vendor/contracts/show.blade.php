@extends('layouts.dashboard')

@section('title', 'Contract Details')
@section('header', 'Contract Details')

@php
    $hasItems  = !empty($contract->order_items);
    $orderDoc  = collect($contract->supporting_documents ?? [])->firstWhere('type', 'order_document');
    $orderLabel = $contract->order_type === 'purchase_order' ? 'Purchase Order' : 'Work Order';
@endphp

@push('styles')
<style>
@media print {
    .sidebar, .topbar, nav, .no-print,
    button, a[href*="sign"], a[href*="back"] { display: none !important; }
    body, .main-content, .content-area { background: #fff !important; padding: 0 !important; margin: 0 !important; }
    .max-w-4xl { max-width: 100% !important; }
    .bg-white { box-shadow: none !important; border: none !important; }
    .bg-gradient-to-r { background: #1e3a5f !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .text-white { color: #fff !important; }
    .bg-gray-50 { background: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bg-green-50, .bg-red-50, .bg-orange-50 { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rounded-xl, .rounded-lg { border-radius: 0 !important; }
    .shadow-sm { box-shadow: none !important; }
    .px-6 { padding-left: 12px !important; padding-right: 12px !important; }
    .grid { display: block !important; }
    .grid > div { display: inline-block !important; width: 48% !important; vertical-align: top !important; margin-bottom: 8px !important; }
    h4 { font-size: 13px !important; font-weight: 700 !important; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px !important; }
    p, label, span { font-size: 12px !important; }
    .text-2xl { font-size: 18px !important; }
    .border-t { border-top: 1px solid #e2e8f0 !important; }
}
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-file-contract mr-2"></i>{{ $contract->contract_number }}
                </h3>
                <a href="{{ route('vendor.contracts') }}" class="text-white hover:text-gray-200">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Contracts
                </a>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Status Banner -->
            <div class="mb-6">
                @if($contract->status == 'active')
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold text-green-800">Contract is Active</p>
                                <p class="text-sm text-green-600">Valid until {{ $contract->end_date->format('d M, Y') }}</p>
                            </div>
                        </div>
                    </div>
                @elseif($contract->status == 'expired')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-red-600 text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold text-red-800">Contract has Expired</p>
                                <p class="text-sm text-red-600">Expired on {{ $contract->end_date->format('d M, Y') }}</p>
                            </div>
                        </div>
                    </div>
                @elseif($contract->status == 'terminated')
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <i class="fas fa-ban text-orange-600 text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold text-orange-800">Contract has been Terminated</p>
                                @if($contract->termination_reason)
                                    <p class="text-sm text-orange-600">Reason: {{ $contract->termination_reason }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Contract Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Contract Number</label>
                    <p class="font-mono font-semibold text-gray-900">{{ $contract->contract_number }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Status</label>
                    <p>
                        @if($contract->status == 'active')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                        @elseif($contract->status == 'expired')
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Expired</span>
                        @elseif($contract->status == 'terminated')
                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">Terminated</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">{{ ucfirst($contract->status) }}</span>
                        @endif
                    </p>
                </div>
                <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Tender</label>
                    <p class="font-semibold text-gray-900">{{ $contract->tender->title ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">Tender #: {{ $contract->tender->tender_number ?? 'N/A' }}</p>
                </div>
                @if($contract->contract_value > 0)
                <div>
                    <label class="text-xs text-gray-500 uppercase">Contract Value</label>
                    <p class="text-2xl font-bold text-green-600">₹{{ number_format($contract->contract_value, 2) }}</p>
                </div>
                @endif
                <div>
                    <label class="text-xs text-gray-500 uppercase">Duration</label>
                    <p class="text-gray-900">{{ $contract->start_date->format('d M, Y') }} - {{ $contract->end_date->format('d M, Y') }}</p>
                    <p class="text-sm text-gray-500">{{ $contract->start_date->diffInDays($contract->end_date) }} days total</p>
                </div>
            </div>
            
            <!-- Signatures -->
            <div class="border-t pt-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">Signatures</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Vendor Signature</label>
                        @if($contract->signed_by_vendor)
                            <div class="flex items-center mt-1">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="text-gray-900">Signed on {{ $contract->vendor_signed_at->format('d M, Y H:i') }}</span>
                            </div>
                        @else
                            <div class="flex items-center mt-1">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <span class="text-gray-600">Pending Signature</span>
                            </div>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Admin Signature</label>
                        @if($contract->signed_by_admin)
                            <div class="flex items-center mt-1">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="text-gray-900">Signed on {{ $contract->admin_signed_at->format('d M, Y H:i') }}</span>
                            </div>
                        @else
                            <div class="flex items-center mt-1">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <span class="text-gray-600">Pending Signature</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Terms & Conditions -->
            @if($contract->terms_and_conditions)
            <div class="border-t pt-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">Terms & Conditions</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700">{{ $contract->terms_and_conditions }}</p>
                </div>
            </div>
            @endif
            
            <!-- Deliverables -->
            @if($contract->deliverables)
            <div class="border-t pt-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">Deliverables</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700">{{ $contract->deliverables }}</p>
                </div>
            </div>
            @endif
            
            <!-- Payment Terms -->
            @if($contract->payment_terms)
            <div class="border-t pt-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">Payment Terms</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-700">{{ $contract->payment_terms }}</p>
                </div>
            </div>
            @endif
            
            <!-- Extension History -->
            @if($contract->extensions && $contract->extensions->count() > 0)
            <div class="border-t pt-4">
                <h4 class="font-semibold text-gray-800 mb-3">Extension History</h4>
                <div class="space-y-2">
                    @foreach($contract->extensions as $extension)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">Extension #{{ $extension->extension_number }}</p>
                                <p class="text-sm text-gray-600">Extended from {{ \Carbon\Carbon::parse($extension->previous_end_date)->format('d M, Y') }} to {{ \Carbon\Carbon::parse($extension->new_end_date)->format('d M, Y') }}</p>
                                @if($extension->additional_value)
                                <p class="text-sm text-gray-600">Additional Value: ₹{{ number_format($extension->additional_value, 2) }}</p>
                                @endif
                            </div>
                            <div>
                                @if($extension->status == 'approved')
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Approved</span>
                                @endif
                            </div>
                        </div>
                        @if($extension->reason_for_extension)
                        <p class="text-sm text-gray-500 mt-1">Reason: {{ $extension->reason_for_extension }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Work Orders / Purchase Orders -->
            @if($hasItems || $orderDoc || $contract->order_type)
            <div class="border-t pt-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-file-invoice text-orange-500"></i>
                    Work Orders / Purchase Orders
                    @if($contract->is_partial_order)
                        <span class="text-xs font-normal px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full">Partial Order</span>
                    @endif
                </h4>
                <div class="space-y-2">
                    @if($hasItems)
                    <div class="flex items-center justify-between bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-file-pdf text-orange-500 text-lg"></i>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $orderLabel }} — {{ $contract->order_number ?? '—' }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $contract->order_date ? \Carbon\Carbon::parse($contract->order_date)->format('d M, Y') : '' }}
                                    @if($contract->order_generated_at)
                                        · Generated {{ $contract->order_generated_at->format('d M, Y') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('vendor.contracts.order-pdf', $contract->id) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                            <i class="fas fa-download text-xs"></i> Download PDF
                        </a>
                    </div>
                    @elseif($orderDoc)
                    <div class="flex items-center justify-between bg-orange-50 border border-orange-200 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-paperclip text-orange-500 text-lg"></i>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $orderLabel }} — Attachment</p>
                                <p class="text-xs text-gray-500">{{ $orderDoc['name'] ?? basename($orderDoc['path']) }}</p>
                            </div>
                        </div>
                        <a href="{{ Storage::disk('public')->url($orderDoc['path']) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                            <i class="fas fa-download text-xs"></i> Download
                        </a>
                    </div>
                    @else
                    <div class="text-sm text-gray-400 italic px-2 py-3">
                        <i class="fas fa-clock mr-1"></i> Work / Purchase Order not yet issued for this contract.
                    </div>
                    @endif

                    {{-- Additional uploaded supporting docs (non-order_document type) visible to vendor --}}
                    @php
                        $vendorDocs = collect($contract->supporting_documents ?? [])
                            ->filter(fn($d) => ($d['type'] ?? '') !== 'order_document')
                            ->values();
                    @endphp
                    @foreach($vendorDocs as $doc)
                    <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-file-alt text-gray-400 text-lg"></i>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $doc['label'] ?? ucfirst(str_replace('_', ' ', $doc['type'] ?? 'Document')) }}</p>
                                <p class="text-xs text-gray-500">{{ $doc['name'] ?? basename($doc['path']) }}</p>
                            </div>
                        </div>
                        <a href="{{ Storage::disk('public')->url($doc['path']) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition">
                            <i class="fas fa-download text-xs"></i> Download
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Related Partial Orders (sibling contracts) -->
            @if(isset($siblingContracts) && $siblingContracts->isNotEmpty())
            <div class="border-t pt-4 mb-6">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-layer-group text-amber-500"></i>
                    Related Orders (Partial Orders for this Tender)
                </h4>
                <div class="space-y-2">
                    @foreach($siblingContracts as $sib)
                    @php
                        $sibHasItems = !empty($sib->order_items);
                        $sibOrderDoc = collect($sib->supporting_documents ?? [])->firstWhere('type', 'order_document');
                        $sibLabel    = $sib->order_type === 'purchase_order' ? 'PO' : 'WO';
                    @endphp
                    <div class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-file-contract text-amber-500"></i>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm font-mono">{{ $sib->contract_number }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $sib->order_number ?? '—' }}
                                    @if($sib->order_type) · {{ $sibLabel }} @endif
                                    @if($sib->is_partial_order) · <span class="text-amber-600">Partial</span> @endif
                                    @if($sib->contract_value > 0) · ₹{{ number_format($sib->contract_value, 2) }} @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($sibHasItems)
                            <a href="{{ route('vendor.contracts.order-pdf', $sib->id) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition"
                               title="Download {{ $sibLabel }} PDF">
                                <i class="fas fa-file-pdf text-xs"></i> PDF
                            </a>
                            @elseif($sibOrderDoc)
                            <a href="{{ Storage::disk('public')->url($sibOrderDoc['path']) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition"
                               title="Download {{ $sibLabel }} Attachment">
                                <i class="fas fa-paperclip text-xs"></i> File
                            </a>
                            @endif
                            <a href="{{ route('vendor.contracts.show', $sib->id) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
                                <i class="fas fa-eye text-xs"></i> View
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="mt-6 pt-4 border-t flex justify-end space-x-3">
                @if($contract->status == 'draft' && !$contract->signed_by_vendor)
                <button onclick="signContract({{ $contract->id }})" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-signature mr-2"></i>Sign Contract
                </button>
                @endif
                <button onclick="window.print()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function signContract(id) {
    if (confirm('Are you sure you want to sign this contract?')) {
        fetch(`/vendor/contracts/${id}/sign`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Contract signed successfully!');
                  location.reload();
              } else {
                  alert('Failed to sign contract');
              }
          });
    }
}
</script>
@endsection