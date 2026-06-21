@extends('layouts.dashboard')

@section('title', 'My Tenders')
@section('header', 'My Tenders')

@push('styles')
<style>
  .tm-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(3px);
    z-index: 50;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    overflow-y: auto;
  }
  .tm-modal-overlay.open { display: flex; animation: fadeIn .18s ease; }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  .tm-modal {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 25px 60px rgba(0,0,0,.22);
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp .22s ease;
    flex-shrink: 0;
  }
  @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  .tm-modal-header {
    position: sticky; top: 0; z-index: 2;
    padding: 16px 22px;
    display: flex; align-items: center; justify-content: space-between;
    border-radius: 14px 14px 0 0;
  }
  .tm-modal-title { font-size: 1rem; font-weight: 700; color: #fff; letter-spacing: -.01em; }
  .tm-modal-close {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,.15); border: none; cursor: pointer;
    color: #fff; font-size: .9rem;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
  }
  .tm-modal-close:hover { background: rgba(255,255,255,.28); }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">My Tenders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Published</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['published'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Draft</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['draft'] }}</p>
                </div>
                <i class="fas fa-pencil-alt text-3xl text-yellow-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Closed</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['closed'] }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Applications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_applications'] }}</p>
                </div>
                <i class="fas fa-users text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- My Tenders Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>My Tenders
            </h3>
            <a href="{{ route('tender-owner.tenders.create') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-plus mr-2"></i>Create New Tender
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tender No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categories</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Committee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applications</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deadline</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tenders as $tender)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $tender->tender_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($tender->title, 40) }}</td>
                        <td class="px-6 py-4">
                          @php $cats = $tender->categories; @endphp
                          @if($cats->isEmpty())
                            @if($tender->status === 'draft')
                              <a href="{{ route('tender-owner.tenders.edit', $tender->id) }}"
                                 class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold
                                        bg-orange-50 text-orange-700 border border-orange-200"
                                 title="No categories assigned — assign before publishing so vendors are notified">
                                <i class="fas fa-exclamation-triangle text-xs"></i> No Category
                              </a>
                            @else
                              <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold
                                           bg-gray-100 text-gray-500"
                                    title="No category filter — visible to all vendors">
                                <i class="fas fa-globe text-xs"></i> All Vendors
                              </span>
                            @endif
                          @else
                            <div class="flex flex-wrap gap-1">
                              @foreach($cats->take(2) as $cat)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold text-white"
                                      style="background-color:{{ $cat->color }}"
                                      title="{{ $cat->name }}">
                                  <i class="fas {{ $cat->icon }}" style="font-size:.6rem"></i>
                                  {{ Str::limit($cat->name, 12) }}
                                </span>
                              @endforeach
                              @if($cats->count() > 2)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                                  +{{ $cats->count() - 2 }}
                                </span>
                              @endif
                            </div>
                          @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <a href="{{ route('tender-owner.committee', $tender->id) }}" class="text-purple-600 hover:text-purple-800">
                                <i class="fas fa-users mr-1"></i>{{ $tender->committeeMembers->count() }} Members
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $tender->applications_count }}</td>
                        <td class="px-6 py-4">
                            @if($tender->status == 'published')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Published</span>
                            @elseif($tender->status == 'draft')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Draft</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Closed</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $tender->submission_deadline ? date('d M, Y', strtotime($tender->submission_deadline)) : 'N/A' }}
                            @if($tender->submission_deadline && $tender->submission_deadline->isFuture())
                                <span class="text-xs text-green-600 block">{{ $tender->submission_deadline->diffInDays(now()) }} days left</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('tender-owner.tenders.show', $tender->id) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($tender->status == 'draft')
                                <a href="{{ route('tender-owner.tenders.edit', $tender->id) }}" class="text-green-600 hover:text-green-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="openPublishModal({{ $tender->id }})" class="text-purple-600 hover:text-purple-800" title="Publish">
                                    <i class="fas fa-rocket"></i>
                                </button>
                                @endif
                                @if($tender->status == 'published')
                                <button onclick="openUniversityModal({{ $tender->id }}, {{ $tender->show_on_university ? 'true' : 'false' }}, {{ json_encode($tender->university_description ?? '') }})"
                                        class="{{ $tender->show_on_university ? 'text-blue-600 hover:text-blue-800' : 'text-gray-400 hover:text-gray-600' }}"
                                        title="{{ $tender->show_on_university ? 'Showing on University Website' : 'Not on University Website' }}">
                                    <i class="fas fa-globe"></i>
                                </button>
                                <button onclick="closeTender({{ $tender->id }})" class="text-orange-600 hover:text-orange-800" title="Close">
                                    <i class="fas fa-lock"></i>
                                </button>
                                @endif
                                <button onclick="deleteTender({{ $tender->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No tenders found</p>
                            <a href="{{ route('tender-owner.tenders.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">Create your first tender</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $tenders->links() }}
        </div>
    </div>
