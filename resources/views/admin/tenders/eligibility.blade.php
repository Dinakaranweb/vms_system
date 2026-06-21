@extends('layouts.dashboard')

@section('title', 'Eligibility Questions')
@section('header', 'Eligibility Questions for ' . $tender->tender_number)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-question-circle mr-2"></i>Define Eligibility Criteria
            </h3>
        </div>
        
        <div class="p-6">
            <form id="questionsForm" method="POST">
                @csrf
                <div id="questionsContainer">
                    <!-- Questions will be added here dynamically -->
                </div>
                
                <button type="button" onclick="addQuestion()" class="mt-4 mb-6 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    <i class="fas fa-plus mr-2"></i>Add Question
                </button>
                
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.tenders.show', $tender->id) }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        <i class="fas fa-save mr-2"></i>Save Questions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let questionCount = 0;

function addQuestion(data = null) {
    const container = document.getElementById('questionsContainer');
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item bg-gray-50 rounded-lg p-4 mb-4';
    questionDiv.setAttribute('data-id', questionCount);
    
    questionDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-gray-800">Question ${questionCount + 1}</h4>
            <button type="button" onclick="removeQuestion(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Question *</label>
                <input type="text" name="questions[${questionCount}][question]" value="${data?.question || ''}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                <textarea name="questions[${questionCount}][description]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">${data?.description || ''}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Field Type *</label>
                    <select name="questions[${questionCount}][field_type]" onchange="toggleOptions(${questionCount})" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="text" ${data?.field_type === 'text' ? 'selected' : ''}>Short Text</option>
                        <option value="textarea" ${data?.field_type === 'textarea' ? 'selected' : ''}>Paragraph Text</option>
                        <option value="select" ${data?.field_type === 'select' ? 'selected' : ''}>Dropdown Select</option>
                        <option value="radio" ${data?.field_type === 'radio' ? 'selected' : ''}>Radio Buttons</option>
                        <option value="checkbox" ${data?.field_type === 'checkbox' ? 'selected' : ''}>Checkboxes</option>
                        <option value="number" ${data?.field_type === 'number' ? 'selected' : ''}>Number</option>
                        <option value="date" ${data?.field_type === 'date' ? 'selected' : ''}>Date</option>
                        <option value="file" ${data?.field_type === 'file' ? 'selected' : ''}>File Upload</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <input type="checkbox" name="questions[${questionCount}][is_required]" value="1" ${data?.is_required !== false ? 'checked' : ''}> Required
                    </label>
                </div>
            </div>
            <div id="options_${questionCount}" style="display: ${data?.field_type === 'select' || data?.field_type === 'radio' || data?.field_type === 'checkbox' ? 'block' : 'none'}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Options (comma separated)</label>
                <input type="text" name="questions[${questionCount}][options]" value="${data?.options || ''}" placeholder="Option 1, Option 2, Option 3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div id="file_size_${questionCount}" style="display: ${data?.field_type === 'file' ? 'block' : 'none'}">
                <label class="block text-sm font-medium text-gray-700 mb-1">Max File Size (KB)</label>
                <input type="number" name="questions[${questionCount}][max_size]" value="${data?.max_size || 5120}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>
    `;
    
    container.appendChild(questionDiv);
    questionCount++;
}

function toggleOptions(index) {
    const fieldType = document.querySelector(`select[name="questions[${index}][field_type]"]`).value;
    const optionsDiv = document.getElementById(`options_${index}`);
    const fileSizeDiv = document.getElementById(`file_size_${index}`);
    
    if (optionsDiv) {
        optionsDiv.style.display = (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') ? 'block' : 'none';
    }
    if (fileSizeDiv) {
        fileSizeDiv.style.display = fieldType === 'file' ? 'block' : 'none';
    }
}

function removeQuestion(btn) {
    btn.closest('.question-item').remove();
}

// Add initial question
addQuestion();
</script>
@endsection