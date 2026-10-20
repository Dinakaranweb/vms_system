@extends('layouts.dashboard')

@section('title', 'Shared Documents — ' . $tender->title)
@section('header', 'Shared Documents')

@section('content')
@php
    $totalFiles = $bundles->sum(fn($b) => count($b['files']));
    $awaiting   = $bundles->filter(fn($b) => $b['awaiting_postal_entry'])->count();
@endphp

<div class="max-w-5xl mx-auto space-y-4">

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('purchase-committee.tenders.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Back to All Tenders
        </a>
        <div class="flex items-center gap-2">
            @if($tender->hasPostalBids())
            <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}" class="text-sm text-emerald-700 border border-emerald-200 bg-emerald-50 px-4 py-2 rounded-lg hover:bg-emerald-100 inline-flex items-center gap-1.5">
                <i class="fas fa-file-arrow-up text-xs"></i> Record Received Docs
            </a>
            @endif
            <a href="{{ route('purchase-committee.review.applications', $tender->id) }}" class="text-sm text-indigo-600 border border-indigo-200 bg-indigo-50 px-4 py-2 rounded-lg hover:bg-indigo-100 inline-flex items-center gap-1.5">
                <i class="fas fa-list text-xs"></i> Applications
            </a>
            <a href="{{ route('purchase-committee.review.compare-bids', $tender->id) }}" class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1.5">
                <i class="fas fa-scale-balanced text-xs"></i> Compare Bids
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 to-purple-700">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-white font-semibold truncate">{{ $tender->title }}</h3>
                    <p class="text-indigo-100 text-xs mt-0.5">
                        {{ $tender->tender_number }} · {{ $tender->getSubmissionModeLabel() }}
                        @if($tender->review_opened_at) · Shared {{ $tender->review_opened_at->format('d M Y, h:i A') }} @endif
                    </p>
                </div>
                <span class="bg-white/15 text-white text-xs px-2.5 py-1 rounded-full border border-white/25 flex-shrink-0">
                    {{ $totalFiles }} file(s)
                </span>
            </div>
        </div>

        <div class="px-6 py-3 bg-indigo-50/60 border-b border-indigo-100">
            <p class="text-xs text-indigo-900">
                <i class="fas fa-circle-info mr-1"></i>
                @if($tender->isHybrid())
                    This is a hybrid tender — both the online submissions and the scans of the physical postal submissions are shared with you below.
                @elseif($tender->isPostal())
                    This is a postal tender — the scans of the physical submissions appear here as they are recorded.
                @else
                    This is an online tender — everything the vendors uploaded through the portal is shared with you below.
                @endif
            </p>
        </div>

        @if($awaiting > 0)
        <div class="px-6 py-3 bg-amber-50 border-b border-amber-100">
            <p class="text-xs text-amber-900">
                <i class="fas fa-hourglass-half mr-1"></i>
                {{ $awaiting }} postal submission(s) have nothing recorded yet.
                <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}" class="underline font-medium">Record what was received</a>
                to upload or scan the papers from the envelope.
            </p>
        </div>
        @endif
    </div>

    {{-- Tender's own documents --}}
    @if($tender->documents->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-folder-open text-gray-400 mr-1.5"></i>Tender Documents</h4>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($tender->documents as $doc)
            <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
               class="px-6 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                <span class="text-sm text-gray-700 truncate">
                    <i class="fas fa-file-lines text-gray-300 mr-2"></i>{{ $doc->title ?? $doc->file_name ?? 'Document' }}
                </span>
                <span class="text-xs text-indigo-600 flex-shrink-0">Open <i class="fas fa-arrow-up-right-from-square ml-0.5"></i></span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Per-vendor submissions --}}
    @if($bundles->isEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 py-16 text-center">
        <i class="fas fa-file-alt text-5xl text-gray-200 block mb-3"></i>
        <p class="text-gray-400">No applications were received for this tender.</p>
    </div>
    @else
        @foreach($bundles as $bundle)
        @php $app = $bundle['application']; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $app->vendor->company_name ?? $app->vendor->name ?? 'Unknown vendor' }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $app->application_number }}
                        · Bid: {{ $app->bid_amount ? '₹' . number_format($app->bid_amount, 2) : 'N/A' }}
                        @if(($bundle['received_count'] ?? 0) > 0)
                        · <span class="text-teal-600">{{ $bundle['received_count'] }} received by post</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('purchase-committee.review.application-show', [$tender->id, $app->id]) }}"
                   class="text-xs text-indigo-600 hover:underline flex-shrink-0">Review <i class="fas fa-chevron-right text-[0.6rem]"></i></a>
            </div>

            @if(empty($bundle['files']))
            <div class="px-6 py-5 text-center">
                <p class="text-xs text-gray-400">
                    @if($bundle['awaiting_postal_entry'])
                        <i class="fas fa-hourglass-half mr-1"></i>Awaiting the scan of this vendor's postal submission.
                    @else
                        <i class="fas fa-circle-minus mr-1"></i>No documents were submitted with this application.
                    @endif
                </p>
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($bundle['files'] as $file)
                @php
                    [$fIcon, $fChip, $fLabel] = match ($file['source']) {
                        'postal'   => ['fa-envelope-open-text text-emerald-400', 'bg-emerald-50 text-emerald-700', 'Postal'],
                        'received' => ['fa-paperclip text-teal-400',             'bg-teal-50 text-teal-700',       'Received'],
                        'scanned'  => ['fa-camera text-teal-500',                'bg-teal-50 text-teal-700',       'Scanned'],
                        default    => ['fa-cloud-arrow-up text-blue-400',        'bg-blue-50 text-blue-700',       'Online'],
                    };
                @endphp
                <a href="{{ asset('storage/' . $file['path']) }}" target="_blank"
                   class="px-6 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                    <span class="text-sm text-gray-700 truncate min-w-0">
                        <i class="fas {{ $fIcon }} mr-2"></i>
                        {{ $file['label'] }}
                        <span class="ml-1.5 text-[0.65rem] px-1.5 py-0.5 rounded {{ $fChip }}">{{ $fLabel }}</span>
                    </span>
                    <span class="text-xs text-indigo-600 flex-shrink-0">Open <i class="fas fa-arrow-up-right-from-square ml-0.5"></i></span>
                </a>
                @endforeach
            </div>
            @endif

            @if($bundle['awaiting_postal_entry'] && !empty($bundle['files']))
            <div class="px-6 py-2.5 bg-amber-50/60 border-t border-amber-100">
                <p class="text-xs text-amber-800"><i class="fas fa-hourglass-half mr-1"></i>The postal part of this submission has not been scanned in yet.</p>
            </div>
            @endif
        </div>
        @endforeach
    @endif
</div>
@endsection
