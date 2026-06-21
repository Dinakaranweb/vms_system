@extends('layouts.dashboard')

@section('title', 'Manage Contracts')
@section('header', 'Contract Management')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Contracts</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <i class="fas fa-file-contract text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Active</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['active'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Expired</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['expired'] ?? 0 }}</p>
                </div>
                <i class="fas fa-clock text-3xl text-red-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Terminated</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['terminated'] ?? 0 }}</p>
                </div>
                <i class="fas fa-ban text-3xl text-orange-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Value</p>
                    <p class="text-2xl font-bold text-gray-800">₹{{ number_format($stats['total_value'] ?? 0, 2) }}</p>
                </div>
                <i class="fas fa-rupee-sign text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>All Contracts
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contract No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($contracts as $contract)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $contract->contract_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($contract->tender->title ?? 'N/A', 30) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $contract->vendor->company_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600">₹{{ number_format($contract->contract_value, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $contract->start_date->format('d M, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $contract->end_date->format('d M, Y') }}
                            @if($contract->status == 'active')
                                <span class="text-xs text-{{ $contract->end_date->diffInDays(now()) <= 30 ? 'red' : 'green' }}-600">
                                    ({{ $contract->end_date->diffInDays(now()) }} days left)
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($contract->status == 'active')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                            @elseif($contract->status == 'expired')
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Expired</span>
                            @elseif($contract->status == 'terminated')
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">Terminated</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">{{ ucfirst($contract->status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2 items-center">
                                <button onclick="viewContract({{ $contract->id }})" class="text-blue-600 hover:text-blue-800" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @php
                                    $rp       = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin';
                                    $hasItems = !empty($contract->order_items);
                                    $orderDoc = collect($contract->supporting_documents ?? [])->firstWhere('type', 'order_document');
                                @endphp
                                @if($hasItems)
                                    <a href="{{ route("{$rp}.contracts.order-pdf", $contract->id) }}"
                                       target="_blank"
                                       class="text-orange-500 hover:text-orange-700"
                                       title="Download {{ $contract->order_type === 'purchase_order' ? 'PO' : 'WO' }} PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @elseif($orderDoc)
                                    <a href="{{ Storage::disk('public')->url($orderDoc['path']) }}"
                                       target="_blank"
                                       class="text-orange-500 hover:text-orange-700"
                                       title="View Order Attachment">
                                        <i class="fas fa-paperclip"></i>
                                    </a>
                                @else
                                    <a href="{{ route("{$rp}.contracts.work-order", $contract->id) }}"
                                       class="text-orange-500 hover:text-orange-700"
                                       title="Generate Work/Purchase Order">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                @endif
                                @if($contract->status == 'draft' && !$contract->signed_by_admin && ($contract->tender->require_contract_signing ?? true))
                                <button onclick="signContract({{ $contract->id }})" class="text-green-600 hover:text-green-800" title="Sign Contract">
                                    <i class="fas fa-signature"></i>
                                </button>
                                @endif
                                @if($contract->status == 'active')
                                <button onclick="showExtendModal({{ $contract->id }})" class="text-purple-600 hover:text-purple-800" title="Extend">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                <button onclick="showTerminateModal({{ $contract->id }})" class="text-red-600 hover:text-red-800" title="Terminate">
                                    <i class="fas fa-ban"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">No contracts found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $contracts->links() }}
        </div>
    </div>
</div>

<!-- View Contract Modal -->
<div id="viewContractModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Contract Details</h3>
            <button onclick="closeContractModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="contractDetails" class="p-6">Loading...</div>
    </div>
</div>

<!-- Extend Contract Modal -->
<div id="extendModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white">Extend Contract</h3>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">New End Date *</label>
                <input type="date" id="newEndDate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Value (₹)</label>
                <input type="number" id="additionalValue" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Extension *</label>
                <textarea id="extensionReason" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeExtendModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button onclick="confirmExtend()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Extend Contract</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let currentContractId = null;

function viewContract(id) {
    const modal = document.getElementById('viewContractModal');
    const detailsDiv = document.getElementById('contractDetails');
    modal.style.display = 'flex';
    detailsDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl"></i><p>Loading...</p></div>';
    
    fetch(`/admin/contracts/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.text())
    .then(html => { detailsDiv.innerHTML = html; });
}

function closeContractModal() {
    document.getElementById('viewContractModal').style.display = 'none';
}

function signContract(id) {
    if (confirm('Sign this contract?')) {
        fetch(`/admin/contracts/${id}/sign`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(() => location.reload());
    }
}

function showExtendModal(id) {
    currentContractId = id;
    document.getElementById('extendModal').style.display = 'flex';
}

function closeExtendModal() {
    document.getElementById('extendModal').style.display = 'none';
    currentContractId = null;
}

function confirmExtend() {
    const data = {
        new_end_date: document.getElementById('newEndDate').value,
        additional_value: document.getElementById('additionalValue').value,
        reason: document.getElementById('extensionReason').value
    };
    
    if (!data.new_end_date || !data.reason) {
        alert('Please fill all required fields');
        return;
    }
    
    fetch(`/admin/contracts/${currentContractId}/extend`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(data)
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              alert('Contract extended successfully!');
              location.reload();
          } else {
              alert('Failed: ' + data.message);
          }
      });
}

function showTerminateModal(id) {
    const reason = prompt('Reason for termination:');
    if (reason) {
        fetch(`/admin/contracts/${id}/terminate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ reason: reason })
        }).then(() => location.reload());
    }
}

function uploadSupportingDocs(contractId) {
    const rolePrefix = window.location.pathname.startsWith('/super-admin/') ? 'super-admin' : 'admin';
    const form = document.getElementById('sdUploadForm');
    const formData = new FormData(form);
    const msg = document.getElementById('sdUploadMsg');

    msg.className = 'mt-2 text-sm text-blue-600';
    msg.textContent = 'Uploading…';
    msg.classList.remove('hidden');

    fetch(`/${rolePrefix}/contracts/${contractId}/supporting-docs`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msg.className = 'mt-2 text-sm text-green-700';
            msg.textContent = data.message;
            // Reload contract details to refresh the list
            viewContract(contractId);
        } else {
            msg.className = 'mt-2 text-sm text-red-600';
            msg.textContent = 'Error: ' + data.message;
        }
    })
    .catch(() => {
        msg.className = 'mt-2 text-sm text-red-600';
        msg.textContent = 'Upload failed. Please try again.';
    });
}

function deleteSupportingDoc(contractId, index) {
    if (!confirm('Delete this document?')) return;
    const rolePrefix = window.location.pathname.startsWith('/super-admin/') ? 'super-admin' : 'admin';
    fetch(`/${rolePrefix}/contracts/${contractId}/supporting-docs`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ index: index }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { viewContract(contractId); }
        else { alert('Delete failed: ' + data.message); }
    });
}
</script>
@endsection