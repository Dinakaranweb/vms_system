<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>New Application Received</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#0f766e,#14b8a6 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:20px}
.card h2{margin:0 0 12px;font-size:16px;color:#0f172a}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#0f766e;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>📥 New Application Received</h1>
        <p>A vendor has submitted an application for a tender</p>
    </div>
    <div class="body">
        <p style="font-size:14px;color:#475569;line-height:1.7;margin-bottom:20px">
            A new tender application has been submitted and requires your review.
        </p>

        <div class="card">
            <h2>Tender Details</h2>
            <div class="row"><strong>Tender No.</strong><span>{{ $application->tender->tender_number }}</span></div>
            <div class="row"><strong>Title</strong><span>{{ $application->tender->title }}</span></div>
            <div class="row"><strong>Type</strong><span>{{ ucfirst($application->tender->tender_type) }}</span></div>
            <div class="row"><strong>Submission Deadline</strong><span>{{ \Carbon\Carbon::parse($application->tender->submission_deadline)->format('d M Y') }}</span></div>
        </div>

        <div class="card">
            <h2>Applicant Details</h2>
            <div class="row"><strong>Application No.</strong><span>{{ $application->application_number }}</span></div>
            <div class="row"><strong>Company Name</strong><span>{{ $application->vendor->company_name }}</span></div>
            <div class="row"><strong>Vendor Code</strong><span>{{ $application->vendor->vendor_code }}</span></div>
            <div class="row"><strong>Email</strong><span>{{ $application->vendor->email }}</span></div>
            <div class="row"><strong>Bid Amount</strong><span>₹ {{ number_format($application->bid_amount, 2) }}</span></div>
            <div class="row"><strong>Submitted On</strong><span>{{ $application->submitted_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</span></div>
        </div>

        <div class="cta">
            <a href="{{ url('/admin/applications') }}">Review Application</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">This is an automated notification from {{ config('app.name') }}.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/admin/applications') }}">Manage Applications</a></div>
</div>
</body>
</html>
