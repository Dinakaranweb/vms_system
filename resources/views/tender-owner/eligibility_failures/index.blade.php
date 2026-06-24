@extends('layouts.dashboard')

@section('title', 'Eligibility Failures')
@section('header', 'Eligibility Failure Log')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header card --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-5 bg-gradient-to-r from-red-600 to-orange-600 flex justify-between items-center">
            <div>
                <h3 class="text-white font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-ban"></i> Eligibility Failure Log
                </h3>
                <p class="text-red-100 text-sm mt-0.5">Vendors who attempted to apply to your tenders but failed eligibility criteria</p>
            </div>
            <span id="totalBadge" class="bg-white bg-opacity-20 text-white text-sm font-semibold px-4 py-1.5 rounded-full">Loading…</span>
        </div>

        {{-- Filter bar --}}
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Filter by Tender</label>
                <select id="tenderFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400">
                    <option value="">All My Tenders</option>
                    @foreach($tenders as $t)
                    <option value="{{ $t->id }}" {{ $tenderId == $t->id ? 'selected' : '' }}>
                        {{ $t->tender_number }} — {{ Str::limit($t->title, 60) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Search Vendor</label>
                <input type="text" id="searchInput" placeholder="Company name or email…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-400">
            </div>
            <button onclick="loadData()" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition flex items-center gap-1">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button onclick="clearFilters()" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase border-b border-gray-200">
                        <th class="px-4 py-3 text-left w-12">#</th>
                        <th class="px-4 py-3 text-left">Vendor</th>
                        <th class="px-4 py-3 text-left">Tender</th>
                        <th class="px-4 py-3 text-left">Failed Questions</th>
                        <th class="px-4 py-3 text-center">Date &amp; Time</th>
                        <th class="px-4 py-3 text-center">IP Address</th>
                        <th class="px-4 py-3 text-center">Details</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-gray-300 mb-3 block"></i>
                            <span class="text-gray-400 text-sm">Loading…</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center" id="paginationBar">
            <div class="text-xs text-gray-500" id="paginationInfo"></div>
            <div class="flex gap-2" id="paginationBtns"></div>
        </div>
    </div>

    {{-- Detail modal --}}
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="px-6 py-4 bg-red-600 flex justify-between items-center rounded-t-xl">
                <h3 class="text-white font-semibold flex items-center gap-2"><i class="fas fa-info-circle"></i> Failure Details</h3>
                <button onclick="closeModal()" class="text-white hover:text-red-200 text-xl leading-none">&times;</button>
            </div>
            <div id="modalContent" class="p-6"></div>
        </div>
    </div>

</div>

<script>
let currentPage = 1;
let allFailures = [];
const dataApiUrl = "{{ route('tender-owner.eligibility-failures.data') }}";

function loadData(page = 1) {
    currentPage = page;
    const tenderId = document.getElementById('tenderFilter').value;
    const search   = document.getElementById('searchInput').value.trim();

    const url = new URL(dataApiUrl, window.location.origin);
    if (tenderId) url.searchParams.set('tender_id', tenderId);
    if (search)   url.searchParams.set('search', search);
    url.searchParams.set('page', page);

    document.getElementById('tableBody').innerHTML = `
        <tr><td colspan="7" class="py-12 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-gray-300 mb-2 block"></i>
            <span class="text-gray-400 text-sm">Loading…</span>
        </td></tr>`;

    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Server error');
            allFailures = data.data;
            renderTable(data);
        })
        .catch(err => {
            document.getElementById('tableBody').innerHTML = `
                <tr><td colspan="7" class="py-12 text-center text-red-400 text-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i>Failed to load data: ${err.message}
                </td></tr>`;
            document.getElementById('totalBadge').textContent = 'Error';
        });
}

