@extends('layouts.dashboard')

@section('title', 'Received Documents — ' . $tender->title)
@section('header', 'Record Received Documents')

@section('content')
<div class="max-w-5xl mx-auto space-y-4" id="rdRoot" data-scan-url="{{ route('purchase-committee.received-documents.scan', [$tender->id, 0]) }}">

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('purchase-committee.review.documents', $tender->id) }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Back to Shared Documents
        </a>
        <a href="{{ route('purchase-committee.review.applications', $tender->id) }}" class="text-sm text-indigo-600 border border-indigo-200 bg-indigo-50 px-4 py-2 rounded-lg hover:bg-indigo-100 inline-flex items-center gap-1.5">
            <i class="fas fa-list text-xs"></i> Applications
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
        @foreach($errors->all() as $error)<p><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</p>@endforeach
    </div>
    @endif

    {{-- Tender header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-emerald-700 to-teal-700">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-white font-semibold truncate">{{ $tender->title }}</h3>
                    <p class="text-emerald-100 text-xs mt-0.5">
                        {{ $tender->tender_number }} · {{ $tender->getSubmissionModeLabel() }}
                        @if($tender->bid_opening_at) · Opened {{ $tender->bid_opening_at->format('d M Y, h:i A') }} @endif
                    </p>
                </div>
                <span class="bg-white/15 text-white text-xs px-2.5 py-1 rounded-full border border-white/25 flex-shrink-0">
                    {{ $stats['files'] }} file(s) recorded
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-100 border-b border-gray-100">
            @foreach([
                ['Applications', $stats['applications'], 'fa-file-signature', 'text-indigo-500'],
                ['Documents recorded', $stats['files'], 'fa-folder-open', 'text-emerald-500'],
                ['Docs pending', $stats['pending'], 'fa-hourglass-half', 'text-amber-500'],
                ['Bid values missing', $stats['bids_missing'] ?? 0, 'fa-indian-rupee-sign', 'text-amber-500'],
            ] as [$label, $value, $icon, $colour])
            <div class="px-4 py-3 text-center">
                <i class="fas {{ $icon }} {{ $colour }} text-sm"></i>
                <p class="text-xl font-bold text-gray-800 mt-1">{{ $value }}</p>
                <p class="text-xs text-gray-400">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        <div class="px-6 py-3 bg-emerald-50/60">
            <p class="text-xs text-emerald-900">
                <i class="fas fa-circle-info mr-1"></i>
                The tender is open, so the envelopes can be recorded. Upload what was received against the
                vendor who sent it, or use <strong>Scan</strong> to capture the pages with this device's camera,
                and enter the <strong>bid value</strong> read off each quote.
                Every change notifies the whole Purchase Committee — in their login and by email.
            </p>
        </div>
    </div>

    {{-- One card per application --}}
    @forelse($applications as $application)
    @php
        $vendor = $application->vendor;
        $docs   = $application->receivedDocuments;
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        {{-- Applied tender / vendor details --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 truncate">
                    {{ $vendor->company_name ?? $vendor->name ?? 'Unknown vendor' }}
                </p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-gray-500">
                    <span><i class="fas fa-hashtag text-gray-300 mr-1"></i>{{ $application->application_number }}</span>
                    <span class="{{ $application->bid_amount ? '' : 'text-amber-600 font-medium' }}">
                        <i class="fas fa-indian-rupee-sign text-gray-300 mr-1"></i>{{ $application->bid_amount ? number_format($application->bid_amount, 2) : 'Bid value not entered' }}
                    </span>
                    @if($application->submitted_at)
                    <span><i class="fas fa-calendar-check text-gray-300 mr-1"></i>{{ $application->submitted_at->format('d M Y') }}</span>
                    @endif
                    @if($vendor?->email)
                    <span class="truncate"><i class="fas fa-envelope text-gray-300 mr-1"></i>{{ $vendor->email }}</span>
                    @endif
                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ ucfirst($application->status) }}</span>
                </div>
            </div>
            <div class="text-right flex-shrink-0">
                @if($docs->isEmpty())
                <span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full">
                    <i class="fas fa-hourglass-half mr-1"></i>Nothing recorded
                </span>
                @else
                <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full">
                    <i class="fas fa-check mr-1"></i>{{ $docs->count() }} recorded
                </span>
                @endif
            </div>
        </div>

        {{-- Already recorded --}}
        @if($docs->isNotEmpty())
        <div class="divide-y divide-gray-50">
            @foreach($docs as $doc)
            @php $fileExists = $doc->fileExists(); @endphp
            <div class="px-6 py-2.5 flex items-center justify-between gap-3 hover:bg-gray-50 transition {{ $fileExists ? '' : 'bg-red-50/40' }}">
                <a href="{{ $doc->getUrl() }}" target="_blank" class="min-w-0 flex-1 flex items-center gap-2">
                    <i class="fas {{ $doc->isScan() ? 'fa-camera text-teal-400' : 'fa-paperclip text-gray-300' }} text-xs"></i>
                    <span class="text-sm {{ $fileExists ? 'text-gray-700' : 'text-red-700 line-through' }} truncate">{{ $doc->file_name }}</span>
                    @unless($fileExists)
                    <span class="text-[0.65rem] bg-red-100 text-red-700 px-1.5 py-0.5 rounded flex-shrink-0" title="The record exists but the file is no longer on disk — remove the row and upload it again">
                        File missing
                    </span>
                    @endunless
                    <span class="text-[0.65rem] px-1.5 py-0.5 rounded flex-shrink-0
                                 {{ $doc->isScan() ? 'bg-teal-50 text-teal-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $doc->isScan() ? 'Scanned' : 'Uploaded' }}
                    </span>
                    <span class="text-xs text-gray-400 flex-shrink-0 hidden sm:inline">
                        {{ $doc->getFormattedSize() }}
                        @if($doc->received_at) · {{ $doc->received_at->format('d M, h:i A') }} @endif
                        @if($doc->uploadedBy) · {{ $doc->uploadedBy->name }} @endif
                    </span>
                </a>
                <form method="POST" action="{{ route('purchase-committee.received-documents.destroy', [$tender->id, $doc->id]) }}"
                      onsubmit="return confirm('Remove this file? The file itself will be deleted too.')" class="flex-shrink-0">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 px-2" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Bid value read off the physical quote --}}
        <div class="px-6 py-4 border-t border-gray-100 {{ $application->bid_amount ? 'bg-white' : 'bg-amber-50/50' }}">
            <form method="POST" action="{{ route('purchase-committee.received-documents.bid', [$tender->id, $application->id]) }}"
                  class="flex flex-col sm:flex-row sm:items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1" for="bid-{{ $application->id }}">
                        <i class="fas fa-indian-rupee-sign text-gray-400 mr-1"></i>Bid Value from the Quote
                        @if(!$application->bid_amount)
                        <span class="ml-1 text-[0.65rem] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Not entered yet</span>
                        @endif
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                        <input type="number" step="0.01" min="0" name="bid_amount" id="bid-{{ $application->id }}"
                               value="{{ old('bid_amount', $application->bid_amount) }}"
                               placeholder="Enter the amount quoted in the envelope"
                               class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm
                                      focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        @if($tender->hasOnlineBids())
                            This vendor bid online as well — only change this if the postal quote differs.
                        @else
                            Read the figure off the quote in the envelope. Leave empty to clear it back to N/A.
                        @endif
                        @if($application->bid_recorded_at)
                            <span class="block mt-0.5 text-gray-500">
                                <i class="fas fa-user-pen mr-1"></i>Entered {{ $application->bid_recorded_at->format('d M Y, h:i A') }}@if($application->bidRecordedBy) by {{ $application->bidRecordedBy->name }}@endif
                            </span>
                        @endif
                    </p>
                </div>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 text-sm font-medium flex-shrink-0">
                    <i class="fas fa-floppy-disk mr-1.5"></i>Save Bid Value
                </button>
            </form>
        </div>

        {{-- Upload + scan --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <form method="POST" action="{{ route('purchase-committee.received-documents.store', [$tender->id, $application->id]) }}"
                  enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                    <input type="file" name="documents[]" multiple required
                           accept=".pdf,.jpg,.jpeg,.png,.webp,.tif,.tiff,.doc,.docx,.xls,.xlsx"
                           class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white
                                  file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs
                                  file:bg-emerald-50 file:text-emerald-700">
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 text-sm font-medium">
                            <i class="fas fa-upload mr-1.5"></i>Upload
                        </button>
                        <button type="button"
                                onclick="rdOpenScanner({{ $application->id }}, @js($vendor->company_name ?? $vendor->name ?? 'this vendor'))"
                                class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 text-sm font-medium">
                            <i class="fas fa-camera mr-1.5"></i>Scan
                        </button>
                    </div>
                </div>
                <input type="text" name="remarks" maxlength="1000" placeholder="Remarks (optional) — e.g. received by post on 12 Oct, envelope sealed"
                       class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                <p class="text-xs text-gray-400">
                    Multiple files at once. PDF, images, Word or Excel, up to 20 MB each. Saved under this vendor's own folder.
                </p>
            </form>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 py-16 text-center">
        <i class="fas fa-inbox text-5xl text-gray-200 block mb-3"></i>
        <p class="text-gray-400">No applications were received for this tender.</p>
    </div>
    @endforelse
</div>

{{-- ── Scanner interface ─────────────────────────────────────────────────── --}}
<div id="rdScanModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">

        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
            <div class="min-w-0">
                <h3 class="font-semibold text-gray-800 text-sm"><i class="fas fa-camera text-teal-600 mr-1.5"></i>Scan Received Documents</h3>
                <p class="text-xs text-gray-400 mt-0.5 truncate" id="rdScanTarget"></p>
            </div>
            <button type="button" onclick="rdCloseScanner()" class="text-gray-400 hover:text-gray-700 px-2" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            {{-- Live camera --}}
            <div id="rdCameraWrap" class="hidden">
                <div class="relative bg-black rounded-lg overflow-hidden" style="aspect-ratio:4/3;">
                    <video id="rdVideo" autoplay playsinline muted class="w-full h-full object-contain"></video>
                    <div class="absolute inset-4 border-2 border-dashed border-white/40 rounded pointer-events-none"></div>
                </div>
                <div class="flex items-center justify-between gap-3 mt-3">
                    <select id="rdCameraSelect" class="text-xs border border-gray-300 rounded-lg px-2 py-1.5 max-w-[55%]"></select>
                    <button type="button" onclick="rdCapture()" class="bg-teal-600 text-white px-5 py-2 rounded-lg hover:bg-teal-700 text-sm font-medium">
                        <i class="fas fa-circle-dot mr-1.5"></i>Capture Page
                    </button>
                </div>
            </div>

            {{-- Fallback when no camera is reachable --}}
            <div id="rdFallback" class="hidden">
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                    <p class="text-xs text-amber-900" id="rdFallbackMsg"></p>
                </div>
                <label class="mt-3 block border-2 border-dashed border-gray-300 rounded-lg px-4 py-6 text-center cursor-pointer hover:border-teal-400 hover:bg-teal-50/40 transition">
                    <i class="fas fa-file-arrow-up text-2xl text-gray-300 block mb-2"></i>
                    <span class="text-sm text-gray-600">Take a photo or pick the scan file</span>
                    <span class="block text-xs text-gray-400 mt-1">On a phone this opens the camera; on a desktop, your files</span>
                    <input type="file" id="rdFallbackInput" accept="image/*" capture="environment" multiple class="hidden"
                           onchange="rdFallbackPick(event)">
                </label>
            </div>

            {{-- Captured pages --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-600">Pages captured: <span id="rdPageCount">0</span></p>
                    <button type="button" onclick="rdClearPages()" id="rdClearBtn" class="text-xs text-red-500 hover:underline hidden">Clear all</button>
                </div>
                <div id="rdPages" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <p class="col-span-full text-xs text-gray-400 text-center py-6" id="rdPagesEmpty">
                        No pages yet — capture the first page above.
                    </p>
                </div>
            </div>

            <input type="text" id="rdScanRemarks" maxlength="1000" placeholder="Remarks (optional)"
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">

            <p id="rdScanError" class="hidden text-xs text-red-600"></p>
        </div>

        <div class="px-5 py-3.5 border-t border-gray-100 flex items-center justify-between gap-3 bg-gray-50">
            <p class="text-xs text-gray-400">Pages are saved in order under this vendor's folder.</p>
            <div class="flex items-center gap-2">
                <button type="button" onclick="rdCloseScanner()" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-900">Cancel</button>
                <button type="button" onclick="rdSavePages()" id="rdSaveBtn" disabled
                        class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-teal-700">
                    <i class="fas fa-floppy-disk mr-1.5"></i>Save Pages
                </button>
            </div>
        </div>
    </div>
</div>

<canvas id="rdCanvas" class="hidden"></canvas>

<script>
/* ── In-browser scan interface ─────────────────────────────────────────────
   A browser cannot drive a TWAIN/WIA desk scanner directly, so this captures
   pages from the device camera (the practical "scan this envelope" path). For a
   desk scanner, scan with its own software and use Upload, or the fallback
   picker below — both end up in the same place. */

const rdCsrf = document.querySelector('meta[name="csrf-token"]')?.content;
const rdScanUrlTemplate = document.getElementById('rdRoot').dataset.scanUrl;

let rdPages = [];          // data URLs, in capture order
let rdStream = null;
let rdApplicationId = null;

function rdScanUrlFor(applicationId) {
    /* The template was generated with a 0 placeholder for the application id. */
    return rdScanUrlTemplate.replace(/\/0\/scan$/, '/' + applicationId + '/scan');
}

async function rdOpenScanner(applicationId, vendorName) {
    rdApplicationId = applicationId;
    rdPages = [];
    rdRenderPages();
    rdSetError('');
    document.getElementById('rdScanTarget').textContent = vendorName;
    document.getElementById('rdScanRemarks').value = '';

    const modal = document.getElementById('rdScanModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    await rdStartCamera();
}

function rdCloseScanner() {
    rdStopCamera();
    const modal = document.getElementById('rdScanModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    rdPages = [];
    rdRenderPages();
}

async function rdStartCamera(deviceId) {
    const wrap = document.getElementById('rdCameraWrap');
    const fallback = document.getElementById('rdFallback');

    if (!navigator.mediaDevices?.getUserMedia) {
        return rdShowFallback('This browser cannot reach a camera. Take a photo on your phone, or scan with your scanner software and pick the file.');
    }

    try {
        rdStopCamera();
        rdStream = await navigator.mediaDevices.getUserMedia({
            video: deviceId
                ? { deviceId: { exact: deviceId } }
                : { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1440 } },
            audio: false,
        });

        document.getElementById('rdVideo').srcObject = rdStream;
        wrap.classList.remove('hidden');
        fallback.classList.add('hidden');
        await rdListCameras();
    } catch (e) {
        const why = e.name === 'NotAllowedError'
            ? 'Camera access was blocked for this site. Allow it in your browser, or use the picker below.'
            : 'No camera could be opened (' + (e.name || 'error') + '). Use the picker below instead.';
        rdShowFallback(why);
    }
}

async function rdListCameras() {
    const select = document.getElementById('rdCameraSelect');
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cams = devices.filter(d => d.kind === 'videoinput');
        if (cams.length < 2) { select.classList.add('hidden'); return; }

        select.classList.remove('hidden');
        const current = rdStream?.getVideoTracks()[0]?.getSettings()?.deviceId;
        select.innerHTML = cams.map((c, i) =>
            '<option value="' + c.deviceId + '"' + (c.deviceId === current ? ' selected' : '') + '>'
            + (c.label || ('Camera ' + (i + 1))) + '</option>').join('');
        select.onchange = () => rdStartCamera(select.value);
    } catch (e) { select.classList.add('hidden'); }
}

function rdStopCamera() {
    if (rdStream) {
        rdStream.getTracks().forEach(t => t.stop());
        rdStream = null;
    }
}

function rdShowFallback(message) {
    document.getElementById('rdCameraWrap').classList.add('hidden');
    document.getElementById('rdFallback').classList.remove('hidden');
    document.getElementById('rdFallbackMsg').textContent = message;
}

function rdCapture() {
    const video = document.getElementById('rdVideo');
    if (!video.videoWidth) return rdSetError('The camera is still warming up — try again in a moment.');

    const canvas = document.getElementById('rdCanvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    rdPushPage(canvas.toDataURL('image/jpeg', 0.9));
}

/* The fallback picker hands us real image files; downscale them the same way a
   capture would, so one oversized phone photo cannot blow the request limit. */
function rdFallbackPick(event) {
    const files = Array.from(event.target.files || []);
    files.forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                const max = 2000;
                const scale = Math.min(1, max / Math.max(img.width, img.height));
                const canvas = document.getElementById('rdCanvas');
                canvas.width = Math.round(img.width * scale);
                canvas.height = Math.round(img.height * scale);
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                rdPushPage(canvas.toDataURL('image/jpeg', 0.9));
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
    event.target.value = '';
}

function rdPushPage(dataUrl) {
    if (rdPages.length >= 40) return rdSetError('A single scan batch is limited to 40 pages. Save these, then start another.');
    rdPages.push(dataUrl);
    rdSetError('');
    rdRenderPages();
}

function rdRemovePage(index) {
    rdPages.splice(index, 1);
    rdRenderPages();
}

function rdClearPages() {
    rdPages = [];
    rdRenderPages();
}

function rdRenderPages() {
    const holder = document.getElementById('rdPages');
    const empty = document.getElementById('rdPagesEmpty');
    document.getElementById('rdPageCount').textContent = rdPages.length;
    document.getElementById('rdSaveBtn').disabled = rdPages.length === 0;
    document.getElementById('rdClearBtn').classList.toggle('hidden', rdPages.length === 0);

    holder.querySelectorAll('[data-page]').forEach(n => n.remove());
    empty.classList.toggle('hidden', rdPages.length > 0);

    rdPages.forEach((src, i) => {
        const cell = document.createElement('div');
        cell.setAttribute('data-page', i);
        cell.className = 'relative border border-gray-200 rounded-lg overflow-hidden group';
        cell.innerHTML =
            '<img src="' + src + '" class="w-full h-24 object-cover">' +
            '<span class="absolute top-1 left-1 bg-black/60 text-white text-[0.6rem] px-1.5 py-0.5 rounded">Page ' + (i + 1) + '</span>' +
            '<button type="button" class="absolute top-1 right-1 bg-red-600 text-white w-5 h-5 rounded-full text-[0.6rem] opacity-0 group-hover:opacity-100 transition" title="Remove page">&times;</button>';
        cell.querySelector('button').onclick = () => rdRemovePage(i);
        holder.appendChild(cell);
    });
}

function rdSetError(message) {
    const node = document.getElementById('rdScanError');
    node.textContent = message;
    node.classList.toggle('hidden', !message);
}

async function rdSavePages() {
    if (!rdPages.length) return;

    const button = document.getElementById('rdSaveBtn');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Saving…';

    try {
        const response = await fetch(rdScanUrlFor(rdApplicationId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': rdCsrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                pages: rdPages,
                remarks: document.getElementById('rdScanRemarks').value || null,
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok && data.success) {
            rdStopCamera();
            window.location.reload();
            return;
        }

        rdSetError(data.message || 'The pages could not be saved. Please try again.');
    } catch (e) {
        rdSetError('The pages could not be saved: ' + e.message);
    }

    button.disabled = false;
    button.innerHTML = '<i class="fas fa-floppy-disk mr-1.5"></i>Save Pages';
}

/* Close on Escape, and never leave the camera running. */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !document.getElementById('rdScanModal').classList.contains('hidden')) rdCloseScanner();
});
window.addEventListener('beforeunload', rdStopCamera);
</script>
@endsection
