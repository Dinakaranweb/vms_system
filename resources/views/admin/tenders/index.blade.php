@extends('layouts.dashboard')

@section('title', 'Manage Tenders')
@section('header', 'Tender Management')

@section('content')
@php $rp = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp

<style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

  :root {
    --primary:       #1a56db;
    --primary-dark:  #1344b5;
    --primary-light: #ebf1ff;
    --accent:        #0ea5e9;
    --surface:       #ffffff;
    --surface-2:     #f7f8fc;
    --border:        #e4e8f0;
    --text-main:     #0f172a;
    --text-muted:    #64748b;
    --text-light:    #94a3b8;
    --green:  #10b981; --green-bg:  #d1fae5; --green-text:  #065f46;
    --yellow: #f59e0b; --yellow-bg: #fef3c7; --yellow-text: #92400e;
    --red:    #ef4444; --red-bg:    #fee2e2; --red-text:    #991b1b;
    --purple: #8b5cf6; --purple-bg: #ede9fe; --purple-text: #5b21b6;
    --orange: #f97316; --orange-bg: #ffedd5; --orange-text: #9a3412;
    --radius-sm: 8px;
    --radius:    12px;
    --radius-lg: 16px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --shadow:    0 4px 12px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.04);
    --shadow-lg: 0 12px 32px rgba(0,0,0,.10), 0 4px 12px rgba(0,0,0,.06);
  }

  * { box-sizing: border-box; }

  .tm-wrap {
    font-family: 'Sora', sans-serif;
    color: var(--text-main);
    display: flex;
    flex-direction: column;
    gap: 28px;
    padding: 8px 0;
  }

  /* ── Page header bar ── */
  .tm-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }
  .tm-page-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text-main);
    letter-spacing: -.02em;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .tm-page-title span.badge-live {
    font-size: .65rem;
    font-weight: 600;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 100px;
    padding: 3px 10px;
    letter-spacing: .04em;
    text-transform: uppercase;
  }
  .tm-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--primary);
    color: #fff;
    font-family: 'Sora', sans-serif;
    font-size: .82rem;
    font-weight: 600;
    padding: 9px 18px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background .18s, transform .12s, box-shadow .18s;
    box-shadow: 0 2px 8px rgba(26,86,219,.25);
    letter-spacing: -.01em;
    white-space: nowrap;
  }
  .tm-btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(26,86,219,.35);
    color: #fff;
    text-decoration: none;
  }

  /* ── Stat cards ── */
  .tm-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }
  @media (max-width: 1024px) { .tm-stats { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 520px)  { .tm-stats { grid-template-columns: 1fr; } }

  .tm-stat-card {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 20px 22px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    transition: box-shadow .2s, transform .2s;
    position: relative;
    overflow: hidden;
  }
  .tm-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: var(--radius) var(--radius) 0 0;
  }
  .tm-stat-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
  .tm-stat-card.blue::before   { background: var(--primary); }
  .tm-stat-card.green::before  { background: var(--green); }
  .tm-stat-card.yellow::before { background: var(--yellow); }
  .tm-stat-card.red::before    { background: var(--red); }

  .tm-stat-info { display: flex; flex-direction: column; gap: 4px; }
  .tm-stat-label {
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--text-light);
  }
  .tm-stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1;
    letter-spacing: -.03em;
  }
  .tm-stat-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 2px;
  }
  .tm-stat-icon.blue   { background: var(--primary-light); color: var(--primary); }
  .tm-stat-icon.green  { background: var(--green-bg);      color: var(--green); }
  .tm-stat-icon.yellow { background: var(--yellow-bg);     color: var(--yellow); }
  .tm-stat-icon.red    { background: var(--red-bg);        color: var(--red); }

  /* ── Table card ── */
  .tm-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow: hidden;
  }
  .tm-card-header {
    padding: 18px 24px;
    background: linear-gradient(135deg, var(--primary) 0%, #3b6fd4 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  .tm-card-title {
    font-size: .95rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -.01em;
  }
  .tm-card-title i { opacity: .85; }

  .tm-search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .tm-search-input {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    border-radius: var(--radius-sm);
    padding: 8px 12px;
    font-size: .85rem;
    font-family: 'Sora', sans-serif;
    min-width: 220px;
  }
  .tm-search-input::placeholder { color: rgba(255,255,255,.7); }
  .tm-search-input:focus { outline: none; background: rgba(255,255,255,.25); }
  .tm-search-select {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    font-size: .85rem;
    font-family: 'Sora', sans-serif;
  }
  .tm-search-select option { color: var(--text-main); }
  .tm-search-btn {
    background: #fff;
    color: var(--primary);
    border: none;
    border-radius: var(--radius-sm);
    padding: 8px 14px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
  }
  .tm-search-clear {
    color: rgba(255,255,255,.85);
    font-size: .8rem;
    text-decoration: underline;
  }

  /* scroll wrapper for mobile */
  .tm-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

  table.tm-table {
    width: 100%;
    border-collapse: collapse;
  }
  table.tm-table thead tr {
    background: var(--surface-2);
    border-bottom: 2px solid var(--border);
  }
  table.tm-table th {
    padding: 11px 16px;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-light);
    white-space: nowrap;
    text-align: left;
  }
  table.tm-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .12s;
  }
  table.tm-table tbody tr:last-child { border-bottom: none; }
  table.tm-table tbody tr:hover { background: var(--surface-2); }
  table.tm-table td {
    padding: 13px 16px;
    font-size: .82rem;
    color: var(--text-muted);
    white-space: nowrap;
    vertical-align: middle;
  }
  table.tm-table td.td-primary {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    color: var(--text-main);
    font-size: .78rem;
  }
  table.tm-table td.td-title {
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text-main);
    font-weight: 500;
  }
  table.tm-table td.td-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: .77rem;
  }

  .tm-empty-state {
    padding: 60px 24px;
    text-align: center;
    color: var(--text-light);
  }
  .tm-empty-state i { font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: .4; }
  .tm-empty-state p { font-size: .88rem; font-weight: 500; }

  /* ── Status badges ── */
  .tm-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px;
    border-radius: 100px;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .tm-badge::before {
    content: '';
    width: 5px; height: 5px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
  }
  .tm-badge.published { background: var(--green-bg);  color: var(--green-text);  }
  .tm-badge.published::before { background: var(--green); }
  .tm-badge.draft     { background: var(--yellow-bg); color: var(--yellow-text); }
  .tm-badge.draft::before     { background: var(--yellow); }
  .tm-badge.closed    { background: var(--red-bg);    color: var(--red-text);    }
  .tm-badge.closed::before    { background: var(--red); }

  /* ── Category chips ── */
  .cat-chips { display: flex; flex-wrap: wrap; gap: 4px; max-width: 200px; }
  .cat-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 100px;
    font-size: .65rem; font-weight: 600; color: #fff;
    white-space: nowrap;
  }
  .cat-chip-more {
    padding: 2px 8px; border-radius: 100px;
    font-size: .65rem; font-weight: 600;
    background: var(--surface-2); color: var(--text-light);
    border: 1px solid var(--border);
  }
  .cat-warn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 100px;
    font-size: .65rem; font-weight: 700;
    background: var(--orange-bg); color: var(--orange-text);
    border: 1px solid #fed7aa;
    white-space: nowrap;
  }
  .cat-all {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 100px;
    font-size: .65rem; font-weight: 600;
    background: var(--surface-2); color: var(--text-muted);
    border: 1px solid var(--border);
    white-space: nowrap;
  }

  /* ── Action buttons ── */
  .tm-actions { display: flex; gap: 6px; align-items: center; }
  .tm-action-btn {
    width: 30px; height: 30px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    background: var(--surface);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .75rem;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, border-color .15s, color .15s, transform .12s;
    color: var(--text-muted);
  }
  .tm-action-btn:hover { transform: translateY(-1px); text-decoration: none; }
  .tm-action-btn.view:hover    { background: #eff6ff; border-color: var(--primary); color: var(--primary); }
  .tm-action-btn.edit:hover    { background: var(--green-bg);  border-color: var(--green);  color: var(--green); }
  .tm-action-btn.publish:hover { background: var(--purple-bg); border-color: var(--purple); color: var(--purple); }
  .tm-action-btn.close-t:hover { background: var(--orange-bg); border-color: var(--orange); color: var(--orange); }
  .tm-action-btn.delete:hover  { background: var(--red-bg);    border-color: var(--red);    color: var(--red); }

  /* ── Pagination wrapper ── */
  .tm-pagination-wrap {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
  }

  /* ─────────────────────────────────────
     MODAL
  ───────────────────────────────────── */
  .tm-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(3px);
    z-index: 50;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    overflow-y: auto;
  }
  .tm-modal-overlay.open { display: flex; animation: fadeIn .18s ease; }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  .tm-modal {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 860px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp .22s ease;
    flex-shrink: 0;
  }
  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  .tm-modal-header {
    position: sticky; top: 0; z-index: 2;
    background: linear-gradient(135deg, var(--primary) 0%, #3b6fd4 100%);
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
  }
  .tm-modal-title {
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.01em;
  }
  .tm-modal-close {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(255,255,255,.15);
    border: none; cursor: pointer;
    color: #fff; font-size: .9rem;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
  }
  .tm-modal-close:hover { background: rgba(255,255,255,.28); }

  .tm-modal-body { padding: 24px; }

  /* Loading spinner */
  .tm-spinner {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 12px; padding: 56px 24px;
    color: var(--text-light);
  }
  .tm-spinner i { font-size: 2rem; color: var(--primary); }
  .tm-spinner p { font-size: .85rem; font-weight: 500; }

  /* Responsive helpers */
  @media (max-width: 640px) {
    .tm-stat-card { padding: 16px; }
    .tm-stat-value { font-size: 1.45rem; }
    table.tm-table th,
    table.tm-table td { padding: 10px 12px; }
    .tm-card-header { padding: 14px 16px; }
  }
</style>

<div class="tm-wrap">

  {{-- ── Page Header ── --}}
  <div class="tm-page-header">
    <h2 class="tm-page-title">
      <i class="fas fa-file-contract" style="color:var(--primary)"></i>
      Tender Management
      <span class="badge-live">Admin</span>
    </h2>
    <a href="{{ route($rp . '.tenders.create') }}" class="tm-btn-primary">
      <i class="fas fa-plus"></i> New Tender
    </a>
  </div>

  {{-- ── Stats ── --}}
  <div class="tm-stats">
    <div class="tm-stat-card blue">
      <div class="tm-stat-info">
        <span class="tm-stat-label">Total Tenders</span>
        <span class="tm-stat-value">{{ $stats['total'] }}</span>
      </div>
      <div class="tm-stat-icon blue"><i class="fas fa-file-alt"></i></div>
    </div>
    <div class="tm-stat-card green">
      <div class="tm-stat-info">
        <span class="tm-stat-label">Published</span>
        <span class="tm-stat-value">{{ $stats['published'] }}</span>
      </div>
      <div class="tm-stat-icon green"><i class="fas fa-check-circle"></i></div>
    </div>
    <div class="tm-stat-card yellow">
      <div class="tm-stat-info">
        <span class="tm-stat-label">Draft</span>
        <span class="tm-stat-value">{{ $stats['draft'] }}</span>
      </div>
      <div class="tm-stat-icon yellow"><i class="fas fa-pencil-alt"></i></div>
    </div>
    <div class="tm-stat-card red">
      <div class="tm-stat-info">
        <span class="tm-stat-label">Closed</span>
        <span class="tm-stat-value">{{ $stats['closed'] }}</span>
      </div>
      <div class="tm-stat-icon red"><i class="fas fa-times-circle"></i></div>
    </div>
  </div>

  {{-- ── Tenders Table ── --}}
  <div class="tm-card">
    <div class="tm-card-header">
      <span class="tm-card-title">
        <i class="fas fa-list"></i> All Tenders
      </span>
      <form method="GET" action="{{ route($rp . '.tenders') }}" class="tm-search-form">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title or tender no…" class="tm-search-input">
        <select name="status" class="tm-search-select">
          <option value="">All Status</option>
          <option value="draft" @selected(request('status') === 'draft')>Draft</option>
          <option value="published" @selected(request('status') === 'published')>Published</option>
          <option value="closed" @selected(request('status') === 'closed')>Closed</option>
        </select>
        <button type="submit" class="tm-search-btn"><i class="fas fa-search"></i> Search</button>
        @if(request('search') || request('status'))
        <a href="{{ route($rp . '.tenders') }}" class="tm-search-clear">Clear</a>
        @endif
      </form>
    </div>

    <div class="tm-table-scroll">
      <table class="tm-table">
        <thead>
          <tr>
            <th>Tender No.</th>
            <th>Title</th>
            <th>Est. Value</th>
            <th>Deadline</th>
            <th>Applications</th>
            <th>Status</th>
            <th>Categories</th>
            <th>Created By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($tenders as $tender)
          <tr>
            <td class="td-primary">{{ $tender->tender_number }}</td>
            <td class="td-title" title="{{ $tender->title }}">{{ Str::limit($tender->title, 40) }}</td>
            <td class="td-value">{{ $tender->estimated_value ? '₹' . number_format($tender->estimated_value, 2) : '—' }}</td>
            <td>{{ $tender->submission_deadline->format('d M, Y') }}</td>
            <td>
              <span style="background:var(--primary-light);color:var(--primary);padding:3px 10px;border-radius:100px;font-size:.72rem;font-weight:700;">
                {{ $tender->applications()->count() }}
              </span>
            </td>
            <td>
              @if($tender->status == 'published')
                <span class="tm-badge published">Published</span>
              @elseif($tender->status == 'draft')
                <span class="tm-badge draft">Draft</span>
              @else
                <span class="tm-badge closed">Closed</span>
              @endif
              @if($tender->isPrivate())
                <span style="background:#ede9fe;color:#6d28d9;padding:2px 8px;border-radius:100px;font-size:.68rem;font-weight:700;margin-left:4px;display:inline-flex;align-items:center;gap:3px;">
                  <i class="fas fa-lock" style="font-size:.6rem"></i> Private
                </span>
              @endif
            </td>
            <td>
              @php $cats = $tender->categories; @endphp
              @if($cats->isEmpty())
                @if($tender->status === 'draft')
                  <a href="{{ route($rp . '.tenders.edit', $tender->id) }}"
                     class="cat-warn" title="No categories assigned — assign before publishing so vendors are notified">
                    <i class="fas fa-exclamation-triangle" style="font-size:.6rem"></i> No Category
                  </a>
                @else
                  <span class="cat-all" title="No category filter — visible to all vendors">
                    <i class="fas fa-globe" style="font-size:.6rem"></i> All Vendors
                  </span>
                @endif
              @else
                <div class="cat-chips">
                  @foreach($cats->take(2) as $cat)
                    <span class="cat-chip" style="background-color:{{ $cat->color }}"
                          title="{{ $cat->name }}">
                      <i class="fas {{ $cat->icon }}" style="font-size:.55rem"></i>
                      {{ Str::limit($cat->name, 12) }}
                    </span>
                  @endforeach
                  @if($cats->count() > 2)
                    <span class="cat-chip-more">+{{ $cats->count() - 2 }}</span>
                  @endif
                </div>
              @endif
            </td>
            <td>
              @php
                  $creator = $tender->creator ?? $tender->owner;
                  $creatorRole = $creator?->getRoleNames()->first() ?? 'admin';
                  $roleColor = match($creatorRole) {
                      'super-admin'  => 'background:#fef3c7;color:#92400e',
                      'tender-owner' => 'background:#ede9fe;color:#6d28d9',
                      default        => 'background:#dbeafe;color:#1e40af',
                  };
              @endphp
              @if($creator)
              <div style="display:flex;flex-direction:column;gap:2px;">
                  <span style="font-weight:600;font-size:.8rem;color:#1e293b;">{{ $creator->name }}</span>
                  <span style="font-size:.68rem;padding:1px 7px;border-radius:100px;font-weight:600;width:fit-content;{{ $roleColor }}">
                      {{ ucfirst(str_replace('-', ' ', $creatorRole)) }}
                  </span>
                  <span style="font-size:.7rem;color:#94a3b8;">{{ $tender->created_at->format('d M, Y') }}</span>
              </div>
              @else
              <span style="color:#94a3b8;font-size:.8rem;">{{ $tender->created_at->format('d M, Y') }}</span>
              @endif
            </td>
            <td>
              <div class="tm-actions">
                <button onclick="viewTender({{ $tender->id }})" class="tm-action-btn view" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                @php $roleSegment = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
                <a href="/{{ $roleSegment }}/tenders/{{ $tender->id }}/analytics" class="tm-action-btn" style="color:#0891b2;" title="View Analytics (hits &amp; visitors)">
                  <i class="fas fa-chart-line"></i>
                </a>
                <a href="/{{ $roleSegment }}/tenders/{{ $tender->id }}/committee" class="tm-action-btn" style="color:#7c3aed;" title="Manage Committee">
                  <i class="fas fa-users"></i>
                </a>
                @if($tender->status == 'draft')
                  <a href="{{ route($rp . '.tenders.edit', $tender->id) }}" class="tm-action-btn edit" title="Edit Tender">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button onclick="openPublishModal({{ $tender->id }})" class="tm-action-btn publish" title="Publish">
                    <i class="fas fa-rocket"></i>
                  </button>
                @endif
                @if($tender->status == 'published')
                  <button onclick="openUniversityModal({{ $tender->id }}, {{ $tender->show_on_university ? 'true' : 'false' }}, {{ json_encode($tender->university_description ?? '') }})"
                          class="tm-action-btn"
                          style="color:{{ $tender->show_on_university ? '#2563eb' : '#94a3b8' }};"
                          title="{{ $tender->show_on_university ? 'Showing on University Website — click to update' : 'Not on University Website — click to enable' }}">
                    <i class="fas fa-globe"></i>
                  </button>
                  <button onclick="closeTender({{ $tender->id }})" class="tm-action-btn close-t" title="Close Tender">
                    <i class="fas fa-lock"></i>
                  </button>
                @endif
                @if($tender->canBeOpened())
                  <button onclick="openBidding({{ $tender->id }})" class="tm-action-btn" style="color:#059669;" title="Open Tender (sends a code to each Purchase Committee member's login)">
                    <i class="fas fa-envelope-open-text"></i>
                  </button>
                @elseif($tender->isOpeningOtpPending())
                  <a href="{{ route('purchase-committee.review.opening-codes', $tender->id) }}" class="tm-action-btn" style="color:#d97706;"
                     title="Collect committee codes — {{ $tender->openingOtpsVerifiedCount() }} of {{ $tender->openingOtpsIssuedCount() }} entered">
                    <i class="fas fa-hourglass-half"></i>
                  </a>
                @elseif($tender->isBidOpen())
                  <span class="tm-action-btn" style="color:#059669;cursor:default;" title="Bids opened {{ $tender->bid_opening_at->format('d M Y, h:i A') }}">
                    <i class="fas fa-envelope-open"></i>
                  </span>
                  @if($tender->hasPostalBids())
                  <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}" class="tm-action-btn" style="color:#0d9488;"
                     title="Record the documents physically received for this tender">
                    <i class="fas fa-file-arrow-up"></i>
                  </a>
                  @endif
                  <a href="{{ route('purchase-committee.award.finalize', $tender->id) }}" class="tm-action-btn" style="color:#1e293b;"
                     title="Finalize the award — record the chosen applicant and the committee discussion">
                    <i class="fas fa-gavel"></i>
                  </a>
                @endif
                <button onclick="deleteTender({{ $tender->id }})" class="tm-action-btn delete" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9">
              <div class="tm-empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No tenders found</p>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="tm-pagination-wrap">
      {{ $tenders->links() }}
    </div>
  </div>