function renderTable(data) {
    document.getElementById('totalBadge').textContent = data.total + ' Record(s)';
    const tbody = document.getElementById('tableBody');

    if (!data.data || data.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-16 text-center">
            <i class="fas fa-check-circle text-5xl text-green-200 mb-3 block"></i>
            <span class="text-gray-400 text-sm">No eligibility failures found.</span>
        </td></tr>`;
        document.getElementById('paginationInfo').textContent = '';
        document.getElementById('paginationBtns').innerHTML = '';
        return;
    }

    tbody.innerHTML = data.data.map((f, i) => {
        const failedArr = Array.isArray(f.failed_questions) ? f.failed_questions : [];
        const failedHtml = failedArr.slice(0, 2).map(q =>
            `<span class="inline-block px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full mr-1 mb-1">${escHtml(q)}</span>`
        ).join('') + (failedArr.length > 2 ? `<span class="text-xs text-gray-400">+${failedArr.length - 2} more</span>` : '');

        return `<tr class="border-b border-gray-50 hover:bg-red-50 transition">
            <td class="px-4 py-3 text-gray-400 text-xs">${((currentPage - 1) * 20) + i + 1}</td>
            <td class="px-4 py-3">
                <div class="font-medium text-gray-800">${escHtml(f.vendor?.company_name ?? '—')}</div>
                <div class="text-xs text-gray-400">${escHtml(f.vendor?.email ?? '')}</div>
            </td>
            <td class="px-4 py-3">
                <div class="font-medium text-gray-800">${escHtml(f.tender?.tender_number ?? '—')}</div>
                <div class="text-xs text-gray-400 max-w-xs truncate">${escHtml(f.tender?.title ?? '')}</div>
            </td>
            <td class="px-4 py-3">${failedHtml || '<span class="text-gray-400 text-xs">—</span>'}</td>
            <td class="px-4 py-3 text-center text-xs text-gray-500">${formatDate(f.created_at)}</td>
            <td class="px-4 py-3 text-center text-xs text-gray-500">${escHtml(f.ip_address ?? '—')}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="showDetail(${i})" class="px-3 py-1 bg-gray-100 text-gray-600 text-xs rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-eye mr-1"></i> View
                </button>
            </td>
        </tr>`;
    }).join('');

    // Pagination
    const start = (currentPage - 1) * 20 + 1;
    const end   = Math.min(currentPage * 20, data.total);
    document.getElementById('paginationInfo').textContent = `Showing ${start}–${end} of ${data.total}`;

    let btns = '';
    if (currentPage > 1) btns += `<button onclick="loadData(${currentPage - 1})" class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition"><i class="fas fa-chevron-left"></i></button>`;
    for (let p = Math.max(1, currentPage - 2); p <= Math.min(data.last_page, currentPage + 2); p++) {
        btns += `<button onclick="loadData(${p})" class="px-3 py-1.5 text-sm rounded-lg transition ${p === currentPage ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}">${p}</button>`;
    }
    if (currentPage < data.last_page) btns += `<button onclick="loadData(${currentPage + 1})" class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition"><i class="fas fa-chevron-right"></i></button>`;
    document.getElementById('paginationBtns').innerHTML = btns;
}

function showDetail(idx) {
    const f = allFailures[idx];
    if (!f) return;
    const failedArr = Array.isArray(f.failed_questions) ? f.failed_questions : [];
    const answers   = f.submitted_answers || {};

    let html = `
        <div class="mb-4 p-4 bg-red-50 rounded-lg border border-red-100">
            <h4 class="font-semibold text-red-800 mb-2 text-sm">Vendor Details</h4>
            <p class="text-sm"><strong>Company:</strong> ${escHtml(f.vendor?.company_name ?? '—')}</p>
            <p class="text-sm"><strong>Email:</strong> ${escHtml(f.vendor?.email ?? '—')}</p>
            <p class="text-sm"><strong>Phone:</strong> ${escHtml(f.vendor?.phone ?? '—')}</p>
        </div>
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
            <h4 class="font-semibold text-gray-800 mb-2 text-sm">Tender</h4>
            <p class="text-sm"><strong>Tender No.:</strong> ${escHtml(f.tender?.tender_number ?? '—')}</p>
            <p class="text-sm"><strong>Title:</strong> ${escHtml(f.tender?.title ?? '—')}</p>
        </div>
        <div class="mb-4">
            <h4 class="font-semibold text-red-700 mb-2 text-sm flex items-center gap-1">
                <i class="fas fa-ban text-xs"></i> Failed Eligibility Questions (${failedArr.length})
            </h4>
            <ul class="space-y-1">
                ${failedArr.map(q => `<li class="flex items-start gap-2 text-sm text-red-700">
                    <i class="fas fa-times-circle text-red-400 mt-0.5 flex-shrink-0"></i>${escHtml(q)}</li>`).join('')}
            </ul>
        </div>`;

    if (Object.keys(answers).length) {
        html += `<div>
            <h4 class="font-semibold text-gray-700 mb-2 text-sm">All Submitted Answers</h4>
            <div class="space-y-2">
            ${Object.values(answers).map(a => `
                <div class="p-3 bg-gray-50 rounded-lg text-sm">
                    <p class="text-gray-600 font-medium">${escHtml(a.question ?? '')}</p>
                    <p class="text-gray-800 mt-0.5">${Array.isArray(a.answer) ? escHtml(a.answer.join(', ')) : escHtml(String(a.answer ?? '—'))}</p>
                </div>`).join('')}
            </div></div>`;
    }

    document.getElementById('modalContent').innerHTML = html;
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

function clearFilters() {
    document.getElementById('tenderFilter').value = '';
    document.getElementById('searchInput').value = '';
    loadData();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(dt) {
    if (!dt) return '—';
    const d = new Date(dt);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) + '<br>' + d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });
}

document.getElementById('searchInput').addEventListener('keydown', e => { if (e.key === 'Enter') loadData(); });

document.getElementById('detailModal').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
});

window.addEventListener('DOMContentLoaded', loadData);
</script>
@endsection
