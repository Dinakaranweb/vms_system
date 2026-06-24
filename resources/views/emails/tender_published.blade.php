<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Tender Published</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #1e293b; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #1a56db, #6366f1); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -.3px; }
        .header p { color: rgba(255,255,255,.8); margin: 6px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 15px; margin-bottom: 20px; }
        .tender-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 24px; }
        .tender-card h2 { margin: 0 0 12px; font-size: 17px; color: #0f172a; }
        .meta-row { display: flex; gap: 6px; align-items: flex-start; margin-bottom: 8px; font-size: 13.5px; color: #475569; }
        .meta-row strong { color: #0f172a; min-width: 130px; flex-shrink: 0; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-published { background: #dcfce7; color: #15803d; }
        .categories { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
        .cat-chip { background: #eff6ff; color: #1a56db; border: 1px solid #bfdbfe; border-radius: 20px; padding: 2px 10px; font-size: 12px; font-weight: 500; }
        .cta { text-align: center; margin: 28px 0 0; }
        .cta a { display: inline-block; background: linear-gradient(135deg, #1a56db, #6366f1); color: #fff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-size: 14px; font-weight: 600; letter-spacing: .2px; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 28px 0; }
        .footer { background: #f8fafc; padding: 20px 40px; text-align: center; font-size: 12px; color: #94a3b8; }
        .footer a { color: #1a56db; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <h1><i>📋</i>&nbsp; New Tender Published</h1>
        <p>A tender matching your category has been published</p>
    </div>

    <div class="body">

        <p class="greeting">
            Dear <strong>{{ $vendor->company_name }}</strong>,
        </p>

        <p style="font-size:14px; color:#475569; line-height:1.7;">
            A new tender has been published that matches your registered category.
            Please review the details below and apply before the deadline.
        </p>

        <div class="tender-card">
            <h2>{{ $tender->title }}</h2>

            <div class="meta-row">
                <strong>Tender No.</strong>
                <span>{{ $tender->tender_number }}</span>
            </div>

            @if($tender->require_estimated_value && $tender->estimated_value > 0)
            <div class="meta-row">
                <strong>Estimated Value</strong>
                <span>₹ {{ number_format($tender->estimated_value, 2) }}</span>
            </div>
            @endif
            @if($tender->require_emd && $tender->emda_amount > 0)
            <div class="meta-row">
                <strong>EMD Amount</strong>
                <span>₹ {{ number_format($tender->emda_amount, 2) }}</span>
            </div>
            @endif

            <div class="meta-row">
                <strong>Submission Deadline</strong>
                <span>{{ \Carbon\Carbon::parse($tender->submission_deadline)->format('d M Y') }}</span>
            </div>

            <div class="meta-row">
                <strong>Opening Date</strong>
                <span>{{ \Carbon\Carbon::parse($tender->opening_date)->format('d M Y') }}</span>
            </div>

            <div class="meta-row">
                <strong>Type</strong>
                <span>{{ ucfirst($tender->tender_type) }}</span>
            </div>

            @if($tender->categories->count())
            <div class="meta-row">
                <strong>Categories</strong>
                <div class="categories">
                    @foreach($tender->categories as $cat)
                        <span class="cat-chip">{{ $cat->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($tender->description)
            <div class="meta-row" style="margin-top:12px;">
                <strong>Description</strong>
                <span>{{ Str::limit($tender->description, 200) }}</span>
            </div>
            @endif
        </div>

        <p style="font-size:13.5px; color:#475569; line-height:1.6;">
            Log in to the vendor portal to view the full tender details, download documents, and submit your application.
        </p>

        <div class="cta">
            <a href="{{ url('/vendor/tenders/' . $tender->id) }}">View Tender &amp; Apply</a>
        </div>

        <hr class="divider">

        <p style="font-size:12px; color:#94a3b8; line-height:1.6;">
            You are receiving this email because your vendor account (<strong>{{ $vendor->vendor_code }}</strong>)
            is registered under a category that matches this tender. If you believe this is an error,
            please contact the administrator.
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} VMU Vendor Management System &nbsp;·&nbsp;
        <a href="{{ url('/vendor/tenders') }}">View All Tenders</a>
    </div>

</div>
</body>
</html>
