@extends('layouts.dashboard')

@section('title', 'System Settings')
@section('header', 'System Settings')

@section('content')
<div class="space-y-6">

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

    <!-- General Settings -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-sliders-h mr-2"></i>General Settings</h3>
        </div>
        <form method="POST" action="{{ route('super-admin.settings.general') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Application Name</label>
                    <input type="text" name="app_name" value="{{ old('app_name', $general['app_name']) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email', $general['support_email']) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Support Phone</label>
                    <input type="text" name="support_phone" value="{{ old('support_phone', $general['support_phone']) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-center gap-3 pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" {{ $general['maintenance_mode'] == '1' ? 'checked' : '' }}
                            class="rounded text-blue-600">
                        <span class="text-sm text-gray-700">Maintenance mode banner (informational)</span>
                    </label>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">
                <i class="fas fa-save mr-1"></i>Save General Settings
            </button>
        </form>
    </div>

    <!-- Roles & Permissions -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-user-shield mr-2"></i>Roles &amp; Permissions</h3>
            <span class="text-purple-100 text-xs">Super Admin always has full access and cannot be edited</span>
        </div>
        <div class="p-6 space-y-8">
            @foreach($roles as $role)
            <div class="border border-gray-200 rounded-lg overflow-hidden {{ $role->name === 'super-admin' ? 'opacity-60' : '' }}">
                <div class="px-4 py-3 bg-gray-50 flex justify-between items-center">
                    <h4 class="font-semibold text-gray-800 text-sm">
                        <i class="fas fa-shield-alt mr-1 text-gray-400"></i>{{ ucwords(str_replace('-', ' ', $role->name)) }}
                    </h4>
                    <span class="text-xs text-gray-400">{{ $role->permissions->count() }} / {{ $permissions->count() }} permissions</span>
                </div>
                @if($role->name !== 'super-admin')
                <form method="POST" action="{{ route('super-admin.settings.roles') }}" class="p-4">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $role->id }}">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-4">
                        @foreach($permissions as $perm)
                        <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}
                                class="rounded text-purple-600">
                            {{ str_replace('-', ' ', $perm->name) }}
                        </label>
                        @endforeach
                    </div>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-1.5 rounded-lg hover:bg-purple-700 text-xs font-medium">
                        <i class="fas fa-save mr-1"></i>Save Permissions for {{ ucwords(str_replace('-', ' ', $role->name)) }}
                    </button>
                </form>
                @else
                <div class="p-4 text-xs text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>Grants every permission in the system automatically.
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
