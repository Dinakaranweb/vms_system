{{--
    Shared partial: shows technical proposal, financial proposal (text or file),
    and supporting documents with view/download buttons.
    Expects: $application (TenderApplication)
--}}
@php
    $techText = $application->technical_proposal;
    $techFile = $application->technical_proposal_file;
    $finText  = $application->financial_proposal;
    $finFile  = $application->financial_proposal_file;

    function appFileIcon(string $path): string {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match($ext) {
            'pdf'              => 'fas fa-file-pdf text-red-500',
            'doc', 'docx'     => 'fas fa-file-word text-blue-700',
            'xls', 'xlsx'     => 'fas fa-file-excel text-green-600',
            'jpg','jpeg','png','gif' => 'fas fa-file-image text-indigo-500',
            default            => 'fas fa-file text-gray-500',
        };
    }

    function appFileViewable(string $path): bool {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['pdf','jpg','jpeg','png','gif']);
    }
@endphp

{{-- ── Proposals ── --}}
<div class="border-t pt-4 mt-4">
    <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
        <i class="fas fa-file-alt text-blue-500"></i> Proposals
    </h4>
    <div class="space-y-4">

        {{-- Technical Proposal --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-2">
                Technical Proposal
            </label>
            @if($techText && trim($techText) !== '')
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $techText }}</p>
                @if($techFile)
                    {{-- Both text and file exist --}}
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ Storage::url($techFile) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                            <i class="{{ appFileIcon($techFile) }}"></i>
                            View Attached File
                            <i class="fas fa-external-link-alt text-xs text-gray-400"></i>
                        </a>
                        <a href="{{ Storage::url($techFile) }}" download
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                            <i class="fas fa-download text-gray-400"></i> Download
                        </a>
                    </div>
                @endif
            @elseif($techFile)
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <i class="{{ appFileIcon($techFile) }} text-xl"></i>
                        <span class="text-sm text-gray-700 font-medium">
                            {{ basename($techFile) }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        @if(appFileViewable($techFile))
                        <a href="{{ Storage::url($techFile) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                            <i class="fas fa-eye"></i> View
                        </a>
                        @endif
                        <a href="{{ Storage::url($techFile) }}" download
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            @else
                <p class="text-gray-400 italic text-sm">Not provided</p>
            @endif
        </div>

        {{-- Financial Proposal --}}
        <div class="bg-gray-50 rounded-lg p-4">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-2">
                Financial Proposal
            </label>
            @if($finText && trim($finText) !== '')
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line">{{ $finText }}</p>
                @if($finFile)
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ Storage::url($finFile) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                            <i class="{{ appFileIcon($finFile) }}"></i>
                            View Attached File
                            <i class="fas fa-external-link-alt text-xs text-gray-400"></i>
                        </a>
                        <a href="{{ Storage::url($finFile) }}" download
                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                            <i class="fas fa-download text-gray-400"></i> Download
                        </a>
                    </div>
                @endif
            @elseif($finFile)
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <i class="{{ appFileIcon($finFile) }} text-xl"></i>
                        <span class="text-sm text-gray-700 font-medium">
                            {{ basename($finFile) }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        @if(appFileViewable($finFile))
                        <a href="{{ Storage::url($finFile) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                            <i class="fas fa-eye"></i> View
                        </a>
                        @endif
                        <a href="{{ Storage::url($finFile) }}" download
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            @else
                <p class="text-gray-400 italic text-sm">Not provided</p>
            @endif
        </div>

    </div>
</div>

{{-- ── Supporting Documents ── --}}
@if($application->documents && count($application->documents) > 0)
<div class="border-t pt-4 mt-4">
    <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
        <i class="fas fa-paperclip text-green-500"></i>
        Supporting Documents
        <span class="text-xs text-gray-400 font-normal">({{ count($application->documents) }})</span>
    </h4>
    <div class="space-y-2">
        @foreach($application->documents as $index => $doc)
        @php
            $docName = $doc['name'] ?? 'Document ' . ($index + 1);
            $docExt  = strtolower(pathinfo($docName, PATHINFO_EXTENSION));
            $docIcon = appFileIcon($doc['path'] ?? $docName);
            $canView = appFileViewable($doc['path'] ?? $docName);
        @endphp
        <div class="flex items-center justify-between bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg px-4 py-3 transition">
            <div class="flex items-center gap-3 min-w-0">
                <i class="{{ $docIcon }} text-lg flex-shrink-0"></i>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $docName }}</p>
                    @if(isset($doc['size']))
                    <p class="text-xs text-gray-400">{{ number_format($doc['size'] / 1024, 2) }} KB</p>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 flex-shrink-0 ml-3">
                @if($canView)
                <a href="{{ Storage::url($doc['path']) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                    <i class="fas fa-eye"></i> View
                </a>
                @endif
                <a href="{{ Storage::url($doc['path']) }}" download="{{ $docName }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
