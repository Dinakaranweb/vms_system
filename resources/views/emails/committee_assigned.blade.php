<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Committee Assignment</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#4f46e5,#7c3aed 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#eef2ff;border:1px solid #c7d2fe;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 12px;font-size:16px;color:#3730a3}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:160px;flex-shrink:0}
.badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;background:#e0e7ff;color:#4338ca;border:1px solid #c7d2fe}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.info-box{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13.5px;color:#92400e;line-height:1.6}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Committee Assignment</h1>
        <p>You have been added to a tender evaluation committee</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $member->name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            You have been assigned as a <strong style="color:#4338ca">{{ ucwords(str_replace('_', ' ', $committeeRole)) }}</strong>
            on the evaluation committee for the following tender.
        </p>

        <div class="card">
            <h2>Tender Details</h2>
            <div class="row"><strong>Tender Number</strong><span>{{ $tender->tender_number }}</span></div>
            <div class="row"><strong>Title</strong><span>{{ $tender->title }}</span></div>
            <div class="row"><strong>Your Role</strong><span class="badge">{{ ucwords(str_replace('_', ' ', $committeeRole)) }}</span></div>
            @if($designation)
            <div class="row"><strong>Designation</strong><span>{{ $designation }}</span></div>
            @endif
            @if($tender->submission_deadline)
            <div class="row"><strong>Submission Deadline</strong><span>{{ \Carbon\Carbon::parse($tender->submission_deadline)->format('d M, Y') }}</span></div>
            @endif
        </div>

        <div class="info-box">
            <strong>What this means:</strong> You will be able to view and evaluate vendor applications submitted for this tender through the committee portal. Please log in to get started.
        </div>

        <div class="cta">
            <a href="{{ url('/committee/dashboard') }}">Go to Committee Portal</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;margin:0">
            If you believe you received this email in error, please contact the administrator.
        </p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
</div>
</body>
</html>
