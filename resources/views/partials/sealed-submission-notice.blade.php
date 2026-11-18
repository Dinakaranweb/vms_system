{{--
    Sealed-bid notice.

    Shown on the staff-facing screens in place of whatever a vendor submitted, for as long as
    the tender is still sealed. Nothing a vendor sent in may be read before the Purchase
    Committee has opened the tender by entering every member's code, and Tender::canBeOpened()
    will not allow that before the tender's scheduled opening moment.

    This deliberately renders no file URL at all rather than hiding a link with CSS, so the
    documents cannot simply be read out of the page source before opening.

    Expects:
      $tender (Tender)    - the tender being withheld, used for the scheduled opening moment
      $count  (int|null)  - how many documents are being withheld, when that is known
      $label  (string|null) - what is sealed, defaults to "submission"
--}}
@php
    $sealOpening = $tender?->openingMoment();
    $sealLabel   = $label ?? 'submission';
@endphp
<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
    <i class="fas fa-lock text-amber-500 mt-0.5"></i>
    <div class="text-sm min-w-0">
        <p class="font-semibold text-amber-800">
            This {{ $sealLabel }} is sealed until the tender is opened
        </p>
        <p class="text-xs text-amber-700 mt-1 leading-relaxed">
            @if(!empty($count))
                {{ $count }} {{ \Illuminate\Support\Str::plural('document', $count) }} on record.
            @endif
            @if($sealOpening)
                Scheduled opening: <span class="font-semibold">{{ $sealOpening->format('d M Y, h:i A') }}</span>.
            @else
                It can be opened once the submission deadline has passed.
            @endif
            It becomes readable only after every Purchase Committee member's opening code has
            been collected and entered.
        </p>
    </div>
</div>
