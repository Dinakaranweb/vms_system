@extends('layouts.dashboard')

@section('title', 'My Assigned Tenders')
@section('header', 'My Assigned Tenders')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 flex items-center justify-between">
            <h3 class="text-white font-semibold flex items-center gap-2">
                <i class="fas fa-gavel"></i> Assigned Tenders
            </h3>
            <span class="bg-white/20 text-white text-sm font-semibold px-3 py-0.5 rounded-full">
                {{ $tenders->count() }} tender(s)
            </span>
        </div>

        @if($tenders->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-gavel text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">You have not been assigned to any tenders yet.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($tenders as $tender)
            <div class="px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <p class="font-semibold text-gray-900">{{ $tender->title }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $tender->status==='published' ? 'bg-green-100 text-green-700' :
                                  ($tender->status==='draft'     ? 'bg-yellow-100 text-yellow-700' :
                                                                   'bg-gray-100 text-gray-500') }}">
                                {{ ucfirst($tender->status) }}
                            </span>
                            @if(!$tender->pivot->is_active)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-600">Inactive</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 font-mono">{{ $tender->tender_number }}</p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-user-tag text-indigo-400"></i>
                                <span class="font-medium text-indigo-700">{{ ucwords(str_replace('_', ' ', $tender->pivot->role)) }}</span>
                            </span>
                            @if($tender->pivot->designation)
                            <span class="flex items-center gap-1">
                                <i class="fas fa-id-badge text-gray-400"></i> {{ $tender->pivot->designation }}
                            </span>
                            @endif
                            @if($tender->submission_deadline)
                            <span class="flex items-center gap-1">
                                <i class="fas fa-calendar text-gray-400"></i>
                                Deadline: {{ \Carbon\Carbon::parse($tender->submission_deadline)->format('d M, Y') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-2xl font-bold text-indigo-700">{{ $tender->applications_count }}</p>
                        <p class="text-xs text-gray-400">applications</p>
                        <a href="{{ route('committee.applications', ['tender_id' => $tender->id]) }}"
                            class="mt-2 inline-block text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            Review <i class="fas fa-arrow-right text-xs ml-0.5"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
