@extends('layouts.dashboard')

@section('title', 'Tender Analytics')
@section('header', 'Tender Analytics')

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route("{$rp}.tenders") }}" class="hover:text-blue-600">Tenders</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-700 font-medium">{{ Str::limit($tender->title, 50) }}</span>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 font-semibold">View Analytics</span>
    </div>

    {{-- Tender Info Banner --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border-l-4 border-indigo-500">
        <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs text-gray-400 uppercase font-medium">Tender</p>
                <h2 class="text-lg font-bold text-gray-900">{{ $tender->title }}</h2>
                <p class="text-sm text-gray-500 font-mono">{{ $tender->tender_number }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($tender->status === 'published')
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Published</span>
                @elseif($tender->status === 'closed')
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Closed</span>
                @else
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">{{ ucfirst($tender->status) }}</span>
                @endif
                <a href="{{ route("{$rp}.tenders") }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition">
                    <i class="fas fa-arrow-left text-xs"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Total Hits</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_hits']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">All page views</p>
                </div>
                <i class="fas fa-chart-bar text-3xl text-blue-400"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Unique IPs</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['unique_ips']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Distinct visitors</p>
                </div>
                <i class="fas fa-network-wired text-3xl text-purple-400"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Vendors</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['logged_users']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Logged-in vendors</p>
                </div>
                <i class="fas fa-users text-3xl text-green-400"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Today</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['today']) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Views today</p>
                </div>
                <i class="fas fa-calendar-day text-3xl text-orange-400"></i>
            </div>
        </div>
    </div>

    {{-- Chart + Top IPs --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Line Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">
                <i class="fas fa-chart-line text-blue-500 mr-2"></i>Daily Views — Last 30 Days
            </h3>
            <canvas id="viewsChart" height="100"></canvas>
        </div>

        {{-- Top IPs --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">
                <i class="fas fa-fire text-orange-500 mr-2"></i>Top IP Addresses
            </h3>
            @if($topIps->isEmpty())
                <p class="text-sm text-gray-400 italic">No data yet.</p>
            @else
            <div class="space-y-2">
                @foreach($topIps as $ip)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-globe text-gray-300 text-xs"></i>
                        <span class="font-mono text-xs text-gray-700">{{ $ip->ip_address }}</span>
                    </div>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">{{ $ip->hits }} hits</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Detailed View Log --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 flex justify-between items-center">
            <h3 class="text-base font-semibold text-white">
                <i class="fas fa-list mr-2"></i>View Log
                <span class="ml-2 text-indigo-200 font-normal text-sm">({{ number_format($stats['total_hits']) }} total)</span>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Viewer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Browser / OS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($views as $view)
                    @php
                        $browser = \App\Models\TenderView::parseBrowser($view->user_agent);
                        $os      = \App\Models\TenderView::parseOs($view->user_agent);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            {{ $view->viewed_at->format('d M Y') }}
                            <span class="text-gray-400 text-xs ml-1">{{ $view->viewed_at->format('H:i:s') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($view->user)
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-blue-700 text-xs font-semibold">{{ strtoupper(substr($view->user->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 text-xs">{{ $view->user->name }}</p>
                                        <p class="text-gray-400 text-xs">{{ $view->user->email }}</p>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-400 italic text-xs">Anonymous / Public</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-700">{{ $view->ip_address }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <span class="inline-flex items-center gap-1">
                                @php
                                    $browserIcon = match($browser) {
                                        'Chrome'  => 'fab fa-chrome text-yellow-500',
                                        'Firefox' => 'fab fa-firefox-browser text-orange-500',
                                        'Safari'  => 'fab fa-safari text-blue-400',
                                        'Edge'    => 'fab fa-edge text-blue-600',
                                        'Opera'   => 'fab fa-opera text-red-500',
                                        default   => 'fas fa-globe text-gray-400',
                                    };
                                @endphp
                                <i class="{{ $browserIcon }}"></i>
                                {{ $browser }}
                                @if($os) <span class="text-gray-400">· {{ $os }}</span> @endif
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($view->user_id)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">Vendor Portal</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-xs">Public / API</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-chart-bar text-4xl mb-3"></i>
                            <p>No views recorded yet for this tender.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $views->links() }}
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels  = {!! collect($days)->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))->toJson() !!};
const data    = {!! collect($days)->pluck('count')->toJson() !!};
const maxVal  = Math.max(...data, 1);

new Chart(document.getElementById('viewsChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Views',
            data,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#6366f1',
            pointRadius: 3,
            tension: 0.35,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: ctx => ` ${ctx.parsed.y} views` }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                suggestedMax: maxVal + 1,
                ticks: { stepSize: 1, precision: 0 },
                grid: { color: 'rgba(0,0,0,0.04)' }
            },
            x: {
                grid: { display: false },
                ticks: { maxTicksLimit: 10, maxRotation: 0 }
            }
        }
    }
});
</script>
@endpush
@endsection
