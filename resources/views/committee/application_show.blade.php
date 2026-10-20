@extends('layouts.dashboard')

@section('title', 'Application — ' . $application->application_number)
@section('header', 'Application Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Back + header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('committee.applications') }}"
            class="text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg px-3 py-1.5 text-sm flex items-center gap-1.5 bg-white">
            <i class="fas fa-arrow-left text-xs"></i> Back
        </a>
        <div class="flex-1">
            <h2 class="text-lg font-bold text-gray-900">{{ $application->application_number }}</h2>
            <p class="text-sm text-gray-400">{{ $application->tender->tender_number }} &nbsp;·&nbsp; {{ $application->tender->title }}</p>
        </div>
        @php
            $statusMap = ['submitted'=>['bg-amber-100 text-amber-700','Submitted'],'shortlisted'=>['bg-blue-100 text-blue-700','Shortlisted'],'rejected'=>['bg-red-100 text-red-700','Rejected'],'awarded'=>['bg-green-100 text-green-700','Awarded']];
            [$cls,$label] = $statusMap[$application->status] ?? ['bg-gray-100 text-gray-600', ucfirst($application->status)];
        @endphp
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $cls }}">{{ $label }}</span>
    </div>

    {{-- Notice: read-only --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700 flex items-center gap-2">
        <i class="fas fa-info-circle"></i>
        You are viewing this application as a committee member. Application decisions are made by the tender owner or administrator.
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Left: Vendor info --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor Information</h4>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Company</p>
                        <p class="font-semibold text-gray-900">{{ $application->vendor->company_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p class="text-sm text-gray-700">{{ $application->vendor->email }}</p>
                    </div>
                    @if($application->vendor->phone)
                    <div>
                        <p class="text-xs text-gray-400">Phone</p>
                        <p class="text-sm text-gray-700">{{ $application->vendor->phone }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Financials</h4>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Bid Amount</p>
                        @if($application->tender && !$application->tender->isBidOpen())
                        <p class="text-sm text-gray-400 font-medium"><i class="fas fa-lock mr-1"></i>Sealed until bids are opened</p>
                        @elseif($application->bid_amount !== null && $application->bid_amount > 0)
                        <p class="text-xl font-bold text-gray-900">₹{{ number_format($application->bid_amount, 2) }}</p>
                        @else
                        <p class="text-sm text-gray-400 font-medium">N/A</p>
                        @endif
                    </div>
                    @if($application->emda_paid_amount)
                    <div>
                        <p class="text-xs text-gray-400">EMD Paid</p>
                        <p class="font-semibold text-gray-700">₹{{ number_format($application->emda_paid_amount, 2) }}</p>
                    </div>
                    @endif
                    @if($application->emda_reference_number)
                    <div>
                        <p class="text-xs text-gray-400">EMD Reference</p>
                        <p class="text-sm text-gray-700">{{ $application->emda_reference_number }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Proposals --}}
        <div class="lg:col-span-2 space-y-4">
            @if($application->technical_proposal)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Technical Proposal</h4>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $application->technical_proposal }}</p>
                </div>
            </div>
            @endif

            @if($application->financial_proposal)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Financial Proposal</h4>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $application->financial_proposal }}</p>
                </div>
            </div>
            @endif

            @if($application->admin_remarks)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-yellow-50">
                    <h4 class="text-xs font-semibold text-yellow-600 uppercase tracking-wide">Admin Remarks</h4>
                </div>
                <div class="px-5 py-4">
                    <p class="text-sm text-gray-700">{{ $application->admin_remarks }}</p>
                </div>
            </div>
            @endif

            @if($application->documents && count($application->documents))
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        Documents ({{ count($application->documents) }})
                    </h4>
                </div>
                <div class="px-5 py-4 space-y-2">
                    @foreach($application->documents as $doc)
                    <div class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                        <i class="fas fa-file-pdf text-red-400"></i>
                        <span class="text-sm text-gray-700 flex-1 truncate">{{ basename($doc) }}</span>
                        <a href="{{ Storage::url($doc) }}" target="_blank"
                            class="text-xs text-indigo-600 hover:underline flex-shrink-0">
                            <i class="fas fa-download mr-0.5"></i> Download
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