</div>

{{-- ── Publish Modal ── --}}
<div id="publishModal" class="tm-modal-overlay">
  <div class="tm-modal" style="max-width:520px;" role="dialog" aria-modal="true">
    <div class="tm-modal-header" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
      <span class="tm-modal-title"><i class="fas fa-rocket" style="margin-right:8px;opacity:.85"></i>Publish Tender</span>
      <button class="tm-modal-close" onclick="closePublishModal()" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="tm-modal-body" style="padding:24px;">
      <input type="hidden" id="publishTenderId">
      <p style="font-size:13.5px;color:#475569;margin:0 0 20px;">Publishing will make this tender visible to vendors.</p>
      <div style="border:1.5px solid #e2e8f0;border-radius:10px;padding:16px;background:#f8fafc;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <input type="checkbox" id="pub_showOnUni" style="width:17px;height:17px;accent-color:#2563eb;flex-shrink:0;">
          <div>
            <span style="font-size:13.5px;font-weight:600;color:#1e293b;">Show on University Website</span>
            <p style="font-size:12px;color:#64748b;margin:2px 0 0;">Tick to display this tender on the university portal via API.</p>
          </div>
        </label>
        <div id="pub_uniDescWrap" style="display:none;margin-top:14px;">
          <label style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
            University Description <span style="color:#dc2626;">*</span>
          </label>
          <textarea id="pub_uniDesc" rows="4"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 12px;font-size:13.5px;font-family:inherit;resize:vertical;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#93c5fd'" onblur="this.style.borderColor='#e2e8f0'"
                    placeholder="Brief description for the university website…"></textarea>
          <p style="font-size:11.5px;color:#94a3b8;margin:4px 0 0;">This text will appear on the university website alongside the tender details.</p>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button onclick="closePublishModal()"
                style="font-size:13.5px;font-weight:500;padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;cursor:pointer;">
          Cancel
        </button>
        <button onclick="confirmPublish()"
                style="font-size:14px;font-weight:700;padding:10px 26px;border:none;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
          <i class="fas fa-rocket"></i> Publish Now
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ── University Display Modal (for published tenders) ── --}}
<div id="universityModal" class="tm-modal-overlay">
  <div class="tm-modal" style="max-width:520px;" role="dialog" aria-modal="true">
    <div class="tm-modal-header" style="background:linear-gradient(135deg,#0369a1,#2563eb);">
      <span class="tm-modal-title"><i class="fas fa-globe" style="margin-right:8px;opacity:.85"></i>University Website Display</span>
      <button class="tm-modal-close" onclick="closeUniversityModal()" aria-label="Close"><i class="fas fa-times"></i></button>
    </div>
    <div class="tm-modal-body" style="padding:24px;">
      <input type="hidden" id="uniTenderId">
      <p style="font-size:13.5px;color:#475569;margin:0 0 20px;">Control whether this tender appears on the university website. You can change this at any time while the tender is active.</p>
      <div style="border:1.5px solid #e2e8f0;border-radius:10px;padding:16px;background:#f8fafc;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <input type="checkbox" id="uni_showOnUni" style="width:17px;height:17px;accent-color:#2563eb;flex-shrink:0;">
          <div>
            <span style="font-size:13.5px;font-weight:600;color:#1e293b;">Show on University Website</span>
            <p style="font-size:12px;color:#64748b;margin:2px 0 0;">Fetched by the university portal via <code style="background:#e0f2fe;padding:1px 5px;border-radius:4px;font-size:11px;">/api/public/university/tenders</code></p>
          </div>
        </label>
        <div id="uni_uniDescWrap" style="display:none;margin-top:14px;">
          <label style="font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
            University Description <span style="color:#dc2626;">*</span>
          </label>
          <textarea id="uni_uniDesc" rows="4"
                    style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 12px;font-size:13.5px;font-family:inherit;resize:vertical;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#93c5fd'" onblur="this.style.borderColor='#e2e8f0'"
                    placeholder="Brief description for the university website…"></textarea>
          <p style="font-size:11.5px;color:#94a3b8;margin:4px 0 0;">This text will appear on the university website alongside the tender details.</p>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button onclick="closeUniversityModal()"
                style="font-size:13.5px;font-weight:500;padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;cursor:pointer;">
          Cancel
        </button>
        <button onclick="saveUniversityDisplay()"
                style="font-size:14px;font-weight:700;padding:10px 26px;border:none;border-radius:8px;background:linear-gradient(135deg,#0369a1,#2563eb);color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

