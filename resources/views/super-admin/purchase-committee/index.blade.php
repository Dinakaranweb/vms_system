@extends('layouts.dashboard')

@section('title', 'Purchase Committee')
@section('header', 'Purchase Committee')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Members approve vendor awards in this order, one at a time — the 2nd member can't act until the 1st has approved. Drag rows to reorder, then save.</p>
        <a href="{{ route('super-admin.purchase-committee.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Member
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-3 py-3 w-10"></th>
                    <th class="px-2 py-3 w-24">Approver</th>
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Designation</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="memberRows" class="divide-y divide-gray-50">
                @php
                    $ordinal = function ($n) {
                        if (in_array($n % 100, [11, 12, 13])) return $n . 'th';
                        return $n . match ($n % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' };
                    };
                @endphp
                @forelse($members as $i => $member)
                <tr class="hover:bg-gray-50" data-member-id="{{ $member->id }}" draggable="true">
                    <td class="px-3 py-3.5 cursor-move text-gray-300 hover:text-gray-500" title="Drag to reorder">
                        <i class="fas fa-grip-vertical"></i>
                    </td>
                    <td class="px-2 py-3.5">
                        <span class="rank-badge inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">{{ $i + 1 }}</span>
                        <span class="rank-label text-xs text-gray-400 ml-1">{{ $ordinal($i + 1) }}</span>
                    </td>
                    <td class="px-5 py-3.5 font-medium text-gray-900">{{ $member->user->name ?? 'Unknown' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $member->designation ?? '—' }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $member->user->email ?? '—' }}</td>
                    <td class="px-5 py-3.5">
                        @if($member->is_active)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Active</span>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-3 whitespace-nowrap">
                        <button type="button" onclick="openResetPasswordModal({{ $member->id }}, '{{ addslashes($member->user->name ?? '') }}')" class="text-purple-600 hover:underline text-xs font-medium">Set Password</button>
                        <button type="button" onclick="toggleActive({{ $member->id }})" class="text-yellow-600 hover:underline text-xs font-medium">{{ $member->is_active ? 'Deactivate' : 'Activate' }}</button>
                        <button type="button" onclick="removeMember({{ $member->id }}, '{{ addslashes($member->user->name ?? '') }}')" class="text-red-600 hover:underline text-xs font-medium">Remove</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-2 block"></i>
                        No Purchase Committee members yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($members->isNotEmpty())
        <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
            <p id="orderDirtyNote" class="text-xs text-amber-600 hidden"><i class="fas fa-exclamation-circle mr-1"></i>Order changed — save to apply.</p>
            <button type="button" id="saveOrderBtn" onclick="saveOrder()" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 ml-auto">
                <i class="fas fa-save mr-1"></i> Save Order
            </button>
        </div>
        @endif
    </div>
</div>

<!-- Set Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-key mr-2"></i>Set Password</h3>
            <button onclick="closeResetPasswordModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="resetPasswordForm" class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Set a password for <strong id="resetPasswordMemberName"></strong>. They'll be notified by email that their password was changed.</p>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">New Password</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div id="resetPasswordError" class="text-xs text-red-600 hidden"></div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                    <i class="fas fa-key mr-1"></i> Set Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// ---- Drag and drop reorder ----
const tbody = document.getElementById('memberRows');
let dragEl = null;

tbody.querySelectorAll('tr[draggable="true"]').forEach(row => {
    row.addEventListener('dragstart', () => { dragEl = row; row.classList.add('opacity-40'); });
    row.addEventListener('dragend', () => { row.classList.remove('opacity-40'); markDirty(); renumberRanks(); });
    row.addEventListener('dragover', (e) => {
        e.preventDefault();
        const target = e.currentTarget;
        if (target === dragEl) return;
        const rect = target.getBoundingClientRect();
        const before = (e.clientY - rect.top) < rect.height / 2;
        tbody.insertBefore(dragEl, before ? target : target.nextSibling);
    });
});

function renumberRanks() {
    Array.from(tbody.querySelectorAll('tr[data-member-id]')).forEach((row, i) => {
        row.querySelector('.rank-badge').textContent = i + 1;
        row.querySelector('.rank-label').textContent = ordinal(i + 1);
    });
}
function ordinal(n) {
    if ([11, 12, 13].includes(n % 100)) return n + 'th';
    const suffixes = { 1: 'st', 2: 'nd', 3: 'rd' };
    return n + (suffixes[n % 10] || 'th');
}
function markDirty() {
    document.getElementById('orderDirtyNote')?.classList.remove('hidden');
}
function saveOrder() {
    const order = Array.from(tbody.querySelectorAll('tr[data-member-id]')).map(row => row.dataset.memberId);
    fetch('{{ route("super-admin.purchase-committee.reorder") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ order }),
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}

function toggleActive(id) {
    fetch(`/super-admin/purchase-committee/${id}/toggle-active`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}

function removeMember(id, name) {
    if (!confirm(`Remove ${name} from the Purchase Committee?`)) return;
    fetch(`/super-admin/purchase-committee/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}

// ---- Set password modal ----
let resetPasswordMemberId = null;
function openResetPasswordModal(id, name) {
    resetPasswordMemberId = id;
    document.getElementById('resetPasswordMemberName').textContent = name;
    document.getElementById('resetPasswordForm').reset();
    document.getElementById('resetPasswordError').classList.add('hidden');
    document.getElementById('resetPasswordModal').style.display = 'flex';
}
function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
    resetPasswordMemberId = null;
}
document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const errorBox = document.getElementById('resetPasswordError');
    errorBox.classList.add('hidden');
    const formData = new FormData(this);
    fetch(`/super-admin/purchase-committee/${resetPasswordMemberId}/reset-password`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => r.json().then(data => ({ status: r.status, data })))
    .then(({ data }) => {
        if (data.success) { alert(data.message); closeResetPasswordModal(); }
        else {
            errorBox.textContent = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to set password.');
            errorBox.classList.remove('hidden');
        }
    })
    .catch(() => { errorBox.textContent = 'Network error. Please try again.'; errorBox.classList.remove('hidden'); });
});
</script>
@endsection
