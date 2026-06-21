@extends('layouts.dashboard')

@section('title', 'Open Tenders')
@section('header', 'Available Tenders')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Open Tenders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $tenders->total() }}</p>
                </div>
                <i class="fas fa-gavel text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Deadline ≤ 7 Days</p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ $tenders->filter(function($t) { return $t->submission_deadline->diffInDays(now()) <= 7; })->count() }}
                    </p>
                </div>
                <i class="fas fa-hourglass-half text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">My Applications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ Auth::user()->vendor->applications()->count() }}</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-purple-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">My Contracts</p>
                    <p class="text-2xl font-bold text-gray-800">{{ Auth::user()->vendor->contracts()->where('status', 'active')->count() }}</p>
                </div>
                <i class="fas fa-file-contract text-3xl text-orange-500"></i>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[250px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Tenders</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="searchTenders" placeholder="Search by title, tender number..." 
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select id="sortBy" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="deadline_asc">Deadline (Earliest First)</option>
                    <option value="deadline_desc">Deadline (Latest First)</option>
                    <option value="value_desc">Highest Value</option>
                    <option value="value_asc">Lowest Value</option>
                    <option value="newest">Newest First</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="searchBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <button id="resetBtn" class="ml-2 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-undo mr-2"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Tenders List -->
    <div class="space-y-4">
        @forelse($tenders as $tender)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <!-- Tender Header -->
                        <div class="flex items-center space-x-3 mb-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-xs font-mono">
                                {{ $tender->tender_number }}
                            </span>
                            @if($tender->tender_type == 'online')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                    <i class="fas fa-globe mr-1"></i>Online
                                </span>
                            @else
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">
                                    <i class="fas fa-envelope mr-1"></i>Postal
                                </span>
                            @endif
                            @if($tender->submission_deadline->diffInDays(now()) <= 7)
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Deadline Approaching
                                </span>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $tender->title }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ Str::limit($tender->description, 150) }}</p>
                        
                        <!-- Tender Details Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-rupee-sign text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Est. Value</p>
                                    <p class="font-semibold text-gray-900">₹{{ number_format($tender->estimated_value ?? 0, 2) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Deadline</p>
                                    <p class="font-semibold text-gray-900">{{ $tender->submission_deadline->format('d M, Y') }}</p>
                                    <p class="text-xs {{ $tender->submission_deadline->diffInDays(now()) <= 7 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $tender->submission_deadline->diffInDays(now()) }} days left
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar-check text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Opening Date</p>
                                    <p class="font-semibold text-gray-900">{{ $tender->opening_date->format('d M, Y') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-alt text-gray-400"></i>
                                <div>
                                    <p class="text-xs text-gray-500">Documents</p>
                                    <p class="font-semibold text-gray-900">{{ $tender->documents->count() }} files</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Eligibility Criteria Preview -->
                        @if($tender->eligibility_criteria)
                        <div class="bg-gray-50 rounded-lg p-3 mb-4">
                            <p class="text-sm text-gray-600 line-clamp-2">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                {{ Str::limit($tender->eligibility_criteria, 100) }}
                            </p>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="ml-6 flex flex-col space-y-2">
                        <a href="{{ route('vendor.tenders.show', $tender->id) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                            <i class="fas fa-eye mr-2"></i>View Details
                        </a>
                        @php
                            $hasApplied = Auth::user()->vendor->hasAppliedForTender($tender);
                        @endphp
                        @if(!$hasApplied)
                            <a href="{{ route('vendor.tenders.apply', $tender->id) }}" 
                               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-center">
                                <i class="fas fa-paper-plane mr-2"></i>Apply Now
                            </a>
                        @else
                            <button disabled 
                                    class="px-4 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed">
                                <i class="fas fa-check-circle mr-2"></i>Applied
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Open Tenders</h3>
            <p class="text-gray-500">There are no open tenders available at the moment. Please check back later.</p>
        </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        {{ $tenders->links() }}
    </div>
</div>

@push('scripts')
<script>
// Search functionality
document.getElementById('searchBtn')?.addEventListener('click', function() {
    const searchTerm = document.getElementById('searchTenders').value;
    const sortBy = document.getElementById('sortBy').value;
    
    let url = new URL(window.location.href);
    if (searchTerm) url.searchParams.set('search', searchTerm);
    else url.searchParams.delete('search');
    if (sortBy) url.searchParams.set('sort', sortBy);
    else url.searchParams.delete('sort');
    
    window.location.href = url.toString();
});

document.getElementById('resetBtn')?.addEventListener('click', function() {
    window.location.href = window.location.pathname;
});

// Enter key search
document.getElementById('searchTenders')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
    }
});
</script>
@endpush

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection