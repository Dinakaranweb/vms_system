@extends('layouts.dashboard')

@section('title', 'Committee Request')
@section('header', $seat->user->name ?? 'Committee request')

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    <a href="{{ route('purchase-committee.committee-requests.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left text-xs"></i> Back to committee requests
    </a>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fas fa-circle-exclamation mr-1.5"></i>{{ $errors->first() }}
        </div>
    @endif

    {{-- Who is being proposed, and for what. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                <i class="fas fa-user-plus text-gray-400"></i> The request
            </h3>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 px-6 py-4 text-sm">
            @foreach([
                'Person'        => $seat->user->name ?? '—',
                'Their email'   => $seat->user->email ?? '—',
                'Committee role'=> ucwords(str_replace('_', ' ', (string) $seat->role)),
                'Designation'   => $seat->designation ?: '—',
                'Tender'        => $seat->tender->title ?? '—',
                'Tender number' => $seat->tender->tender_number ?? '—',
                'Tender owner'  => $seat->tender->owner->name ?? '—',
                'Put forward by'=> $seat->requestedBy->name ?? '—',
                'Requested'     => $seat->created_at?->format('d M Y, h:i A'),
            ] as $label => $value)
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                    <dd class="text-gray-800 break-words">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        @if($seat->tender && $seat->tender->isOpeningOtpPending())
            <div class="px-6 py-3 bg-amber-50 border-t border-amber-100 text-xs text-amber-800">
                <i class="fas fa-triangle-exclamation mr-1.5"></i>
                This tender is mid-opening. Approving will issue this person an opening code as
                well, and the tender cannot open until theirs has been collected too.
            </div>
        @endif
    </div>

    {{-- Where it sits in the order. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <h3 class="text-white font-semibold flex items-center gap-2">
                <i class="fas fa-list-ol"></i> Approval order
            </h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($seat->approvals as $step)
                @php $isCurrent = $current && $current->id === $step->id; @endphp
                <div class="px-6 py-3 flex items-center justify-between gap-4 {{ $isCurrent ? 'bg-indigo-50/60' : '' }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                            {{ $step->isApproved() ? 'bg-emerald-100 text-emerald-700'
                               : ($step->isRejected() ? 'bg-red-100 text-red-700'
                               : ($isCurrent ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500')) }}">
                            {{ $step->approval_order }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate">
                                {{ $step->committeeMember->name ?? 'Unknown member' }}
                                @if($step->committee_member_id === auth()->id())
                                    <span class="text-xs text-indigo-600 font-semibold">(you)</span>
                                @endif
                            </p>
                            @if($step->designation)
                                <p class="text-xs text-gray-500">{{ $step->designation }}</p>
                            @endif
                            @if($step->comments)
                                <p class="text-xs text-gray-500 mt-0.5 italic">“{{ $step->comments }}”</p>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $step->isApproved() ? 'bg-emerald-100 text-emerald-700'
                           : ($step->isRejected() ? 'bg-red-100 text-red-700'
                           : ($isCurrent ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $step->isApproved() ? 'Approved ' . $step->decided_at?->format('d M')
                           : ($step->isRejected() ? 'Rejected ' . $step->decided_at?->format('d M')
                           : ($isCurrent ? 'Awaiting decision' : 'Queued')) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- The decision. Rendered only on this member's turn; the rule is enforced again in
         TenderCommitteeApprovalChain, so hiding this is a courtesy, not the control. --}}
    @if($isMyTurn)
        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-200 overflow-hidden">
            <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-100">
                <h3 class="font-semibold text-indigo-900 text-sm flex items-center gap-2">
                    <i class="fas fa-gavel"></i> Your decision
                </h3>
                <p class="text-xs text-indigo-700 mt-1">
                    @if($isFinal)
                        You are the final approver. Approving adds
                        {{ $seat->user->name ?? 'this person' }} to the tender and tells them so.
                    @else
                        Approving passes this to the next member in the order. Nothing reaches
                        {{ $seat->user->name ?? 'the person put forward' }} yet.
                    @endif
                </p>
            </div>

            <div class="px-6 py-4 space-y-4">
                <form method="POST" action="{{ route('purchase-committee.committee-requests.approve', $seat->id) }}">
                    @csrf
                    <label for="comments" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">
                        Comments (optional)
                    </label>
                    <textarea id="comments" name="comments" rows="2" maxlength="2000"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none"
                              placeholder="Anything you want recorded against your approval"></textarea>
                    <button type="submit"
                            onclick="return confirm('{{ $isFinal
                                ? 'You are the final approver. This adds them to the tender and emails them. Continue?'
                                : 'Approve and pass this to the next member in the order?' }}')"
                            class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                        <i class="fas fa-check mr-1.5"></i>
                        {{ $isFinal ? 'Approve — final approval' : 'Approve and pass on' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('purchase-committee.committee-requests.reject', $seat->id) }}"
                      class="border-t border-gray-100 pt-4">
                    @csrf
                    <label for="reason" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">
                        Reason for refusing <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reason" name="reason" rows="2" maxlength="2000"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-red-400 focus:outline-none"
                              placeholder="Whoever asked for this addition is told the reason"></textarea>
                    <button type="submit"
                            onclick="return confirm('Refuse this addition? The request stops here and no later member is asked.')"
                            class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                        <i class="fas fa-xmark mr-1.5"></i> Refuse
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-6 py-5 text-sm text-gray-600">
            @if($myStep->isApproved())
                <i class="fas fa-circle-check text-emerald-500 mr-1.5"></i>
                You approved this on {{ $myStep->decided_at?->format('d M Y, h:i A') }}.
            @elseif($myStep->isRejected())
                <i class="fas fa-circle-xmark text-red-500 mr-1.5"></i>
                You refused this on {{ $myStep->decided_at?->format('d M Y, h:i A') }}.
            @elseif($seat->isApproved())
                <i class="fas fa-circle-check text-emerald-500 mr-1.5"></i>
                {{ $seat->user->name ?? 'This person' }} has already been added to the tender.
            @elseif($seat->isRejected())
                <i class="fas fa-circle-xmark text-red-500 mr-1.5"></i>
                This addition was refused{{ $seat->decision_reason ? ': ' . $seat->decision_reason : '.' }}
            @else
                <i class="fas fa-hourglass-half text-gray-400 mr-1.5"></i>
                It is not your turn yet — this is still with
                {{ $current?->committeeMember->name ?? 'an earlier approver' }}.
            @endif
        </div>
    @endif

</div>
@endsection
