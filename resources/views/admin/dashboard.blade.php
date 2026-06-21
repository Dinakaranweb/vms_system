@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')

{{-- ═══════════════════════════════════════════════
     STYLES  (scoped — won't leak into layout)
═══════════════════════════════════════════════ --}}
<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');

.adm-wrap *{ box-sizing:border-box; font-family:'Sora',sans-serif; }

/* ── page wrapper ── */
.adm-wrap{ padding:28px 20px; }

/* ── stat grid ── */
.adm-stats{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:28px;
}
@media(max-width:1024px){ .adm-stats{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:580px){  .adm-stats{ grid-template-columns:1fr; } }

.adm-stat{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    padding:22px 20px;
    display:flex;
    align-items:center;
    gap:16px;
    position:relative;
    overflow:hidden;
    transition:transform .2s,box-shadow .2s;
    cursor:default;
}
.adm-stat:hover{ transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,.09); }
.adm-stat::before{
    content:'';
    position:absolute;
    left:0; top:0;
    width:4px; height:100%;
    border-radius:4px 0 0 4px;
}
.adm-stat.s-blue::before  { background:#4f46e5; }
.adm-stat.s-amber::before { background:#f59e0b; }
.adm-stat.s-green::before { background:#10b981; }
.adm-stat.s-red::before   { background:#ef4444; }

.adm-stat-ico{
    width:52px; height:52px;
    border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px;
    flex-shrink:0;
}
.adm-stat.s-blue  .adm-stat-ico { background:#ede9fe; color:#4f46e5; }
.adm-stat.s-amber .adm-stat-ico { background:#fef3c7; color:#d97706; }
.adm-stat.s-green .adm-stat-ico { background:#d1fae5; color:#059669; }
.adm-stat.s-red   .adm-stat-ico { background:#fee2e2; color:#dc2626; }

.adm-stat-lbl{
    font-size:11px; font-weight:600; letter-spacing:.7px;
    text-transform:uppercase; color:#94a3b8; margin-bottom:6px;
}
.adm-stat-val{
    font-size:30px; font-weight:700;
    color:#0f172a; line-height:1;
    font-family:'JetBrains Mono',monospace;
    letter-spacing:-1px;
}

/* ── main card ── */
.adm-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 4px 24px rgba(0,0,0,.07);
}

/* card header */
.adm-card-hd{
    padding:20px 28px;
    background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:12px;
}
.adm-hd-left{ display:flex; align-items:center; gap:14px; }
.adm-hd-ico{
    width:42px; height:42px;
    background:rgba(255,255,255,.13);
    border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:18px;
}
.adm-hd-title{ font-size:15px; font-weight:600; color:#fff; margin:0 0 3px; }
.adm-hd-sub  { font-size:12px; color:rgba(255,255,255,.5); margin:0; }
.adm-badge{
    background:rgba(255,255,255,.15);
    color:#fff; font-size:12px; font-weight:600;
    padding:4px 14px; border-radius:999px;
    font-family:'JetBrains Mono',monospace;
    border:1px solid rgba(255,255,255,.2);
}

/* table */
.adm-tbl-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
.adm-table{ width:100%; border-collapse:collapse; }
.adm-table thead{ background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.adm-table thead th{
    padding:13px 18px;
    font-size:11px; font-weight:600; letter-spacing:.7px;
    text-transform:uppercase; color:#94a3b8;
    white-space:nowrap; text-align:left;
}
.adm-table thead th:first-child{ padding-left:28px; }
.adm-table thead th:last-child { padding-right:28px; }
.adm-table tbody tr{ border-bottom:1px solid #f1f5f9; transition:background .15s; }
.adm-table tbody tr:last-child{ border-bottom:none; }
.adm-table tbody tr:hover{ background:#fafaff; }
.adm-table tbody td{ padding:15px 18px; vertical-align:middle; }
.adm-table tbody td:first-child{ padding-left:28px; }
.adm-table tbody td:last-child { padding-right:28px; }

/* checkbox */
.adm-chk{
    width:17px; height:17px;
    accent-color:#4f46e5;
    cursor:pointer; border-radius:4px;
}

/* company cell */
.adm-co{ display:flex; align-items:center; gap:12px; }
.adm-avatar{
    width:42px; height:42px; border-radius:10px;
    background:linear-gradient(135deg,#4f46e5,#818cf8);
    display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:700; color:#fff;
    flex-shrink:0; letter-spacing:-.3px;
}
.adm-co-name{ font-size:14px; font-weight:600; color:#0f172a; margin:0 0 3px; }
.adm-co-code{
    font-size:11px; color:#94a3b8;
    font-family:'JetBrains Mono',monospace;
    background:#f1f5f9; padding:2px 7px;
    border-radius:4px; border:1px solid #e2e8f0;
    display:inline-block;
}
.adm-email{ font-size:13px; color:#0f172a; margin:0 0 3px; }
.adm-phone{ font-size:12px; color:#94a3b8; margin:0; }
.adm-pan{
    font-size:12px; font-family:'JetBrains Mono',monospace;
    font-weight:500; color:#475569;
    background:#f8fafc; border:1px solid #e2e8f0;
    padding:4px 9px; border-radius:6px;
}
.adm-date{ font-size:13px; color:#475569; }

/* action buttons */
.adm-acts{ display:flex; gap:6px; align-items:center; }
.adm-act{
    width:34px; height:34px; border-radius:8px;
    border:1px solid #e2e8f0;
    background:#fff;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:14px;
    transition:all .18s; outline:none;
}
.adm-act:hover{ transform:translateY(-2px); }
.adm-act.av{ color:#0284c7; border-color:#bae6fd; }
.adm-act.av:hover{ background:#e0f2fe; border-color:#0284c7; box-shadow:0 4px 12px rgba(2,132,199,.15); }
.adm-act.aa{ color:#059669; border-color:#a7f3d0; }
.adm-act.aa:hover{ background:#d1fae5; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.15); }
.adm-act.ar{ color:#dc2626; border-color:#fecaca; }
.adm-act.ar:hover{ background:#fee2e2; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.15); }

/* empty state */
.adm-empty{ padding:60px 20px; text-align:center; }
.adm-empty-ico{
    width:64px; height:64px; background:#d1fae5; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:28px; color:#059669; margin:0 auto 16px;
}
.adm-empty-t{ font-size:16px; font-weight:600; color:#0f172a; margin:0 0 6px; }
.adm-empty-s{ font-size:13px; color:#94a3b8; margin:0; }

/* card footer */
.adm-card-ft{
    padding:16px 28px;
    background:#f8fafc; border-top:1px solid #e2e8f0;
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px;
}
.adm-ft-note{ font-size:13px; color:#94a3b8; display:flex; align-items:center; gap:6px; }

/* buttons */
.adm-btn{
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 18px; font-size:13px; font-weight:600;
    border-radius:8px; border:none; cursor:pointer;
    transition:all .18s; font-family:'Sora',sans-serif;
    text-decoration:none;
}
.adm-btn-success{
    background:#059669; color:#fff;
    box-shadow:0 2px 8px rgba(5,150,105,.25);
}
.adm-btn-success:hover{
    background:#047857; transform:translateY(-1px);
    box-shadow:0 6px 18px rgba(5,150,105,.35);
}
.adm-btn-danger{
    background:#dc2626; color:#fff;
    box-shadow:0 2px 8px rgba(220,38,38,.25);
}
.adm-btn-danger:hover{ background:#b91c1c; transform:translateY(-1px); }
.adm-btn-ghost{
    background:transparent; color:#475569;
    border:1px solid #e2e8f0;
}
.adm-btn-ghost:hover{ background:#f1f5f9; color:#0f172a; }

/* ── modals ── */
.adm-overlay{
    position:fixed; inset:0;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(4px);
    z-index:9999;
    display:none; align-items:center; justify-content:center;
    padding:16px;
}
.adm-overlay.adm-open{ display:flex; }

.adm-modal{
    background:#fff;
    border-radius:20px;
    box-shadow:0 24px 64px rgba(0,0,0,.2);
    width:100%; max-width:460px;
    animation:admSlideUp .25s cubic-bezier(.34,1.56,.64,1);
    overflow:hidden;
}
.adm-modal.adm-wide{ max-width:660px; max-height:88vh; overflow-y:auto; }
@keyframes admSlideUp{
    from{ opacity:0; transform:translateY(20px) scale(.97); }
    to  { opacity:1; transform:none; }
}

.adm-m-hd{
    padding:22px 24px 18px;
    border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:14px;
}
.adm-m-hd-ico{
    width:42px; height:42px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:19px; flex-shrink:0;
}
.adm-m-hd-ico.danger{ background:#fee2e2; color:#dc2626; }
.adm-m-hd-ico.info  { background:#e0f2fe; color:#0284c7; }
.adm-m-title{ font-size:16px; font-weight:700; color:#0f172a; letter-spacing:-.3px; margin:0 0 3px; }
.adm-m-desc { font-size:13px; color:#475569; margin:0; }
.adm-m-body { padding:22px 24px; }
.adm-m-ft   { padding:14px 24px 20px; display:flex; justify-content:flex-end; gap:10px; }

/* sticky modal header/footer for wide modal */
.adm-sticky-hd{
    position:sticky; top:0; z-index:10;
    background:#fff; border-bottom:1px solid #f1f5f9;
    padding:20px 24px 16px;
    display:flex; align-items:center; gap:14px;
}
.adm-sticky-ft{
    position:sticky; bottom:0; z-index:10;
    background:#fff; border-top:1px solid #f1f5f9;
    padding:14px 24px;
    display:flex; justify-content:flex-end;
}

/* textarea */
.adm-ta{
    width:100%;
    border:1.5px solid #e2e8f0; border-radius:8px;
    padding:12px 14px; font-size:14px; color:#0f172a;
    font-family:'Sora',sans-serif;
    resize:vertical; min-height:108px;
    background:#f8fafc;
    transition:border-color .18s, box-shadow .18s;
}
.adm-ta:focus{
    outline:none;
    border-color:#dc2626;
    box-shadow:0 0 0 3px rgba(220,38,38,.1);
    background:#fff;
}
.adm-ta::placeholder{ color:#94a3b8; }
.adm-lbl{
    display:block; font-size:13px; font-weight:600;
    color:#0f172a; margin-bottom:8px;
}

/* responsive hide */
@media(max-width:640px){
    .adm-hide-sm{ display:none !important; }
    .adm-wrap{ padding:14px 10px; }
    .adm-card-hd{ padding:16px 18px; }
    .adm-table thead th,
    .adm-table tbody td{ padding:12px 12px; }
    .adm-table thead th:first-child,
    .adm-table tbody td:first-child{ padding-left:16px; }
    .adm-table thead th:last-child,
    .adm-table tbody td:last-child{ padding-right:16px; }
    .adm-card-ft{ padding:13px 18px; }
}

/* ── vendor detail sections (inside viewModal) ── */
.vd-section{ margin-bottom:22px; }
.vd-section:last-child{ margin-bottom:0; }
.vd-section-title{
    font-size:11px; font-weight:600; letter-spacing:.7px;
    text-transform:uppercase; color:#94a3b8;
    margin:0 0 12px; padding-bottom:8px;
    border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:7px;
}
.vd-section-title i{ font-size:13px; }
.vd-grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
@media(max-width:480px){ .vd-grid{ grid-template-columns:1fr; } }
.vd-field{ display:flex; flex-direction:column; gap:4px; }
.vd-field.vd-full{ grid-column:1/-1; }
.vd-lbl{ font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.vd-val{ font-size:13px; color:#0f172a; font-weight:500; line-height:1.5; }
.vd-mono{ font-family:'JetBrains Mono',monospace; font-size:12px; background:#f8fafc; border:1px solid #e2e8f0; padding:2px 8px; border-radius:5px; display:inline-block; }
.vd-status{
    display:inline-block; font-size:11px; font-weight:600;
    padding:3px 10px; border-radius:999px;
    text-transform:capitalize; letter-spacing:.3px;
}
.vd-status-pending { background:#fef3c7; color:#d97706; }
.vd-status-approved{ background:#d1fae5; color:#059669; }
.vd-status-rejected{ background:#fee2e2; color:#dc2626; }
.vd-docs{ display:flex; flex-direction:column; gap:8px; }
.vd-doc-link{
    display:flex; align-items:center; gap:10px;
    padding:11px 14px; border-radius:9px;
    background:#f8fafc; border:1px solid #e2e8f0;
    color:#0f172a; font-size:13px; font-weight:500;
    text-decoration:none; transition:all .15s;
}
.vd-doc-link:hover{ background:#ede9fe; border-color:#c4b5fd; color:#4f46e5; }
.vd-doc-link .vd-doc-ico{ font-size:17px; color:#dc2626; flex-shrink:0; }
.vd-doc-link .vd-doc-ext{ margin-left:auto; font-size:10px; color:#94a3b8; }
.vd-doc-link:hover .vd-doc-ext{ color:#818cf8; }
.vd-spinner{ text-align:center; padding:36px; color:#94a3b8; font-size:24px; }
.vd-error{ text-align:center; padding:24px; color:#dc2626; font-size:13px; }
</style>

<div class="adm-wrap">

    {{-- ══════════════ STAT CARDS ══════════════ --}}
    <div class="adm-stats">

        <div class="adm-stat s-blue">
            <div class="adm-stat-ico"><i class="fas fa-building"></i></div>
            <div>
                <div class="adm-stat-lbl">Total Vendors</div>
                <div class="adm-stat-val">{{ $stats['total_vendors'] }}</div>
            </div>
        </div>

        <div class="adm-stat s-amber">
            <div class="adm-stat-ico"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="adm-stat-lbl">Pending Approval</div>
                <div class="adm-stat-val">{{ $stats['pending_vendors'] }}</div>
            </div>
        </div>

        <div class="adm-stat s-green">
            <div class="adm-stat-ico"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="adm-stat-lbl">Approved Vendors</div>
                <div class="adm-stat-val">{{ $stats['approved_vendors'] }}</div>
            </div>
        </div>

        <div class="adm-stat s-red">
            <div class="adm-stat-ico"><i class="fas fa-times-circle"></i></div>
            <div>
                <div class="adm-stat-lbl">Rejected Vendors</div>
                <div class="adm-stat-val">{{ $stats['rejected_vendors'] }}</div>
            </div>
        </div>

    </div>

    {{-- ══════════════ PENDING TABLE CARD ══════════════ --}}
    <div class="adm-card">

        {{-- Header --}}
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

        {{-- Table --}}
        <div class="adm-tbl-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="adm-chk"></th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th class="adm-hide-sm">PAN Number</th>
                        <th class="adm-hide-sm">Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingVendors as $vendor)
                    <tr>
                        <td>
                            <input type="checkbox" class="vendor-checkbox adm-chk" value="{{ $vendor->id }}">
                        </td>
                        <td>
                            <div class="adm-co">
                                <div class="adm-avatar">
                                    {{ strtoupper(substr($vendor->company_name, 0, 1)) }}{{ strtoupper(substr(strstr($vendor->company_name, ' '), 1, 1)) }}
                                </div>
                                <div>
                                    <div class="adm-co-name">{{ $vendor->company_name }}</div>
                                    <span class="adm-co-code">{{ $vendor->vendor_code }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="adm-email">{{ $vendor->email }}</p>
                            <p class="adm-phone">
                                <i class="fas fa-phone-alt" style="font-size:10px;margin-right:4px;"></i>
                                {{ $vendor->phone }}
                            </p>
                        </td>
                        <td class="adm-hide-sm">
                            <span class="adm-pan">{{ $vendor->pan_number }}</span>
                        </td>
                        <td class="adm-hide-sm">
                            <span class="adm-date">{{ $vendor->created_at->format('d M, Y') }}</span>
                        </td>
                        <td>
                            <div class="adm-acts">
                                <button onclick="viewVendor({{ $vendor->id }})"
                                        class="adm-act av" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="approveVendor({{ $vendor->id }})"
                                        class="adm-act aa" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="showRejectModal({{ $vendor->id }})"
                                        class="adm-act ar" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="adm-empty">
                                <div class="adm-empty-ico">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <p class="adm-empty-t">All caught up!</p>
                                <p class="adm-empty-s">No vendors are currently pending approval.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        @if($pendingVendors->count() > 0)
        <div class="adm-card-ft">
            <span class="adm-ft-note">
                <i class="fas fa-info-circle"></i>
                Select vendors above to perform bulk actions
            </span>
            <button onclick="bulkApprove()" class="adm-btn adm-btn-success">
                <i class="fas fa-check-double"></i>
                Bulk Approve Selected
            </button>
        </div>
        @endif

    </div>{{-- /.adm-card --}}

</div>{{-- /.adm-wrap --}}


{{-- ══════════════════════════════════════════════
     REJECT MODAL  (exact same logic, new look)
══════════════════════════════════════════════ --}}
<div id="rejectModal" class="adm-overlay" onclick="admCloseOnBg(event,'rejectModal')">
    <div class="adm-modal">
        <div class="adm-m-hd">
            <div class="adm-m-hd-ico danger"><i class="fas fa-ban"></i></div>
            <div>
                <p class="adm-m-title">Reject Application</p>
                <p class="adm-m-desc">The vendor will be notified via email.</p>
            </div>
        </div>
        <div class="adm-m-body">
            <label class="adm-lbl" for="rejectionReason">
                Rejection reason <span style="color:#dc2626">*</span>
            </label>
            <textarea id="rejectionReason" class="adm-ta"
                      placeholder="Explain why this application is being rejected…"></textarea>
        </div>
        <div class="adm-m-ft">
            <button onclick="closeRejectModal()" class="adm-btn adm-btn-ghost">Cancel</button>
            <button onclick="confirmReject()"    class="adm-btn adm-btn-danger">
                <i class="fas fa-times-circle"></i> Reject Vendor
            </button>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════
     VIEW VENDOR MODAL  (exact same logic, new look)
══════════════════════════════════════════════ --}}
<div id="viewModal" class="adm-overlay" onclick="admCloseOnBg(event,'viewModal')">
    <div class="adm-modal adm-wide">

        <div class="adm-sticky-hd">
            <div class="adm-m-hd-ico info"><i class="fas fa-user-tie"></i></div>
            <div>
                <p class="adm-m-title" id="viewModalTitle">Vendor Details</p>
                <p class="adm-m-desc">Full application information</p>
            </div>
        </div>

        <div id="vendorDetails" class="adm-m-body">
            {{-- Dynamic content loaded via JS --}}
        </div>

        <div class="adm-sticky-ft">
            <button onclick="closeViewModal()" class="adm-btn adm-btn-ghost">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

    </div>
</div>


@push('scripts')
<script>
    let currentVendorId = null;

    /* ── helpers ── */
    function admOpen(id)  { document.getElementById(id).classList.add('adm-open'); }
    function admClose(id) { document.getElementById(id).classList.remove('adm-open'); }
    function admCloseOnBg(e, id){ if(e.target === document.getElementById(id)) admClose(id); }

    /* ── ESC key closes any open modal ── */
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape'){ admClose('rejectModal'); admClose('viewModal'); }
    });

    /* ── Select All ── */
    document.getElementById('selectAll')?.addEventListener('change', function(e){
        document.querySelectorAll('.vendor-checkbox').forEach(cb => cb.checked = e.target.checked);
    });

    /* ── Approve Single Vendor ── */
    function approveVendor(id){
        if(confirm('Are you sure you want to approve this vendor?')){
            fetch(`/admin/vendors/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(data => {
                if(data.success){ location.reload(); }
                else { alert('Failed to approve vendor'); }
            });
        }
    }

    /* ── Reject Modal ── */
    function showRejectModal(id){
        currentVendorId = id;
        admOpen('rejectModal');
    }
    function closeRejectModal(){
        admClose('rejectModal');
        document.getElementById('rejectionReason').value = '';
    }
    function confirmReject(){
        const reason = document.getElementById('rejectionReason').value.trim();
        if(!reason){ alert('Please enter a rejection reason'); return; }
        fetch(`/admin/vendors/${currentVendorId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason })
        })
        .then(r => r.json())
        .then(data => {
            if(data.success){ location.reload(); }
            else { alert('Failed to reject vendor'); }
        });
    }

    /* ── View Vendor Details ── */
    function viewVendor(id){
        const box = document.getElementById('vendorDetails');
        box.innerHTML = '<div class="vd-spinner"><i class="fas fa-spinner fa-spin"></i></div>';
        admOpen('viewModal');

        fetch(`/admin/vendors/${id}`)
            .then(r => r.json())
            .then(({ success, vendor }) => {
                if (!success || !vendor) {
                    box.innerHTML = '<p class="vd-error">Failed to load vendor details.</p>';
                    return;
                }

                /* ── documents ── */
                const docs = vendor.registration_documents || {};
                const docEntries = Object.entries(docs);
                const docsHtml = docEntries.length
                    ? docEntries.map(([type, path]) => {
                        const label = type.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
                        const ext   = path.split('.').pop().toUpperCase();
                        return `<a href="/storage/${path}" target="_blank" class="vd-doc-link">
                                    <i class="fas fa-file-pdf vd-doc-ico"></i>
                                    ${label}
                                    <span class="vd-doc-ext">${ext} &nbsp;<i class="fas fa-external-link-alt"></i></span>
                                </a>`;
                    }).join('')
                    : '<p style="color:#94a3b8;font-size:13px;margin:0">No documents uploaded yet.</p>';

                /* ── bank details ── */
                const bank = vendor.bank_details || {};
                const bankHtml = Object.keys(bank).length
                    ? `<div class="vd-grid">
                            <div class="vd-field"><span class="vd-lbl">Bank Name</span><span class="vd-val">${bank.bank_name || '—'}</span></div>
                            <div class="vd-field"><span class="vd-lbl">Account Holder</span><span class="vd-val">${bank.account_holder_name || '—'}</span></div>
                            <div class="vd-field"><span class="vd-lbl">Account Number</span><span class="vd-val"><span class="vd-mono">${bank.account_number || '—'}</span></span></div>
                            <div class="vd-field"><span class="vd-lbl">IFSC Code</span><span class="vd-val"><span class="vd-mono">${bank.ifsc_code || '—'}</span></span></div>
                       </div>`
                    : '<p style="color:#94a3b8;font-size:13px;margin:0">No bank details provided yet.</p>';

                /* ── address string ── */
                const addr = [vendor.address, vendor.city, vendor.state, vendor.pincode ? '- ' + vendor.pincode : ''].filter(Boolean).join(', ');

                /* ── registered date ── */
                const regDate = vendor.created_at
                    ? new Date(vendor.created_at).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'})
                    : '—';

                /* ── modal title ── */
                const titleEl = document.getElementById('viewModalTitle');
                if (titleEl) titleEl.textContent = vendor.company_name || 'Vendor Details';

                /* ── status badge ── */
                const status = vendor.status || 'pending';
                const statusBadge = `<span class="vd-status vd-status-${status}">${status.charAt(0).toUpperCase()+status.slice(1)}</span>`;

                box.innerHTML = `
                    <div class="vd-section">
                        <p class="vd-section-title"><i class="fas fa-building"></i> Company Information</p>
                        <div class="vd-grid">
                            <div class="vd-field"><span class="vd-lbl">Company Name</span><span class="vd-val">${vendor.company_name}</span></div>
                            <div class="vd-field"><span class="vd-lbl">Vendor Code</span><span class="vd-val"><span class="vd-mono">${vendor.vendor_code}</span></span></div>
                            <div class="vd-field"><span class="vd-lbl">PAN Number</span><span class="vd-val"><span class="vd-mono">${vendor.pan_number}</span></span></div>
                            <div class="vd-field"><span class="vd-lbl">GST Number</span><span class="vd-val"><span class="vd-mono">${vendor.gst_number || '—'}</span></span></div>
                            <div class="vd-field"><span class="vd-lbl">Status</span><span class="vd-val">${statusBadge}</span></div>
                            <div class="vd-field"><span class="vd-lbl">Registered On</span><span class="vd-val">${regDate}</span></div>
                        </div>
                    </div>

                    <div class="vd-section">
                        <p class="vd-section-title"><i class="fas fa-user"></i> Contact Information</p>
                        <div class="vd-grid">
                            <div class="vd-field"><span class="vd-lbl">Contact Person</span><span class="vd-val">${vendor.user?.name || '—'}</span></div>
                            <div class="vd-field"><span class="vd-lbl">Email</span><span class="vd-val">${vendor.email}</span></div>
                            <div class="vd-field"><span class="vd-lbl">Phone</span><span class="vd-val">${vendor.phone}</span></div>
                            <div class="vd-field vd-full"><span class="vd-lbl">Address</span><span class="vd-val">${addr || '—'}</span></div>
                        </div>
                    </div>

                    <div class="vd-section">
                        <p class="vd-section-title"><i class="fas fa-university"></i> Bank Details</p>
                        ${bankHtml}
                    </div>

                    <div class="vd-section">
                        <p class="vd-section-title"><i class="fas fa-file-alt"></i> Registration Documents</p>
                        <div class="vd-docs">${docsHtml}</div>
                    </div>
                `;
            })
            .catch(() => {
                box.innerHTML = '<p class="vd-error">Unable to fetch vendor details. Please try again.</p>';
            });
    }
    function closeViewModal(){
        admClose('viewModal');
        const titleEl = document.getElementById('viewModalTitle');
        if (titleEl) titleEl.textContent = 'Vendor Details';
    }

    /* ── Bulk Approve ── */
    function bulkApprove(){
        const selected = Array.from(
            document.querySelectorAll('.vendor-checkbox:checked')
        ).map(cb => cb.value);

        if(selected.length === 0){ alert('Please select at least one vendor'); return; }

        if(confirm(`Approve ${selected.length} vendor(s)?`)){
            fetch('/admin/vendors/bulk-approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ vendor_ids: selected })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success){ location.reload(); }
                else { alert('Failed to approve vendors'); }
            });
        }
    }
</script>
@endpush

@endsection