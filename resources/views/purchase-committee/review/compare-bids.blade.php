@extends('layouts.dashboard')

@section('title', 'Compare Bids — ' . $tender->title)
@section('header', 'Bid Comparison')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <a href="{{ route('purchase-committee.review.applications', $tender->id) }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Applications
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <h3 class="text-white font-semibold">{{ $tender->title }}</h3>
            <p class="text-indigo-100 text-xs mt-0.5">
                {{ $tender->tender_number }} · {{ ucfirst($tender->tender_type) }} tender
                · {{ $applications->count() }} bid(s) entered
            </p>
        </div>

        @if(($awaitingBid ?? collect())->isNotEmpty())
        <div class="px-6 py-3 bg-amber-50 border-b border-amber-100">
            <p class="text-xs text-amber-900">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                <strong>{{ $awaitingBid->count() }} bidder(s) are missing from this comparison</strong> — no bid value has been
                entered for them yet:
                {{ $awaitingBid->map(fn($a) => $a->vendor->company_name ?? $a->application_number)->implode(', ') }}.
                @if($tender->hasPostalBids())
                <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}" class="underline font-medium">Enter the bid values</a>
                from their quotes before treating this ranking as final.
                @endif
            </p>
        </div>
        @endif

        @if($applications->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-scale-balanced text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No bid amounts have been entered for this tender yet.</p>
            @if($tender->hasPostalBids())
            <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}"
               class="inline-block mt-3 text-sm bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
                <i class="fas fa-indian-rupee-sign mr-1.5"></i>Enter Bid Values
            </a>
            @endif
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-3 text-left">Rank</th>
                        <th class="px-6 py-3 text-left">Vendor</th>
                        <th class="px-6 py-3 text-right">Bid Amount (₹)</th>
                        <th class="px-6 py-3 text-center">Docs</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($applications as $i => $app)
                    <tr class="{{ $i === 0 ? 'bg-green-50/60' : '' }}">
                        <td class="px-6 py-4">
                            <span class="w-6 h-6 inline-flex items-center justify-center rounded-full text-xs font-bold
                                {{ $i === 0 ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-500' }}">
                                {{ $i + 1 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-900">{{ $app->vendor->company_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $app->application_number }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold {{ $i === 0 ? 'text-green-600' : 'text-gray-800' }}">
                                {{ number_format($app->bid_amount, 2) }}
                            </span>
                            @if($i === 0)
                            <span class="block text-xs text-green-600 font-medium">Lowest bid</span>
                            @endif
                            @if($app->bid_recorded_at)
                            <span class="block text-[0.65rem] text-gray-400 mt-0.5" title="Read off the physical quote and keyed in">
                                <i class="fas fa-user-pen mr-0.5"></i>entered @if($app->bidRecordedBy)by {{ $app->bidRecordedBy->name }}@endif
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center space-x-2">
                            @if($tender->hasPostalBids() && $app->postal_quote_scan)
                            <a href="{{ Storage::url($app->postal_quote_scan) }}" target="_blank" title="Postal quote scan" class="text-amber-600 hover:text-amber-800">
                                <i class="fas fa-file-alt"></i>
                            </a>
                            @endif
                            @if($app->technical_proposal_file)
                            <a href="{{ Storage::url($app->technical_proposal_file) }}" target="_blank" title="Technical proposal" class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-file-lines"></i>
                            </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                {{ $app->status === 'shortlisted' ? 'bg-blue-100 text-blue-700' :
                                  ($app->status === 'awarded' ? 'bg-green-100 text-green-700' :
                                  ($app->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700')) }}">
                                {{ ucfirst($app->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('purchase-committee.review.application-show', [$tender->id, $app->id]) }}" class="text-xs text-indigo-600 hover:underline">
                                View details
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
