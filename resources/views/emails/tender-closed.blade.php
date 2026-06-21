<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; margin: 0; padding: 0; background: #f1f5f9; }
  .wrapper { max-width: 620px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
  .header { background: linear-gradient(135deg, #dc2626, #b91c1c); padding: 32px 32px 24px; text-align: center; }
  .header-icon { font-size: 2rem; color: #fff; margin-bottom: 8px; }
  .header h1 { color: #fff; font-size: 22px; margin: 0 0 4px; font-weight: 700; }
  .header p { color: rgba(255,255,255,.8); font-size: 13px; margin: 0; }
  .body { padding: 32px; }
  .greeting { font-size: 16px; color: #0f172a; margin-bottom: 16px; }
  .info-box { background: #fff7ed; border-left: 4px solid #f97316; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
  .info-box .label { font-size: 11px; color: #9a3412; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
  .info-box .value { font-size: 15px; color: #1e293b; font-weight: 600; }
  .reason-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
  .reason-box .reason-label { font-size: 12px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
  .reason-box .reason-text { font-size: 14px; color: #374151; line-height: 1.6; }
  .notice { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin: 20px 0; font-size: 13px; color: #475569; line-height: 1.6; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
  .footer strong { color: #64748b; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="header-icon">🔒</div>
    <h1>Tender Closed</h1>
    <p>This tender has been officially closed</p>
  </div>

  <div class="body">
    <p class="greeting">Dear Vendor,</p>
    <p style="color:#475569;line-height:1.7;">
      We would like to inform you that the following tender, to which you had submitted an application, has been <strong>closed</strong>.
    </p>

    <div class="info-box">
      <div class="label">Tender Reference</div>
      <div class="value">{{ $tender->tender_number }}</div>
    </div>

    <div class="info-box">
      <div class="label">Tender Title</div>
      <div class="value">{{ $tender->title }}</div>
    </div>

    @if($reason)
    <div class="reason-box">
      <div class="reason-label">Reason for Closure</div>
      <div class="reason-text">{{ $reason }}</div>
    </div>
    @endif

    <div class="notice">
      <strong>Please note:</strong> No further applications or submissions will be accepted for this tender.
      If you have any queries regarding this closure, please contact the procurement team.
    </div>

    <p style="color:#475569;line-height:1.7;margin-top:24px;">
      Thank you for your participation and interest in VMRF-DU procurement processes. We encourage you to apply for future tenders.
    </p>

    <p style="color:#475569;margin-top:24px;">
      Regards,<br>
      <strong>VMRF-DU Procurement Team</strong>
    </p>
  </div>

  <div class="footer">
    <strong>VMRF-DU Vendor Management System</strong><br>
    This is an automated notification. Please do not reply to this email.
  </div>
</div>
</body>
</html>
