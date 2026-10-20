<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Password Was Reset</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#D97706,#F59E0B);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.alert-box{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.cta{text-align:center;margin:28px 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#D97706,#F59E0B);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:.3px}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#D97706;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>🔒 Your Password Was Reset</h1>
        <p>A Super Admin reset the password on your account</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $user->name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            The password for your account (<strong>{{ $user->email }}</strong>) on {{ config('app.name') }} was just reset by a Super Admin.
            You'll need your new password the next time you log in — please contact the IT Department if you don't already have it.
        </p>

        <div class="alert-box">
            <p style="margin:0;font-size:13.5px;color:#92400E;line-height:1.6">
                <strong>Didn't expect this?</strong> If you believe this was done in error, contact the IT Department immediately.
            </p>
        </div>

        <div class="cta">
            <a href="{{ url('/login') }}">Go to Login</a>
        </div>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">
            This is an automated security notification from {{ config('app.name') }}.
        </p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/login') }}">Login Portal</a></div>
</div>
</body>
</html>
