@extends('layouts.dashboard')

@section('title', 'Create New User')
@section('header', 'Create New User')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Page header --}}
    <div class="flex items-center gap-3 mb-6">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-500 flex items-center justify-center text-white text-lg shadow-md flex-shrink-0">
            <i class="fas fa-user-plus"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Create New User</h2>
            <p class="text-sm text-gray-500 mt-0.5">Add a staff, admin, or vendor account to the system</p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-5 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        <p class="font-semibold flex items-center gap-2 mb-1"><i class="fas fa-exclamation-circle"></i> Please fix the following errors</p>
        <ul class="list-disc ml-5 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @php $rp = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <form action="{{ route($rp . '.users.store') }}" method="POST" id="createUserForm">
            @csrf

            {{-- Basic Info --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Account Details</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="e.g. Ravi Kumar"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent @error('name') border-red-400 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            placeholder="user@example.com"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent @error('email') border-red-400 @enderror">
                    </div>
                </div>
            </div>

            {{-- Password --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Password</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="passwordField" required
                                placeholder="Min. 8 characters"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent pr-10 @error('password') border-red-400 @enderror">
                            <button type="button" onclick="togglePwd('passwordField', 'eyeIcon1')"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye text-sm" id="eyeIcon1"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="passwordConfirm" required
                                placeholder="Repeat password"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent pr-10">
                            <button type="button" onclick="togglePwd('passwordConfirm', 'eyeIcon2')"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye text-sm" id="eyeIcon2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Role & Vendor --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Role &amp; Access</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select name="role" id="roleSelect" required onchange="toggleVendorField()"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent @error('role') border-red-400 @enderror">
                            <option value="">— Select a role —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">This determines what the user can access in the system.</p>
                    </div>

                    {{-- Vendor linkage (only shown for vendor role) --}}
                    <div id="vendorField" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Link to Vendor Account <span class="text-red-500">*</span>
                        </label>
                        <select name="vendor_id"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent @error('vendor_id') border-red-400 @enderror">
                            <option value="">— Select approved vendor —</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->company_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Only approved vendors appear in this list.</p>
                    </div>
                </div>
            </div>

            {{-- Account Status --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Account Status</p>
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" checked
                        class="w-4 h-4 rounded accent-blue-600">
                    <span class="text-sm text-gray-700 font-medium">Active — user can log in immediately</span>
                </label>
                <p class="text-xs text-gray-400 mt-1 ml-7">Uncheck to create the account in a disabled state.</p>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50">
                <a href="{{ route($rp . '.users') }}"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Cancel
                </a>
                <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleVendorField() {
    const role = document.getElementById('roleSelect').value;
    const field = document.getElementById('vendorField');
    const vendorSelect = field.querySelector('select');
    field.style.display = role === 'vendor' ? 'block' : 'none';
    vendorSelect.required = role === 'vendor';
}

function togglePwd(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon  = document.getElementById(iconId);
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Run on page load to restore old() state
document.addEventListener('DOMContentLoaded', toggleVendorField);
</script>
@endsection
