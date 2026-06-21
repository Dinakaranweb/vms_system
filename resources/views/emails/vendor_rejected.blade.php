<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vendor Registration Update</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#dc2626,#f87171 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.reason-box{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px 20px;margin-bottom:24px}
.reason-box p{margin:0;font-size:13.5px;color:#b91c1c;line-height:1.6}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:150px;flex-shrink:0}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>❌ Registration Not Approved</h1>
        <p>Your vendor registration application</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $vendor->company_name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            After reviewing your registration, we regret to inform you that your vendor application could not be approved at this time.
        </p>

        <div class="reason-box">
            <p><strong>Reason for Rejection:</strong><br>{{ $reason }}</p>
        </div>

        <div class="card">
            <div class="row"><strong>Vendor Code</strong><span>{{ $vendor->vendor_code }}</span></div>
            <div class="row"><strong>Company Name</strong><span>{{ $vendor->company_name }}</span></div>
            <div class="row"><strong>Email</strong><span>{{ $vendor->email }}</span></div>
        </div>

        <p style="font-size:13.5px;color:#475569;line-height:1.6">
            If you believe this decision is incorrect or would like to address the above reason, please contact the administrator for further guidance.
        </p>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">This is an automated notification from {{ config('app.name') }}.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
</div>
</body>
</html>
