@extends('layouts.dashboard')
@section('title', 'Admin Dashboard')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
.adm-wrap *{ box-sizing:border-box; font-family:'Sora',sans-serif; }
.adm-wrap{ padding:24px 20px; }

/* stat grid */
.stat-grid{ display:grid; gap:16px; margin-bottom:24px; }
.stat-grid-4{ grid-template-columns:repeat(4,1fr); }
.stat-grid-3{ grid-template-columns:repeat(3,1fr); }
@media(max-width:1100px){ .stat-grid-4{ grid-template-columns:repeat(2,1fr); } .stat-grid-3{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:580px){ .stat-grid-4,.stat-grid-3{ grid-template-columns:1fr; } }

.stat-card{
    background:#fff; border:1px solid #e2e8f0; border-radius:16px;
    padding:20px 22px; display:flex; align-items:center; gap:16px;
    position:relative; overflow:hidden; transition:transform .2s,box-shadow .2s; cursor:default;
}
.stat-card:hover{ transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.09); }
.stat-card::before{ content:''; position:absolute; left:0;top:0; width:4px;height:100%; border-radius:4px 0 0 4px; }
.sc-indigo::before { background:#4f46e5; }
.sc-amber::before  { background:#f59e0b; }
.sc-green::before  { background:#10b981; }
.sc-red::before    { background:#ef4444; }
.sc-blue::before   { background:#3b82f6; }
.sc-purple::before { background:#8b5cf6; }
.sc-cyan::before   { background:#06b6d4; }
.stat-ico{ width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
.sc-indigo .stat-ico{ background:#ede9fe;color:#4f46e5; }
.sc-amber  .stat-ico{ background:#fef3c7;color:#d97706; }
.sc-green  .stat-ico{ background:#d1fae5;color:#059669; }
.sc-red    .stat-ico{ background:#fee2e2;color:#dc2626; }
.sc-blue   .stat-ico{ background:#dbeafe;color:#2563eb; }
.sc-purple .stat-ico{ background:#ede9fe;color:#7c3aed; }
.sc-cyan   .stat-ico{ background:#cffafe;color:#0891b2; }
.stat-lbl{ font-size:11px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:#94a3b8;margin-bottom:5px; }
.stat-val{ font-size:28px;font-weight:700;color:#0f172a;line-height:1;font-family:'JetBrains Mono',monospace;letter-spacing:-1px; }
.stat-sub{ font-size:11px;color:#94a3b8;margin-top:4px; }

/* chart grid */
.chart-grid{ display:grid; gap:20px; margin-bottom:24px; }
.chart-grid-2{ grid-template-columns:1fr 1fr; }
.chart-grid-3{ grid-template-columns:2fr 1fr; }
@media(max-width:900px){ .chart-grid-2,.chart-grid-3{ grid-template-columns:1fr; } }

.chart-card{
    background:#fff; border:1px solid #e2e8f0; border-radius:16px;
    overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.05);
}
.chart-hd{
    padding:16px 22px; border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; justify-content:space-between;
}
.chart-hd-title{ font-size:14px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px; }
.chart-hd-sub{ font-size:12px;color:#94a3b8;margin-top:2px; }
.chart-body{ padding:20px; }
.chart-wrap{ position:relative; }

/* section label */
.section-hd{ font-size:13px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.7px;margin:0 0 14px; }

/* pending table */
.adm-card{ background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07); }
.adm-card-hd{ padding:18px 26px;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px; }
.adm-hd-left{ display:flex;align-items:center;gap:14px; }
.adm-hd-ico{ width:40px;height:40px;background:rgba(255,255,255,.13);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px; }
.adm-hd-title{ font-size:14px;font-weight:600;color:#fff;margin:0 0 2px; }
.adm-hd-sub{ font-size:12px;color:rgba(255,255,255,.5);margin:0; }
.adm-badge{ background:rgba(255,255,255,.15);color:#fff;font-size:12px;font-weight:600;padding:4px 14px;border-radius:999px;font-family:'JetBrains Mono',monospace;border:1px solid rgba(255,255,255,.2); }
.adm-tbl-wrap{ overflow-x:auto; }
.adm-table{ width:100%;border-collapse:collapse; }
.adm-table thead{ background:#f8fafc;border-bottom:1px solid #e2e8f0; }
.adm-table thead th{ padding:12px 18px;font-size:11px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:#94a3b8;white-space:nowrap;text-align:left; }
.adm-table thead th:first-child{ padding-left:26px; }
.adm-table thead th:last-child{ padding-right:26px; }
.adm-table tbody tr{ border-bottom:1px solid #f1f5f9;transition:background .15s; }
.adm-table tbody tr:last-child{ border-bottom:none; }
.adm-table tbody tr:hover{ background:#fafaff; }
.adm-table tbody td{ padding:14px 18px;vertical-align:middle; }
.adm-table tbody td:first-child{ padding-left:26px; }
.adm-table tbody td:last-child{ padding-right:26px; }
.adm-chk{ width:16px;height:16px;accent-color:#4f46e5;cursor:pointer; }
.adm-co{ display:flex;align-items:center;gap:12px; }
.adm-avatar{ width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#4f46e5,#818cf8);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0; }
.adm-co-name{ font-size:14px;font-weight:600;color:#0f172a;margin:0 0 3px; }
.adm-co-code{ font-size:11px;color:#94a3b8;font-family:'JetBrains Mono',monospace;background:#f1f5f9;padding:2px 7px;border-radius:4px;border:1px solid #e2e8f0;display:inline-block; }
.adm-email{ font-size:13px;color:#0f172a;margin:0 0 3px; }
.adm-phone{ font-size:12px;color:#94a3b8;margin:0; }
.adm-pan{ font-size:12px;font-family:'JetBrains Mono',monospace;font-weight:500;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;padding:4px 9px;border-radius:6px; }
.adm-acts{ display:flex;gap:6px;align-items:center; }
.adm-act{ width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;transition:all .18s;outline:none; }
.adm-act:hover{ transform:translateY(-2px); }
.adm-act.av{ color:#0284c7;border-color:#bae6fd; } .adm-act.av:hover{ background:#e0f2fe; }
.adm-act.aa{ color:#059669;border-color:#a7f3d0; } .adm-act.aa:hover{ background:#d1fae5; }
.adm-act.ar{ color:#dc2626;border-color:#fecaca; } .adm-act.ar:hover{ background:#fee2e2; }
.adm-empty{ padding:50px 20px;text-align:center; }
.adm-empty-ico{ width:60px;height:60px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;color:#059669;margin:0 auto 14px; }
.adm-empty-t{ font-size:15px;font-weight:600;color:#0f172a;margin:0 0 5px; }
.adm-empty-s{ font-size:13px;color:#94a3b8;margin:0; }
.adm-card-ft{ padding:14px 26px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px; }
.adm-ft-note{ font-size:13px;color:#94a3b8;display:flex;align-items:center;gap:6px; }
.adm-btn{ display:inline-flex;align-items:center;gap:8px;padding:9px 16px;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;transition:all .18s;font-family:'Sora',sans-serif;text-decoration:none; }
.adm-btn-success{ background:#059669;color:#fff; } .adm-btn-success:hover{ background:#047857; }
.adm-btn-danger { background:#dc2626;color:#fff; } .adm-btn-danger:hover { background:#b91c1c; }
.adm-btn-ghost  { background:transparent;color:#475569;border:1px solid #e2e8f0; } .adm-btn-ghost:hover{ background:#f1f5f9; }

/* modals */
.adm-overlay{ position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px; }
.adm-overlay.adm-open{ display:flex; }
.adm-modal{ background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,.2);width:100%;max-width:460px;animation:admSlideUp .25s cubic-bezier(.34,1.56,.64,1);overflow:hidden; }
.adm-modal.adm-wide{ max-width:660px;max-height:88vh;overflow-y:auto; }
@keyframes admSlideUp{ from{ opacity:0;transform:translateY(20px) scale(.97); } to{ opacity:1;transform:none; } }
.adm-m-hd{ padding:20px 22px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:14px; }
.adm-m-hd-ico{ width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
.adm-m-hd-ico.danger{ background:#fee2e2;color:#dc2626; }
.adm-m-hd-ico.info{ background:#e0f2fe;color:#0284c7; }
.adm-m-title{ font-size:15px;font-weight:700;color:#0f172a;margin:0 0 2px; }
.adm-m-desc{ font-size:13px;color:#475569;margin:0; }
.adm-m-body{ padding:20px 22px; }
.adm-m-ft{ padding:12px 22px 18px;display:flex;justify-content:flex-end;gap:10px; }
.adm-sticky-hd{ position:sticky;top:0;z-index:10;background:#fff;border-bottom:1px solid #f1f5f9;padding:18px 22px 14px;display:flex;align-items:center;gap:14px; }
.adm-sticky-ft{ position:sticky;bottom:0;z-index:10;background:#fff;border-top:1px solid #f1f5f9;padding:12px 22px;display:flex;justify-content:flex-end; }
.adm-ta{ width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:12px 14px;font-size:14px;color:#0f172a;font-family:'Sora',sans-serif;resize:vertical;min-height:100px;background:#f8fafc;transition:border-color .18s; }
.adm-ta:focus{ outline:none;border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.1);background:#fff; }
.adm-lbl{ display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:8px; }
.vd-section{ margin-bottom:20px; }
.vd-section-title{ font-size:11px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;color:#94a3b8;margin:0 0 10px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:7px; }
.vd-grid{ display:grid;grid-template-columns:1fr 1fr;gap:12px; }
.vd-field{ display:flex;flex-direction:column;gap:4px; }
.vd-field.vd-full{ grid-column:1/-1; }
.vd-lbl{ font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px; }
.vd-val{ font-size:13px;color:#0f172a;font-weight:500;line-height:1.5; }
.vd-mono{ font-family:'JetBrains Mono',monospace;font-size:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 8px;border-radius:5px;display:inline-block; }
.vd-status{ display:inline-block;font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;text-transform:capitalize; }
.vd-status-pending{ background:#fef3c7;color:#d97706; }
.vd-status-approved{ background:#d1fae5;color:#059669; }
.vd-status-rejected{ background:#fee2e2;color:#dc2626; }
.vd-docs{ display:flex;flex-direction:column;gap:8px; }
.vd-doc-link{ display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:9px;background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;font-size:13px;font-weight:500;text-decoration:none;transition:all .15s; }
.vd-doc-link:hover{ background:#ede9fe;border-color:#c4b5fd;color:#4f46e5; }
.vd-doc-link .vd-doc-ico{ font-size:17px;color:#dc2626;flex-shrink:0; }
.vd-doc-link .vd-doc-ext{ margin-left:auto;font-size:10px;color:#94a3b8; }
.vd-spinner{ text-align:center;padding:36px;color:#94a3b8;font-size:24px; }
.vd-error{ text-align:center;padding:24px;color:#dc2626;font-size:13px; }
@media(max-width:640px){ .adm-hide-sm{ display:none!important; } .adm-wrap{ padding:12px 10px; } }

/* recent tender list */
.tender-row{ display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f1f5f9; }
.tender-row:last-child{ border-bottom:none; }
.t-num{ font-size:11px;font-family:'JetBrains Mono',monospace;background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:5px;border:1px solid #e2e8f0;white-space:nowrap; }
.t-title{ font-size:13px;font-weight:600;color:#0f172a;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.t-badge{ font-size:11px;font-weight:600;padding:3px 9px;border-radius:999px;white-space:nowrap; }
.tb-draft    { background:#fef3c7;color:#b45309; }
.tb-published{ background:#d1fae5;color:#065f46; }
.tb-closed   { background:#fee2e2;color:#991b1b; }
</style>

@php
  $monthLabels   = collect($vendorMonthly)->pluck('label')->toJson();
  $vendorCounts  = collect($vendorMonthly)->pluck('count')->toJson();
  $appCounts     = collect($appMonthly)->pluck('count')->toJson();
@endphp

<div class="adm-wrap">

{{-- ═══ ROW 1 – Vendor Stats ═══ --}}
<p class="section-hd"><i class="fas fa-building mr-2"></i>Vendor Overview</p>
<div class="stat-grid stat-grid-4">
    <div class="stat-card sc-indigo">
        <div class="stat-ico"><i class="fas fa-building"></i></div>
        <div>
            <div class="stat-lbl">Total Vendors</div>
            <div class="stat-val">{{ $stats['total_vendors'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-ico"><i class="fas fa-hourglass-half"></i></div>
        <div>
            <div class="stat-lbl">Pending</div>
            <div class="stat-val">{{ $stats['pending_vendors'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-green">
        <div class="stat-ico"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="stat-lbl">Approved</div>
            <div class="stat-val">{{ $stats['approved_vendors'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-red">
        <div class="stat-ico"><i class="fas fa-times-circle"></i></div>
        <div>
            <div class="stat-lbl">Rejected</div>
            <div class="stat-val">{{ $stats['rejected_vendors'] }}</div>
        </div>
    </div>
</div>

{{-- ═══ ROW 2 – Tender + Application Stats ═══ --}}
<p class="section-hd" style="margin-top:8px;"><i class="fas fa-file-alt mr-2"></i>Tender &amp; Application Overview</p>
<div class="stat-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
    <div class="stat-card sc-blue">
        <div class="stat-ico"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="stat-lbl">Total Tenders</div>
            <div class="stat-val">{{ $tenderStats['total'] }}</div>
            <div class="stat-sub">{{ $tenderStats['online'] }} online · {{ $tenderStats['postal'] }} postal · {{ $tenderStats['hybrid'] ?? 0 }} hybrid</div>
        </div>
    </div>
    <div class="stat-card sc-green">
        <div class="stat-ico"><i class="fas fa-rocket"></i></div>
        <div>
            <div class="stat-lbl">Published</div>
            <div class="stat-val">{{ $tenderStats['published'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-amber">
        <div class="stat-ico"><i class="fas fa-pencil-alt"></i></div>
        <div>
            <div class="stat-lbl">Draft</div>
            <div class="stat-val">{{ $tenderStats['draft'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-red">
        <div class="stat-ico"><i class="fas fa-lock"></i></div>
        <div>
            <div class="stat-lbl">Closed</div>
            <div class="stat-val">{{ $tenderStats['closed'] }}</div>
        </div>
    </div>
    <div class="stat-card sc-purple">
        <div class="stat-ico"><i class="fas fa-inbox"></i></div>
        <div>
            <div class="stat-lbl">Applications</div>
            <div class="stat-val">{{ $appStats['total'] }}</div>
            <div class="stat-sub">{{ $appStats['awarded'] }} awarded</div>
        </div>
    </div>
</div>

{{-- ═══ ROW 3 – Charts: Donut + Bar + Recent ═══ --}}
<div class="chart-grid chart-grid-3" style="margin-bottom:20px;">

    {{-- 2/3 width: Line charts stacked --}}
    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- Vendor Registrations Line --}}
        <div class="chart-card">
            <div class="chart-hd">
                <div>
                    <div class="chart-hd-title"><i class="fas fa-user-plus" style="color:#4f46e5;"></i> Vendor Registrations</div>
                    <div class="chart-hd-sub">Last 6 months</div>
                </div>
            </div>
            <div class="chart-body">
                <div class="chart-wrap" style="height:190px;">
                    <canvas id="vendorLineChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Applications Line --}}
        <div class="chart-card">
            <div class="chart-hd">
                <div>
                    <div class="chart-hd-title"><i class="fas fa-file-signature" style="color:#8b5cf6;"></i> Applications Received</div>
                    <div class="chart-hd-sub">Last 6 months</div>
                </div>
            </div>
            <div class="chart-body">
                <div class="chart-wrap" style="height:190px;">
                    <canvas id="appLineChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- 1/3 width: Donuts stacked --}}
    <div style="display:flex;flex-direction:column;gap:20px;">

        {{-- Vendor Status Donut --}}
        <div class="chart-card" style="flex:1;">
            <div class="chart-hd">
                <div>
                    <div class="chart-hd-title"><i class="fas fa-circle-notch" style="color:#10b981;"></i> Vendor Status</div>
                </div>
            </div>
            <div class="chart-body" style="display:flex;flex-direction:column;align-items:center;">
                <div class="chart-wrap" style="height:160px;width:160px;">
                    <canvas id="vendorDonut"></canvas>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;margin-top:12px;width:100%;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:12px;"><span style="width:10px;height:10px;border-radius:2px;background:#f59e0b;flex-shrink:0;display:inline-block;"></span> Pending <span style="margin-left:auto;font-weight:700;">{{ $stats['pending_vendors'] }}</span></div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:12px;"><span style="width:10px;height:10px;border-radius:2px;background:#10b981;flex-shrink:0;display:inline-block;"></span> Approved <span style="margin-left:auto;font-weight:700;">{{ $stats['approved_vendors'] }}</span></div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:12px;"><span style="width:10px;height:10px;border-radius:2px;background:#ef4444;flex-shrink:0;display:inline-block;"></span> Rejected <span style="margin-left:auto;font-weight:700;">{{ $stats['rejected_vendors'] }}</span></div>
                </div>
            </div>
        </div>

        {{-- Application Status Donut --}}
        <div class="chart-card" style="flex:1;">
            <div class="chart-hd">
                <div>
                    <div class="chart-hd-title"><i class="fas fa-circle-notch" style="color:#8b5cf6;"></i> Application Status</div>
                </div>
            </div>
            <div class="chart-body" style="display:flex;flex-direction:column;align-items:center;">
                <div class="chart-wrap" style="height:140px;width:140px;">
                    <canvas id="appDonut"></canvas>
                </div>
                <div style="display:flex;flex-direction:column;gap:5px;margin-top:12px;width:100%;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;"><span style="width:9px;height:9px;border-radius:2px;background:#f59e0b;flex-shrink:0;display:inline-block;"></span> Submitted <span style="margin-left:auto;font-weight:700;">{{ $appStats['submitted'] }}</span></div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;"><span style="width:9px;height:9px;border-radius:2px;background:#3b82f6;flex-shrink:0;display:inline-block;"></span> Shortlisted <span style="margin-left:auto;font-weight:700;">{{ $appStats['shortlisted'] }}</span></div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;"><span style="width:9px;height:9px;border-radius:2px;background:#ef4444;flex-shrink:0;display:inline-block;"></span> Rejected <span style="margin-left:auto;font-weight:700;">{{ $appStats['rejected'] }}</span></div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:11px;"><span style="width:9px;height:9px;border-radius:2px;background:#8b5cf6;flex-shrink:0;display:inline-block;"></span> Awarded <span style="margin-left:auto;font-weight:700;">{{ $appStats['awarded'] }}</span></div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ═══ ROW 4 – Tender Status Bar + Recent Tenders ═══ --}}
<div class="chart-grid chart-grid-2" style="margin-bottom:24px;">

    {{-- Tender Status Bar --}}
    <div class="chart-card">
        <div class="chart-hd">
            <div>
                <div class="chart-hd-title"><i class="fas fa-chart-bar" style="color:#3b82f6;"></i> Tender Status Distribution</div>
                <div class="chart-hd-sub">Online vs Postal breakdown</div>
            </div>
        </div>
        <div class="chart-body">
            <div class="chart-wrap" style="height:220px;">
                <canvas id="tenderBarChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Tenders --}}
    <div class="chart-card">
        <div class="chart-hd">
            <div>
                <div class="chart-hd-title"><i class="fas fa-clock" style="color:#06b6d4;"></i> Recent Tenders</div>
                <div class="chart-hd-sub">Latest 5 created</div>
            </div>
            <a href="{{ route('admin.tenders') }}" style="font-size:12px;color:#4f46e5;font-weight:600;text-decoration:none;">View all →</a>
        </div>
        <div class="chart-body" style="padding-top:8px;padding-bottom:8px;">
            @forelse($recentTenders as $t)
            <div class="tender-row">
                <span class="t-num">{{ $t->tender_number }}</span>
                <span class="t-title" title="{{ $t->title }}">{{ Str::limit($t->title, 38) }}</span>
                <span class="t-badge {{ $t->status === 'published' ? 'tb-published' : ($t->status === 'draft' ? 'tb-draft' : 'tb-closed') }}">
                    {{ ucfirst($t->status) }}
                </span>
                <span style="font-size:11px;color:#94a3b8;white-space:nowrap;">{{ $t->applications_count }} apps</span>
            </div>
            @empty
            <p style="text-align:center;color:#94a3b8;font-size:13px;padding:20px 0;">No tenders yet.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ═══ ROW 5 – Pending Vendors Table ═══ --}}
<p class="section-hd"><i class="fas fa-user-clock mr-2"></i>Pending Vendor Approvals</p>
<div class="adm-card">
    <div class="adm-card-hd">
        <div class="adm-hd-left">
            <div class="adm-hd-ico"><i class="fas fa-user-clock"></i></div>
            <div>
                <p class="adm-hd-title">Pending Vendor Approvals</p>
                <p class="adm-hd-sub">Applications awaiting review</p>
            </div>
        </div>
        @if($pendingVendors->count() > 0)
            <span class="adm-badge">{{ $pendingVendors->count() }} pending</span>
        @endif
    </div>
    <div class="adm-tbl-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" class="adm-chk"></th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th class="adm-hide-sm">PAN</th>
                    <th class="adm-hide-sm">Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingVendors as $vendor)
                <tr>
                    <td><input type="checkbox" class="vendor-checkbox adm-chk" value="{{ $vendor->id }}"></td>
                    <td>
                        <div class="adm-co">
                            <div class="adm-avatar">{{ strtoupper(substr($vendor->company_name,0,1)) }}{{ strtoupper(substr(strstr($vendor->company_name,' '),1,1)) }}</div>
                            <div>
                                <div class="adm-co-name">{{ $vendor->company_name }}</div>
                                <span class="adm-co-code">{{ $vendor->vendor_code }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <p class="adm-email">{{ $vendor->email }}</p>
                        <p class="adm-phone"><i class="fas fa-phone-alt" style="font-size:10px;margin-right:4px;"></i>{{ $vendor->phone }}</p>
                    </td>
                    <td class="adm-hide-sm"><span class="adm-pan">{{ $vendor->pan_number }}</span></td>
                    <td class="adm-hide-sm" style="font-size:13px;color:#475569;">{{ $vendor->created_at->format('d M, Y') }}</td>
                    <td>
                        <div class="adm-acts">
                            <button onclick="viewVendor({{ $vendor->id }})" class="adm-act av" title="View"><i class="fas fa-eye"></i></button>
                            <button onclick="approveVendor({{ $vendor->id }})" class="adm-act aa" title="Approve"><i class="fas fa-check"></i></button>
                            <button onclick="showRejectModal({{ $vendor->id }})" class="adm-act ar" title="Reject"><i class="fas fa-times"></i></button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="adm-empty">
                            <div class="adm-empty-ico"><i class="fas fa-check-double"></i></div>
                            <p class="adm-empty-t">All caught up!</p>
                            <p class="adm-empty-s">No vendors are currently pending approval.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pendingVendors->count() > 0)
    <div class="adm-card-ft">
        <span class="adm-ft-note"><i class="fas fa-info-circle"></i> Select vendors to perform bulk actions</span>
        <button onclick="bulkApprove()" class="adm-btn adm-btn-success"><i class="fas fa-check-double"></i> Bulk Approve Selected</button>
    </div>
    @endif
</div>

</div>{{-- /.adm-wrap --}}

{{-- Reject Modal --}}
<div id="rejectModal" class="adm-overlay" onclick="admCloseOnBg(event,'rejectModal')">
    <div class="adm-modal">
        <div class="adm-m-hd">
            <div class="adm-m-hd-ico danger"><i class="fas fa-ban"></i></div>
            <div><p class="adm-m-title">Reject Application</p><p class="adm-m-desc">The vendor will be notified via email.</p></div>
        </div>
        <div class="adm-m-body">
            <label class="adm-lbl" for="rejectionReason">Rejection reason <span style="color:#dc2626">*</span></label>
            <textarea id="rejectionReason" class="adm-ta" placeholder="Explain why this application is being rejected…"></textarea>
        </div>
        <div class="adm-m-ft">
            <button onclick="closeRejectModal()" class="adm-btn adm-btn-ghost">Cancel</button>
            <button onclick="confirmReject()" class="adm-btn adm-btn-danger"><i class="fas fa-times-circle"></i> Reject Vendor</button>
        </div>
    </div>
</div>

{{-- View Vendor Modal --}}
<div id="viewModal" class="adm-overlay" onclick="admCloseOnBg(event,'viewModal')">
    <div class="adm-modal adm-wide">
        <div class="adm-sticky-hd">
            <div class="adm-m-hd-ico info"><i class="fas fa-user-tie"></i></div>
            <div><p class="adm-m-title" id="viewModalTitle">Vendor Details</p><p class="adm-m-desc">Full application information</p></div>
        </div>
        <div id="vendorDetails" class="adm-m-body"></div>
        <div class="adm-sticky-ft">
            <button onclick="closeViewModal()" class="adm-btn adm-btn-ghost"><i class="fas fa-times"></i> Close</button>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto mt-6">
    @include('partials.dashboard-widgets', ['widgetData' => $widgetData, 'hideEmptyState' => true])
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const CSRF = '{{ csrf_token() }}';
let currentVendorId = null;

// ── helpers ──
function admOpen(id)  { document.getElementById(id).classList.add('adm-open'); }
function admClose(id) { document.getElementById(id).classList.remove('adm-open'); }
function admCloseOnBg(e,id){ if(e.target===document.getElementById(id)) admClose(id); }
document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ admClose('rejectModal');admClose('viewModal'); } });
document.getElementById('selectAll')?.addEventListener('change',e=>{
    document.querySelectorAll('.vendor-checkbox').forEach(cb=>cb.checked=e.target.checked);
});

// ── Chart defaults ──
Chart.defaults.font.family = "'Sora', sans-serif";
Chart.defaults.plugins.legend.display = false;

const monthLabels  = {!! $monthLabels !!};
const vendorCounts = {!! $vendorCounts !!};
const appCounts    = {!! $appCounts !!};

// Line chart helper
function makeLineChart(id, labels, data, color, label) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data,
                fill: true,
                tension: 0.45,
                borderColor: color,
                backgroundColor: color + '22',
                pointBackgroundColor: color,
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
}
makeLineChart('vendorLineChart', monthLabels, vendorCounts, '#4f46e5', 'Vendor Registrations');
makeLineChart('appLineChart',    monthLabels, appCounts,    '#8b5cf6', 'Applications');

// Vendor Donut
new Chart(document.getElementById('vendorDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Approved', 'Rejected'],
        datasets: [{
            data: [{{ $stats['pending_vendors'] }}, {{ $stats['approved_vendors'] }}, {{ $stats['rejected_vendors'] }}],
            backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
            borderWidth: 3, borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
        }
    }
});

// Application Status Donut
new Chart(document.getElementById('appDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Submitted','Shortlisted','Rejected','Awarded'],
        datasets: [{
            data: [{{ $appStats['submitted'] }}, {{ $appStats['shortlisted'] }}, {{ $appStats['rejected'] }}, {{ $appStats['awarded'] }}],
            backgroundColor: ['#f59e0b','#3b82f6','#ef4444','#8b5cf6'],
            borderWidth: 3, borderColor: '#fff', hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } }
    }
});

// Tender Status Grouped Bar
new Chart(document.getElementById('tenderBarChart'), {
    type: 'bar',
    data: {
        labels: ['Draft', 'Published', 'Closed', 'Online', 'Postal', 'Hybrid'],
        datasets: [{
            label: 'Count',
            data: [
                {{ $tenderStats['draft'] }},
                {{ $tenderStats['published'] }},
                {{ $tenderStats['closed'] }},
                {{ $tenderStats['online'] }},
                {{ $tenderStats['postal'] }},
                {{ $tenderStats['hybrid'] ?? 0 }},
            ],
            backgroundColor: ['#fbbf24','#34d399','#f87171','#60a5fa','#a78bfa','#c084fc'],
            borderRadius: 6, borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} tenders` } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 12 } } },
            y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }
        }
    }
});

// ── Vendor actions ──
function approveVendor(id){
    if(confirm('Approve this vendor?')){
        fetch(`/admin/vendors/${id}/approve`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF}})
        .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Failed'); });
    }
}
function showRejectModal(id){ currentVendorId=id; admOpen('rejectModal'); }
function closeRejectModal(){ admClose('rejectModal'); document.getElementById('rejectionReason').value=''; }
function confirmReject(){
    const reason=document.getElementById('rejectionReason').value.trim();
    if(!reason){ alert('Please enter a rejection reason'); return; }
    fetch(`/admin/vendors/${currentVendorId}/reject`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({reason})})
    .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Failed'); });
}
function viewVendor(id){
    const box=document.getElementById('vendorDetails');
    box.innerHTML='<div class="vd-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
    admOpen('viewModal');
    fetch(`/admin/vendors/${id}`).then(r=>r.json()).then(({success,vendor})=>{
        if(!success||!vendor){ box.innerHTML='<p class="vd-error">Failed to load vendor details.</p>'; return; }
        const docs=vendor.registration_documents||{};
        const docLinks=[];
        function makeDocLink(label,filePath,fileName){
            const ext=(filePath||'').split('.').pop().toUpperCase();
            const display=fileName?`${label} — ${fileName}`:label;
            return `<a href="/storage/${filePath}" target="_blank" class="vd-doc-link"><i class="fas fa-file-alt vd-doc-ico"></i>${display}<span class="vd-doc-ext">${ext} &nbsp;<i class="fas fa-external-link-alt"></i></span></a>`;
        }
        Object.entries(docs).forEach(([type,value])=>{
            const label=type.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
            if(type==='certificates'&&Array.isArray(value)){ value.forEach(cert=>{ if(cert&&cert.path) docLinks.push(makeDocLink(cert.label||'Certificate',cert.path,cert.name)); }); }
            else if(value&&typeof value==='object'&&value.path){ docLinks.push(makeDocLink(label,value.path,value.name)); }
            else if(typeof value==='string'){ docLinks.push(makeDocLink(label,value,null)); }
        });
        const docsHtml=docLinks.length?docLinks.join(''):'<p style="color:#94a3b8;font-size:13px;margin:0">No documents uploaded yet.</p>';
        const bank=vendor.bank_details||{};
        const bankHtml=Object.keys(bank).length?`<div class="vd-grid"><div class="vd-field"><span class="vd-lbl">Bank Name</span><span class="vd-val">${bank.bank_name||'—'}</span></div><div class="vd-field"><span class="vd-lbl">Account Holder</span><span class="vd-val">${bank.account_holder_name||'—'}</span></div><div class="vd-field"><span class="vd-lbl">Account Number</span><span class="vd-val"><span class="vd-mono">${bank.account_number||'—'}</span></span></div><div class="vd-field"><span class="vd-lbl">IFSC Code</span><span class="vd-val"><span class="vd-mono">${bank.ifsc_code||'—'}</span></span></div></div>`:'<p style="color:#94a3b8;font-size:13px;margin:0">No bank details provided.</p>';
        const addr=[vendor.address,vendor.city,vendor.state,vendor.pincode?'- '+vendor.pincode:''].filter(Boolean).join(', ');
        const regDate=vendor.created_at?new Date(vendor.created_at).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}):'—';
        const titleEl=document.getElementById('viewModalTitle');
        if(titleEl) titleEl.textContent=vendor.company_name||'Vendor Details';
        const status=vendor.status||'pending';
        const statusBadge=`<span class="vd-status vd-status-${status}">${status.charAt(0).toUpperCase()+status.slice(1)}</span>`;
        box.innerHTML=`
            <div class="vd-section"><p class="vd-section-title"><i class="fas fa-building"></i> Company Information</p>
            <div class="vd-grid">
                <div class="vd-field"><span class="vd-lbl">Company Name</span><span class="vd-val">${vendor.company_name}</span></div>
                <div class="vd-field"><span class="vd-lbl">Vendor Code</span><span class="vd-val"><span class="vd-mono">${vendor.vendor_code}</span></span></div>
                <div class="vd-field"><span class="vd-lbl">PAN Number</span><span class="vd-val"><span class="vd-mono">${vendor.pan_number}</span></span></div>
                <div class="vd-field"><span class="vd-lbl">GST Number</span><span class="vd-val"><span class="vd-mono">${vendor.gst_number||'—'}</span></span></div>
                <div class="vd-field"><span class="vd-lbl">Status</span><span class="vd-val">${statusBadge}</span></div>
                <div class="vd-field"><span class="vd-lbl">Registered On</span><span class="vd-val">${regDate}</span></div>
            </div></div>
            <div class="vd-section"><p class="vd-section-title"><i class="fas fa-user"></i> Contact Information</p>
            <div class="vd-grid">
                <div class="vd-field"><span class="vd-lbl">Contact Person</span><span class="vd-val">${vendor.user?.name||'—'}</span></div>
                <div class="vd-field"><span class="vd-lbl">Email</span><span class="vd-val">${vendor.email}</span></div>
                <div class="vd-field"><span class="vd-lbl">Phone</span><span class="vd-val">${vendor.phone}</span></div>
                <div class="vd-field vd-full"><span class="vd-lbl">Address</span><span class="vd-val">${addr||'—'}</span></div>
            </div></div>
            <div class="vd-section"><p class="vd-section-title"><i class="fas fa-university"></i> Bank Details</p>${bankHtml}</div>
            <div class="vd-section"><p class="vd-section-title"><i class="fas fa-file-alt"></i> Registration Documents</p><div class="vd-docs">${docsHtml}</div></div>`;
    }).catch(()=>{ box.innerHTML='<p class="vd-error">Unable to fetch vendor details.</p>'; });
}
function closeViewModal(){ admClose('viewModal'); document.getElementById('viewModalTitle').textContent='Vendor Details'; }
function bulkApprove(){
    const selected=Array.from(document.querySelectorAll('.vendor-checkbox:checked')).map(cb=>cb.value);
    if(!selected.length){ alert('Please select at least one vendor'); return; }
    if(confirm(`Approve ${selected.length} vendor(s)?`)){
        fetch('/admin/vendors/bulk-approve',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({vendor_ids:selected})})
        .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Failed'); });
    }
}
</script>
@endpush
@endsection
