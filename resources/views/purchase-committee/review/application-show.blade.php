@extends('layouts.dashboard')

@section('title', 'Application — ' . $application->application_number)
@section('header', 'Application Review')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('purchase-committee.review.applications', $tender->id) }}"
            class="text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg px-3 py-1.5 text-sm flex items-center gap-1.5 bg-white">
            <i class="fas fa-arrow-left text-xs"></i> Back
        </a>
        <div class="flex-1">
            <h2 class="text-lg font-bold text-gray-900">{{ $application->application_number }}</h2>
            <p class="text-sm text-gray-400">{{ $tender->tender_number }} &nbsp;·&nbsp; {{ $tender->title }}</p>
        </div>
        @php
            $statusMap = ['submitted'=>['bg-amber-100 text-amber-700','Submitted'],'shortlisted'=>['bg-blue-100 text-blue-700','Shortlisted'],'rejected'=>['bg-red-100 text-red-700','Rejected'],'awarded'=>['bg-green-100 text-green-700','Awarded']];
            [$cls,$label] = $statusMap[$application->status] ?? ['bg-gray-100 text-gray-600', ucfirst($application->status)];
        @endphp
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $cls }}">{{ $label }}</span>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor</h4>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Company</p>
                        <p class="font-semibold text-gray-900">{{ $application->vendor->company_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <p class="text-sm text-gray-700">{{ $application->vendor->email ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Financials</h4>
                </div>
                <div class="px-5 py-4">
                    <p class="text-xs text-gray-400">Bid Amount</p>
                    <p class="text-2xl font-bold text-gray-900">₹{{ number_format($application->bid_amount ?? 0, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Technical Proposal</h4>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 prose prose-sm max-w-none">
                    {!! $application->technical_proposal ?: '<span class="text-gray-400 italic">Not provided</span>' !!}
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Financial Proposal</h4>
                </div>
                <div class="px-5 py-4 text-sm text-gray-700 prose prose-sm max-w-none">
                    {!! $application->financial_proposal ?: '<span class="text-gray-400 italic">Not provided</span>' !!}
                </div>
            </div>

            {{-- Committee review comments — informal, unordered, any member can comment any time --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <i class="fas fa-comments mr-1"></i> Committee Recommendations
                    </h4>
                    @if($application->reviewComments->count() > 0)
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">{{ $application->reviewComments->count() }}</span>
                    @endif
                </div>
                <div class="px-5 py-4 space-y-4">
                    @forelse($application->reviewComments as $comment)
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($comment->committeeMember->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-semibold text-gray-800">{{ $comment->committeeMember->name ?? 'Unknown' }}</span>
                                @if($comment->hasVerdict())
                                @php
                                    $vChip = match($comment->verdict) {
                                        'support' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'oppose'  => 'bg-red-50 text-red-700 border-red-200',
                                        default   => 'bg-gray-100 text-gray-600 border-gray-200',
                                    };
                                    $vIcon = match($comment->verdict) {
                                        'support' => 'fa-thumbs-up',
                                        'oppose'  => 'fa-thumbs-down',
                                        default   => 'fa-minus',
                                    };
                                @endphp
                                <span class="text-[0.65rem] px-1.5 py-0.5 rounded border {{ $vChip }}">
                                    <i class="fas {{ $vIcon }} mr-0.5"></i>{{ $comment->getVerdictLabel() }}
                                </span>
                                @endif
                                <span class="text-xs text-gray-400">{{ $comment->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="text-sm text-gray-700 mt-1 prose prose-sm max-w-none">{!! $comment->comment !!}</div>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400 text-center py-6">No recommendations yet. Be the first to share your view below.</p>
                    @endforelse
                </div>
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50">
                    <form method="POST" action="{{ route('purchase-committee.review.comment.store', $application->id) }}">
                        @csrf
                        <textarea name="comment" id="reviewComment" placeholder="Share your recommendation about this application..."></textarea>

                        <div class="mt-3">
                            <p class="text-xs font-semibold text-gray-600 mb-1.5">
                                Your position on awarding this applicant
                                <span class="font-normal text-gray-400">— optional, the Purchase Officer decides</span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                {{-- Full class strings, not interpolated: the runtime Tailwind
                                     build must be able to see each one literally. --}}
                                @foreach([
                                    ['support', 'Support award', 'fa-thumbs-up',   'peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-700'],
                                    ['oppose',  'Oppose award',  'fa-thumbs-down', 'peer-checked:border-red-400 peer-checked:bg-red-50 peer-checked:text-red-700'],
                                    ['neutral', 'No objection',  'fa-minus',       'peer-checked:border-gray-400 peer-checked:bg-gray-100 peer-checked:text-gray-700'],
                                    ['',        'Remark only',   'fa-comment',     'peer-checked:border-indigo-400 peer-checked:bg-indigo-50 peer-checked:text-indigo-700'],
                                ] as [$val, $label, $icon, $checkedClasses])
                                <label class="cursor-pointer">
                                    <input type="radio" name="verdict" value="{{ $val }}" class="peer sr-only" @checked($val === '')>
                                    <span class="inline-flex items-center gap-1.5 text-xs border rounded-lg px-3 py-1.5 border-gray-200 text-gray-600 peer-checked:font-semibold {{ $checkedClasses }}">
                                        <i class="fas {{ $icon }}"></i>{{ $label }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="mt-3 bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                            <i class="fas fa-paper-plane mr-1"></i> Add Recommendation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function () {
    $('#reviewComment').summernote({
        height: 140,
        toolbar: [['style', ['bold', 'italic', 'underline']], ['para', ['ul', 'ol']], ['view', ['fullscreen']]],
        placeholder: 'Share your recommendation about this application...',
    });
});
</script>
@endpush
