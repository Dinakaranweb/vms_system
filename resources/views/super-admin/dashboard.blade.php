@extends('layouts.dashboard')
@section('title', 'Super Admin Dashboard')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Serif+Display&display=swap');

.db * { box-sizing: border-box; margin: 0; padding: 0; }

.db {
    font-family: 'DM Sans', sans-serif;
    background: #f0f2f5;
    padding: 28px;
    min-height: 100vh;
}

/* Header */
.db-hd { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
.db-hd-left { display: flex; align-items: center; gap: 14px; }
.db-avatar {
    width: 46px; height: 46px; border-radius: 14px;
    background: #1a2332;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.db-avatar i { color: #e8edf3; font-size: 18px; }
.db-title { font-family: 'DM Serif Display', serif; font-size: 22px; color: #1a2332; letter-spacing: -0.02em; line-height: 1; }
.db-sub { font-size: 12px; color: #7a8499; font-weight: 400; margin-top: 3px; }
.db-hd-right { display: flex; align-items: center; gap: 10px; }
.db-chip {
    display: flex; align-items: center; gap: 7px;
    background: #fff; border: 1px solid #e2e6ed; border-radius: 100px;
    padding: 8px 16px; font-size: 12px; font-weight: 500; color: #1a2332;
}
.db-chip-dim { color: #7a8499; font-weight: 400; }
.db-pulse { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; position: relative; flex-shrink: 0; }
.db-pulse::after {
    content: ''; position: absolute; inset: -3px; border-radius: 50%;
    border: 2px solid #22c55e; animation: db-ring 1.8s ease-out infinite; opacity: 0;
}
@keyframes db-ring { 0%{transform:scale(.6);opacity:.8} 100%{transform:scale(1.8);opacity:0} }

/* Stat Cards */
.db-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 14px; }
.db-card {
    background: #fff; border: 1px solid #e2e6ed; border-radius: 18px; padding: 24px;
    position: relative; overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease; cursor: default;
}
.db-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(26,35,50,.08); }
.db-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.db-card-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.icon-blue { background: #f0f4ff; }  .icon-blue i  { color: #3b5bdb; font-size: 18px; }
.icon-amber { background: #fff8ee; } .icon-amber i { color: #d97706; font-size: 18px; }
.icon-teal  { background: #f0fdf4; } .icon-teal i  { color: #059669; font-size: 18px; }
.db-badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.03em; }
.badge-b { background: #eef2ff; color: #3b5bdb; }
.badge-a { background: #fffbeb; color: #b45309; }
.badge-g { background: #f0fdf4; color: #059669; }
.db-card-label { font-size: 11px; font-weight: 600; color: #9ba4b8; text-transform: uppercase; letter-spacing: 0.09em; margin-bottom: 8px; }
.db-card-val { font-family: 'DM Serif Display', serif; font-size: 48px; line-height: 1; letter-spacing: -0.03em; margin-bottom: 18px; }
.val-b { color: #3b5bdb; } .val-a { color: #d97706; } .val-g { color: #059669; }
.db-card-rule { height: 1px; background: #f0f2f5; margin-bottom: 14px; }
.db-card-foot { display: flex; align-items: center; justify-content: space-between; }
.foot-txt { font-size: 12px; color: #9ba4b8; }
.trend-g { font-size: 11px; font-weight: 600; color: #059669; }
.trend-a { font-size: 11px; font-weight: 600; color: #d97706; }
.db-wm { position: absolute; bottom: -14px; right: -8px; font-size: 88px; color: #1a2332; opacity: .025; pointer-events: none; line-height: 1; }

/* Bottom row */
.db-bot { display: grid; grid-template-columns: 1.1fr 1fr; gap: 14px; }
.db-panel { background: #fff; border: 1px solid #e2e6ed; border-radius: 18px; padding: 24px; }
.db-panel-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.db-panel-title { font-size: 13px; font-weight: 600; color: #1a2332; letter-spacing: -0.01em; }
.db-panel-link { font-size: 11px; color: #9ba4b8; font-weight: 500; text-decoration: none; transition: color .15s; }
.db-panel-link:hover { color: #3b5bdb; }

/* Breakdown bars */
.brk-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.brk-row:last-child { margin-bottom: 0; }
.brk-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.brk-lbl { font-size: 12px; color: #7a8499; min-width: 60px; }
.brk-track { flex: 1; height: 6px; background: #f0f2f5; border-radius: 100px; overflow: hidden; }
.brk-fill { height: 100%; border-radius: 100px; }
.brk-cnt { font-size: 13px; font-weight: 600; color: #1a2332; min-width: 28px; text-align: right; }

/* Action buttons */
.act-btn {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; border-radius: 12px;
    border: 1px solid #e2e6ed; background: #fff;
    color: #1a2332; font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 500;
    text-decoration: none; margin-bottom: 10px;
    transition: all .16s ease; cursor: pointer; width: 100%;
}
.act-btn:last-child { margin-bottom: 0; }
.act-btn:hover { background: #f7f9ff; border-color: #c5cfe8; transform: translateX(3px); }
.act-btn:hover .act-arr { color: #3b5bdb; opacity: 1; }
.act-ico { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.act-ico.b { background: #eef2ff; } .act-ico.b i { color: #3b5bdb; font-size: 15px; }
.act-ico.v { background: #f5f0ff; } .act-ico.v i { color: #7c3aed; font-size: 15px; }
.act-lbl { flex: 1; text-align: left; }
.act-arr { color: #c5cfe8; opacity: .7; font-size: 13px; transition: all .16s ease; }

@media (max-width: 900px) {
    .db-stats { grid-template-columns: 1fr; }
    .db-bot   { grid-template-columns: 1fr; }
    .db-hd    { flex-direction: column; align-items: flex-start; gap: 14px; }
}
</style>

<div class="db">

    {{-- Header --}}
    <div class="db-hd">
        <div class="db-hd-left">
            <div class="db-avatar"><i class="fas fa-user-tie"></i></div>
            <div>
                <div class="db-title">Welcome back, {{ Auth::user()->name }}</div>
                <div class="db-sub">Super Admin &nbsp;·&nbsp; {{ now()->format('l, d M Y') }}</div>
            </div>
        </div>
        <div class="db-hd-right">
            <div class="db-chip">
                <span class="db-pulse"></span>
                All systems operational
            </div>
            <div class="db-chip db-chip-dim">
                <i class="fas fa-check-circle" style="color:#22c55e;font-size:12px"></i>
                Super Admin Access
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="db-stats">

        <div class="db-card">
            <div class="db-card-top">
                <div class="db-card-icon icon-blue"><i class="fas fa-building"></i></div>
                <span class="db-badge badge-b">All Vendors</span>
            </div>
            <div class="db-card-label">Total Vendors</div>
            <div class="db-card-val val-b">{{ $stats['total_vendors'] }}</div>
            <div class="db-card-rule"></div>
            <div class="db-card-foot">
                <span class="foot-txt">Registered on platform</span>
                <span class="trend-g">Active</span>
            </div>
            <span class="db-wm"><i class="fas fa-building"></i></span>
        </div>

        <div class="db-card">
            <div class="db-card-top">
                <div class="db-card-icon icon-amber"><i class="fas fa-clock"></i></div>
                <span class="db-badge badge-a">Needs Review</span>
            </div>
            <div class="db-card-label">Pending Vendors</div>
            <div class="db-card-val val-a">{{ $stats['pending_vendors'] }}</div>
            <div class="db-card-rule"></div>
            <div class="db-card-foot">
                <span class="foot-txt">Awaiting approval</span>
                <span class="trend-a">Review now</span>
            </div>
            <span class="db-wm"><i class="fas fa-clock"></i></span>
        </div>

        <div class="db-card">
            <div class="db-card-top">
                <div class="db-card-icon icon-teal"><i class="fas fa-check-circle"></i></div>
                <span class="db-badge badge-g">Verified</span>
            </div>
            <div class="db-card-label">Approved Vendors</div>
            <div class="db-card-val val-g">{{ $stats['approved_vendors'] }}</div>
            <div class="db-card-rule"></div>
            <div class="db-card-foot">
                <span class="foot-txt">Live &amp; active</span>
                <span class="trend-g">Operational</span>
            </div>
            <span class="db-wm"><i class="fas fa-check-circle"></i></span>
        </div>

    </div>

    {{-- Bottom Row --}}
    @php
        $total    = max($stats['total_vendors'], 1);
        $appPct   = round($stats['approved_vendors'] / $total * 100);
        $penPct   = round($stats['pending_vendors']  / $total * 100);
        $otherCnt = max($stats['total_vendors'] - $stats['approved_vendors'] - $stats['pending_vendors'], 0);
        $othPct   = max(100 - $appPct - $penPct, 0);
    @endphp

    <div class="db-bot">

        <div class="db-panel">
            <div class="db-panel-hd">
                <span class="db-panel-title">Vendor breakdown</span>
                <a href="#" class="db-panel-link">View all →</a>
            </div>
            <div class="brk-row">
                <span class="brk-dot" style="background:#3b5bdb"></span>
                <span class="brk-lbl">Total</span>
                <div class="brk-track"><div class="brk-fill" style="background:#3b5bdb;width:100%"></div></div>
                <span class="brk-cnt">{{ $stats['total_vendors'] }}</span>
            </div>
            <div class="brk-row">
                <span class="brk-dot" style="background:#059669"></span>
                <span class="brk-lbl">Approved</span>
                <div class="brk-track"><div class="brk-fill" style="background:#059669;width:{{ $appPct }}%"></div></div>
                <span class="brk-cnt">{{ $stats['approved_vendors'] }}</span>
            </div>
            <div class="brk-row">
                <span class="brk-dot" style="background:#d97706"></span>
                <span class="brk-lbl">Pending</span>
                <div class="brk-track"><div class="brk-fill" style="background:#d97706;width:{{ $penPct }}%"></div></div>
                <span class="brk-cnt">{{ $stats['pending_vendors'] }}</span>
            </div>
            <div class="brk-row">
                <span class="brk-dot" style="background:#cbd2e0"></span>
                <span class="brk-lbl">Other</span>
                <div class="brk-track"><div class="brk-fill" style="background:#cbd2e0;width:{{ $othPct }}%"></div></div>
                <span class="brk-cnt">{{ $otherCnt }}</span>
            </div>
        </div>

        <div class="db-panel">
            <div class="db-panel-hd">
                <span class="db-panel-title">Quick actions</span>
            </div>
            <a href="#" class="act-btn">
                <span class="act-ico b"><i class="fas fa-users"></i></span>
                <span class="act-lbl">Manage Vendors</span>
                <i class="fas fa-arrow-right act-arr"></i>
            </a>
            <a href="#" class="act-btn">
                <span class="act-ico v"><i class="fas fa-user-shield"></i></span>
                <span class="act-lbl">Manage Admins</span>
                <i class="fas fa-arrow-right act-arr"></i>
            </a>
        </div>

    </div>
</div>

@endsection