</div>{{-- end .tm-wrap --}}


{{-- ── Publish Modal ── --}}
<div id="publishModal" class="tm-modal-overlay">
  <div class="tm-modal" style="max-width:520px;" role="dialog" aria-modal="true">
    <div class="tm-modal-header" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
      <span class="tm-modal-title"><i class="fas fa-rocket" style="margin-right:8px;opacity:.85"></i>Publish Tender</span>
      <button class="tm-modal-close" onclick="closePublishModal()" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="tm-modal-body" style="padding:24px;">
      <input type="hidden" id="publishTenderId">
      <p style="font-size:13.5px;color:#475569;margin:0 0 20px;">Publishing will make this tender visible to vendors.</p>

      {{-- University website option --}}
      <div style="border:1.5px solid #e2e8f0;border-radius:10px;padding:16px;background:#f8fafc;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <input type="checkbox" id="pub_showOnUni"
                 style="width:17px;height:17px;accent-color:#2563eb;flex-shrink:0;">
          <div>
            <span style="font-size:13.5px;font-weight:600;color:#1e293b;">Show on University Website</span>
            <p style="font-size:12px;color:#64748b;margin:2px 0 0;">Tick to display this tender on the university portal via API.</p>
          </div>
        </label>
        <div id="pub_uniDescWrap" style="display:none;margin-top:14px;">
          <label style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
            University Description <span style="color:#dc2626;">*</span>
          </label>
          <textarea id="pub_uniDesc" rows="4"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 12px;font-size:13.5px;font-family:inherit;resize:vertical;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#93c5fd'" onblur="this.style.borderColor='#e2e8f0'"
                    placeholder="Brief description for the university website…"></textarea>
          <p style="font-size:11.5px;color:#94a3b8;margin:4px 0 0;">This text will appear on the university website alongside the tender details.</p>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button onclick="closePublishModal()"
                style="font-size:13.5px;font-weight:500;padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;cursor:pointer;">
          Cancel
        </button>
        <button onclick="confirmPublish()"
                style="font-size:14px;font-weight:700;padding:10px 26px;border:none;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
          <i class="fas fa-rocket"></i> Publish Now
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ── University Display Modal (for published tenders) ── --}}
<div id="universityModal" class="tm-modal-overlay">
  <div class="tm-modal" style="max-width:520px;" role="dialog" aria-modal="true">
    <div class="tm-modal-header" style="background:linear-gradient(135deg,#0369a1,#2563eb);">
      <span class="tm-modal-title"><i class="fas fa-globe" style="margin-right:8px;opacity:.85"></i>University Website Display</span>
      <button class="tm-modal-close" onclick="closeUniversityModal()" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="tm-modal-body" style="padding:24px;">
      <input type="hidden" id="uniTenderId">
      <p style="font-size:13.5px;color:#475569;margin:0 0 20px;">Control whether this tender appears on the university website. You can change this at any time while the tender is active.</p>

      <div style="border:1.5px solid #e2e8f0;border-radius:10px;padding:16px;background:#f8fafc;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <input type="checkbox" id="uni_showOnUni"
                 style="width:17px;height:17px;accent-color:#2563eb;flex-shrink:0;">
          <div>
            <span style="font-size:13.5px;font-weight:600;color:#1e293b;">Show on University Website</span>
            <p style="font-size:12px;color:#64748b;margin:2px 0 0;">Fetched by the university portal at <code style="background:#e0f2fe;padding:1px 5px;border-radius:4px;font-size:11px;">/api/public/university/tenders</code></p>
          </div>
        </label>
        <div id="uni_uniDescWrap" style="display:none;margin-top:14px;">
          <label style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
            University Description <span style="color:#dc2626;">*</span>
          </label>
          <textarea id="uni_uniDesc" rows="4"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 12px;font-size:13.5px;font-family:inherit;resize:vertical;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#93c5fd'" onblur="this.style.borderColor='#e2e8f0'"
                    placeholder="Brief description for the university website…"></textarea>
          <p style="font-size:11.5px;color:#94a3b8;margin:4px 0 0;">This text will appear on the university website alongside the tender details.</p>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button onclick="closeUniversityModal()"
                style="font-size:13.5px;font-weight:500;padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;cursor:pointer;">
          Cancel
        </button>
        <button onclick="saveUniversityDisplay()"
                style="font-size:14px;font-weight:700;padding:10px 26px;border:none;border-radius:8px;background:linear-gradient(135deg,#0369a1,#2563eb);color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ── View Tender Modal ── --}}
