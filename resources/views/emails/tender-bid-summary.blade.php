<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bid Summary</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #1e293b; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #4338ca, #7c3aed); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
        .header p { color: rgba(255,255,255,.8); margin: 6px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .tender-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 24px; }
        .tender-card h2 { margin: 0 0 12px; font-size: 17px; color: #0f172a; }
        .meta-row { display: flex; gap: 6px; align-items: flex-start; margin-bottom: 8px; font-size: 13.5px; color: #475569; }
        .meta-row strong { color: #0f172a; min-width: 130px; flex-shrink: 0; }
        .footer { background: #f8fafc; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Bid Summary</h1>
        <p>Bids have been opened for this tender</p>
    </div>
    <div class="body">
        <p style="font-size:14px; color:#475569; line-height:1.7;">
            Dear Purchase Committee Member,
        </p>
        <p style="font-size:14px; color:#475569; line-height:1.7;">
            Bids for the tender below have just been opened. A ranked spreadsheet of every bid amount received is attached to this email for your review.
        </p>
        <div class="tender-card">
            <h2>{{ $tender->title }}</h2>
            <div class="meta-row"><strong>Tender No.</strong><span>{{ $tender->tender_number }}</span></div>
            <div class="meta-row"><strong>Opened At</strong><span>{{ $tender->bid_opening_at?->format('d M Y, h:i A') }}</span></div>
        </div>
        <p style="font-size:13.5px; color:#475569; line-height:1.6;">
            Log in to the Purchase Committee portal to view full application details and add your comments.
        </p>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} VMU Vendor Management System
    </div>
</div>
</body>
</html>
