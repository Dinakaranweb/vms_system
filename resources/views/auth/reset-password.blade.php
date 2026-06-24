@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-900 via-purple-900 to-pink-800 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-lg rounded-2xl mb-4">
                <img src="{{ asset('img/logo1.jpg') }}" alt="VMS Logo" class="w-12 h-12">
            </div>
            <h2 class="text-3xl font-bold text-white">Reset Password</h2>
            <p class="text-gray-300 mt-2">Enter and confirm your new password</p>
        </div>

        <!-- Card -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl p-8 border border-white/20">
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/20 border border-red-400/50 rounded-lg text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required
                           class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">New Password</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Min. 8 characters">
                </div>

                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Repeat new password">
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition flex items-center justify-center gap-2">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-blue-400 hover:text-blue-300 inline-flex items-center gap-1">
                    <i class="fas fa-arrow-left text-xs"></i> Back to Login
                </a>
            </div>
        </div>

        <div class="text-center mt-8">
            <p class="text-gray-400 text-sm">&copy; {{ date('Y') }} VMS Portal. All rights reserved. | Designed &amp; Developed by <a href="https://vmrfdu.edu.in" class="text-blue-400 hover:text-blue-300">VMRF(DU) IT Team</a></p>
        </div>
    </div>
</div>
@endsection
