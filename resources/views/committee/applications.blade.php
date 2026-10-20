@extends('layouts.dashboard')

@section('title', 'Applications to Review')
@section('header', 'Applications to Review')

@section('content')
<div class="max-w-6xl mx-auto space-y-4">

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Tender</label>
                <select name="tender_id" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 focus:border-transparent">
                    <option value="">All Tenders</option>
                    @foreach($myTenders as $t)
                    <option value="{{ $t->id }}" {{ request('tender_id') == $t->id ? 'selected' : '' }}>
                        {{ $t->tender_number }} — {{ Str::limit($t->title, 40) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 focus:border-transparent">
                    <option value="">All Statuses</option>
                    @foreach(['submitted'=>'Submitted','shortlisted'=>'Shortlisted','rejected'=>'Rejected','awarded'=>'Awarded'] as $val=>$label)
                    <option value="{{ $val }}" {{ request('status')===$val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            @if(request('tender_id') || request('status'))
            <a href="{{ route('committee.applications') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg transition">
                Clear
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Applications <span class="text-gray-400 font-normal text-sm">({{ $applications->total() }} total)</span></h3>
        </div>

        @if($applications->isEmpty())
        <div class="py-16 text-center">
            <i class="fas fa-file-alt text-5xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No applications found for your assigned tenders.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Application No.</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tender</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Bid Amount</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Submitted</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($applications as $app)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3.5 font-mono text-xs text-gray-600">{{ $app->application_number }}</td>
                        <td class="px-4 py-3.5">
                            <p class="font-medium text-gray-900">{{ $app->vendor->company_name }}</p>
                            <p class="text-xs text-gray-400">{{ $app->vendor->email }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-xs font-medium text-gray-700">{{ $app->tender->tender_number }}</p>
                            <p class="text-xs text-gray-400">{{ Str::limit($app->tender->title, 35) }}</p>
                        </td>
                        <td class="px-4 py-3.5 text-sm text-gray-700">
                            @if($app->tender && !$app->tender->isBidOpen())
                                <span class="text-gray-400 text-xs"><i class="fas fa-lock mr-1"></i>Sealed</span>
                            @else
                                {{ ($app->bid_amount !== null && $app->bid_amount > 0) ? '₹'.number_format($app->bid_amount, 2) : 'N/A' }}
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            @php
                                $statusMap = ['submitted'=>'bg-amber-100 text-amber-700','shortlisted'=>'bg-blue-100 text-blue-700','rejected'=>'bg-red-100 text-red-700','awarded'=>'bg-green-100 text-green-700'];
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusMap[$app->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($app->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-gray-400">
                            {{ $app->submitted_at ? $app->submitted_at->format('d M, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3.5">
                            <a href="{{ route('committee.applications.show', $app->id) }}"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap">
                                View <i class="fas fa-arrow-right text-xs ml-0.5"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-50">
            {{ $applications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
