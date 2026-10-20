@extends('layouts.dashboard')

@section('title', 'Postal Bid Entry')
@section('header', 'Postal Bid Entry')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-envelope mr-1"></i>
        Enter the details exactly as received in the physical postal submission for <strong>{{ $application->vendor->company_name }}</strong>, after checking the documents.
    </div>

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

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-amber-600 to-orange-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-envelope-open-text mr-2"></i>{{ $application->tender->title }}</h3>
            <p class="text-amber-100 text-xs mt-0.5">{{ $application->tender->tender_number }} · {{ $application->application_number }}</p>
        </div>

        <div class="p-6 grid grid-cols-2 gap-4 border-b border-gray-100">
            <div>
                <label class="text-xs text-gray-500 uppercase">Vendor</label>
                <p class="text-gray-900 font-medium">{{ $application->vendor->company_name }}</p>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Vendor Code</label>
                <p class="text-gray-900">{{ $application->vendor->vendor_code ?? 'N/A' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('purchase-committee.postal-bid.update', $application->id) }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Final Quoted Amount (₹)</label>
                <input type="number" step="0.01" min="0" name="bid_amount" value="{{ old('bid_amount', $application->bid_amount) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Technical Specification / Proposal</label>
                <textarea name="technical_proposal" id="technicalProposal">{{ old('technical_proposal', $application->technical_proposal) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Financial Proposal / Terms</label>
                <textarea name="financial_proposal" id="financialProposal">{{ old('financial_proposal', $application->financial_proposal) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Postal Quote — Scanned Copy (optional)</label>
                @if($application->postal_quote_scan)
                <p class="text-xs text-gray-500 mb-1">
                    <i class="fas fa-file-alt mr-1"></i>
                    <a href="{{ Storage::url($application->postal_quote_scan) }}" target="_blank" class="text-amber-700 hover:underline">View currently uploaded scan</a>
                    — uploading a new file will replace it.
                </p>
                @endif
                <input type="file" name="postal_quote_scan" accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500">
                <p class="text-xs text-gray-400 mt-1">PDF or image, up to 10MB.</p>
            </div>

            <button type="submit" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg hover:bg-amber-700 text-sm font-medium">
                <i class="fas fa-save mr-1"></i> Save Postal Bid Details
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function () {
    $('#technicalProposal, #financialProposal').summernote({
        height: 160,
        toolbar: [
            ['style', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol']],
            ['insert', ['link']],
            ['view', ['fullscreen']]
        ],
    });
});
</script>
@endpush
