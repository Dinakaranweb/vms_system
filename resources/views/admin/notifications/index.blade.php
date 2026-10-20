@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('header', 'Notifications')

@section('content')
@php
    $prefix = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin';
@endphp
<div class="max-w-3xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Notifications</h2>
            <p class="text-sm text-gray-500">Recent alerts about application queries and updates</p>
        </div>
        @if(auth()->user()->unreadNotifications->count() > 0)
        <button onclick="markAllRead()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-check-double mr-1"></i> Mark All Read
        </button>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100">
        @forelse($notifications as $notif)
        @php
            $data = $notif->data;
            $appUrl = isset($data['application_id']) ? route($prefix . '.applications.show', $data['application_id']) : '#';
        @endphp
        <div class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 transition {{ $notif->read_at ? '' : 'bg-blue-50' }}">
            <div class="mt-0.5 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                        {{ ($data['type'] ?? '') === 'query' ? 'bg-orange-100' : 'bg-blue-100' }}">
                <i class="fas {{ ($data['type'] ?? '') === 'query' ? 'fa-question-circle text-orange-600' : 'fa-reply text-blue-600' }} text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <p class="text-sm font-semibold text-gray-800">
                        {{ ($data['type'] ?? '') === 'query' ? 'New Query' : 'Query Reply' }}
                        — {{ $data['tender_title'] ?? '' }}
                    </p>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notif->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-sm text-gray-600 mt-0.5">
                    <span class="font-medium">{{ $data['sender_name'] ?? '' }}</span>:
                    {{ $data['message_preview'] ?? '' }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    App #{{ $data['application_number'] ?? '' }} · Tender #{{ $data['tender_number'] ?? '' }}
                </p>
                <a href="{{ $appUrl }}" class="mt-2 inline-flex items-center gap-1.5 text-xs text-blue-600 hover:underline font-medium">
                    <i class="fas fa-external-link-alt"></i> View Application
                </a>
            </div>
            @if(!$notif->read_at)
            <span class="w-2.5 h-2.5 bg-blue-500 rounded-full flex-shrink-0 mt-1.5"></span>
            @endif
        </div>
        @empty
        <div class="py-20 text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bell text-2xl text-gray-300"></i>
            </div>
            <p class="text-gray-500 font-medium">No notifications yet</p>
        </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>

<script>
function markAllRead() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/my/notifications/mark-all-read', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf }
    }).then(() => location.reload());
}
</script>
@endsection
