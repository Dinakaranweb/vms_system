<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:9.5px; color:#1a1a2e; background:#fff; }
.page { padding:22px 30px 18px; }

/* ── HEADER ── */
.hdr { width:100%; border-collapse:collapse; margin-bottom:0; }
.hdr td { vertical-align:middle; }
.hdr .logo-cell  { width:72px; padding-right:14px; }
.hdr .info-cell  { vertical-align:middle; }
.hdr .title-cell { width:165px; text-align:right; vertical-align:middle; }
.org-name  { font-size:15px; font-weight:bold; color:#003580; letter-spacing:.4px; }
.org-full  { font-size:7.5px; color:#555; margin-top:1px; }
.org-addr  { font-size:7px; color:#666; margin-top:3px; line-height:1.5; }
.doc-badge { display:inline-block; background:#003580; color:#fff; font-size:13px;
             font-weight:bold; letter-spacing:1.5px; padding:5px 14px 5px 14px;
             border-radius:4px; text-transform:uppercase; }
.doc-sub   { font-size:7px; color:#888; margin-top:3px; text-align:right; }

.accent-bar { height:3px; background:linear-gradient(90deg,#003580,#0072ce,#e8a000); margin:8px 0 10px; border-radius:2px; }

/* ── META TABLE ── */
.meta { width:100%; border-collapse:collapse; margin-bottom:10px; }
.meta td { padding:4px 8px; font-size:8.5px; border:1px solid #d1d9e6; }
.meta .lbl { background:#eef3fb; font-weight:bold; color:#003580; width:17%; }
.meta .val { background:#fff; color:#222; }

/* ── PARTY BOXES ── */
.parties { width:100%; border-collapse:collapse; margin-bottom:10px; }
.parties td { vertical-align:top; width:50%; padding:0 5px 0 0; }
.parties td:last-child { padding:0 0 0 5px; }
.party-box { border:1.5px solid #ccd6e8; border-radius:4px; padding:8px 10px; }
.party-box .ph { font-size:7.5px; font-weight:bold; color:#003580; text-transform:uppercase;
                 letter-spacing:.6px; border-bottom:1px solid #dde5f2; padding-bottom:4px; margin-bottom:6px; }
.party-box p  { font-size:8.5px; line-height:1.65; color:#333; }
.party-box strong { color:#111; }

/* ── SUBJECT BAR ── */
.subject { background:#f0f5ff; border-left:3px solid #003580; padding:6px 10px; margin-bottom:10px;
           font-size:8.5px; border-radius:0 3px 3px 0; }
.subject strong { color:#003580; }

/* ── ITEMS TABLE ── */
.items-wrap { margin-bottom:8px; }
.section-head { background:#003580; color:#fff; font-size:8px; font-weight:bold;
                text-transform:uppercase; letter-spacing:.6px; padding:4px 8px;
                border-radius:3px 3px 0 0; }
.delivery-head { background:#e8f0fe; color:#003580; font-size:8px; font-weight:bold;
                 padding:3px 8px; border-top:1px solid #c5d3f0; border-left:1px solid #c5d3f0;
                 border-right:1px solid #c5d3f0; }
.delivery-head span { font-size:7.5px; font-weight:normal; color:#555; margin-left:6px; }
.items { width:100%; border-collapse:collapse; }
.items th { background:#003580; color:#fff; padding:6px 8px; text-align:left; font-size:8px;
            font-weight:bold; letter-spacing:.3px; }
.items th.r, .items td.r { text-align:right; }
.items th.c, .items td.c { text-align:center; }
.items td { padding:5px 8px; font-size:8.5px; border-bottom:1px solid #e8eef8; vertical-align:top; }
.items tr:nth-child(even) td { background:#f8faff; }
.items .sub-row td { font-weight:bold; background:#eef3fb; border-top:1.5px solid #c5d3f0; color:#003580; }
.items .disc-row td { background:#fff8e8; color:#7a5800; border-top:1px solid #f0dfa0; font-weight:bold; }
.items .gst-row  td { background:#eef3fb; color:#003580; font-weight:bold; }
.items .grand-row td { background:#003580; color:#fff; font-weight:bold; font-size:10px; }

/* ── AMOUNT WORDS ── */
.words-box { border:1px solid #c5d3f0; background:#f0f5ff; padding:6px 10px; margin:6px 0 10px;
             font-size:8.5px; border-radius:3px; }
.words-box strong { color:#003580; }

/* ── SECTION TITLE ── */
.sec-title { font-size:9px; font-weight:bold; color:#003580; margin:12px 0 5px;
             padding-bottom:3px; border-bottom:1.5px solid #003580; text-transform:uppercase; letter-spacing:.5px; }

/* ── T&C ── */
.tc { width:100%; border-collapse:collapse; }
.tc td { padding:5px 8px; font-size:8px; border:1px solid #dde5f2; vertical-align:top; }
.tc td:first-child { width:4%; font-weight:bold; background:#eef3fb; color:#003580; text-align:center; }
.tc td:nth-child(2) { width:22%; font-weight:bold; background:#f5f8ff; color:#222; }
.tc tr:nth-child(even) td { background:#fafbff; }
.tc tr:nth-child(even) td:first-child  { background:#e6ecf9; }
.tc tr:nth-child(even) td:nth-child(2) { background:#f0f4fc; }

/* ── NOTES ── */
.notes { margin:6px 0 10px; padding:7px 10px; border-left:3px solid #e8a000;
         background:#fffbf0; font-size:8.5px; border-radius:0 3px 3px 0; }

/* ── SIGNATURE ── */
.sig { width:100%; margin-top:28px; border-collapse:collapse; }
.sig td { text-align:center; vertical-align:bottom; padding:0 8px; }
.sig-line { border-top:1px solid #888; margin-top:36px; padding-top:5px;
            font-size:8px; font-weight:bold; color:#333; }
.sig-sub  { font-size:7.5px; color:#777; margin-top:2px; }

/* ── FOOTER ── */
.footer { margin-top:16px; border-top:2px solid #003580; padding-top:5px;
          display:table; width:100%; }
.footer-l { display:table-cell; font-size:7px; color:#999; vertical-align:middle; }
.footer-r { display:table-cell; text-align:right; font-size:7px; color:#003580; vertical-align:middle; font-weight:bold; }
</style>
</head>
<body>
<div class="page">

{{-- ── HEADER ── --}}
<table class="hdr">
  <tr>
    <td class="logo-cell">
      <img src="{{ public_path('img/logo1.jpg') }}" alt="VMRF-DU" style="height:60px;width:auto;">
    </td>
    <td class="info-cell">
      <div class="org-name">VMRF-DU</div>
      <div class="org-full">Vinayaka Missions Research Foundation – Deemed University</div>
      <div class="org-addr">
        No. 160, Sankari Main Road, Ariyanoor, Salem – 636 308, Tamil Nadu, India<br>
        Ph: 0427-3912200 &nbsp;|&nbsp; procurement@vmu.edu.in &nbsp;|&nbsp; www.vmu.edu.in
      </div>
    </td>
    <td class="title-cell">
      <div class="doc-badge">{{ strtoupper(str_replace('_',' ',$order_type)) }}</div>
      <div class="doc-sub">VMRF-DU Procurement Document</div>
    </td>
  </tr>
</table>

<div class="accent-bar"></div>

{{-- ── META INFO ── --}}
<table class="meta">
  <tr>
    <td class="lbl">Order No.</td>
    <td class="val">{{ $order_number }}</td>
    <td class="lbl">Date</td>
    <td class="val">{{ \Carbon\Carbon::parse($order_date)->format('d M Y') }}</td>
    <td class="lbl">VMRFDU Ref.</td>
    <td class="val">{{ $vmrfdu_ref ?: '–' }}</td>
  </tr>
  <tr>
    <td class="lbl">Supplier Ref.</td>
    <td class="val">{{ $supplier_ref ?: '–' }}</td>
    <td class="lbl">Tender No.</td>
    <td class="val">{{ $contract->tender->tender_number ?? '–' }}</td>
    <td class="lbl">Contract No.</td>
    <td class="val">{{ $contract->contract_number }}</td>
  </tr>
</table>

{{-- ── PARTIES ── --}}
<table class="parties">
  <tr>
    <td>
      <div class="party-box">
        <div class="ph">M/s – Supplier / Contractor</div>
        <p>
          <strong>{{ $contract->vendor->company_name }}</strong><br>
          {{ $contract->vendor->address ?? '' }}<br>
          @if($contract->vendor->gst_number)GST: {{ $contract->vendor->gst_number }}<br>@endif
          @if($contract->vendor->pan_number)PAN: {{ $contract->vendor->pan_number }}<br>@endif
          Ph: {{ $contract->vendor->phone ?? '–' }}&nbsp;&nbsp;|&nbsp;&nbsp;{{ $contract->vendor->email }}
        </p>
      </div>
    </td>
    <td>
      <div class="party-box">
        <div class="ph">Invoice To</div>
        <p>
          <strong>VMRF-DU – Procurement Department</strong><br>
          No. 160, Sankari Main Road, Ariyanoor,<br>
          Salem – 636 308, Tamil Nadu, India<br>
          GSTIN: 33AAACV0397P1Z7&nbsp;&nbsp;|&nbsp;&nbsp;Ph: 0427-3912200
        </p>
      </div>
    </td>
  </tr>
</table>

{{-- ── SUBJECT ── --}}
<div class="subject">
  <strong>Subject / Work:</strong>&nbsp; {{ $contract->tender->title ?? '–' }}
</div>

{{-- ── LINE ITEMS — grouped by delivery date if present ── --}}
@php
  $hasDelivery = collect($items)->contains(fn($i) => !empty($i['delivery_date']));

  if ($hasDelivery) {
      // Group items by delivery_date; null/empty = "On Demand / As Required"
      $groups = [];
      foreach ($items as $item) {
          $key = !empty($item['delivery_date']) ? $item['delivery_date'] : '__ondemand__';
          $groups[$key][] = $item;
      }
      ksort($groups);
  }
@endphp

<div class="items-wrap">
  <div class="section-head">{{ $order_type === 'purchase_order' ? 'Schedule of Materials' : 'Scope of Work' }}</div>

  @if($hasDelivery)
    {{-- Grouped by delivery batch --}}
    @php $partNo = 1; $globalIdx = 0; @endphp
    @foreach($groups as $dateKey => $groupItems)
    @php
      $delivLabel = $dateKey === '__ondemand__'
          ? 'On Demand / As Required'
          : 'Delivery Part ' . $partNo . ' — Deliver by: ' . \Carbon\Carbon::parse($dateKey)->format('d M Y');
      $partNo++;
    @endphp
    <div class="delivery-head">
      <i>{{ $delivLabel }}</i>
    </div>
    <table class="items">
      <thead>
        <tr>
          <th class="c" style="width:5%">S.No</th>
          <th style="width:42%">Description</th>
          <th class="c" style="width:8%">Qty</th>
          <th class="c" style="width:8%">UOM</th>
          <th class="r" style="width:14%">Rate (₹)</th>
          <th class="r" style="width:14%">Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        @php $groupSubtotal = 0; @endphp
        @foreach($groupItems as $item)
        @php $globalIdx++; $groupSubtotal += (float)$item['amount']; @endphp
        <tr>
          <td class="c">{{ $globalIdx }}</td>
          <td>{{ $item['description'] }}</td>
          <td class="c">{{ $item['qty'] }}</td>
          <td class="c">{{ $item['uom'] }}</td>
          <td class="r">{{ number_format((float)$item['rate'], 2) }}</td>
          <td class="r">{{ number_format((float)$item['amount'], 2) }}</td>
        </tr>
        @endforeach
        <tr>
          <td colspan="5" class="r" style="font-weight:bold;background:#eef3fb;color:#003580;border-top:1px solid #c5d3f0;font-size:8px;">
            Part Sub-Total
          </td>
          <td class="r" style="font-weight:bold;background:#eef3fb;color:#003580;border-top:1px solid #c5d3f0;">
            {{ number_format($groupSubtotal, 2) }}
          </td>
        </tr>
      </tbody>
    </table>
    @endforeach

    {{-- Grand totals table --}}
    <table class="items" style="margin-top:4px;">
      <tfoot>
        <tr class="sub-row">
          <td colspan="5" class="r" style="width:86%">Sub Total</td>
          <td class="r" style="width:14%">{{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($discount > 0)
        <tr class="disc-row">
          <td colspan="5" class="r">Less: Discount</td>
          <td class="r">– {{ number_format($discount, 2) }}</td>
        </tr>
        @endif
        <tr class="gst-row">
          <td colspan="5" class="r">GST @ {{ $gst_rate }}%</td>
          <td class="r">{{ number_format($gst_amount, 2) }}</td>
        </tr>
        <tr class="grand-row">
          <td colspan="5" class="r">NETT TOTAL</td>
          <td class="r">Rs. {{ number_format($nett_total, 2) }}</td>
        </tr>
      </tfoot>
    </table>

  @else
    {{-- No delivery grouping — standard flat table --}}
    <table class="items">
      <thead>
        <tr>
          <th class="c" style="width:5%">S.No</th>
          <th style="width:40%">Description</th>
          <th class="c" style="width:8%">Qty</th>
          <th class="c" style="width:8%">UOM</th>
          <th class="r" style="width:13%">Rate (₹)</th>
          <th class="r" style="width:13%">Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $i => $item)
        <tr>
          <td class="c">{{ $i+1 }}</td>
          <td>{{ $item['description'] }}</td>
          <td class="c">{{ $item['qty'] }}</td>
          <td class="c">{{ $item['uom'] }}</td>
          <td class="r">{{ number_format((float)$item['rate'], 2) }}</td>
          <td class="r">{{ number_format((float)$item['amount'], 2) }}</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="sub-row">
          <td colspan="5" class="r">Sub Total</td>
          <td class="r">{{ number_format($subtotal, 2) }}</td>
        </tr>
        @if($discount > 0)
        <tr class="disc-row">
          <td colspan="5" class="r">Less: Discount</td>
          <td class="r">– {{ number_format($discount, 2) }}</td>
        </tr>
        @endif
        <tr class="gst-row">
          <td colspan="5" class="r">GST @ {{ $gst_rate }}%</td>
          <td class="r">{{ number_format($gst_amount, 2) }}</td>
        </tr>
        <tr class="grand-row">
          <td colspan="5" class="r">NETT TOTAL</td>
          <td class="r">Rs. {{ number_format($nett_total, 2) }}</td>
        </tr>
      </tfoot>
    </table>
  @endif
</div>

{{-- ── AMOUNT IN WORDS ── --}}
<div class="words-box">
  <strong>Total Amount in Words:</strong>&nbsp; {{ $amount_words }}
</div>

{{-- ── DELIVERY SCHEDULE SUMMARY (if split) ── --}}
@if($hasDelivery)
<div class="sec-title">Delivery Schedule</div>
<table class="tc" style="margin-bottom:10px;">
  <tr>
    <td style="width:4%;text-align:center;font-weight:bold;background:#eef3fb;color:#003580;">Pt.</td>
    <td style="width:22%;font-weight:bold;background:#f5f8ff;">Deliver By</td>
    <td style="font-weight:bold;background:#f5f8ff;">Items</td>
    <td style="width:14%;text-align:right;font-weight:bold;background:#f5f8ff;">Part Value (₹)</td>
  </tr>
  @php $pn = 1; @endphp
  @foreach($groups as $dateKey => $groupItems)
  @php
    $ptLabel = $dateKey === '__ondemand__' ? 'On Demand' : \Carbon\Carbon::parse($dateKey)->format('d M Y');
    $ptTotal = collect($groupItems)->sum(fn($i) => (float)$i['amount']);
  @endphp
  <tr>
    <td style="text-align:center;background:#eef3fb;color:#003580;font-weight:bold;">{{ $dateKey === '__ondemand__' ? '—' : $pn++ }}</td>
    <td>{{ $ptLabel }}</td>
    <td style="font-size:8px;color:#444;">{{ collect($groupItems)->pluck('description')->map(fn($d) => Str::limit($d,40))->implode('; ') }}</td>
    <td style="text-align:right;font-weight:bold;">{{ number_format($ptTotal, 2) }}</td>
  </tr>
  @endforeach
</table>
@endif

{{-- ── TERMS & CONDITIONS ── --}}
@if(!empty($terms))
<div class="sec-title">Terms &amp; Conditions</div>
<table class="tc" style="margin-bottom:10px;">
  @php $sno = 1; @endphp
  @foreach($terms as $head => $body)
  @if(!empty(trim($body)))
  <tr>
    <td>{{ $sno++ }}</td>
    <td>{{ $head }}</td>
    <td>{{ $body }}</td>
  </tr>
  @endif
  @endforeach
</table>
@endif

{{-- ── NOTES ── --}}
@if(!empty($order_notes))
<div class="sec-title">Notes</div>
<div class="notes">{{ $order_notes }}</div>
@endif

{{-- ── SIGNATURE BLOCK ── --}}
<table class="sig">
  <tr>
    <td>
      <div class="sig-line">Supplier / Contractor</div>
      <div class="sig-sub">Authorised Signature &amp; Seal</div>
    </td>
    <td>
      <div class="sig-line">Prepared By</div>
      <div class="sig-sub">Procurement Department, VMRF-DU</div>
    </td>
    <td>
      <div class="sig-line">Approved By</div>
      <div class="sig-sub">Authorised Signatory, VMRF-DU</div>
    </td>
  </tr>
</table>

{{-- ── FOOTER ── --}}
<table class="footer" style="margin-top:16px;border-top:2px solid #003580;padding-top:5px;width:100%;border-collapse:collapse;">
  <tr>
    <td style="font-size:7px;color:#999;">This is a system-generated document. | VMRF-DU Procurement System</td>
    <td style="text-align:right;font-size:7px;color:#003580;font-weight:bold;">
      {{ $order_number }} | {{ \Carbon\Carbon::parse($order_date)->format('d M Y') }}
    </td>
  </tr>
</table>

</div>
</body>
</html>
