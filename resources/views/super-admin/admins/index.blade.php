@extends('layouts.dashboard')

@section('title', 'Admin Management')
@section('header', 'Admin Management')

@section('content')
<div class="space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-4 flex justify-between items-center">
        <div>
            <h3 class="font-semibold text-gray-800">Admin Accounts</h3>
            <p class="text-sm text-gray-500">Staff with full administrative access (vendors, tenders, applications, contracts, users).</p>
        </div>
        <button onclick="document.getElementById('createAdminModal').style.display='flex'" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
            <i class="fas fa-user-plus mr-2"></i>Add Admin
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-user-shield mr-2"></i>All Admins</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($admins as $admin)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                    {{ substr($admin->name, 0, 2) }}
                                </div>
                                <span class="font-medium text-gray-900">{{ $admin->name }}</span>
                                @if($admin->id === auth()->id())
                                <span class="text-xs text-gray-400">(you)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $admin->email }}</td>
                        <td class="px-6 py-4">
                            @foreach($admin->getRoleNames() as $role)
                                <span class="px-2 py-1 {{ $role === 'super-admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }} rounded-full text-xs">{{ ucfirst($role) }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">
                            @if($admin->is_active)
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $admin->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            @if(!$admin->hasRole('super-admin') && $admin->id !== auth()->id())
                            <button onclick="deleteAdmin({{ $admin->id }}, '{{ addslashes($admin->name) }}')" class="text-red-600 hover:text-red-800" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            @else
                            <span class="text-gray-300"><i class="fas fa-lock"></i></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-user-shield text-4xl mb-2"></i>
                            <p>No admin accounts yet</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Admin Modal -->
<div id="createAdminModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 flex justify-between items-center rounded-t-xl">
            <h3 class="text-lg font-bold text-white">Add New Admin</h3>
            <button onclick="document.getElementById('createAdminModal').style.display='none'" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('super-admin.admins.store') }}" class="p-6 space-y-4">
            @csrf
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name *</label>
                <input type="text" name="name" required value="{{ old('name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Email *</label>
                <input type="email" name="email" required value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Password *</label>
                <input type="password" name="password" required minlength="8" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Confirm Password *</label>
                <input type="password" name="password_confirmation" required minlength="8" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 text-sm font-medium">
                <i class="fas fa-user-plus mr-1"></i>Create Admin
            </button>
        </form>
    </div>
</div>

@if($errors->any())
<script>document.getElementById('createAdminModal').style.display='flex';</script>
@endif

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function deleteAdmin(id, name) {
    if (!confirm(`Delete admin account "${name}"? This cannot be undone.`)) return;
    fetch(`/super-admin/admins/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(d => {
        if (d.success) { alert(d.message); location.reload(); }
        else alert('Failed: ' + d.message);
    });
}
</script>
@endsection
