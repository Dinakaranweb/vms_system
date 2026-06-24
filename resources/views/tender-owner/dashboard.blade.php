@extends('layouts.dashboard')
@section('title', 'Tender Owner Dashboard')
@section('header', 'My Tenders Dashboard')

@section('content')

@php
  $monthLabels  = collect($appMonthly)->pluck('label')->toJson();
  $appCounts    = collect($appMonthly)->pluck('count')->toJson();
  $topNames     = $topTenders->map(fn($t) => Str::limit($t->title, 22))->toJson();
  $topCounts    = $topTenders->pluck('applications_count')->toJson();
@endphp

<style>
.to-wrap *{ box-sizing:border-box; }
.to-wrap{ padding:4px 0 24px; }

.stat-grid{ display:grid;gap:16px;margin-bottom:24px; }
.sg-5{ grid-template-columns:repeat(5,1fr); }
.sg-4{ grid-template-columns:repeat(4,1fr); }
@media(max-width:1100px){ .sg-5{ grid-template-columns:repeat(3,1fr); } .sg-4{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:680px){ .sg-5,.sg-4{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:460px){ .sg-5,.sg-4{ grid-template-columns:1fr; } }

.stat-card{
    background:#fff; border:1px solid #e2e8f0; border-radius:16px;
    padding:18px 20px; display:flex; align-items:center; gap:14px;
    position:relative; overflow:hidden; transition:transform .2s,box-shadow .2s;
}
.stat-card:hover{ transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.08); }
.stat-card::before{ content:'';position:absolute;left:0;top:0;width:4px;height:100%;border-radius:4px 0 0 4px; }
.sc-blue::before  { background:#3b82f6; }
.sc-green::before { background:#10b981; }
.sc-amber::before { background:#f59e0b; }
.sc-red::before   { background:#ef4444; }
.sc-purple::before{ background:#8b5cf6; }
.sc-cyan::before  { background:#06b6d4; }
.stat-ico{ width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0; }
.sc-blue   .stat-ico{ background:#dbeafe;color:#2563eb; }
.sc-green  .stat-ico{ background:#d1fae5;color:#059669; }
.sc-amber  .stat-ico{ background:#fef3c7;color:#d97706; }
.sc-red    .stat-ico{ background:#fee2e2;color:#dc2626; }
.sc-purple .stat-ico{ background:#ede9fe;color:#7c3aed; }
.sc-cyan   .stat-ico{ background:#cffafe;color:#0891b2; }
.stat-lbl{ font-size:10.5px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:#94a3b8;margin-bottom:4px; }
.stat-val{ font-size:26px;font-weight:700;color:#0f172a;line-height:1; }
.stat-sub{ font-size:10.5px;color:#94a3b8;margin-top:3px; }

.chart-grid{ display:grid;gap:20px;margin-bottom:24px; }
.cg-2{ grid-template-columns:1fr 1fr; }
.cg-31{ grid-template-columns:2fr 1fr; }
@media(max-width:900px){ .cg-2,.cg-31{ grid-template-columns:1fr; } }

.chart-card{ background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05); }
.chart-hd{ padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between; }
.chart-hd-title{ font-size:13.5px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px; }
.chart-hd-sub{ font-size:11.5px;color:#94a3b8;margin-top:2px; }
.chart-body{ padding:18px 20px; }

.section-hd{ font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.7px;margin:0 0 12px; }

/* tenders table */
.tbl-card{ background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.05); }
.tbl-hd{ padding:16px 24px;background:linear-gradient(135deg,#2563eb,#4f46e5);display:flex;align-items:center;justify-content:space-between; }
.tbl-title{ font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:8px; }
.tbl-wrap{ overflow-x:auto; }
table.to-table{ width:100%;border-collapse:collapse; }
table.to-table thead{ background:#f8fafc;border-bottom:1px solid #e2e8f0; }
table.to-table thead th{ padding:11px 18px;font-size:11px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:#94a3b8;white-space:nowrap;text-align:left; }
table.to-table thead th:first-child{ padding-left:24px; }
table.to-table thead th:last-child{ padding-right:24px; }
table.to-table tbody tr{ border-bottom:1px solid #f1f5f9;transition:background .15s; }
table.to-table tbody tr:last-child{ border-bottom:none; }
table.to-table tbody tr:hover{ background:#f8faff; }
table.to-table tbody td{ padding:13px 18px;vertical-align:middle; }
table.to-table tbody td:first-child{ padding-left:24px; }
table.to-table tbody td:last-child{ padding-right:24px; }
.tbl-ft{ padding:12px 24px;background:#f8fafc;border-top:1px solid #e2e8f0; }

.legend-row{ display:flex;align-items:center;gap:8px;font-size:12px;padding:4px 0; }
.legend-dot{ width:10px;height:10px;border-radius:2px;flex-shrink:0;display:inline-block; }
</style>

<div class="to-wrap">

{{-- ═══ ROW 1 – 5 stat cards ═══ --}}
<p class="section-hd"><i class="fas fa-tachometer-alt mr-2"></i>Overview</p>
<div class="stat-grid sg-5">
    <div class="stat-card sc-blue">
        <div class="stat-ico"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="stat-lbl">My Tenders</div>
            <div class="stat-val">{{ $stats['total'] }}</div>
            <div class="stat-sub">{{ $onlineCount }} online · {{ $postalCount }} postal</div>
        </div>
    </div>
    <div class="stat-card sc-green">
        <div class="stat-ico"><i class="fas fa-rocket"></i></div>
        <div>
            <div class="stat-lbl">Published</div>
            <div class="stat-val">{{ $stats['published'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-ico"><i class="fas fa-pencil-alt"></i></div>
        <div>
            <div class="stat-lbl">Draft</div>
            <div class="stat-val">{{ $stats['draft'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-red">
        <div class="stat-ico"><i class="fas fa-lock"></i></div>
        <div>
            <div class="stat-lbl">Closed</div>
            <div class="stat-val">{{ $stats['closed'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-purple">
        <div class="stat-ico"><i class="fas fa-inbox"></i></div>
        <div>
            <div class="stat-lbl">Applications</div>
            <div class="stat-val">{{ $stats['total_applications'] }}</div>
            <div class="stat-sub">{{ $appStats['awarded'] }} awarded</div>
        </div>
    </div>
</div>

{{-- ═══ ROW 2 – 4 application status mini-cards ═══ --}}
<p class="section-hd"><i class="fas fa-inbox mr-2"></i>Application Breakdown</p>
<div class="stat-grid sg-4">
    <div class="stat-card sc-amber">
        <div class="stat-ico"><i class="fas fa-clock"></i></div>
        <div><div class="stat-lbl">Submitted</div><div class="stat-val">{{ $appStats['submitted'] }}</div></div>
    </div>
    <div class="stat-card sc-blue">
        <div class="stat-ico"><i class="fas fa-star"></i></div>
        <div><div class="stat-lbl">Shortlisted</div><div class="stat-val">{{ $appStats['shortlisted'] }}</div></div>
    </div>
    <div class="stat-card sc-red">
        <div class="stat-ico"><i class="fas fa-times-circle"></i></div>
        <div><div class="stat-lbl">Rejected</div><div class="stat-val">{{ $appStats['rejected'] }}</div></div>
    </div>
    <div class="stat-card sc-purple">
        <div class="stat-ico"><i class="fas fa-trophy"></i></div>
        <div><div class="stat-lbl">Awarded</div><div class="stat-val">{{ $appStats['awarded'] }}</div></div>
    </div>
</div>

{{-- ═══ ROW 3 – Charts: Line + Top Tenders Bar ═══ --}}
<p class="section-hd"><i class="fas fa-chart-line mr-2"></i>Analytics</p>
<div class="chart-grid cg-2">

    {{-- Applications over time --}}
    <div class="chart-card">
        <div class="chart-hd">
            <div>
                <div class="chart-hd-title"><i class="fas fa-chart-line" style="color:#4f46e5;"></i> Applications Over Time</div>
                <div class="chart-hd-sub">Last 6 months across all your tenders</div>
            </div>
        </div>
        <div class="chart-body">
            <div style="position:relative;height:210px;">
                <canvas id="appLineChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top tenders by applications --}}
    <div class="chart-card">
        <div class="chart-hd">
            <div>
                <div class="chart-hd-title"><i class="fas fa-chart-bar" style="color:#10b981;"></i> Top Tenders by Applications</div>
                <div class="chart-hd-sub">Top 5 tenders</div>
            </div>
        </div>
        <div class="chart-body">
            <div style="position:relative;height:210px;">
                <canvas id="topTendersBar"></canvas>
            </div>
        </div>
    </div>

</div>

{{-- ═══ ROW 4 – Tender Status Donut + Online/Postal Donut ═══ --}}
<div class="chart-grid cg-2" style="margin-bottom:24px;">

    {{-- Tender status donut --}}
    <div class="chart-card">
        <div class="chart-hd">
            <div>
                <div class="chart-hd-title"><i class="fas fa-circle-notch" style="color:#3b82f6;"></i> Tender Status Distribution</div>
            </div>
        </div>
        <div class="chart-body" style="display:flex;align-items:center;gap:32px;">
            <div style="position:relative;height:180px;width:180px;flex-shrink:0;">
                <canvas id="tenderStatusDonut"></canvas>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;flex:1;">
                <div class="legend-row"><span class="legend-dot" style="background:#fbbf24;"></span> Draft <span style="margin-left:auto;font-weight:700;font-size:14px;">{{ $stats['draft'] }}</span></div>
                <div class="legend-row"><span class="legend-dot" style="background:#34d399;"></span> Published <span style="margin-left:auto;font-weight:700;font-size:14px;">{{ $stats['published'] }}</span></div>
                <div class="legend-row"><span class="legend-dot" style="background:#f87171;"></span> Closed <span style="margin-left:auto;font-weight:700;font-size:14px;">{{ $stats['closed'] }}</span></div>
                <div style="border-top:1px solid #f1f5f9;padding-top:10px;margin-top:2px;">
                    <div class="legend-row" style="font-weight:600;"><span>Total</span><span style="margin-left:auto;font-size:15px;font-weight:800;">{{ $stats['total'] }}</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Online vs Postal donut --}}
    <div class="chart-card">
        <div class="chart-hd">
            <div>
                <div class="chart-hd-title"><i class="fas fa-circle-notch" style="color:#06b6d4;"></i> Tender Type Split</div>
            </div>
        </div>
        <div class="chart-body" style="display:flex;align-items:center;gap:32px;">
            <div style="position:relative;height:180px;width:180px;flex-shrink:0;">
                <canvas id="tenderTypeDonut"></canvas>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;flex:1;">
                <div class="legend-row"><span class="legend-dot" style="background:#60a5fa;"></span> Online <span style="margin-left:auto;font-weight:700;font-size:14px;">{{ $onlineCount }}</span></div>
                <div class="legend-row"><span class="legend-dot" style="background:#fbbf24;"></span> Postal <span style="margin-left:auto;font-weight:700;font-size:14px;">{{ $postalCount }}</span></div>
                <div style="border-top:1px solid #f1f5f9;padding-top:10px;margin-top:2px;">
                    <div class="legend-row" style="font-weight:600;"><span>Total</span><span style="margin-left:auto;font-size:15px;font-weight:800;">{{ $stats['total'] }}</span></div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ═══ ROW 5 – My Tenders Table ═══ --}}
<p class="section-hd"><i class="fas fa-list mr-2"></i>My Tenders</p>
<div class="tbl-card">
    <div class="tbl-hd">
        <span class="tbl-title"><i class="fas fa-list"></i> My Tenders</span>
        <a href="{{ route('tender-owner.tenders.create') }}"
           style="background:rgba(255,255,255,.2);color:#fff;font-size:12px;font-weight:600;padding:7px 14px;border-radius:8px;text-decoration:none;border:1px solid rgba(255,255,255,.3);transition:background .15s;"
           onmouseover="this.style.background='rgba(255,255,255,.3)'" onmouseout="this.style.background='rgba(255,255,255,.2)'">
            <i class="fas fa-plus mr-1"></i> Create New Tender
        </a>
    </div>
    <div class="tbl-wrap">
        <table class="to-table">
            <thead>
                <tr>
                    <th>Tender No.</th>
                    <th>Title</th>
                    <th>Committee</th>
                    <th>Applications</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenders as $tender)
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:#0f172a;">{{ $tender->tender_number }}</td>
                    <td style="font-size:13px;color:#0f172a;">{{ Str::limit($tender->title, 38) }}</td>
                    <td>
                        <a href="{{ route('tender-owner.committee', $tender->id) }}" style="font-size:13px;color:#7c3aed;text-decoration:none;font-weight:500;">
                            <i class="fas fa-users mr-1"></i>{{ $tender->committeeMembers->count() }} Members
                        </a>
                    </td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:5px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:600;padding:3px 10px;border-radius:999px;">
                            <i class="fas fa-inbox" style="font-size:10px;"></i>
                            {{ $tender->applications_count }}
                        </span>
                    </td>
                    <td>
                        @if($tender->status == 'published')
                            <span style="background:#d1fae5;color:#065f46;font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;">Published</span>
                        @elseif($tender->status == 'draft')
                            <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;">Draft</span>
                        @else
                            <span style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;">Closed</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:#64748b;">{{ $tender->created_at->format('d M, Y') }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="{{ route('tender-owner.tenders.show', $tender->id) }}" style="width:30px;height:30px;border-radius:7px;border:1px solid #bae6fd;background:#fff;display:flex;align-items:center;justify-content:center;color:#0284c7;font-size:13px;" title="View"><i class="fas fa-eye"></i></a>
                            @if($tender->status == 'draft')
                            <a href="{{ route('tender-owner.tenders.edit', $tender->id) }}" style="width:30px;height:30px;border-radius:7px;border:1px solid #a7f3d0;background:#fff;display:flex;align-items:center;justify-content:center;color:#059669;font-size:13px;" title="Edit"><i class="fas fa-edit"></i></a>
                            <button onclick="publishTender({{ $tender->id }})" style="width:30px;height:30px;border-radius:7px;border:1px solid #ddd6fe;background:#fff;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:13px;cursor:pointer;" title="Publish"><i class="fas fa-rocket"></i></button>
                            @endif
                            @if($tender->status == 'published')
                            <button onclick="closeTender({{ $tender->id }})" style="width:30px;height:30px;border-radius:7px;border:1px solid #fed7aa;background:#fff;display:flex;align-items:center;justify-content:center;color:#ea580c;font-size:13px;cursor:pointer;" title="Close"><i class="fas fa-lock"></i></button>
                            @endif
                            <button onclick="deleteTender({{ $tender->id }})" style="width:30px;height:30px;border-radius:7px;border:1px solid #fecaca;background:#fff;display:flex;align-items:center;justify-content:center;color:#dc2626;font-size:13px;cursor:pointer;" title="Delete"><i class="fas fa-trash"></i></button>
                            <a href="{{ route('tender-owner.committee', $tender->id) }}" style="width:30px;height:30px;border-radius:7px;border:1px solid #ddd6fe;background:#fff;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:13px;" title="Committee"><i class="fas fa-users"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:50px 20px;text-align:center;color:#94a3b8;">
                        <i class="fas fa-inbox" style="font-size:36px;display:block;margin-bottom:10px;opacity:.4;"></i>
                        No tenders yet.
                        <a href="{{ route('tender-owner.tenders.create') }}" style="display:block;margin-top:8px;color:#4f46e5;font-weight:600;">Create your first tender →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="tbl-ft">
        {{ $tenders->links() }}
    </div>
</div>

</div>{{-- /.to-wrap --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const CSRF = '{{ csrf_token() }}';
Chart.defaults.font.family = "'Sora', sans-serif";
Chart.defaults.plugins.legend.display = false;

const monthLabels = {!! $monthLabels !!};
const appCounts   = {!! $appCounts !!};
const topNames    = {!! $topNames !!};
const topCounts   = {!! $topCounts !!};

// Applications over time – Line
new Chart(document.getElementById('appLineChart'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Applications',
            data: appCounts,
            fill: true,
            tension: 0.45,
            borderColor: '#4f46e5',
            backgroundColor: '#4f46e522',
            pointBackgroundColor: '#4f46e5',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2.5,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { tooltip: { mode: 'index', intersect: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }
        }
    }
});

// Top Tenders – Horizontal Bar
new Chart(document.getElementById('topTendersBar'), {
    type: 'bar',
    data: {
        labels: topNames,
        datasets: [{
            label: 'Applications',
            data: topCounts,
            backgroundColor: ['#60a5fa','#34d399','#fbbf24','#f87171','#a78bfa'],
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} applications` } } },
        scales: {
            x: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
            y: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// Tender Status Donut
new Chart(document.getElementById('tenderStatusDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Draft', 'Published', 'Closed'],
        datasets: [{
            data: [{{ $stats['draft'] }}, {{ $stats['published'] }}, {{ $stats['closed'] }}],
            backgroundColor: ['#fbbf24','#34d399','#f87171'],
            borderWidth: 3, borderColor: '#fff', hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } }
    }
});

// Tender Type Donut
new Chart(document.getElementById('tenderTypeDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Online', 'Postal'],
        datasets: [{
            data: [{{ $onlineCount }}, {{ $postalCount }}],
            backgroundColor: ['#60a5fa','#fbbf24'],
            borderWidth: 3, borderColor: '#fff', hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } }
    }
});

// ── Tender actions ──
function publishTender(id) {
    if (confirm('Publish this tender? Vendors will be able to apply.')) {
        fetch(`/tender-owner/tenders/${id}/publish`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
        }).then(r => r.json()).then(d => {
            if (d.success) { alert('Tender published!'); location.reload(); }
            else alert('Failed: ' + d.message);
        });
    }
}
function closeTender(id) {
    if (confirm('Close this tender? No more applications will be accepted.')) {
        fetch(`/tender-owner/tenders/${id}/close`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
        }).then(r => r.json()).then(d => {
            if (d.success) { alert('Tender closed!'); location.reload(); }
            else alert('Failed: ' + d.message);
        });
    }
}
function deleteTender(id) {
    if (confirm('Delete this tender? This cannot be undone.')) {
        fetch(`/tender-owner/tenders/${id}`, {
            method: 'DELETE', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
        }).then(r => r.json()).then(d => {
            if (d.success) { alert('Tender deleted!'); location.reload(); }
            else alert('Failed: ' + d.message);
        });
    }
}
</script>
@endsection
