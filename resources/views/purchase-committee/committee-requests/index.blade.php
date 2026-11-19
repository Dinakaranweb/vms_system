@extends('layouts.dashboard')

@section('title', 'Committee Requests')
@section('header', 'Committee Requests')

@section('content')
<div class="max-w-6xl mx-auto space-y-5">

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <i class="fas fa-circle-check mr-1.5"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fas fa-circle-exclamation mr-1.5"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700 flex items-center justify-between">
            <h3 class="text-white font-semibold flex items-center gap-2">
                <i class="fas fa-user-plus"></i> Waiting for your approval
            </h3>
            <span class="bg-white/20 text-white text-sm font-semibold px-3 py-0.5 rounded-full">
                {{ $awaitingMe->count() }}
            </span>
        </div>

        @if($awaitingMe->isEmpty())
            <div class="py-14 text-center">
                <i class="fas fa-mug-hot text-5xl text-gray-200 block mb-3"></i>
                <p class="text-gray-400">No committee additions are waiting on you.</p>
            </div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($awaitingMe as $step)
                    @php $seat = $step->request; @endphp
                    <div class="px-6 py-4 hover:bg-gray-50 transition flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <p class="font-semibold text-gray-900">{{ $seat->user->name ?? 'Unknown user' }}</p>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-600">
                                    {{ ucwords(str_replace('_', ' ', $seat->role)) }}
                                </span>
                                @if($seat->isFinalApprovalStep($step))
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-800">
                                        <i class="fas fa-flag-checkered mr-1"></i>You are the final approver
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 truncate">
                                For {{ $seat->tender->title ?? 'a tender' }}
                                @if($seat->tender)· #{{ $seat->tender->tender_number }}@endif
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Put forward by {{ $seat->requestedBy->name ?? 'unknown' }}
                                · step {{ $step->approval_order }}
                            </p>
                        </div>
                        <a href="{{ route('purchase-committee.committee-requests.show', $seat->id) }}"
                           class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Review
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($queued->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                    <i class="fas fa-hourglass-half text-gray-400"></i> Ahead of you in the order
                </h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($queued as $step)
                    @php $seat = $step->request; $cur = $seat->currentApprovalStep(); @endphp
                    <div class="px-6 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">
                                {{ $seat->user->name ?? 'Unknown user' }}
                                <span class="text-gray-400 font-normal">for {{ $seat->tender->title ?? 'a tender' }}</span>
                            </p>
                            <p class="text-xs text-gray-500">
                                Currently with {{ $cur?->committeeMember->name ?? 'an earlier approver' }}
                                · you are step {{ $step->approval_order }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-400 shrink-0">Not your turn yet</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($decided->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                    <i class="fas fa-clock-rotate-left text-gray-400"></i> Already decided by you
                </h3>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($decided as $step)
                    @php $seat = $step->request; @endphp
                    <div class="px-6 py-3 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <a href="{{ route('purchase-committee.committee-requests.show', $seat->id) }}"
                               class="font-medium text-gray-800 hover:text-indigo-700 truncate block">
                                {{ $seat->user->name ?? 'Unknown user' }}
                                <span class="text-gray-400 font-normal">for {{ $seat->tender->title ?? 'a tender' }}</span>
                            </a>
                            <p class="text-xs text-gray-500">{{ $step->decided_at?->format('d M Y, h:i A') }}</p>
                        </div>
                        <span class="shrink-0 text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $step->isApproved() ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $step->isApproved() ? 'Approved' : 'Rejected' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