<div id="viewTenderModal" class="tm-modal-overlay">
  <div class="tm-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="tm-modal-header">
      <span class="tm-modal-title" id="modalTitle">
        <i class="fas fa-file-contract" style="margin-right:8px;opacity:.85"></i>Tender Details
      </span>
      <button class="tm-modal-close" onclick="closeTenderModal()" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div id="tenderDetails" class="tm-modal-body">
      <div class="tm-spinner">
        <i class="fas fa-circle-notch fa-spin"></i>
        <p>Loading tender details…</p>
      </div>
    </div>
  </div>
</div>

{{-- ── Eligibility Question Modal ── --}}
<div id="eligibilityModal" class="fixed inset-0 bg-black/50 items-center justify-center z-[60] overflow-y-auto" style="display:none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="eligibilityModalTitle">Add Eligibility Question</h3>
            <button onclick="closeEligibilityModal()" class="text-white hover:text-gray-200"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="editingQuestionId" value="">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Question *</label>
                <input type="text" id="questionText" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Help text)</label>
                <textarea id="questionDescription" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Field Type *</label>
                    <select id="fieldType" onchange="toggleOptionsFields()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="text">Short Text</option>
                        <option value="textarea">Paragraph Text</option>
                        <option value="select">Dropdown Select</option>
                        <option value="radio">Radio Buttons</option>
                        <option value="checkbox">Checkboxes</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="file">File Upload</option>
                    </select>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center"><input type="checkbox" id="isRequired" checked class="mr-2"> Required</label>
                </div>
            </div>
            <div id="optionsField" class="mb-4" style="display:none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Options (comma separated)</label>
                <input type="text" id="questionOptions" placeholder="Option 1, Option 2, Option 3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div id="fileSizeField" class="mb-4" style="display:none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Max File Size (KB)</label>
                <input type="number" id="maxFileSize" value="5120" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeEligibilityModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button onclick="saveEligibilityQuestion()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Save Question</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Document Viewer Modal ── --}}
