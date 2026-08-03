@php
    use Modules\BookingModule\Services\BookingFollowupService;

    $followupListMeta = $followupListMeta ?? [];
    $party = $party ?? 'customer';
    $followup = $followup ?? null;
    $cellMeta = $followupListMeta[$booking->id][$party] ?? null;

    if (! $cellMeta && $followup && $followup->date) {
        $cellMeta = app(BookingFollowupService::class)->buildFollowupListCellMeta(
            $followup,
            $booking->requiresMandatoryNextFollowup()
        );
    }
@endphp
@if($followup && $followup->date)
    <div class="booking-followup-cell {{ $cellMeta['cell_class'] ?? '' }}">
        <span class="booking-followup-cell__date {{ $cellMeta['date_class'] ?? '' }}">
            {{ \Carbon\Carbon::parse($followup->date)->format('d-M-Y') }}
        </span>
        @if($cellMeta)
            <span class="badge rounded-pill {{ $cellMeta['badge_class'] }} booking-followup-badge" title="{{ translate($cellMeta['label']) }}">
                {{ translate($cellMeta['label']) }}
            </span>
        @endif
    </div>
@else
    —
@endif
