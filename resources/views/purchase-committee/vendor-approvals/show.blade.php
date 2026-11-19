@extends('layouts.dashboard')

@section('title', 'Vendor Approval')
@section('header', $vendor->company_name)

@section('content')
@php
    $docs  = $vendor->registration_documents ?? [];
    $panDoc = $docs['pan_document'] ?? null;
    $gstDoc = $docs['gst_document'] ?? null;
    $certs  = $docs['certificates'] ?? [];

    // pan_document was stored as a bare path, the others as ['path' => ..., 'name' => ...].
    $docUrl = fn ($d) => asset('storage/' . (is_array($d) ? ($d['path'] ?? '') : $d));
    $docName = fn ($d, $fallback) => is_array($d) ? ($d['name'] ?? $fallback) : $fallback;
@endphp

<div class="max-w-5xl mx-auto space-y-5">

    <a href="{{ route('purchase-committee.vendor-approvals.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left text-xs"></i> Back to vendor approvals
    </a>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fas fa-circle-exclamation mr-1.5"></i>{{ $errors->first() }}
        </div>
    @endif

    {{-- Where this sits in the order. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <h3 class="text-white font-semibold flex items-center gap-2">
                <i class="fas fa-list-ol"></i> Approval order
            </h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($vendor->approvals as $step)
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
        @if($vendor->officerApprover)
            <div class="px-6 py-3 bg-gray-50 text-xs text-gray-500 border-t border-gray-100">
                Cleared by the Purchase Officer, {{ $vendor->officerApprover->name }},
                on {{ $vendor->officer_approved_at?->format('d M Y, h:i A') }}.
            </div>
        @endif
    </div>

    {{-- The registration itself. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                <i class="fas fa-building text-gray-400"></i> Company details
            </h3>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 px-6 py-4 text-sm">
            @foreach([
                'Company'      => $vendor->company_name,
                'Vendor code'  => $vendor->vendor_code,
                'GST number'   => $vendor->gst_number ?: '—',
                'PAN number'   => $vendor->pan_number,
                'Email'        => $vendor->email,
                'Phone'        => $vendor->phone,
                'Address'      => trim($vendor->address . ', ' . $vendor->city . ', ' . $vendor->state . ' ' . $vendor->pincode, ' ,'),
                'Registered'   => $vendor->created_at?->format('d M Y'),
            ] as $label => $value)
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                    <dd class="text-gray-800 break-words">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Documents. A member cannot sensibly approve without being able to open these. --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                <i class="fas fa-paperclip text-gray-400"></i> Documents filed
            </h3>
        </div>
        <div class="divide-y divide-gray-50">
            @if($gstDoc)
                <a href="{{ $docUrl($gstDoc) }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                    <span class="text-sm text-gray-800">
                        <i class="fas fa-file-invoice text-indigo-500 mr-2"></i>GST certificate
                        <span class="text-gray-400">— {{ $docName($gstDoc, 'document') }}</span>
                    </span>
                    <i class="fas fa-arrow-up-right-from-square text-xs text-gray-400"></i>
                </a>
            @endif
            @if($panDoc)
                <a href="{{ $docUrl($panDoc) }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                    <span class="text-sm text-gray-800">
                        <i class="fas fa-id-card text-indigo-500 mr-2"></i>PAN document
                        <span class="text-gray-400">— {{ $docName($panDoc, 'document') }}</span>
                    </span>
                    <i class="fas fa-arrow-up-right-from-square text-xs text-gray-400"></i>
                </a>
            @endif
            @foreach($certs as $cert)
                <a href="{{ $docUrl($cert) }}" target="_blank" rel="noopener"
                   class="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                    <span class="text-sm text-gray-800">
                        <i class="fas fa-certificate text-indigo-500 mr-2"></i>{{ $cert['label'] ?? 'Certificate' }}
                        <span class="text-gray-400">— {{ $cert['name'] ?? 'document' }}</span>
                    </span>
                    <i class="fas fa-arrow-up-right-from-square text-xs text-gray-400"></i>
                </a>
            @endforeach
            @if(!$gstDoc && !$panDoc && empty($certs))
                <p class="px-6 py-8 text-center text-sm text-gray-400">No documents were filed with this registration.</p>
            @endif
        </div>
    </div>

    {{-- The decision. Only rendered when it is genuinely this member's turn; the same rule is
         enforced again in VendorApprovalChain, so hiding this is a courtesy, not the control. --}}
    @if($isMyTurn)
        <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-200 overflow-hidden">
            <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-100">
                <h3 class="font-semibold text-indigo-900 text-sm flex items-center gap-2">
                    <i class="fas fa-gavel"></i> Your decision
                </h3>
                <p class="text-xs text-indigo-700 mt-1">
                    @if($isFinal)
                        You are the final approver. Approving makes {{ $vendor->company_name }} an active
                        vendor and sends them the approval email.
                    @else
                        Approving passes this to the next member in the order. The vendor is not told anything yet.
                    @endif
                </p>
            </div>

            <div class="px-6 py-4 space-y-4">
                <form method="POST" action="{{ route('purchase-committee.vendor-approvals.approve', $vendor->id) }}">
                    @csrf
                    <label for="comments" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">
                        Comments (optional)
                    </label>
                    <textarea id="comments" name="comments" rows="2" maxlength="2000"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none"
                              placeholder="Anything you want recorded against your approval"></textarea>
                    <button type="submit"
                            onclick="return confirm('{{ $isFinal
                                ? 'You are the final approver. This will activate the vendor and email them. Continue?'
                                : 'Approve and pass this to the next member in the order?' }}')"
                            class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                        <i class="fas fa-check mr-1.5"></i>
                        {{ $isFinal ? 'Approve — final approval' : 'Approve and pass on' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('purchase-committee.vendor-approvals.reject', $vendor->id) }}"
                      class="border-t border-gray-100 pt-4">
                    @csrf
                    <label for="reason" class="block text-xs uppercase tracking-wide text-gray-400 mb-1">
                        Reason for rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea id="reason" name="reason" rows="2" maxlength="2000"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-red-400 focus:outline-none"
                              placeholder="The vendor is told this reason"></textarea>
                    <button type="submit"
                            onclick="return confirm('Reject this vendor? The approval order stops here and the vendor is told.')"
                            class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                        <i class="fas fa-xmark mr-1.5"></i> Reject
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
                You rejected this on {{ $myStep->decided_at?->format('d M Y, h:i A') }}.
            @elseif($vendor->isApproved())
                <i class="fas fa-circle-check text-emerald-500 mr-1.5"></i>
                This vendor has already been approved.
            @elseif($vendor->isRejected())
                <i class="fas fa-circle-xmark text-red-500 mr-1.5"></i>
                This vendor was rejected{{ $vendor->rejection_reason ? ': ' . $vendor->rejection_reason : '.' }}
            @else
                <i class="fas fa-hourglass-half text-gray-400 mr-1.5"></i>
                It is not your turn yet — this is still with
                {{ $current?->committeeMember->name ?? 'an earlier approver' }}.
            @endif
        </div>
    @endif

</div>
@endsection
