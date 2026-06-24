<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Application Status Update</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#dc2626,#f87171 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 12px;font-size:17px;color:#0f172a}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.reason-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;margin-bottom:24px}
.reason-box p{margin:0;font-size:13.5px;color:#b91c1c;line-height:1.6}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>📋 Application Status Update</h1>
        <p>Regarding your tender application</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $application->vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            After careful evaluation, we regret to inform you that your application for the following tender has not been selected to proceed further.
        </p>

        <div class="card">
            <h2>Application Details</h2>
            <div class="row"><strong>Application No.</strong><span>{{ $application->application_number }}</span></div>
            <div class="row"><strong>Tender No.</strong><span>{{ $application->tender->tender_number }}</span></div>
            <div class="row"><strong>Tender Title</strong><span>{{ $application->tender->title }}</span></div>
            <div class="row"><strong>Bid Amount</strong><span>{{ ($application->bid_amount !== null && $application->bid_amount > 0) ? '₹ ' . number_format($application->bid_amount, 2) : 'N/A' }}</span></div>
        </div>

        @if($application->rejection_reason)
        <div class="reason-box">
            <p><strong>Reason:</strong><br>{{ $application->rejection_reason }}</p>
        </div>
        @endif

        <p style="font-size:13.5px;color:#475569;line-height:1.6">We appreciate your effort and encourage you to apply for future tenders that match your profile.</p>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8">Application No: {{ $application->application_number }}</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/vendor/tenders') }}" style="color:#dc2626;text-decoration:none">Browse Tenders</a></div>
</div>
</body>
</html>
