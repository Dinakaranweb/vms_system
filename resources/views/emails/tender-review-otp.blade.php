<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; margin: 0; padding: 0; background: #f1f5f9; }
  .wrapper { max-width: 620px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
  .header { background: linear-gradient(135deg, #4f46e5, #4338ca); padding: 32px 32px 24px; text-align: center; }
  .header-icon { font-size: 2rem; color: #fff; margin-bottom: 8px; }
  .header h1 { color: #fff; font-size: 22px; margin: 0 0 4px; font-weight: 700; }
  .header p { color: rgba(255,255,255,.85); font-size: 13px; margin: 0; }
  .body { padding: 32px; text-align: center; }
  .otp-box { background: #eef2ff; border: 2px dashed #6366f1; border-radius: 10px; padding: 20px; margin: 24px 0; }
  .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #4338ca; font-family: 'Courier New', monospace; }
  .notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px 18px; margin: 20px 0; font-size: 13px; color: #92400e; line-height: 1.6; text-align: left; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="header-icon">🔐</div>
    <h1>Review Access Code</h1>
    <p>Enter this code to view and comment on applications</p>
  </div>
  <div class="body">
    <p style="text-align:left;color:#475569;line-height:1.7;">
      Dear {{ $reviewOtp->committeeMember->name }},<br><br>
      The applications for <strong>{{ $reviewOtp->tender->tender_number }}</strong> ({{ $reviewOtp->tender->title }})
      have been opened for Purchase Committee review. Enter the code below in the portal to unlock viewing and add your recommendations.
    </p>
    <div class="otp-box">
      <div class="otp-code">{{ $otpCode }}</div>
    </div>
    <div class="notice">
      <strong>This code expires in 30 minutes.</strong> You only need to enter it once for this tender — after that you can freely view every application and leave comments. Do not share this code with anyone.
    </div>
  </div>
  <div class="footer">
    <strong>VMRF-DU Vendor Management System</strong><br>
    This is an automated notification. Please do not reply to this email.
  </div>
</div>
</body>
</html>
