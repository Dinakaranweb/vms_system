<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Your Password</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:linear-gradient(135deg,#3b82f6,#6366f1 200%);padding:36px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:22px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:36px 40px}
.alert-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:20px 24px;margin-bottom:24px}
.cta{text-align:center;margin:28px 0}
.cta a{display:inline-block;background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:.3px}
.divider{border:none;border-top:1px solid #e2e8f0;margin:28px 0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
.footer a{color:#3b82f6;text-decoration:none}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Reset Your Password</h1>
        <p>Password reset request for your account</p>
    </div>
    <div class="body">
        <p style="font-size:15px;margin-bottom:16px">Dear <strong>{{ $user->name }}</strong>,</p>
        <p style="font-size:14px;color:#475569;line-height:1.7">
            We received a request to reset the password for your account associated with <strong>{{ $user->email }}</strong>.
            Click the button below to set a new password.
        </p>

        <div class="cta">
            <a href="{{ $resetUrl }}">Reset Password</a>
        </div>

        <div class="alert-box">
            <p style="margin:0;font-size:13.5px;color:#1e40af;line-height:1.6">
                <strong>Note:</strong> This password reset link will expire in <strong>60 minutes</strong>.
                If you did not request a password reset, please ignore this email — your password will remain unchanged.
            </p>
        </div>

        <p style="font-size:13px;color:#64748b;line-height:1.6">
            If the button above doesn't work, copy and paste this URL into your browser:
        </p>
        <p style="font-size:12px;color:#3b82f6;word-break:break-all;background:#f1f5f9;padding:10px 14px;border-radius:6px;">{{ $resetUrl }}</p>

        <hr class="divider">
        <p style="font-size:12px;color:#94a3b8;line-height:1.6">
            This email was sent by {{ config('app.name') }}. If you have questions, contact the system administrator.
        </p>
    </div>
    <div class="footer">&copy; {{ date('Y') }} {{ config('app.name') }} &nbsp;·&nbsp; <a href="{{ url('/login') }}">Login Portal</a></div>
</div>
</body>
</html>
