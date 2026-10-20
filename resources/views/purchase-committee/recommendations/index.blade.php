@extends('layouts.dashboard')

@section('title', 'My Recommendations')
@section('header', 'Award Recommendations')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <h3 class="text-white font-semibold flex items-center gap-2"><i class="fas fa-gavel"></i> Award Recommendations Involving You</h3>
        </div>

        @if($recommendations->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-gavel text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No award recommendations yet.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($recommendations as $rec)
            @php
                $statusColor = [
                    'pending_review'    => 'bg-amber-100 text-amber-700',
                    'committee_approved'=> 'bg-blue-100 text-blue-700',
                    'otp_pending'       => 'bg-purple-100 text-purple-700',
                    'completed'         => 'bg-green-100 text-green-700',
                    'rejected'          => 'bg-red-100 text-red-700',
                ][$rec->status] ?? 'bg-gray-100 text-gray-600';
            @endphp
            <a href="{{ route('purchase-committee.recommendations.show', $rec->id) }}" class="block px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $rec->tender->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $rec->tender->tender_number }} · {{ $rec->application->vendor->company_name ?? 'N/A' }}</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium flex-shrink-0 {{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $rec->status)) }}</span>
                </div>
            </a>
            @endforeach
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $recommendations->links() }}</div>
        @endif
    </div>
</div>
@endsection