<div id="documentModal" class="fixed inset-0 bg-black/50 items-center justify-center z-[60] overflow-y-auto" style="display:none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="documentModalTitle">Document Viewer</h3>
            <button onclick="closeDocumentModal()" class="text-white hover:text-gray-200"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div id="documentViewer" class="p-6 min-h-[400px] flex items-center justify-center"></div>
        <div class="sticky bottom-0 bg-gray-50 border-t px-6 py-4 flex justify-end gap-3">
            <a id="downloadDocumentBtn" href="#" download class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Download
            </a>
            <button onclick="closeDocumentModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">Close</button>
        </div>
    </div>
</div>


<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const rolePrefix = '{{ $rp }}';
let currentTenderId = null;

function viewTender(id) {
  currentTenderId = id;
  const overlay    = document.getElementById('viewTenderModal');
  const detailsDiv = document.getElementById('tenderDetails');
  overlay.classList.add('open');
  detailsDiv.innerHTML = `
    <div class="tm-spinner">
      <i class="fas fa-circle-notch fa-spin"></i>
      <p>Loading tender details…</p>
    </div>`;

  fetch(`/${rolePrefix}/tenders/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(response => response.text())
    .then(html => { detailsDiv.innerHTML = html; })
    .catch(() => {
      detailsDiv.innerHTML = `<p style="color:var(--red);text-align:center;padding:32px;">Failed to load tender details.</p>`;
    });
}

function closeTenderModal() {
  document.getElementById('viewTenderModal').classList.remove('open');
}

// Close on overlay click
document.getElementById('viewTenderModal').addEventListener('click', function(e) {
  if (e.target === this) closeTenderModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeTenderModal();
});

/* ── Publish modal ── */
function openPublishModal(id) {
  document.getElementById('publishTenderId').value = id;
  document.getElementById('publishModal').classList.add('open');
}
function closePublishModal() {
  document.getElementById('publishModal').classList.remove('open');
  document.getElementById('publishTenderId').value = '';
  document.getElementById('pub_showOnUni').checked = false;
  document.getElementById('pub_uniDescWrap').style.display = 'none';
  document.getElementById('pub_uniDesc').value = '';
}
document.getElementById('pub_showOnUni').addEventListener('change', function () {
  document.getElementById('pub_uniDescWrap').style.display = this.checked ? '' : 'none';
});
function confirmPublish() {
  const id       = document.getElementById('publishTenderId').value;
  const showOnUni = document.getElementById('pub_showOnUni').checked;
  const desc      = document.getElementById('pub_uniDesc').value.trim();
  const rp        = rolePrefix;

  if (showOnUni && !desc) {
    document.getElementById('pub_uniDesc').focus();
    document.getElementById('pub_uniDesc').style.borderColor = '#dc2626';
    return;
  }

  fetch(`/${rp}/tenders/${id}/publish`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ show_on_university: showOnUni ? 1 : 0, university_description: desc })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) { closePublishModal(); location.reload(); }
    else { alert(data.message || 'Failed to publish.'); }
  });
}

/* ── University display modal (for already-published tenders) ── */
function openUniversityModal(id, currentShow, currentDesc) {
  document.getElementById('uniTenderId').value = id;
  document.getElementById('uni_showOnUni').checked = !!currentShow;
  document.getElementById('uni_uniDesc').value = currentDesc || '';
  document.getElementById('uni_uniDescWrap').style.display = currentShow ? '' : 'none';
  document.getElementById('universityModal').classList.add('open');
}
function closeUniversityModal() {
  document.getElementById('universityModal').classList.remove('open');
}
document.getElementById('uni_showOnUni').addEventListener('change', function () {
  document.getElementById('uni_uniDescWrap').style.display = this.checked ? '' : 'none';
});
function saveUniversityDisplay() {
  const id        = document.getElementById('uniTenderId').value;
  const showOnUni = document.getElementById('uni_showOnUni').checked;
  const desc      = document.getElementById('uni_uniDesc').value.trim();
  const rp        = rolePrefix;

  if (showOnUni && !desc) {
    document.getElementById('uni_uniDesc').focus();
    document.getElementById('uni_uniDesc').style.borderColor = '#dc2626';
    return;
  }

  fetch(`/${rp}/tenders/${id}/university-display`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ show_on_university: showOnUni ? 1 : 0, university_description: desc })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) { closeUniversityModal(); location.reload(); }
    else { alert(data.message || 'Failed to update.'); }
  });
}

function closeTender(id) {
  const reason = prompt('Close this tender?\n\nEnter reason for closure (will be emailed to vendors who applied):');
  if (reason === null) return;
  fetch(`/${rolePrefix}/tenders/${id}/close`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ reason: reason })
  }).then(r => r.json()).then(data => {
    if (data.success) location.reload();
    else alert(data.message || 'Failed to close tender.');
  });
}

function openBidding(id) {
  if (!confirm('Send an opening code to every active Purchase Committee member? Each member receives their code in their own login and reads it out to you in person — you then enter all of them on the next screen, and the tender opens once every code is in.')) return;
  fetch(`/${rolePrefix}/tenders/${id}/open-bidding`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
  }).then(r => r.json()).then(data => {
    if (data.success) {
      alert(data.message);
      /* Straight to the screen where the officer keys in the members' codes. */
      window.location.href = data.redirect || window.location.href;
    }
    else alert(data.message || 'Failed to open bidding.');
  });
}

function deleteTender(id) {
  if (confirm('Delete this tender? This action cannot be undone.')) {
    fetch(`/${rolePrefix}/tenders/${id}`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(() => location.reload());
  }
}

/* ── Eligibility Question Functions ── */
function addEligibilityQuestion() {
    document.getElementById('eligibilityModalTitle').innerText = 'Add Eligibility Question';
    document.getElementById('editingQuestionId').value = '';
    document.getElementById('questionText').value = '';
    document.getElementById('questionDescription').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('isRequired').checked = true;
    document.getElementById('questionOptions').value = '';
    document.getElementById('maxFileSize').value = '5120';
    toggleOptionsFields();
    document.getElementById('eligibilityModal').style.display = 'flex';
}

function editEligibilityQuestion(questionId) {
    const rp = rolePrefix;
    fetch(`/${rp}/tenders/questions/${questionId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('eligibilityModalTitle').innerText = 'Edit Eligibility Question';
                document.getElementById('editingQuestionId').value = questionId;
                document.getElementById('questionText').value = data.question.question;
                document.getElementById('questionDescription').value = data.question.description || '';
                document.getElementById('fieldType').value = data.question.field_type;
                document.getElementById('isRequired').checked = data.question.is_required;
                document.getElementById('questionOptions').value = data.question.options
                    ? (Array.isArray(data.question.options) ? data.question.options.join(', ') : data.question.options) : '';
                document.getElementById('maxFileSize').value = data.question.max_size || 5120;
                toggleOptionsFields();
                document.getElementById('eligibilityModal').style.display = 'flex';
            }
        });
}

