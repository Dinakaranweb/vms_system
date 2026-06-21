<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Application Shortlisted</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#7c3aed,#a78bfa 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 12px;font-size:17px;color:#4c1d95}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.badge-shortlisted{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#f3e8ff;color:#7c3aed;border:1px solid #e9d5ff}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#7c3aed;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🌟 Application Shortlisted!</h1>
        <p>You have advanced to the next stage</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $application->vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            Congratulations! Your application for the tender below has been <strong style="color:#7c3aed">shortlisted</strong>. You have advanced to the next evaluation stage.
        </p>

        <div class="card">
            <h2>Application Details</h2>
            <div class="row"><strong>Application No.</strong><span>{{ $application->application_number }}</span></div>
            <div class="row"><strong>Tender No.</strong><span>{{ $application->tender->tender_number }}</span></div>
            <div class="row"><strong>Tender Title</strong><span>{{ $application->tender->title }}</span></div>
            <div class="row"><strong>Bid Amount</strong><span>₹ {{ number_format($application->bid_amount, 2) }}</span></div>
            <div class="row"><strong>Status</strong><span class="badge-shortlisted">Shortlisted</span></div>
            @if($application->admin_remarks)
            <div class="row"><strong>Remarks</strong><span>{{ $application->admin_remarks }}</span></div>
            @endif
        </div>

        <p style="font-size:13.5px;color:#475569;line-height:1.6">Our team will be in touch with the next steps. Please monitor your application status via the portal.</p>

        <div class="cta">
            <a href="{{ url('/vendor/applications/' . $application->id) }}">View Application</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8">Application No: {{ $application->application_number }}</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/vendor/applications') }}">My Applications</a></div>
</div>
</body>
</html>
