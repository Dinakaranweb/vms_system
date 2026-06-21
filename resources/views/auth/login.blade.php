@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-900 via-purple-900 to-pink-800 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-lg rounded-2xl">
                <img src="{{ asset('img/logo1.jpg') }}" alt="VMS Logo" class="w-12 h-12">
            </div>
            <h2 class="text-3xl font-bold text-white">Welcome Back</h2>
            <p class="text-gray-300 mt-2">Sign in to your account</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl p-8 border border-white/20">
            @if(session('success'))
                <div class="mb-4 p-3 bg-green-500/20 border border-green-400/50 rounded-lg text-green-200">
                    {{ session('success') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/20 border border-red-400/50 rounded-lg text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif
            
            <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
                @csrf
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Email Address</label>
                    <input type="email" name="email" value="" 
                           class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" value=""
                           class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="mr-2">
                        <span class="text-sm text-gray-300">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-blue-400 hover:text-blue-300">Forgot password?</a>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition">
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-300">
                    Don't have an account?
                    <a href="/register" class="text-blue-400 hover:text-blue-300">Register as Vendor</a>
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-gray-400 text-sm">&copy; 2026 VMS Portal. All rights reserved. | Designed & Developed by <a href="https://vmrfdu.edu.in" class="text-blue-400 hover:text-blue-300">VMRF(DU) IT Team</a></p>
        </div>
    </div>
</div>
@endsection