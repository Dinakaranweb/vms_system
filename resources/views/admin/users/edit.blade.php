@extends('layouts.dashboard')

@section('title', 'Edit User')
@section('header', 'Edit User')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-user-edit mr-2"></i>Edit User: {{ $user->name }}
            </h3>
        </div>
        
        @if($errors->any())
        <div class="m-6 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password (Leave blank to keep unchanged)</label>
                    <input type="password" name="password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                    @if(in_array('vendor', $userRoles) && ($hasApplications ?? false))
                    <div class="mb-2 px-3 py-2 bg-amber-50 border border-amber-300 rounded-lg text-xs text-amber-800 flex items-start gap-2">
                        <i class="fas fa-lock text-amber-500 mt-0.5 flex-shrink-0"></i>
                        <span>This vendor has tender applications on record. Role cannot be changed to Admin or Tender Owner. Deactivate the account if needed.</span>
                    </div>
                    <select name="role" id="role" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            {{ ($hasApplications ?? false) ? 'disabled' : '' }}>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                <option value="{{ $role->name }}" {{ in_array($role->name, $userRoles) ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    {{-- Keep the value submitted even when select is disabled --}}
                    @if($hasApplications ?? false)
                        <input type="hidden" name="role" value="{{ $userRoles[0] ?? 'vendor' }}">
                    @endif
                    @else
                    <select name="role" id="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                            @if($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                <option value="{{ $role->name }}" {{ in_array($role->name, $userRoles) ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @endif
                </div>
                <div id="vendorField" style="display: {{ in_array('vendor', $userRoles) ? 'block' : 'none' }};">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Associated Vendor</label>
                    <select name="vendor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ $user->vendor_id == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->company_name }} ({{ $vendor->vendor_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center mt-6">
                        <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="mr-2">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <a href="{{ route('admin.users') }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-save mr-2"></i>Update User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('role').addEventListener('change', function() {
    const vendorField = document.getElementById('vendorField');
    if (this.value === 'vendor') {
        vendorField.style.display = 'block';
    } else {
        vendorField.style.display = 'none';
    }
});
</script>
@endsection