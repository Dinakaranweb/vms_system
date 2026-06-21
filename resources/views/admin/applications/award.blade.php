@extends('layouts.dashboard')

@section('title', 'Award Application & Create Order')
@section('header', 'Award Application')

@section('content')
@php
    $rolePrefix = str_starts_with(request()->route()->getName(), 'super-admin.') ? 'super-admin' : 'admin';
    $postAction = route("{$rolePrefix}.applications.award", $application->id);
    $backUrl    = route("{$rolePrefix}.applications");
@endphp

<style>
:root {
    --aw-blue:    #1a56db;
    --aw-indigo:  #4f46e5;
    --aw-green:   #059669;
    --aw-amber:   #d97706;
    --aw-red:     #dc2626;
    --aw-border:  #e2e8f0;
    --aw-surface: #f8fafc;
    --aw-text:    #0f172a;
    --aw-muted:   #94a3b8;
    --aw-radius:  12px;
}
.aw-wrap { max-width:1100px; margin:0 auto; padding:0 0 3rem; font-family:'DM Sans',sans-serif; }

/* ── Summary banner ── */
.aw-banner {
    background:linear-gradient(135deg,#1e3a8a,#4f46e5);
    border-radius:var(--aw-radius);
    padding:22px 28px;
    color:#fff;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:24px;
}
.aw-banner-title { font-size:18px; font-weight:700; margin:0 0 4px; letter-spacing:-.3px; }
.aw-banner-sub   { font-size:13px; color:rgba(255,255,255,.7); margin:0; }
.aw-banner-meta  { display:flex; gap:24px; flex-wrap:wrap; }
.aw-meta-item    { text-align:right; }
.aw-meta-label   { font-size:11px; color:rgba(255,255,255,.6); text-transform:uppercase; letter-spacing:.5px; }
.aw-meta-val     { font-size:15px; font-weight:700; }

/* ── Cards ── */
.aw-card {
    background:#fff;
    border:1px solid var(--aw-border);
    border-radius:var(--aw-radius);
    overflow:hidden;
    margin-bottom:20px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
}
.aw-card-hd {
    padding:16px 24px;
    border-bottom:1px solid var(--aw-border);
    display:flex;
    align-items:center;
    gap:10px;
    background:var(--aw-surface);
}
.aw-card-hd-ico {
    width:34px; height:34px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; flex-shrink:0;
}
.ico-blue   { background:#dbeafe; color:#1d4ed8; }
.ico-green  { background:#d1fae5; color:#059669; }
.ico-purple { background:#ede9fe; color:#7c3aed; }
.ico-amber  { background:#fef3c7; color:#d97706; }
.aw-card-hd h3 { font-size:14px; font-weight:600; color:var(--aw-text); margin:0; }
.aw-card-body  { padding:24px; }

/* ── Form fields ── */
.aw-grid  { display:grid; gap:16px; }
.aw-grid-2 { grid-template-columns:1fr 1fr; }
.aw-grid-3 { grid-template-columns:1fr 1fr 1fr; }
.aw-col-2  { grid-column:1/-1; }
.aw-field  { display:flex; flex-direction:column; gap:5px; }
.aw-label  { font-size:12px; font-weight:600; color:#475569; text-transform:uppercase; letter-spacing:.5px; }
.aw-label .req { color:var(--aw-red); }
.aw-input, .aw-select, .aw-textarea {
    font-family:'DM Sans',sans-serif;
    font-size:14px;
    color:var(--aw-text);
    background:#fff;
    border:1.5px solid var(--aw-border);
    border-radius:8px;
    padding:9px 12px;
    width:100%;
    box-sizing:border-box;
    outline:none;
    transition:border-color .15s, box-shadow .15s;
    -webkit-appearance:none;
}
.aw-input:focus, .aw-select:focus, .aw-textarea:focus {
    border-color:#93c5fd;
    box-shadow:0 0 0 3px rgba(59,130,246,.15);
}
.aw-textarea { resize:vertical; min-height:72px; line-height:1.5; }
.aw-hint { font-size:11px; color:var(--aw-muted); margin:0; }

/* ── Mode toggle tabs ── */
.mode-tabs { display:flex; gap:0; border:1.5px solid var(--aw-border); border-radius:8px; overflow:hidden; margin-bottom:20px; }
.mode-tab {
    flex:1; padding:10px 16px; font-size:13px; font-weight:600; text-align:center;
    cursor:pointer; border:none; background:#f8fafc; color:#64748b;
    transition:background .15s, color .15s; display:flex; align-items:center; justify-content:center; gap:7px;
    font-family:'DM Sans',sans-serif;
}
.mode-tab.active { background:#1a56db; color:#fff; }
.mode-tab:first-child { border-right:1.5px solid var(--aw-border); }

/* ── Order type tiles ── */
.order-type-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
.order-type-tile {
    border:1.5px solid var(--aw-border); border-radius:10px;
    padding:14px 16px; cursor:pointer; display:flex; align-items:center; gap:12px;
    background:#f8fafc; transition:border-color .15s, background .15s;
}
.order-type-tile input[type="radio"] { display:none; }
.order-type-tile.selected { border-color:var(--aw-blue); background:#eff6ff; }
.order-type-tile .ot-ico { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
.ot-ico-blue { background:#dbeafe; color:#1d4ed8; }
.ot-ico-green{ background:#d1fae5; color:#059669; }
.order-type-tile b { font-size:13.5px; color:var(--aw-text); display:block; }
.order-type-tile small { font-size:11.5px; color:var(--aw-muted); }

/* ── Items table ── */
#itemsTable { width:100%; border-collapse:collapse; font-size:13px; }
#itemsTable thead tr { background:#f1f5f9; }
#itemsTable th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
#itemsTable th:last-child { width:36px; }
#itemsTable td { padding:6px 6px; vertical-align:middle; }
#itemsTable td input { border:1.5px solid #e2e8f0; border-radius:6px; padding:6px 8px; font-size:13px; width:100%; outline:none; font-family:'DM Sans',sans-serif; }
#itemsTable td input:focus { border-color:#93c5fd; }
.btn-rm-row { background:none; border:1px solid #fca5a5; color:#dc2626; border-radius:5px; padding:4px 7px; cursor:pointer; font-size:11px; }
.btn-rm-row:hover { background:#fef2f2; }

/* ── Totals ── */
.totals-box { margin-top:14px; display:flex; justify-content:flex-end; }
.totals-inner { width:280px; }
.totals-row { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid #f1f5f9; font-size:13px; color:#475569; }
.totals-row:last-child { border-bottom:none; border-top:2px solid #dbeafe; padding-top:10px; font-size:15px; font-weight:700; color:#1d4ed8; margin-top:4px; }
.totals-row input { width:100px; text-align:right; border:1.5px solid #e2e8f0; border-radius:6px; padding:4px 8px; font-size:13px; font-family:'DM Sans',sans-serif; outline:none; }
.totals-row input:focus { border-color:#93c5fd; }

/* ── Terms ── */
.term-item { display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1px solid var(--aw-border); border-radius:8px; background:#f8fafc; margin-bottom:8px; }
.term-item input[type="checkbox"] { margin-top:3px; width:15px; height:15px; accent-color:#1a56db; flex-shrink:0; }
.term-key  { font-size:12px; font-weight:600; color:#475569; margin-bottom:3px; }
.term-val  { width:100%; font-size:12.5px; color:var(--aw-text); border:1px solid #e2e8f0; border-radius:6px; padding:6px 8px; resize:vertical; font-family:'DM Sans',sans-serif; outline:none; background:#fff; }
.term-val:focus { border-color:#93c5fd; }

/* ── Attachment zone ── */
.attach-zone {
    border:2px dashed #bfdbfe; border-radius:10px; padding:36px 24px;
    text-align:center; background:#eff6ff; cursor:pointer; transition:border-color .15s;
    position:relative;
}
.attach-zone:hover { border-color:#3b82f6; }
.attach-zone input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.attach-zone i { font-size:32px; color:#3b82f6; margin-bottom:10px; display:block; }
.attach-zone p { margin:0; color:#1e40af; font-size:14px; font-weight:600; }
.attach-zone small { color:#64748b; font-size:12px; }
#attachFileName { margin-top:10px; font-size:13px; color:#059669; font-weight:500; display:none; }

/* ── Error box ── */
.aw-errors { background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:14px 16px; margin-bottom:20px; font-size:13px; color:#dc2626; }
.aw-errors ul { margin:6px 0 0 18px; padding:0; }

/* ── Footer ── */
.aw-footer { display:flex; justify-content:flex-end; align-items:center; gap:12px; padding-top:20px; }
.btn-back { font-family:'DM Sans',sans-serif; font-size:13.5px; font-weight:500; color:#475569; background:#fff; border:1.5px solid var(--aw-border); border-radius:8px; padding:9px 20px; text-decoration:none; display:inline-flex; align-items:center; gap:7px; transition:background .15s; }
.btn-back:hover { background:#f1f5f9; color:var(--aw-text); }
.btn-award { font-family:'DM Sans',sans-serif; font-size:14px; font-weight:700; color:#fff; background:linear-gradient(135deg,#059669,#047857); border:none; border-radius:8px; padding:11px 28px; cursor:pointer; display:inline-flex; align-items:center; gap:9px; box-shadow:0 3px 12px rgba(5,150,105,.3); transition:all .15s; }
.btn-award:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(5,150,105,.4); }

@media(max-width:640px) {
    .aw-grid-2, .aw-grid-3, .order-type-grid { grid-template-columns:1fr; }
    .aw-col-2 { grid-column:1; }
    .aw-banner { flex-direction:column; }
    .aw-banner-meta { justify-content:flex-start; }
    .aw-meta-item { text-align:left; }
}
</style>

<div class="aw-wrap">

    {{-- Back link --}}
    <div style="margin-bottom:16px;">
        <a href="{{ $backUrl }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1" style="width:fit-content;">
            <i class="fas fa-arrow-left"></i> Back to Applications
        </a>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
    <div class="aw-errors">
        <strong><i class="fas fa-exclamation-circle mr-1"></i> Please fix the following:</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Application summary banner --}}
    <div class="aw-banner">
        <div>
            <p class="aw-banner-title">
                <i class="fas fa-trophy" style="margin-right:8px;color:#fbbf24;"></i>
                Award Application &amp; Create {{ strtolower(str_contains(request()->fullUrl(), 'super')) ? '' : '' }}WO / PO
            </p>
            <p class="aw-banner-sub">
                {{ $application->application_number }} &nbsp;·&nbsp;
                {{ $application->tender->title ?? 'N/A' }} &nbsp;·&nbsp;
                {{ $application->vendor->company_name ?? 'N/A' }}
            </p>
        </div>
        <div class="aw-banner-meta">
            @if($application->bid_amount)
            <div class="aw-meta-item">
                <div class="aw-meta-label">Bid Amount</div>
                <div class="aw-meta-val">₹ {{ number_format($application->bid_amount, 2) }}</div>
            </div>
            @endif
            <div class="aw-meta-item">
                <div class="aw-meta-label">Tender No.</div>
                <div class="aw-meta-val" style="font-size:13px;">{{ $application->tender->tender_number ?? '—' }}</div>
            </div>
            <div class="aw-meta-item">
                <div class="aw-meta-label">Vendor Code</div>
                <div class="aw-meta-val" style="font-size:13px;">{{ $application->vendor->vendor_code ?? '—' }}</div>
            </div>
        </div>
    </div>

    @if($isPartialOrder ?? false)
    <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-layer-group" style="font-size:16px;color:#d97706;"></i>
        <span>Creating a <strong>Partial Order</strong> — this will be an additional WO/PO linked to the same application.</span>
    </div>
    @endif

    <form method="POST" action="{{ $postAction }}" enctype="multipart/form-data" id="awardForm" novalidate>
        @csrf
        <input type="hidden" name="order_mode" id="orderModeInput" value="details">
        @if($isPartialOrder ?? false)
        <input type="hidden" name="is_partial_order" value="1">
        @endif

        <div style="display:grid; grid-template-columns:1fr 360px; gap:20px; align-items:start;">

        {{-- ══ LEFT COLUMN ══ --}}
        <div>

            {{-- Section 1: Contract Details --}}
            <div class="aw-card">
                <div class="aw-card-hd">
                    <div class="aw-card-hd-ico ico-blue"><i class="fas fa-file-contract"></i></div>
                    <h3>Contract Details</h3>
                </div>
                <div class="aw-card-body">
                    <div class="aw-grid aw-grid-2">
                        <div class="aw-field">
                            <label class="aw-label">Start Date <span class="req">*</span></label>
                            <input type="date" name="start_date" class="aw-input" required
                                   value="{{ old('start_date', date('Y-m-d')) }}">
                        </div>
                        <div class="aw-field">
                            <label class="aw-label">End Date <span class="req">*</span></label>
                            <input type="date" name="end_date" class="aw-input" required
                                   value="{{ old('end_date') }}">
                        </div>
                        <div class="aw-field">
                            <label class="aw-label">Contract Value (₹)
                                @if($application->tender->require_bid_amount ?? true)
                                <span class="req">*</span>
                                @else
                                <span style="color:#94a3b8;font-weight:400;text-transform:none;letter-spacing:0;">(optional – bid value not required)</span>
                                @endif
                            </label>
                            <input type="number" name="contract_value" class="aw-input"
                                   step="0.01" min="0"
                                   value="{{ old('contract_value', $application->bid_amount > 0 ? $application->bid_amount : '') }}"
                                   placeholder="0.00">
                        </div>
                        <div class="aw-field">
                            <label class="aw-label">Order Options</label>
                            <label style="display:flex;align-items:center;gap:8px;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;background:#f8fafc;">
                                <input type="checkbox" name="is_partial_order" value="1" id="partialOrderCheck"
                                       {{ (old('is_partial_order') || ($isPartialOrder ?? false)) ? 'checked' : '' }}
                                       style="width:16px;height:16px;accent-color:#d97706;flex-shrink:0;">
                                <div>
                                    <span style="font-size:13px;font-weight:600;color:#1e293b;">Partial Order</span>
                                    <p style="font-size:11px;color:#64748b;margin:0;">Multiple WOs/POs allowed for this tender</p>
                                </div>
                            </label>
                        </div>
                        <div class="aw-field aw-col-2">
                            <label class="aw-label">Payment Terms</label>
                            <textarea name="payment_terms" class="aw-textarea"
                                      placeholder="e.g. 30% advance, 70% on completion…">{{ old('payment_terms') }}</textarea>
                        </div>
                        <div class="aw-field aw-col-2">
                            <label class="aw-label">Deliverables</label>
                            <textarea name="deliverables" class="aw-textarea"
                                      placeholder="List key deliverables…">{{ old('deliverables') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Work Order / Purchase Order --}}
            <div class="aw-card">
                <div class="aw-card-hd">
                    <div class="aw-card-hd-ico ico-green"><i class="fas fa-clipboard-list"></i></div>
                    <h3>Work Order / Purchase Order</h3>
                </div>
                <div class="aw-card-body">

                    {{-- Order type selector --}}
                    <label class="aw-label" style="margin-bottom:8px;display:block;">Order Type <span class="req">*</span></label>
                    <div class="order-type-grid">
                        <label class="order-type-tile selected" id="tile_wo" onclick="selectOrderType('work_order')">
                            <input type="radio" name="order_type" value="work_order"
                                   {{ old('order_type', 'work_order') === 'work_order' ? 'checked' : '' }}>
                            <div class="ot-ico ot-ico-blue"><i class="fas fa-hard-hat"></i></div>
                            <div>
                                <b>Work Order</b>
                                <small>Services / construction work</small>
                            </div>
                        </label>
                        <label class="order-type-tile" id="tile_po" onclick="selectOrderType('purchase_order')">
                            <input type="radio" name="order_type" value="purchase_order"
                                   {{ old('order_type') === 'purchase_order' ? 'checked' : '' }}>
                            <div class="ot-ico ot-ico-green"><i class="fas fa-shopping-cart"></i></div>
                            <div>
                                <b>Purchase Order</b>
                                <small>Goods / materials supply</small>
                            </div>
                        </label>
                    </div>

                    {{-- Order date + number --}}
                    <div class="aw-grid aw-grid-2" style="margin-bottom:16px;">
                        <div class="aw-field">
                            <label class="aw-label">Order Date <span class="req">*</span></label>
                            <input type="date" name="order_date" class="aw-input" required
                                   value="{{ old('order_date', date('Y-m-d')) }}">
                        </div>
                        <div class="aw-field">
                            <label class="aw-label">Order Number</label>
                            <input type="text" name="custom_order_number" class="aw-input"
                                   value="{{ old('custom_order_number') }}"
                                   placeholder="{{ $previewOrderNumber }} (auto if blank)">
                            <p class="aw-hint">Leave blank to auto-generate. Or enter any custom number.</p>
                        </div>
                    </div>

                    {{-- References --}}
                    <div class="aw-grid aw-grid-2" style="margin-bottom:20px;">
                        <div class="aw-field">
                            <label class="aw-label">VMRFDU Ref.</label>
                            <input type="text" name="vmrfdu_ref" class="aw-input"
                                   value="{{ old('vmrfdu_ref') }}"
                                   placeholder="e.g. 3204 / 22.05.2026">
                        </div>
                        <div class="aw-field">
                            <label class="aw-label">Supplier / Vendor Ref.</label>
                            <input type="text" name="supplier_ref" class="aw-input"
                                   value="{{ old('supplier_ref') }}"
                                   placeholder="e.g. Quote 02.06.2026">
                        </div>
                    </div>

                    {{-- Mode toggle --}}
                    <div class="mode-tabs" role="tablist">
                        <button type="button" class="mode-tab active" id="tab-details" onclick="switchMode('details')">
                            <i class="fas fa-table"></i> Fill Line Items
                        </button>
                        <button type="button" class="mode-tab" id="tab-attach" onclick="switchMode('attachment')">
                            <i class="fas fa-paperclip"></i> Attach Document
                        </button>
                    </div>

                    {{-- DETAILS MODE --}}
                    <div id="modeDetails">
                        {{-- Line items table --}}
                        <div style="overflow-x:auto;margin-bottom:14px;">
                            <table id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:24px;">#</th>
                                        <th>Description</th>
                                        <th style="width:68px;text-align:center;">Qty</th>
                                        <th style="width:72px;text-align:center;">UOM</th>
                                        <th style="width:100px;text-align:right;">Rate (₹)</th>
                                        <th style="width:100px;text-align:right;">Amount (₹)</th>
                                        <th style="width:36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addRow()"
                                style="font-size:12px;padding:6px 14px;background:#f0fdf4;border:1.5px solid #86efac;color:#15803d;border-radius:6px;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;gap:5px;">
                            <i class="fas fa-plus"></i> Add Row
                        </button>

                        <div class="totals-box">
                            <div class="totals-inner">
                                <div class="totals-row">
                                    <span>Subtotal</span>
                                    <span id="subtotalDisplay">₹ 0.00</span>
                                </div>
                                <div class="totals-row">
                                    <span>Discount (₹)</span>
                                    <input type="number" name="order_discount" id="discountInput"
                                           min="0" step="0.01" value="{{ old('order_discount', 0) }}"
                                           oninput="calcTotals()">
                                </div>
                                <div class="totals-row">
                                    <span>GST (%)</span>
                                    <input type="number" name="gst_rate" id="gstInput"
                                           min="0" max="100" step="0.01" value="{{ old('gst_rate', 18) }}"
                                           oninput="calcTotals()">
                                </div>
                                <div class="totals-row">
                                    <span>GST Amount</span>
                                    <span id="gstDisplay">₹ 0.00</span>
                                </div>
                                <div class="totals-row">
                                    <span>NETT TOTAL</span>
                                    <span id="nettDisplay">₹ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="aw-field" style="margin-top:16px;">
                            <label class="aw-label">Additional Notes</label>
                            <textarea name="order_notes" class="aw-textarea"
                                      placeholder="Any additional instructions…">{{ old('order_notes') }}</textarea>
                        </div>
                    </div>

                    {{-- ATTACHMENT MODE --}}
                    <div id="modeAttach" style="display:none;">
                        <div class="attach-zone" id="dropZone">
                            <input type="file" name="order_attachment" accept=".pdf,.doc,.docx,.jpg,.png"
                                   onchange="showFileName(this)">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click or drag &amp; drop the WO / PO document</p>
                            <small>PDF, DOC, DOCX accepted &nbsp;·&nbsp; Max 10 MB</small>
                            <div id="attachFileName"></div>
                        </div>
                        <p class="aw-hint" style="margin-top:8px;">
                            <i class="fas fa-info-circle mr-1"></i>
                            The attached file will be stored as the order document on the contract.
                            You can still regenerate it later via the contract page.
                        </p>
                    </div>

                </div>
            </div>

        </div>

        {{-- ══ RIGHT COLUMN: Terms ══ --}}
        <div id="termsColumn">
            <div class="aw-card" style="position:sticky;top:16px;">
                <div class="aw-card-hd">
                    <div class="aw-card-hd-ico ico-purple"><i class="fas fa-gavel"></i></div>
                    <h3>Terms &amp; Conditions</h3>
                </div>
                <div class="aw-card-body" style="padding:16px;">
                    <p style="font-size:12px;color:var(--aw-muted);margin:0 0 12px;">
                        Check clauses to include. Edit text inline.
                    </p>
                    <div id="tcContainer"></div>
                </div>
            </div>
        </div>

        </div>{{-- end two-col grid --}}

        {{-- Footer --}}
        <div class="aw-footer">
            <a href="{{ $backUrl }}" class="btn-back">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
            <button type="submit" class="btn-award">
                <i class="fas fa-trophy"></i> Award &amp; Create Order
            </button>
        </div>

    </form>
</div>

@push('scripts')
<script>
const presets = @json($presets);
let rowCount   = 0;
let currentMode = 'details';
let currentType = '{{ old("order_type", "work_order") }}';

/* ── Order type ── */
function selectOrderType(type) {
    currentType = type;
    document.querySelector('input[name="order_type"][value="work_order"]').checked = (type === 'work_order');
    document.querySelector('input[name="order_type"][value="purchase_order"]').checked = (type === 'purchase_order');
    document.getElementById('tile_wo').classList.toggle('selected', type === 'work_order');
    document.getElementById('tile_po').classList.toggle('selected', type === 'purchase_order');
    renderTerms();
}

/* ── Mode switch ── */
function switchMode(mode) {
    currentMode = mode;
    document.getElementById('orderModeInput').value = mode;
    document.getElementById('tab-details').classList.toggle('active', mode === 'details');
    document.getElementById('tab-attach').classList.toggle('active', mode === 'attachment');
    document.getElementById('modeDetails').style.display = mode === 'details' ? '' : 'none';
    document.getElementById('modeAttach').style.display  = mode === 'attachment' ? '' : 'none';
    document.getElementById('termsColumn').style.display = mode === 'details' ? '' : 'none';

    /* GST/discount are only relevant in details mode — remove required when hidden */
    const gst = document.getElementById('gstInput');
    if (gst) gst.required = (mode === 'details');
}

/* ── Attachment file name ── */
function showFileName(input) {
    const el = document.getElementById('attachFileName');
    if (input.files && input.files[0]) {
        el.textContent = '✓ ' + input.files[0].name;
        el.style.display = 'block';
    }
}

/* ── Line items ── */
function addRow(desc='', qty='1', uom='Nos', rate='', amount='') {
    const body = document.getElementById('itemsBody');
    const idx  = rowCount++;
    const tr   = document.createElement('tr');
    tr.dataset.row = idx;
    tr.innerHTML = `
        <td style="color:#94a3b8;font-size:12px;padding:6px 8px;">${body.rows.length + 1}</td>
        <td><input type="text"   name="items[${idx}][description]" value="${esc(desc)}" placeholder="Item description"></td>
        <td><input type="number" name="items[${idx}][qty]"         value="${esc(qty)}"  placeholder="1"  min="0" step="0.01" oninput="calcRow(this)" style="text-align:center;"></td>
        <td><input type="text"   name="items[${idx}][uom]"         value="${esc(uom)}"  placeholder="Nos" style="text-align:center;"></td>
        <td><input type="number" name="items[${idx}][rate]"        value="${esc(rate)}" placeholder="0.00" min="0" step="0.01" oninput="calcRow(this)" style="text-align:right;"></td>
        <td style="text-align:right;padding-right:6px;color:#0f172a;font-weight:500;" class="row-amount">₹ 0.00</td>
        <td><button type="button" class="btn-rm-row" onclick="removeRow(this)"><i class="fas fa-times"></i></button></td>
    `;
    body.appendChild(tr);
    if (amount) {
        tr.querySelector('.row-amount').textContent = '₹ ' + parseFloat(amount).toFixed(2);
    }
    reindex();
    calcTotals();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    reindex();
    calcTotals();
}

function reindex() {
    document.querySelectorAll('#itemsBody tr').forEach((tr, i) => {
        tr.cells[0].textContent = i + 1;
    });
}

function calcRow(input) {
    const tr   = input.closest('tr');
    const qty  = parseFloat(tr.querySelector('[name*="[qty]"]').value) || 0;
    const rate = parseFloat(tr.querySelector('[name*="[rate]"]').value) || 0;
    tr.querySelector('.row-amount').textContent = '₹ ' + (qty * rate).toFixed(2);
    calcTotals();
}

function calcTotals() {
    let sub = 0;
    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const qty  = parseFloat(tr.querySelector('[name*="[qty]"]')?.value) || 0;
        const rate = parseFloat(tr.querySelector('[name*="[rate]"]')?.value) || 0;
        sub += qty * rate;
    });
    const disc    = parseFloat(document.getElementById('discountInput')?.value) || 0;
    const gst     = parseFloat(document.getElementById('gstInput')?.value) || 0;
    const after   = sub - disc;
    const gstAmt  = after * gst / 100;
    const nett    = after + gstAmt;

    document.getElementById('subtotalDisplay').textContent = '₹ ' + sub.toFixed(2);
    document.getElementById('gstDisplay').textContent      = '₹ ' + gstAmt.toFixed(2);
    document.getElementById('nettDisplay').textContent     = '₹ ' + nett.toFixed(2);
}

/* ── Terms ── */
function renderTerms() {
    const container = document.getElementById('tcContainer');
    const typePresets = presets[currentType] || {};
    const old = @json(old('terms', []));
    container.innerHTML = '';
    Object.entries(typePresets).forEach(([key, defaultVal]) => {
        const savedVal = old[key] ?? defaultVal;
        const isChecked = (Object.keys(old).length === 0) || (old[key] !== undefined);
        container.innerHTML += `
            <div class="term-item">
                <input type="checkbox" name="terms_include[${key}]" value="1" ${isChecked ? 'checked' : ''}
                       onchange="toggleTermText(this, '${key}')">
                <div style="flex:1;">
                    <div class="term-key">${esc(key)}</div>
                    <textarea name="terms[${key}]" class="term-val" rows="2"
                              style="display:${isChecked ? '' : 'none'};">${esc(savedVal)}</textarea>
                </div>
            </div>`;
    });
}

function toggleTermText(cb, key) {
    const textarea = cb.closest('.term-item').querySelector('textarea');
    if (textarea) textarea.style.display = cb.checked ? '' : 'none';
}

function esc(str) {
    if (!str && str !== 0) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}

/* ── Form submit validation ── */
document.getElementById('awardForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const errors = [];
    const mode = document.getElementById('orderModeInput').value;

    // Top-level required fields
    const startDate     = document.querySelector('[name="start_date"]');
    const endDate       = document.querySelector('[name="end_date"]');
    const contractValue = document.querySelector('[name="contract_value"]');
    const orderDate     = document.querySelector('[name="order_date"]');
    const orderType     = document.querySelector('[name="order_type"]:checked');

    if (!startDate?.value)     errors.push('Start Date is required.');
    if (!endDate?.value)       errors.push('End Date is required.');
    if (endDate?.value && startDate?.value && endDate.value <= startDate.value)
                               errors.push('End Date must be after Start Date.');
    if (!contractValue?.value || parseFloat(contractValue.value) < 0)
                               errors.push('Contract Value is required.');
    if (!orderType)            errors.push('Order Type is required (Work Order or Purchase Order).');
    if (!orderDate?.value)     errors.push('Order Date is required.');

    if (mode === 'details') {
        const rows = document.querySelectorAll('#itemsBody tr');
        if (rows.length === 0) {
            errors.push('At least one line item is required.');
        } else {
            let itemOk = true;
            rows.forEach((tr, i) => {
                const desc = tr.querySelector('[name*="[description]"]')?.value?.trim();
                const qty  = tr.querySelector('[name*="[qty]"]')?.value;
                const rate = tr.querySelector('[name*="[rate]"]')?.value;
                if (!desc) { errors.push(`Row ${i+1}: Description is required.`); itemOk = false; }
                if (!qty || parseFloat(qty) < 0) { errors.push(`Row ${i+1}: Qty is required.`); itemOk = false; }
                if (!rate || parseFloat(rate) < 0) { errors.push(`Row ${i+1}: Rate is required.`); itemOk = false; }
            });
        }
    } else {
        const attachment = document.querySelector('[name="order_attachment"]');
        if (!attachment || !attachment.files || attachment.files.length === 0) {
            errors.push('Please attach the WO / PO document.');
        }
    }

    // Show or clear errors
    let errBox = document.getElementById('jsValidationErrors');
    if (!errBox) {
        errBox = document.createElement('div');
        errBox.id = 'jsValidationErrors';
        errBox.className = 'aw-errors';
        document.getElementById('awardForm').prepend(errBox);
    }

    if (errors.length > 0) {
        errBox.innerHTML = '<strong><i class="fas fa-exclamation-circle mr-1"></i> Please fix the following:</strong><ul>' +
            errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        errBox.style.display = '';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    errBox.style.display = 'none';
    this.submit();
});

/* ── Init ── */
(function () {
    addRow();
    renderTerms();
    selectOrderType(currentType);
    switchMode('details');
})();
</script>
@endpush
@endsection
