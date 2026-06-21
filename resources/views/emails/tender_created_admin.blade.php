<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Tender Created</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#0f766e,#0284c7);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.8);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 14px;font-size:17px;color:#0f172a}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.badge-draft{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#fef9c3;color:#854d0e}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#0f766e,#0284c7);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#0284c7;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>New Tender Submitted for Review</h1>
        <p>A tender-owner has created a new tender awaiting admin review</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:20px">
            <strong>{{ $owner->name }}</strong> has created a new tender. Please review and publish it when ready.
        </p>

        <div class="card">
            <h2>Tender Details</h2>
            <div class="row"><strong>Tender Number</strong><span>{{ $tender->tender_number }}</span></div>
            <div class="row"><strong>Title</strong><span>{{ $tender->title }}</span></div>
            <div class="row"><strong>Type</strong><span>{{ ucfirst($tender->tender_type) }}</span></div>
            <div class="row"><strong>Estimated Value</strong><span>{{ $tender->estimated_value ? '₹' . number_format($tender->estimated_value, 2) : 'Not specified' }}</span></div>
            <div class="row"><strong>Submission Deadline</strong><span>{{ $tender->submission_deadline?->format('d M Y') }}</span></div>
            <div class="row"><strong>Opening Date</strong><span>{{ $tender->opening_date?->format('d M Y') }}</span></div>
            <div class="row"><strong>Created By</strong><span>{{ $owner->name }} ({{ $owner->email }})</span></div>
            <div class="row"><strong>Created At</strong><span>{{ $tender->created_at->format('d M Y, h:i A') }}</span></div>
            <div class="row"><strong>Status</strong><span class="badge-draft">Draft — Awaiting Publish</span></div>
        </div>

        @if($tender->description)
        <div class="card">
            <h2>Description</h2>
            <p style="font-size:13.5px;color:#475569;line-height:1.6;margin:0">{{ Str::limit($tender->description, 400) }}</p>
        </div>
        @endif

        <p style="font-size:13.5px;color:#475569;line-height:1.6">
            Please log in to the admin panel to review this tender. Once reviewed, you can publish it to notify vendors.
        </p>

        <div class="cta">
            <a href="{{ url('/admin/tenders/' . $tender->id . '/edit') }}">Review &amp; Publish Tender</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">This is an automated notification from {{ config('app.name') }}.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/admin/dashboard') }}">Admin Panel</a></div>
</div>
</body>
</html>
