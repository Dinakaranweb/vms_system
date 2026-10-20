@extends('layouts.dashboard')

@section('title', 'Collect Opening Codes — ' . $tender->title)
@section('header', 'Tender Opening — Committee Codes')

@section('content')
@php
    $verified    = $otps->filter(fn($o) => $o->isVerified());
    $outstanding = $otps->reject(fn($o) => $o->isVerified());
    $total       = $otps->count();
@endphp

<div class="max-w-3xl mx-auto space-y-4">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
        @foreach($errors->all() as $error)<p><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</p>@endforeach
    </div>
    @endif

    {{-- Tender header + progress --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-white font-semibold truncate">{{ $tender->title }}</h3>
                    <p class="text-indigo-100 text-xs mt-0.5">
                        {{ $tender->tender_number }} · {{ $tender->getSubmissionModeLabel() }}
                        @if($tender->bidOpenedBy) · Initiated by {{ $tender->bidOpenedBy->name }} @endif
                    </p>
                </div>
                <span class="bg-white/15 text-white text-xs px-2.5 py-1 rounded-full border border-white/25 flex-shrink-0">
                    {{ $verified->count() }} / {{ $total }} collected
                </span>
            </div>
            <div class="mt-3 h-1.5 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-400 rounded-full transition-all"
                     style="width: {{ $total ? round($verified->count() / $total * 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="px-6 py-4 bg-amber-50 border-b border-amber-100">
            <p class="text-xs text-amber-900 leading-relaxed">
                <i class="fas fa-circle-info mr-1"></i>
                Each Purchase Committee member has received their own code in their login. Ask every member
                to read their code out to you in person and enter it against their name below. You may enter
                them as members arrive — the tender opens, and the submitted documents reach the committee's
                logins, only once all {{ $total }} codes have been entered.
            </p>
        </div>

        {{-- Code entry --}}
        <form method="POST" action="{{ route('purchase-committee.review.verify-opening-codes', $tender->id) }}">
            @csrf
            <div class="divide-y divide-gray-50">
                @foreach($otps as $otp)
                @php
                    $member    = $otp->committeeMember;
                    $isActive  = $members->has($otp->committee_member_id);
                    $desig     = $members->get($otp->committee_member_id)?->designation;
                @endphp
                <div class="px-6 py-4 flex items-center gap-4 {{ $otp->isVerified() ? 'bg-emerald-50/40' : '' }}">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-semibold
                                {{ $otp->isVerified() ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                        @if($otp->isVerified())
                            <i class="fas fa-check"></i>
                        @else
                            {{ strtoupper(substr($member->name ?? '?', 0, 2)) }}
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $member->name ?? 'Unknown member' }}
                            @unless($isActive)
                            <span class="ml-1 text-[0.65rem] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">no longer active</span>
                            @endunless
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ $desig ?: 'Purchase Committee' }}
                            @if($otp->isVerified())
                                · Collected {{ $otp->otp_verified_at->format('d M, h:i A') }}@if($otp->collectedBy) by {{ $otp->collectedBy->name }}@endif
                            @elseif($otp->isExpired())
                                · <span class="text-red-600">Code expired {{ $otp->otp_expires_at->diffForHumans() }}</span>
                            @elseif($otp->otp_expires_at)
                                · Code valid until {{ $otp->otp_expires_at->format('h:i A') }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($otp->isVerified())
                            <span class="text-xs text-emerald-700 font-medium"><i class="fas fa-lock-open mr-1"></i>Verified</span>
                        @else
                            <input type="text" name="otps[{{ $otp->committee_member_id }}]"
                                   maxlength="6" inputmode="numeric" autocomplete="off" placeholder="000000"
                                   class="w-28 border border-gray-300 rounded-lg px-3 py-2 text-center font-mono tracking-[0.2em] text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            @if($outstanding->isNotEmpty())
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                <p class="text-xs text-gray-500">
                    {{ $outstanding->count() }} code(s) still to collect.
                </p>
                <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    <i class="fas fa-key mr-1.5"></i>Confirm Codes
                </button>
            </div>
            @endif
        </form>
    </div>

    {{-- Resend, one member at a time --}}
    @if($outstanding->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-paper-plane text-gray-400 mr-1.5"></i>Resend a code</h4>
            <p class="text-xs text-gray-400 mt-0.5">If a member never received their code or it has expired, send them a fresh one.</p>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($outstanding as $otp)
            <form method="POST" action="{{ route('purchase-committee.review.resend-opening-code', $tender->id) }}"
                  class="px-6 py-3 flex items-center justify-between gap-3">
                @csrf
                <input type="hidden" name="committee_member_id" value="{{ $otp->committee_member_id }}">
                <span class="text-sm text-gray-700 truncate">{{ $otp->committeeMember->name ?? 'Unknown member' }}</span>
                <button type="submit" class="text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100 flex-shrink-0">
                    Resend code
                </button>
            </form>
            @endforeach
        </div>
    </div>
    @endif

    <p class="text-center text-xs text-gray-400">
        <i class="fas fa-shield-halved mr-1"></i>
        Codes are shown only in each member's own login and are never visible on this screen.
    </p>
</div>
@endsection
