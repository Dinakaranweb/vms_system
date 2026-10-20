@extends('layouts.dashboard')

@section('title', 'Open Tenders')
@section('header', 'Open Tenders')

@section('content')
<div class="space-y-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Open Tenders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $tenders->total() }}</p>
                </div>
                <i class="fas fa-gavel text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Deadline Approaching</p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ $tenders->filter(function($t) { return $t->submission_deadline->diffInDays(now()) <= 7; })->count() }}
                    </p>
                </div>
                <i class="fas fa-hourglass-half text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">My Applications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ \App\Models\TenderApplication::where('vendor_id', auth()->user()->vendor_id)->count() }}</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Type Tabs -->
    <div class="flex gap-3 mb-2">
        <a href="{{ route('vendor.tenders') }}?type=online"
           class="px-5 py-2 rounded-lg font-medium text-sm transition {{ ($type ?? 'online') === 'online' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 border hover:bg-gray-50' }}">
            <i class="fas fa-globe mr-1"></i> Online Tenders
        </a>
        <a href="{{ route('vendor.tenders') }}?type=postal"
           class="px-5 py-2 rounded-lg font-medium text-sm transition {{ ($type ?? 'online') === 'postal' ? 'bg-yellow-500 text-white shadow' : 'bg-white text-gray-700 border hover:bg-gray-50' }}">
            <i class="fas fa-envelope mr-1"></i> Postal Tenders
        </a>
    </div>

    <!-- Tenders List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>{{ ($type ?? 'online') === 'postal' ? 'Postal' : 'Online' }} Tenders
            </h3>
        </div>
        
        @forelse($tenders as $tender)
        <div class="border-b border-gray-200 hover:bg-gray-50 transition">
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2 flex-wrap gap-1">
                            <span class="text-xs font-mono text-gray-500">{{ $tender->tender_number }}</span>
                            @if($tender->isPrivate())
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold flex items-center gap-1">
                                    <i class="fas fa-lock text-xs"></i> Private
                                </span>
                            @endif
                            @if($tender->isPostal())
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Postal</span>
                            @endif
                            @if($tender->submission_deadline->diffInDays(now()) <= 7)
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                                    Deadline approaching
                                </span>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $tender->title }}</h3>
                        <p class="text-gray-600 mb-3">{{ Str::limit($tender->description, 150) }}</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Est. Value:</span>
                                <span class="font-semibold text-gray-900 ml-1">₹{{ number_format($tender->estimated_value ?? 0, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Deadline:</span>
                                <span class="font-semibold text-gray-900 ml-1">{{ $tender->submission_deadline->format('d M, Y') }}</span>
                            </div>
                            @if($tender->opening_date)
                            <div>
                                <span class="text-gray-500">Opening Date:</span>
                                <span class="font-semibold text-gray-900 ml-1">{{ $tender->opening_date->format('d M, Y') }}</span>
                            </div>
                            @endif
                            <div>
                                <span class="text-gray-500">Days Left:</span>
                                <span class="font-semibold text-{{ $tender->getRemainingDays() <= 7 ? 'red' : 'green' }}-600 ml-1">
                                    {{ $tender->getRemainingDays() }} days
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="ml-4 flex flex-col items-end gap-2">
                        @if(isset($appliedTenderIds[$tender->id]))
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                <i class="fas fa-check-circle"></i> Applied
                            </span>
                        @endif
                        <a href="{{ route('vendor.tenders.show', $tender->id) }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-eye mr-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No open tenders available at the moment.</p>
        </div>
        @endforelse
    </div>
    
    <div class="mt-4">
        {{ $tenders->links() }}
    </div>
</div>
@endsection