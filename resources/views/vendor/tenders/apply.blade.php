@extends('layouts.dashboard')

@section('title', 'Apply — ' . $tender->title)
@section('header', 'Apply for Tender')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- =================== TOP NAV =================== --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('vendor.tenders.show', $tender->id) }}"
           class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Back to Tender
        </a>
        {{-- step progress --}}
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <span class="flex items-center gap-1">
                <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">1</span>
                <span>Tender Details &amp; Documents</span>
            </span>
            @if($hasQuestions)
            <span class="text-gray-300">—</span>
            <span id="step2Label" class="flex items-center gap-1 opacity-50">
                <span class="w-5 h-5 rounded-full bg-gray-300 text-white flex items-center justify-center text-xs font-bold" id="step2Dot">2</span>
                <span>Eligibility Check</span>
            </span>
            <span class="text-gray-300">—</span>
            <span id="step3Label" class="flex items-center gap-1 opacity-50">
                <span class="w-5 h-5 rounded-full bg-gray-300 text-white flex items-center justify-center text-xs font-bold" id="step3Dot">3</span>
                <span>Application Form</span>
            </span>
            @else
            <span class="text-gray-300">—</span>
            <span id="step2Label" class="flex items-center gap-1 opacity-50">
                <span class="w-5 h-5 rounded-full bg-gray-300 text-white flex items-center justify-center text-xs font-bold" id="step2Dot">2</span>
                <span>Application Form</span>
            </span>
            @endif
        </div>
    </div>

    {{-- =================== TENDER DETAILS CARD =================== --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        {{-- Header band --}}
        <div class="px-6 py-5 bg-gradient-to-r from-blue-700 to-indigo-700">
            <div class="flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 bg-white bg-opacity-20 text-white text-xs rounded-full">{{ $tender->tender_number }}</span>
                        @if($tender->isPostal())
                        <span class="px-2 py-0.5 bg-yellow-400 text-yellow-900 text-xs rounded-full font-semibold">Postal Tender</span>
                        @else
                        <span class="px-2 py-0.5 bg-green-400 text-green-900 text-xs rounded-full font-semibold">Online Tender</span>
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-white leading-snug">{{ $tender->title }}</h2>
                    @if($tender->categories && $tender->categories->isNotEmpty())
                    <div class="flex gap-1 mt-2 flex-wrap">
                        @foreach($tender->categories as $cat)
                        <span class="px-2 py-0.5 bg-white bg-opacity-15 text-blue-100 text-xs rounded-full">{{ $cat->name }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="text-right text-sm">
                    @if($tender->getRemainingDays() !== null)
                    <div class="bg-white bg-opacity-20 rounded-lg px-3 py-2">
                        <div class="text-blue-100 text-xs">Deadline</div>
                        <div class="text-white font-bold text-base">{{ $tender->submission_deadline->format('d M Y') }}</div>
                        <div class="text-yellow-300 text-xs font-semibold">{{ $tender->getRemainingDays() }} days left</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Details grid --}}
        <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 border-b border-gray-100">
            @if($tender->require_estimated_value && $tender->estimated_value > 0)
            <div class="text-center">
                <div class="text-xs text-gray-500 mb-0.5">Estimated Value</div>
                <div class="font-bold text-green-700 text-sm">{{ $tender->getFormattedEstimatedValue() }}</div>
            </div>
            @endif
            @if($tender->require_emd && $tender->emda_amount > 0)
            <div class="text-center">
                <div class="text-xs text-gray-500 mb-0.5">EMD Amount</div>
                <div class="font-bold text-orange-600 text-sm">{{ $tender->getFormattedEmdaAmount() }}</div>
            </div>
            @endif
            <div class="text-center">
                <div class="text-xs text-gray-500 mb-0.5">Published</div>
                <div class="font-semibold text-gray-700 text-sm">{{ $tender->published_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 mb-0.5">Opening Date</div>
                <div class="font-semibold text-gray-700 text-sm">{{ $tender->opening_date?->format('d M Y') ?? '—' }}</div>
            </div>
        </div>

        {{-- Description + Requirements --}}
        <div class="p-6 space-y-4">
            @if($tender->description)
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-1">
                    <i class="fas fa-align-left text-blue-500 text-xs"></i> Description
                </h4>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $tender->description }}</p>
            </div>
            @endif

            @if($tender->eligibility_criteria)
            <div class="bg-purple-50 border border-purple-100 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-purple-800 mb-1 flex items-center gap-1">
                    <i class="fas fa-clipboard-check text-purple-500 text-xs"></i> Eligibility Criteria
                </h4>
                <p class="text-sm text-purple-700 leading-relaxed">{{ $tender->eligibility_criteria }}</p>
            </div>
            @endif

            @if($tender->technical_requirements)
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-800 mb-1 flex items-center gap-1">
                    <i class="fas fa-cogs text-blue-500 text-xs"></i> Technical Requirements
                </h4>
                <p class="text-sm text-blue-700 leading-relaxed">{{ $tender->technical_requirements }}</p>
            </div>
            @endif

            @if($tender->isPostal())
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 flex items-start gap-3">
                <i class="fas fa-envelope text-yellow-600 text-lg mt-0.5"></i>
                <div>
                    <p class="font-semibold text-yellow-800 text-sm">Postal Submission Address</p>
                    <p class="text-sm text-gray-700 mt-1 font-medium">{{ $tender->postal_address }}</p>
                    @if($tender->contact_person || $tender->contact_phone || $tender->contact_email)
                    <div class="mt-2 text-xs text-yellow-700 space-y-0.5">
                        @if($tender->contact_person)<span class="mr-3"><i class="fas fa-user mr-1"></i>{{ $tender->contact_person }}</span>@endif
                        @if($tender->contact_phone)<span class="mr-3"><i class="fas fa-phone mr-1"></i>{{ $tender->contact_phone }}</span>@endif
                        @if($tender->contact_email)<span><i class="fas fa-at mr-1"></i>{{ $tender->contact_email }}</span>@endif
                    </div>
                    @endif
                    @if($tender->postal_receipt_deadline)
                    <div class="mt-2 text-xs font-semibold text-red-700 flex items-center gap-1">
                        <i class="fas fa-calendar-times"></i>
                        Physical documents must be received by: {{ $tender->postal_receipt_deadline->format('d M Y') }}
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- =================== TENDER DOCUMENTS =================== --}}
    @if($tender->documents && $tender->documents->isNotEmpty())
        @if(!$hasQuestions)
        {{-- No eligibility check: show documents immediately in step 1 --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-teal-600 to-cyan-600 flex justify-between items-center">
                <h3 class="text-white font-semibold flex items-center gap-2">
                    <i class="fas fa-file-alt"></i> Tender Documents
                </h3>
                <span class="text-teal-100 text-xs">{{ $tender->documents->count() }} file(s) — read all before applying</span>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($tender->documents as $doc)
                <div class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 hover:border-teal-300 hover:bg-teal-50 transition">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $doc->isPdf() ? 'bg-red-100 text-red-600' : ($doc->isImage() ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600') }}">
                        <i class="fas {{ $doc->isPdf() ? 'fa-file-pdf' : ($doc->isImage() ? 'fa-file-image' : 'fa-file-word') }} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $doc->title }}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                            <span>{{ $doc->getCategoryLabel() }}</span>
                            <span>·</span>
                            <span>{{ $doc->getFileSizeFormatted() }}</span>
                            @if($doc->is_mandatory)<span class="text-red-500 font-semibold">· Mandatory</span>@endif
                        </div>
                    </div>
                    <a href="{{ route('vendor.tenders.download', [$tender->id, $doc->id]) }}"
                       class="flex-shrink-0 px-3 py-1.5 bg-teal-600 text-white text-xs rounded-lg hover:bg-teal-700 transition flex items-center gap-1">
                        <i class="fas fa-download text-xs"></i> Download
                    </a>
                    <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank"
                       class="flex-shrink-0 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs rounded-lg hover:bg-gray-200 transition flex items-center gap-1">
                        <i class="fas fa-eye text-xs"></i> View
                    </a>
                </div>
                @endforeach
            </div>
            <div class="px-6 pb-4">
                <p class="text-xs text-gray-500 flex items-center gap-1">
                    <i class="fas fa-info-circle text-blue-400"></i>
                    Please download and review all mandatory documents before proceeding with your application.
                </p>
            </div>
        </div>
        @else
        {{-- Has eligibility check: show a hint that docs will unlock after eligibility --}}
        <div class="bg-white rounded-xl shadow-sm p-4 border border-dashed border-gray-300 flex items-center gap-3">
            <i class="fas fa-lock text-gray-400 text-xl"></i>
            <div>
                <p class="text-sm font-semibold text-gray-600">Tender Documents — {{ $tender->documents->count() }} file(s)</p>
                <p class="text-xs text-gray-400 mt-0.5">Documents will be available to download after you pass the eligibility check below.</p>
            </div>
        </div>
        @endif
    @endif

    {{-- "Proceed to Apply" button (scrolls to step 2) --}}
    <div class="flex justify-center">
        <button type="button" onclick="document.getElementById('step2Section').scrollIntoView({behavior:'smooth'})"
            class="px-8 py-3 bg-blue-700 text-white rounded-xl font-semibold text-sm hover:bg-blue-800 transition flex items-center gap-2 shadow-md">
            <i class="fas fa-arrow-down"></i>
            {{ $hasQuestions ? 'Proceed to Eligibility Check' : 'Proceed to Application Form' }}
        </button>
    </div>

    {{-- =================== STEP 2: ELIGIBILITY CHECK =================== --}}
    <div id="step2Section">
    @if($hasQuestions)
    <div id="eligibilitySection" class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 flex justify-between items-center">
            <div>
                <h4 class="text-white font-semibold flex items-center gap-2">
                    <i class="fas fa-clipboard-check"></i>
                    Step 2 — Eligibility Check
                </h4>
                <p class="text-purple-100 text-xs mt-0.5">Answer all questions to verify your eligibility before applying.</p>
            </div>
            <span class="bg-white bg-opacity-20 text-white text-xs px-2 py-1 rounded-full">{{ $tender->eligibilityQuestions->count() }} question(s)</span>
        </div>

        {{-- Result banner --}}
        <div id="eligResult" class="hidden px-6 pt-4"></div>

        <div class="p-6">
            <p class="text-sm text-gray-500 mb-5 flex items-center gap-1">
                <i class="fas fa-info-circle text-purple-400"></i>
                Questions tagged <span class="mx-1 px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">Eligibility</span> determine your qualification for this tender.
            </p>

            @foreach($tender->eligibilityQuestions as $index => $question)
            <div class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-start gap-2">
                    <span class="text-xs text-gray-400 font-mono mt-1 w-5 flex-shrink-0">{{ $index + 1 }}.</span>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-800 mb-1">
                            {{ $question->question }}
                            @if($question->is_required)<span class="text-red-500 ml-1">*</span>@endif
                            @if($question->determines_eligibility)
                                <span class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">Eligibility</span>
                            @endif
                        </label>
                        @if($question->description)
                            <p class="text-xs text-gray-500 mb-2">{{ $question->description }}</p>
                        @endif

                        @switch($question->field_type)
                            @case('text')
                                <input type="text" name="eligibility_answers[{{ $question->id }}]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                                    {{ $question->is_required ? 'required' : '' }} placeholder="Enter your answer">
                                @break
                            @case('textarea')
                                <textarea name="eligibility_answers[{{ $question->id }}]" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                                    {{ $question->is_required ? 'required' : '' }} placeholder="Enter your detailed answer"></textarea>
                                @break
                            @case('number')
                                <input type="number" name="eligibility_answers[{{ $question->id }}]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                                    {{ $question->is_required ? 'required' : '' }} placeholder="Enter a number">
                                @break
                            @case('date')
                                <input type="date" name="eligibility_answers[{{ $question->id }}]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                                    {{ $question->is_required ? 'required' : '' }}>
                                @break
                            @case('select')
                                <select name="eligibility_answers[{{ $question->id }}]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm"
                                    {{ $question->is_required ? 'required' : '' }}>
                                    <option value="">-- Select an option --</option>
                                    @foreach($question->options as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                                @break
                            @case('radio')
                                <div class="space-y-2 mt-1">
                                    @foreach($question->options as $option)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="eligibility_answers[{{ $question->id }}]" value="{{ $option }}"
                                                class="text-purple-600 focus:ring-purple-500"
                                                {{ $question->is_required ? 'required' : '' }}>
                                            <span class="text-sm text-gray-700">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break
                            @case('checkbox')
                                <div class="space-y-2 mt-1">
                                    @foreach($question->options as $option)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="eligibility_answers[{{ $question->id }}][]" value="{{ $option }}"
                                                class="rounded text-purple-600 focus:ring-purple-500">
                                            <span class="text-sm text-gray-700">{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break
                            @case('file')
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-purple-400 transition cursor-pointer"
                                     onclick="document.getElementById('elig_file_{{ $question->id }}').click()">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                                    <p class="text-xs text-gray-500">Click to upload</p>
                                    <input type="file" id="elig_file_{{ $question->id }}"
                                        name="eligibility_files[{{ $question->id }}]"
                                        class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                        {{ $question->is_required ? 'required' : '' }}
                                        onchange="showFileName(this)">
                                    <p class="text-xs text-gray-400 mt-1">PDF, DOC, JPG, PNG — max 5 MB</p>
                                </div>
                                @break
                        @endswitch
                    </div>
                </div>
            </div>
            @endforeach

            <div class="flex justify-end pt-2">
                <button type="button" id="checkEligBtn"
                    class="px-6 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium text-sm flex items-center gap-2">
                    <i class="fas fa-clipboard-check"></i>Check My Eligibility
                </button>
            </div>
        </div>
    </div>
    @endif
    </div>{{-- #step2Section --}}

    {{-- =================== STEP 3 (or 2 if no questions): APPLICATION FORM =================== --}}
    <div id="applicationSection" class="{{ $hasQuestions ? 'hidden' : '' }}">

        {{-- Documents unlocked after eligibility (only shown when hasQuestions) --}}
        @if($hasQuestions && $tender->documents && $tender->documents->isNotEmpty())
        <div id="docsSection" class="hidden bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 bg-gradient-to-r from-teal-600 to-cyan-600 flex justify-between items-center">
                <h3 class="text-white font-semibold flex items-center gap-2">
                    <i class="fas fa-file-alt"></i> Tender Documents — Unlocked
                </h3>
                <span class="text-teal-100 text-xs">{{ $tender->documents->count() }} file(s) — read all before applying</span>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($tender->documents as $doc)
                <div class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 hover:border-teal-300 hover:bg-teal-50 transition">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $doc->isPdf() ? 'bg-red-100 text-red-600' : ($doc->isImage() ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600') }}">
                        <i class="fas {{ $doc->isPdf() ? 'fa-file-pdf' : ($doc->isImage() ? 'fa-file-image' : 'fa-file-word') }} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $doc->title }}</p>
                        <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                            <span>{{ $doc->getCategoryLabel() }}</span>
                            <span>·</span>
                            <span>{{ $doc->getFileSizeFormatted() }}</span>
                            @if($doc->is_mandatory)<span class="text-red-500 font-semibold">· Mandatory</span>@endif
                        </div>
                    </div>
                    <a href="{{ route('vendor.tenders.download', [$tender->id, $doc->id]) }}"
                       class="flex-shrink-0 px-3 py-1.5 bg-teal-600 text-white text-xs rounded-lg hover:bg-teal-700 transition flex items-center gap-1">
                        <i class="fas fa-download text-xs"></i> Download
                    </a>
                    <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank"
                       class="flex-shrink-0 px-3 py-1.5 bg-gray-100 text-gray-600 text-xs rounded-lg hover:bg-gray-200 transition flex items-center gap-1">
                        <i class="fas fa-eye text-xs"></i> View
                    </a>
                </div>
                @endforeach
            </div>
            <div class="px-6 pb-4">
                <p class="text-xs text-gray-500 flex items-center gap-1">
                    <i class="fas fa-info-circle text-blue-400"></i>
                    Please download and review all mandatory documents before submitting your application.
                </p>
            </div>
        </div>
        @endif

        <form action="{{ route('vendor.tenders.apply.submit', $tender->id) }}" method="POST"
              enctype="multipart/form-data" id="applicationForm">
            @csrf

            {{-- Carry eligibility answers as hidden fields (populated by JS) --}}
            <div id="eligibilityHiddenFields"></div>

            @error('eligibility')
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-4 text-sm">
                <i class="fas fa-times-circle mr-2"></i>{{ $message }}
            </div>
            @enderror

            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600">
                    <h4 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-file-alt"></i>
                        Step {{ $hasQuestions ? '3' : '2' }} — Application Details
                    </h4>
                    <p class="text-green-100 text-xs mt-0.5">Fill in your bid and proposal details below.</p>
                </div>
                <div class="p-6 space-y-6">

                    @php
                        $requireBid  = ($tender->require_bid_amount  ?? true) && $tender->isOnline();
                        $requireTech = ($tender->require_technical_bid ?? true) && $tender->isOnline();
                        $requireFin  = ($tender->require_financial_bid ?? true) && $tender->isOnline();
                    @endphp

                    {{-- Bid Amount — online only --}}
                    @if($requireBid)
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h5 class="font-semibold text-blue-800 mb-3 text-sm flex items-center gap-1">
                            <i class="fas fa-rupee-sign"></i> Bid Information
                        </h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bid Amount (₹) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">₹</span>
                                    <input type="number" name="bid_amount" step="0.01" required
                                        class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                                        placeholder="0.00">
                                </div>
                            </div>
                            @if($tender->require_emd && $tender->emda_amount > 0)
                            <div class="bg-white rounded-lg p-3 border border-blue-200 flex items-center gap-3">
                                <i class="fas fa-info-circle text-orange-500 text-lg"></i>
                                <div>
                                    <p class="text-xs text-gray-500">EMD Required</p>
                                    <p class="text-lg font-bold text-orange-600">₹{{ number_format($tender->emda_amount, 2) }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @elseif($tender->isOnline())
                    <input type="hidden" name="bid_amount" value="0">
                    @endif

                    {{-- Technical Proposal — online only --}}
                    @if($tender->isOnline())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Technical Proposal
                            @if($requireTech)<span class="text-red-500">*</span>@else<span class="text-xs text-gray-400 font-normal">(optional)</span>@endif
                        </label>
                        {{-- Toggle: Write or Upload --}}
                        <div class="flex gap-3 mb-2">
                            <button type="button" onclick="toggleProposal('tech','write')"
                                id="techWriteBtn"
                                class="px-3 py-1 text-xs rounded-full bg-green-600 text-white">Write</button>
                            <button type="button" onclick="toggleProposal('tech','upload')"
                                id="techUploadBtn"
                                class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700">Upload File</button>
                        </div>
                        <div id="techWriteBox">
                            <textarea name="technical_proposal" id="techProposalEditor" rows="5"
                                {{ $requireTech ? 'required' : '' }}
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                                placeholder="Describe your technical approach, methodology, and capabilities..."></textarea>
                        </div>
                        <div id="techUploadBox" class="hidden">
                            <input type="file" name="technical_proposal_file" accept=".pdf,.doc,.docx"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <p class="text-xs text-gray-400 mt-1">PDF or Word document, max 10 MB</p>
                        </div>
                    </div>

                    {{-- Financial Proposal — online only --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Financial Proposal
                            @if($requireFin)<span class="text-red-500">*</span>@else<span class="text-xs text-gray-400 font-normal">(optional)</span>@endif
                        </label>
                        <div class="flex gap-3 mb-2">
                            <button type="button" onclick="toggleProposal('fin','write')"
                                id="finWriteBtn"
                                class="px-3 py-1 text-xs rounded-full bg-green-600 text-white">Write</button>
                            <button type="button" onclick="toggleProposal('fin','upload')"
                                id="finUploadBtn"
                                class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700">Upload File</button>
                        </div>
                        <div id="finWriteBox">
                            <textarea name="financial_proposal" id="finProposalEditor" rows="4"
                                {{ $requireFin ? 'required' : '' }}
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                                placeholder="Provide financial breakdown, payment terms, and cost structure..."></textarea>
                        </div>
                        <div id="finUploadBox" class="hidden">
                            <input type="file" name="financial_proposal_file" accept=".pdf,.doc,.docx"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <p class="text-xs text-gray-400 mt-1">PDF or Word document, max 10 MB</p>
                        </div>
                    </div>
                    @endif

                    {{-- Online: document upload --}}
                    @if($tender->isOnline())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-paperclip text-green-600 mr-1"></i>Supporting Documents
                            <span class="text-xs text-gray-400 font-normal ml-1">(PDF, DOC, JPG, PNG — max 10 MB each)</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-green-400 transition">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-600 mb-3">Click to upload documents or drag and drop</p>
                            <input type="file" name="application_documents[]" id="docUpload" multiple
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                class="hidden" onchange="previewDocuments(this)">
                            <button type="button" onclick="document.getElementById('docUpload').click()"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                                <i class="fas fa-folder-open mr-1"></i>Choose Files
                            </button>
                        </div>
                        <div id="docList" class="mt-3 space-y-2"></div>
                    </div>
                    @endif

                    {{-- Postal: address confirmation --}}
                    @if($tender->isPostal())
                    <div class="bg-yellow-50 border border-yellow-300 rounded-xl p-5">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-yellow-600 text-xl mt-0.5"></i>
                            <div class="flex-1">
                                <p class="font-semibold text-yellow-800 text-sm">Send Physical Documents To:</p>
                                <p class="text-gray-800 font-medium mt-1 leading-relaxed">{{ $tender->postal_address }}</p>
                                @if($tender->contact_person || $tender->contact_phone || $tender->contact_email)
                                <div class="mt-3 pt-3 border-t border-yellow-200 text-xs text-yellow-700 space-y-1">
                                    @if($tender->contact_person)<p><i class="fas fa-user mr-1"></i>{{ $tender->contact_person }}</p>@endif
                                    @if($tender->contact_phone)<p><i class="fas fa-phone mr-1"></i>{{ $tender->contact_phone }}</p>@endif
                                    @if($tender->contact_email)<p><i class="fas fa-envelope mr-1"></i>{{ $tender->contact_email }}</p>@endif
                                </div>
                                @endif
                                @if($tender->postal_receipt_deadline)
                                <p class="mt-3 text-xs text-red-700 font-semibold">
                                    <i class="fas fa-calendar-times mr-1"></i>Physical documents must be received by: <strong>{{ $tender->postal_receipt_deadline->format('d M Y') }}</strong>
                                </p>
                                @else
                                <p class="mt-3 text-xs text-yellow-700">
                                    <i class="fas fa-clock mr-1"></i>Physical documents must arrive before the submission deadline.
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Terms / Declaration --}}
            <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" id="terms_agree" required class="mt-1 flex-shrink-0 rounded">
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Declaration — I confirm that:</span>
                        <ul class="list-disc list-inside mt-1 text-gray-600 space-y-0.5">
                            <li>All information provided is accurate and complete</li>
                            <li>I have read and understood the tender terms and conditions</li>
                            <li>I have downloaded and reviewed all tender documents</li>
                            <li>I am authorised to submit this application on behalf of the company</li>
                            <li>I understand that false information may lead to disqualification</li>
                            @if($tender->isPostal())
                            <li>
                                I will send physical documents to the postal address
                                @if($tender->postal_receipt_deadline)
                                    on or before <strong>{{ $tender->postal_receipt_deadline->format('d M Y') }}</strong>
                                @else
                                    before the submission deadline
                                @endif
                                @if($tender->postal_address)
                                    at: <strong>{{ $tender->postal_address }}</strong>
                                @endif
                            </li>
                            @endif
                            <li>VMRF-DU reserves the right to accept or reject any or all proposals without assigning any reason.</li>
                        </ul>
                    </div>
                </label>
            </div>

            <div class="flex justify-between items-center">
                <button type="button"
                    onclick="document.getElementById('step2Section').scrollIntoView({behavior:'smooth'})"
                    class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <i class="fas fa-arrow-up text-xs"></i> Back to Eligibility
                </button>
                <button type="submit" id="submitBtn" disabled
                    class="px-8 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Submit
                </button>
            </div>
        </form>
    </div>

</div>

<script>
const hasQuestions = {{ $hasQuestions ? 'true' : 'false' }};
const checkEligUrl = "{{ route('vendor.tenders.check-eligibility', $tender->id) }}";
const csrfToken    = document.querySelector('meta[name="csrf-token"]')?.content;

document.getElementById('terms_agree')?.addEventListener('change', function () {
    document.getElementById('submitBtn').disabled = !this.checked;
});

function showFileName(input) {
    const box = input.closest('.border-2');
    if (box && input.files[0]) {
        const p = box.querySelector('p.text-xs.text-gray-400');
        if (p) p.textContent = input.files[0].name;
    }
}

function previewDocuments(input) {
    const list = document.getElementById('docList');
    list.innerHTML = '';
    Array.from(input.files).forEach(f => {
        list.innerHTML += `
            <div class="flex items-center gap-3 p-2.5 bg-gray-50 rounded-lg border border-gray-200 text-sm">
                <i class="fas fa-file text-green-500"></i>
                <span class="flex-1 truncate text-gray-700">${f.name}</span>
                <span class="text-xs text-gray-400">${(f.size/1024/1024).toFixed(2)} MB</span>
            </div>`;
    });
}

function activateStep(step) {
    const dot = document.getElementById('step' + step + 'Dot');
    const label = document.getElementById('step' + step + 'Label');
    if (dot) dot.className = 'w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold';
    if (label) label.classList.remove('opacity-50');
}

if (hasQuestions) {
    activateStep(2);
    document.getElementById('checkEligBtn').addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking…';

        const section  = document.getElementById('eligibilitySection');
        const inputs   = section.querySelectorAll('input, select, textarea');
        const formData = new FormData();
        formData.append('_token', csrfToken);

        inputs.forEach(input => {
            if (!input.name) return;
            if (input.type === 'checkbox') { if (input.checked) formData.append(input.name, input.value); }
            else if (input.type === 'radio') { if (input.checked) formData.append(input.name, input.value); }
            else if (input.type === 'file')  { if (input.files[0]) formData.append(input.name, input.files[0]); }
            else formData.append(input.name, input.value);
        });

        try {
            const res  = await fetch(checkEligUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: formData });
            const data = await res.json();
            const resultDiv = document.getElementById('eligResult');
            resultDiv.classList.remove('hidden');

            if (data.eligible) {
                resultDiv.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3 mb-2">
                        <i class="fas fa-check-circle text-green-600 text-xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-green-800">Eligibility Passed!</p>
                            <p class="text-sm text-green-700">You meet all eligibility criteria. Download the documents and complete the application form below.</p>
                        </div>
                    </div>`;

                // Reveal documents section (shown after eligibility passes)
                const docsSection = document.getElementById('docsSection');
                if (docsSection) docsSection.classList.remove('hidden');

                copyEligibilityAnswers();
                activateStep(3);

                const appSection = document.getElementById('applicationSection');
                appSection.classList.remove('hidden');
                setTimeout(() => appSection.scrollIntoView({ behavior: 'smooth', block: 'start' }), 200);

                inputs.forEach(el => el.disabled = true);
                btn.innerHTML = '<i class="fas fa-check"></i> Eligibility Passed';
                btn.className = btn.className.replace('bg-purple-600 hover:bg-purple-700', 'bg-green-600 cursor-default');
            } else {
                resultDiv.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3 mb-2">
                        <i class="fas fa-times-circle text-red-600 text-xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-red-800">Not Eligible for This Tender</p>
                            <p class="text-sm text-red-700">${data.message || 'You do not meet the required eligibility criteria.'}</p>
                            ${data.failed?.length ? `<ul class="mt-2 text-sm text-red-600 list-disc list-inside">${data.failed.map(q => `<li>${q}</li>`).join('')}</ul>` : ''}
                            <p class="text-xs text-red-400 mt-2 flex items-center gap-1"><i class="fas fa-envelope"></i> Notifications have been sent to you and the procurement team.</p>
                        </div>
                    </div>`;
                btn.disabled  = false;
                btn.innerHTML = '<i class="fas fa-redo"></i> Re-check Eligibility';
            }
        } catch (err) {
            document.getElementById('eligResult').innerHTML = `
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-2 text-sm">
                    <i class="fas fa-exclamation-circle mr-1"></i> A network error occurred. Please try again.
                </div>`;
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-clipboard-check"></i> Check My Eligibility';
        }
    });
} else {
    activateStep(2);
}

function toggleProposal(type, mode) {
    const writeBox = document.getElementById(type + 'WriteBox');
    const uploadBox = document.getElementById(type + 'UploadBox');
    const writeBtn = document.getElementById(type + 'WriteBtn');
    const uploadBtn = document.getElementById(type + 'UploadBtn');
    if (mode === 'write') {
        writeBox.classList.remove('hidden');
        uploadBox.classList.add('hidden');
        writeBtn.className = 'px-3 py-1 text-xs rounded-full bg-green-600 text-white';
        uploadBtn.className = 'px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700';
    } else {
        writeBox.classList.add('hidden');
        uploadBox.classList.remove('hidden');
        writeBtn.className = 'px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700';
        uploadBtn.className = 'px-3 py-1 text-xs rounded-full bg-green-600 text-white';
    }
}

function copyEligibilityAnswers() {
    const container = document.getElementById('eligibilityHiddenFields');
    container.innerHTML = '';
    const inputs = document.getElementById('eligibilitySection').querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (!input.name || input.type === 'file') return;
        if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) return;
        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = input.name;
        hidden.value = input.type === 'select-multiple'
            ? Array.from(input.selectedOptions).map(o => o.value).join(',')
            : input.value;
        container.appendChild(hidden);
    });
}
</script>
@endsection
