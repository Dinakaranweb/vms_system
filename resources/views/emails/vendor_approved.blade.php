<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vendor Account Approved</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#16a34a,#4ade80 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 14px;font-size:17px;color:#14532d}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:150px;flex-shrink:0}
.badge-success{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#dcfce7;color:#15803d}
.cta{text-align:center;margin:28px 0 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#16a34a;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>✅ Account Approved!</h1>
        <p>Your vendor registration has been approved</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            We are pleased to inform you that your vendor registration has been <strong style="color:#16a34a">approved</strong>. You can now log in to the vendor portal and start participating in tenders.
        </p>

        <div class="card">
            <h2>Your Account Details</h2>
            <div class="row"><strong>Vendor Code</strong><span>{{ $vendor->vendor_code }}</span></div>
            <div class="row"><strong>Company Name</strong><span>{{ $vendor->company_name }}</span></div>
            <div class="row"><strong>Email (Login)</strong><span>{{ $vendor->email }}</span></div>
            <div class="row"><strong>Status</strong><span class="badge-success">Approved</span></div>
            <div class="row"><strong>Approved On</strong><span>{{ $vendor->approved_at?->format('d M Y, h:i A') }}</span></div>
        </div>

        <p style="font-size:13.5px;color:#475569;line-height:1.6">You can now log in and browse available tenders, submit applications, and manage your profile.</p>

        <div class="cta">
            <a href="{{ url('/login') }}" style="margin-right:12px;background:linear-gradient(135deg,#16a34a,#22c55e);display:inline-block;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600;">Login to Portal</a>
            <a href="{{ url('/vendor/tenders') }}" style="background:linear-gradient(135deg,#0284c7,#1a56db);display:inline-block;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-size:14px;font-weight:600;">Browse Tenders</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">If you have any questions, please contact the system administrator.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/login') }}">Login Portal</a></div>
</div>
</body>
</html>
