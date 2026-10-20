@extends('layouts.dashboard')

@section('title', 'Edit Role Permissions')
@section('header', 'Edit Role Permissions')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <a href="{{ route('super-admin.roles.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Roles
    </a>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('super-admin.roles.update', $role->id) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @csrf
        @method('PUT')
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-user-shield mr-2"></i>{{ \Illuminate\Support\Str::headline($role->name) }} — Permissions</h3>
            @if($isBuiltIn)<span class="text-indigo-100 text-xs">Built-in role — editing changes what it can do, but not its existing menu/routes</span>@endif
        </div>
        <div class="p-6 space-y-6">
            <div class="space-y-4 max-h-[28rem] overflow-y-auto border border-gray-200 rounded-lg p-4">
                @foreach($permissions as $group => $perms)
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1.5">{{ $group }}</p>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($perms as $perm)
                        <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                {{ in_array($perm->name, $assignedPermissions) ? 'checked' : '' }}
                                class="rounded text-indigo-600">
                            {{ str_replace('-', ' ', $perm->name) }}
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                <i class="fas fa-save mr-1"></i> Save Permissions
            </button>
        </div>
    </form>

    <div class="flex gap-3">
        <a href="{{ route('super-admin.menu-builder.edit', $role->id) }}" class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:border-indigo-300 transition text-center">
            <i class="fas fa-bars text-indigo-500 text-xl block mb-1"></i>
            <span class="text-sm font-medium text-gray-700">Configure Menu</span>
        </a>
        <a href="{{ route('super-admin.widget-builder.edit', $role->id) }}" class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:border-indigo-300 transition text-center">
            <i class="fas fa-th-large text-indigo-500 text-xl block mb-1"></i>
            <span class="text-sm font-medium text-gray-700">Configure Dashboard</span>
        </a>
    </div>
</div>
@endsection
