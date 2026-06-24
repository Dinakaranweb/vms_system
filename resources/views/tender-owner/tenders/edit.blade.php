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
                <a href="{{ route('tender-owner.tenders.index') }}" class="text-white hover:text-gray-200">
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
        
        <form action="{{ route('tender-owner.tenders.update', $tender->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Eligibility Criteria <span class="text-gray-400 font-normal text-xs">(optional)</span></label>
                    <textarea name="eligibility_criteria" rows="3"
                              placeholder="Specify who is eligible to apply… (leave blank if no criteria)"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('eligibility_criteria', $tender->eligibility_criteria) }}</textarea>
                </div>
                
                <!-- Eligibility Questions Section -->
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
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Question</label>
                                        <p class="text-gray-900">{{ $question->question }}</p>
                                    </div>
                                    @if($question->description)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                        <p class="text-gray-500 text-sm">{{ $question->description }}</p>
                                    </div>
                                    @endif
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Field Type</label>
                                            <p class="text-gray-900">{{ $question->getFieldTypeLabel() }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                {{ $question->is_required ? 'Required' : 'Optional' }}
                                            </label>
                                        </div>
                                    </div>
                                    @if($question->options)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Options</label>
                                        <p class="text-gray-500 text-sm">{{ is_array($question->options) ? implode(', ', $question->options) : $question->options }}</p>
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
                        <input type="checkbox" name="require_estimated_value" value="1"
                               {{ old('require_estimated_value', $tender->require_estimated_value) ? 'checked' : '' }}
                               class="accent-blue-600" onchange="document.getElementById('estBoxTo').classList.toggle('hidden',!this.checked)">
                        Show Estimated Value
                    </label>
                    <div id="estBoxTo" class="{{ old('require_estimated_value', $tender->require_estimated_value) ? '' : 'hidden' }}">
                        <input type="number" name="estimated_value" step="0.01" value="{{ old('estimated_value', $tender->estimated_value) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-1 cursor-pointer">
                        <input type="checkbox" name="require_emd" value="1"
                               {{ old('require_emd', $tender->require_emd) ? 'checked' : '' }}
                               class="accent-blue-600" onchange="document.getElementById('emdBoxTo').classList.toggle('hidden',!this.checked)">
                        EMD Amount Required
                    </label>
                    <div id="emdBoxTo" class="{{ old('require_emd', $tender->require_emd) ? '' : 'hidden' }}">
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
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tender Type *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="tender_type" value="online" {{ $tender->tender_type == 'online' ? 'checked' : '' }} class="mr-3">
                            <div>
                                <i class="fas fa-globe text-blue-600 text-xl mr-2"></i>
                                <span class="font-medium">Online Tender</span>
                                <p class="text-xs text-gray-500">Vendors submit documents online</p>
                            </div>
                        </label>
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="tender_type" value="postal" {{ $tender->tender_type == 'postal' ? 'checked' : '' }} class="mr-3">
                            <div>
                                <i class="fas fa-envelope text-green-600 text-xl mr-2"></i>
                                <span class="font-medium">Postal Tender</span>
                                <p class="text-xs text-gray-500">Vendors download forms and submit by post</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div id="postalFields" class="md:col-span-2" style="display: {{ $tender->tender_type == 'postal' ? 'block' : 'none' }}">
                    <div class="bg-gray-50 rounded-lg p-4 mt-2 space-y-4">
                        <h4 class="font-semibold text-gray-800 mb-3">Postal Submission Details</h4>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Address</label>
                            <textarea name="postal_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('postal_address', $tender->postal_address) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Date to Receive Physical Documents</label>
                            <input type="date" name="postal_receipt_deadline"
                                   value="{{ old('postal_receipt_deadline', $tender->postal_receipt_deadline?->format('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-400 mt-1">Deadline by which physical documents must arrive. Leave blank if not applicable.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Section -->
                <div class="md:col-span-2 mt-4">
                    <div class="border-t pt-4">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-file-alt text-blue-600 mr-2"></i>Documents
                        </h4>
                        
                        @php
                            $existingDocs = \App\Models\TenderDocument::where('tender_id', $tender->id)->orderBy('order', 'asc')->get();
                            $docCount = $existingDocs->count();
                        @endphp
                        
                        @if($docCount > 0)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Existing Documents ({{ $docCount }})</label>
                            <div class="space-y-2" id="existingDocuments">
                                @foreach($existingDocs as $doc)
                                <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 document-item" id="document-row-{{ $doc->id }}" data-id="{{ $doc->id }}" data-order="{{ $doc->order ?? $loop->index }}">
                                    <div class="flex items-center space-x-3 flex-1">
                                        @php
                                            $extension = pathinfo($doc->file_name, PATHINFO_EXTENSION);
                                            $fileUrl = asset('storage/' . $doc->file_path);
                                            if (str_starts_with($doc->file_path, 'public/')) {
                                                $fileUrl = asset('storage/' . substr($doc->file_path, 7));
                                            }
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
                                        <div class="flex-1">
                                            <span class="text-gray-700 font-medium">{{ $doc->file_name }}</span>
                                            <div class="text-xs text-gray-500">
                                                {{ $doc->getFileSizeFormatted() }} | {{ ucfirst(str_replace('_', ' ', $doc->document_category)) }}
                                                @if($doc->is_mandatory) <span class="text-red-600 ml-2">Mandatory</span> @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2 ml-3">
                                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'pdf']))
                                        <button onclick="viewDocument('{{ $fileUrl }}', '{{ $doc->file_name }}')" 
                                                class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </button>
                                        
                                        @endif
                                        @php
    $fileUrl = route('documents.view', ['id' => $doc->id, 'filename' => urlencode($doc->file_name)]);
