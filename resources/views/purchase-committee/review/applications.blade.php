@extends('layouts.dashboard')

@section('title', 'Applications — ' . $tender->title)
@section('header', 'Applications for Review')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <a href="{{ route('purchase-committee.tenders.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Back to All Tenders
        </a>
        <a href="{{ route('purchase-committee.review.compare-bids', $tender->id) }}" class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1.5">
            <i class="fas fa-scale-balanced text-xs"></i> Compare Bids
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <h3 class="text-white font-semibold">{{ $tender->title }}</h3>
            <p class="text-indigo-100 text-xs mt-0.5">{{ $tender->tender_number }} · Opened for review {{ $tender->review_opened_at->format('d M Y, h:i A') }}</p>
        </div>

        @if($applications->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-file-alt text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No applications received for this tender.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($applications as $app)
            <a href="{{ route('purchase-committee.review.application-show', [$tender->id, $app->id]) }}" class="block px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900">{{ $app->vendor->company_name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $app->application_number }}</p>
                    </div>
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <div class="text-right">
                            <p class="text-lg font-bold text-green-600">₹{{ number_format($app->bid_amount ?? 0, 2) }}</p>
                            <p class="text-xs text-gray-400">bid amount</p>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                            {{ $app->status === 'shortlisted' ? 'bg-blue-100 text-blue-700' :
                              ($app->status === 'awarded' ? 'bg-green-100 text-green-700' :
                              ($app->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700')) }}">
                            {{ ucfirst($app->status) }}
                        </span>
                        @if($app->reviewComments->count() > 0)
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">
                            <i class="fas fa-comments mr-1"></i>{{ $app->reviewComments->count() }}
                        </span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
