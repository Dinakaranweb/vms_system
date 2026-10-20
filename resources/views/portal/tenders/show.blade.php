@extends('layouts.dashboard')

@section('title', $tender->title)
@section('header', 'Tender Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <a href="{{ route('portal.tenders.index') }}" class="text-sm text-slate-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to All Tenders
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $tender->title }}</h2>
                <p class="text-xs text-gray-400 font-mono mt-1">{{ $tender->tender_number }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $tender->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($tender->status) }}</span>
                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $tender->tender_type === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($tender->tender_type) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                <label class="text-xs text-gray-500 uppercase">Description</label>
                <p class="text-gray-700 text-sm mt-1">{{ $tender->description }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <label class="text-xs text-gray-500 uppercase">Tender Owner</label>
                <p class="text-gray-900 text-sm mt-1">{{ $tender->owner->name ?? 'Unassigned' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <label class="text-xs text-gray-500 uppercase">Applications Received</label>
                <p class="text-gray-900 text-sm mt-1">{{ $tender->applications_count }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <label class="text-xs text-gray-500 uppercase">Submission Deadline</label>
                <p class="text-gray-900 text-sm mt-1">{{ $tender->submission_deadline ? \Carbon\Carbon::parse($tender->submission_deadline)->format('d M, Y') : 'N/A' }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <label class="text-xs text-gray-500 uppercase">Created By</label>
                <p class="text-gray-900 text-sm mt-1">{{ $tender->creator->name ?? 'N/A' }}</p>
            </div>
        </div>

        @include('partials.tender-query-thread', ['tender' => $tender])
    </div>
</div>
@endsection
