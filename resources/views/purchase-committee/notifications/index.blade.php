@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('header', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('purchase-committee.dashboard') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Back to Dashboard
        </a>
        @if(auth()->user()->unreadNotifications()->count() > 0)
        <button onclick="markAllRead()" class="text-xs text-indigo-600 hover:underline">Mark all read</button>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($notifications->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-bell-slash text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No notifications yet.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($notifications as $notification)
            @php
                $data = $notification->data ?? [];
                $type = $data['type'] ?? '';
            @endphp

            {{-- Opening code: the one notification the member reads out loud, so the code is
                 shown in full rather than as a preview. --}}
            @if($type === 'tender_opening_otp')
            @php
                $expiresAt = !empty($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null;
                $expired   = $expiresAt && $expiresAt->isPast();
            @endphp
            <div class="px-6 py-5 {{ $notification->read_at ? '' : 'bg-purple-50/40' }}">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-key text-purple-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">Your opening code</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $data['tender_title'] ?? 'Tender' }} · {{ $data['tender_number'] ?? '' }}
                            @if(!empty($data['officer_name'])) · Requested by {{ $data['officer_name'] }} @endif
                        </p>

                        <div class="mt-3 inline-flex items-center gap-3 border rounded-lg px-4 py-2.5
                                    {{ $expired ? 'bg-gray-50 border-gray-200' : 'bg-white border-purple-200' }}">
                            <span class="text-2xl font-mono tracking-[0.3em] {{ $expired ? 'text-gray-400 line-through' : 'text-purple-700' }}">
                                {{ $data['otp_code'] ?? '——————' }}
                            </span>
                        </div>

                        <p class="text-xs mt-2.5 {{ $expired ? 'text-red-600' : 'text-gray-500' }}">
                            @if($expired)
                                <i class="fas fa-circle-exclamation mr-1"></i>This code has expired — ask the Purchase Officer to resend it.
                            @else
                                <i class="fas fa-user-check mr-1"></i>Read this code out to the Purchase Officer in person. They enter every
                                member's code together, and the tender opens once all of them are in.
                                @if($expiresAt) Valid until {{ $expiresAt->format('d M, h:i A') }}. @endif
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-1.5">
                            <i class="fas fa-shield-halved mr-1"></i>Never forward this code by email or message.
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if(!$notification->read_at)
                    <span class="w-2 h-2 bg-purple-500 rounded-full flex-shrink-0 mt-2"></span>
                    @endif
                </div>
            </div>

            {{-- Documents shared after the opening --}}
            @elseif($type === 'tender_documents_shared')
            <a href="{{ route('purchase-committee.review.documents', $data['tender_id'] ?? 0) }}"
               class="block px-6 py-4 hover:bg-gray-50 transition {{ $notification->read_at ? '' : 'bg-emerald-50/40' }}">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-folder-open text-emerald-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">Documents shared — {{ $data['tender_title'] ?? 'Tender' }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $data['message_preview'] ?? '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $data['tender_number'] ?? '' }} · {{ $data['application_count'] ?? 0 }} application(s) · {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                    <span class="w-2 h-2 bg-emerald-500 rounded-full flex-shrink-0 mt-2"></span>
                    @endif
                </div>
            </a>

            {{-- Physical documents recorded against a postal/hybrid submission --}}
            @elseif($type === 'received_documents_uploaded')
            <a href="{{ route('purchase-committee.review.documents', $data['tender_id'] ?? 0) }}"
               class="block px-6 py-4 hover:bg-gray-50 transition {{ $notification->read_at ? '' : 'bg-teal-50/40' }}">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ ($data['source'] ?? '') === 'scan' ? 'fa-camera' : 'fa-file-arrow-up' }} text-teal-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                            {{ ($data['source'] ?? '') === 'scan' ? 'Documents scanned' : 'Documents received' }}
                            — {{ $data['vendor_name'] ?? 'a vendor' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $data['message_preview'] ?? '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $data['tender_title'] ?? '' }} · {{ $data['application_number'] ?? '' }}
                            @if(!empty($data['uploader_name'])) · by {{ $data['uploader_name'] }} @endif
                            · {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                    <span class="w-2 h-2 bg-teal-500 rounded-full flex-shrink-0 mt-2"></span>
                    @endif
                </div>
            </a>

            {{-- A bid value read off a physical quote --}}
            @elseif($type === 'bid_value_recorded')
            <a href="{{ route('purchase-committee.review.compare-bids', $data['tender_id'] ?? 0) }}"
               class="block px-6 py-4 hover:bg-gray-50 transition {{ $notification->read_at ? '' : 'bg-amber-50/40' }}">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-indian-rupee-sign text-amber-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                            {{ ($data['was_changed'] ?? false) ? 'Bid value changed' : 'Bid value recorded' }}
                            — {{ $data['vendor_name'] ?? 'a vendor' }}
                        </p>
                        <p class="text-sm text-gray-700 mt-0.5">
                            {{ isset($data['bid_amount']) && $data['bid_amount'] !== null ? '₹' . number_format($data['bid_amount'], 2) : 'N/A' }}
                            @if(($data['was_changed'] ?? false) && isset($data['previous_amount']))
                            <span class="text-xs text-gray-400 line-through ml-1.5">₹{{ number_format($data['previous_amount'], 2) }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $data['tender_title'] ?? '' }} · {{ $data['application_number'] ?? '' }}
                            @if(!empty($data['recorded_by'])) · by {{ $data['recorded_by'] }} @endif
                            · {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                    <span class="w-2 h-2 bg-amber-500 rounded-full flex-shrink-0 mt-2"></span>
                    @endif
                </div>
            </a>

            {{-- The officer's award decision, or its withdrawal --}}
            @elseif(in_array($type, ['award_finalized', 'award_finalization_revoked'], true))
            @php $wasRevoked = $type === 'award_finalization_revoked'; @endphp
            <a href="{{ route('purchase-committee.review.documents', $data['tender_id'] ?? 0) }}"
               class="block px-6 py-4 hover:bg-gray-50 transition {{ $notification->read_at ? '' : ($wasRevoked ? 'bg-red-50/40' : 'bg-slate-50') }}">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full {{ $wasRevoked ? 'bg-red-100' : 'bg-slate-200' }} flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $wasRevoked ? 'fa-rotate-left text-red-600' : 'fa-gavel text-slate-700' }} text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                            {{ $wasRevoked ? 'Award finalization withdrawn' : 'Award finalized' }}
                            — {{ $data['vendor_name'] ?? 'a vendor' }}
                        </p>
                        <p class="text-sm text-gray-700 mt-0.5">
                            {{ isset($data['awarded_value']) && $data['awarded_value'] !== null ? '₹' . number_format($data['awarded_value'], 2) : 'N/A' }}
                            @if(!empty($data['discussion_mode']))
                            <span class="text-xs text-gray-500 ml-1.5">
                                <i class="fas {{ $data['discussion_mode'] === 'oral' ? 'fa-comments' : 'fa-video' }} mr-0.5"></i>{{ ucfirst($data['discussion_mode']) }} discussion
                            </span>
                            @endif
                        </p>
                        @if(!empty($data['participants']))
                        <p class="text-xs text-gray-400 mt-0.5">Took part: {{ implode(', ', (array) $data['participants']) }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $data['tender_title'] ?? '' }} · {{ $data['application_number'] ?? '' }}
                            @if(!empty($data['finalized_by'])) · by {{ $data['finalized_by'] }} @endif
                            · {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                    <span class="w-2 h-2 {{ $wasRevoked ? 'bg-red-500' : 'bg-slate-600' }} rounded-full flex-shrink-0 mt-2"></span>
                    @endif
                </div>
            </a>

            {{-- Everything else (queries, replies, …) --}}
            @else
            <div class="px-6 py-4 {{ $notification->read_at ? '' : 'bg-blue-50/40' }}">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bell text-blue-600 text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $data['tender_title'] ?? 'Notification' }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            @if(!empty($data['sender_name'])){{ $data['sender_name'] }}: @endif{{ $data['message_preview'] ?? '' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @if(!$notification->read_at)
                    <span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></span>
                    @endif
                </div>
            </div>
            @endif
            @endforeach
        </div>

        <div class="px-6 py-4 border-t border-gray-100">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>

<script>
function markAllRead() {
    fetch('{{ route('purchase-committee.notifications.read-all') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
    }).then(() => location.reload());
}
</script>
@endsection
