@extends('layouts.dashboard')

@section('title', 'Notifications')
@section('header', 'Notifications')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Notifications</h2>
            <p class="text-sm text-gray-500">Activity alerts for your account</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="py-20 text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bell text-2xl text-gray-300"></i>
            </div>
            <p class="text-gray-500 font-medium">No notifications yet</p>
            <p class="text-gray-400 text-sm mt-1">You'll be notified about tender updates and application status changes here.</p>
            <a href="{{ route('vendor.dashboard') }}" class="mt-5 inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left text-xs"></i> Back to Dashboard
            </a>
        </div>
    </div>

</div>
@endsection
