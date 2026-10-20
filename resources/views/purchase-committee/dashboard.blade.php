@extends('layouts.dashboard')

@section('title', 'Purchase Committee Dashboard')
@section('header', 'Purchase Committee Dashboard')

@section('content')
<div class="space-y-6">

    <div class="bg-gradient-to-r from-indigo-700 to-purple-700 rounded-2xl px-6 py-5 text-white shadow-md">
        <p class="text-indigo-200 text-sm mb-1">Welcome back,</p>
        <h2 class="text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="text-indigo-200 text-sm mt-1">Purchase Committee &nbsp;·&nbsp; {{ now()->format('d M, Y') }}</p>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        @foreach([
            ['label'=>'Awaiting Your Approval', 'value'=>$stats['pending_approvals'],    'icon'=>'fa-vote-yea',  'color'=>'indigo'],
            ['label'=>'OTP Confirmations Due',  'value'=>$stats['pending_otps'],         'icon'=>'fa-lock',       'color'=>'purple'],
            ['label'=>'Your Codes To Open',     'value'=>$stats['opening_otps_pending'], 'icon'=>'fa-key',        'color'=>'purple'],
            ['label'=>'Docs To Record',         'value'=>$stats['received_pending'] ?? 0, 'icon'=>'fa-file-arrow-up', 'color'=>'teal'],
            ['label'=>'Tenders To Open',        'value'=>$stats['tenders_to_open'],      'icon'=>'fa-envelope-open-text', 'color'=>'green'],
            ['label'=>'Postal Entries Pending', 'value'=>$stats['postal_pending'],       'icon'=>'fa-envelope',   'color'=>'amber'],
        ] as $s)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $s['label'] }}</span>
                <span class="w-8 h-8 rounded-lg flex items-center justify-center
                    {{ $s['color']==='indigo' ? 'bg-indigo-100 text-indigo-600' :
                      ($s['color']==='purple' ? 'bg-purple-100 text-purple-600' :
                      ($s['color']==='green'  ? 'bg-green-100 text-green-600'  :
                      ($s['color']==='teal'   ? 'bg-teal-100 text-teal-600'    :
                                               'bg-amber-100 text-amber-600'))) }}">
                    <i class="fas {{ $s['icon'] }} text-sm"></i>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $s['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Awaiting my approval --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-vote-yea text-indigo-500 text-sm"></i> Awaiting Your Approval</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($myPendingApprovals as $approval)
            <a href="{{ route('purchase-committee.recommendations.show', $approval->recommendation->id) }}" class="block px-5 py-3.5 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $approval->recommendation->tender->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $approval->recommendation->tender->tender_number }} · {{ $approval->recommendation->application->vendor->company_name ?? 'N/A' }}</p>
                    </div>
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium flex-shrink-0">Your turn — Step {{ $approval->approval_order }}</span>
                </div>
            </a>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">Nothing awaiting your approval right now.</div>
            @endforelse
        </div>
    </div>

    {{-- Opening codes — the member reads theirs out to the Purchase Officer, who keys it in --}}
    @if($myPendingOpeningOtps->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-key text-purple-500 text-sm"></i> Your Code Is Needed To Open This Tender</h3>
            <p class="text-xs text-gray-400 mt-1">Read your code out to the Purchase Officer in person — they enter every member's code together. Never forward it.</p>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($myPendingOpeningOtps as $otp)
            <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $otp->tender->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $otp->tender->tender_number }} · {{ $otp->tender->getSubmissionModeLabel() }}
                        @if($otp->isExpired())
                            · <span class="text-red-600">Code expired — ask the officer to resend</span>
                        @elseif($otp->otp_expires_at)
                            · Valid until {{ $otp->otp_expires_at->format('h:i A') }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('purchase-committee.notifications') }}" class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-3 py-1.5 rounded-lg hover:bg-purple-100 flex-shrink-0">
                    View My Code
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- OTP confirmations --}}
    @if($myPendingOtps->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-lock text-purple-500 text-sm"></i> OTP Confirmation Needed</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($myPendingOtps as $approval)
            <a href="{{ route('purchase-committee.recommendations.show', $approval->recommendation->id) }}" class="block px-5 py-3.5 hover:bg-gray-50 transition">
                <p class="text-sm font-medium text-gray-900">{{ $approval->recommendation->tender->title }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $approval->recommendation->tender->tender_number }} · Check your email for the code</p>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Tenders to open --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-envelope-open-text text-green-500 text-sm"></i> Tenders To Open</h3>
                <a href="{{ route('purchase-committee.tenders.index') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($tendersToOpen as $tender)
                <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $tender->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $tender->tender_number }} · {{ $tender->applications_count }} applications</p>
                    </div>
                    <button onclick="openBidding({{ $tender->id }})" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 flex-shrink-0"
                            title="Sends a code to every committee member's login, then opens the screen where the codes are entered">Open</button>
                </div>
                @empty
                <div class="px-5 py-10 text-center text-gray-400 text-sm">No closed tenders waiting to be opened.</div>
                @endforelse
            </div>
        </div>

        {{-- Postal pending entry --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-envelope text-amber-500 text-sm"></i> Postal Bids Pending Entry</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($postalPendingEntry as $app)
                <a href="{{ route('purchase-committee.postal-bid.edit', $app->id) }}" class="block px-5 py-3.5 hover:bg-gray-50 transition">
                    <p class="text-sm font-medium text-gray-900">{{ $app->vendor->company_name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $app->tender->title ?? 'N/A' }}</p>
                </a>
                @empty
                <div class="px-5 py-10 text-center text-gray-400 text-sm">No postal bids waiting for data entry.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Envelopes still to be recorded on an open postal/hybrid tender --}}
    @if($receivedPending->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-file-arrow-up text-teal-500 text-sm"></i> Received Documents To Record
            </h3>
            <p class="text-xs text-gray-400 mt-1">Upload or scan the papers that arrived by post — the committee is notified each time.</p>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($receivedPending as $t)
            <a href="{{ route('purchase-committee.received-documents.index', $t->id) }}" class="block px-5 py-3.5 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $t->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $t->tender_number }} · {{ $t->getSubmissionModeLabel() }} · opened {{ $t->bid_opening_at->format('d M Y') }}
                        </p>
                    </div>
                    <span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full flex-shrink-0">
                        {{ $t->applications_without_received_count }} of {{ $t->applications_count }} pending
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recently opened — bid comparison --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2"><i class="fas fa-scale-balanced text-indigo-500 text-sm"></i> Recently Opened — Compare Bids</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentlyOpenedTenders as $tender)
            <div class="px-5 py-3.5 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $tender->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $tender->tender_number }} · Opened {{ $tender->bid_opening_at->format('d M Y') }}</p>
                </div>
                @if($tender->isReviewUnlockedFor(auth()->id()))
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('purchase-committee.review.documents', $tender->id) }}" class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-100">Documents</a>
                    <a href="{{ route('purchase-committee.review.compare-bids', $tender->id) }}" class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-lg hover:bg-indigo-100">Compare Bids</a>
                </div>
                @else
                <span class="text-xs text-gray-400 flex-shrink-0">Your code was not collected for this opening</span>
                @endif
            </div>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">No tenders opened yet.</div>
            @endforelse
        </div>
    </div>

    @include('partials.dashboard-widgets', ['widgetData' => $widgetData, 'hideEmptyState' => true])
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
function openBidding(id) {
    if (!confirm('Send an opening code to every active Purchase Committee member? Each member receives their code in their own login and reads it out to the Purchase Officer in person — the codes are then entered together on the next screen, and the tender opens once every code is in.')) return;
    fetch(`/purchase-committee/tenders/${id}/open-bidding`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            alert(data.message);
            /* Straight to the screen where the codes are keyed in. */
            window.location.href = data.redirect || window.location.href;
        }
        else alert(data.message || 'Failed to open bidding.');
    });
}
</script>
@endsection
