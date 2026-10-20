@extends('layouts.dashboard')

@section('title', 'Add Purchase Committee Member')
@section('header', 'Add Purchase Committee Member')

@section('content')
<div class="max-w-2xl mx-auto space-y-4">

    <a href="{{ route('super-admin.purchase-committee.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Purchase Committee
    </a>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('super-admin.purchase-committee.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @csrf
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-user-plus mr-2"></i>Add Member</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">If this email already belongs to an existing user, they'll just be added to the committee. Otherwise a new account is created and a login-setup email is sent.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Designation</label>
                <input type="text" name="designation" value="{{ old('designation') }}" placeholder="e.g. Deputy Director – IT"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <p class="text-xs text-gray-400">New members are added at the end of the approval order — adjust it from the Purchase Committee list afterward.</p>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                <i class="fas fa-save mr-1"></i> Add Member
            </button>
        </div>
    </form>
</div>
@endsection