function removeEligibilityQuestion(questionId) {
    if (confirm('Are you sure you want to remove this eligibility question?')) {
        const rp = rolePrefix;
        fetch(`/${rp}/tenders/questions/${questionId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(data => {
            if (data.success) { location.reload(); }
            else { alert('Failed to remove question'); }
        });
    }
}

function toggleOptionsFields() {
    const ft = document.getElementById('fieldType').value;
    document.getElementById('optionsField').style.display =
        (ft === 'select' || ft === 'radio' || ft === 'checkbox') ? 'block' : 'none';
    document.getElementById('fileSizeField').style.display = ft === 'file' ? 'block' : 'none';
}

function saveEligibilityQuestion() {
    const questionText = document.getElementById('questionText').value;
    if (!questionText) { alert('Please enter a question'); return; }
    const editingId = document.getElementById('editingQuestionId').value;
    const rp = rolePrefix;
    const url = editingId
        ? `/${rp}/tenders/questions/${editingId}`
        : `/${rp}/tenders/${currentTenderId}/questions`;
    fetch(url, {
        method: editingId ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            question: questionText,
            description: document.getElementById('questionDescription').value,
            field_type: document.getElementById('fieldType').value,
            is_required: document.getElementById('isRequired').checked,
            options: document.getElementById('questionOptions').value,
            max_size: document.getElementById('maxFileSize').value
        })
    }).then(r => r.json()).then(data => {
        if (data.success) { location.reload(); }
        else { alert('Failed to save question: ' + (data.message || '')); }
    });
}

function closeEligibilityModal() {
    document.getElementById('eligibilityModal').style.display = 'none';
}

/* ── Document Viewer Functions ── */
function viewDocument(fileUrl, fileName) {
    const modal = document.getElementById('documentModal');
    const viewer = document.getElementById('documentViewer');
    document.getElementById('documentModalTitle').textContent = fileName;
    document.getElementById('downloadDocumentBtn').href = fileUrl;
    document.getElementById('downloadDocumentBtn').download = fileName;
    const ext = fileName.split('.').pop().toLowerCase();
    if (['jpg','jpeg','png','gif'].includes(ext)) {
        viewer.innerHTML = `<img src="${fileUrl}" alt="${fileName}" class="max-w-full max-h-[70vh] object-contain">`;
    } else if (ext === 'pdf') {
        viewer.innerHTML = `<iframe src="${fileUrl}" class="w-full h-[70vh] rounded-lg" frameborder="0"></iframe>`;
    } else {
        viewer.innerHTML = `<div class="text-center py-12"><i class="fas fa-file-alt text-6xl text-gray-400 mb-4"></i><p class="text-gray-600">Preview not available. Use the download button.</p></div>`;
    }
    modal.style.display = 'flex';
}

function closeDocumentModal() {
    document.getElementById('documentModal').style.display = 'none';
    document.getElementById('documentViewer').innerHTML = '';
}
document.getElementById('documentModal').addEventListener('click', function(e) {
    if (e.target === this) closeDocumentModal();
});
</script>

@endsection