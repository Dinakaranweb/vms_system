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
        @if(!($application->tender && $application->tender->isPostal()))
        <div>
            <label class="text-xs text-gray-500 uppercase">Bid Amount</label>
            <p class="text-2xl font-bold text-green-600">
                @if($application->bid_amount !== null && $application->bid_amount > 0)₹{{ number_format($application->bid_amount, 2) }}@else<span class="text-gray-400 text-sm font-normal">N/A</span>@endif
            </p>
        </div>
        @endif
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
    
    <!-- Eligibility Answers Section -->
    @if($application->eligibilityAnswers && $application->eligibilityAnswers->count() > 0)
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-question-circle mr-2 text-purple-600"></i>Eligibility Answers
        </h4>
        @php
            $allDetermining = $application->eligibilityAnswers->filter(fn($a) => $a->question && $a->question->determines_eligibility);
            $overallEligible = $allDetermining->every(fn($a) => $a->is_eligible);
        @endphp
        @if($allDetermining->count() > 0)
        <div class="mb-3 px-3 py-2 rounded-lg {{ $overallEligible ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
            <span class="text-sm font-semibold {{ $overallEligible ? 'text-green-700' : 'text-red-700' }}">
                <i class="fas {{ $overallEligible ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                Eligibility: {{ $overallEligible ? 'ELIGIBLE' : 'NOT ELIGIBLE' }}
            </span>
        </div>
        @endif
        <div class="space-y-2">
            @foreach($application->eligibilityAnswers->sortBy('question.order') as $answer)
            @if($answer->question)
            <div class="bg-gray-50 rounded-lg p-3 {{ $answer->question->determines_eligibility ? 'border-l-4 ' . ($answer->is_eligible ? 'border-green-500' : 'border-red-500') : '' }}">
                <p class="text-sm font-medium text-gray-800">
                    {{ $answer->question->question }}
                    @if($answer->question->determines_eligibility)
                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full {{ $answer->is_eligible ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $answer->is_eligible ? '✓ Eligible' : '✗ Not Eligible' }}
                        </span>
                    @else
                        <span class="ml-2 text-xs text-gray-400">(Info only)</span>
                    @endif
                </p>
                <p class="text-sm text-gray-600 mt-1">
                    @if($answer->file_path)
                        <a href="{{ Storage::url($answer->file_path) }}" target="_blank" class="text-blue-600 hover:underline">
                            <i class="fas fa-file mr-1"></i>View Uploaded File
                        </a>
                    @else
                        {{ $answer->answer ?: '(No answer provided)' }}
                    @endif
                </p>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    @if($application->documents && count($application->documents) > 0)
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-file-upload mr-2 text-green-600"></i>Application Documents
        </h4>
        <div class="space-y-2">
            @foreach($application->documents as $index => $doc)
            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2">
                <div class="flex items-center space-x-2">
                    @php $ext = pathinfo($doc['name'] ?? '', PATHINFO_EXTENSION); @endphp
                    <i class="fas fa-file-{{ $ext === 'pdf' ? 'pdf text-red-500' : (in_array($ext, ['jpg','jpeg','png']) ? 'image text-blue-500' : 'text-gray-500') }}"></i>
                    <span class="text-sm text-gray-700">{{ $doc['name'] ?? 'Document ' . ($index + 1) }}</span>
                </div>
                <div class="flex gap-2">
                    @if(in_array($ext, ['pdf','jpg','jpeg','png','gif']))
                    <button onclick="viewDocument('{{ Storage::url($doc['path']) }}', '{{ $doc['name'] ?? '' }}')"
                            class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        <i class="fas fa-eye"></i>
                    </button>
                    @endif
                    <a href="{{ Storage::url($doc['path']) }}" download="{{ $doc['name'] ?? '' }}"
                       class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
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