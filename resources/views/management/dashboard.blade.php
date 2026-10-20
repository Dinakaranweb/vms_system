@extends('layouts.dashboard')

@section('title', 'Management Dashboard')
@section('header', 'Management Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Welcome banner --}}
    <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-2xl px-6 py-5 text-white shadow-md">
        <p class="text-slate-300 text-sm mb-1">Welcome back,</p>
        <h2 class="text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="text-slate-300 text-sm mt-1">Read-only oversight of every published and closed tender &nbsp;·&nbsp; {{ now()->format('d M, Y') }}</p>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach([
            ['label'=>'All Tenders',   'value'=>$stats['total'],     'icon'=>'fa-layer-group',  'color'=>'slate'],
            ['label'=>'Published',     'value'=>$stats['published'], 'icon'=>'fa-bullhorn',     'color'=>'green'],
            ['label'=>'Closed',        'value'=>$stats['closed'],    'icon'=>'fa-flag-checkered','color'=>'gray'],
            ['label'=>'Online',        'value'=>$stats['online'],    'icon'=>'fa-globe',        'color'=>'blue'],
            ['label'=>'Postal',        'value'=>$stats['postal'],    'icon'=>'fa-envelope',     'color'=>'amber'],
            ['label'=>'Hybrid',        'value'=>$stats['hybrid'] ?? 0, 'icon'=>'fa-shuffle', 'color'=>'purple'],
        ] as $s)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $s['label'] }}</span>
                <span class="w-8 h-8 rounded-lg flex items-center justify-center
                    {{ $s['color']==='slate'  ? 'bg-slate-100 text-slate-600'  :
                      ($s['color']==='green'  ? 'bg-green-100 text-green-600' :
                      ($s['color']==='gray'   ? 'bg-gray-100 text-gray-600'   :
                      ($s['color']==='blue'   ? 'bg-blue-100 text-blue-600'   :
                                               'bg-amber-100 text-amber-600'))) }}">
                    <i class="fas {{ $s['icon'] }} text-sm"></i>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $s['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Recent tenders --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <i class="fas fa-layer-group text-slate-500 text-sm"></i> Recent Tenders
            </h3>
            <a href="{{ route('management.tenders.index') }}" class="text-xs text-slate-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentTenders as $tender)
            <a href="{{ route('management.tenders.show', $tender->id) }}" class="block px-5 py-3.5 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $tender->title }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $tender->tender_number }} &nbsp;·&nbsp; {{ $tender->owner->name ?? 'Unassigned' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $tender->tender_type === 'online' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ ucfirst($tender->tender_type) }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $tender->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($tender->status) }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $tender->applications_count }} applications</span>
                    </div>
                </div>
            </a>
            @empty
            <div class="px-5 py-10 text-center text-gray-400 text-sm">
                <i class="fas fa-layer-group text-3xl text-gray-200 block mb-2"></i>
                No published or closed tenders yet.
            </div>
            @endforelse
        </div>
    </div>

    @include('partials.dashboard-widgets', ['widgetData' => $widgetData, 'hideEmptyState' => true])
</div>
@endsection
