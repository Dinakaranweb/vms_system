@extends('layouts.dashboard')

@section('title', 'Edit Tender')
@section('header', 'Edit Tender')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-edit mr-2"></i>Edit Tender: {{ $tender->tender_number }}
                </h3>
                <a href="{{ route('admin.tenders') }}" class="text-white hover:text-gray-200">
                    <i class="fas fa-times mr-1"></i> Cancel
                </a>
            </div>
        </div>
        
        @if($errors->any())
        <div class="m-6 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <form action="{{ route('admin.tenders.update', $tender->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $tender->title) }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" rows="4" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $tender->description) }}</textarea>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Eligibility Criteria
                        <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span>
                    </label>
                    <textarea name="eligibility_criteria" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Specify who is eligible to apply… (leave blank if no specific criteria)">{{ old('eligibility_criteria', $tender->eligibility_criteria) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">If left blank, all registered vendors can apply directly.</p>
                </div>
                
                <!-- Dynamic Eligibility Questions Section -->
                <div class="md:col-span-2 mt-2">
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-800">
                                <i class="fas fa-question-circle text-purple-600 mr-2"></i>Eligibility Questions
                            </h4>
                            <button type="button" onclick="addEligibilityQuestion()" class="bg-purple-600 text-white px-3 py-1 rounded-lg hover:bg-purple-700 text-sm">
                                <i class="fas fa-plus mr-1"></i> Add Question
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">Define questions that vendors must answer to determine eligibility.</p>
                        
                        <div id="eligibilityQuestionsContainer">
                            @foreach($tender->eligibilityQuestions as $index => $question)
                            <div class="eligibility-question-item bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200" data-id="{{ $question->id }}">
                                <div class="flex justify-between items-center mb-3">
                                    <h5 class="font-semibold text-gray-800">Question {{ $loop->iteration }}</h5>
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="editEligibilityQuestion({{ $question->id }})" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" onclick="removeEligibilityQuestion({{ $question->id }})" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="flex items-start justify-between">
                                        <p class="text-gray-900 font-medium">{{ $question->question }}</p>
                                        <div class="flex gap-1 ml-2 flex-shrink-0">
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $question->is_required ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $question->is_required ? 'Required' : 'Optional' }}
                                            </span>
                                            @if($question->determines_eligibility)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-semibold">
                                                <i class="fas fa-filter mr-0.5"></i> Determines Eligibility
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($question->description)
                                    <p class="text-gray-500 text-sm">{{ $question->description }}</p>
                                    @endif
                                    <div class="flex gap-4 text-sm text-gray-600">
                                        <span><span class="font-medium">Type:</span> {{ $question->getFieldTypeLabel() }}</span>
                                    </div>
                                    @if($question->options)
                                    <div>
                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Options:</span>
                                        <p class="text-gray-600 text-sm">{{ is_array($question->options) ? implode(', ', $question->options) : $question->options }}</p>
                                    </div>
                                    @endif
                                    @if($question->determines_eligibility && $question->eligible_options)
                                    <div class="bg-purple-50 border border-purple-200 rounded p-2">
                                        <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">Eligible Options:</span>
                                        <p class="text-purple-800 text-sm font-medium">{{ is_array($question->eligible_options) ? implode(', ', $question->eligible_options) : $question->eligible_options }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div id="newEligibilityQuestions"></div>
                        <input type="hidden" name="eligibility_questions_removed" id="eligibilityQuestionsRemoved" value="">
                    </div>
                </div>
                
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-1 cursor-pointer">
                        <input type="checkbox" name="require_estimated_value" value="1" id="chkEstimatedEdit"
                               {{ old('require_estimated_value', $tender->require_estimated_value) ? 'checked' : '' }}
                               class="accent-blue-600" onchange="document.getElementById('estimatedBoxEdit').classList.toggle('hidden',!this.checked)">
                        Show Estimated Value
                    </label>
                    <div id="estimatedBoxEdit" class="{{ old('require_estimated_value', $tender->require_estimated_value) ? '' : 'hidden' }}">
                        <input type="number" name="estimated_value" step="0.01" value="{{ old('estimated_value', $tender->estimated_value) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-1 cursor-pointer">
                        <input type="checkbox" name="require_emd" value="1" id="chkEmdEdit"
                               {{ old('require_emd', $tender->require_emd) ? 'checked' : '' }}
                               class="accent-blue-600" onchange="document.getElementById('emdBoxEdit').classList.toggle('hidden',!this.checked)">
                        EMD Amount Required
                    </label>
                    <div id="emdBoxEdit" class="{{ old('require_emd', $tender->require_emd) ? '' : 'hidden' }}">
                        <input type="number" name="emda_amount" step="0.01" value="{{ old('emda_amount', $tender->emda_amount) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Submission Deadline *</label>
                    <input type="date" name="submission_deadline" value="{{ old('submission_deadline', $tender->submission_deadline->format('Y-m-d')) }}" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Opening Date *</label>
                    <input type="date" name="opening_date" value="{{ old('opening_date', $tender->opening_date->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Tender Type -->
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-3">
                    <i class="fas fa-paper-plane text-blue-600 mr-2"></i>Submission Type <span class="text-red-500">*</span>
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition
                                  {{ old('tender_type', $tender->tender_type) == 'online' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}"
                           id="label-online" onclick="selectTenderType('online')">
                        <input type="radio" name="tender_type" value="online" id="type-online"
                               {{ old('tender_type', $tender->tender_type) == 'online' ? 'checked' : '' }}
                               class="mr-3 accent-blue-600">
                        <div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-globe text-blue-600 text-lg"></i>
                                <span class="font-semibold text-gray-800">Online Tender</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Vendors submit documents digitally through the portal</p>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition
                                  {{ old('tender_type', $tender->tender_type) == 'postal' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}"
                           id="label-postal" onclick="selectTenderType('postal')">
                        <input type="radio" name="tender_type" value="postal" id="type-postal"
                               {{ old('tender_type', $tender->tender_type) == 'postal' ? 'checked' : '' }}
                               class="mr-3 accent-blue-600">
                        <div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-envelope text-green-600 text-lg"></i>
                                <span class="font-semibold text-gray-800">Postal Tender</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Vendors download forms and submit by physical post</p>
                        </div>
                    </label>
                </div>

                <div id="postalFields" class="mt-4 {{ old('tender_type', $tender->tender_type) == 'postal' ? '' : 'hidden' }}">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Postal Address <span class="text-red-500">*</span>
                            </label>
                            <textarea name="postal_address" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Enter the full postal address for submissions…">{{ old('postal_address', $tender->postal_address) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Last Date to Receive Physical Documents
                            </label>
                            <input type="date" name="postal_receipt_deadline"
                                   value="{{ old('postal_receipt_deadline', $tender->postal_receipt_deadline?->format('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-400 mt-1">Deadline by which physical documents must arrive. Leave blank if not applicable.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Management Section -->
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>Documents
                </h4>
                
                <!-- Existing Documents -->
                @if($tender->documents && $tender->documents->count() > 0)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Existing Documents</label>
                    <div class="space-y-2" id="existingDocuments">
                        @foreach($tender->documents->sortBy('order') as $index => $doc)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 document-item" id="document-row-{{ $doc->id }}" data-id="{{ $doc->id }}" data-order="{{ $doc->order ?? $index }}">
                            <div class="flex items-center space-x-3 flex-1">
                                <button type="button" onclick="moveDocumentUp({{ $doc->id }})" class="text-gray-500 hover:text-blue-600" title="Move Up">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <button type="button" onclick="moveDocumentDown({{ $doc->id }})" class="text-gray-500 hover:text-blue-600" title="Move Down">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                                @php $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)); @endphp
                                @if($ext === 'pdf')
                                    <i class="fas fa-file-pdf text-red-500 text-xl"></i>
                                @elseif(in_array($ext, ['doc','docx']))
                                    <i class="fas fa-file-word text-blue-700 text-xl"></i>
                                @elseif(in_array($ext, ['xls','xlsx']))
                                    <i class="fas fa-file-excel text-green-600 text-xl"></i>
                                @elseif(in_array($ext, ['jpg','jpeg','png','gif']))
                                    <i class="fas fa-file-image text-blue-500 text-xl"></i>
                                @else
                                    <i class="fas fa-file text-gray-500 text-xl"></i>
                                @endif
                                <div>
                                    <span class="text-gray-700 font-medium">{{ $doc->file_name }}</span>
                                    <div class="text-xs text-gray-500">
                                        {{ $doc->getFileSizeFormatted() }} | {{ $doc->getCategoryLabel() }}
                                        @if($doc->is_mandatory) <span class="text-red-600 ml-2">Mandatory</span> @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ $doc->getFileUrl() }}" target="_blank"
                                   class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <a href="{{ $doc->getFileUrl() }}" download="{{ $doc->file_name }}"
                                   class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                                <button type="button" onclick="deleteDocument({{ $doc->id }})"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="mb-4 p-4 bg-yellow-50 rounded-lg text-center">
                    <i class="fas fa-file-alt text-yellow-600 text-2xl mb-2"></i>
                    <p class="text-yellow-700">No documents uploaded yet for this tender.</p>
                    <p class="text-sm text-yellow-600">Use the form below to add documents.</p>
                </div>
                @endif

                <!-- Add New Documents -->
                <div class="border rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add New Documents</label>
                    <div id="newDocumentsContainer">
                        <div class="document-upload-item mb-3" id="doc-0">
                            <div class="flex items-center space-x-3">
                                <input type="file" name="new_documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                <select name="document_categories[]" class="px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="tender_notice">Tender Notice</option>
                                    <option value="bidding_document">Bidding Document</option>
                                    <option value="specification">Specification</option>
                                    <option value="terms_conditions">Terms & Conditions</option>
                                    <option value="boq">Bill of Quantities</option>
                                    <option value="drawing">Drawing</option>
                                    <option value="other">Other</option>
                                </select>
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_mandatory[]" value="1" class="mr-2"> Mandatory
                                </label>
                                <button type="button" onclick="removeDocumentUpload(this)" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addDocumentUpload()" class="mt-3 text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Another Document
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Allowed formats: PDF, DOC, DOCX, JPG, PNG. Max size: 10MB per file.</p>
                </div>
            </div>
            
            {{-- Application Field Requirements --}}
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-1">
                    <i class="fas fa-tasks text-purple-600 mr-2"></i>Application Field Requirements
                </h4>
                <p class="text-sm text-gray-500 mb-4">Choose which fields vendors must fill when applying for this tender.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-amber-50 transition
                                  {{ old('require_bid_amount', $tender->require_bid_amount ?? true) ? 'border-amber-400 bg-amber-50' : 'border-gray-200' }}"
                           id="edit_tile_bid">
                        <input type="checkbox" name="require_bid_amount" value="1"
                               {{ old('require_bid_amount', $tender->require_bid_amount ?? true) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-amber-500"
                               onchange="editTileBorder(this,'edit_tile_bid','amber')">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-rupee-sign text-amber-600 text-sm"></i> Bid Amount
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Vendor must enter their bid price</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-purple-50 transition
                                  {{ old('require_technical_bid', $tender->require_technical_bid ?? true) ? 'border-purple-400 bg-purple-50' : 'border-gray-200' }}"
                           id="edit_tile_tech">
                        <input type="checkbox" name="require_technical_bid" value="1"
                               {{ old('require_technical_bid', $tender->require_technical_bid ?? true) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-purple-600"
                               onchange="editTileBorder(this,'edit_tile_tech','purple')">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-file-alt text-purple-600 text-sm"></i> Technical Bid
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Vendor must provide a technical proposal</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-green-50 transition
                                  {{ old('require_financial_bid', $tender->require_financial_bid ?? true) ? 'border-green-400 bg-green-50' : 'border-gray-200' }}"
                           id="edit_tile_fin">
                        <input type="checkbox" name="require_financial_bid" value="1"
                               {{ old('require_financial_bid', $tender->require_financial_bid ?? true) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-green-600"
                               onchange="editTileBorder(this,'edit_tile_fin','green')">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-coins text-green-600 text-sm"></i> Financial Bid
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Vendor must provide a financial proposal</p>
                        </div>
                    </label>
                    {{-- Email Notifications --}}
                    <label class="flex items-start gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition
                                  {{ old('email_notifications_enabled', $tender->email_notifications_enabled ?? true) ? 'border-fuchsia-400 bg-fuchsia-50' : 'border-gray-200' }}">
                        <input type="checkbox" name="email_notifications_enabled" value="1"
                               {{ old('email_notifications_enabled', $tender->email_notifications_enabled ?? true) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-fuchsia-600">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-envelope text-fuchsia-600 text-sm"></i> Send Email Notifications
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">If unchecked, no emails triggered for this tender</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Categories --}}
            @if(isset($categories) && $categories->count())
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-1">
                    <i class="fas fa-tags text-blue-600 mr-2"></i>Categories
                </h4>
                <p class="text-sm text-gray-500 mb-4">
                    Select categories to control which vendors see this tender and receive email notifications.
                    Leave unselected to make the tender visible to all vendors.
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($categories as $cat)
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                               class="w-4 h-4 accent-blue-600"
                               {{ in_array($cat->id, $selectedCategoryIds) ? 'checked' : '' }}>
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-md text-white text-xs flex-shrink-0"
                              style="background-color: {{ $cat->color }}">
                            <i class="fas {{ $cat->icon }}"></i>
                        </span>
                        <span class="text-sm font-medium text-gray-900">{{ $cat->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tender Visibility --}}
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-purple-600"></i> Tender Visibility
                </h4>
                <p class="text-sm text-gray-500 mb-4">Control who can see and apply for this tender.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer transition
                                  {{ !old('is_private', $tender->is_private) ? 'border-green-400 bg-green-50' : 'border-gray-200' }}"
                           id="edit_vis_public">
                        <input type="radio" name="is_private" value="0"
                               {{ !old('is_private', $tender->is_private) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-green-600"
                               onchange="editTogglePrivate(false)">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-globe text-green-600 text-sm"></i> Public
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">All vendors in matching categories can view and apply</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer transition
                                  {{ old('is_private', $tender->is_private) ? 'border-red-400 bg-red-50' : 'border-gray-200' }}"
                           id="edit_vis_private">
                        <input type="radio" name="is_private" value="1"
                               {{ old('is_private', $tender->is_private) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-red-600"
                               onchange="editTogglePrivate(true)">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-lock text-red-600 text-sm"></i> Private
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Only specifically invited vendors can view and apply</p>
                        </div>
                    </label>
                </div>
                <div id="editPrivateVendorsBox" class="{{ old('is_private', $tender->is_private) ? '' : 'hidden' }} mt-3">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Invited Vendors <span class="text-red-500">*</span>
                        </label>
                        <button type="button" onclick="editLoadPrivateVendors()"
                                class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                            <i class="fas fa-sync-alt"></i> Load from Categories
                        </button>
                    </div>
                    <div id="editPrivateVendorList"
                         class="max-h-52 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                        @php
                            $invitedIds = array_map('intval', (array)($tender->notify_specific_vendors ?? []));
                        @endphp
                        @if(count($invitedIds))
                            <p class="text-xs text-gray-400">{{ count($invitedIds) }} vendor(s) currently invited. Click "Load from Categories" to see/change.</p>
                        @else
                            <p class="text-xs text-gray-400"><i class="fas fa-arrow-up mr-1"></i>Select categories above, then click "Load from Categories".</p>
                        @endif
                    </div>
                    <input type="hidden" name="notify_specific_vendors" id="editPrivateVendorsInput"
                           value="{{ old('notify_specific_vendors', $tender->notify_specific_vendors ? implode(',', (array)$tender->notify_specific_vendors) : '') }}">
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle text-purple-500 mr-1"></i>
                        Only these vendors will see and apply. They also receive the publish notification email.
                    </p>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <a href="{{ route('admin.tenders') }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i>Update Tender
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Eligibility Question Modal -->
<div id="eligibilityModal" class="fixed inset-0 bg-black/50 z-50 overflow-y-auto" style="display:none; align-items:flex-start; justify-content:center; padding:24px;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
        <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-lg font-bold text-white" id="eligibilityModalTitle">Add Eligibility Question</h3>
            <button onclick="closeEligibilityModal()" class="text-white hover:text-red-200 text-xl leading-none">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="editingQuestionId" value="">

            {{-- Question text --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Question <span class="text-red-500">*</span></label>
                <input type="text" id="questionText" placeholder="Enter your question…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent">
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Help Text <span class="text-xs font-normal text-gray-400">(optional)</span>
                </label>
                <textarea id="questionDescription" rows="2" placeholder="Provide additional context or guidance…"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent"></textarea>
            </div>

            {{-- Field Type + Required + Determines Eligibility --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Field Type <span class="text-red-500">*</span></label>
                    <select id="fieldType" onchange="toggleOptionsFields()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400">
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
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none text-sm font-medium text-gray-700">
                        <input type="checkbox" id="isRequired" checked
                            class="w-4 h-4 rounded accent-purple-600">
                        Mark as required
                    </label>
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none text-sm font-medium text-gray-700">
                        <input type="checkbox" id="determinesEligibility"
                            class="w-4 h-4 rounded accent-red-500"
                            onchange="toggleOptionsFields()">
                        <span>Determines eligibility</span>
                    </label>
                </div>
            </div>

            {{-- Options (for select/radio/checkbox) --}}
            <div id="optionsField" style="display:none;">
                <label class="block text-sm font-semibold text-gray-700 mb-1">
                    Options <span class="text-xs font-normal text-gray-400">(comma separated)</span>
                </label>
                <input type="text" id="questionOptions" placeholder="Option A, Option B, Option C"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400"
                    oninput="toggleOptionsFields()">
            </div>

            {{-- Eligible Options (shown when Determines Eligibility + option field type) --}}
            <div id="eligibleOptionsField" style="display:none;" class="bg-red-50 border border-red-200 rounded-lg p-4">
                <label class="block text-sm font-semibold text-red-700 mb-1">
                    Eligible Options <span class="text-xs font-normal text-red-500">(comma separated — must match options above exactly)</span>
                </label>
                <input type="text" id="eligibleOptions" placeholder="Option A, Option C  ← these answers qualify the vendor"
                    class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm focus:ring-2 focus:ring-red-400 bg-white">
                <p class="text-xs text-red-500 mt-1">Only vendors who select these options will pass the eligibility check.</p>
            </div>

            {{-- File size (for file type) --}}
            <div id="fileSizeField" style="display:none;">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Max File Size (KB)</label>
                <input type="number" id="maxFileSize" value="5120"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-400">
            </div>

            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                <button onclick="closeEligibilityModal()"
                    class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                <button onclick="saveEligibilityQuestion()"
                    class="px-5 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium">
                    <i class="fas fa-save mr-1"></i> Save Question
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Document Management Variables
let documentCounter = 1;

// Eligibility Questions Variables
let removedQuestions = [];

function addDocumentUpload() {
    const container = document.getElementById('newDocumentsContainer');
    const newId = documentCounter;
    const div = document.createElement('div');
    div.className = 'document-upload-item mb-3';
    div.id = `doc-${newId}`;
    div.innerHTML = `
        <div class="flex items-center space-x-3">
            <input type="file" name="new_documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
            <select name="document_categories[]" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="tender_notice">Tender Notice</option>
                <option value="bidding_document">Bidding Document</option>
                <option value="specification">Specification</option>
                <option value="terms_conditions">Terms & Conditions</option>
                <option value="boq">Bill of Quantities</option>
                <option value="drawing">Drawing</option>
                <option value="other">Other</option>
            </select>
            <label class="flex items-center">
                <input type="checkbox" name="is_mandatory[]" value="1" class="mr-2"> Mandatory
            </label>
            <button type="button" onclick="removeDocumentUpload(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    documentCounter++;
}

function removeDocumentUpload(btn) {
    btn.closest('.document-upload-item').remove();
}

function deleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        // Admin route for deleting documents
        const url = `/admin/tenders/documents/${documentId}`;
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById(`document-row-${documentId}`);
                if (row) {
                    row.remove();
                }
                alert('Document deleted successfully!');
                const remainingDocs = document.querySelectorAll('[id^="document-row-"]').length;
                if (remainingDocs === 0) {
                    location.reload();
                }
            } else {
                alert('Failed to delete document: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting document');
        });
    }
}

function moveDocumentUp(documentId) {
    updateDocumentOrder(documentId, 'up');
}

function moveDocumentDown(documentId) {
    updateDocumentOrder(documentId, 'down');
}

function updateDocumentOrder(documentId, direction) {
    fetch(`/admin/tenders/documents/${documentId}/order`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ direction: direction })
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              location.reload();
          } else {
              alert('Failed to reorder document');
          }
      });
}

// ==================== ELIGIBILITY QUESTIONS FUNCTIONS ====================

function addEligibilityQuestion() {
    document.getElementById('eligibilityModalTitle').innerText = 'Add Eligibility Question';
    document.getElementById('editingQuestionId').value = '';
    document.getElementById('questionText').value = '';
    document.getElementById('questionDescription').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('isRequired').checked = true;
    document.getElementById('determinesEligibility').checked = false;
    document.getElementById('questionOptions').value = '';
    document.getElementById('eligibleOptions').value = '';
    document.getElementById('maxFileSize').value = '5120';
    toggleOptionsFields();
    document.getElementById('eligibilityModal').style.display = 'flex';
}

function editEligibilityQuestion(questionId) {
    fetch(`/admin/tenders/questions/${questionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const q = data.question;
                document.getElementById('eligibilityModalTitle').innerText = 'Edit Eligibility Question';
                document.getElementById('editingQuestionId').value = questionId;
                document.getElementById('questionText').value = q.question;
                document.getElementById('questionDescription').value = q.description || '';
                document.getElementById('fieldType').value = q.field_type;
                document.getElementById('isRequired').checked = !!q.is_required;
                document.getElementById('determinesEligibility').checked = !!q.determines_eligibility;
                document.getElementById('questionOptions').value = q.options
                    ? (Array.isArray(q.options) ? q.options.join(', ') : q.options)
                    : '';
                document.getElementById('eligibleOptions').value = q.eligible_options
                    ? (Array.isArray(q.eligible_options) ? q.eligible_options.join(', ') : q.eligible_options)
                    : '';
                document.getElementById('maxFileSize').value = q.max_size || 5120;
                toggleOptionsFields();
                document.getElementById('eligibilityModal').style.display = 'flex';
            }
        });
}

function removeEligibilityQuestion(questionId) {
    if (confirm('Are you sure you want to remove this eligibility question?')) {
        removedQuestions.push(questionId);
        document.getElementById('eligibilityQuestionsRemoved').value = JSON.stringify(removedQuestions);
        document.querySelector(`.eligibility-question-item[data-id="${questionId}"]`).remove();
    }
}

function toggleOptionsFields() {
    const fieldType = document.getElementById('fieldType').value;
    const isOptionType = (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox');
    const deChk = document.getElementById('determinesEligibility');

    document.getElementById('optionsField').style.display = isOptionType ? 'block' : 'none';
    document.getElementById('fileSizeField').style.display = fieldType === 'file' ? 'block' : 'none';

    deChk.disabled = !isOptionType;
    if (!isOptionType) deChk.checked = false;
    deChk.closest('label').style.opacity = isOptionType ? '1' : '0.4';

    document.getElementById('eligibleOptionsField').style.display = (isOptionType && deChk.checked) ? 'block' : 'none';
}

function saveEligibilityQuestion() {
    const questionText = document.getElementById('questionText').value.trim();
    if (!questionText) { alert('Please enter a question'); return; }

    const editingId = document.getElementById('editingQuestionId').value;
    const questionData = {
        id: editingId,
        question: questionText,
        description: document.getElementById('questionDescription').value,
        field_type: document.getElementById('fieldType').value,
        is_required: document.getElementById('isRequired').checked,
        determines_eligibility: document.getElementById('determinesEligibility').checked,
        options: document.getElementById('questionOptions').value,
        eligible_options: document.getElementById('eligibleOptions').value,
        max_size: document.getElementById('maxFileSize').value
    };

    if (editingId) {
        fetch(`/admin/tenders/questions/${editingId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(questionData)
        }).then(r => r.json())
          .then(data => {
              if (data.success) { location.reload(); }
              else { alert('Failed to update question: ' + (data.message || 'Unknown error')); }
          });
    } else {
        const container = document.getElementById('newEligibilityQuestions');
        const index = Date.now();
        const div = document.createElement('div');
        div.className = 'eligibility-question-item bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200';

        const badgesHtml = `
            <span class="text-xs px-2 py-0.5 rounded-full ${questionData.is_required ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'}">
                ${questionData.is_required ? 'Required' : 'Optional'}
            </span>
            ${questionData.determines_eligibility ? '<span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-semibold"><i class="fas fa-filter mr-0.5"></i> Determines Eligibility</span>' : ''}
        `;

        div.innerHTML = `
            <input type="hidden" name="new_questions[${index}][question]" value="${escapeHtml(questionData.question)}">
            <input type="hidden" name="new_questions[${index}][description]" value="${escapeHtml(questionData.description)}">
            <input type="hidden" name="new_questions[${index}][field_type]" value="${questionData.field_type}">
            <input type="hidden" name="new_questions[${index}][is_required]" value="${questionData.is_required ? 1 : 0}">
            <input type="hidden" name="new_questions[${index}][determines_eligibility]" value="${questionData.determines_eligibility ? 1 : 0}">
            <input type="hidden" name="new_questions[${index}][options]" value="${escapeHtml(questionData.options)}">
            <input type="hidden" name="new_questions[${index}][eligible_options]" value="${escapeHtml(questionData.eligible_options)}">
            <input type="hidden" name="new_questions[${index}][max_size]" value="${questionData.max_size}">
            <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="font-semibold text-gray-800">${escapeHtml(questionData.question)}</p>
                    ${badgesHtml}
                </div>
                <button type="button" onclick="this.closest('.eligibility-question-item').remove()" class="text-red-500 hover:text-red-700 ml-2 flex-shrink-0">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            ${questionData.description ? `<p class="text-gray-500 text-sm mb-2">${escapeHtml(questionData.description)}</p>` : ''}
            <p class="text-sm text-gray-600"><span class="font-medium">Type:</span> ${getFieldTypeLabel(questionData.field_type)}</p>
            ${questionData.options ? `<p class="text-sm text-gray-500 mt-1"><span class="font-medium">Options:</span> ${escapeHtml(questionData.options)}</p>` : ''}
            ${(questionData.determines_eligibility && questionData.eligible_options) ? `
                <div class="bg-purple-50 border border-purple-200 rounded p-2 mt-2">
                    <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">Eligible Options:</span>
                    <p class="text-purple-800 text-sm font-medium">${escapeHtml(questionData.eligible_options)}</p>
                </div>` : ''}
        `;
        container.appendChild(div);
    }

    closeEligibilityModal();
}

function getFieldTypeLabel(fieldType) {
    const labels = {
        'text': 'Short Text',
        'textarea': 'Paragraph Text',
        'select': 'Dropdown Select',
        'radio': 'Radio Buttons',
        'checkbox': 'Checkboxes',
        'number': 'Number',
        'date': 'Date',
        'file': 'File Upload'
    };
    return labels[fieldType] || fieldType;
}

function closeEligibilityModal() {
    document.getElementById('eligibilityModal').style.display = 'none';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== APPLICATION REQUIREMENT TILES ====================
function editTileBorder(cb, tileId, color) {
    const tile = document.getElementById(tileId);
    if (!tile) return;
    const colorMap = { amber: ['border-amber-400','bg-amber-50'], purple: ['border-purple-400','bg-purple-50'], green: ['border-green-400','bg-green-50'] };
    const [border, bg] = colorMap[color] || ['border-blue-400','bg-blue-50'];
    if (cb.checked) { tile.classList.add(border, bg); tile.classList.remove('border-gray-200'); }
    else { tile.classList.remove(border, bg); tile.classList.add('border-gray-200'); }
}

// ==================== TENDER TYPE TOGGLE ====================
function selectTenderType(type) {
    const labelOnline = document.getElementById('label-online');
    const labelPostal = document.getElementById('label-postal');
    const postalFields = document.getElementById('postalFields');

    if (type === 'online') {
        document.getElementById('type-online').checked = true;
        labelOnline.classList.add('border-blue-500', 'bg-blue-50');
        labelOnline.classList.remove('border-gray-200');
        labelPostal.classList.remove('border-blue-500', 'bg-blue-50');
        labelPostal.classList.add('border-gray-200');
        postalFields.classList.add('hidden');
    } else {
        document.getElementById('type-postal').checked = true;
        labelPostal.classList.add('border-blue-500', 'bg-blue-50');
        labelPostal.classList.remove('border-gray-200');
        labelOnline.classList.remove('border-blue-500', 'bg-blue-50');
        labelOnline.classList.add('border-gray-200');
        postalFields.classList.remove('hidden');
    }
}

function editTogglePrivate(show) {
    const box = document.getElementById('editPrivateVendorsBox');
    const pubTile = document.getElementById('edit_vis_public');
    const privTile = document.getElementById('edit_vis_private');
    box.classList.toggle('hidden', !show);
    pubTile.className  = pubTile.className.replace(/border-(green|gray)-\d+\s*bg-green-50/, '').trim();
    privTile.className = privTile.className.replace(/border-(red|gray)-\d+\s*bg-red-50/, '').trim();
    pubTile.classList.add(show  ? 'border-gray-200' : 'border-green-400', show ? '' : 'bg-green-50');
    privTile.classList.add(show ? 'border-red-400'  : 'border-gray-200', show ? 'bg-red-50' : '');
}

function editLoadPrivateVendors() {
    const checkedCats = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked')).map(c => c.value);
    if (!checkedCats.length) { alert('Please select at least one category first.'); return; }
    const rolePrefix = window.location.pathname.startsWith('/super-admin/') ? 'super-admin' : 'admin';
    const box = document.getElementById('editPrivateVendorList');
    box.innerHTML = '<p class="text-xs text-gray-400">Loading…</p>';
    const existingIds = (document.getElementById('editPrivateVendorsInput').value || '').split(',').filter(Boolean);
    const vendorMap = {};
    Promise.all(checkedCats.map(catId =>
        fetch(`/${rolePrefix}/categories/${catId}/vendors`).then(r => r.json()).catch(() => [])
    )).then(results => {
        results.flat().forEach(v => { vendorMap[v.id] = v; });
        const vendors = Object.values(vendorMap);
        if (!vendors.length) { box.innerHTML = '<p class="text-xs text-gray-400">No vendors found in selected categories.</p>'; return; }
        box.innerHTML = vendors.map(v => `
            <label class="flex items-center gap-2 mb-1.5 cursor-pointer text-sm">
                <input type="checkbox" class="edit-private-cb w-4 h-4 accent-purple-600" value="${v.id}"
                       ${existingIds.includes(String(v.id)) ? 'checked' : ''}
                       onchange="updateEditPrivateIds()">
                <span class="text-gray-800">${v.company_name || v.name}</span>
                <span class="text-xs text-gray-400">${v.email || ''}</span>
            </label>`).join('');
        updateEditPrivateIds();
    });
}

function updateEditPrivateIds() {
    const ids = Array.from(document.querySelectorAll('.edit-private-cb:checked')).map(c => c.value);
    document.getElementById('editPrivateVendorsInput').value = ids.join(',');
}
</script>
@endsection