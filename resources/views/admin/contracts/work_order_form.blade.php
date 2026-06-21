@extends('layouts.dashboard')

@section('title', 'Generate Work / Purchase Order')
@section('header', 'Generate Work / Purchase Order')

@section('content')
<div class="max-w-5xl mx-auto">

  {{-- Back button --}}
  <div class="mb-4">
    <a href="{{ url()->previous() }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
      <i class="fas fa-arrow-left"></i> Back to Contract
    </a>
  </div>

  {{-- Contract summary card --}}
  <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 bg-gradient-to-r from-blue-700 to-indigo-700 flex justify-between items-start">
      <div>
        <h3 class="text-white font-semibold text-lg flex items-center gap-2">
          <i class="fas fa-file-contract"></i>
          {{ $contract->contract_number }}
        </h3>
        <p class="text-blue-200 text-sm mt-1">{{ $contract->tender->title ?? '—' }}</p>
      </div>
      <div class="text-right text-sm text-blue-200">
        <div>Vendor: <span class="text-white font-semibold">{{ $contract->vendor->company_name }}</span></div>
        <div>Value: <span class="text-white font-semibold">₹ {{ number_format($contract->contract_value, 2) }}</span></div>
      </div>
    </div>
  </div>

  {{-- Flash messages --}}
  @if(session('success'))
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
  </div>
  @endif

  @php
      $downloadRoute = str_starts_with(request()->route()->getName(), 'super-admin.')
          ? route('super-admin.contracts.work-order.download', $contract->id)
          : route('admin.contracts.work-order.download', $contract->id);
  @endphp
  <form id="orderForm" method="POST" action="{{ $downloadRoute }}">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      {{-- LEFT / MAIN COLUMN --}}
      <div class="lg:col-span-2 space-y-6">

        {{-- Order Type + Header Info --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h4 class="text-gray-800 font-semibold text-base mb-4 flex items-center gap-2">
            <i class="fas fa-clipboard-list text-blue-600"></i> Order Information
          </h4>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Order Type <span class="text-red-500">*</span></label>
              <select name="order_type" id="orderType" required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                onchange="updateTerms()">
                <option value="work_order" {{ old('order_type', $contract->order_type) === 'work_order' ? 'selected' : '' }}>Work Order</option>
                <option value="purchase_order" {{ old('order_type', $contract->order_type) === 'purchase_order' ? 'selected' : '' }}>Purchase Order</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Order Date <span class="text-red-500">*</span></label>
              <input type="date" name="order_date" required
                value="{{ old('order_date', $contract->order_date ? $contract->order_date->format('Y-m-d') : date('Y-m-d')) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
          </div>

          {{-- Order Number --}}
          <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <label class="block text-xs font-semibold text-gray-600 mb-1">
              PO / WO Number
              <span class="ml-1 text-gray-400 font-normal">(leave blank to auto-generate in VMRF-DU/###/YYYY-YY format)</span>
            </label>
            <input type="text" name="custom_order_number"
              value="{{ old('custom_order_number', $contract->order_number) }}"
              placeholder="e.g. VMRF-DU/001/2026-27 or type a custom number"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white">
            <p class="text-xs text-blue-600 mt-1">
              <i class="fas fa-info-circle mr-1"></i>
              You may type any number format (PO-2026-001, WO/DEPT/001, etc.) or leave blank for auto-generated number.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">VMRFDU Ref.</label>
              <input type="text" name="vmrfdu_ref" placeholder="e.g. 3204 / 22.05.2026"
                value="{{ old('vmrfdu_ref', $contract->vmrfdu_ref) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Supplier Ref.</label>
              <input type="text" name="supplier_ref" placeholder="e.g. Quote 02.06.2026"
                value="{{ old('supplier_ref', $contract->supplier_ref) }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
          </div>
        </div>

        {{-- Line Items --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-gray-800 font-semibold text-base flex items-center gap-2">
              <i class="fas fa-list text-green-600"></i> Line Items
            </h4>
            <button type="button" onclick="addRow()"
              class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded-lg flex items-center gap-1 transition">
              <i class="fas fa-plus"></i> Add Row
            </button>
          </div>

          <p class="text-xs text-gray-500 mb-3 flex items-center gap-1">
            <i class="fas fa-info-circle text-blue-400"></i>
            Set a <strong>Delivery By</strong> date per item to split the order into multiple delivery batches. Items with the same delivery date are grouped together in the PDF.
          </p>
          <div class="overflow-x-auto">
            <table class="w-full text-sm" id="itemsTable">
              <thead>
                <tr class="bg-gray-100 text-gray-600 text-xs uppercase">
                  <th class="px-2 py-2 text-left w-6">#</th>
                  <th class="px-2 py-2 text-left">Description</th>
                  <th class="px-2 py-2 text-center w-16">Qty</th>
                  <th class="px-2 py-2 text-center w-20">UOM</th>
                  <th class="px-2 py-2 text-right w-24">Rate (₹)</th>
                  <th class="px-2 py-2 text-right w-24">Amount (₹)</th>
                  <th class="px-2 py-2 text-center w-32">Delivery By</th>
                  <th class="px-2 py-2 w-8"></th>
                </tr>
              </thead>
              <tbody id="itemsBody">
                {{-- rows injected by JS --}}
              </tbody>
            </table>
          </div>

          {{-- Totals --}}
          <div class="mt-4 flex justify-end">
            <div class="w-72 space-y-2">
              <div class="flex justify-between text-sm text-gray-600 py-1 border-b">
                <span>Subtotal</span>
                <span id="subtotalDisplay" class="font-medium">₹ 0.00</span>
              </div>
              <div class="flex justify-between items-center text-sm py-1 border-b">
                <span class="text-gray-600">Discount (₹)</span>
                <input type="number" name="order_discount" id="discountInput" min="0" step="0.01"
                  value="{{ old('order_discount', $contract->order_discount ?? 0) }}"
                  class="w-28 border border-gray-300 rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-400"
                  oninput="calcTotals()">
              </div>
              <div class="flex justify-between items-center text-sm py-1 border-b">
                <span class="text-gray-600">GST (%)</span>
                <input type="number" name="gst_rate" id="gstInput" min="0" max="100" step="0.01"
                  value="{{ old('gst_rate', $contract->gst_rate ?? 18) }}"
                  class="w-28 border border-gray-300 rounded px-2 py-1 text-right text-sm focus:ring-1 focus:ring-blue-400"
                  oninput="calcTotals()">
              </div>
              <div class="flex justify-between text-sm text-gray-600 py-1 border-b">
                <span>GST Amount</span>
                <span id="gstDisplay" class="font-medium">₹ 0.00</span>
              </div>
              <div class="flex justify-between font-bold text-base py-2 bg-blue-50 rounded-lg px-3 text-blue-800">
                <span>NETT TOTAL</span>
                <span id="nettDisplay">₹ 0.00</span>
              </div>
            </div>
          </div>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h4 class="text-gray-800 font-semibold text-base mb-3 flex items-center gap-2">
            <i class="fas fa-sticky-note text-yellow-500"></i> Additional Notes
          </h4>
          <textarea name="order_notes" rows="3" placeholder="Any additional instructions or notes..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('order_notes', $contract->order_notes) }}</textarea>
        </div>

      </div>{{-- end main col --}}

      {{-- RIGHT COLUMN: Terms & Conditions --}}
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
          <h4 class="text-gray-800 font-semibold text-base mb-3 flex items-center gap-2">
            <i class="fas fa-gavel text-purple-600"></i> Terms &amp; Conditions
          </h4>
          <p class="text-xs text-gray-500 mb-3">Check to include preset clauses. You can edit the text inline.</p>

          <div id="tcContainer" class="space-y-3">
            {{-- populated by JS based on order type --}}
          </div>
        </div>
      </div>

    </div>{{-- end grid --}}

    {{-- ACTION BUTTONS --}}
    <div class="flex gap-3 mt-6 justify-end items-center">
      <p class="text-xs text-gray-400 flex items-center gap-1">
        <i class="fas fa-info-circle"></i> Email is automatically sent to the vendor when you download the PDF.
      </p>
      @if($contract->order_items)
      <button type="button" id="resendEmailBtn"
        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg font-medium text-sm flex items-center gap-2 transition border border-gray-300">
        <i class="fas fa-redo"></i> Resend Email
      </button>
      @endif
      <button type="submit"
        class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-2.5 rounded-lg font-medium text-sm flex items-center gap-2 transition">
        <i class="fas fa-file-pdf"></i> Generate &amp; Download PDF
      </button>
    </div>

  </form>

  {{-- Send Email Result Toast --}}
  <div id="emailToast" class="fixed bottom-6 right-6 hidden z-50">
    <div id="emailToastContent" class="px-5 py-3 rounded-lg shadow-lg text-sm font-medium text-white flex items-center gap-2"></div>
  </div>

</div>

@push('scripts')
<script>
const presets = @json($presets);
const existingItems = @json($contract->order_items ?? []);
const existingTerms = @json($contract->order_terms ?? []);
@php
    $isSuperAdmin = str_starts_with(request()->route()->getName(), 'super-admin.');
    $emailRoute   = $isSuperAdmin ? 'super-admin.contracts.work-order.email' : 'admin.contracts.work-order.email';
@endphp
const sendEmailUrl = "{{ route($emailRoute, $contract->id) }}";
const csrfToken = "{{ csrf_token() }}";
const orderAlreadySaved = {{ $contract->order_items ? 'true' : 'false' }};

let rowCount = 0;

function addRow(desc = '', qty = '', uom = '', rate = '', amount = '', deliveryDate = '') {
  rowCount++;
  const idx = rowCount;
  const tr = document.createElement('tr');
  tr.className = 'border-b border-gray-100 hover:bg-gray-50';
  tr.id = 'row_' + idx;
  tr.innerHTML = `
    <td class="px-2 py-1 text-gray-400 text-xs">${idx}</td>
    <td class="px-1 py-1">
      <input type="text" name="items[${idx}][description]" value="${desc}" required placeholder="Item / Work description"
        class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-400 focus:border-transparent">
    </td>
    <td class="px-1 py-1">
      <input type="number" name="items[${idx}][qty]" value="${qty}" min="0" step="0.01" required
        class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-center focus:ring-1 focus:ring-blue-400 rowQty"
        oninput="calcRow(${idx})">
    </td>
    <td class="px-1 py-1">
      <input type="text" name="items[${idx}][uom]" value="${uom}" placeholder="Nos/Sqft/…" required
        class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-center focus:ring-1 focus:ring-blue-400">
    </td>
    <td class="px-1 py-1">
      <input type="number" name="items[${idx}][rate]" value="${rate}" min="0" step="0.01" required
        class="w-full border border-gray-200 rounded px-2 py-1 text-sm text-right focus:ring-1 focus:ring-blue-400 rowRate"
        oninput="calcRow(${idx})">
    </td>
    <td class="px-1 py-1">
      <input type="number" name="items[${idx}][amount]" id="amt_${idx}" value="${amount}" readonly
        class="w-full bg-gray-50 border border-gray-200 rounded px-2 py-1 text-sm text-right font-medium text-blue-700">
    </td>
    <td class="px-1 py-1">
      <input type="date" name="items[${idx}][delivery_date]" value="${deliveryDate}"
        title="Leave blank if no specific delivery date"
        class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-center focus:ring-1 focus:ring-orange-400"
        style="min-width:110px">
    </td>
    <td class="px-1 py-1">
      <button type="button" onclick="removeRow(${idx})" class="text-red-400 hover:text-red-600 p-1">
        <i class="fas fa-times text-xs"></i>
      </button>
    </td>
  `;
  document.getElementById('itemsBody').appendChild(tr);
  calcTotals();
}

function removeRow(idx) {
  const el = document.getElementById('row_' + idx);
  if (el) el.remove();
  calcTotals();
}

function calcRow(idx) {
  const qty  = parseFloat(document.querySelector(`[name="items[${idx}][qty]"]`)?.value) || 0;
  const rate = parseFloat(document.querySelector(`[name="items[${idx}][rate]"]`)?.value) || 0;
  const amt  = (qty * rate).toFixed(2);
  const amtEl = document.getElementById('amt_' + idx);
  if (amtEl) amtEl.value = amt;
  calcTotals();
}

function calcTotals() {
  let subtotal = 0;
  document.querySelectorAll('[id^="amt_"]').forEach(el => {
    subtotal += parseFloat(el.value) || 0;
  });
  const discount = parseFloat(document.getElementById('discountInput').value) || 0;
  const gstRate  = parseFloat(document.getElementById('gstInput').value) || 0;
  const afterDisc = subtotal - discount;
  const gstAmt    = afterDisc * gstRate / 100;
  const nett      = afterDisc + gstAmt;

  document.getElementById('subtotalDisplay').textContent = '₹ ' + subtotal.toFixed(2);
  document.getElementById('gstDisplay').textContent      = '₹ ' + gstAmt.toFixed(2);
  document.getElementById('nettDisplay').textContent     = '₹ ' + nett.toFixed(2);
}

function updateTerms() {
  const type = document.getElementById('orderType').value;
  const container = document.getElementById('tcContainer');
  container.innerHTML = '';
  const terms = presets[type] || {};

  Object.entries(terms).forEach(([head, body]) => {
    const savedVal = existingTerms[head] !== undefined ? existingTerms[head] : body;
    const isChecked = existingTerms && Object.keys(existingTerms).length > 0
      ? (existingTerms[head] !== undefined && existingTerms[head] !== '')
      : true; // default: all selected

    const div = document.createElement('div');
    div.className = 'border border-gray-200 rounded-lg p-3';
    div.innerHTML = `
      <label class="flex items-start gap-2 cursor-pointer mb-1">
        <input type="checkbox" name="terms_check[${head}]" value="1" ${isChecked ? 'checked' : ''}
          class="mt-0.5 rounded" onchange="toggleTerm('${head}', this.checked)">
        <span class="text-xs font-semibold text-gray-700">${head}</span>
      </label>
      <textarea name="terms[${head}]" id="tc_${head.replace(/\s/g,'_')}" rows="2"
        class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-purple-400 ${!isChecked ? 'opacity-40' : ''}"
        ${!isChecked ? 'disabled' : ''}>${savedVal}</textarea>
    `;
    container.appendChild(div);
  });
}

function toggleTerm(head, checked) {
  const ta = document.getElementById('tc_' + head.replace(/\s/g,'_'));
  if (!ta) return;
  ta.disabled = !checked;
  ta.classList.toggle('opacity-40', !checked);
}

function sendEmail(btn) {
  if (!btn) return;
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
  fetch(sendEmailUrl, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({})
  })
  .then(r => r.json())
  .then(data => {
    const toast = document.getElementById('emailToast');
    const content = document.getElementById('emailToastContent');
    content.className = 'px-5 py-3 rounded-lg shadow-lg text-sm font-medium text-white flex items-center gap-2 ' + (data.success ? 'bg-green-600' : 'bg-red-600');
    content.innerHTML = (data.success ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-times-circle"></i> ') + data.message;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 4000);
  })
  .catch(() => {})
  .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
}

// Init on load
window.addEventListener('DOMContentLoaded', () => {
  if (existingItems && existingItems.length > 0) {
    existingItems.forEach(item => addRow(item.description, item.qty, item.uom, item.rate, item.amount, item.delivery_date || ''));
  } else {
    addRow();
  }
  updateTerms();
  calcTotals();

  const resendBtn = document.getElementById('resendEmailBtn');
  if (resendBtn) resendBtn.addEventListener('click', () => sendEmail(resendBtn));
});
</script>
@endpush
@endsection
