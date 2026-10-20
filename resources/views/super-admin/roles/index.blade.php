@extends('layouts.dashboard')

@section('title', 'Role Builder')
@section('header', 'Role Builder')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Create new roles, control their permissions, sidebar menu, and dashboard widgets — no code required.</p>
        <a href="{{ route('super-admin.roles.create') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
            <i class="fas fa-plus"></i> New Role
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Users</th>
                    <th class="px-5 py-3">Permissions</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($roles as $role)
                @php $isBuiltIn = in_array($role->name, ['super-admin','admin','tender-owner','vendor','committee-member','management']); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3.5">
                        <span class="font-medium text-gray-900">{{ \Illuminate\Support\Str::headline($role->name) }}</span>
                        @if($isBuiltIn)<span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Built-in</span>@endif
                        @if(!$isBuiltIn)<span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Custom</span>@endif
                    </td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $role->users_count }}</td>
                    <td class="px-5 py-3.5 text-gray-600">{{ $role->permissions_count }}</td>
                    <td class="px-5 py-3.5 text-right space-x-3 whitespace-nowrap">
                        @if($role->name !== 'super-admin')
                        <a href="{{ route('super-admin.roles.edit', $role->id) }}" class="text-indigo-600 hover:underline text-xs font-medium">Permissions</a>
                        <a href="{{ route('super-admin.menu-builder.edit', $role->id) }}" class="text-indigo-600 hover:underline text-xs font-medium">Menu</a>
                        @endif
                        <a href="{{ route('super-admin.widget-builder.edit', $role->id) }}" class="text-indigo-600 hover:underline text-xs font-medium">Dashboard</a>
                        @if(!$isBuiltIn)
                        <button onclick="deleteRole({{ $role->id }}, '{{ $role->name }}', {{ $role->users_count }})" class="text-red-600 hover:underline text-xs font-medium">Delete</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
function deleteRole(id, name, userCount) {
    if (userCount > 0) {
        alert(`Cannot delete "${name}" — ${userCount} user(s) still have this role. Reassign them first.`);
        return;
    }
    if (!confirm(`Delete the "${name}" role? This cannot be undone.`)) return;
    fetch(`/super-admin/roles/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
        .then(r => r.json())
        .then(d => { if (d.success) location.reload(); else alert(d.message); });
}
</script>
@endsection
