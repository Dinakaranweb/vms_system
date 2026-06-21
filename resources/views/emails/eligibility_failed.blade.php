<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Eligibility Check Result</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#d97706,#f59e0b 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 12px;font-size:17px;color:#0f172a}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:140px;flex-shrink:0}
.alert{background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:16px 20px;margin-bottom:24px}
.alert p{margin:0;font-size:13.5px;color:#92400e;line-height:1.6}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>⚠️ Eligibility Check Result</h1>
        <p>You do not meet the eligibility criteria for this tender</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            Thank you for your interest in the tender listed below. After evaluating your eligibility responses, we regret to inform you that your application does not meet the required eligibility criteria.
        </p>

        <div class="card">
            <h2>Tender Details</h2>
            <div class="row"><strong>Tender No.</strong><span>{{ $tender->tender_number }}</span></div>
            <div class="row"><strong>Title</strong><span>{{ $tender->title }}</span></div>
            <div class="row"><strong>Type</strong><span>{{ ucfirst($tender->tender_type) }}</span></div>
            <div class="row"><strong>Submission Deadline</strong><span>{{ \Carbon\Carbon::parse($tender->submission_deadline)->format('d M Y') }}</span></div>
        </div>

        <div class="alert">
            <p><strong>Result:</strong> Not Eligible<br>Your answers to one or more eligibility criteria did not satisfy the requirements for this tender. You are not permitted to submit an application for this tender.</p>
        </div>

        <p style="font-size:13.5px;color:#475569;line-height:1.6">Please continue to monitor the portal for other tenders that may match your profile.</p>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">This is an automated notification from {{ config('app.name') }}.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/vendor/tenders') }}" style="color:#d97706;text-decoration:none">View All Tenders</a></div>
</div>
</body>
</html>
