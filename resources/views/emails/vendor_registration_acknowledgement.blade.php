<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Welcome to {{ config('app.name') }}</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#059669,#10b981);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.card h2{margin:0 0 14px;font-size:17px;color:#0f172a}
.row{display:flex;gap:6px;align-items:flex-start;margin-bottom:8px;font-size:13.5px;color:#475569}
.row strong{color:#0f172a;min-width:150px;flex-shrink:0}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#fef9c3;color:#854d0e}
.steps{margin:0;padding-left:20px;font-size:13.5px;color:#475569;line-height:1.9}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#059669;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>👋 Welcome, {{ $vendor->company_name }}</h1>
        <p>Thank you for registering on {{ config('app.name') }}</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:20px">
            Your vendor registration has been received successfully. Here's what happens next:
        </p>

        <div class="card">
            <h2>Registration Summary</h2>
            <div class="row"><strong>Company Name</strong><span>{{ $vendor->company_name }}</span></div>
            <div class="row"><strong>Vendor Code</strong><span>{{ $vendor->vendor_code }}</span></div>
            <div class="row"><strong>Registered At</strong><span>{{ $vendor->created_at->format('d M Y, h:i A') }}</span></div>
            <div class="row"><strong>Status</strong><span class="badge">Awaiting Approval</span></div>
        </div>

        <ol class="steps">
            <li>Our Admin team will review your submitted details and documents.</li>
            <li>You'll receive an email as soon as your account is approved.</li>
            <li>Once approved, you can log in and start participating in tenders — browsing published tenders and submitting your bids.</li>
        </ol>

        <p style="font-size:13.5px;color:#475569;line-height:1.6;margin-top:20px">
            No action is needed from you right now. If any of your details need correction, please contact the procurement team before approval.
        </p>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">This is an automated notification from {{ config('app.name') }}.</p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
</div>
</body>
</html>
