@php
    use Modules\BookingModule\Services\BookingFollowupService;

    $party = $party ?? 'customer';
    $followup = $followup ?? null;
    $followupListMeta = $followupListMeta ?? [];
    $cellMeta = $followupListMeta[$booking->id][$party] ?? null;

    if (! $cellMeta && $followup && $followup->date) {
        $cellMeta = app(BookingFollowupService::class)->buildFollowupListCellMeta(
            $followup,
            $booking->requiresMandatoryNextFollowup()
        );
    }
@endphp
@if($followup && $followup->date)
    <span class="bc-fup">
        <span class="bc-lbl">{{ translate('Fup') }}</span>
        <span class="bc-val">
            {{ \Carbon\Carbon::parse($followup->date)->format('d-M-Y') }}
            @if($cellMeta)
                <span class="badge rounded-pill {{ $cellMeta['badge_class'] }} booking-followup-badge" title="{{ translate($cellMeta['label']) }}">
                    {{ translate($cellMeta['label']) }}
                </span>
            @endif
        </span>
    </span>
@endif
