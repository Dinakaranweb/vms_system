@extends('layouts.dashboard')

@section('title', 'Application #' . $application->application_number)
@section('header', 'Application Details')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
@media print {
    .no-print, .nav-sidebar, header, .topbar { display:none !important; }
    body { font-size:12px; }
    .shadow-sm { box-shadow:none !important; }
    .rounded-xl { border-radius:0 !important; }
}
</style>
@endpush

@section('content')
@php
    $prefix = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin';
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <a href="{{ route($prefix . '.applications') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left"></i> Back to Applications
        </a>
        <div class="flex gap-2">
            <button onclick="window.print()"
                    class="no-print px-4 py-1.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            @if($application->status == 'submitted')
            <button onclick="shortlistThis()" class="px-4 py-1.5 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check mr-1"></i> Shortlist
            </button>
            <button onclick="rejectThis()" class="px-4 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-times mr-1"></i> Reject
            </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-file-alt mr-2"></i> Application #{{ $application->application_number }}
                </h3>
                <span class="text-sm text-blue-100">{{ $application->tender->title ?? '' }}</span>
            </div>
        </div>

        <div class="p-6">
            @include('admin.applications.show')

            @include('partials.application-query-thread')
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const rolePrefix = '{{ $prefix }}';

function shortlistThis() {
    if (!confirm('Shortlist this application?')) return;
    fetch(`/${rolePrefix}/applications/{{ $application->id }}/shortlist`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'Failed');
    });
}

function rejectThis() {
    const reason = prompt('Enter rejection reason:');
    if (!reason?.trim()) return;
    fetch(`/${rolePrefix}/applications/{{ $application->id }}/reject`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason })
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'Failed');
    });
}
</script>
@endpush
