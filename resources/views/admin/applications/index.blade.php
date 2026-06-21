@extends('layouts.dashboard')

@section('title', 'Manage Applications')
@section('header', 'Application Management')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Applications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Submitted</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['submitted'] ?? 0 }}</p>
                </div>
                <i class="fas fa-clock text-3xl text-yellow-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Shortlisted</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['shortlisted'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Rejected</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['rejected'] ?? 0 }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Awarded</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['awarded'] ?? 0 }}</p>
                </div>
                <i class="fas fa-trophy text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-4">
        <form method="GET" action="" class="px-6 py-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-52">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Filter by Tender</label>
                <select name="tender_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400">
                    <option value="">All Tenders</option>
                    @foreach($tenders as $t)
                    <option value="{{ $t->id }}" {{ $tenderId == $t->id ? 'selected' : '' }}>
                        {{ $t->tender_number }} — {{ Str::limit($t->title, 55) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-36">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400">
                    <option value="">All Statuses</option>
                    <option value="submitted"   {{ $statusFilter === 'submitted'   ? 'selected' : '' }}>Submitted</option>
                    <option value="shortlisted" {{ $statusFilter === 'shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                    <option value="rejected"    {{ $statusFilter === 'rejected'    ? 'selected' : '' }}>Rejected</option>
                    <option value="awarded"     {{ $statusFilter === 'awarded'     ? 'selected' : '' }}>Awarded</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                <i class="fas fa-filter"></i> Filter
            </button>
            @if($tenderId || $statusFilter)
            <a href="{{ url()->current() }}" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition flex items-center gap-1">
                <i class="fas fa-times"></i> Clear
            </a>
            @endif
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>
                @if($tenderId && $tenders->count())
                    Applications for: {{ $tenders->firstWhere('id', $tenderId)?->title ?? 'Selected Tender' }}
                @else
                    All Applications
                @endif
                <span class="ml-2 text-sm font-normal opacity-75">({{ $applications->total() }} total)</span>
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
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">{{ ucfirst($app->status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $app->created_at->format('d M, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button type="button" onclick="viewApplication({{ $app->id }})" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($app->status == 'submitted')
                                <button type="button" onclick="shortlistApplication({{ $app->id }})" class="text-green-600 hover:text-green-800" title="Shortlist">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button type="button" onclick="showRejectModal({{ $app->id }})" class="text-red-600 hover:text-red-800" title="Reject">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                @endif
                                @if($app->status == 'shortlisted')
                                <button type="button" onclick="showAwardModal({{ $app->id }}, {{ $app->bid_amount }})" class="text-purple-600 hover:text-purple-800" title="Award">
                                    <i class="fas fa-trophy"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">No applications found</td>
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
<div id="viewAppModal" class="fixed inset-0 bg-black/50 items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Application Details</h3>
            <button onclick="closeAppModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="applicationDetails" class="p-6">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                <p class="mt-2 text-gray-500">Loading application details...</p>
            </div>
        </div>
    </div>
</div>

{{-- Award modal removed: clicking Award now navigates to the dedicated award page --}}

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const rolePrefix = window.location.pathname.startsWith('/super-admin/') ? 'super-admin' : 'admin';
let currentApplicationId = null;

function viewApplication(id) {
    console.log('View application called for ID:', id);
    
    const modal = document.getElementById('viewAppModal');
    const detailsDiv = document.getElementById('applicationDetails');
    
    // Show modal
    modal.style.display = 'flex';
    
    // Show loading
    detailsDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2 text-gray-500">Loading application details...</p></div>';
    
    // Fetch application details
    fetch(`/${rolePrefix}/applications/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => { 
        detailsDiv.innerHTML = html; 
        console.log('Application details loaded successfully');
    })
    .catch(error => {
        console.error('Error:', error);
        detailsDiv.innerHTML = '<div class="text-center py-8 text-red-500">Error loading application details. Please try again.</div>';
    });
}

function closeAppModal() {
    const modal = document.getElementById('viewAppModal');
    modal.style.display = 'none';
}

function shortlistApplication(id) {
    if (confirm('Are you sure you want to shortlist this application?')) {
        fetch(`/${rolePrefix}/applications/${id}/shortlist`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => { 
            if(data.success) {
                alert('Application shortlisted successfully!');
                location.reload();
            } else {
                alert('Failed to shortlist application: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error shortlisting application');
        });
    }
}

function showRejectModal(id) {
    const reason = prompt('Please enter rejection reason:');
    if (reason && reason.trim()) {
        fetch(`/${rolePrefix}/applications/${id}/reject`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => { 
            if(data.success) {
                alert('Application rejected successfully!');
                location.reload();
            } else {
                alert('Failed to reject application: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error rejecting application');
        });
    }
}

/* Award now navigates to the dedicated award page instead of opening a modal */
function showAwardModal(id) {
    const rolePrefix = window.location.pathname.startsWith('/super-admin/') ? 'super-admin' : 'admin';
    window.location.href = `/${rolePrefix}/applications/${id}/award`;
}

// Close modal when clicking outside
document.getElementById('viewAppModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAppModal();
    }
});

</script>
@endsection