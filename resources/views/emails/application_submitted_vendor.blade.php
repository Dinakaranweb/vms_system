<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Application Submitted</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#1a56db,#6366f1);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.8);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 12px;font-size:17px;color:#0f172a}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.badge-info{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#eff6ff;color:#1a56db;border:1px solid #bfdbfe}
.postal-box{background:#fefce8;border:1px solid #fef08a;border-radius:10px;padding:16px 20px;margin-bottom:20px}
.postal-box p{margin:0 0 6px;font-size:13.5px;color:#713f12;line-height:1.6}
.postal-box strong{color:#92400e}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#1a56db,#6366f1);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#1a56db;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>📋 Application Submitted</h1>
        <p>Your tender application has been received</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $application->vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            Your application for the following tender has been successfully submitted and is now under review.
        </p>

        <div class="card">
            <h2>Application Details</h2>
            <div class="row"><strong>Application No.</strong><span>{{ $application->application_number }}</span></div>
            <div class="row"><strong>Tender No.</strong><span>{{ $application->tender->tender_number }}</span></div>
            <div class="row"><strong>Tender Title</strong><span>{{ $application->tender->title }}</span></div>
            <div class="row"><strong>Tender Type</strong><span>{{ ucfirst($application->tender->tender_type) }}</span></div>
            <div class="row"><strong>Bid Amount</strong><span>{{ ($application->bid_amount !== null && $application->bid_amount > 0) ? '₹ ' . number_format($application->bid_amount, 2) : 'N/A' }}</span></div>
            <div class="row"><strong>Status</strong><span class="badge-info">Submitted</span></div>
            <div class="row"><strong>Submitted On</strong><span>{{ $application->submitted_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</span></div>
        </div>

        @if($application->tender->isPostal())
        <div class="postal-box">
            <p><strong>📬 Postal Submission Required</strong></p>
            <p>This is a <strong>Postal Tender</strong>. You must also physically mail your application documents to the address below within 7 days:</p>
            <p style="margin-top:10px;font-size:14px;font-weight:600;color:#1e293b">{{ $application->tender->postal_address }}</p>
            @if($application->tender->contact_person)
            <p style="margin-top:6px;font-size:13px">Contact Person: <strong>{{ $application->tender->contact_person }}</strong>
            @if($application->tender->contact_phone) | {{ $application->tender->contact_phone }}@endif
            @if($application->tender->contact_email) | {{ $application->tender->contact_email }}@endif
            </p>
            @endif
        </div>
        @endif

        <div class="cta">
            <a href="{{ url('/vendor/applications/' . $application->id) }}">View Application Status</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">Keep your application number <strong>{{ $application->application_number }}</strong> for reference.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/vendor/applications') }}">My Applications</a></div>
</div>
</body>
</html>
