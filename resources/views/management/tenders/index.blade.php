@extends('layouts.dashboard')

@section('title', 'All Tenders')
@section('header', 'All Tenders')

@section('content')
<div class="max-w-6xl mx-auto space-y-4">

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Title or tender number"
                   class="w-full border border-gray-300 rounded-lg text-sm px-3 py-2">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Type</label>
            <select name="tender_type" class="border border-gray-300 rounded-lg text-sm px-3 py-2">
                <option value="">All Types</option>
                <option value="online" @selected(request('tender_type')==='online')>Online</option>
                <option value="postal" @selected(request('tender_type')==='postal')>Postal</option>
                <option value="hybrid" @selected(request('tender_type')==='hybrid')>Hybrid</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="border border-gray-300 rounded-lg text-sm px-3 py-2">
                <option value="">Published + Closed</option>
                <option value="published" @selected(request('status')==='published')>Published</option>
                <option value="closed" @selected(request('status')==='closed')>Closed</option>
            </select>
        </div>
        <button type="submit" class="px-5 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-900 transition">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        @if(request()->anyFilled(['search','tender_type','status']))
        <a href="{{ route('management.tenders.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-800 to-slate-700 flex items-center justify-between">
            <h3 class="text-white font-semibold flex items-center gap-2">
                <i class="fas fa-layer-group"></i> Tenders
            </h3>
            <span class="bg-white/20 text-white text-sm font-semibold px-3 py-0.5 rounded-full">
                {{ $tenders->total() }} tender(s)
            </span>
        </div>

        @if($tenders->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-layer-group text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No tenders match these filters.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($tenders as $tender)
            <a href="{{ route('management.tenders.show', $tender->id) }}" class="block px-6 py-4 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <p class="font-semibold text-gray-900">{{ $tender->title }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $tender->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($tender->status) }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $tender->tender_type === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ ucfirst($tender->tender_type) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 font-mono">{{ $tender->tender_number }}</p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <i class="fas fa-user text-gray-400"></i> {{ $tender->owner->name ?? 'Unassigned' }}
                            </span>
                            @if($tender->submission_deadline)
                            <span class="flex items-center gap-1">
                                <i class="fas fa-calendar text-gray-400"></i>
                                Deadline: {{ \Carbon\Carbon::parse($tender->submission_deadline)->format('d M, Y') }}
                            </span>
                            @endif
                            @if($tender->estimated_value)
                            <span class="flex items-center gap-1">
                                <i class="fas fa-rupee-sign text-gray-400"></i> {{ $tender->getFormattedEstimatedValue() }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <p class="text-2xl font-bold text-slate-700">{{ $tender->applications_count }}</p>
                        <p class="text-xs text-gray-400">applications</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $tenders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
