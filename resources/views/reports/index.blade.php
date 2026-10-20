@extends('layouts.dashboard')

@section('title', 'Reports')
@section('header', 'Tender & Work Order Report')

@section('content')
<div class="space-y-5">

{{-- ── Stats ── --}}
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
    @php
    $cards = [
        ['label'=>'Total Contracts',  'val'=> $stats['total_contracts'],  'color'=>'blue',   'icon'=>'fa-file-contract'],
        ['label'=>'Total Value (₹)',  'val'=> $stats['total_value'] > 0 ? '₹'.number_format($stats['total_value'],0) : '—', 'color'=>'green',  'icon'=>'fa-rupee-sign'],
        ['label'=>'Work Orders',      'val'=> $stats['work_orders'],       'color'=>'indigo', 'icon'=>'fa-hard-hat'],
        ['label'=>'Purchase Orders',  'val'=> $stats['purchase_orders'],   'color'=>'teal',   'icon'=>'fa-shopping-cart'],
        ['label'=>'Partial Orders',   'val'=> $stats['partial_orders'],    'color'=>'amber',  'icon'=>'fa-layer-group'],
        ['label'=>'Active',           'val'=> $stats['active'],            'color'=>'emerald','icon'=>'fa-check-circle'],
        ['label'=>'Draft / Pending',  'val'=> $stats['draft'],             'color'=>'gray',   'icon'=>'fa-clock'],
    ];
    $palettes = [
        'blue'   => ['bg'=>'bg-blue-50',   'border'=>'border-blue-400',   'txt'=>'text-blue-700',   'ico'=>'text-blue-400'],
        'green'  => ['bg'=>'bg-green-50',  'border'=>'border-green-400',  'txt'=>'text-green-700',  'ico'=>'text-green-400'],
        'indigo' => ['bg'=>'bg-indigo-50', 'border'=>'border-indigo-400', 'txt'=>'text-indigo-700', 'ico'=>'text-indigo-400'],
        'teal'   => ['bg'=>'bg-teal-50',   'border'=>'border-teal-400',   'txt'=>'text-teal-700',   'ico'=>'text-teal-400'],
        'amber'  => ['bg'=>'bg-amber-50',  'border'=>'border-amber-400',  'txt'=>'text-amber-700',  'ico'=>'text-amber-400'],
        'emerald'=> ['bg'=>'bg-emerald-50','border'=>'border-emerald-400','txt'=>'text-emerald-700','ico'=>'text-emerald-400'],
        'gray'   => ['bg'=>'bg-gray-50',   'border'=>'border-gray-300',   'txt'=>'text-gray-700',   'ico'=>'text-gray-400'],
    ];
    @endphp
    @foreach($cards as $c)
    @php $p = $palettes[$c['color']]; @endphp
    <div class="{{ $p['bg'] }} border-l-4 {{ $p['border'] }} rounded-lg p-3 flex items-center gap-3">
        <i class="fas {{ $c['icon'] }} {{ $p['ico'] }} text-xl"></i>
        <div>
            <p class="text-xs text-gray-500">{{ $c['label'] }}</p>
            <p class="font-bold {{ $p['txt'] }}">{{ $c['val'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Filters ── --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-gradient-to-r from-slate-600 to-slate-700 flex items-center justify-between">
        <h3 class="text-white font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-filter"></i> Filters
        </h3>
        <button type="button" onclick="document.getElementById('filterForm').reset();window.location='{{ url()->current() }}'"
                class="text-xs text-white/70 hover:text-white">
            <i class="fas fa-times mr-1"></i>Clear
        </button>
    </div>
    <form id="filterForm" method="GET" action="{{ url()->current() }}"
          class="p-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">From Date</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">To Date</label>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 outline-none">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tender</label>
            <select name="tender_id" class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 outline-none">
                <option value="">All Tenders</option>
                @foreach($tenders as $t)
                <option value="{{ $t->id }}" {{ $tenderId == $t->id ? 'selected' : '' }}>
                    {{ $t->tender_number }} – {{ Str::limit($t->title,30) }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor</label>
            <select name="vendor_id" class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 outline-none">
                <option value="">All Vendors</option>
                @foreach($vendors as $v)
                <option value="{{ $v->id }}" {{ $vendorId == $v->id ? 'selected' : '' }}>
                    {{ $v->company_name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Order Type</label>
            <select name="order_type" class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 outline-none">
                <option value="">All Types</option>
                <option value="work_order"     {{ $orderType === 'work_order'     ? 'selected' : '' }}>Work Order</option>
                <option value="purchase_order" {{ $orderType === 'purchase_order' ? 'selected' : '' }}>Purchase Order</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</label>
            <select name="status" class="mt-1 w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 outline-none">
                <option value="">All Statuses</option>
                <option value="draft"      {{ $status === 'draft'      ? 'selected' : '' }}>Draft</option>
                <option value="active"     {{ $status === 'active'     ? 'selected' : '' }}>Active</option>
                <option value="expired"    {{ $status === 'expired'    ? 'selected' : '' }}>Expired</option>
                <option value="terminated" {{ $status === 'terminated' ? 'selected' : '' }}>Terminated</option>
            </select>
        </div>
        <div class="col-span-full flex justify-end gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                <i class="fas fa-search text-xs"></i> Apply
            </button>
            <button type="button" onclick="window.print()"
                    class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition flex items-center gap-1">
                <i class="fas fa-print text-xs"></i> Print
            </button>
        </div>
    </form>
</div>

{{-- ── Main Report Table ── --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-gradient-to-r from-blue-700 to-indigo-700 flex items-center justify-between">
        <h3 class="text-white font-semibold flex items-center gap-2">
            <i class="fas fa-table"></i>
            Tender Applications &amp; Work Orders
            <span class="bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">{{ $contracts->total() }} records</span>
        </h3>
        <button onclick="window.print()"
                class="no-print inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-lg transition">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="reportTable">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Tender No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Tender Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Vendor</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Application No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Contract No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Order No.</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Value (₹)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Order Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Start</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">End / Deadline</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">VMRFDU Ref</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Supplier Ref</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Flags</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Queries</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($contracts as $i => $c)
                @php
                    $appNo    = $c->application->application_number ?? '—';
                    $oType    = $c->order_type === 'purchase_order' ? 'PO' : ($c->order_type === 'work_order' ? 'WO' : '—');
                    $statusColors = [
                        'active'     => 'bg-green-100 text-green-800',
                        'draft'      => 'bg-yellow-100 text-yellow-800',
                        'expired'    => 'bg-red-100 text-red-800',
                        'terminated' => 'bg-orange-100 text-orange-800',
                        'completed'  => 'bg-blue-100 text-blue-800',
                    ];
                    $sc      = $statusColors[$c->status] ?? 'bg-gray-100 text-gray-800';
                    $queries = $c->application ? $c->application->queries : collect([]);
                    $qCount  = $queries->count();
                @endphp
                <tr class="hover:bg-blue-50/30 transition">
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $contracts->firstItem() + $i }}</td>
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-700 whitespace-nowrap">
                        {{ $c->tender->tender_number ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 max-w-xs">
                        <p class="truncate" title="{{ $c->tender->title ?? '' }}">{{ Str::limit($c->tender->title ?? '—', 35) }}</p>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800 whitespace-nowrap">{{ Str::limit($c->vendor->company_name ?? '—', 25) }}</p>
                        <p class="text-xs text-gray-400">{{ $c->vendor->vendor_code ?? '' }}</p>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">{{ $appNo }}</td>
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-700 whitespace-nowrap">
                        {{ $c->contract_number }}
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">{{ $c->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($c->order_type)
                        <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $c->order_type === 'purchase_order' ? 'bg-teal-100 text-teal-800' : 'bg-indigo-100 text-indigo-800' }}">
                            {{ $oType }}
                        </span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800 whitespace-nowrap">
                        @if($c->contract_value > 0)
                        ₹{{ number_format($c->contract_value, 2) }}
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ $c->order_date ? $c->order_date->format('d M Y') : '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ $c->start_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-xs whitespace-nowrap">
                        <span class="{{ $c->end_date->isPast() && !in_array($c->status,['terminated','completed']) ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            {{ $c->end_date->format('d M Y') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ $c->vmrfdu_ref ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ $c->supplier_ref ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc }}">
                            {{ ucfirst($c->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex flex-wrap gap-1">
                            @if($c->is_partial_order)
                            <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded text-xs font-semibold" title="Partial Order">
                                <i class="fas fa-layer-group text-xs"></i> Partial
                            </span>
                            @endif
                            @if($c->signed_by_admin && $c->signed_by_vendor)
                            <span class="px-1.5 py-0.5 bg-green-100 text-green-700 rounded text-xs font-semibold" title="Fully Signed">
                                <i class="fas fa-signature text-xs"></i> Signed
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($qCount > 0)
                        <button onclick="toggleQueries('q{{ $c->id }}')"
                                class="query-toggle-btn inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold hover:bg-purple-200 transition">
                            <i class="fas fa-comments text-xs"></i> {{ $qCount }}
                        </button>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($rp === 'tender-owner')
                        <a href="{{ route('tender-owner.contracts.show', $c->id) }}"
                           class="text-blue-600 hover:text-blue-800 text-xs flex items-center gap-1">
                            <i class="fas fa-eye text-xs"></i> View
                        </a>
                        @else
                        <a href="{{ route("{$rp}.contracts.show", $c->id) }}"
                           class="text-blue-600 hover:text-blue-800 text-xs flex items-center gap-1">
                            <i class="fas fa-eye text-xs"></i> View
                        </a>
                        @endif
                    </td>
                </tr>
                @if($qCount > 0)
                <tr id="q{{ $c->id }}" class="query-expand-row" style="display:none;">
                    <td colspan="18" class="px-6 pb-5 pt-0">
                        <div class="border border-purple-200 rounded-lg overflow-hidden mt-1">
                            <div class="px-4 py-2 bg-purple-50 border-b border-purple-200 flex items-center justify-between">
                                <h4 class="text-xs font-bold text-purple-700 uppercase tracking-wide">
                                    <i class="fas fa-comments mr-1"></i> Query Thread — {{ $qCount }} message{{ $qCount !== 1 ? 's' : '' }}
                                </h4>
                                <span class="text-xs text-gray-500">App: {{ $appNo }}</span>
                            </div>
                            <div class="p-4 space-y-3 bg-white">
                                @foreach($queries as $q)
                                @php
                                    $sr = $q->sender_role ?? 'unknown';
                                    $rcMap = [
                                        'admin'        => 'bg-red-50 border-red-200',
                                        'super-admin'  => 'bg-red-50 border-red-200',
                                        'tender-owner' => 'bg-purple-50 border-purple-200',
                                        'vendor'       => 'bg-green-50 border-green-200',
                                    ];
                                    $badgeMap = [
                                        'admin'        => 'bg-red-100 text-red-700',
                                        'super-admin'  => 'bg-red-100 text-red-700',
                                        'tender-owner' => 'bg-purple-100 text-purple-700',
                                        'vendor'       => 'bg-green-100 text-green-700',
                                    ];
                                    $rc = $rcMap[$sr] ?? 'bg-gray-50 border-gray-200';
                                    $bc = $badgeMap[$sr] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <div class="border rounded-lg {{ $rc }} overflow-hidden">
                                    <div class="px-3 py-2 flex items-center justify-between border-b border-current/10 bg-white/60">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-gray-800">{{ $q->sender->name ?? 'Unknown' }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $bc }}">
                                                {{ ucfirst($sr === 'super-admin' ? 'admin' : str_replace('-', ' ', $sr)) }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ $q->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div class="px-3 py-2 text-sm text-gray-700 prose prose-sm max-w-none" style="white-space: pre-wrap;">
                                        {{ $q->message }}
                                    </div>
                                    @if($q->attachment_path)
                                    <div class="px-3 py-2 bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                                        <i class="fas fa-paperclip text-gray-400 text-xs"></i>
                                        <a href="{{ Storage::url($q->attachment_path) }}" target="_blank"
                                           class="text-xs text-blue-600 hover:underline font-medium">
                                            {{ $q->attachment_name ?? 'Attachment' }}
                                        </a>
                                        <a href="{{ Storage::url($q->attachment_path) }}" download="{{ $q->attachment_name ?? 'attachment' }}"
                                           class="text-xs text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="18" class="px-6 py-16 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3 block"></i>
                        No records found for the selected filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($contracts->hasPages())
    <div class="px-5 py-4 bg-gray-50 border-t">
        {{ $contracts->links() }}
    </div>
    @endif
</div>

</div>
@endsection

@push('styles')
<style>
@media print {
    .no-print, .nav-sidebar, header, .topbar, form, .pagination { display:none !important; }
    body { font-size:11px; }
    #reportTable th, #reportTable td { padding:4px 6px !important; font-size:10px !important; }
    .rounded-xl { border-radius:0 !important; }
    .shadow-sm { box-shadow:none !important; }
    .query-expand-row { display:table-row !important; }
    .query-toggle-btn { display:none !important; }
}
</style>
@endpush

@push('scripts')
<script>
function toggleQueries(id) {
    var row = document.getElementById(id);
    row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
}
</script>
@endpush
