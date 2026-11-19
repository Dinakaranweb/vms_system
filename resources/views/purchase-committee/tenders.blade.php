@extends('layouts.dashboard')

@section('title', 'All Tenders')
@section('header', 'All Tenders')

@section('content')
<div class="max-w-6xl mx-auto space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700 flex items-center justify-between">
            <h3 class="text-white font-semibold flex items-center gap-2"><i class="fas fa-layer-group"></i> Tenders</h3>
            <span class="bg-white/20 text-white text-sm font-semibold px-3 py-0.5 rounded-full">{{ $tenders->total() }} tender(s)</span>
        </div>

        @if($tenders->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-layer-group text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No tenders yet.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($tenders as $tender)
            <div class="px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <p class="font-semibold text-gray-900">{{ $tender->title }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $tender->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($tender->status) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ ['online' => 'bg-blue-100 text-blue-700', 'postal' => 'bg-amber-100 text-amber-700', 'hybrid' => 'bg-teal-100 text-teal-700'][$tender->tender_type] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($tender->tender_type) }}</span>
                            @if($tender->isBidOpen())
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-emerald-100 text-emerald-700"><i class="fas fa-envelope-open mr-1"></i>Bids Opened</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 font-mono">{{ $tender->tender_number }}</p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap text-xs text-gray-500">
                            <span class="flex items-center gap-1"><i class="fas fa-user text-gray-400"></i> {{ $tender->owner->name ?? 'Unassigned' }}</span>
                            @if($tender->submission_deadline)
                            <span class="flex items-center gap-1"><i class="fas fa-calendar text-gray-400"></i> Deadline: {{ \Carbon\Carbon::parse($tender->submission_deadline)->format('d M, Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex-shrink-0 flex flex-col items-end gap-2">
                        <div class="text-right">
                            <p class="text-2xl font-bold text-indigo-700">{{ $tender->applications_count }}</p>
                            <p class="text-xs text-gray-400">applications</p>
                        </div>
                        @if($tender->canBeOpened() && !$tender->isOpeningOtpPending() && $tender->isClosed() && $tender->hasOpeningMomentArrived())
                        {{-- A committee member supplies a code; they never start the opening.
                             This is status only, so the action belongs to the officer alone. --}}
                        <span class="text-xs bg-slate-50 text-slate-600 border border-slate-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1"
                              title="The Purchase Officer starts the opening. Your code will arrive in your login when they do.">
                            <i class="fas fa-hourglass-start"></i> Awaiting the Purchase Officer
                        </span>
                        @elseif($tender->isOpeningOtpPending())
                            @if($tender->hasVerifiedOpeningOtp(auth()->id()))
                            <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1"><i class="fas fa-check"></i> Your code was collected</span>
                            @else
                            <span class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1" title="Your code is in your notifications — read it out to the Purchase Officer">
                                <i class="fas fa-key"></i> Read your code to the officer
                            </span>
                            @endif
                            {{-- The code-entry screen is the officer's and now refuses
                                 committee members, so show the tally without a dead link. --}}
                            <span class="text-xs text-gray-400">
                                {{ $tender->openingOtpsVerifiedCount() }}/{{ $tender->openingOtpsIssuedCount() }} codes collected
                            </span>
                        @elseif(!$tender->isBidOpen() && !$tender->isOpeningOtpPending() && $tender->isClosed())
                        {{-- A date still to come is the advertised schedule, not a lock: the
                             officer may start the opening on or before it. --}}
                        <span class="text-xs bg-slate-50 text-slate-600 border border-slate-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1"
                              title="Advertised opening. The Purchase Officer may start the opening on or before this, and your code arrives in your login when they do.">
                            <i class="fas fa-hourglass-start"></i> Scheduled {{ $tender->openingMoment()->format('d M, h:i A') }}
                        </span>
                        @elseif($tender->isBidOpen() && $tender->isReviewUnlockedFor(auth()->id()))
                        @php $tFinal = \App\Models\AwardFinalization::activeFor($tender->id); @endphp
                        @if($tFinal)
                        <span class="text-xs bg-slate-100 text-slate-700 border border-slate-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1"
                              title="Finalized by {{ $tFinal->finalizedBy->name ?? 'the Purchase Officer' }} after {{ strtolower($tFinal->getDiscussionModeLabel()) }}">
                            <i class="fas fa-gavel"></i> Awarded: {{ \Illuminate\Support\Str::limit($tFinal->application->vendor->company_name ?? 'vendor', 18) }}
                        </span>
                        @endif
                        <a href="{{ route('purchase-committee.review.documents', $tender->id) }}" class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-100">Shared Documents</a>
                        <a href="{{ route('purchase-committee.review.applications', $tender->id) }}" class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-lg hover:bg-indigo-100">View Applications</a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $tenders->links() }}</div>
        @endif
    </div>
</div>

@endsection
