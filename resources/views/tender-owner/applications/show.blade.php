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
            <p class="text-2xl font-bold text-green-600">
                @if($application->tender && !$application->tender->isBidOpen())
                    <span class="text-gray-400 text-sm font-normal"><i class="fas fa-lock mr-1"></i>Sealed until bids are opened</span>
                @elseif($application->bid_amount !== null && $application->bid_amount > 0)
                    ₹{{ number_format($application->bid_amount, 2) }}
                @else
                    <span class="text-gray-400 text-sm font-normal">N/A</span>
                @endif
            </p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Submitted On</label>
            <p class="text-gray-900">{{ $application->created_at->format('d M, Y H:i') }}</p>
        </div>
        <div class="col-span-2">
            <label class="text-xs text-gray-500 uppercase">Technical Proposal</label>
            @if($application->technical_proposal && trim($application->technical_proposal) !== '')
                <p class="text-gray-700 mt-1 text-sm leading-relaxed whitespace-pre-line">{{ $application->technical_proposal }}</p>
            @endif
            @if($application->technical_proposal_file)
                <div class="mt-2 flex gap-2 flex-wrap">
                    <a href="{{ Storage::url($application->technical_proposal_file) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                        <i class="fas fa-eye"></i> View File
                    </a>
                    <a href="{{ Storage::url($application->technical_proposal_file) }}" download
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            @elseif(!($application->technical_proposal && trim($application->technical_proposal) !== ''))
                <p class="text-gray-400 italic text-sm mt-1">Not provided</p>
            @endif
        </div>
        <div class="col-span-2">
            <label class="text-xs text-gray-500 uppercase">Financial Proposal</label>
            @if($application->financial_proposal && trim($application->financial_proposal) !== '')
                <p class="text-gray-700 mt-1 text-sm leading-relaxed whitespace-pre-line">{{ $application->financial_proposal }}</p>
            @endif
            @if($application->financial_proposal_file)
                <div class="mt-2 flex gap-2 flex-wrap">
                    <a href="{{ Storage::url($application->financial_proposal_file) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                        <i class="fas fa-eye"></i> View File
                    </a>
                    <a href="{{ Storage::url($application->financial_proposal_file) }}" download
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            @elseif(!($application->financial_proposal && trim($application->financial_proposal) !== ''))
                <p class="text-gray-400 italic text-sm mt-1">Not provided</p>
            @endif
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
        @php
            $tenderAlreadyAwarded = \App\Models\TenderApplication::where('tender_id', $application->tender_id)
                ->where('status', 'awarded')
                ->where('id', '!=', $application->id)
                ->exists();
            $hasActiveCommittee = \App\Models\PurchaseCommitteeMember::active()->exists();
            $recommendation = $hasActiveCommittee ? \App\Models\AwardRecommendation::where('application_id', $application->id)->first() : null;
        @endphp
        @if($tenderAlreadyAwarded)
        <div class="w-full bg-gray-100 text-gray-500 py-2 rounded-lg flex items-center justify-center gap-2 text-sm font-medium border border-gray-200">
            <i class="fas fa-lock"></i> Award Locked — Tender already awarded to another vendor
        </div>
        @elseif($hasActiveCommittee && (!$recommendation || $recommendation->status !== 'completed'))
            @if(!$application->tender->isBidOpen())
            <div class="w-full bg-amber-50 text-amber-700 py-2 rounded-lg flex items-center justify-center gap-2 text-sm font-medium border border-amber-200">
                <i class="fas fa-lock"></i> Open bids for this tender before recommending an award
            </div>
            @elseif(!$recommendation)
            <a href="{{ route('purchase-committee.recommendations.create', $application->id) }}"
               class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition flex items-center justify-center">
                <i class="fas fa-gavel mr-2"></i>Recommend for Award — Purchase Committee Approval Required
            </a>
            @else
            <a href="{{ route('purchase-committee.recommendations.show', $recommendation->id) }}"
               class="w-full bg-indigo-50 text-indigo-700 py-2 rounded-lg hover:bg-indigo-100 transition flex items-center justify-center border border-indigo-200">
                <i class="fas fa-hourglass-half mr-2"></i>View Committee Approval Status ({{ ucfirst(str_replace('_',' ',$recommendation->status)) }})
            </a>
            @endif
        @else
        <a href="{{ route('tender-owner.applications.award-form', $application->id) }}"
           class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition flex items-center justify-center">
            <i class="fas fa-trophy mr-2"></i>Award & Generate Contract
        </a>
        @endif
    </div>
    @endif
</div>