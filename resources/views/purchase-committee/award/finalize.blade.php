@extends('layouts.dashboard')

@section('title', 'Finalize Award — ' . $tender->title)
@section('header', 'Finalize Award')

@section('content')
@php
    $rp = auth()->user()->hasRole('super-admin') ? 'super-admin'
        : (auth()->user()->hasRole('admin') ? 'admin' : 'tender-owner');
    $lowest = $applications->firstWhere(fn($a) => $a->bid_amount !== null);
@endphp

<div class="max-w-5xl mx-auto space-y-4">

    <div class="flex items-center justify-between gap-3">
        <a href="{{ route($rp . '.tenders.show', $tender->id) }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
            <i class="fas fa-arrow-left text-xs"></i> Back to Tender
        </a>
        <div class="flex items-center gap-2">
            @if($tender->hasPostalBids())
            <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}" class="text-sm text-emerald-700 border border-emerald-200 bg-emerald-50 px-4 py-2 rounded-lg hover:bg-emerald-100 inline-flex items-center gap-1.5">
                <i class="fas fa-file-arrow-up text-xs"></i> Received Docs
            </a>
            @endif
            <a href="{{ route('purchase-committee.review.compare-bids', $tender->id) }}" class="text-sm text-indigo-600 border border-indigo-200 bg-indigo-50 px-4 py-2 rounded-lg hover:bg-indigo-100 inline-flex items-center gap-1.5">
                <i class="fas fa-scale-balanced text-xs"></i> Compare Bids
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm space-y-1">
        @foreach($errors->all() as $error)<p><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</p>@endforeach
    </div>
    @endif

    {{-- Tender header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-800 to-slate-700">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-white font-semibold truncate">{{ $tender->title }}</h3>
                    <p class="text-slate-300 text-xs mt-0.5">
                        {{ $tender->tender_number }} · {{ $tender->getSubmissionModeLabel() }}
                        @if($tender->bid_opening_at) · Opened {{ $tender->bid_opening_at->format('d M Y') }} @endif
                    </p>
                </div>
                @if($finalization)
                <span class="bg-emerald-500/20 text-emerald-200 text-xs px-2.5 py-1 rounded-full border border-emerald-400/30 flex-shrink-0">
                    <i class="fas fa-gavel mr-1"></i>Finalized
                </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-100 border-b border-gray-100">
            @foreach([
                ['Applicants', $stats['applications'], 'fa-file-signature', 'text-indigo-500'],
                ['Priced', $stats['priced'], 'fa-indian-rupee-sign', 'text-emerald-500'],
                ['Without a price', $stats['unpriced'], 'fa-circle-exclamation', 'text-amber-500'],
                ['Committee remarks', $stats['recommendations'], 'fa-comments', 'text-indigo-500'],
            ] as [$label, $value, $icon, $colour])
            <div class="px-4 py-3 text-center">
                <i class="fas {{ $icon }} {{ $colour }} text-sm"></i>
                <p class="text-xl font-bold text-gray-800 mt-1">{{ $value }}</p>
                <p class="text-xs text-gray-400">{{ $label }}</p>
            </div>
            @endforeach
        </div>

        <div class="px-6 py-3 bg-slate-50">
            <p class="text-xs text-slate-700">
                <i class="fas fa-circle-info mr-1"></i>
                The decision is yours. The Purchase Committee's recommendations below are <strong>advisory</strong> —
                weigh them against the bid values and the documents received, then record which applicant is being
                awarded and how the committee was consulted. A contract cannot be issued until that is on record.
            </p>
        </div>

        @if($stats['unpriced'] > 0)
        <div class="px-6 py-3 bg-amber-50 border-t border-amber-100">
            <p class="text-xs text-amber-900">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                <strong>{{ $stats['unpriced'] }} applicant(s) have no bid value entered</strong> and cannot be compared on price.
                @if($tender->hasPostalBids())
                <a href="{{ route('purchase-committee.received-documents.index', $tender->id) }}" class="underline font-medium">Enter their values</a>
                from the quotes first if they are still in contention.
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Already awarded --}}
    @if($alreadyAwarded)
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-4">
        <p class="text-sm text-emerald-900">
            <i class="fas fa-circle-check mr-1.5"></i>
            This tender has been awarded to <strong>{{ $alreadyAwarded->vendor->company_name ?? 'a vendor' }}</strong>
            ({{ $alreadyAwarded->application_number }}).
            @if($tender->contract)
            Contract <strong>{{ $tender->contract->contract_number }}</strong> has been issued.
            @endif
        </p>
    </div>
    @endif

    {{-- The decision in force --}}
    @if($finalization)
    <div class="bg-white rounded-xl shadow-sm border-2 border-emerald-200 overflow-hidden">
        <div class="px-6 py-3 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between gap-3">
            <h4 class="text-sm font-semibold text-emerald-900"><i class="fas fa-gavel mr-1.5"></i>Finalized Award</h4>
            <span class="text-xs text-emerald-700">{{ $finalization->created_at->format('d M Y, h:i A') }}</span>
        </div>
        <div class="px-6 py-4 space-y-3">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <span class="text-lg font-bold text-gray-900">{{ $finalization->application->vendor->company_name ?? 'Unknown vendor' }}</span>
                <span class="text-sm text-gray-500">{{ $finalization->application->application_number }}</span>
                <span class="text-lg font-bold text-emerald-700">
                    {{ $finalization->awarded_value !== null ? '₹' . number_format($finalization->awarded_value, 2) : 'N/A' }}
                </span>
            </div>

            <div class="grid sm:grid-cols-2 gap-3 text-xs">
                <div class="bg-gray-50 rounded-lg px-3 py-2">
                    <p class="text-gray-400 uppercase tracking-wide">Committee consulted</p>
                    <p class="text-gray-800 font-medium mt-0.5">
                        <i class="fas {{ $finalization->wasOral() ? 'fa-comments' : 'fa-video' }} mr-1"></i>
                        {{ $finalization->getDiscussionModeLabel() }}
                        @if($finalization->discussion_held_at)
                        <span class="font-normal text-gray-500">· {{ $finalization->discussion_held_at->format('d M Y, h:i A') }}</span>
                        @endif
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg px-3 py-2">
                    <p class="text-gray-400 uppercase tracking-wide">Finalized by</p>
                    <p class="text-gray-800 font-medium mt-0.5">{{ $finalization->finalizedBy->name ?? '—' }}</p>
                </div>
            </div>

            @php $parts = $finalization->participantUsers(); @endphp
            @if($parts->isNotEmpty())
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Took part in the discussion</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($parts as $p)
                    <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-full">{{ $p->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Reason recorded</p>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $finalization->decision_note }}</p>
            </div>

            @if($cs = $finalization->committee_summary)
            <p class="text-xs text-gray-400">
                Committee position at the time of the decision:
                {{ $cs['support'] ?? 0 }} supporting, {{ $cs['oppose'] ?? 0 }} opposing,
                {{ $cs['neutral'] ?? 0 }} no objection, {{ $cs['remarks'] ?? 0 }} remark-only.
            </p>
            @endif
        </div>

        <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
            @if(!$alreadyAwarded)
            <a href="{{ route($rp . '.applications.award-form', $finalization->application_id) }}"
               class="bg-emerald-600 text-white px-5 py-2.5 rounded-lg hover:bg-emerald-700 text-sm font-medium">
                <i class="fas fa-file-contract mr-1.5"></i>Give the Contract to this Applicant
            </a>
            @else
            <span class="text-xs text-gray-500"><i class="fas fa-lock mr-1"></i>Contract already issued — the decision is locked.</span>
            @endif

            @if(!$alreadyAwarded && !$tender->contract)
            <button type="button" onclick="document.getElementById('revokeBox').classList.toggle('hidden')"
                    class="text-xs text-red-600 hover:underline">Withdraw this decision</button>
            @endif
        </div>

        @if(!$alreadyAwarded && !$tender->contract)
        <div id="revokeBox" class="hidden px-6 py-4 bg-red-50 border-t border-red-100">
            <form method="POST" action="{{ route('purchase-committee.award.finalize.revoke', [$tender->id, $finalization->id]) }}"
                  onsubmit="return confirm('Withdraw this finalized award? The committee will be notified.')">
                @csrf
                <label class="block text-xs font-semibold text-red-900 mb-1">Why is the decision being withdrawn?</label>
                <textarea name="revoked_reason" rows="2" required minlength="5" maxlength="2000"
                          class="w-full text-sm border border-red-200 rounded-lg px-3 py-2"
                          placeholder="e.g. the committee raised a documentation issue after the decision"></textarea>
                <button type="submit" class="mt-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-medium">
                    Withdraw Decision
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif

    {{-- Applicants, cheapest first --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-ranking-star text-gray-400 mr-1.5"></i>Applicants by Bid Value</h4>
            <p class="text-xs text-gray-400 mt-0.5">Applicants with no price entered are listed last.</p>
        </div>

        @if($applications->isEmpty())
        <div class="py-14 text-center">
            <i class="fas fa-inbox text-4xl text-gray-200 block mb-2"></i>
            <p class="text-gray-400 text-sm">No applications were received for this tender.</p>
        </div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($applications as $i => $app)
            @php
                $support = $app->reviewComments->where('verdict', 'support')->count();
                $oppose  = $app->reviewComments->where('verdict', 'oppose')->count();
                $neutral = $app->reviewComments->where('verdict', 'neutral')->count();
                $isChosen = $finalization && $finalization->application_id === $app->id;
                $rec = $recommendations->get($app->id);
            @endphp
            <div class="px-6 py-4 {{ $isChosen ? 'bg-emerald-50/50' : '' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($app->bid_amount !== null && $lowest && $lowest->id === $app->id)
                            <span class="text-[0.65rem] bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-semibold">LOWEST</span>
                            @endif
                            @if($isChosen)
                            <span class="text-[0.65rem] bg-emerald-600 text-white px-1.5 py-0.5 rounded font-semibold">SELECTED</span>
                            @endif
                            <span class="text-sm font-semibold text-gray-900 truncate">{{ $app->vendor->company_name ?? $app->vendor->name ?? 'Unknown vendor' }}</span>
                            <span class="text-xs text-gray-400">{{ $app->application_number }}</span>
                            <span class="text-[0.65rem] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ ucfirst($app->status) }}</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5 text-xs text-gray-500">
                            <span class="{{ $app->bid_amount === null ? 'text-amber-600 font-medium' : 'text-gray-800 font-semibold' }}">
                                <i class="fas fa-indian-rupee-sign text-gray-300 mr-1"></i>
                                {{ $app->bid_amount !== null ? number_format($app->bid_amount, 2) : 'no value entered' }}
                            </span>
                            @if($app->bid_recorded_at)
                            <span class="text-gray-400"><i class="fas fa-user-pen mr-1"></i>keyed in @if($app->bidRecordedBy)by {{ $app->bidRecordedBy->name }}@endif</span>
                            @endif
                            <span>
                                <i class="fas fa-folder-open text-gray-300 mr-1"></i>
                                {{ $app->receivedDocuments->count() }} received
                                @if($app->postal_quote_scan) + quote scan @endif
                            </span>
                        </div>

                        {{-- Advisory committee position --}}
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            @if($app->reviewComments->isEmpty())
                            <span class="text-xs text-gray-400"><i class="fas fa-comment-slash mr-1"></i>No committee recommendation</span>
                            @else
                                @if($support)<span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full"><i class="fas fa-thumbs-up mr-1"></i>{{ $support }} support</span>@endif
                                @if($oppose)<span class="text-xs bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded-full"><i class="fas fa-thumbs-down mr-1"></i>{{ $oppose }} oppose</span>@endif
                                @if($neutral)<span class="text-xs bg-gray-100 text-gray-600 border border-gray-200 px-2 py-0.5 rounded-full"><i class="fas fa-minus mr-1"></i>{{ $neutral }} no objection</span>@endif
                                <span class="text-xs text-gray-400">{{ $app->reviewComments->count() }} remark(s)</span>
                            @endif

                            @if($rec)
                            <span class="text-xs px-2 py-0.5 rounded-full border
                                         {{ $rec->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : ($rec->status === 'rejected' ? 'bg-red-50 text-red-700 border-red-200'
                                            : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                                Formal sign-off: {{ str_replace('_', ' ', $rec->status) }}
                            </span>
                            @endif
                        </div>

                        @if($app->reviewComments->isNotEmpty())
                        <details class="mt-2">
                            <summary class="text-xs text-indigo-600 cursor-pointer hover:underline">Read the committee's remarks</summary>
                            <div class="mt-2 space-y-2 border-l-2 border-gray-100 pl-3">
                                @foreach($app->reviewComments as $c)
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">
                                        {{ $c->committeeMember->name ?? 'Unknown' }}
                                        <span class="font-normal text-gray-400">· {{ $c->getVerdictLabel() }} · {{ $c->created_at->format('d M, h:i A') }}</span>
                                    </p>
                                    <div class="text-xs text-gray-600 mt-0.5">{!! $c->comment !!}</div>
                                </div>
                                @endforeach
                            </div>
                        </details>
                        @endif

                        @if($app->receivedDocuments->isNotEmpty())
                        <details class="mt-1.5">
                            <summary class="text-xs text-indigo-600 cursor-pointer hover:underline">Documents received ({{ $app->receivedDocuments->count() }})</summary>
                            <div class="mt-2 space-y-1 border-l-2 border-gray-100 pl-3">
                                @foreach($app->receivedDocuments as $doc)
                                <a href="{{ $doc->getUrl() }}" target="_blank" class="block text-xs text-gray-600 hover:text-indigo-600 truncate">
                                    <i class="fas {{ $doc->isScan() ? 'fa-camera' : 'fa-paperclip' }} text-gray-300 mr-1"></i>{{ $doc->file_name }}
                                </a>
                                @endforeach
                            </div>
                        </details>
                        @endif
                    </div>

                    <a href="{{ route('purchase-committee.review.application-show', [$tender->id, $app->id]) }}"
                       class="text-xs text-indigo-600 hover:underline flex-shrink-0">Full application <i class="fas fa-chevron-right text-[0.6rem]"></i></a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- The decision form --}}
    @if(!$finalization && !$alreadyAwarded && $candidates->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 bg-slate-800">
            <h4 class="text-white font-semibold text-sm"><i class="fas fa-gavel mr-1.5"></i>Record the Decision</h4>
            <p class="text-slate-300 text-xs mt-0.5">This is what allows a contract to be issued.</p>
        </div>

        <form method="POST" action="{{ route('purchase-committee.award.finalize.store', $tender->id) }}" class="p-6 space-y-5">
            @csrf

            {{-- Applicant --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Award the contract to <span class="text-red-500">*</span></label>
                <div class="space-y-2">
                    @foreach($candidates as $app)
                    <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-50 transition
                                  {{ old('application_id') == $app->id ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200' }}">
                        <input type="radio" name="application_id" value="{{ $app->id }}" required
                               @checked(old('application_id') == $app->id)
                               class="accent-emerald-600"
                               data-bid="{{ $app->bid_amount }}">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium text-gray-900 truncate">{{ $app->vendor->company_name ?? $app->vendor->name ?? 'Unknown vendor' }}</span>
                            <span class="block text-xs text-gray-400">
                                {{ $app->application_number }} ·
                                {{ $app->bid_amount !== null ? '₹' . number_format($app->bid_amount, 2) : 'no bid value entered' }} ·
                                {{ $app->receivedDocuments->count() }} document(s)
                            </span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Discussion mode --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">How was the Purchase Committee consulted? <span class="text-red-500">*</span></label>
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-50 transition
                                  {{ old('discussion_mode') === 'online' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
                        <input type="radio" name="discussion_mode" value="online" required @checked(old('discussion_mode') === 'online') class="mt-0.5 accent-indigo-600">
                        <span>
                            <span class="block text-sm font-medium text-gray-900"><i class="fas fa-video text-indigo-500 mr-1.5"></i>Online discussion</span>
                            <span class="block text-xs text-gray-500 mt-0.5">Through the portal, a call or a video meeting</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 border rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-50 transition
                                  {{ old('discussion_mode') === 'oral' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200' }}">
                        <input type="radio" name="discussion_mode" value="oral" required @checked(old('discussion_mode') === 'oral') class="mt-0.5 accent-indigo-600">
                        <span>
                            <span class="block text-sm font-medium text-gray-900"><i class="fas fa-comments text-indigo-500 mr-1.5"></i>Oral discussion</span>
                            <span class="block text-xs text-gray-500 mt-0.5">In person at the committee table</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="discussion_held_at">When was it held?</label>
                    <input type="datetime-local" name="discussion_held_at" id="discussion_held_at"
                           value="{{ old('discussion_held_at', now()->format('Y-m-d\TH:i')) }}"
                           max="{{ now()->format('Y-m-d\TH:i') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="awarded_value">Awarded value</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                        <input type="number" step="0.01" min="0" name="awarded_value" id="awarded_value"
                               value="{{ old('awarded_value') }}" placeholder="Defaults to the selected bid value"
                               class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Leave empty to use the applicant's bid value.</p>
                </div>
            </div>

            {{-- Participants --}}
            @if($members->isNotEmpty())
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Committee members who took part
                    <span class="font-normal text-gray-400">— optional</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach($members as $m)
                    <label class="cursor-pointer">
                        <input type="checkbox" name="participants[]" value="{{ $m->user_id }}" class="peer sr-only"
                               @checked(in_array($m->user_id, (array) old('participants', [])))>
                        <span class="inline-flex items-center gap-1.5 text-xs border border-gray-200 text-gray-600 rounded-lg px-3 py-1.5
                                     peer-checked:border-indigo-400 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 peer-checked:font-semibold">
                            <i class="fas fa-user-check"></i>{{ $m->user->name ?? 'Member' }}
                            @if($m->designation)<span class="font-normal text-gray-400">· {{ $m->designation }}</span>@endif
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Reason --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="decision_note">Why this applicant? <span class="text-red-500">*</span></label>
                <textarea name="decision_note" id="decision_note" rows="4" required minlength="10" maxlength="5000"
                          placeholder="e.g. Lowest responsive bid at ₹6,90,000. All required documents received and verified. Committee raised no objection in the meeting on 14 Oct."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('decision_note') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Recorded permanently against the award and sent to the committee.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route($rp . '.tenders.show', $tender->id) }}" class="text-sm text-gray-600 px-4 py-2 hover:text-gray-900">Cancel</a>
                <button type="submit" class="bg-slate-800 text-white px-6 py-2.5 rounded-lg hover:bg-slate-900 text-sm font-semibold">
                    <i class="fas fa-gavel mr-1.5"></i>Finalize Award
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- Withdrawn decisions, kept for the record --}}
    @if($revoked->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700"><i class="fas fa-clock-rotate-left text-gray-400 mr-1.5"></i>Withdrawn Decisions</h4>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($revoked as $r)
            <div class="px-6 py-3">
                <p class="text-sm text-gray-700">
                    <span class="line-through">{{ $r->application->vendor->company_name ?? 'Unknown vendor' }}</span>
                    <span class="text-xs text-gray-400 ml-1.5">
                        {{ $r->awarded_value !== null ? '₹' . number_format($r->awarded_value, 2) : 'N/A' }} ·
                        {{ $r->getDiscussionModeLabel() }} ·
                        finalized by {{ $r->finalizedBy->name ?? '—' }} on {{ $r->created_at->format('d M Y') }}
                    </span>
                </p>
                <p class="text-xs text-red-600 mt-0.5">
                    Withdrawn {{ $r->revoked_at->format('d M Y, h:i A') }}@if($r->revokedBy) by {{ $r->revokedBy->name }}@endif — {{ $r->revoked_reason }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
/* Prefill the awarded value from whichever applicant is selected, unless the officer has
   deliberately typed a different figure. */
(function () {
    const valueField = document.getElementById('awarded_value');
    if (!valueField) return;
    let touched = valueField.value !== '';
    valueField.addEventListener('input', () => { touched = true; });

    document.querySelectorAll('input[name="application_id"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (touched) return;
            valueField.value = this.dataset.bid || '';
        });
    });
})();
</script>
@endsection
