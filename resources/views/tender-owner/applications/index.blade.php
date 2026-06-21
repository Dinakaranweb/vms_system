@extends('layouts.dashboard')

@section('title', 'Applications Management')
@section('header', 'Tender Applications')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Applications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Submitted</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['submitted'] }}</p>
                </div>
                <i class="fas fa-clock text-3xl text-yellow-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Shortlisted</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['shortlisted'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Rejected</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['rejected'] }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Awarded</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['awarded'] }}</p>
                </div>
                <i class="fas fa-trophy text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" action="{{ route('tender-owner.applications.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tender</label>
                <select name="tender_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="all">All Tenders</option>
                    @foreach($tenders as $tender)
                        <option value="{{ $tender->id }}" {{ request('tender_id') == $tender->id ? 'selected' : '' }}>
                            {{ $tender->title }} ({{ $tender->tender_number }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="all">All Status</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="shortlisted" {{ request('status') == 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="awarded" {{ request('status') == 'awarded' ? 'selected' : '' }}>Awarded</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <a href="{{ route('tender-owner.applications.index') }}" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-undo mr-2"></i>Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Applications Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>Applications for Your Tenders
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">App. No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bid Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($applications as $app)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $app->application_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($app->tender->title ?? 'N/A', 30) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $app->vendor->company_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600">₹{{ number_format($app->bid_amount, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($app->status == 'submitted')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Submitted</span>
                            @elseif($app->status == 'shortlisted')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Shortlisted</span>
                            @elseif($app->status == 'rejected')
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Rejected</span>
                            @elseif($app->status == 'awarded')
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">Awarded</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $app->created_at->format('d M, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewApplication({{ $app->id }})" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($app->status == 'submitted')
                                <button onclick="shortlistApplication({{ $app->id }})" class="text-green-600 hover:text-green-800" title="Shortlist">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button onclick="showRejectModal({{ $app->id }})" class="text-red-600 hover:text-red-800" title="Reject">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                @endif
                                @if($app->status == 'shortlisted')
                                <a href="{{ route('tender-owner.applications.award-form', $app->id) }}" class="text-purple-600 hover:text-purple-800" title="Award & Generate Contract">
                                    <i class="fas fa-trophy"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No applications found for your tenders.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $applications->links() }}
        </div>
    </div>
</div>

<!-- View Application Modal -->
<div id="viewAppModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Application Details</h3>
            <button onclick="closeAppModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="applicationDetails" class="p-6">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-purple-600"></i>
                <p class="mt-2">Loading application details...</p>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let currentApplicationId = null;

function viewApplication(id) {
    const modal = document.getElementById('viewAppModal');
    const detailsDiv = document.getElementById('applicationDetails');
    modal.style.display = 'flex';
    detailsDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl"></i><p>Loading...</p></div>';
    
    fetch(`/tender-owner/applications/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.text())
    .then(html => { detailsDiv.innerHTML = html; });
}

function closeAppModal() {
    document.getElementById('viewAppModal').style.display = 'none';
}

function shortlistApplication(id) {
    if (confirm('Shortlist this application?')) {
        fetch(`/tender-owner/applications/${id}/shortlist`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        }).then(response => response.json())
          .then(data => { if(data.success) location.reload(); });
    }
}

function showRejectModal(id) {
    const reason = prompt('Please enter rejection reason:');
    if (reason && reason.trim()) {
        fetch(`/tender-owner/applications/${id}/reject`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ reason: reason })
        }).then(response => response.json())
          .then(data => { if(data.success) location.reload(); });
    }
}

</script>
@endsection