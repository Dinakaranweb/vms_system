<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 14px; color: #1e293b; margin: 0; padding: 0; background: #f1f5f9; }
  .wrapper { max-width: 620px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
  .header { background: linear-gradient(135deg, #059669, #047857); padding: 32px 32px 24px; text-align: center; }
  .header-icon { font-size: 2rem; color: #fff; margin-bottom: 8px; }
  .header h1 { color: #fff; font-size: 22px; margin: 0 0 4px; font-weight: 700; }
  .header p { color: rgba(255,255,255,.85); font-size: 13px; margin: 0; }
  .body { padding: 32px; }
  .greeting { font-size: 16px; color: #0f172a; margin-bottom: 16px; }
  .info-box { background: #ecfdf5; border-left: 4px solid #10b981; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
  .info-box .label { font-size: 11px; color: #065f46; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
  .info-box .value { font-size: 15px; color: #1e293b; font-weight: 600; }
  .notice { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin: 20px 0; font-size: 13px; color: #475569; line-height: 1.6; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
  .footer strong { color: #64748b; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="header-icon">📂</div>
    <h1>Bids Opened</h1>
    <p>The bid opening process has begun for this tender</p>
  </div>

  <div class="body">
    <p class="greeting">Dear Vendor,</p>
    <p style="color:#475569;line-height:1.7;">
      The Purchase Committee has opened bids for the tender you submitted an application for. Evaluation and comparison of all received bids is now underway.
    </p>

    <div class="info-box">
      <div class="label">Tender Reference</div>
      <div class="value">{{ $tender->tender_number }}</div>
    </div>

    <div class="info-box">
      <div class="label">Tender Title</div>
      <div class="value">{{ $tender->title }}</div>
    </div>

    <div class="info-box">
      <div class="label">Bids Opened On</div>
      <div class="value">{{ $tender->bid_opening_at->format('d M Y, h:i A') }}</div>
    </div>

    <div class="notice">
      <strong>What happens next:</strong> The Purchase Committee will review and compare all submitted bids. You will be notified separately once an award decision has been made for this tender.
    </div>

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
