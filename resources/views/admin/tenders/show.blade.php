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
        @if($tender->start_date)
        <div>
            <label class="text-xs text-gray-500 uppercase">Start Date</label>
            <p class="text-gray-900">{{ $tender->start_date->format('d M, Y') }}</p>
        </div>
        @endif
        <div>
            <label class="text-xs text-gray-500 uppercase">End Date</label>
            <p class="text-gray-900">{{ $tender->submission_deadline ? date('d M, Y', strtotime($tender->submission_deadline)) : 'N/A' }}</p>
        </div>
        @if($tender->opening_date)
        <div>
            <label class="text-xs text-gray-500 uppercase">Opening Date</label>
            <p class="text-gray-900">{{ date('d M, Y', strtotime($tender->opening_date)) }}</p>
        </div>
        @endif
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
                        <div class="ml-6 flex flex-wrap items-center gap-2 text-xs text-gray-500 mt-1">
                            <span class="px-2 py-0.5 bg-gray-100 rounded-full"><i class="fas fa-tag mr-1"></i>{{ ucfirst(str_replace('_', ' ', $question->field_type)) }}</span>
                            @if($question->determines_eligibility)
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-medium"><i class="fas fa-shield-alt mr-1"></i>Determines Eligibility</span>
                            @endif
                            @if(in_array($question->field_type, ['select', 'radio', 'checkbox']) && $question->options)
                                @php
                                    $optParts = collect($question->options)->map(function($o) {
                                        $label = is_array($o) ? ($o['label'] ?? '') : (string)$o;
                                        $eligible = is_array($o) ? ($o['eligible'] ?? false) : false;
                                        return ['label' => $label, 'eligible' => $eligible];
                                    })->filter(fn($o) => $o['label'] !== '');
                                @endphp
                                <span class="px-2 py-0.5 bg-gray-100 rounded-full">
                                    <i class="fas fa-list mr-1"></i>
                                    @foreach($optParts as $op)
                                        @if($op['eligible'])
                                            <span class="text-green-700 font-medium">{{ $op['label'] }}✓</span>
                                        @else
                                            {{ $op['label'] }}
                                        @endif
                                        @if(!$loop->last), @endif
                                    @endforeach
                                </span>
                            @endif
                            @if(in_array($question->field_type, ['text','textarea','number']) && $question->determines_eligibility && $question->eligibility_condition)
                                @php $cond = is_array($question->eligibility_condition) ? $question->eligibility_condition : []; @endphp
                                @if(!empty($cond['operator']))
                                    @php
                                        $opLabel = match($cond['operator']) {
                                            'gte' => '≥', 'lte' => '≤', 'gt' => '>', 'lt' => '<',
                                            'eq' => '=', 'between' => 'between', default => $cond['operator']
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 bg-green-50 text-green-700 border border-green-200 rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i>Eligible if {{ $opLabel }} {{ $cond['value'] ?? '' }}
                                        @if(($cond['operator'] ?? '') === 'between') and {{ $cond['value2'] ?? '' }} @endif
                                    </span>
                                @endif
                            @endif
                            @if($question->field_type == 'file')
                                <span class="px-2 py-0.5 bg-gray-100 rounded-full"><i class="fas fa-file-upload mr-1"></i>Max: {{ round(($question->max_size ?? 0) / 1024, 2) }} MB</span>
                            @endif
                        </div>
                    </div>
                    @if($tender->status === 'draft')
                    <div class="flex space-x-2">
                        <button onclick="editEligibilityQuestion({{ $question->id }})" class="text-blue-600 hover:text-blue-800" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="removeEligibilityQuestion({{ $question->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @if($tender->status === 'draft')
        <div class="mt-3">
            <button onclick="addEligibilityQuestion()" class="text-purple-600 hover:text-purple-800 text-sm">
                <i class="fas fa-plus mr-1"></i> Add New Question
            </button>
        </div>
        @endif
    </div>
    @else
    <div class="mt-4 pt-4 border-t">
        <div class="flex justify-between items-center">
            <h4 class="font-semibold text-gray-800 mb-3">
                <i class="fas fa-question-circle mr-2 text-purple-600"></i>Eligibility Questions
            </h4>
            @if($tender->status === 'draft')
            <button onclick="addEligibilityQuestion()" class="text-purple-600 hover:text-purple-800 text-sm">
                <i class="fas fa-plus mr-1"></i> Add Question
            </button>
            @endif
        </div>
        <div class="bg-gray-50 rounded-lg p-8 text-center text-gray-500">
            <i class="fas fa-question-circle text-4xl mb-2"></i>
            <p>No eligibility questions added yet.</p>
            @if($tender->status === 'draft')
            <p class="text-sm">Click "Add Question" to create eligibility criteria for vendors.</p>
            @endif
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
    
    <!-- Analytics Link -->
    @php $rp = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
    <div class="mt-4 pt-4 border-t flex justify-end">
        <a href="{{ route("{$rp}.tenders.analytics", $tender->id) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <i class="fas fa-chart-line"></i> View Analytics &amp; Visitor Log
        </a>
    </div>

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

    @if(auth()->user()->hasRole('super-admin') || $tender->owner_id === auth()->id())
        @include('partials.tender-query-thread', ['tender' => $tender])
    @endif
</div>

