@extends('layouts.dashboard')

@section('title', 'Committee Dashboard')
@section('header', 'Committee Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Welcome banner --}}
    <div class="bg-gradient-to-r from-indigo-700 to-purple-700 rounded-2xl px-6 py-5 text-white shadow-md">
        <p class="text-indigo-200 text-sm mb-1">Welcome back,</p>
        <h2 class="text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="text-indigo-200 text-sm mt-1">Committee Member &nbsp;·&nbsp; {{ now()->format('d M, Y') }}</p>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label'=>'Assigned Tenders',   'value'=>$stats['assigned_tenders'],   'icon'=>'fa-gavel',       'color'=>'blue'],
            ['label'=>'Active Assignments',  'value'=>$stats['active_tenders'],     'icon'=>'fa-check-circle','color'=>'green'],
            ['label'=>'Pending Review',      'value'=>$stats['pending_review'],     'icon'=>'fa-clock',       'color'=>'amber'],
            ['label'=>'Total Applications',  'value'=>$stats['total_applications'], 'icon'=>'fa-file-alt',    'color'=>'purple'],
        ] as $s)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $s['label'] }}</span>
                <span class="w-8 h-8 rounded-lg flex items-center justify-center
                    {{ $s['color']==='blue'   ? 'bg-blue-100 text-blue-600'   :
                      ($s['color']==='green'  ? 'bg-green-100 text-green-600' :
                      ($s['color']==='amber'  ? 'bg-amber-100 text-amber-600' :
                                               'bg-purple-100 text-purple-600')) }}">
                    <i class="fas {{ $s['icon'] }} text-sm"></i>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $s['value'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Assigned tenders list --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-gavel text-indigo-500 text-sm"></i> My Tenders
                    </h3>
                    <a href="{{ route('committee.tenders') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($tenders as $tender)
                    <div class="px-5 py-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $tender->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $tender->tender_number }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-medium">
                                        {{ ucwords(str_replace('_', ' ', $tender->pivot->role)) }}
                                    </span>
                                    @if(!$tender->pivot->is_active)
                                    <span class="text-xs text-gray-400">(Inactive)</span>
                                    @endif
                                </div>
                            </div>
                            <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $tender->status==='published' ? 'bg-green-100 text-green-700' :
                                  ($tender->status==='draft'     ? 'bg-yellow-100 text-yellow-700' :
                                                                   'bg-gray-100 text-gray-500') }}">
                                {{ ucfirst($tender->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">
                        <i class="fas fa-gavel text-3xl text-gray-200 block mb-2"></i>
                        Not assigned to any tenders yet.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Pending applications --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-clock text-amber-500 text-sm"></i> Pending Review
                        @if($stats['pending_review'] > 0)
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold">
                            {{ $stats['pending_review'] }}
                        </span>
                        @endif
                    </h3>
                    <a href="{{ route('committee.applications') }}" class="text-xs text-indigo-600 hover:underline">View all</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($pendingApplications as $app)
                    <div class="px-5 py-3.5 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $app->vendor->company_name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $app->application_number }} &nbsp;·&nbsp; {{ $app->tender->tender_number }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @if($app->bid_amount !== null && $app->bid_amount > 0)
                                        Bid: ₹{{ number_format($app->bid_amount, 2) }}
                                    @else
                                        Bid: N/A
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">
                                    Submitted
                                </span>
                                <a href="{{ route('committee.applications.show', $app->id) }}"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                    View <i class="fas fa-arrow-right text-xs ml-0.5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">
                        <i class="fas fa-check-circle text-3xl text-green-200 block mb-2"></i>
                        No applications pending review.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
