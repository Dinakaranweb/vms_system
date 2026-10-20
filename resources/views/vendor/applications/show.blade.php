@extends('layouts.dashboard')

@section('title', 'Application #' . $application->application_number)
@section('header', 'Application Details')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
@media print {
    .no-print, .nav-sidebar, header, .topbar { display:none !important; }
    body { font-size:12px; }
    .shadow-sm { box-shadow:none !important; }
    .rounded-xl { border-radius:0 !important; }
}
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-file-alt mr-2"></i>Application #{{ $application->application_number }}
                </h3>
                <div class="flex items-center gap-3">
                    <button onclick="window.print()"
                            class="no-print inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-lg transition">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="{{ route('vendor.applications') }}" class="text-white hover:text-gray-200 text-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Status</label>
                    <p>
                        @if($application->status == 'submitted')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Submitted</span>
                        @elseif($application->status == 'shortlisted')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Shortlisted</span>
                        @elseif($application->status == 'rejected')
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Rejected</span>
                        @elseif($application->status == 'awarded')
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">Awarded</span>
                        @elseif($application->status == 'withdrawn')
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Withdrawn</span>
                        @endif
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Submitted On</label>
                    <p class="text-gray-900">{{ $application->created_at->format('d M, Y H:i') }}</p>
                </div>
                <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Tender</label>
                    <p class="font-semibold text-gray-900">{{ $application->tender->title ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">Tender #: {{ $application->tender->tender_number ?? 'N/A' }}</p>
                </div>
                @if(!($application->tender && $application->tender->isPostal()))
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Bid Amount</label>
                    <p class="text-2xl font-bold text-green-600">
                        @if($application->bid_amount !== null && $application->bid_amount > 0)₹{{ number_format($application->bid_amount, 2) }}@else<span class="text-gray-400 text-sm font-normal">N/A</span>@endif
                    </p>
                </div>
                @endif
                @if($application->rejection_reason)
                <div class="col-span-2 bg-red-50 rounded-lg p-3">
                    <label class="text-xs text-red-600 uppercase">Rejection Reason</label>
                    <p class="text-red-600">{{ $application->rejection_reason }}</p>
                </div>
                @endif
            </div>
            
            @include('partials.application-proposals')

            @include('partials.application-query-thread')
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
@endpush
@endsection