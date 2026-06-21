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
            <p class="text-lg font-bold text-green-600">₹{{ number_format($application->bid_amount, 2) }}</p>
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
    
    <!-- Application Documents Section -->
    @if($application->documents && count($application->documents) > 0)
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-file-upload mr-2 text-green-600"></i>Application Documents ({{ count($application->documents) }})
        </h4>
        <div class="space-y-2">
            @foreach($application->documents as $index => $doc)
            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition">
                <div class="flex items-center space-x-3">
                    @php
                        $fileName = $doc['name'] ?? 'Document ' . ($index + 1);
                        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
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
                        <span class="text-gray-700 font-medium">{{ $fileName }}</span>
                        <div class="text-xs text-gray-500">
                            {{ isset($doc['size']) ? number_format($doc['size'] / 1024, 2) : '0' }} KB
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf']))
                    <button onclick="viewDocument('{{ Storage::url($doc['path']) }}', '{{ $fileName }}')" 
                            class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-eye mr-1"></i> View
                    </button>
                    @endif
                    <a href="{{ Storage::url($doc['path']) }}" download="{{ $fileName }}" 
                       class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-download mr-1"></i> Download
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
            $awardRoute = str_starts_with(request()->route()->getName() ?? '', 'super-admin.')
                ? route('super-admin.applications.award-form', $application->id)
                : route('admin.applications.award-form', $application->id);
        @endphp
        <a href="{{ $awardRoute }}"
           class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition flex items-center justify-center gap-2 text-sm font-semibold">
            <i class="fas fa-trophy"></i> Award &amp; Create WO / PO
        </a>
    </div>
    @endif
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
            <!-- Document content will be loaded here -->
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
    document.getElementById('documentViewer').innerHTML = '';
}

// Close modal when clicking outside
document.getElementById('documentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDocumentModal();
    }
});
</script>