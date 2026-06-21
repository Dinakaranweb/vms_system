@extends('layouts.dashboard')

@section('title', 'Manage Committee')
@section('header', 'Committee Members — ' . $tender->tender_number)

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    {{-- Tender header --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-700 to-indigo-700 flex justify-between items-center">
            <div>
                <h3 class="text-white font-semibold text-lg">{{ $tender->title }}</h3>
                <p class="text-purple-200 text-sm mt-0.5">{{ $tender->tender_number }} &nbsp;·&nbsp; Committee Management</p>
            </div>
            <a href="{{ route('tender-owner.dashboard') }}" class="text-purple-200 hover:text-white text-sm flex items-center gap-1">
                <i class="fas fa-arrow-left text-xs"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Flash messages --}}
    <div id="alertBox" class="hidden"></div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- LEFT: Add form with tabs --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="border-b border-gray-100">
                    {{-- Tabs --}}
                    <div class="flex">
                        <button id="tabExisting" onclick="switchTab('existing')"
                            class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 border-purple-600 text-purple-700 transition">
                            <i class="fas fa-user-check mr-1"></i> Add Existing User
                        </button>
                        <button id="tabNew" onclick="switchTab('new')"
                            class="flex-1 px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition">
                            <i class="fas fa-user-plus mr-1"></i> Create New User
                        </button>
                    </div>
                </div>

                {{-- Tab: Add Existing --}}
                <div id="panelExisting" class="p-5">
                    <form id="addExistingForm">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Select User *</label>
                                <select name="user_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                                    <option value="">Choose a user…</option>
                                    @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }}) — {{ $u->getRoleNames()->first() }}</option>
                                    @endforeach
                                </select>
                                @if($users->isEmpty())
                                <p class="text-xs text-gray-400 mt-1">No eligible users found (all may already be members).</p>
                                @endif
                            </div>
                            @include('tender-owner._committee_common_fields')
                            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition text-sm font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Add to Committee
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Tab: Create New User --}}
                <div id="panelNew" class="p-5 hidden">
                    <form id="createUserForm">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name *</label>
                                <input type="text" name="name" required placeholder="John Doe"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Email Address *</label>
                                <input type="email" name="email" required placeholder="john@example.com"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Password *</label>
                                <input type="password" name="password" required minlength="8" placeholder="Min. 8 characters"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">System Role (Spatie) *</label>
                                <select name="spatie_role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                                    <option value="committee-member">Committee Member (default)</option>
                                    @foreach($availableRoles as $role)
                                    <option value="{{ $role->name }}">{{ ucwords(str_replace('-', ' ', $role->name)) }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-400 mt-1">Controls what parts of the portal this user can access.</p>
                            </div>
                            @include('tender-owner._committee_common_fields')
                            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium flex items-center justify-center gap-2">
                                <i class="fas fa-user-plus"></i> Create User &amp; Add to Committee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT: Members list --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600 flex justify-between items-center">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-users"></i> Committee Members
                    </h3>
                    <span class="bg-white bg-opacity-20 text-white text-sm font-semibold px-3 py-0.5 rounded-full">
                        {{ $tender->committeeMembers->count() }} member(s)
                    </span>
                </div>
                <div class="divide-y divide-gray-50" id="membersList">
                    @forelse($tender->committeeMembers as $member)
                    <div class="p-4 hover:bg-gray-50 transition" id="member_{{ $member->id }}">
                        <div class="flex justify-between items-start">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">{{ $member->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $member->email }}</p>
                                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                                        <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded-full text-xs">
                                            {{ ucwords(str_replace('_', ' ', $member->pivot->role)) }}
                                        </span>
                                        @if($member->pivot->designation)
                                        <span class="text-xs text-gray-400">{{ $member->pivot->designation }}</span>
                                        @endif
                                        <span class="text-xs {{ $member->pivot->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                            <i class="fas fa-circle text-xs mr-0.5"></i>{{ $member->pivot->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    @if($member->pivot->permissions)
                                    @php $perms = json_decode($member->pivot->permissions, true) ?? []; @endphp
                                    @if(!empty($perms))
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($perms as $perm)
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs">{{ str_replace('_', ' ', $perm) }}</span>
                                        @endforeach
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            </div>
                            <button onclick="removeMember({{ $member->id }}, '{{ addslashes($member->name) }}')"
                                class="text-red-400 hover:text-red-600 transition p-1 ml-2" title="Remove">
                                <i class="fas fa-trash-alt text-sm"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="p-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-3 block text-gray-200"></i>
                        No committee members added yet.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const tenderId  = {{ $tender->id }};

function switchTab(tab) {
    document.getElementById('panelExisting').classList.toggle('hidden', tab !== 'existing');
    document.getElementById('panelNew').classList.toggle('hidden', tab !== 'new');
    document.getElementById('tabExisting').className = 'flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition ' +
        (tab === 'existing' ? 'border-purple-600 text-purple-700' : 'border-transparent text-gray-500 hover:text-gray-700');
    document.getElementById('tabNew').className = 'flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition ' +
        (tab === 'new' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700');
}

function showAlert(msg, success = true) {
    const box = document.getElementById('alertBox');
    box.className = 'px-4 py-3 rounded-lg text-sm flex items-center gap-2 ' + (success ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800');
    box.innerHTML = `<i class="fas ${success ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'}"></i> ${msg}`;
    box.classList.remove('hidden');
    setTimeout(() => box.classList.add('hidden'), 5000);
}

// Add existing user
document.getElementById('addExistingForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = Object.fromEntries(fd);
    data.permissions = fd.getAll('permissions[]');

    fetch(`/tender-owner/tenders/${tenderId}/committee/add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showAlert(d.message); setTimeout(() => location.reload(), 1500); }
        else showAlert(d.message, false);
    })
    .catch(() => showAlert('Network error. Please try again.', false));
});

// Create new user + add
document.getElementById('createUserForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = Object.fromEntries(fd);
    data.permissions = fd.getAll('permissions[]');

    const btn = this.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating…';

    fetch(`/tender-owner/tenders/${tenderId}/committee/create-user`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showAlert(d.message); setTimeout(() => location.reload(), 1500); }
        else showAlert(d.message, false);
    })
    .catch(() => showAlert('Network error. Please try again.', false))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Create User &amp; Add to Committee';
    });
});

function removeMember(userId, name) {
    if (!confirm(`Remove "${name}" from this committee?`)) return;
    fetch(`/tender-owner/tenders/${tenderId}/committee/remove/${userId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const el = document.getElementById('member_' + userId);
            if (el) el.remove();
            showAlert(d.message);
        } else showAlert(d.message, false);
    });
}
</script>
@endsection
