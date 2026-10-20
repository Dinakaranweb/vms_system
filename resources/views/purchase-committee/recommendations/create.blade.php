@extends('layouts.dashboard')

@section('title', 'Recommend for Award')
@section('header', 'Recommend for Award')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-gavel mr-2"></i>{{ $application->tender->title }}</h3>
            <p class="text-indigo-100 text-xs mt-0.5">{{ $application->tender->tender_number }} · {{ $application->application_number }}</p>
        </div>

        <div class="p-6 grid grid-cols-2 gap-4 border-b border-gray-100">
            <div>
                <label class="text-xs text-gray-500 uppercase">Vendor</label>
                <p class="text-gray-900 font-medium">{{ $application->vendor->company_name }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Bid Amount</label>
                <p class="text-gray-900 font-medium">₹{{ number_format($application->bid_amount ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="p-6 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Approval Sequence</h4>
            @if($members->isEmpty())
            <p class="text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>No active Purchase Committee members are configured. Ask a Super Admin to add members first.</p>
            @else
            <ol class="space-y-2">
                @foreach($members as $i => $member)
                <li class="flex items-center gap-3 text-sm">
                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">{{ $i + 1 }}</span>
                    <span class="text-gray-800 font-medium">{{ $member->user->name ?? 'Unknown' }}</span>
                    @if($member->designation)<span class="text-gray-400 text-xs">— {{ $member->designation }}</span>@endif
                </li>
                @endforeach
            </ol>
            @endif
        </div>

        @if($members->isNotEmpty())
        <form method="POST" action="{{ route('purchase-committee.recommendations.store', $application->id) }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Initiation Note (optional)</label>
                <textarea name="initiation_note" rows="3" placeholder="Any context for the committee before they review..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('initiation_note') }}</textarea>
            </div>
            <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-4 py-3 text-xs text-indigo-700">
                <i class="fas fa-info-circle mr-1"></i>
                Each committee member will approve in order — the 2nd member can't act until the 1st has approved. Once everyone approves, an OTP will be emailed to each member; the award unlocks only once all OTPs are verified.
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                <i class="fas fa-paper-plane mr-1"></i> Send to Purchase Committee
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