/* ── Publish modal ── */
function openPublishModal(id) {
  document.getElementById('publishTenderId').value = id;
  document.getElementById('publishModal').classList.add('open');
}
function closePublishModal() {
  document.getElementById('publishModal').classList.remove('open');
  document.getElementById('publishTenderId').value = '';
  document.getElementById('pub_showOnUni').checked = false;
  document.getElementById('pub_uniDescWrap').style.display = 'none';
  document.getElementById('pub_uniDesc').value = '';
}
document.getElementById('pub_showOnUni').addEventListener('change', function () {
  document.getElementById('pub_uniDescWrap').style.display = this.checked ? '' : 'none';
});
function confirmPublish() {
  const id        = document.getElementById('publishTenderId').value;
  const showOnUni = document.getElementById('pub_showOnUni').checked;
  const desc      = document.getElementById('pub_uniDesc').value.trim();
  if (showOnUni && !desc) {
    document.getElementById('pub_uniDesc').focus();
    document.getElementById('pub_uniDesc').style.borderColor = '#dc2626';
    return;
  }
  fetch(`/tender-owner/tenders/${id}/publish`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ show_on_university: showOnUni ? 1 : 0, university_description: desc })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) { closePublishModal(); location.reload(); }
    else { alert(data.message || 'Failed to publish.'); }
  });
}

/* ── University display modal (for already-published tenders) ── */
function openUniversityModal(id, currentShow, currentDesc) {
  document.getElementById('uniTenderId').value = id;
  document.getElementById('uni_showOnUni').checked = !!currentShow;
  document.getElementById('uni_uniDesc').value = currentDesc || '';
  document.getElementById('uni_uniDescWrap').style.display = currentShow ? '' : 'none';
  document.getElementById('universityModal').classList.add('open');
}
function closeUniversityModal() {
  document.getElementById('universityModal').classList.remove('open');
}
document.getElementById('uni_showOnUni').addEventListener('change', function () {
  document.getElementById('uni_uniDescWrap').style.display = this.checked ? '' : 'none';
});
function saveUniversityDisplay() {
  const id        = document.getElementById('uniTenderId').value;
  const showOnUni = document.getElementById('uni_showOnUni').checked;
  const desc      = document.getElementById('uni_uniDesc').value.trim();
  if (showOnUni && !desc) {
    document.getElementById('uni_uniDesc').focus();
    document.getElementById('uni_uniDesc').style.borderColor = '#dc2626';
    return;
  }
  fetch(`/tender-owner/tenders/${id}/university-display`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ show_on_university: showOnUni ? 1 : 0, university_description: desc })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) { closeUniversityModal(); location.reload(); }
    else { alert(data.message || 'Failed to update.'); }
  });
}

function closeTender(id) {
    const reason = prompt('Close this tender?\n\nEnter reason for closure (will be emailed to vendors who applied):');
    if (reason === null) return;
    fetch(`/tender-owner/tenders/${id}/close`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ reason: reason })
    }).then(r => r.json())
      .then(data => {
          if (data.success) { location.reload(); }
          else { alert('Failed to close tender: ' + data.message); }
      });
}

function deleteTender(id) {
    if (confirm('Are you sure you want to delete this tender? This action cannot be undone.')) {
        fetch(`/tender-owner/tenders/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Tender deleted successfully!');
                  location.reload();
              } else {
                  alert('Failed to delete tender: ' + data.message);
              }
          });
    }
}
</script>
@endsection