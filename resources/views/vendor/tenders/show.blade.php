@extends('layouts.dashboard')

@section('title', 'Tender Details')
@section('header', 'Tender Details')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $tender->title }}</h2>
                <p class="text-gray-500">Tender Number: {{ $tender->tender_number }}</p>
            </div>
            <div>
                @if($tender->status == 'published')
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                        <i class="fas fa-check-circle mr-1"></i>Published
                    </span>
                @elseif($tender->status == 'draft')
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">Draft</span>
                @else
                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Closed</span>
                @endif
            </div>
        </div>

        <!-- Basic Information -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tender Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-500">Tender Number</label>
                    <p class="font-medium text-gray-900">{{ $tender->tender_number }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Tender Type</label>
                    <p class="font-medium text-gray-900 flex flex-wrap gap-1">
                        @if($tender->tender_type == 'online')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                <i class="fas fa-globe mr-1"></i> Online
                            </span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">
                                <i class="fas fa-envelope mr-1"></i> Postal
                            </span>
                        @endif
                        @if($tender->isPrivate())
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">
                                <i class="fas fa-lock mr-1"></i> Private
                            </span>
                        @endif
                    </p>
                </div>
                @if($tender->require_estimated_value && $tender->estimated_value > 0)
                <div>
                    <label class="text-sm text-gray-500">Estimated Value</label>
                    <p class="font-medium text-gray-900">₹{{ number_format($tender->estimated_value, 2) }}</p>
                </div>
                @endif
                @if($tender->require_emd && $tender->emda_amount > 0)
                <div>
                    <label class="text-sm text-gray-500">EMD Amount</label>
                    <p class="font-medium text-gray-900">₹{{ number_format($tender->emda_amount, 2) }}</p>
                </div>
                @endif
                <div>
                    <label class="text-sm text-gray-500">End Date</label>
                    <p class="font-medium text-gray-900">
                        {{ $tender->submission_deadline ? date('d M, Y', strtotime($tender->submission_deadline)) : 'N/A' }}
                        @if($tender->submission_deadline && $tender->submission_deadline->isFuture())
                            <span class="ml-2 text-xs text-{{ $tender->submission_deadline->diffInDays(now()) <= 7 ? 'red' : 'green' }}-600">
                                ({{ $tender->submission_deadline->diffInDays(now()) }} days left)
                            </span>
                        @endif
                    </p>
                </div>
                <div>
                    @if($tender->opening_date)
                    <label class="text-sm text-gray-500">Opening Date</label>
                    <p class="font-medium text-gray-900">{{ date('d M, Y', strtotime($tender->opening_date)) }}</p>
                    @endif
                </div>
                <div>
                    <label class="text-sm text-gray-500">Created By</label>
                    <p class="font-medium text-gray-900">{{ $tender->creator->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Published On</label>
                    <p class="font-medium text-gray-900">{{ $tender->created_at ? date('d M, Y H:i', strtotime($tender->created_at)) : 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Description</h3>
            <p class="text-gray-700 leading-relaxed">{{ $tender->description }}</p>
        </div>

        <!-- Eligibility Criteria -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Eligibility Criteria</h3>
            <p class="text-gray-700 leading-relaxed">{{ $tender->eligibility_criteria }}</p>
        </div>

        <!-- Eligibility Questions -->
        @if($tender->eligibilityQuestions && $tender->eligibilityQuestions->count() > 0)
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-question-circle text-purple-600 mr-2"></i>Eligibility Questions
            </h3>
            <div class="space-y-4">
                @foreach($tender->eligibilityQuestions->sortBy('order') as $index => $question)
                <div class="border-b border-gray-200 pb-3 last:border-0">
                    <div class="flex items-start">
                        <span class="text-gray-400 text-sm mr-2">{{ $index + 1 }}.</span>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">
                                {{ $question->question }}
                                @if($question->is_required)
                                    <span class="ml-2 text-xs text-red-600">(Required)</span>
                                @else
                                    <span class="ml-2 text-xs text-gray-500">(Optional)</span>
                                @endif
                            </p>
                            @if($question->description)
                                <p class="text-sm text-gray-500 mt-1">{{ $question->description }}</p>
                            @endif
                            <div class="mt-2 text-xs text-gray-500">
                                <span class="px-2 py-1 bg-gray-100 rounded-full">Type: {{ ucfirst(str_replace('_', ' ', $question->field_type)) }}</span>
                                @if(in_array($question->field_type, ['select', 'radio', 'checkbox']) && $question->options)
                                    @php
                                        $optLabels = collect($question->options)->map(fn($o) => is_array($o) ? ($o['label'] ?? '') : (string)$o)->filter()->join(', ');
                                    @endphp
                                    <span class="px-2 py-1 bg-gray-100 rounded-full ml-2">Options: {{ $optLabels }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Postal Address — locked until eligibility check during application -->
        @if($tender->tender_type == 'postal')
        <div class="bg-amber-50 rounded-lg p-4 border border-amber-200 flex items-start gap-3">
            <i class="fas fa-lock text-amber-500 text-xl mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="font-semibold text-amber-800 text-sm">Postal Submission Address — Restricted</p>
                <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                    The postal submission address and downloadable documents will be revealed only after you pass
                    the eligibility check during the application process.
                    @if($tender->postal_receipt_deadline)
                        <br><span class="font-semibold mt-1 inline-block">Physical documents receipt deadline: {{ $tender->postal_receipt_deadline->format('d M Y') }}</span>
                    @endif
                </p>
            </div>
        </div>
        @endif

        <!-- Documents Section -->
       

        <!-- Action Buttons -->
        @if(!$hasApplied && $tender->isOpen())
        <div class="flex justify-center pt-4">
            <a href="{{ route('vendor.tenders.apply', $tender->id) }}" 
               class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition transform hover:scale-105">
                <i class="fas fa-paper-plane mr-2"></i>
                Apply for this Tender
            </a>
        </div>
        @elseif($hasApplied)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <div class="flex items-center justify-center">
                <i class="fas fa-info-circle text-yellow-600 text-xl mr-2"></i>
                <p class="text-yellow-800 font-medium">You have already submitted an application for this tender.</p>
            </div>
            <p class="text-sm text-yellow-700 mt-1">You cannot apply again for the same tender.</p>
        </div>
        @endif
    </div>
</div>

<!-- Document View Modal -->

<script>
function viewDocument(fileUrl, fileName) {
    const modal = document.getElementById('documentModal');
    const viewer = document.getElementById('documentViewer');
    const title = document.getElementById('documentModalTitle');
    const downloadBtn = document.getElementById('downloadDocumentBtn');
    
    title.textContent = fileName;
    downloadBtn.href = fileUrl;
    downloadBtn.download = fileName;
    
    const extension = fileName.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        viewer.innerHTML = `<img src="${fileUrl}" alt="${fileName}" class="max-w-full max-h-[70vh] object-contain">`;
    } else if (extension === 'pdf') {
        viewer.innerHTML = `<iframe src="${fileUrl}" class="w-full h-[70vh] rounded-lg" frameborder="0"></iframe>`;
    } else {
        viewer.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-file-alt text-6xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">Preview not available for this file type.</p>
                <p class="text-sm text-gray-500 mt-2">Click the download button to view the document.</p>
            </div>
        `;
    }
    
    modal.style.display = 'flex';
}

function closeDocumentModal() {
    document.getElementById('documentModal').style.display = 'none';
    document.getElementById('documentViewer').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2 text-gray-500">Loading document...</p></div>';
}

// Close modal when clicking outside
document.getElementById('documentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDocumentModal();
    }
});
</script>
@endsection