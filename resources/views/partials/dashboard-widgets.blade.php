{{-- Dynamic dashboard widgets — configured per role via Super Admin > Dashboard Widget Builder --}}
{{-- Required: $widgetData (collection of ['widget' => DashboardWidget, 'data' => array]) --}}
{{-- Optional: $widgetSectionTitle (string), $hideEmptyState (bool, default false) --}}
@php $hideEmptyState = $hideEmptyState ?? false; @endphp

@if(($widgetData ?? collect())->isNotEmpty() || !$hideEmptyState)
<div class="space-y-6">
    @if(($widgetData ?? collect())->isNotEmpty())
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest -mb-2">{{ $widgetSectionTitle ?? 'More' }}</h3>
    @endif

    @forelse($widgetData ?? [] as $entry)
        @php [$widget, $data] = [$entry['widget'], $entry['data']]; @endphp

        @if(($data['type'] ?? null) === 'stat_grid')
        <div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ $widget->label }}</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($data['items'] as $s)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $s['label'] }}</span>
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center
                            {{ $s['color']==='blue'   ? 'bg-blue-100 text-blue-600'   :
                              ($s['color']==='green'  ? 'bg-green-100 text-green-600' :
                              ($s['color']==='amber'  ? 'bg-amber-100 text-amber-600' :
                              ($s['color']==='purple' ? 'bg-purple-100 text-purple-600' :
                                                       'bg-gray-100 text-gray-600'))) }}">
                            <i class="fas {{ $s['icon'] }} text-sm"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">{{ $s['value'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @elseif(($data['type'] ?? null) === 'list')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">{{ $data['title'] ?? $widget->label }}</h3>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($data['items'] as $item)
                <a href="{{ isset($item['route']) ? route($item['route']['name'], $item['route']['param']) : '#' }}" class="block px-5 py-3.5 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $item['title'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item['subtitle'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if(!empty($item['badge']))<span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-medium">{{ $item['badge'] }}</span>@endif
                            @if(!empty($item['meta']))<span class="text-xs text-gray-400">{{ $item['meta'] }}</span>@endif
                        </div>
                    </div>
                </a>
                @empty
                <div class="px-5 py-10 text-center text-gray-400 text-sm">No data yet.</div>
                @endforelse
            </div>
        </div>
        @endif
    @empty
        @unless($hideEmptyState)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 py-16 text-center">
            <i class="fas fa-th-large text-4xl text-gray-200 block mb-3"></i>
            <p class="text-gray-400">No dashboard widgets have been configured for your role yet.</p>
            <p class="text-xs text-gray-400 mt-1">A Super Admin can add some from Dashboard Widget Builder.</p>
        </div>
        @endunless
    @endforelse
</div>
@endif
