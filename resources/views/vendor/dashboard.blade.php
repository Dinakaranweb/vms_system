@extends('layouts.dashboard')

@section('title', 'Vendor Dashboard')
@section('header', 'Dashboard')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap');

    .vd-root {
        font-family: 'DM Sans', sans-serif;
        background: #f5f4f0;
        min-height: 100vh;
        padding: 2rem;
        color: #1a1916;
    }

    /* ─── Hero Banner ─────────────────────────────────────── */
    .vd-hero {
        position: relative;
        background: #111;
        border-radius: 20px;
        padding: 2.5rem 2.75rem;
        overflow: hidden;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .vd-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 55% 70% at -5% 50%, rgba(59,130,246,.45) 0%, transparent 65%),
            radial-gradient(ellipse 40% 60% at 105% 80%, rgba(99,102,241,.35) 0%, transparent 60%),
            linear-gradient(135deg, #0f172a 0%, #111 50%, #0a0a0a 100%);
        pointer-events: none;
    }
    .vd-hero::after {
        content: '';
        position: absolute;
        top: -80px; right: -80px;
        width: 320px; height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99,102,241,.2) 0%, transparent 70%);
        pointer-events: none;
    }
    .vd-hero-content { position: relative; z-index: 1; }
    .vd-hero-eyebrow {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgba(255,255,255,.45);
        margin-bottom: .5rem;
    }
    .vd-hero-name {
        font-family: 'Instrument Serif', serif;
        font-size: 2.1rem;
        color: #fff;
        line-height: 1.15;
        margin-bottom: .35rem;
    }
    .vd-hero-code {
        font-size: 13px;
        color: rgba(255,255,255,.5);
        font-family: 'DM Sans', monospace;
        letter-spacing: .04em;
    }
    .vd-hero-badge-wrap { position: relative; z-index: 1; text-align: right; }
    .vd-hero-label {
        font-size: 10.5px;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: rgba(255,255,255,.4);
        margin-bottom: .5rem;
    }
    .vd-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem 1rem;
        border-radius: 999px;
        font-size: 12.5px;
        font-weight: 600;
    }
    .vd-status-pill.approved {
        background: rgba(34,197,94,.18);
        color: #4ade80;
        border: 1px solid rgba(74,222,128,.25);
    }
    .vd-status-pill.pending {
        background: rgba(251,191,36,.18);
        color: #fbbf24;
        border: 1px solid rgba(251,191,36,.25);
    }
    .vd-status-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
    }
    .vd-status-pill.approved .vd-status-dot { background: #4ade80; box-shadow: 0 0 6px #4ade80; }
    .vd-status-pill.pending  .vd-status-dot { background: #fbbf24; box-shadow: 0 0 6px #fbbf24; }

    /* ─── Stat Cards ─────────────────────────────────────── */
    .vd-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .vd-stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        border: 1px solid rgba(0,0,0,.06);
        position: relative;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .vd-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(0,0,0,.07);
    }
    .vd-stat-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 0 0 16px 16px;
    }
    .vd-stat-card.blue::after   { background: linear-gradient(90deg, #3b82f6, #6366f1); }
    .vd-stat-card.green::after  { background: linear-gradient(90deg, #10b981, #34d399); }
    .vd-stat-card.purple::after { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .vd-stat-card.amber::after  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

    .vd-stat-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        margin-bottom: 1.1rem;
    }
    .vd-stat-card.blue   .vd-stat-icon { background: #eff6ff; color: #3b82f6; }
    .vd-stat-card.green  .vd-stat-icon { background: #f0fdf4; color: #10b981; }
    .vd-stat-card.purple .vd-stat-icon { background: #f5f3ff; color: #8b5cf6; }
    .vd-stat-card.amber  .vd-stat-icon { background: #fffbeb; color: #f59e0b; }

    .vd-stat-label {
        font-size: 12px;
        font-weight: 500;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: .35rem;
    }
    .vd-stat-number {
        font-family: 'Instrument Serif', serif;
        font-size: 2.2rem;
        color: #111;
        line-height: 1;
        margin-bottom: .6rem;
    }
    .vd-stat-sub {
        font-size: 12px;
        color: #9ca3af;
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
    }
    .vd-stat-sub span { display: inline-flex; align-items: center; gap: .25rem; }
    .vd-stat-link {
        font-size: 12.5px;
        font-weight: 500;
        display: inline-flex; align-items: center; gap: .3rem;
        text-decoration: none;
        margin-top: .4rem;
    }
    .vd-stat-card.blue   .vd-stat-link { color: #3b82f6; }
    .vd-stat-card.green  .vd-stat-link { color: #10b981; }
    .vd-stat-card.purple .vd-stat-link { color: #8b5cf6; }
    .vd-stat-card.amber  .vd-stat-link { color: #f59e0b; }

    .vd-stat-link::after {
        content: '→';
        transition: transform .15s ease;
        display: inline-block;
    }
    .vd-stat-link:hover::after { transform: translateX(3px); }

    /* ─── Section Panels ─────────────────────────────────── */
    .vd-panels {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
    }
    @media (max-width: 900px) { .vd-panels { grid-template-columns: 1fr; } }

    .vd-panel {
        background: #fff;
        border-radius: 18px;
        border: 1px solid rgba(0,0,0,.06);
        overflow: hidden;
    }
    .vd-panel-head {
        padding: 1.25rem 1.75rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .vd-panel-title {
        font-size: 14px;
        font-weight: 600;
        color: #111;
        letter-spacing: .01em;
    }
    .vd-panel-count {
        font-size: 11px;
        font-weight: 600;
        padding: .2rem .65rem;
        border-radius: 999px;
        background: #f3f4f6;
        color: #6b7280;
    }
    .vd-panel-foot {
        padding: .9rem 1.75rem;
        border-top: 1px solid #f3f4f6;
        background: #fafaf9;
    }
    .vd-panel-view-link {
        font-size: 13px;
        font-weight: 500;
        color: #6366f1;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: .3rem;
    }
    .vd-panel-view-link::after { content: '→'; transition: transform .15s ease; display: inline-block; }
    .vd-panel-view-link:hover::after { transform: translateX(3px); }

    /* ─── Row Items ──────────────────────────────────────── */
    .vd-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.75rem;
        border-bottom: 1px solid #f9fafb;
        transition: background .15s ease;
    }
    .vd-row:last-child { border-bottom: none; }
    .vd-row:hover { background: #fafaf9; }
    .vd-row-title { font-size: 14px; font-weight: 500; color: #1f2937; margin-bottom: .25rem; }
    .vd-row-sub   { font-size: 12.5px; color: #9ca3af; }

    .vd-badge {
        font-size: 11.5px;
        font-weight: 600;
        padding: .28rem .75rem;
        border-radius: 999px;
        white-space: nowrap;
    }
    .vd-badge.submitted  { background: #fef9c3; color: #a16207; }
    .vd-badge.shortlisted{ background: #dcfce7; color: #166534; }
    .vd-badge.awarded    { background: #ede9fe; color: #5b21b6; }
    .vd-badge.active     { background: #d1fae5; color: #065f46; }
    .vd-badge.other      { background: #f3f4f6; color: #374151; }

    /* ─── Empty State ─────────────────────────────────────── */
    .vd-empty {
        padding: 2.5rem 1.75rem;
        text-align: center;
    }
    .vd-empty-icon {
        font-size: 2rem;
        color: #e5e7eb;
        margin-bottom: .75rem;
    }
    .vd-empty-text { font-size: 13.5px; color: #d1d5db; }
</style>

<div class="vd-root">

    {{-- ── Hero ── --}}
    <div class="vd-hero">
        <div class="vd-hero-content">
            <p class="vd-hero-eyebrow">Vendor Portal</p>
            <h1 class="vd-hero-name">Welcome back, {{ Auth::user()->name }}</h1>
            <p class="vd-hero-code">Vendor Code &nbsp;·&nbsp; {{ Auth::user()->vendor->vendor_code ?? 'N/A' }}</p>
        </div>
        <div class="vd-hero-badge-wrap">
            <p class="vd-hero-label">Account Status</p>
            @if(Auth::user()->vendor->status == 'approved')
                <span class="vd-status-pill approved">
                    <span class="vd-status-dot"></span> Approved
                </span>
            @else
                <span class="vd-status-pill pending">
                    <span class="vd-status-dot"></span> Pending
                </span>
            @endif
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="vd-stats">

        <div class="vd-stat-card blue">
            <div class="vd-stat-icon"><i class="fas fa-gavel"></i></div>
            <p class="vd-stat-label">Open Tenders</p>
            <p class="vd-stat-number" id="stat-open-tenders">{{ $openTenders ?? 0 }}</p>
            <a href="{{ route('vendor.tenders') }}" class="vd-stat-link">Browse Tenders</a>
        </div>

        <div class="vd-stat-card" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-left:4px solid #7c3aed;">
            <div class="vd-stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-lock"></i></div>
            <p class="vd-stat-label" style="color:#5b21b6;">Private Tenders Assigned</p>
            <p class="vd-stat-number" style="color:#6d28d9;">{{ $privateTendersAssigned ?? 0 }}</p>
            <a href="{{ route('vendor.tenders') }}" class="vd-stat-link" style="color:#7c3aed;">View Tenders</a>
        </div>

        <div class="vd-stat-card green">
            <div class="vd-stat-icon"><i class="fas fa-file-alt"></i></div>
            <p class="vd-stat-label">Total Applications</p>
            <p class="vd-stat-number" id="stat-total-applications">{{ $stats['total_applications'] ?? 0 }}</p>
            <div class="vd-stat-sub">
                <span style="color:#d97706;">● Submitted: <span id="stat-submitted">{{ $stats['submitted_applications'] ?? 0 }}</span></span>
                <span style="color:#3b82f6;">● Shortlisted: <span id="stat-shortlisted">{{ $stats['shortlisted_applications'] ?? 0 }}</span></span>
            </div>
        </div>

        <div class="vd-stat-card purple">
            <div class="vd-stat-icon"><i class="fas fa-trophy"></i></div>
            <p class="vd-stat-label">Awarded Contracts</p>
            <p class="vd-stat-number" id="stat-awarded">{{ $stats['awarded_applications'] ?? 0 }}</p>
        </div>

        <div class="vd-stat-card amber">
            <div class="vd-stat-icon"><i class="fas fa-file-contract"></i></div>
            <p class="vd-stat-label">Active Contracts</p>
            <p class="vd-stat-number" id="stat-active-contracts">{{ $stats['active_contracts'] ?? 0 }}</p>
            <div class="vd-stat-sub">
                <span>Total Value: ₹<span id="stat-contract-value">{{ number_format($stats['total_contract_value'] ?? 0, 2) }}</span></span>
            </div>
        </div>

    </div>

    {{-- ── Charts ── --}}
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.5rem;" class="vd-chart-row">

        {{-- Application Trend --}}
        <div class="vd-panel">
            <div class="vd-panel-head">
                <span class="vd-panel-title"><i class="fas fa-chart-line mr-2 text-indigo-500"></i>Applications Over Time</span>
                <span class="vd-panel-count">Last 6 Months</span>
            </div>
            <div style="padding:1.25rem 1.75rem;">
                <canvas id="appTrendChart" height="120"></canvas>
            </div>
        </div>

        {{-- Application Status Donut --}}
        <div class="vd-panel">
            <div class="vd-panel-head">
                <span class="vd-panel-title"><i class="fas fa-chart-pie mr-2 text-purple-500"></i>Application Status</span>
            </div>
            <div style="padding:1.25rem 1.75rem;display:flex;flex-direction:column;align-items:center;gap:1rem;">
                <canvas id="appStatusChart" width="160" height="160" style="max-width:160px;"></canvas>
                <div style="width:100%;font-size:12px;">
                    @php
                        $statusData = [
                            ['label'=>'Submitted',  'val'=>$stats['submitted_applications'],  'color'=>'#d97706'],
                            ['label'=>'Shortlisted','val'=>$stats['shortlisted_applications'], 'color'=>'#3b82f6'],
                            ['label'=>'Awarded',    'val'=>$stats['awarded_applications'],     'color'=>'#7c3aed'],
                        ];
                        $totalApps = $stats['total_applications'] ?: 1;
                    @endphp
                    @foreach($statusData as $sd)
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.4rem;">
                        <span style="display:flex;align-items:center;gap:.4rem;color:#374151;">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $sd['color'] }};display:inline-block;"></span>
                            {{ $sd['label'] }}
                        </span>
                        <span style="font-weight:600;color:#111;">{{ $sd['val'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
    <style>@media(max-width:900px){.vd-chart-row{grid-template-columns:1fr!important;}}</style>

    {{-- ── Panels ── --}}
    <div class="vd-panels">

        {{-- Recent Applications --}}
        <div class="vd-panel">
            <div class="vd-panel-head">
                <span class="vd-panel-title">Recent Applications</span>
                @php $appCount = count($recentApplications ?? []); @endphp
                @if($appCount)
                    <span class="vd-panel-count">{{ $appCount }}</span>
                @endif
            </div>

            @forelse($recentApplications ?? [] as $application)
            <div class="vd-row">
                <div>
                    <p class="vd-row-title">{{ $application->tender->title ?? 'N/A' }}</p>
                    <p class="vd-row-sub">
                    @if($application->bid_amount !== null && $application->bid_amount > 0)
                        Bid: ₹{{ number_format($application->bid_amount, 2) }}
                    @else
                        Bid: N/A
                    @endif
                </p>
                </div>
                @if($application->status == 'submitted')
                    <span class="vd-badge submitted">Submitted</span>
                @elseif($application->status == 'shortlisted')
                    <span class="vd-badge shortlisted">Shortlisted</span>
                @elseif($application->status == 'awarded')
                    <span class="vd-badge awarded">Awarded</span>
                @endif
            </div>
            @empty
            <div class="vd-empty">
                <div class="vd-empty-icon"><i class="fas fa-file-alt"></i></div>
                <p class="vd-empty-text">No applications yet</p>
            </div>
            @endforelse

            <div class="vd-panel-foot">
                <a href="{{ route('vendor.applications') }}" class="vd-panel-view-link">View all applications</a>
            </div>
        </div>

        {{-- Recent Contracts --}}
        <div class="vd-panel">
            <div class="vd-panel-head">
                <span class="vd-panel-title">Recent Contracts</span>
                @php $contractCount = count($recentContracts ?? []); @endphp
                @if($contractCount)
                    <span class="vd-panel-count">{{ $contractCount }}</span>
                @endif
            </div>

            @forelse($recentContracts ?? [] as $contract)
            <div class="vd-row">
                <div>
                    <p class="vd-row-title">{{ $contract->contract_number }}</p>
                    <p class="vd-row-sub">Value: ₹{{ number_format($contract->contract_value, 2) }}</p>
                </div>
                @if($contract->status == 'active')
                    <span class="vd-badge active">Active</span>
                @else
                    <span class="vd-badge other">{{ ucfirst($contract->status) }}</span>
                @endif
            </div>
            @empty
            <div class="vd-empty">
                <div class="vd-empty-icon"><i class="fas fa-file-contract"></i></div>
                <p class="vd-empty-text">No contracts yet</p>
            </div>
            @endforelse

            <div class="vd-panel-foot">
                <a href="{{ route('vendor.contracts') }}" class="vd-panel-view-link">View all contracts</a>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Application trend line chart ──────────────────────────
@php
    $trendLabels = collect($appMonthly)->pluck('label');
    $trendCounts = collect($appMonthly)->pluck('count');
@endphp
(function () {
    const ctx = document.getElementById('appTrendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! $trendLabels->toJson() !!},
            datasets: [{
                label: 'Applications',
                data: {!! $trendCounts->toJson() !!},
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f3f4f6' } },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
})();

// ── Application status donut ──────────────────────────────
(function () {
    const ctx = document.getElementById('appStatusChart');
    if (!ctx) return;
    const vals = [{{ $stats['submitted_applications'] }}, {{ $stats['shortlisted_applications'] }}, {{ $stats['awarded_applications'] }}];
    const total = vals.reduce((a, b) => a + b, 0);
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Submitted', 'Shortlisted', 'Awarded'],
            datasets: [{
                data: total > 0 ? vals : [1],
                backgroundColor: total > 0 ? ['#fbbf24', '#3b82f6', '#7c3aed'] : ['#e5e7eb'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: total > 0 }
            }
        }
    });
})();

// ── Live stats refresh ────────────────────────────────────
function refreshStats() {
    fetch('/vendor/stats', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
    })
    .then(r => r.json())
    .then(d => {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('stat-open-tenders',       d.open_tenders ?? 0);
        set('stat-total-applications', d.total_applications ?? 0);
        set('stat-submitted',          d.submitted_applications ?? 0);
        set('stat-shortlisted',        d.shortlisted_applications ?? 0);
        set('stat-awarded',            d.awarded_applications ?? 0);
        set('stat-active-contracts',   d.active_contracts ?? 0);
        set('stat-contract-value',     Number(d.total_contract_value ?? 0).toLocaleString('en-IN', {minimumFractionDigits:2}));
    })
    .catch(() => {});
}

setInterval(refreshStats, 60000);
</script>
@endsection