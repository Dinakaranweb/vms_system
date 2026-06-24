<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Tender Number</label>
            <p class="font-mono font-semibold text-gray-900">{{ $tender->tender_number ?? 'N/A' }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Status</label>
            <p>
                @if($tender->status == 'published')
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Published</span>
                @elseif($tender->status == 'draft')
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Draft</span>
                @else
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Closed</span>
                @endif
            </p>
        </div>
        <div class="col-span-2 bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Title</label>
            <p class="font-semibold text-gray-900">{{ $tender->title }}</p>
        </div>
        <div class="col-span-2 bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Description</label>
            <p class="text-gray-700">{{ $tender->description }}</p>
        </div>
        <div class="col-span-2 bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Eligibility Criteria</label>
            <p class="text-gray-700">{{ $tender->eligibility_criteria }}</p>
        </div>
        @if($tender->require_estimated_value && $tender->estimated_value > 0)
        <div>
            <label class="text-xs text-gray-500 uppercase">Estimated Value</label>
            <p class="text-gray-900">₹{{ number_format($tender->estimated_value, 2) }}</p>
        </div>
        @endif
        @if($tender->require_emd && $tender->emda_amount > 0)
        <div>
            <label class="text-xs text-gray-500 uppercase">EMD Amount</label>
            <p class="text-gray-900">₹{{ number_format($tender->emda_amount, 2) }}</p>
        </div>
        @endif
        <div>
            <label class="text-xs text-gray-500 uppercase">Submission Deadline</label>
            <p class="text-gray-900">{{ $tender->submission_deadline ? date('d M, Y', strtotime($tender->submission_deadline)) : 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Opening Date</label>
            <p class="text-gray-900">{{ $tender->opening_date ? date('d M, Y', strtotime($tender->opening_date)) : 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Created By</label>
            <p class="text-gray-900">{{ $tender->creator->name ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Created On</label>
            <p class="text-gray-900">{{ $tender->created_at ? date('d M, Y H:i', strtotime($tender->created_at)) : 'N/A' }}</p>
        </div>
    </div>
    
    <!-- Eligibility Questions Section -->
    @if($tender->eligibilityQuestions && $tender->eligibilityQuestions->count() > 0)
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-question-circle mr-2 text-purple-600"></i>Eligibility Questions ({{ $tender->eligibilityQuestions->count() }})
        </h4>
        <div class="space-y-3">
            @foreach($tender->eligibilityQuestions->sortBy('order') as $index => $question)
            <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-gray-400 text-sm">{{ $index + 1 }}.</span>
                            <span class="font-semibold text-gray-900">{{ $question->question }}</span>
                            @if($question->is_required)
                                <span class="px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-xs">Required</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs">Optional</span>
                            @endif
                        </div>
                        @if($question->description)
                            <p class="text-sm text-gray-500 ml-6 mb-2">{{ $question->description }}</p>
                        @endif
                        <div class="ml-6 flex items-center space-x-3 text-xs text-gray-500">
                            <span><i class="fas fa-tag mr-1"></i> {{ ucfirst(str_replace('_', ' ', $question->field_type)) }}</span>
                            @if(in_array($question->field_type, ['select', 'radio', 'checkbox']) && $question->options)
                                <span><i class="fas fa-list mr-1"></i> Options: {{ is_array($question->options) ? implode(', ', $question->options) : $question->options }}</span>
                            @endif
                            @if($question->field_type == 'file')
                                <span><i class="fas fa-file-upload mr-1"></i> Max: {{ round($question->max_size / 1024, 2) }} MB</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="editEligibilityQuestion({{ $question->id }})" class="text-blue-600 hover:text-blue-800" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="removeEligibilityQuestion({{ $question->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-3">
            <button onclick="addEligibilityQuestion()" class="text-purple-600 hover:text-purple-800 text-sm">
                <i class="fas fa-plus mr-1"></i> Add New Question
            </button>
        </div>
    </div>
    @else
    <div class="mt-4 pt-4 border-t">
        <div class="flex justify-between items-center">
            <h4 class="font-semibold text-gray-800 mb-3">
                <i class="fas fa-question-circle mr-2 text-purple-600"></i>Eligibility Questions
            </h4>
            <button onclick="addEligibilityQuestion()" class="text-purple-600 hover:text-purple-800 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Question
            </button>
        </div>
        <div class="bg-gray-50 rounded-lg p-8 text-center text-gray-500">
            <i class="fas fa-question-circle text-4xl mb-2"></i>
            <p>No eligibility questions added yet.</p>
            <p class="text-sm">Click "Add Question" to create eligibility criteria for vendors.</p>
        </div>
    </div>
    @endif
    
    <!-- Tender Documents Section -->
    @if($tender->documents && $tender->documents->count() > 0)
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-file-alt mr-2 text-blue-600"></i>Tender Documents ({{ $tender->documents->count() }})
        </h4>
        <div class="space-y-2">
            @foreach($tender->documents->sortBy('order') as $index => $doc)
            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition">
                <div class="flex items-center space-x-3">
                    <span class="text-gray-400 text-sm w-8">{{ $doc->order ?? $index + 1 }}.</span>
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
                        <span class="text-gray-700 font-medium">{{ $doc->file_name }}</span>
                        <div class="flex items-center space-x-3 text-xs text-gray-500">
                            <span>{{ $doc->getFileSizeFormatted() }}</span>
                            <span>•</span>
                            <span>{{ ucfirst(str_replace('_', ' ', $doc->document_category)) }}</span>
                            @if($doc->is_mandatory)
                                <span class="text-red-600">(Mandatory)</span>
                            @endif
                        </div>
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
        <div class="mt-3">
            <a href="{{ route('admin.tenders.edit', $tender->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-plus mr-1"></i> Manage Documents
            </a>
        </div>
    </div>
    @else
    <div class="mt-4 pt-4 border-t">
        <div class="flex justify-between items-center">
            <h4 class="font-semibold text-gray-800 mb-3">
                <i class="fas fa-file-alt mr-2 text-blue-600"></i>Tender Documents
            </h4>
            <a href="{{ route('admin.tenders.edit', $tender->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Documents
            </a>
        </div>
        <div class="bg-gray-50 rounded-lg p-8 text-center text-gray-500">
            <i class="fas fa-file-alt text-4xl mb-2"></i>
            <p>No documents uploaded yet.</p>
        </div>
    </div>
    @endif
    
    <!-- Applications Summary -->
    <div class="mt-4 pt-4 border-t">
        <h4 class="font-semibold text-gray-800 mb-3">Applications Summary</h4>
        <div class="grid grid-cols-4 gap-3">
            <div class="text-center p-2 bg-blue-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-600">{{ $stats['total_applications'] }}</p>
                <p class="text-xs text-gray-600">Total</p>
            </div>
            <div class="text-center p-2 bg-yellow-50 rounded-lg">
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['submitted_applications'] }}</p>
                <p class="text-xs text-gray-600">Submitted</p>
            </div>
            <div class="text-center p-2 bg-green-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ $stats['shortlisted_applications'] }}</p>
                <p class="text-xs text-gray-600">Shortlisted</p>
            </div>
            <div class="text-center p-2 bg-purple-50 rounded-lg">
                <p class="text-2xl font-bold text-purple-600">{{ $stats['awarded_applications'] }}</p>
                <p class="text-xs text-gray-600">Awarded</p>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Eligibility Question Modal -->
<div id="eligibilityModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white" id="eligibilityModalTitle">Add Eligibility Question</h3>
            <button onclick="closeEligibilityModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <input type="hidden" id="editingQuestionId" value="">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Question *</label>
                <input type="text" id="questionText" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Help text)</label>
                <textarea id="questionDescription" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Field Type *</label>
                    <select id="fieldType" onchange="toggleOptionsFields()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="text">Short Text</option>
                        <option value="textarea">Paragraph Text</option>
                        <option value="select">Dropdown Select</option>
                        <option value="radio">Radio Buttons</option>
                        <option value="checkbox">Checkboxes</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="file">File Upload</option>
                    </select>
                </div>
                <div class="flex items-center pt-6">
                    <label class="flex items-center">
                        <input type="checkbox" id="isRequired" checked class="mr-2"> Required
                    </label>
                </div>
            </div>
            <div id="optionsField" class="mb-4" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Options (comma separated)</label>
                <input type="text" id="questionOptions" placeholder="Option 1, Option 2, Option 3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div id="fileSizeField" class="mb-4" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Max File Size (KB)</label>
                <input type="number" id="maxFileSize" value="5120" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeEligibilityModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button onclick="saveEligibilityQuestion()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Save Question</button>
            </div>
        </div>
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

// Close document modal when clicking outside
document.getElementById('documentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDocumentModal();
    }
});

// ==================== ELIGIBILITY QUESTIONS FUNCTIONS ====================

function addEligibilityQuestion() {
    document.getElementById('eligibilityModalTitle').innerText = 'Add Eligibility Question';
    document.getElementById('editingQuestionId').value = '';
    document.getElementById('questionText').value = '';
    document.getElementById('questionDescription').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('isRequired').checked = true;
    document.getElementById('questionOptions').value = '';
    document.getElementById('maxFileSize').value = '5120';
    toggleOptionsFields();
    document.getElementById('eligibilityModal').style.display = 'flex';
}

function editEligibilityQuestion(questionId) {
    fetch(`/admin/tenders/questions/${questionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('eligibilityModalTitle').innerText = 'Edit Eligibility Question';
                document.getElementById('editingQuestionId').value = questionId;
                document.getElementById('questionText').value = data.question.question;
                document.getElementById('questionDescription').value = data.question.description || '';
                document.getElementById('fieldType').value = data.question.field_type;
                document.getElementById('isRequired').checked = data.question.is_required;
                document.getElementById('questionOptions').value = data.question.options ? (Array.isArray(data.question.options) ? data.question.options.join(', ') : data.question.options) : '';
                document.getElementById('maxFileSize').value = data.question.max_size || 5120;
                toggleOptionsFields();
                document.getElementById('eligibilityModal').style.display = 'flex';
            }
        });
}

function removeEligibilityQuestion(questionId) {
    if (confirm('Are you sure you want to remove this eligibility question?')) {
        fetch(`/admin/tenders/questions/${questionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  location.reload();
              } else {
                  alert('Failed to remove question');
              }
          });
    }
}

function toggleOptionsFields() {
    const fieldType = document.getElementById('fieldType').value;
    const optionsField = document.getElementById('optionsField');
    const fileSizeField = document.getElementById('fileSizeField');
    
    optionsField.style.display = (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') ? 'block' : 'none';
    fileSizeField.style.display = fieldType === 'file' ? 'block' : 'none';
}

function saveEligibilityQuestion() {
    const questionText = document.getElementById('questionText').value;
    if (!questionText) {
        alert('Please enter a question');
        return;
    }
    
    const editingId = document.getElementById('editingQuestionId').value;
    const questionData = {
        question: questionText,
        description: document.getElementById('questionDescription').value,
        field_type: document.getElementById('fieldType').value,
        is_required: document.getElementById('isRequired').checked,
        options: document.getElementById('questionOptions').value,
        max_size: document.getElementById('maxFileSize').value
    };
    
    const url = editingId ? `/admin/tenders/questions/${editingId}` : `/admin/tenders/${$tender->id}/questions`;
    const method = editingId ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(questionData)
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          } else {
              alert('Failed to save question: ' + data.message);
          }
      });
}

function closeEligibilityModal() {
    document.getElementById('eligibilityModal').style.display = 'none';
}
</script>