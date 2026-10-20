@extends('layouts.dashboard')

@section('title', 'Award Recommendation')
@section('header', 'Award Recommendation')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush

@section('content')
@php
    $statusLabel = [
        'pending_review'    => 'Pending Committee Review',
        'committee_approved'=> 'Committee Approved',
        'otp_pending'       => 'Awaiting OTP Confirmation',
        'completed'         => 'Fully Approved',
        'rejected'          => 'Rejected',
    ][$recommendation->status] ?? $recommendation->status;
    $statusColor = [
        'pending_review'    => 'bg-amber-100 text-amber-700',
        'committee_approved'=> 'bg-blue-100 text-blue-700',
        'otp_pending'       => 'bg-purple-100 text-purple-700',
        'completed'         => 'bg-green-100 text-green-700',
        'rejected'          => 'bg-red-100 text-red-700',
    ][$recommendation->status] ?? 'bg-gray-100 text-gray-600';
@endphp
<div class="max-w-3xl mx-auto space-y-4">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white"><i class="fas fa-gavel mr-2"></i>{{ $recommendation->tender->title }}</h3>
                <p class="text-indigo-100 text-xs mt-0.5">{{ $recommendation->tender->tender_number }}</p>
            </div>
            <span class="text-xs px-3 py-1 rounded-full font-medium {{ $statusColor }}">{{ $statusLabel }}</span>
        </div>

        <div class="p-6 grid grid-cols-2 gap-4 border-b border-gray-100">
            <div>
                <label class="text-xs text-gray-500 uppercase">Recommended Vendor</label>
                <p class="text-gray-900 font-medium">{{ $recommendation->application->vendor->company_name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Bid Amount</label>
                <p class="text-gray-900 font-medium">₹{{ number_format($recommendation->application->bid_amount ?? 0, 2) }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Initiated By</label>
                <p class="text-gray-900">{{ $recommendation->initiator->name ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Initiated On</label>
                <p class="text-gray-900">{{ $recommendation->created_at->format('d M Y, h:i A') }}</p>
            </div>
            @if($recommendation->initiation_note)
            <div class="col-span-2">
                <label class="text-xs text-gray-500 uppercase">Initiation Note</label>
                <p class="text-gray-700 text-sm mt-1">{{ $recommendation->initiation_note }}</p>
            </div>
            @endif
        </div>

        {{-- Earlier informal committee discussion, from before this formal recommendation was made --}}
        @if($recommendation->application->reviewComments->isNotEmpty())
        <div class="p-6 border-b border-gray-100 bg-indigo-50/40">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-comments text-indigo-500 mr-1"></i> Committee Discussion (before this recommendation)
            </h4>
            <div class="space-y-3">
                @foreach($recommendation->application->reviewComments as $comment)
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                        {{ strtoupper(substr($comment->committeeMember->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-800">{{ $comment->committeeMember->name ?? 'Unknown' }}</span>
                            <span class="text-xs text-gray-400">{{ $comment->created_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="text-sm text-gray-700 mt-0.5 prose prose-sm max-w-none">{!! $comment->comment !!}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Approval steps --}}
        <div class="p-6 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Committee Approval Sequence</h4>
            <div class="space-y-3">
                @foreach($recommendation->approvals as $approval)
                @php
                    $stepColor = match($approval->status) {
                        'approved' => 'bg-green-100 text-green-700 border-green-200',
                        'rejected' => 'bg-red-100 text-red-700 border-red-200',
                        default => 'bg-gray-100 text-gray-500 border-gray-200',
                    };
                @endphp
                <div class="border rounded-lg p-4 {{ $stepColor }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-white flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $approval->approval_order }}</span>
                            <span class="font-medium text-gray-900">{{ $approval->committeeMember->name ?? 'Unknown' }}</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-white font-medium">{{ ucfirst($approval->status) }}</span>
                    </div>
                    @if($approval->comments)
                    <div class="mt-2 text-sm text-gray-700 bg-white/60 rounded p-2 prose prose-sm max-w-none">{!! $approval->comments !!}</div>
                    @endif
                    @if($approval->decided_at)
                    <p class="text-xs text-gray-500 mt-1">{{ $approval->decided_at->format('d M Y, h:i A') }}</p>
                    @endif
                    @if($recommendation->status === 'otp_pending' || $recommendation->status === 'completed')
                    <p class="text-xs mt-2 {{ $approval->otp_verified_at ? 'text-green-600' : 'text-amber-600' }}">
                        <i class="fas {{ $approval->otp_verified_at ? 'fa-check-circle' : 'fa-clock' }} mr-1"></i>
                        {{ $approval->otp_verified_at ? 'OTP confirmed ' . $approval->otp_verified_at->format('d M, h:i A') : 'Awaiting OTP confirmation' }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Action area for the current approver --}}
        @if($isMyTurn && $recommendation->status === 'pending_review')
        <div class="p-6 bg-indigo-50 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-vote-yea mr-1 text-indigo-600"></i>It's your turn to review</h4>
            <form method="POST" action="{{ route('purchase-committee.recommendations.approve', $recommendation->id) }}" id="approveForm" class="space-y-3">
                @csrf
                <textarea name="comments" id="approveComments" placeholder="Add your comments or enquiry before approving (optional)"></textarea>
                <div class="flex gap-3">
                    <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 text-sm font-medium">
                        <i class="fas fa-check mr-1"></i> Approve
                    </button>
                </div>
            </form>
            <form method="POST" action="{{ route('purchase-committee.recommendations.reject', $recommendation->id) }}" id="rejectForm" class="mt-3">
                @csrf
                <input type="hidden" name="comments" id="rejectComments">
                <button type="button" onclick="submitReject()" class="text-red-600 hover:underline text-sm font-medium">
                    <i class="fas fa-times mr-1"></i> Reject this recommendation
                </button>
            </form>
        </div>
        @endif

        {{-- OTP entry --}}
        @if($recommendation->status === 'otp_pending' && $myApproval && !$myApproval->otp_verified_at)
        <div class="p-6 bg-purple-50">
            <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-lock mr-1 text-purple-600"></i>Confirm your approval</h4>
            <p class="text-xs text-gray-500 mb-3">Check your email for a 6-digit code and enter it below to confirm you were present for this decision.</p>
            <form method="POST" action="{{ route('purchase-committee.recommendations.verify-otp', $recommendation->id) }}" class="flex items-end gap-3">
                @csrf
                <div>
                    <input type="text" name="otp" maxlength="6" placeholder="000000" required
                        class="border border-gray-300 rounded-lg px-4 py-2 text-lg tracking-widest text-center font-mono w-40">
                </div>
                <button type="submit" class="bg-purple-600 text-white px-5 py-2.5 rounded-lg hover:bg-purple-700 text-sm font-medium">Verify</button>
            </form>
            <form method="POST" action="{{ route('purchase-committee.recommendations.resend-otp', $recommendation->id) }}" class="mt-2">
                @csrf
                <button type="submit" class="text-xs text-purple-600 hover:underline">Resend code</button>
            </form>
        </div>
        @endif

        @if($recommendation->status === 'completed')
        <div class="p-6 bg-green-50 text-sm text-green-800">
            <i class="fas fa-check-circle mr-1"></i>
            All committee members have approved and confirmed via OTP. The Tender Owner / Admin can now proceed to finalize the award and generate the contract.
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function () {
    $('#approveComments').summernote({
        height: 120,
        toolbar: [['style', ['bold', 'italic', 'underline']], ['para', ['ul', 'ol']], ['view', ['fullscreen']]],
        placeholder: 'Add your comments or enquiry before approving (optional)',
    });
});

function submitReject() {
    const reason = prompt('Please enter your reason for rejecting this recommendation:');
    if (!reason || reason.trim().length < 5) {
        if (reason !== null) alert('Please provide a reason of at least 5 characters.');
        return;
    }
    document.getElementById('rejectComments').value = reason;
    document.getElementById('rejectForm').submit();
}
</script>
@endpush
