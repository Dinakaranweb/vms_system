@extends('layouts.dashboard')

@section('title', 'Create New Tender')
@section('header', 'Create New Tender')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">

<style>
    :root {
        --brand-primary: #059669;
        --brand-deep:    #047857;
        --brand-soft:    #ecfdf5;
        --brand-glow:    #a7f3d0;
        --surface:       #ffffff;
        --surface-alt:   #f8fafc;
        --surface-hover: #f1f5f9;
        --border:        #e2e8f0;
        --border-focus:  #6ee7b7;
        --text-primary:  #0f172a;
        --text-secondary:#475569;
        --text-muted:    #94a3b8;
        --purple-bg:     #f5f3ff;
        --purple-border: #c4b5fd;
        --purple-accent: #7c3aed;
        --success-bg:    #f0fdf4;
        --success-border:#86efac;
        --success-text:  #15803d;
        --danger-bg:     #fef2f2;
        --danger-border: #fca5a5;
        --danger-text:   #dc2626;
        --radius-sm:     6px;
        --radius-md:     10px;
        --radius-lg:     14px;
        --radius-xl:     20px;
        --shadow-sm:     0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        --shadow-md:     0 4px 16px rgba(0,0,0,.07), 0 1px 4px rgba(0,0,0,.04);
        --shadow-focus:  0 0 0 3px rgba(5,150,105,.2);
        --transition:    all .18s ease;
    }

    .tender-wrap {
        font-family: 'DM Sans', sans-serif;
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 0 2rem;
    }

    .tender-page-header {
        display: flex; align-items: center; gap: 14px; margin-bottom: 28px;
    }
    .tender-page-header .icon-ring {
        width: 46px; height: 46px; border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--brand-primary), #0d9488);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 20px; flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(5,150,105,.35);
    }
    .tender-page-header h2 {
        font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 700;
        color: var(--text-primary); margin: 0; letter-spacing: -.3px;
    }
    .tender-page-header p { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

    .tender-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-xl); box-shadow: var(--shadow-md); overflow: hidden;
    }

    .form-section { padding: 28px 32px; border-bottom: 1px solid var(--border); }
    .form-section:last-child { border-bottom: none; }
    .section-label { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
    .section-label .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dot-green  { background: var(--brand-primary); }
    .dot-purple { background: var(--purple-accent); }
    .dot-amber  { background: #d97706; }
    .dot-teal   { background: #0d9488; }
    .section-label h3 {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .7px; color: var(--text-secondary); margin: 0;
    }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .col-span-2 { grid-column: 1 / -1; }

    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label {
        font-size: 13px; font-weight: 500; color: var(--text-primary);
        display: flex; align-items: center; gap: 5px;
    }
    .field label .req { color: var(--brand-primary); font-size: 15px; line-height: 1; }
    .field input, .field textarea, .field select {
        font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text-primary);
        background: var(--surface); border: 1.5px solid var(--border);
        border-radius: var(--radius-md); padding: 10px 13px;
        width: 100%; box-sizing: border-box; transition: var(--transition); outline: none; -webkit-appearance: none;
    }
    .field input:hover, .field textarea:hover, .field select:hover {
        border-color: #cbd5e1; background: var(--surface-alt);
    }
    .field input:focus, .field textarea:focus, .field select:focus {
        border-color: var(--border-focus); box-shadow: var(--shadow-focus); background: var(--surface);
    }
    .field textarea { resize: vertical; min-height: 90px; line-height: 1.6; }
    .field .hint { font-size: 12px; color: var(--text-muted); margin: 0; }

    .alert-error {
        margin: 0 32px 0; padding: 14px 16px;
        background: var(--danger-bg); border: 1px solid var(--danger-border);
        border-radius: var(--radius-md); font-size: 13.5px; color: var(--danger-text);
    }
    .alert-error ul { margin: 6px 0 0 18px; padding: 0; }
    .alert-error li { margin: 3px 0; }
    .alert-error .err-title { font-weight: 600; display: flex; align-items: center; gap: 7px; }

    /* ── Tender type tiles ── */
    .type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .type-tile {
        position: relative; border: 1.5px solid var(--border);
        border-radius: var(--radius-lg); padding: 16px 18px; cursor: pointer;
        transition: var(--transition); background: var(--surface);
        display: flex; align-items: flex-start; gap: 14px;
    }
    .type-tile:hover { border-color: var(--border-focus); background: var(--brand-soft); }
    .type-tile input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .type-tile.selected {
        border-color: var(--brand-primary); background: var(--brand-soft);
        box-shadow: 0 0 0 3px rgba(5,150,105,.1);
    }
    .type-tile .tile-icon {
        width: 42px; height: 42px; border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
    }
    .tile-icon-green { background: #dcfce7; color: var(--brand-primary); }
    .tile-icon-teal  { background: #ccfbf1; color: #0d9488; }
    .type-tile .tile-body h4 {
        font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 600;
        color: var(--text-primary); margin: 0 0 3px;
    }
    .type-tile .tile-body p { font-size: 12px; color: var(--text-secondary); margin: 0; line-height: 1.4; }
    .type-tile .check-ring {
        margin-left: auto; width: 20px; height: 20px; border-radius: 50%;
        border: 2px solid var(--border); flex-shrink: 0;
        transition: var(--transition); display: flex; align-items: center; justify-content: center;
    }
    .type-tile.selected .check-ring { border-color: var(--brand-primary); background: var(--brand-primary); }
    .type-tile.selected .check-ring::after { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #fff; }

    .postal-box {
        display: none; margin-top: 16px; background: var(--surface-alt);
        border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px;
    }
    .postal-box.visible { display: block; }
    .postal-box h4 {
        font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 600;
        text-transform: uppercase; letter-spacing: .6px; color: var(--text-secondary); margin: 0 0 16px;
    }

    /* ── Eligibility questions ── */
    .questions-empty {
        text-align: center; padding: 32px 20px;
        border: 1.5px dashed var(--border); border-radius: var(--radius-lg);
        color: var(--text-muted); font-size: 13.5px;
    }
    .questions-empty i { font-size: 32px; margin-bottom: 8px; display: block; }
    .question-card {
        background: var(--surface-alt); border: 1px solid var(--border);
        border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 12px; transition: var(--transition);
    }
    .question-card:hover { border-color: #cbd5e1; }
    .question-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px; background: var(--surface); border-bottom: 1px solid var(--border);
    }
    .question-badge {
        display: inline-flex; align-items: center; gap: 7px; font-size: 12px;
        font-weight: 600; color: var(--purple-accent); background: var(--purple-bg);
        border: 1px solid var(--purple-border); border-radius: 20px; padding: 3px 10px;
    }
    .question-body { padding: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .question-body .q-full { grid-column: 1 / -1; }
    .btn-remove {
        background: none; border: 1px solid #fca5a5; color: #dc2626;
        border-radius: var(--radius-sm); padding: 4px 10px; font-size: 12px; font-weight: 500;
        cursor: pointer; display: flex; align-items: center; gap: 5px;
        font-family: 'DM Sans', sans-serif; transition: var(--transition);
    }
    .btn-remove:hover { background: #fef2f2; }
    .required-toggle { display: flex; align-items: center; gap: 8px; padding: 10px 0; }
    .required-toggle input[type="checkbox"] {
        width: 16px; height: 16px; accent-color: var(--purple-accent); cursor: pointer; flex-shrink: 0;
    }
    .required-toggle span { font-size: 13px; color: var(--text-secondary); font-weight: 500; }

    .btn-add-q {
        display: flex; align-items: center; gap: 8px; background: none;
        border: 1.5px dashed var(--purple-border); color: var(--purple-accent);
        font-family: 'DM Sans', sans-serif; font-size: 13.5px; font-weight: 500;
        padding: 10px 18px; border-radius: var(--radius-md); cursor: pointer;
        width: 100%; justify-content: center; transition: var(--transition); margin-top: 4px;
    }
    .btn-add-q:hover { background: var(--purple-bg); border-color: var(--purple-accent); }

    .input-prefix { position: relative; display: flex; align-items: center; }
    .input-prefix .prefix-symbol {
        position: absolute; left: 13px; font-size: 14px; font-weight: 600;
        color: var(--text-secondary); pointer-events: none; z-index: 1;
    }
    .input-prefix input { padding-left: 30px !important; }

    /* ── Document rows ── */
    .doc-item {
        display: grid;
        grid-template-columns: 1fr auto auto auto;
        align-items: center;
        gap: 10px;
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 10px 14px;
        margin-bottom: 10px;
        transition: var(--transition);
    }
    .doc-item:hover { border-color: #cbd5e1; }
    .doc-item input[type="file"] {
        font-size: 13px; padding: 7px 10px; background: var(--surface);
        border: 1.5px solid var(--border); border-radius: var(--radius-md);
        width: 100%; box-sizing: border-box; cursor: pointer; color: var(--text-secondary);
        font-family: 'DM Sans', sans-serif; outline: none; transition: var(--transition);
    }
    .doc-item input[type="file"]:focus { border-color: var(--border-focus); box-shadow: var(--shadow-focus); }
    .doc-item .doc-cat {
        font-family: 'DM Sans', sans-serif; font-size: 13px; color: var(--text-primary);
        background: var(--surface); border: 1.5px solid var(--border);
        border-radius: var(--radius-md); padding: 8px 10px; outline: none;
        cursor: pointer; -webkit-appearance: none; white-space: nowrap; transition: var(--transition);
    }
    .doc-item .doc-cat:focus { border-color: var(--border-focus); box-shadow: var(--shadow-focus); }
    .doc-item .mandatory-label {
        display: flex; align-items: center; gap: 6px; font-size: 13px;
        color: var(--text-secondary); font-weight: 500; white-space: nowrap; cursor: pointer;
    }
    .doc-item .mandatory-label input[type="checkbox"] {
        width: 15px; height: 15px; accent-color: var(--brand-primary); cursor: pointer; flex-shrink: 0;
    }
    .btn-rm-doc {
        background: none; border: 1px solid #fca5a5; color: #dc2626;
        border-radius: var(--radius-sm); padding: 5px 9px; font-size: 12px;
        cursor: pointer; display: flex; align-items: center;
        font-family: 'DM Sans', sans-serif; transition: var(--transition); flex-shrink: 0;
    }
    .btn-rm-doc:hover { background: #fef2f2; }

    .form-footer {
        display: flex; align-items: center; justify-content: flex-end; gap: 12px;
        padding: 22px 32px; background: var(--surface-alt); border-top: 1px solid var(--border);
    }
    .btn-cancel {
        font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 500;
        color: var(--text-secondary); background: none; border: 1.5px solid var(--border);
        border-radius: var(--radius-md); padding: 9px 20px; cursor: pointer;
        text-decoration: none; transition: var(--transition); display: inline-flex; align-items: center; gap: 7px;
    }
    .btn-cancel:hover { background: var(--surface-hover); border-color: #cbd5e1; color: var(--text-primary); }
    .btn-submit {
        font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600; color: #fff;
        background: linear-gradient(135deg, var(--brand-primary) 0%, #0d9488 100%);
        border: none; border-radius: var(--radius-md); padding: 10px 24px;
        cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 3px 12px rgba(5,150,105,.35); transition: var(--transition);
    }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5,150,105,.4); }
    .btn-submit:active { transform: translateY(0); }

    .sub-field { display: none; }
    .sub-field.visible { display: block; }

    @media (max-width: 640px) {
        .form-section  { padding: 22px 18px; }
        .form-footer   { padding: 18px; }
        .grid-2        { grid-template-columns: 1fr; }
        .type-grid     { grid-template-columns: 1fr; }
        .question-body { grid-template-columns: 1fr; }
        .question-body .q-full { grid-column: 1; }
        .tender-page-header h2 { font-size: 18px; }
        .alert-error   { margin: 0 18px; }
        .doc-item      { grid-template-columns: 1fr; }
    }
    @media (max-width: 400px) {
        .form-section  { padding: 18px 14px; }
        .form-footer   { flex-direction: column-reverse; }
        .btn-cancel, .btn-submit { width: 100%; justify-content: center; }
    }
</style>

<div class="tender-wrap">

    <div class="tender-page-header">
        <div class="icon-ring"><i class="fas fa-file-contract"></i></div>
        <div>
            <h2>Create New Tender</h2>
            <p>Fill in the details below to publish a new tender listing</p>
        </div>
    </div>

    <div class="tender-card">

        @if($errors->any())
        <div class="alert-error" style="margin: 24px 32px 0;">
            <div class="err-title">
                <i class="fas fa-exclamation-circle"></i> Please fix the following errors
            </div>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('tender-owner.tenders.store') }}" method="POST" enctype="multipart/form-data" id="tenderForm">
            @csrf

            {{-- Section 1: Basic Info --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-green"></span>
                    <h3>Basic Information</h3>
                </div>
                <div class="grid-2">
                    <div class="field col-span-2">
                        <label>Title <span class="req">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               placeholder="Enter tender title…" required>
                    </div>
                    <div class="field col-span-2">
                        <label>Description <span class="req">*</span></label>
                        <textarea name="description" rows="4"
                                  placeholder="Describe the scope and purpose of this tender…" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="field col-span-2">
                        <label>Eligibility Criteria <span style="font-size:11px;color:var(--text-muted);font-weight:400;">(optional)</span></label>
                        <textarea name="eligibility_criteria" rows="3"
                                  placeholder="Specify who is eligible to apply… (leave blank if no criteria)">{{ old('eligibility_criteria') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Section 2: Eligibility Questions --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-purple"></span>
                    <h3>Eligibility Questions</h3>
                </div>
                <p style="font-size:13.5px; color:var(--text-secondary); margin:0 0 16px;">
                    Define questions vendors must answer to determine eligibility. Leave empty if not required.
                </p>

                <div id="questionsContainer">
                    <div id="emptyState" class="questions-empty">
                        <i class="fas fa-question-circle"></i>
                        No questions added yet.<br>Click <strong>Add Question</strong> to get started.
                    </div>
                </div>

                <input type="hidden" name="eligibility_questions" id="eligibilityQuestionsInput">

                <button type="button" onclick="addQuestion()" class="btn-add-q" style="margin-top:14px;">
                    <i class="fas fa-plus"></i> Add Question
                </button>
            </div>

            {{-- Section 3: Schedule & Value --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-amber"></span>
                    <h3>Schedule &amp; Value</h3>
                </div>
                <div class="grid-2">
                    <div class="field">
                        <label>Estimated Value (₹)</label>
                        <div class="input-prefix">
                            <span class="prefix-symbol">₹</span>
                            <input type="number" name="estimated_value" step="0.01"
                                   value="{{ old('estimated_value') }}" placeholder="0.00">
                        </div>
                    </div>
                    <div></div>
                    <div class="field">
                        <label>Submission Deadline <span class="req">*</span></label>
                        <input type="date" name="submission_deadline"
                               value="{{ old('submission_deadline') }}" required>
                    </div>
                    <div class="field">
                        <label>Opening Date <span class="req">*</span></label>
                        <input type="date" name="opening_date"
                               value="{{ old('opening_date') }}" required>
                    </div>
                </div>
            </div>

            {{-- Section 4: Tender Type --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-teal"></span>
                    <h3>Submission Type</h3>
                </div>
                <div class="type-grid">
                    <label class="type-tile selected" id="tile-online">
                        <input type="radio" name="tender_type" value="online" checked>
                        <div class="tile-icon tile-icon-green"><i class="fas fa-globe"></i></div>
                        <div class="tile-body">
                            <h4>Online Tender</h4>
                            <p>Vendors submit all documents digitally through the portal</p>
                        </div>
                        <span class="check-ring"></span>
                    </label>
                    <label class="type-tile" id="tile-postal">
                        <input type="radio" name="tender_type" value="postal">
                        <div class="tile-icon tile-icon-teal"><i class="fas fa-envelope"></i></div>
                        <div class="tile-body">
                            <h4>Postal Tender</h4>
                            <p>Vendors download forms and submit by physical post</p>
                        </div>
                        <span class="check-ring"></span>
                    </label>
                </div>

                <div id="postalBox" class="postal-box">
                    <h4><i class="fas fa-map-marker-alt" style="margin-right:6px;"></i>Postal Submission Details</h4>
                    <div class="field">
                        <label>Postal Address <span class="req">*</span></label>
                        <textarea name="postal_address" rows="3"
                                  placeholder="Enter the full postal address for submissions…"></textarea>
                    </div>
                    <div class="field" style="margin-top:12px;">
                        <label>Last Date to Receive Physical Documents</label>
                        <input type="date" name="postal_receipt_deadline"
                               style="width:100%;padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--radius-md);font-size:13px;">
                        <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Deadline by which physical documents must arrive at the postal address.</p>
                    </div>
                </div>
            </div>

            {{-- Section 4b: Application Field Requirements --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-purple"></span>
                    <h3>Application Field Requirements</h3>
                </div>
                <p style="font-size:13.5px; color:var(--text-secondary); margin:0 0 18px;">
                    Choose which fields vendors must fill when applying for this tender.
                </p>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:12px;">

                    <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:var(--surface-alt);transition:border-color .15s;"
                           id="tile_bid"
                           onmouseover="this.style.borderColor='var(--brand-primary)'"
                           onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--brand-primary)':'var(--border)'">
                        <input type="checkbox" name="require_bid_amount" value="1"
                               {{ old('require_bid_amount', '1') ? 'checked' : '' }}
                               style="accent-color:var(--brand-primary);width:16px;height:16px;flex-shrink:0;margin-top:2px;"
                               onchange="this.closest('label').style.borderColor=this.checked?'var(--brand-primary)':'var(--border)'">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-rupee-sign" style="color:#d97706;font-size:12px;"></i> Bid Amount
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Vendor must enter their bid price</div>
                        </div>
                    </label>

                    <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:var(--surface-alt);transition:border-color .15s;"
                           id="tile_tech"
                           onmouseover="this.style.borderColor='var(--brand-primary)'"
                           onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--brand-primary)':'var(--border)'">
                        <input type="checkbox" name="require_technical_bid" value="1"
                               {{ old('require_technical_bid', '1') ? 'checked' : '' }}
                               style="accent-color:var(--brand-primary);width:16px;height:16px;flex-shrink:0;margin-top:2px;"
                               onchange="this.closest('label').style.borderColor=this.checked?'var(--brand-primary)':'var(--border)'">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-file-alt" style="color:#7c3aed;font-size:12px;"></i> Technical Bid
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Vendor must provide a technical proposal</div>
                        </div>
                    </label>

                    <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:var(--surface-alt);transition:border-color .15s;"
                           id="tile_fin"
                           onmouseover="this.style.borderColor='var(--brand-primary)'"
                           onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--brand-primary)':'var(--border)'">
                        <input type="checkbox" name="require_financial_bid" value="1"
                               {{ old('require_financial_bid', '1') ? 'checked' : '' }}
                               style="accent-color:var(--brand-primary);width:16px;height:16px;flex-shrink:0;margin-top:2px;"
                               onchange="this.closest('label').style.borderColor=this.checked?'var(--brand-primary)':'var(--border)'">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-coins" style="color:#059669;font-size:12px;"></i> Financial Bid
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Vendor must provide a financial proposal</div>
                        </div>
                    </label>

                    <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid var(--border);border-radius:10px;cursor:pointer;background:var(--surface-alt);transition:border-color .15s;"
                           id="tile_sign"
                           onmouseover="this.style.borderColor='var(--brand-primary)'"
                           onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--brand-primary)':'var(--border)'">
                        <input type="checkbox" name="require_contract_signing" value="1"
                               {{ old('require_contract_signing', '1') ? 'checked' : '' }}
                               style="accent-color:var(--brand-primary);width:16px;height:16px;flex-shrink:0;margin-top:2px;"
                               onchange="this.closest('label').style.borderColor=this.checked?'var(--brand-primary)':'var(--border)'">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-signature" style="color:#0891b2;font-size:12px;"></i> Require Contract Signing
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">If unchecked, contract activates automatically after awarding (no signatures needed)</div>
                        </div>
                    </label>

                </div>
            </div>

            {{-- Section 5: Documents --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-green"></span>
                    <h3>Supporting Documents</h3>
                </div>
                <p style="font-size:13.5px; color:var(--text-secondary); margin:0 0 16px;">
                    Attach one or more files. Set a category and mandatory flag per document.
                </p>
                <div id="documentsContainer"></div>
                <button type="button" onclick="addDocument()" class="btn-add-q"
                        style="margin-top:4px; border-color:var(--border-focus); color:var(--brand-primary);">
                    <i class="fas fa-plus"></i> Add Document
                </button>
                <p style="font-size:12px; color:var(--text-muted); margin-top:8px;">
                    PDF, DOC, DOCX accepted &nbsp;·&nbsp; Max 10 MB per file
                </p>
            </div>

            {{-- Section 6: Categories --}}
            @if(isset($categories) && $categories->count())
            <div class="form-section">
                <div class="section-label">
                    <span class="dot dot-teal"></span>
                    <h3>Categories</h3>
                </div>
                <p style="font-size:13.5px; color:var(--text-secondary); margin:0 0 16px;">
                    Select categories to control which vendors see this tender and receive email notifications.
                    Leave unselected to make the tender visible to all vendors.
                </p>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:10px;">
                    @foreach($categories as $cat)
                    <label style="display:flex; align-items:center; gap:10px; padding:10px 14px;
                                  border:1.5px solid var(--border); border-radius:8px; cursor:pointer;
                                  transition:border-color .15s, background .15s;"
                           onmouseover="this.style.borderColor='var(--brand-primary)'"
                           onmouseout="this.style.borderColor='var(--border)'">
                        <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                               style="accent-color:var(--brand-primary); width:15px; height:15px;">
                        <span style="display:inline-flex; align-items:center; justify-content:center;
                                     width:26px; height:26px; border-radius:6px; color:#fff; font-size:11px; flex-shrink:0;
                                     background-color:{{ $cat->color }}">
                            <i class="fas {{ $cat->icon }}"></i>
                        </span>
                        <span style="font-size:13.5px; font-weight:500; color:var(--text-primary);">{{ $cat->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Section 7: Tender Visibility --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot" style="background:#7c3aed;"></span>
                    <h3>Tender Visibility</h3>
                </div>
                <p style="font-size:13px;color:var(--text-muted);margin:0 0 16px;">
                    Control who can see and apply for this tender.
                </p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;
                                  border:1.5px solid var(--border);border-radius:var(--radius-md);cursor:pointer;
                                  background:var(--surface-alt);" id="ownerVisPublic">
                        <input type="radio" name="is_private" value="0"
                               {{ old('is_private', '0') === '0' ? 'checked' : '' }}
                               style="accent-color:var(--brand-primary);flex-shrink:0;margin-top:3px;"
                               onchange="ownerTogglePrivate(false)">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);">
                                <i class="fas fa-globe" style="color:#16a34a;margin-right:5px;"></i> Public
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                All vendors in the matching categories can view and apply.
                            </div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;
                                  border:1.5px solid var(--border);border-radius:var(--radius-md);cursor:pointer;
                                  background:var(--surface-alt);" id="ownerVisPrivate">
                        <input type="radio" name="is_private" value="1"
                               {{ old('is_private') === '1' ? 'checked' : '' }}
                               style="accent-color:var(--brand-primary);flex-shrink:0;margin-top:3px;"
                               onchange="ownerTogglePrivate(true)">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);">
                                <i class="fas fa-lock" style="color:#dc2626;margin-right:5px;"></i> Private
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                Only the vendors you select below can see and apply for this tender.
                            </div>
                        </div>
                    </label>
                </div>

                <div id="ownerPrivateVendorsBox" style="{{ old('is_private') === '1' ? '' : 'display:none;' }}margin-top:18px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <label style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;">
                            Invited Vendors <span style="color:#dc2626">*</span>
                        </label>
                        <button type="button" onclick="ownerLoadPrivateVendors()"
                                style="font-size:12px;padding:6px 14px;background:var(--brand-primary);color:#fff;border:none;border-radius:6px;cursor:pointer;">
                            <i class="fas fa-sync-alt"></i> Load from Categories
                        </button>
                    </div>
                    <div id="ownerPrivateVendorList"
                         style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--surface-alt);">
                        <p style="font-size:12px;color:var(--text-muted);margin:0;">
                            <i class="fas fa-arrow-up" style="margin-right:4px;"></i>
                            Select categories above, then click "Load from Categories".
                        </p>
                    </div>
                    <input type="hidden" name="notify_specific_vendors" id="ownerPrivateVendorsInput" value="{{ old('notify_specific_vendors') }}">
                    <p style="font-size:12px;color:var(--text-muted);margin-top:6px;">
                        <i class="fas fa-info-circle" style="color:#7c3aed;margin-right:4px;"></i>
                        Only these vendors will be able to view and apply. They will also receive the publish notification email.
                    </p>
                </div>
            </div>

            {{-- Section 8: Vendor Notifications --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="dot" style="background:#0891b2;"></span>
                    <h3>Vendor Notifications</h3>
                </div>

                <label style="display:flex;align-items:flex-start;gap:12px;padding:16px 18px;
                               border:1.5px solid var(--border);border-radius:var(--radius-lg);cursor:pointer;
                               background:var(--surface-alt);transition:border-color .15s;"
                       id="ownerNotifyToggleTile">
                    <input type="checkbox" name="notify_vendors_on_create" value="1" id="ownerNotifyToggle"
                           {{ old('notify_vendors_on_create') ? 'checked' : '' }}
                           style="accent-color:var(--brand-primary);width:16px;height:16px;flex-shrink:0;margin-top:2px;"
                           onchange="ownerToggleNotifyOptions(this.checked)">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-envelope" style="color:#0891b2;"></i>
                            Send email notification to vendors when this tender is published
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">
                            If unchecked, <strong>no email will be sent</strong> to any vendor automatically on publish.
                        </div>
                    </div>
                </label>

                <div id="ownerNotifyOptions" style="{{ old('notify_vendors_on_create') ? '' : 'display:none;' }}margin-top:18px;padding-left:4px;">
                    <p style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">
                        Who should receive the email?
                    </p>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                        <label style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;
                                      border:1.5px solid var(--border);border-radius:var(--radius-md);cursor:pointer;
                                      background:var(--surface-alt);" id="ownerModeAllTile">
                            <input type="radio" name="notify_vendor_mode" value="all"
                                   {{ old('notify_vendor_mode', 'all') === 'all' ? 'checked' : '' }}
                                   style="accent-color:var(--brand-primary);flex-shrink:0;margin-top:3px;"
                                   onchange="ownerToggleSpecificVendors(false)">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);">
                                    <i class="fas fa-users" style="color:#16a34a;margin-right:5px;"></i>
                                    All vendors in the selected categories
                                </div>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                    Every approved vendor in the chosen categories will be notified on publish.
                                </div>
                            </div>
                        </label>

                        <label style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;
                                      border:1.5px solid var(--border);border-radius:var(--radius-md);cursor:pointer;
                                      background:var(--surface-alt);" id="ownerModeSpecificTile">
                            <input type="radio" name="notify_vendor_mode" value="specific"
                                   {{ old('notify_vendor_mode') === 'specific' ? 'checked' : '' }}
                                   style="accent-color:var(--brand-primary);flex-shrink:0;margin-top:3px;"
                                   onchange="ownerToggleSpecificVendors(true)">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--text-primary);">
                                    <i class="fas fa-user-check" style="color:#7c3aed;margin-right:5px;"></i>
                                    Only selected vendors
                                </div>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                                    Manually choose which vendors receive the notification email.
                                </div>
                            </div>
                        </label>
                    </div>

                    <div id="ownerSpecificVendorsBox" style="{{ old('notify_vendor_mode') === 'specific' ? '' : 'display:none;' }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <label style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;">
                                Select Vendors
                            </label>
                            <button type="button" onclick="ownerLoadNotifyVendors()"
                                    style="font-size:12px;padding:6px 14px;background:var(--brand-primary);color:#fff;border:none;border-radius:6px;cursor:pointer;">
                                <i class="fas fa-sync-alt"></i> Load Vendors from Selected Categories
                            </button>
                        </div>
                        <div id="ownerNotifyVendorCheckboxList"
                             style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:12px;background:var(--surface-alt);">
                            <p style="font-size:12px;color:var(--text-muted);margin:0;">
                                <i class="fas fa-arrow-up" style="margin-right:4px;"></i>
                                Select categories above, then click "Load Vendors".
                            </p>
                        </div>
                        <input type="hidden" name="notify_specific_vendors_email" id="ownerNotifySpecificVendorsInput" value="{{ old('notify_specific_vendors_email') }}">
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="form-footer">
                <a href="{{ route('tender-owner.dashboard') }}" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Publish Tender
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/* ════════════════════════════════════════
   ELIGIBILITY QUESTIONS
════════════════════════════════════════ */
let questionCount = 0;

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const empty = document.getElementById('emptyState');
    if (empty) empty.remove();

    const div = document.createElement('div');
    div.className = 'question-card';
    div.setAttribute('data-id', questionCount);

    div.innerHTML = `
        <div class="question-header">
            <span class="question-badge">
                <i class="fas fa-question" style="font-size:10px;"></i> Question ${questionCount + 1}
            </span>
            <button type="button" onclick="removeQuestion(this)" class="btn-remove">
                <i class="fas fa-trash-alt"></i> Remove
            </button>
        </div>
        <div class="question-body">
            <div class="field q-full">
                <label>Question Text <span class="req">*</span></label>
                <input type="text" name="questions[${questionCount}][question]" required
                       placeholder="Enter your question…">
            </div>
            <div class="field q-full">
                <label>Help Text <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
                <textarea name="questions[${questionCount}][description]" rows="2"
                          placeholder="Provide additional context or guidance…"></textarea>
            </div>
            <div class="field">
                <label>Field Type <span class="req">*</span></label>
                <select name="questions[${questionCount}][field_type]"
                        onchange="toggleOptions(this, ${questionCount})" required>
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
            <div class="required-toggle">
                <input type="checkbox" name="questions[${questionCount}][is_required]" value="1" checked
                       id="req_${questionCount}">
                <span>Mark as required</span>
            </div>
            <div class="required-toggle">
                <input type="checkbox" name="questions[${questionCount}][determines_eligibility]" value="1"
                       id="elig_${questionCount}" onchange="toggleDeterminesEligibility(this, ${questionCount})">
                <span>Determines eligibility</span>
            </div>
            <div id="options_${questionCount}" class="field sub-field q-full">
                <label>Options <span style="font-weight:400;color:var(--text-muted);">(comma separated)</span></label>
                <input type="text" name="questions[${questionCount}][options]"
                       placeholder="Option A, Option B, Option C">
            </div>
            <div id="eligible_options_${questionCount}" class="field sub-field q-full" style="display:none;">
                <label>Eligible Options <span style="font-weight:400;color:var(--text-muted);">(comma separated)</span></label>
                <input type="text" name="questions[${questionCount}][eligible_options]"
                       placeholder="Option A, Option C">
                <p class="hint">Only enter options that qualify the vendor as eligible.</p>
            </div>
            <div id="file_size_${questionCount}" class="field sub-field">
                <label>Max File Size (KB)</label>
                <input type="number" name="questions[${questionCount}][max_size]" value="5120">
            </div>
        </div>
    `;

    container.appendChild(div);
    questionCount++;
}

function removeQuestion(btn) {
    btn.closest('.question-card').remove();
    const container = document.getElementById('questionsContainer');
    if (!container.querySelector('.question-card')) {
        const empty = document.createElement('div');
        empty.id = 'emptyState';
        empty.className = 'questions-empty';
        empty.innerHTML = '<i class="fas fa-question-circle"></i>No questions added yet.<br>Click <strong>Add Question</strong> to get started.';
        container.appendChild(empty);
    }
}

function toggleOptions(select, index) {
    const type = select.value;
    const optEl  = document.getElementById(`options_${index}`);
    const fileEl = document.getElementById(`file_size_${index}`);
    const eligEl = document.getElementById(`eligible_options_${index}`);
    const isOpt  = ['select','radio','checkbox'].includes(type);
    if (optEl)  optEl.classList.toggle('visible', isOpt);
    if (fileEl) fileEl.classList.toggle('visible', type === 'file');
    if (eligEl) {
        const chk = document.getElementById(`elig_${index}`);
        eligEl.style.display = (isOpt && chk && chk.checked) ? 'block' : 'none';
    }
}

function toggleDeterminesEligibility(checkbox, index) {
    const eligEl = document.getElementById(`eligible_options_${index}`);
    if (!eligEl) return;
    const sel = document.querySelector(`select[name="questions[${index}][field_type]"]`);
    const isOpt = sel && ['select','radio','checkbox'].includes(sel.value);
    eligEl.style.display = (checkbox.checked && isOpt) ? 'block' : 'none';
}

document.querySelectorAll('input[name="tender_type"]').forEach(radio => {
    radio.addEventListener('change', function () {
        document.querySelectorAll('.type-tile').forEach(t => t.classList.remove('selected'));
        this.closest('.type-tile').classList.add('selected');
        document.getElementById('postalBox').classList.toggle('visible', this.value === 'postal');
    });
});

/* ════════════════════════════════════════
   DOCUMENT ROWS
════════════════════════════════════════ */
let documentCount = 0;

function addDocument() {
    const container = document.getElementById('documentsContainer');
    const idx = documentCount;
    const div = document.createElement('div');
    div.className = 'doc-item';
    div.setAttribute('data-doc', idx);
    div.innerHTML = `
        <input type="file" name="documents[${idx}]" accept=".pdf,.doc,.docx">
        <select name="document_categories[${idx}]" class="doc-cat">
            <option value="bidding_document">Bidding Document</option>
            <option value="tender_notice">Tender Notice</option>
            <option value="specification">Specification</option>
            <option value="terms_conditions">Terms &amp; Conditions</option>
            <option value="boq">Bill of Quantities</option>
            <option value="drawing">Drawing</option>
            <option value="other">Other</option>
        </select>
        <label class="mandatory-label">
            <input type="checkbox" name="is_mandatory[${idx}]" value="1" checked> Mandatory
        </label>
        <button type="button" onclick="removeDocument(this)" class="btn-rm-doc" title="Remove">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
    documentCount++;
}

function removeDocument(btn) {
    btn.closest('.doc-item').remove();
}

// Start with one row
addDocument();

function ownerTogglePrivate(show) {
    document.getElementById('ownerPrivateVendorsBox').style.display = show ? 'block' : 'none';
}

function ownerLoadPrivateVendors() {
    const checkedCats = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked')).map(c => c.value);
    if (!checkedCats.length) { alert('Please select at least one category first.'); return; }
    const box = document.getElementById('ownerPrivateVendorList');
    box.innerHTML = '<p style="font-size:12px;color:#999;margin:0;">Loading…</p>';
    const existingIds = (document.getElementById('ownerPrivateVendorsInput').value || '').split(',').filter(Boolean);
    const vendorMap = {};
    Promise.all(checkedCats.map(catId =>
        fetch(`/tender-owner/categories/${catId}/vendors`).then(r => r.json()).catch(() => [])
    )).then(results => {
        results.flat().forEach(v => { vendorMap[v.id] = v; });
        const vendors = Object.values(vendorMap);
        if (!vendors.length) { box.innerHTML = '<p style="font-size:12px;color:#999;margin:0;">No vendors found in selected categories.</p>'; return; }
        box.innerHTML = vendors.map(v => `
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;font-size:13px;">
                <input type="checkbox" class="owner-private-cb" value="${v.id}"
                       ${existingIds.includes(String(v.id)) ? 'checked' : ''}
                       style="accent-color:#7c3aed;width:14px;height:14px;"
                       onchange="updateOwnerPrivateIds()">
                <span>${v.company_name || v.name}</span>
                <span style="font-size:11px;color:#999;">${v.email || ''}</span>
            </label>`).join('');
        updateOwnerPrivateIds();
    });
}

function updateOwnerPrivateIds() {
    const ids = Array.from(document.querySelectorAll('.owner-private-cb:checked')).map(c => c.value);
    document.getElementById('ownerPrivateVendorsInput').value = ids.join(',');
}

function ownerToggleNotifyOptions(show) {
    document.getElementById('ownerNotifyOptions').style.display = show ? '' : 'none';
}
function ownerToggleSpecificVendors(show) {
    document.getElementById('ownerSpecificVendorsBox').style.display = show ? '' : 'none';
}
function ownerLoadNotifyVendors() {
    const catIds = [...document.querySelectorAll('input[name="category_ids[]"]:checked')].map(c => c.value);
    const container = document.getElementById('ownerNotifyVendorCheckboxList');
    const hiddenInput = document.getElementById('ownerNotifySpecificVendorsInput');
    if (!catIds.length) { container.innerHTML = '<p style="font-size:12px;color:#9ca3af;margin:0;">Please select at least one category first.</p>'; return; }
    container.innerHTML = '<p style="font-size:12px;color:#6b7280;margin:0;">Loading...</p>';
    Promise.all(catIds.map(id => fetch(`/tender-owner/categories/${id}/vendors`).then(r => r.json())))
        .then(results => {
            const seen = new Set(), vendors = [];
            results.flat().forEach(v => { if (!seen.has(v.id)) { seen.add(v.id); vendors.push(v); } });
            if (!vendors.length) { container.innerHTML = '<p style="font-size:12px;color:#9ca3af;margin:0;">No vendors found in the selected categories.</p>'; return; }
            const current = (hiddenInput.value || '').split(',').map(s => s.trim()).filter(Boolean);
            container.innerHTML = vendors.map(v => `
                <label style="display:flex;align-items:center;gap:8px;padding:6px 0;cursor:pointer;">
                    <input type="checkbox" value="${v.id}" ${current.includes(String(v.id)) ? 'checked' : ''}
                           onchange="ownerUpdateNotifyVendorIds()"
                           style="accent-color:var(--brand-primary);width:14px;height:14px;">
                    <span style="font-size:13px;color:#1e293b;">${v.company_name} <span style="color:#94a3b8;font-size:11px;">(${v.vendor_code})</span></span>
                </label>`).join('');
        }).catch(() => { container.innerHTML = '<p style="font-size:12px;color:#ef4444;margin:0;">Failed to load vendors.</p>'; });
}
function ownerUpdateNotifyVendorIds() {
    const ids = [...document.querySelectorAll('#ownerNotifyVendorCheckboxList input[type="checkbox"]:checked')].map(c => c.value);
    document.getElementById('ownerNotifySpecificVendorsInput').value = ids.join(',');
}
</script>
@endsection
