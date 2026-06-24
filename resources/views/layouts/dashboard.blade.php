<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --sidebar-w: 260px;
            --navy:    #0f172a;
            --navy-2:  #1e293b;
            --navy-3:  #334155;
            --accent:  #3b82f6;
            --accent-2:#6366f1;
            --glow:    rgba(59,130,246,.25);
            --text-muted: #94a3b8;
            --border:  rgba(255,255,255,.07);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;
        }

        /* ── Sidebar ───────────────────────────────────────── */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--navy);
            z-index: 40;
            display: flex;
            flex-direction: column;
            transition: transform .28s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: radial-gradient(circle, rgba(99,102,241,.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        /* Brand */
        .sidebar-brand {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 72px;
            background: rgba(255,255,255,0.04);
        }
        .sidebar-brand img {
            height: 48px;
            width: auto;
            object-fit: contain;
            border-radius: 6px;
        }
        /* legacy unused but keep so CSS doesn't error */
        .brand-text p {
            font-size: 11px;
            color: var(--text-muted);
            margin: 2px 0 0;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        /* User Card */
        .sidebar-user {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-2) 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
            font-family: 'DM Mono', monospace;
        }
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }
        .user-role {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: capitalize;
            margin: 1px 0 0;
        }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 14px 8px 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #94a3b8;
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: background .18s, color .18s, transform .15s;
            margin-bottom: 2px;
            position: relative;
        }
        .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: #e2e8f0;
            transform: translateX(2px);
        }
        .nav-link.active {
            background: linear-gradient(90deg, rgba(59,130,246,.22) 0%, rgba(99,102,241,.12) 100%);
            color: #93c5fd;
        }
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0; top: 20%; bottom: 20%;
            width: 3px;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            border-radius: 0 3px 3px 0;
        }
        .nav-link i {
            width: 16px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
            opacity: .85;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
            font-family: 'DM Mono', monospace;
        }
        .nav-badge-blue  { background: rgba(59,130,246,.25); color: #93c5fd; }
        .nav-badge-red   { background: rgba(239,68,68,.25);  color: #fca5a5; }

        /* Logout */
        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            color: #f87171;
            font-size: 13.5px;
            font-weight: 500;
            background: none;
            border: none;
            cursor: pointer;
            transition: background .18s;
        }
        .logout-btn:hover {
            background: rgba(239,68,68,.1);
        }
        .logout-btn i { font-size: 13px; }

        /* ── Main wrapper ──────────────────────────────────── */
        .main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin-left: var(--sidebar-w);
            transition: margin-left .28s cubic-bezier(.4,0,.2,1);
        }
        .content-area.full { margin-left: 0; }

        /* ── Top Navbar ────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid #e2e8f0;
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .topbar-toggle {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: none;
            border: 1px solid #e2e8f0;
            color: #64748b;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .18s, color .18s;
            font-size: 14px;
        }
        .topbar-toggle:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notif-btn {
            position: relative;
            width: 36px; height: 36px;
            border-radius: 8px;
            background: none;
            border: 1px solid #e2e8f0;
            color: #64748b;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .18s;
            font-size: 14px;
        }
        .notif-btn:hover { background: #f1f5f9; }
        .notif-dot {
            position: absolute;
            top: 7px; right: 7px;
            width: 7px; height: 7px;
            background: #ef4444;
            border-radius: 50%;
            border: 1.5px solid white;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .topbar-user-dot {
            width: 7px; height: 7px;
            background: #22c55e;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .topbar-user-name {
            font-size: 13px;
            font-weight: 500;
            color: #1e293b;
        }

        /* ── Page content ──────────────────────────────────── */
        .main-content {
            flex: 1 0 auto;
            padding: 28px;
        }

        /* ── Footer ────────────────────────────────────────── */
        .app-footer {
            flex-shrink: 0;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }

        /* ── Scrollbar (global) ─────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }

        /* ── Overlay (mobile) ──────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 39;
            backdrop-filter: blur(2px);
        }
        @media (max-width: 768px) {
            .content-area { margin-left: 0 !important; }
            .sidebar-overlay { display: block; }
        }

        /* ── Animations ────────────────────────────────────── */
        @keyframes scale {
            from { transform: scale(0.95); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .animate-scale    { animation: scale   .2s ease-out; }
        .animate-slide-up { animation: slideUp .3s ease-out; }

        /* page-load fade-in */
        .main-content { animation: fadeIn .3s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
    @stack('styles')
</head>

<body>
<div x-data="{ sidebarOpen: true }" class="main-wrapper">

    <!-- ── Overlay (mobile tap-to-close) ───────────────────── -->
    <div class="sidebar-overlay"
         x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-cloak
         style="display:none"></div>

    <!-- ══════════════════ SIDEBAR ════════════════════════════ -->
    <aside class="sidebar" :class="{ 'collapsed': !sidebarOpen }">

        <!-- Brand -->
        <div class="sidebar-brand" style="padding:14px 16px;">
            <img src="/img/logo.jpg" alt="VMS" style="height:48px;width:auto;object-fit:contain;border-radius:6px;">
        </div>

        <!-- User Card -->
        <div class="sidebar-user">
            <div class="user-avatar">
                {{ substr(Auth::user()->name, 0, 2) }}
            </div>
            <div>
                <p class="user-name">{{ Auth::user()->name }}</p>
                @if(Auth::user()->hasRole('vendor') && Auth::user()->vendor?->company_name)
                    <p class="user-company" style="font-size:11px;color:#93c5fd;font-weight:500;margin:0 0 1px;line-height:1.3;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ Auth::user()->vendor->company_name }}">{{ Auth::user()->vendor->company_name }}</p>
                @endif
                <p class="user-role">{{ ucfirst(Auth::user()->getRoleNames()->first()) }}</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">

            <!-- Dashboard -->
            @if(Auth::user()->hasRole('super-admin'))
                <a href="/super-admin/dashboard"
                   class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            @endif

            @if(Auth::user()->hasRole('admin'))
                <a href="/admin/dashboard"
                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            @endif

            @if(Auth::user()->hasRole('vendor'))
                <a href="/vendor/dashboard"
                   class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            @endif

            <!-- Vendor Management -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                <div class="nav-section-label">Vendor Management</div>

                <a href="{{ Auth::user()->hasRole('super-admin') ? route('super-admin.vendors') : route('admin.vendors') }}"
                   class="nav-link {{ request()->routeIs('*.vendors*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>All Vendors</span>
                </a>
            @endif

            <!-- Tender Management (admin + super-admin) -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                @php $rp = Auth::user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
                <div class="nav-section-label">Tender Management</div>

                <a href="{{ route($rp . '.tenders') }}"
                   class="nav-link {{ request()->routeIs($rp . '.tenders*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    <span>All Tenders</span>
                </a>
                <a href="{{ route($rp . '.tenders.create') }}" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Tender</span>
                </a>
            @endif

            <!-- Applications (admin + super-admin) -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                @php $rp = Auth::user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
                <div class="nav-section-label">Applications</div>

                <a href="{{ route($rp . '.applications') }}"
                   class="nav-link {{ request()->routeIs($rp . '.applications*') ? 'active' : '' }}">
                    <i class="fas fa-file-signature"></i>
                    <span>All Applications</span>
                </a>
            @endif

            <!-- Contract Management (admin + super-admin) -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                @php $rp = Auth::user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
                <div class="nav-section-label">Contract Management</div>

                <a href="{{ route($rp . '.contracts') }}"
                   class="nav-link {{ request()->routeIs($rp . '.contracts*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>All Contracts</span>
                </a>
                <a href="{{ route($rp . '.eligibility-failures') }}"
                   class="nav-link {{ request()->routeIs($rp . '.eligibility-failures*') ? 'active' : '' }}">
                    <i class="fas fa-ban"></i>
                    <span>Eligibility Failures</span>
                </a>
                <a href="{{ route($rp . '.reports') }}"
                   class="nav-link {{ request()->routeIs($rp . '.reports*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            @endif

            <!-- Vendor Portal -->
            @if(Auth::user()->hasRole('vendor'))
                <div class="nav-section-label">Tenders</div>

                <a href="{{ route('vendor.tenders') }}?type=online"
                   class="nav-link {{ request()->routeIs('vendor.tenders') && request('type','online') === 'online' ? 'active' : '' }}">
                    <i class="fas fa-globe"></i>
                    <span>Online Tenders</span>
                </a>
                <a href="{{ route('vendor.tenders') }}?type=postal"
                   class="nav-link {{ request()->routeIs('vendor.tenders') && request('type') === 'postal' ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i>
                    <span>Postal Tenders</span>
                </a>
                <a href="{{ route('vendor.applications') }}"
                   class="nav-link {{ request()->routeIs('vendor.applications*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    <span>My Applications</span>
                </a>
                <a href="{{ route('vendor.contracts') }}"
                   class="nav-link {{ request()->routeIs('vendor.contracts*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>My Contracts</span>
                </a>
                <a href="{{ route('vendor.profile') }}"
                   class="nav-link {{ request()->routeIs('vendor.profile*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Company Profile</span>
                </a>
            @endif

            <!-- Tender Owner Portal -->
            @if(Auth::user()->hasRole('tender-owner'))
                <div class="nav-section-label">Tender Owner Portal</div>

                <a href="{{ route('tender-owner.dashboard') }}"
                   class="nav-link {{ request()->routeIs('tender-owner.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('tender-owner.tenders.index') }}"
                   class="nav-link {{ request()->routeIs('tender-owner.tenders*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    <span>My Tenders</span>
                    @php $tenderCount = Auth::user()->ownedTenders()->count(); @endphp
                    @if($tenderCount > 0)
                        <span class="nav-badge nav-badge-blue">{{ $tenderCount }}</span>
                    @endif
                </a>

                <a href="{{ route('tender-owner.applications.index') }}"
                   class="nav-link {{ request()->routeIs('tender-owner.applications*') ? 'active' : '' }}">
                    <i class="fas fa-file-signature"></i>
                    <span>Applications</span>
                    @php
                        $applicationCount = App\Models\TenderApplication::whereIn('tender_id', Auth::user()->ownedTenders()->pluck('id'))->where('status', 'submitted')->count();
                    @endphp
                    @if($applicationCount > 0)
                        <span class="nav-badge nav-badge-red">{{ $applicationCount }}</span>
                    @endif
                </a>

                <a href="{{ route('tender-owner.contracts.index') }}"
                   class="nav-link {{ request()->routeIs('tender-owner.contracts*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>Contracts</span>
                </a>

                <a href="{{ route('tender-owner.eligibility-failures') }}"
                   class="nav-link {{ request()->routeIs('tender-owner.eligibility-failures*') ? 'active' : '' }}">
                    <i class="fas fa-ban"></i>
                    <span>Eligibility Failures</span>
                </a>

                <a href="{{ route('tender-owner.reports') }}"
                   class="nav-link {{ request()->routeIs('tender-owner.reports*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>

                <a href="{{ route('tender-owner.tenders.create') }}" class="nav-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Tender</span>
                </a>
            @endif

            <!-- Committee Member Portal -->
            @if(Auth::user()->hasRole('committee-member'))
                <div class="nav-section-label">Committee Portal</div>

                <a href="{{ route('committee.dashboard') }}"
                   class="nav-link {{ request()->routeIs('committee.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('committee.tenders') }}"
                   class="nav-link {{ request()->routeIs('committee.tenders') ? 'active' : '' }}">
                    <i class="fas fa-gavel"></i>
                    <span>My Assigned Tenders</span>
                </a>

                <a href="{{ route('committee.applications') }}"
                   class="nav-link {{ request()->routeIs('committee.applications*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    <span>Applications to Review</span>
                    @php
                        $pendingReview = auth()->user()->committeeTenders()
                            ->wherePivot('is_active', true)
                            ->pluck('tenders.id');
                        $reviewCount = \App\Models\TenderApplication::whereIn('tender_id', $pendingReview)
                            ->where('status', 'submitted')->count();
                    @endphp
                    @if($reviewCount > 0)
                        <span class="nav-badge nav-badge-red">{{ $reviewCount }}</span>
                    @endif
                </a>
            @endif

            <!-- System Administration -->
            @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                @php $rp = Auth::user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
                <div class="nav-section-label">System Administration</div>

                <a href="{{ route($rp . '.users') }}"
                   class="nav-link {{ request()->routeIs($rp . '.users*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                </a>

                <a href="{{ route($rp . '.categories') }}"
                   class="nav-link {{ request()->routeIs($rp . '.categories*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>

                <div class="nav-section-label">Masters</div>

                <a href="{{ route($rp . '.masters.terms') }}"
                   class="nav-link {{ request()->routeIs($rp . '.masters*') ? 'active' : '' }}">
                    <i class="fas fa-gavel"></i>
                    <span>Terms &amp; Conditions</span>
                </a>

                @if(Auth::user()->hasRole('super-admin'))
                <a href="{{ route('super-admin.audit-logs') }}"
                   class="nav-link {{ request()->routeIs('super-admin.audit-logs*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span>Audit Logs</span>
                </a>
                <a href="{{ route('super-admin.settings') }}"
                   class="nav-link {{ request()->routeIs('super-admin.settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                @endif
            @endif

            {{-- Profile link for all roles --}}
            <div class="nav-section-label">Account</div>
            <a href="{{ route('profile.index') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>

        </nav>

        <!-- Logout -->
        <div class="sidebar-footer">
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>
    <!-- ═══════════════════════════════════════════════════════ -->

    <!-- Main Content Area -->
    <div class="content-area" :class="{ 'full': !sidebarOpen }">

        <!-- Top Navbar -->
        <nav class="topbar">
            <button class="topbar-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <div class="topbar-right">
                <button class="notif-btn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notif-dot"></span>
                </button>
                <div class="topbar-user">
                    <span class="topbar-user-dot"></span>
                    <span class="topbar-user-name">{{ Auth::user()->name }}</span>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="main-content">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('layouts.footer')
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        // Additional Alpine initialization if needed
    });
</script>
@stack('scripts')

{{-- =================== COOKIE CONSENT (session-based, once per session) =================== --}}
@if(!session('cookie_accepted'))
<div id="cookieBanner" style="position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#1e293b;color:#e2e8f0;padding:14px 24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 -4px 24px rgba(0,0,0,.3);">
    <i class="fas fa-cookie-bite" style="color:#f59e0b;font-size:1.25rem;flex-shrink:0;"></i>
    <p style="margin:0;font-size:13px;flex:1;min-width:200px;line-height:1.5;">
        We use cookies to keep you signed in and improve your experience. By continuing to use this site you accept our use of cookies.
    </p>
    <div style="display:flex;gap:8px;flex-shrink:0;">
        <button onclick="acceptCookies()"
            style="background:#3b82f6;color:#fff;border:none;border-radius:6px;padding:7px 20px;font-size:13px;font-weight:600;cursor:pointer;">
            Accept
        </button>
    </div>
</div>
<script>
function acceptCookies() {
    fetch('{{ route('cookie.accept') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        document.getElementById('cookieBanner').style.display = 'none';
    });
}
</script>
@endif
</body>
</html>