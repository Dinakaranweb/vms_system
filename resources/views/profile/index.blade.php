@extends('layouts.dashboard')

@section('title', 'My Profile')
@section('header', 'My Profile')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
        <i class="fas fa-check-circle text-green-600 text-xl flex-shrink-0"></i>
        <p class="text-green-800 font-medium">{{ session('success') }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Avatar / Name card --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-blue-600">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="fas fa-user-circle"></i> Account Profile
            </h3>
        </div>
        <div class="p-6 flex items-center gap-6">
            <div class="flex-shrink-0">
                <div class="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-700 border-2 border-indigo-300">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>
            <div>
                <p class="text-xl font-bold text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <span class="mt-1 inline-flex px-3 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                    {{ ucfirst(str_replace('-', ' ', $user->getRoleNames()->first() ?? 'User')) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Update Profile --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="fas fa-user-edit"></i> Update Profile
            </h3>
        </div>
        <div class="p-6">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Change Password --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-gray-700 to-gray-800">
            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="fas fa-lock"></i> Change Password
            </h3>
        </div>
        <div class="p-6">
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password *</label>
                        <input type="password" name="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500">
                        @error('current_password')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password *</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="px-5 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition font-medium">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
