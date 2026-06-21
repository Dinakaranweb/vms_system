<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $contract->order_type === 'work_order' ? 'Work Order' : 'Purchase Order' }} – VMRF-DU</title>
<style>
  body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; font-size: 14px; background: #f4f6f9; color: #333; }
  .wrapper { max-width: 620px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: linear-gradient(135deg, #003366 0%, #0055a5 100%); padding: 30px 36px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 24px; letter-spacing: 1px; }
  .header p { color: #b0c8e8; margin: 6px 0 0; font-size: 13px; }
  .body { padding: 32px 36px; }
  .greeting { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
  .intro { color: #555; line-height: 1.6; margin-bottom: 20px; }
  .info-card { background: #f0f5fb; border: 1px solid #ccddf0; border-radius: 6px; padding: 18px 22px; margin-bottom: 22px; }
  .info-card h3 { margin: 0 0 12px; font-size: 13px; color: #003366; text-transform: uppercase; letter-spacing: 0.5px; }
  .info-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #dde8f5; font-size: 13px; }
  .info-row:last-child { border-bottom: none; }
  .info-row .lbl { color: #666; }
  .info-row .val { font-weight: bold; color: #222; }
  .amount-box { background: #003366; color: #fff; border-radius: 6px; padding: 16px 22px; text-align: center; margin-bottom: 22px; }
  .amount-box .amt { font-size: 28px; font-weight: bold; }
  .amount-box .amt-sub { font-size: 12px; opacity: 0.8; margin-top: 4px; }
  .note { background: #fff8e1; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 0 4px 4px 0; font-size: 13px; color: #555; margin-bottom: 20px; }
  .footer { background: #f4f6f9; padding: 20px 36px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ $contract->order_type === 'work_order' ? 'Work Order' : 'Purchase Order' }}</h1>
    <p>VMRF-DU – Vinayaka Missions Research Foundation – Deemed University</p>
  </div>

  <div class="body">
    <div class="greeting">Dear {{ $contract->vendor->company_name }},</div>
    <p class="intro">
      Please find attached your <strong>{{ $contract->order_type === 'work_order' ? 'Work Order' : 'Purchase Order' }}</strong>
      issued by VMRF-DU. Kindly review all details, sign the document, and return a copy as per the terms mentioned.
    </p>

    <div class="info-card">
      <h3>Order Details</h3>
      <div class="info-row"><span class="lbl">Order Number</span><span class="val">{{ $contract->order_number }}</span></div>
      <div class="info-row"><span class="lbl">Order Date</span><span class="val">{{ \Carbon\Carbon::parse($contract->order_date)->format('d M Y') }}</span></div>
      <div class="info-row"><span class="lbl">Tender Reference</span><span class="val">{{ $contract->tender->tender_number ?? '—' }}</span></div>
      <div class="info-row"><span class="lbl">Contract Number</span><span class="val">{{ $contract->contract_number }}</span></div>
      <div class="info-row"><span class="lbl">Work / Supply Description</span><span class="val">{{ $contract->tender->title ?? '—' }}</span></div>
    </div>

    @php
      $items = $contract->order_items ?? [];
      $subtotal = array_sum(array_column($items, 'amount'));
      $discount = (float)$contract->order_discount;
      $gstRate = (float)$contract->gst_rate;
      $afterDisc = $subtotal - $discount;
      $gstAmount = round($afterDisc * $gstRate / 100, 2);
      $nettTotal = round($afterDisc + $gstAmount, 2);
    @endphp

    <div class="amount-box">
      <div class="amt">₹ {{ number_format($nettTotal, 2) }}</div>
      <div class="amt-sub">Total Order Value (Incl. GST @ {{ $gstRate }}%)</div>
    </div>

    <div class="note">
      <strong>Important:</strong> The PDF copy of your {{ $contract->order_type === 'work_order' ? 'Work Order' : 'Purchase Order' }} is attached to this email.
      Please sign and return within 3 working days. For queries, contact procurement@vmu.edu.in.
    </div>
  </div>

  <div class="footer">
    VMRF-DU Procurement Department &nbsp;|&nbsp; No. 160, Sankari Main Road, Salem – 636 308<br>
    Phone: 0427-3912200 &nbsp;|&nbsp; Email: procurement@vmu.edu.in
  </div>
</div>
</body>
</html>
