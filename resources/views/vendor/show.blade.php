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
                    <p class="font-medium text-gray-900">
                        @if($tender->tender_type == 'online')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                <i class="fas fa-globe mr-1"></i> Online
                            </span>
                        @else
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                <i class="fas fa-envelope mr-1"></i> Postal
                            </span>
                        @endif
                    </p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Estimated Value</label>
                    <p class="font-medium text-gray-900">₹{{ number_format($tender->estimated_value ?? 0, 2) }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">EMD Amount</label>
                    <p class="font-medium text-gray-900">₹{{ number_format($tender->emda_amount ?? 0, 2) }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Submission Deadline</label>
                    <p class="font-medium text-gray-900">
                        {{ $tender->submission_deadline ? date('d M, Y', strtotime($tender->submission_deadline)) : 'N/A' }}
                        @if($tender->submission_deadline && $tender->submission_deadline->isFuture())
                            <span class="ml-2 text-xs text-{{ $tender->submission_deadline->diffInDays(now()) <= 7 ? 'red' : 'green' }}-600">
                                ({{ $tender->submission_deadline->diffInDays(now()) }} days left)
                            </span>
                        @endif
                    </p>
                </div>
                @if($tender->opening_date)
                <div>
                    <label class="text-sm text-gray-500">Opening Date</label>
                    <p class="font-medium text-gray-900">{{ date('d M, Y', strtotime($tender->opening_date)) }}</p>
                </div>
                @endif
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
                                    @php $optLabels = collect($question->options)->map(fn($o) => is_array($o) ? ($o['label'] ?? '') : (string)$o)->filter()->join(', '); @endphp
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

        <!-- Postal Address (for postal tenders) -->
        @if($tender->tender_type == 'postal' && $tender->postal_address)
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">
                <i class="fas fa-envelope mr-2"></i>Postal Submission Address
            </h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $tender->postal_address }}</p>
        </div>
        @endif

        <!-- Documents Section -->
        @php
            $documents = $tender->documents ?? collect();
        @endphp
        
        @if($documents && $documents->count() > 0)
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-file-alt mr-2 text-blue-600"></i>Tender Documents ({{ $documents->count() }})
            </h3>
            <div class="space-y-2">
                @foreach($documents->sortBy('order') as $doc)
                <div class="flex items-center justify-between bg-white rounded-lg p-3 hover:shadow-md transition">
                    <div class="flex items-center space-x-3">
                        @php
                            $extension = pathinfo($doc->file_name, PATHINFO_EXTENSION);
                        @endphp
                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif']))
                            <i class="fas fa-file-image text-blue-500 text-xl"></i>
                        @elseif($extension == 'pdf')
                            <i class="fas fa-file-pdf text-red-500 text-xl"></i>
                        @elseif(in_array($extension, ['doc', 'docx']))
                            <i class="fas fa-file-word text-blue-700 text-xl"></i>
                        @elseif(in_array($extension, ['xls', 'xlsx']))
                            <i class="fas fa-file-excel text-green-600 text-xl"></i>
                        @else
                            <i class="fas fa-file text-gray-500 text-xl"></i>
                        @endif
                        <div>
                            <p class="text-gray-700 font-medium">{{ $doc->file_name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $doc->getFileSizeFormatted() }} | {{ ucfirst(str_replace('_', ' ', $doc->document_category)) }}
                                @if($doc->is_mandatory)
                                    <span class="text-red-600 ml-2">(Mandatory)</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf']))
                        <button onclick="viewDocument('{{ $doc->getFileUrl() }}', '{{ $doc->file_name }}')" 
                                class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-eye mr-1"></i> View
                        </button>
                        @endif
                        <a href="{{ $doc->getFileUrl() }}" download="{{ $doc->file_name }}" 
                           class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-download mr-1"></i> Download
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <i class="fas fa-file-alt text-4xl text-gray-400 mb-2"></i>
            <p class="text-gray-500">No documents have been uploaded for this tender yet.</p>
        </div>
        @endif

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
<div id="documentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="documentModalTitle">Document Viewer</h3>
            <button onclick="closeDocumentModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="documentViewer" class="p-6 min-h-[400px] flex items-center justify-center">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                <p class="mt-2 text-gray-500">Loading document...</p>
            </div>
        </div>
        <div class="sticky bottom-0 bg-gray-50 border-t px-6 py-4 flex justify-end">
            <a id="downloadDocumentBtn" href="#" download class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i>Download Document
            </a>
            <button onclick="closeDocumentModal()" class="ml-3 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                Close
            </button>
        </div>
    </div>
</div>

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