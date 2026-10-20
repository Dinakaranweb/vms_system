@extends('layouts.dashboard')

@section('title', 'Audit Log Details')
@section('header', 'Audit Log Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <a href="{{ route('super-admin.audit-logs') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Audit Logs
    </a>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-700 to-slate-900">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-history mr-2"></i>Log Entry #{{ $log->id }}</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500 uppercase">When</label>
                <p class="text-gray-900 font-medium">{{ $log->created_at->format('d M Y, H:i:s') }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">User</label>
                <p class="text-gray-900 font-medium">{{ $log->user_name ?? 'System' }} <span class="text-gray-400 text-sm">({{ $log->user_email ?? '—' }})</span></p>
                <p class="text-xs text-gray-500">{{ $log->user_role ?? '' }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Action</label>
                <p class="text-gray-900 font-medium">{{ $log->getFormattedAction() }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Module</label>
                <p class="text-gray-900 font-medium">{{ ucfirst($log->module) }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Record</label>
                <p class="text-gray-900 font-medium">{{ $log->record_identifier ?? '—' }} <span class="text-gray-400 text-sm">(#{{ $log->record_id ?? '—' }})</span></p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">IP Address</label>
                <p class="text-gray-900 font-medium">{{ $log->ip_address ?? '—' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500 uppercase">Description</label>
                <p class="text-gray-900">{{ $log->description ?? '—' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500 uppercase">URL</label>
                <p class="text-gray-600 text-sm break-all">{{ $log->method }} {{ $log->url ?? '—' }}</p>
            </div>
            @if($log->old_values || $log->new_values || $log->changes)
            <div class="md:col-span-2">
                <label class="text-xs text-gray-500 uppercase">Data Changes</label>
                <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs overflow-x-auto mt-1">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values, 'changes' => $log->changes], JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
