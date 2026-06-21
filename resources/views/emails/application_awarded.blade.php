<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bid Awarded</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#0284c7,#38bdf8 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 12px;font-size:17px;color:#0c4a6e}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.badge-awarded{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#e0f2fe;color:#0284c7;border:1px solid #bae6fd}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#0284c7,#38bdf8);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#0284c7;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🏆 Congratulations! Bid Awarded</h1>
        <p>Your application has been selected for contract</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $application->vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            We are delighted to inform you that your bid has been <strong style="color:#0284c7">awarded</strong>. A contract will be generated and shared with you shortly for signing.
        </p>

        <div class="card">
            <h2>Award Details</h2>
            <div class="row"><strong>Application No.</strong><span>{{ $application->application_number }}</span></div>
            <div class="row"><strong>Tender No.</strong><span>{{ $application->tender->tender_number }}</span></div>
            <div class="row"><strong>Tender Title</strong><span>{{ $application->tender->title }}</span></div>
            <div class="row"><strong>Awarded Bid Amount</strong><span>₹ {{ number_format($application->bid_amount, 2) }}</span></div>
            <div class="row"><strong>Status</strong><span class="badge-awarded">Awarded</span></div>
        </div>

        <p style="font-size:13.5px;color:#475569;line-height:1.6">Please log in to the vendor portal to review and sign your contract.</p>

        <div class="cta">
            <a href="{{ url('/vendor/contracts') }}">View &amp; Sign Contract</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8">Application No: {{ $application->application_number }}</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/vendor/contracts') }}">My Contracts</a></div>
</div>
</body>
</html>