@endphp
                                        <a href="{{ $fileUrl }}" download="{{ $doc->file_name }}" 
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
                            <button type="button" onclick="addDocumentUpload()" class="mt-2 text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-plus mr-1"></i> Add Another Document
                            </button>
                            <p class="text-xs text-gray-500 mt-2">Allowed formats: PDF, DOC, DOCX, JPG, PNG. Max size: 10MB per file.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Categories --}}
            @if(isset($categories) && $categories->count())
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-1">
                    <i class="fas fa-tags text-indigo-600 mr-2"></i>Categories
                </h4>
                <p class="text-sm text-gray-500 mb-4">
                    Select categories to control which vendors see this tender and receive email notifications.
                    Leave unselected to make the tender visible to all vendors.
                </p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($categories as $cat)
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                               class="w-4 h-4 accent-indigo-600"
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
                           id="owner_vis_public">
                        <input type="radio" name="is_private" value="0"
                               {{ !old('is_private', $tender->is_private) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-green-600"
                               onchange="ownerEditTogglePrivate(false)">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-globe text-green-600 text-sm"></i> Public
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">All vendors in matching categories can view and apply</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer transition
                                  {{ old('is_private', $tender->is_private) ? 'border-red-400 bg-red-50' : 'border-gray-200' }}"
                           id="owner_vis_private">
                        <input type="radio" name="is_private" value="1"
                               {{ old('is_private', $tender->is_private) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-red-600"
                               onchange="ownerEditTogglePrivate(true)">
                        <div>
                            <div class="font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas fa-lock text-red-600 text-sm"></i> Private
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Only specifically invited vendors can view and apply</p>
                        </div>
                    </label>
                </div>
                <div id="ownerEditPrivateBox" class="{{ old('is_private', $tender->is_private) ? '' : 'hidden' }} mt-3">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            Invited Vendors <span class="text-red-500">*</span>
                        </label>
                        <button type="button" onclick="ownerEditLoadPrivateVendors()"
                                class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-1">
                            <i class="fas fa-sync-alt"></i> Load from Categories
                        </button>
                    </div>
                    <div id="ownerEditPrivateList"
                         class="max-h-52 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                        @php $invitedIds = array_map('intval', (array)($tender->notify_specific_vendors ?? [])); @endphp
                        @if(count($invitedIds))
                            <p class="text-xs text-gray-400">{{ count($invitedIds) }} vendor(s) currently invited. Click "Load from Categories" to see/change.</p>
                        @else
                            <p class="text-xs text-gray-400"><i class="fas fa-arrow-up mr-1"></i>Select categories above, then click "Load from Categories".</p>
                        @endif
                    </div>
                    <input type="hidden" name="notify_specific_vendors" id="ownerEditPrivateInput"
                           value="{{ old('notify_specific_vendors', $tender->notify_specific_vendors ? implode(',', (array)$tender->notify_specific_vendors) : '') }}">
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-info-circle text-purple-500 mr-1"></i>
                        Only these vendors will see and apply. They also receive the publish notification email.
                    </p>
                </div>
            </div>

            {{-- Application Field Requirements --}}
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-tasks text-blue-600"></i> Application Options
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach([
                        ['name'=>'require_bid_amount','label'=>'Bid Amount','icon'=>'fa-rupee-sign','color'=>'#d97706'],
                        ['name'=>'require_technical_bid','label'=>'Technical Bid','icon'=>'fa-file-alt','color'=>'#7c3aed'],
                        ['name'=>'require_financial_bid','label'=>'Financial Bid','icon'=>'fa-coins','color'=>'#059669'],
                        ['name'=>'require_contract_signing','label'=>'Contract Signing','icon'=>'fa-signature','color'=>'#0891b2'],
                    ] as $opt)
                    <label class="flex items-start gap-3 p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition">
                        <input type="checkbox" name="{{ $opt['name'] }}" value="1"
                               {{ old($opt['name'], $tender->{$opt['name']} ?? true) ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 accent-blue-600">
                        <div>
                            <div class="text-sm font-semibold text-gray-800 flex items-center gap-1">
                                <i class="fas {{ $opt['icon'] }} text-xs" style="color:{{ $opt['color'] }}"></i> {{ $opt['label'] }}
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Email Master Toggle --}}
            <div class="mt-6 pt-4 border-t">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-envelope text-fuchsia-600"></i> Email Notifications
                </h4>
                <label id="editEmailTile" class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer transition
                                                  {{ old('email_notifications_enabled', $tender->email_notifications_enabled) ? 'border-fuchsia-400 bg-fuchsia-50' : 'border-gray-200' }}">
                    <input type="checkbox" name="email_notifications_enabled" value="1"
                           id="editEmailToggle"
                           {{ old('email_notifications_enabled', $tender->email_notifications_enabled) ? 'checked' : '' }}
                           class="mt-1 w-4 h-4 accent-fuchsia-600"
                           onchange="editToggleEmailSection(this.checked)">
                    <div>
                        <div class="font-semibold text-gray-800 flex items-center gap-1">
                            <i class="fas fa-envelope text-fuchsia-600 text-sm"></i> Send Email Notifications
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">If unchecked, no emails are triggered for this tender (publish, close, etc.)</p>
                    </div>
                </label>
            </div>

            {{-- Vendor Notifications --}}
            <div class="mt-6 pt-4 border-t" id="editNotifySection"
                 style="{{ old('email_notifications_enabled', $tender->email_notifications_enabled) ? '' : 'display:none;' }}">
                <h4 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-bell text-cyan-600"></i> Vendor Notifications on Publish
                </h4>

                {{-- Master notify toggle --}}
                <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;background:#f8fafc;"
                       id="editNotifyToggleTile">
                    <input type="checkbox" name="notify_vendors_on_create" value="1" id="editNotifyToggle"
                           {{ old('notify_vendors_on_create', $tender->notify_vendors_on_create) ? 'checked' : '' }}
                           style="accent-color:#0891b2;width:16px;height:16px;flex-shrink:0;margin-top:2px;"
                           onchange="editToggleNotifyOptions(this.checked)">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-envelope" style="color:#0891b2;"></i>
                            Send email notification to vendors when this tender is published
                        </div>
                        <div style="font-size:12px;color:#94a3b8;margin-top:3px;">
                            If unchecked, <strong>no email will be sent</strong> to any vendor automatically on publish.
                        </div>
                    </div>
                </label>

                {{-- Who to notify panel --}}
                <div id="editNotifyOptions" style="{{ old('notify_vendors_on_create', $tender->notify_vendors_on_create) ? '' : 'display:none;' }}margin-top:18px;padding-left:4px;">
                    <p style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">
                        Who should receive the email?
                    </p>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">

                        @php
                            $savedMode = old('notify_vendor_mode', (($tender->notify_specific_vendors && count((array)$tender->notify_specific_vendors) > 0) ? 'specific' : 'all'));
                        @endphp

                        <label style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;background:#f8fafc;" id="editModeAllTile">
                            <input type="radio" name="notify_vendor_mode" value="all"
                                   {{ $savedMode === 'all' ? 'checked' : '' }}
                                   style="accent-color:#0891b2;flex-shrink:0;margin-top:3px;"
                                   onchange="editToggleSpecificVendors(false)">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:#0f172a;">
                                    <i class="fas fa-users" style="color:#16a34a;margin-right:5px;"></i>
                                    All vendors in the selected categories
                                </div>
                                <div style="font-size:12px;color:#94a3b8;margin-top:2px;">
                                    Every approved vendor in the chosen categories will be notified on publish.
                                </div>
                            </div>
                        </label>

                        <label style="display:flex;align-items:flex-start;gap:12px;padding:13px 16px;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;background:#f8fafc;" id="editModeSpecificTile">
                            <input type="radio" name="notify_vendor_mode" value="specific"
                                   {{ $savedMode === 'specific' ? 'checked' : '' }}
                                   style="accent-color:#0891b2;flex-shrink:0;margin-top:3px;"
                                   onchange="editToggleSpecificVendors(true)">
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:#0f172a;">
                                    <i class="fas fa-user-check" style="color:#7c3aed;margin-right:5px;"></i>
                                    Only selected vendors
                                </div>
                                <div style="font-size:12px;color:#94a3b8;margin-top:2px;">
                                    Manually choose which vendors receive the notification email.
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Specific vendor picker --}}
                    <div id="editSpecificVendorsBox" style="{{ $savedMode === 'specific' ? '' : 'display:none;' }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                            <label style="font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">
                                Select Vendors
                            </label>
                            <button type="button" onclick="editLoadNotifyVendors()"
                                    style="font-size:12px;padding:6px 14px;background:#1a56db;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                                <i class="fas fa-sync-alt"></i> Load Vendors from Selected Categories
                            </button>
                        </div>
                        <div id="editNotifyVendorCheckboxList"
                             style="max-height:220px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:12px;background:#f8fafc;">
                            <p style="font-size:12px;color:#94a3b8;margin:0;">
                                <i class="fas fa-arrow-up" style="margin-right:4px;"></i>
                                Select categories above, then click "Load Vendors".
                            </p>
                        </div>
                        <input type="hidden" name="notify_specific_vendors" id="editNotifySpecificVendorsInput"
                               value="{{ old('notify_specific_vendors', $tender->notify_specific_vendors ? implode(',', (array)$tender->notify_specific_vendors) : '') }}">
                        <p style="font-size:12px;color:#94a3b8;margin-top:6px;">
                            Pre-selected vendors from the saved tender are highlighted above after loading.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <a href="{{ route('tender-owner.tenders.index') }}" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-save mr-2"></i>Update Tender
                </button>
            </div>
        </form>
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
            <div class="grid grid-cols-3 gap-4 mb-4">
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
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Required?</label>
        <select id="isRequired" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="1">Yes - Required</option>
            <option value="0">No - Optional</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Determines Eligibility?</label>
        <select id="determinesEligibility" onchange="toggleOptionsFields()" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <option value="0">No - Just Information</option>
            <option value="1">Yes - Determines Eligibility</option>
        </select>
    </div>
</div>
            <div id="optionsField" class="mb-4" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Options (comma separated)</label>
                <input type="text" id="questionOptions" placeholder="Option 1, Option 2, Option 3" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div id="eligibleOptionsField" class="mb-4" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Eligible Options <span class="text-gray-400 font-normal text-xs">(comma separated — answers that pass eligibility)</span></label>
                <input type="text" id="eligibleOptions" placeholder="Option 1, Option 2" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <p class="text-xs text-gray-400 mt-1">Vendors who answer with one of these options will pass the eligibility check.</p>
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
let documentCounter = {{ isset($existingDocs) ? $existingDocs->count() + 1 : 1 }};
let removedQuestions = [];

// ==================== DOCUMENT FUNCTIONS ====================

function addDocumentUpload() {
    const container = document.getElementById('newDocumentsContainer');
    const newId = documentCounter;
    const div = document.createElement('div');
    div.className = 'document-upload-item mb-3';
    div.id = `doc-${newId}`;
    div.innerHTML = `
        <div class="flex items-center space-x-3">
            <input type="file" name="new_documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
            <select name="document_categories[]" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="bidding_document">Bidding Document</option>
                <option value="tender_notice">Tender Notice</option>
                <option value="specification">Specification</option>
                <option value="terms_conditions">Terms & Conditions</option>
                <option value="boq">Bill of Quantities</option>
                <option value="drawing">Drawing</option>
                <option value="other">Other</option>
            </select>
            <label class="flex items-center">
                <input type="checkbox" name="is_mandatory[]" value="1" class="mr-2"> Mandatory
            </label>
            <button type="button" onclick="removeDocumentUpload(this)" class="text-red-600 hover:text-red-800" title="Remove this field">
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
        // Use tender-owner route for document deletion
        fetch(`/tender-owner/tenders/documents/${documentId}`, {
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

// Tender type toggle
document.querySelectorAll('input[name="tender_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const postalFields = document.getElementById('postalFields');
        if (postalFields) {
            postalFields.style.display = this.value === 'postal' ? 'block' : 'none';
        }
    });
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
                document.getElementById('eligibilityModalTitle').innerText = 'Edit Eligibility Question';
                document.getElementById('editingQuestionId').value = questionId;
                document.getElementById('questionText').value = data.question.question;
                document.getElementById('questionDescription').value = data.question.description || '';
                document.getElementById('fieldType').value = data.question.field_type;
                document.getElementById('isRequired').value = data.question.is_required ? '1' : '0';
                document.getElementById('determinesEligibility').value = data.question.determines_eligibility ? '1' : '0';
                document.getElementById('questionOptions').value = data.question.options ? (Array.isArray(data.question.options) ? data.question.options.join(', ') : data.question.options) : '';
                document.getElementById('eligibleOptions').value = data.question.eligible_options ? (Array.isArray(data.question.eligible_options) ? data.question.eligible_options.join(', ') : data.question.eligible_options) : '';
                document.getElementById('maxFileSize').value = data.question.max_size || 5120;
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
    const optionsField = document.getElementById('optionsField');
    const fileSizeField = document.getElementById('fileSizeField');
    const eligibleOptionsField = document.getElementById('eligibleOptionsField');
    const determinesEligibility = document.getElementById('determinesEligibility');

    const hasOptions = (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox');
    optionsField.style.display = hasOptions ? 'block' : 'none';
    fileSizeField.style.display = fieldType === 'file' ? 'block' : 'none';

    if (determinesEligibility) {
        // Disable determines_eligibility select when field type is not option-based
        const opts = determinesEligibility.querySelectorAll ? null : null;
        if (!hasOptions) {
            determinesEligibility.value = '0';
        }
        determinesEligibility.disabled = !hasOptions;
        determinesEligibility.closest('div').style.opacity = hasOptions ? '1' : '0.4';
    }

    eligibleOptionsField.style.display = (hasOptions && determinesEligibility && determinesEligibility.value === '1') ? 'block' : 'none';
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
        is_required: document.getElementById('isRequired').value === '1',
        determines_eligibility: document.getElementById('determinesEligibility').value === '1',
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
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  location.reload();
              } else {
                  alert('Failed to update question: ' + (data.message || 'Unknown error'));
              }
          });
    } else {
        const container = document.getElementById('newEligibilityQuestions');
        const index = Date.now();
        const div = document.createElement('div');
        div.className = 'new-question-item bg-gray-50 rounded-lg p-4 mb-4 border border-green-200';
        div.innerHTML = `
            <input type="hidden" name="new_questions[${index}][question]" value="${escapeHtml(questionData.question)}">
            <input type="hidden" name="new_questions[${index}][description]" value="${escapeHtml(questionData.description)}">
            <input type="hidden" name="new_questions[${index}][field_type]" value="${questionData.field_type}">
            <input type="hidden" name="new_questions[${index}][is_required]" value="${questionData.is_required ? 1 : 0}">
            <input type="hidden" name="new_questions[${index}][determines_eligibility]" value="${questionData.determines_eligibility ? 1 : 0}">
            <input type="hidden" name="new_questions[${index}][options]" value="${escapeHtml(questionData.options)}">
            <input type="hidden" name="new_questions[${index}][eligible_options]" value="${escapeHtml(questionData.eligible_options)}">
            <input type="hidden" name="new_questions[${index}][max_size]" value="${questionData.max_size}">
            <div class="flex justify-between items-center mb-3">
                <h5 class="font-semibold text-gray-800">New Question</h5>
                <button type="button" onclick="this.closest('.new-question-item').remove()" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-3">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Question</label><p class="text-gray-900">${escapeHtml(questionData.question)}</p></div>
                ${questionData.description ? `<div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><p class="text-gray-500 text-sm">${escapeHtml(questionData.description)}</p></div>` : ''}
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Field Type</label><p class="text-gray-900">${getFieldTypeLabel(questionData.field_type)}</p></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Required</label><p class="text-gray-900">${questionData.is_required ? 'Yes' : 'No'}</p></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Determines Eligibility</label><p class="text-gray-900">${questionData.determines_eligibility ? 'Yes' : 'No'}</p></div>
                </div>
                ${questionData.options ? `<div><label class="block text-sm font-medium text-gray-700 mb-1">Options</label><p class="text-gray-500 text-sm">${escapeHtml(questionData.options)}</p></div>` : ''}
            </div>
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

function ownerEditTogglePrivate(show) {
    document.getElementById('ownerEditPrivateBox').classList.toggle('hidden', !show);
}

function ownerEditLoadPrivateVendors() {
    const checkedCats = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked')).map(c => c.value);
    if (!checkedCats.length) { alert('Please select at least one category first.'); return; }
    const box = document.getElementById('ownerEditPrivateList');
    box.innerHTML = '<p class="text-xs text-gray-400">Loading…</p>';
    const existingIds = (document.getElementById('ownerEditPrivateInput').value || '').split(',').filter(Boolean);
    const vendorMap = {};
    Promise.all(checkedCats.map(catId =>
        fetch(`/tender-owner/categories/${catId}/vendors`).then(r => r.json()).catch(() => [])
    )).then(results => {
        results.flat().forEach(v => { vendorMap[v.id] = v; });
        const vendors = Object.values(vendorMap);
        if (!vendors.length) { box.innerHTML = '<p class="text-xs text-gray-400">No vendors found in selected categories.</p>'; return; }
        box.innerHTML = vendors.map(v => `
            <label class="flex items-center gap-2 mb-1.5 cursor-pointer text-sm">
                <input type="checkbox" class="owner-edit-private-cb w-4 h-4 accent-purple-600" value="${v.id}"
                       ${existingIds.includes(String(v.id)) ? 'checked' : ''}
                       onchange="updateOwnerEditPrivateIds()">
                <span class="text-gray-800">${v.company_name || v.name}</span>
                <span class="text-xs text-gray-400">${v.email || ''}</span>
            </label>`).join('');
        updateOwnerEditPrivateIds();
    });
}

function updateOwnerEditPrivateIds() {
    const ids = Array.from(document.querySelectorAll('.owner-edit-private-cb:checked')).map(c => c.value);
    document.getElementById('ownerEditPrivateInput').value = ids.join(',');
}

// ── Email notifications master toggle ──
function editToggleEmailSection(enabled) {
    const tile    = document.getElementById('editEmailTile');
    const section = document.getElementById('editNotifySection');
    tile.style.borderColor   = enabled ? '#e879f9' : '';
    tile.style.backgroundColor = enabled ? '#fdf4ff' : '';
    section.style.display = enabled ? '' : 'none';
    if (!enabled) {
        const toggle = document.getElementById('editNotifyToggle');
        if (toggle) { toggle.checked = false; editToggleNotifyOptions(false); }
    }
}

// ── Vendor-notify section toggle ──
function editToggleNotifyOptions(show) {
    document.getElementById('editNotifyOptions').style.display = show ? '' : 'none';
    const tile = document.getElementById('editNotifyToggleTile');
    if (tile) tile.style.borderColor = show ? '#0891b2' : '#e2e8f0';
}

// ── Show/hide specific-vendor picker ──
function editToggleSpecificVendors(show) {
    document.getElementById('editSpecificVendorsBox').style.display = show ? '' : 'none';
}

// ── AJAX load vendors for notification picker (same pattern as create view) ──
function editLoadNotifyVendors() {
    const catIds    = Array.from(document.querySelectorAll('[name="category_ids[]"]:checked')).map(c => c.value);
    const container = document.getElementById('editNotifyVendorCheckboxList');
    const hidden    = document.getElementById('editNotifySpecificVendorsInput');
    if (!catIds.length) {
        container.innerHTML = '<p style="font-size:12px;color:#9ca3af;margin:0;">Please select at least one category first.</p>';
        return;
    }
    container.innerHTML = '<p style="font-size:12px;color:#6b7280;margin:0;">Loading...</p>';
    Promise.all(catIds.map(id => fetch(`/tender-owner/categories/${id}/vendors`).then(r => r.json())))
        .then(results => {
            const seen = new Set(), vendors = [];
            results.flat().forEach(v => { if (!seen.has(v.id)) { seen.add(v.id); vendors.push(v); } });
            if (!vendors.length) {
                container.innerHTML = '<p style="font-size:12px;color:#9ca3af;margin:0;">No vendors found in the selected categories.</p>';
                return;
            }
            const current = (hidden.value || '').split(',').map(s => s.trim()).filter(Boolean);
            container.innerHTML = vendors.map(v => `
                <label style="display:flex;align-items:center;gap:8px;padding:6px 0;cursor:pointer;">
                    <input type="checkbox" class="edit-notify-cb" value="${v.id}"
                           ${current.includes(String(v.id)) ? 'checked' : ''}
                           onchange="updateEditNotifyIds()"
                           style="accent-color:#0891b2;width:14px;height:14px;">
                    <span style="font-size:13px;color:#1e293b;">${v.company_name} <span style="color:#94a3b8;font-size:11px;">(${v.vendor_code})</span></span>
                </label>`).join('');
            updateEditNotifyIds();
        })
        .catch(() => { container.innerHTML = '<p style="font-size:12px;color:#ef4444;margin:0;">Failed to load vendors.</p>'; });
}

function updateEditNotifyIds() {
    const ids = Array.from(document.querySelectorAll('.edit-notify-cb:checked')).map(c => c.value);
    document.getElementById('editNotifySpecificVendorsInput').value = ids.join(',');
}

// ── Init on page load ──
document.addEventListener('DOMContentLoaded', function () {
    editToggleEmailSection(document.getElementById('editEmailToggle').checked);
    editToggleNotifyOptions(document.getElementById('editNotifyToggle')?.checked ?? false);
});
</script>
@endsection