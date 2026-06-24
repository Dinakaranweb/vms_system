@extends('layouts.dashboard')

@section('title', 'Application Details')
@section('header', 'Application Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-file-alt mr-2"></i>Application #{{ $application->application_number }}
                </h3>
                <a href="{{ route('vendor.applications') }}" class="text-white hover:text-gray-200">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
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
            
            <div class="border-t pt-4">
                <h4 class="font-semibold text-gray-800 mb-3">Proposals</h4>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Technical Proposal</label>
                        <p class="text-gray-600 mt-1">{{ $application->technical_proposal ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Financial Proposal</label>
                        <p class="text-gray-600 mt-1">{{ $application->financial_proposal ?? 'Not provided' }}</p>
                    </div>
                </div>
            </div>
            
            @if($application->documents && count($application->documents) > 0)
            <div class="border-t pt-4 mt-4">
                <h4 class="font-semibold text-gray-800 mb-3">Supporting Documents</h4>
                <div class="space-y-2">
                    @foreach($application->documents as $index => $doc)
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-file-pdf text-red-500"></i>
                            <span class="text-sm text-gray-700">{{ $doc['name'] ?? 'Document ' . ($index + 1) }}</span>
                        </div>
                        <span class="text-xs text-gray-500">{{ number_format(($doc['size'] ?? 0) / 1024, 2) }} KB</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection