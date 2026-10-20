@extends('layouts.dashboard')

@section('title', 'New Role')
@section('header', 'New Role')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <a href="{{ route('super-admin.roles.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Roles
    </a>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('super-admin.roles.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @csrf
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-user-shield mr-2"></i>Create a New Role</h3>
        </div>
        <div class="p-6 space-y-6">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Role Name (used internally — lowercase, hyphens only)</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. finance-viewer"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Displayed name will be automatically title-cased (e.g. "finance-viewer" → "Finance Viewer").</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2">Permissions</label>
                <div class="space-y-4 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4">
                    @foreach($permissions as $group => $perms)
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1.5">{{ $group }}</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach($perms as $perm)
                            <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                    {{ collect(old('permissions'))->contains($perm->name) ? 'checked' : '' }}
                                    class="rounded text-indigo-600">
                                {{ str_replace('-', ' ', $perm->name) }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="grant_portal_access" value="1" checked class="rounded text-indigo-600 mt-0.5">
                    <span class="text-sm text-gray-700">
                        <strong>Give this role a working dashboard and tender view right away</strong>
                        <p class="text-xs text-gray-500 mt-0.5">Requires the "view all tenders" permission above. Adds the generic Portal menu (Dashboard, All Tenders) and a starter widget — you can fine-tune both after creating the role from the Menu and Dashboard links on the Roles page.</p>
                    </span>
                </label>
            </div>

            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                <i class="fas fa-save mr-1"></i> Create Role
            </button>
        </div>
    </form>
</div>
@endsection
