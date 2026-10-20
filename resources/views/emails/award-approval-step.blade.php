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
  .body { padding: 32px; }
  .info-box { background: #eef2ff; border-left: 4px solid #4f46e5; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
  .info-box .label { font-size: 11px; color: #3730a3; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
  .info-box .value { font-size: 15px; color: #1e293b; font-weight: 600; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="header-icon">🗳️</div>
    <h1>Your Approval Is Needed</h1>
    <p>Purchase Committee — award recommendation</p>
  </div>
  <div class="body">
    <p>Dear {{ $recommendation->currentStepApproval()->committeeMember->name ?? 'Committee Member' }},</p>
    <p style="color:#475569;line-height:1.7;">
      A vendor award recommendation is now awaiting your review. You are approver #{{ $recommendation->currentStepApproval()->approval_order ?? '' }} in the sequence — please log in to the portal to review and add your comments.
    </p>
    <div class="info-box">
      <div class="label">Tender</div>
      <div class="value">{{ $recommendation->tender->tender_number }} — {{ $recommendation->tender->title }}</div>
    </div>
    <div class="info-box">
      <div class="label">Recommended Vendor</div>
      <div class="value">{{ $recommendation->application->vendor->company_name ?? 'N/A' }}</div>
    </div>
    <p style="color:#475569;line-height:1.7;margin-top:24px;">Please log in to the VMS portal to view the full details and record your decision.</p>
  </div>
  <div class="footer">
    <strong>VMRF-DU Vendor Management System</strong><br>
    This is an automated notification. Please do not reply to this email.
  </div>
</div>
</body>
</html>
