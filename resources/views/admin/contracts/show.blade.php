@extends('layouts.dashboard')

@section('title', 'Contract Details')
@section('header', 'Contract Details')

@section('content')
@php
    $rp         = request()->is('super-admin/*') ? 'super-admin' : 'admin';
    $hasItems   = !empty($contract->order_items);
    $orderDoc   = collect($contract->supporting_documents ?? [])->firstWhere('type', 'order_document');
    $orderLabel = $contract->order_type === 'purchase_order' ? 'Purchase Order' : 'Work Order';
    $showValue  = $contract->contract_value > 0;
    // All contracts (including partial orders) for same application
    $siblingContracts = $contract->application_id
        ? \App\Models\Contract::where('application_id', $contract->application_id)
              ->where('id', '!=', $contract->id)
              ->orderBy('created_at')
              ->get()
        : collect();
@endphp

<div class="max-w-5xl mx-auto space-y-4">

    {{-- Flash --}}
    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
        <i class="fas fa-check-circle text-green-600 text-xl flex-shrink-0"></i>
        <p class="text-green-800 font-medium">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Header card --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-700">
            <div class="flex flex-wrap justify-between items-center gap-3">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-file-contract"></i>
                        {{ $contract->contract_number }}
                    </h3>
                    <p class="text-blue-100 text-sm mt-0.5">{{ $contract->tender->title ?? '' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($contract->order_type)
                        @if($hasItems)
                            <a href="{{ route("{$rp}.contracts.order-pdf", $contract->id) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-sm rounded-lg transition">
                                <i class="fas fa-file-pdf text-xs"></i> {{ $orderLabel }}
                            </a>
                        @elseif($orderDoc)
                            <a href="{{ Storage::disk('public')->url($orderDoc['path']) }}" target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-sm rounded-lg transition">
                                <i class="fas fa-paperclip text-xs"></i> {{ $orderLabel }}
                            </a>
                        @endif
                        @if(!$hasItems)
                            <a href="{{ route("{$rp}.contracts.work-order", $contract->id) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-sm rounded-lg transition">
                                <i class="fas fa-edit text-xs"></i> Edit Order
                            </a>
                        @endif
                    @endif
                    @if($contract->application_id)
                    <a href="{{ route("{$rp}.applications.award-form", $contract->application_id) }}?partial_order=1"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500/80 hover:bg-amber-500 text-white text-sm rounded-lg transition">
                        <i class="fas fa-layer-group text-xs"></i> Add Partial Order
                    </a>
                    @endif
                    <a href="{{ route("{$rp}.contracts") }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-sm rounded-lg transition">
                        <i class="fas fa-arrow-left text-xs"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Basic info grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Contract Number</label>
                    <p class="font-mono font-semibold text-gray-900 mt-0.5">{{ $contract->contract_number }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Status</label>
                    <p class="mt-0.5">
                        @if($contract->status == 'active')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Active</span>
                        @elseif($contract->status == 'expired')
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Expired</span>
                        @elseif($contract->status == 'terminated')
                            <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-semibold">Terminated</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">{{ ucfirst($contract->status) }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 uppercase">Tender</label>
                    <p class="text-gray-900 font-medium mt-0.5">{{ $contract->tender->title ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ $contract->tender->tender_number ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 uppercase">Vendor</label>
                    <p class="text-gray-900 font-medium mt-0.5">{{ $contract->vendor->company_name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ $contract->vendor->vendor_code ?? 'N/A' }}</p>
                </div>
                @if($showValue)
                <div>
                    <label class="text-xs text-gray-500 uppercase">Contract Value</label>
                    <p class="text-2xl font-bold text-green-600 mt-0.5">₹{{ number_format($contract->contract_value, 2) }}</p>
                </div>
                @endif
                <div>
                    <label class="text-xs text-gray-500 uppercase">Duration</label>
                    <p class="text-gray-900 mt-0.5">{{ $contract->start_date->format('d M, Y') }} – {{ $contract->end_date->format('d M, Y') }}</p>
                    <p class="text-sm text-gray-500">{{ $contract->start_date->diffInDays($contract->end_date) }} days</p>
                </div>
            </div>

            {{-- Supporting Documents --}}
            <div class="pt-4 border-t">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-paperclip text-blue-500"></i> Supporting Documents
                    </h4>
                    <button type="button" onclick="document.getElementById('sdUploadBox').classList.toggle('hidden')"
                        class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                        <i class="fas fa-upload text-xs"></i> Upload
                    </button>
                </div>

                <div id="sdUploadBox" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <form id="sdUploadForm" enctype="multipart/form-data">
                        @csrf
                        <label class="block text-xs font-semibold text-gray-600 mb-2">
                            Select Files <span class="font-normal text-gray-400">(PDF, DOC, DOCX, JPG, PNG, XLSX — max 20 MB each)</span>
                        </label>
                        <input type="file" name="supporting_docs[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white mb-3">
                        <div class="flex gap-2">
                            <button type="button" onclick="uploadSupportingDocs({{ $contract->id }})"
                                class="px-4 py-2 bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-800 transition flex items-center gap-1">
                                <i class="fas fa-upload text-xs"></i> Upload
                            </button>
                            <button type="button" onclick="document.getElementById('sdUploadBox').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                    <div id="sdUploadMsg" class="mt-2 text-sm hidden"></div>
                </div>

                <div id="sdDocList">
                    @if(!empty($contract->supporting_documents))
                        @foreach($contract->supporting_documents as $idx => $doc)
                        <div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0" id="sdDoc_{{ $idx }}">
                            <i class="fas fa-file text-blue-400 text-sm flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $doc['original_name'] }}</p>
                                <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($doc['uploaded_at'])->format('d M Y, H:i') }}</p>
                            </div>
                            <a href="{{ Storage::disk('public')->url($doc['path']) }}" target="_blank"
                               class="flex-shrink-0 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs rounded-lg hover:bg-gray-200 transition flex items-center gap-1">
                                <i class="fas fa-download text-xs"></i> Download
                            </a>
                            <button type="button" onclick="deleteSupportingDoc({{ $contract->id }}, {{ $idx }})"
                                class="flex-shrink-0 px-2 py-1.5 text-red-500 hover:text-red-700 text-xs transition" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-400 italic" id="sdEmptyMsg">No supporting documents uploaded yet.</p>
                    @endif
                </div>
            </div>

            @if($contract->payment_terms)
            <div class="pt-4 border-t">
                <label class="text-xs text-gray-500 uppercase">Payment Terms</label>
                <p class="text-gray-700 mt-1">{{ $contract->payment_terms }}</p>
            </div>
            @endif

            @if($contract->deliverables)
            <div class="pt-4 border-t">
                <label class="text-xs text-gray-500 uppercase">Deliverables</label>
                <p class="text-gray-700 mt-1">{{ $contract->deliverables }}</p>
            </div>
            @endif

            @if($contract->terms_and_conditions)
            <div class="pt-4 border-t">
                <label class="text-xs text-gray-500 uppercase">Terms &amp; Conditions</label>
                <p class="text-gray-700 mt-1">{{ $contract->terms_and_conditions }}</p>
            </div>
            @endif

            @if($contract->termination_reason)
            <div class="pt-4 border-t">
                <div class="bg-red-50 rounded-lg p-3">
                    <label class="text-xs text-red-600 uppercase font-semibold">Termination Reason</label>
                    <p class="text-red-700 mt-1">{{ $contract->termination_reason }}</p>
                </div>
            </div>
            @endif

            {{-- Signatures --}}
            @if($contract->tender->require_contract_signing ?? true)
            <div class="pt-4 border-t">
                <h4 class="font-semibold text-gray-800 mb-3">Signatures</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Admin Signature</label>
                        @if($contract->signed_by_admin)
                            <div class="flex items-center mt-1">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="text-sm">Signed on {{ $contract->admin_signed_at->format('d M, Y H:i') }}</span>
                            </div>
                        @else
                            <div class="flex items-center mt-1">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <span class="text-sm text-gray-500">Pending</span>
                            </div>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Vendor Signature</label>
                        @if($contract->signed_by_vendor)
                            <div class="flex items-center mt-1">
                                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                <span class="text-sm">Signed on {{ $contract->vendor_signed_at->format('d M, Y H:i') }}</span>
                            </div>
                        @else
                            <div class="flex items-center mt-1">
                                <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                <span class="text-sm text-gray-500">Pending</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Work Order / Purchase Order --}}
            @if($contract->order_number)
            <div class="pt-4 border-t">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-clipboard-list text-orange-500"></i> {{ $orderLabel }}
                </h4>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-orange-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Order Number</label>
                        <p class="font-mono font-semibold text-gray-900 mt-0.5">{{ $contract->order_number }}</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Order Date</label>
                        <p class="text-gray-900 mt-0.5">{{ $contract->order_date ? $contract->order_date->format('d M, Y') : '—' }}</p>
                    </div>
                    @if($contract->vmrfdu_ref)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">VMRFDU Ref.</label>
                        <p class="text-gray-900 mt-0.5">{{ $contract->vmrfdu_ref }}</p>
                    </div>
                    @endif
                    @if($contract->supplier_ref)
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-500 uppercase">Supplier Ref.</label>
                        <p class="text-gray-900 mt-0.5">{{ $contract->supplier_ref }}</p>
                    </div>
                    @endif
                </div>
                <div class="flex gap-3">
                    @if($hasItems)
                        <a href="{{ route("{$rp}.contracts.order-pdf", $contract->id) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm rounded-lg hover:bg-orange-700 transition">
                            <i class="fas fa-file-pdf"></i> Download {{ $orderLabel }} PDF
                        </a>
                        <a href="{{ route("{$rp}.contracts.work-order", $contract->id) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-edit"></i> Edit Order
                        </a>
                    @elseif($orderDoc)
                        <a href="{{ Storage::disk('public')->url($orderDoc['path']) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white text-sm rounded-lg hover:bg-orange-700 transition">
                            <i class="fas fa-paperclip"></i> View Order Attachment
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Extension History --}}
            @if($contract->extensions && $contract->extensions->count() > 0)
            <div class="pt-4 border-t">
                <h4 class="font-semibold text-gray-800 mb-3">Extension History</h4>
                @foreach($contract->extensions as $extension)
                <div class="bg-gray-50 rounded-lg p-3 mb-2">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium">Extension #{{ $extension->extension_number }}</p>
                            <p class="text-sm">New End Date: {{ \Carbon\Carbon::parse($extension->new_end_date)->format('d M, Y') }}</p>
                            @if($extension->additional_value)
                            <p class="text-sm">Additional Value: ₹{{ number_format($extension->additional_value, 2) }}</p>
                            @endif
                            <p class="text-sm text-gray-600">Reason: {{ $extension->reason_for_extension }}</p>
                        </div>
                        <div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Approved</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </div>

        {{-- Partial / Sibling Orders --}}
        @if($siblingContracts->isNotEmpty() || $contract->is_partial_order)
        <div class="pt-4 border-t">
            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-layer-group text-amber-500"></i>
                Related Orders (Partial Orders for this Tender)
            </h4>
            @if($contract->is_partial_order)
            <div class="mb-2 flex items-center gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                <i class="fas fa-info-circle"></i>
                This contract is a <strong>Partial Order</strong>.
            </div>
            @endif
            @if($siblingContracts->isNotEmpty())
            <div class="space-y-2">
                @foreach($siblingContracts as $sib)
                <a href="{{ route("{$rp}.contracts.show", $sib->id) }}"
                   class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg hover:bg-amber-50 hover:border-amber-300 transition">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file-contract text-amber-500"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800 font-mono">{{ $sib->contract_number }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $sib->order_number ?? '—' }}
                                @if($sib->order_type) · {{ $sib->order_type === 'purchase_order' ? 'PO' : 'WO' }} @endif
                                @if($sib->is_partial_order) · <span class="text-amber-600">Partial</span> @endif
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        @if($sib->contract_value > 0)
                        <p class="text-sm font-bold text-green-600">₹{{ number_format($sib->contract_value, 2) }}</p>
                        @endif
                        <p class="text-xs text-gray-400">{{ $sib->end_date?->format('d M Y') ?? '—' }}</p>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function uploadSupportingDocs(contractId) {
    const form = document.getElementById('sdUploadForm');
    const msgEl = document.getElementById('sdUploadMsg');
    const data = new FormData(form);
    msgEl.className = 'mt-2 text-sm text-blue-600';
    msgEl.textContent = 'Uploading...';
    msgEl.classList.remove('hidden');
    fetch(`/{{ $rp === 'super-admin' ? 'super-admin' : 'admin' }}/contracts/${contractId}/supporting-docs`, {
        method: 'POST',
        body: data,
        headers: { 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(data => {
        if (data.success) { location.reload(); }
        else { msgEl.className = 'mt-2 text-sm text-red-600'; msgEl.textContent = data.message || 'Upload failed.'; }
    }).catch(() => { msgEl.className = 'mt-2 text-sm text-red-600'; msgEl.textContent = 'Upload failed.'; });
}

function deleteSupportingDoc(contractId, idx) {
    if (!confirm('Delete this document?')) return;
    fetch(`/{{ $rp === 'super-admin' ? 'super-admin' : 'admin' }}/contracts/${contractId}/supporting-docs`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ doc_index: idx })
    }).then(r => r.json()).then(data => {
        if (data.success) { document.getElementById('sdDoc_' + idx)?.remove(); }
        else { alert(data.message || 'Delete failed.'); }
    });
}
</script>
@endpush
@endsection
