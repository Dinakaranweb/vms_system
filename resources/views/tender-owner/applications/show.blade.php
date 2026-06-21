<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Application No.</label>
            <p class="font-mono font-semibold text-gray-900">{{ $application->application_number }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Status</label>
            <p>
                @if($application->status == 'submitted')
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Submitted</span>
                @elseif($application->status == 'shortlisted')
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Shortlisted</span>
                @elseif($application->status == 'rejected')
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Rejected</span>
                @elseif($application->status == 'awarded')
                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">Awarded</span>
                @endif
            </p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Tender</label>
            <p class="text-gray-900">{{ $application->tender->title ?? 'N/A' }}</p>
            <p class="text-xs text-gray-500">{{ $application->tender->tender_number ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Vendor</label>
            <p class="text-gray-900">{{ $application->vendor->company_name ?? 'N/A' }}</p>
            <p class="text-xs text-gray-500">{{ $application->vendor->vendor_code ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Bid Amount</label>
            <p class="text-2xl font-bold text-green-600">₹{{ number_format($application->bid_amount, 2) }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Submitted On</label>
            <p class="text-gray-900">{{ $application->created_at->format('d M, Y H:i') }}</p>
        </div>
        <div class="col-span-2">
            <label class="text-xs text-gray-500 uppercase">Technical Proposal</label>
            <p class="text-gray-700 mt-1">{{ $application->technical_proposal ?? 'Not provided' }}</p>
        </div>
        <div class="col-span-2">
            <label class="text-xs text-gray-500 uppercase">Financial Proposal</label>
            <p class="text-gray-700 mt-1">{{ $application->financial_proposal ?? 'Not provided' }}</p>
        </div>
        @if($application->rejection_reason)
        <div class="col-span-2 bg-red-50 rounded-lg p-3">
            <label class="text-xs text-red-600 uppercase">Rejection Reason</label>
            <p class="text-red-600">{{ $application->rejection_reason }}</p>
        </div>
        @endif
    </div>
    
    @if($application->documents && count($application->documents) > 0)
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">Supporting Documents</h4>
        <div class="space-y-2">
            @foreach($application->documents as $index => $doc)
            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-file-pdf text-red-500"></i>
                    <span class="text-sm text-gray-700">{{ $doc['name'] ?? 'Document ' . ($index + 1) }}</span>
                </div>
                <span class="text-xs text-gray-500">{{ number_format(($doc['size'] ?? 0) / 1024, 2) }} KB</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    
    @if($application->status == 'shortlisted' && !$contractExists)
    <div class="mt-4 pt-4 border-t">
        <a href="{{ route('tender-owner.applications.award-form', $application->id) }}"
           class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition flex items-center justify-center">
            <i class="fas fa-trophy mr-2"></i>Award & Generate Contract
        </a>
    </div>
    @endif
</div>