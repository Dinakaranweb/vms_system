@extends('layouts.dashboard')

@section('title', 'My Contracts')
@section('header', 'My Contracts')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Contracts</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $contracts->total() }}</p>
                </div>
                <i class="fas fa-file-contract text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Active Contracts</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $contracts->where('status', 'active')->count() }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Value</p>
                    <p class="text-2xl font-bold text-gray-800">₹{{ number_format($contracts->sum('contract_value'), 2) }}</p>
                </div>
                <i class="fas fa-rupee-sign text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
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
                        <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($contract->tender->title ?? 'N/A', 40) }}</td>
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
                                <a href="{{ route('vendor.contracts.show', $contract->id) }}" class="text-blue-600 hover:text-blue-800" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @php
                                    $hasOrderItems = !empty($contract->order_items);
                                    $orderAttach   = collect($contract->supporting_documents ?? [])->firstWhere('type', 'order_document');
                                @endphp
                                @if($hasOrderItems)
                                <a href="{{ route('vendor.contracts.order-pdf', $contract->id) }}" target="_blank"
                                   class="text-orange-500 hover:text-orange-700"
                                   title="Download {{ $contract->order_type === 'purchase_order' ? 'Purchase Order' : 'Work Order' }} PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @elseif($orderAttach)
                                <a href="{{ Storage::disk('public')->url($orderAttach['path']) }}" target="_blank"
                                   class="text-orange-500 hover:text-orange-700"
                                   title="Download Order Attachment">
                                    <i class="fas fa-paperclip"></i>
                                </a>
                                @endif
                                @if($contract->status == 'draft' && !$contract->signed_by_vendor)
                                <button onclick="signContract({{ $contract->id }})" class="text-green-600 hover:text-green-800" title="Sign Contract">
                                    <i class="fas fa-signature"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-file-contract text-4xl mb-2"></i>
                            <p>No contracts found</p>
                            <a href="{{ route('vendor.tenders') }}" class="text-blue-600 hover:underline mt-2 inline-block">Browse Tenders</a>
                        </td>
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