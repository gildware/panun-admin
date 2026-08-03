@php
    $followupsForModals = ($followups ?? $booking->followups ?? collect())
        ->filter(fn ($followup) => ($followup->status ?? '') === 'scheduled')
        ->values();
    $redirectWebPage = $redirectWebPage ?? request('web_page', 'details');
    $requiresMandatoryNextFollowup = $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup();
    $followupScheduleMinAt = $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i');
    $takeFollowupRoutes = $followupsForModals->mapWithKeys(fn ($followup) => [
        $followup->id => route('admin.booking.followup.update', [$booking->id, $followup->id]),
    ]);
    $takeFollowupMeta = $followupsForModals->mapWithKeys(fn ($f) => [
        $f->id => [
            'for' => $f->for,
            'date' => $f->date?->format('d M Y, h:i A'),
            'reason' => $f->reason,
            'urgency' => $f->urgency ?: 'medium',
        ],
    ]);
@endphp
@if($followupsForModals->isNotEmpty())
    @include('bookingmodule::admin.booking.partials._booking-take-followup-modal', [
        'booking' => $booking,
        'requiresMandatoryNextFollowup' => $requiresMandatoryNextFollowup,
        'redirectWebPage' => $redirectWebPage,
        'followupScheduleMinAt' => $followupScheduleMinAt,
    ])
    <script type="application/json" id="booking-take-followup-routes">@json($takeFollowupRoutes)</script>
    <script type="application/json" id="booking-take-followup-meta">@json($takeFollowupMeta)</script>
@endif
