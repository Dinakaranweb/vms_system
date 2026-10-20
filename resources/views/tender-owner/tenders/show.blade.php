@extends('layouts.dashboard')

@section('title', 'Tender Details')
@section('header', 'Tender Details')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-white">
                        <i class="fas fa-file-alt mr-2"></i>{{ $tender->tender_number }}
                    </h3>
                    <p class="text-blue-100 text-sm mt-1">Created on: {{ $tender->created_at->format('d M, Y H:i') }}</p>
                </div>
                <div class="flex space-x-2">
                    @if($tender->status == 'draft')
                    <a href="{{ route('tender-owner.tenders.edit', $tender->id) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                        <i class="fas fa-edit mr-2"></i>Edit Tender
                    </a>
                    <button onclick="publishTender({{ $tender->id }})" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-rocket mr-2"></i>Publish
                    </button>
                    @endif
                    @if($tender->status == 'published')
                    <button onclick="closeTender({{ $tender->id }})" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-lock mr-2"></i>Close Tender
                    </button>
                    @endif
                    @if($tender->canBeOpened())
                    <button onclick="openBidding({{ $tender->id }})" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition">
                        <i class="fas fa-envelope-open-text mr-2"></i>Open Tender
                    </button>
                    @elseif($tender->isOpeningOtpPending())
                    <a href="{{ route('purchase-committee.review.opening-codes', $tender->id) }}" class="bg-amber-50 text-amber-700 border border-amber-200 px-4 py-2 rounded-lg text-sm inline-flex items-center hover:bg-amber-100 transition">
                        <i class="fas fa-hourglass-half mr-2"></i>Enter Committee Codes ({{ $tender->openingOtpsVerifiedCount() }}/{{ $tender->openingOtpsIssuedCount() }})
                    </a>
                    @elseif($tender->isBidOpen())
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-4 py-2 rounded-lg text-sm inline-flex items-center">
                        <i class="fas fa-envelope-open-text mr-2"></i>Bids opened {{ $tender->bid_opening_at->format('d M Y, h:i A') }}
                    </span>
                        @if($tender->hasPostalBids())
                        <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}"
                           class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition text-sm inline-flex items-center">
                            <i class="fas fa-file-arrow-up mr-2"></i>Record Received Documents
                        </a>
                        @endif
                        <a href="{{ route('purchase-committee.award.finalize', $tender->id) }}"
                           class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-900 transition text-sm inline-flex items-center">
                            <i class="fas fa-gavel mr-2"></i>Finalize Award
                        </a>
                    @endif
                    <a href="{{ route('tender-owner.dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Status</label>
                    <p>
                        @if($tender->status == 'published')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Published</span>
                        @elseif($tender->status == 'draft')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Draft</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Closed</span>
                        @endif
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Tender Type</label>
                    <p>
                        @if($tender->tender_type == 'online')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Online</span>
                        @else
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Postal</span>
                        @endif
                    </p>
                </div>
                <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Title</label>
                    <p class="font-semibold text-gray-900">{{ $tender->title }}</p>
                </div>
                <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Description</label>
                    <p class="text-gray-700">{{ $tender->description }}</p>
                </div>
                <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                    <label class="text-xs text-gray-500 uppercase">Eligibility Criteria</label>
                    <p class="text-gray-700">{{ $tender->eligibility_criteria }}</p>
                </div>
                @if($tender->require_estimated_value && $tender->estimated_value > 0)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Estimated Value</p>
                    <p class="text-lg font-bold text-gray-900">₹{{ number_format($tender->estimated_value, 2) }}</p>
                </div>
                @endif
                @if($tender->require_emd && $tender->emda_amount > 0)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">EMD Amount</p>
                    <p class="text-lg font-bold text-orange-600">₹{{ number_format($tender->emda_amount, 2) }}</p>
                </div>
                @endif
                @if($tender->start_date)
                <div>
                    <label class="text-xs text-gray-500 uppercase">Start Date</label>
                    <p class="text-gray-900">{{ $tender->start_date->format('d M, Y') }}</p>
                </div>
                @endif
                <div>
                    <label class="text-xs text-gray-500 uppercase">End Date</label>
                    <p class="text-gray-900">{{ $tender->submission_deadline ? date('d M, Y', strtotime($tender->submission_deadline)) : 'N/A' }}</p>
                </div>
                @if($tender->opening_date)
                <div>
                    <label class="text-xs text-gray-500 uppercase">Opening Date</label>
                    <p class="text-gray-900">{{ date('d M, Y', strtotime($tender->opening_date)) }}</p>
                </div>
                @endif
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Created By</p>
                    <p class="text-sm font-medium text-gray-900">{{ $tender->creator->name ?? auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ $tender->created_at->format('d M Y') }}</p>
                </div>
            </div>
            
            <!-- Postal Address -->
            @if($tender->tender_type == 'postal' && $tender->postal_address)
            <div class="mb-6 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <h4 class="font-semibold text-yellow-800 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Postal Submission Address
                </h4>
                <p class="text-gray-700 whitespace-pre-line">{{ $tender->postal_address }}</p>
            </div>
            @endif
            
            <!-- Documents Section -->
            @if($tender->documents && $tender->documents->count() > 0)
            <div class="mb-6">
                <h4 class="font-semibold text-gray-800 mb-3">
                    <i class="fas fa-file-alt mr-2 text-blue-600"></i>Tender Documents ({{ $tender->documents->count() }})
                </h4>
                <div class="space-y-2">
                    @foreach($tender->documents as $doc)
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-file-pdf text-red-500 text-xl"></i>
                            <div>
                                <span class="text-gray-700 font-medium">{{ $doc->file_name }}</span>
                                <div class="text-xs text-gray-500">
                                    {{ $doc->getFileSizeFormatted() }} | {{ ucfirst(str_replace('_', ' ', $doc->document_category)) }}
                                    @if($doc->is_mandatory) <span class="text-red-600 ml-2">Mandatory</span> @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ $doc->getFileUrl() }}" target="_blank" class="text-blue-600 hover:text-blue-800">View</a>
                            <a href="{{ $doc->getFileUrl() }}" download class="text-green-600 hover:text-green-800">Download</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            
            {{-- Eligibility Questions --}}
            @if($tender->eligibilityQuestions && $tender->eligibilityQuestions->count() > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
                <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-clipboard-check"></i> Eligibility Questions
                        <span class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-sm">{{ $tender->eligibilityQuestions->count() }}</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($tender->eligibilityQuestions as $q)
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ $loop->iteration }}. {{ $q->question }}</p>
                                @if($q->description)
                                    <p class="text-sm text-gray-500 mt-0.5">{{ $q->description }}</p>
                                @endif
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium capitalize">{{ str_replace('_', ' ', $q->field_type) }}</span>
                                    @if($q->is_required)
                                        <span class="px-2 py-0.5 bg-red-50 text-red-700 rounded text-xs font-medium">Required</span>
                                    @endif
                                    @if($q->determines_eligibility)
                                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-semibold">Determines Eligibility</span>
                                    @endif
                                </div>
                                @if(in_array($q->field_type, ['select','radio','checkbox']) && $q->options)
                                <div class="mt-2">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($q->options as $opt)
                                            @php
                                                $optLabel = is_array($opt) ? ($opt['label'] ?? '') : (string)$opt;
                                                $optEligible = is_array($opt) ? ($opt['eligible'] ?? false) : in_array($opt, $q->eligible_options ?? []);
                                            @endphp
                                            @if($optLabel)
                                            <span class="px-2 py-0.5 rounded text-xs border
                                                {{ $optEligible ? 'bg-green-50 text-green-700 border-green-200 font-semibold' : 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                                @if($optEligible)<i class="fas fa-check text-green-500 mr-0.5"></i>@endif{{ $optLabel }}
                                            </span>
                                            @endif
                                        @endforeach
                                    </div>
                                    @if($q->determines_eligibility)
                                        <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Green = eligible answers</p>
                                    @endif
                                </div>
                                @endif
                                @if(in_array($q->field_type, ['text','textarea','number']) && $q->determines_eligibility && $q->eligibility_condition)
                                @php $cond = is_array($q->eligibility_condition) ? $q->eligibility_condition : []; @endphp
                                @if(!empty($cond['operator']))
                                @php
                                    $opLabel = match($cond['operator']) {
                                        'gte' => '≥', 'lte' => '≤', 'gt' => '>', 'lt' => '<',
                                        'eq' => '=', 'between' => 'between', default => $cond['operator']
                                    };
                                @endphp
                                <div class="mt-2 p-2 bg-green-50 rounded-lg border border-green-100">
                                    <p class="text-xs text-green-700 font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>Eligible if value is {{ $opLabel }} {{ $cond['value'] ?? '' }}
                                        @if(($cond['operator'] ?? '') === 'between') and {{ $cond['value2'] ?? '' }} @endif
                                    </p>
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Committee Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-gray-800">
                        <i class="fas fa-users mr-2 text-green-600"></i>Evaluation Committee
                    </h4>
                    <a href="{{ route('tender-owner.committee', $tender->id) }}" class="text-green-600 hover:text-green-800 text-sm">
                        <i class="fas fa-users mr-1"></i> Manage Committee
                    </a>
                </div>
                
                @if($tender->committeeMembers && $tender->committeeMembers->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($tender->committeeMembers as $member)
                    <div class="bg-gray-50 rounded-lg px-3 py-2 flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                            {{ substr($member->name, 0, 2) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $member->pivot->role)) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="bg-gray-50 rounded-lg p-6 text-center text-gray-500">
                    <i class="fas fa-users text-3xl mb-2"></i>
                    <p>No committee members added yet.</p>
                </div>
                @endif
            </div>
            
            <!-- Applications Summary -->
            <div class="border-t pt-4">
                <h4 class="font-semibold text-gray-800 mb-3">Applications Summary</h4>
                <div class="grid grid-cols-4 gap-3">
                    <div class="text-center p-2 bg-blue-50 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">{{ $stats['total_applications'] }}</p>
                        <p class="text-xs text-gray-600">Total</p>
                    </div>
                    <div class="text-center p-2 bg-yellow-50 rounded-lg">
                        <p class="text-2xl font-bold text-yellow-600">{{ $stats['submitted_applications'] }}</p>
                        <p class="text-xs text-gray-600">Submitted</p>
                    </div>
                    <div class="text-center p-2 bg-green-50 rounded-lg">
                        <p class="text-2xl font-bold text-green-600">{{ $stats['shortlisted_applications'] }}</p>
                        <p class="text-xs text-gray-600">Shortlisted</p>
                    </div>
                    <div class="text-center p-2 bg-purple-50 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600">{{ $stats['awarded_applications'] }}</p>
                        <p class="text-xs text-gray-600">Awarded</p>
                    </div>
                </div>
            </div>
        </div>

        @if($tender->owner_id === auth()->id() || auth()->user()->hasRole('super-admin'))
            @include('partials.tender-query-thread', ['tender' => $tender])
        @endif
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function publishTender(id) {
    if (confirm('Are you sure you want to publish this tender?')) {
        fetch(`/tender-owner/tenders/${id}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Tender published successfully!');
                  location.reload();
              } else {
                  alert('Failed to publish tender: ' + data.message);
              }
          });
    }
}

function closeTender(id) {
    if (confirm('Are you sure you want to close this tender?')) {
        fetch(`/tender-owner/tenders/${id}/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Tender closed successfully!');
                  location.reload();
              } else {
                  alert('Failed to close tender: ' + data.message);
              }
          });
    }
}

function openBidding(id) {
    if (confirm('Send an opening code to every active Purchase Committee member? Each member receives their code in their own login and reads it out to you in person — you then enter all of them on the next screen, and the tender opens once every code is in.')) {
        fetch(`/tender-owner/tenders/${id}/open-bidding`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert(data.message);
                  /* Straight to the screen where the officer keys in the members' codes. */
                  window.location.href = data.redirect || window.location.href;
              } else {
                  alert('Failed to open bidding: ' + data.message);
              }
          });
    }
}
</script>
@endsection