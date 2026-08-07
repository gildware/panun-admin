@php
    $followupsForModals = ($followups ?? $booking->followups ?? collect())
        ->filter(fn ($followup) => ($followup->status ?? '') === 'scheduled')
        ->values();
    $redirectWebPage = $redirectWebPage ?? request('web_page', 'details');
    $requiresMandatoryNextFollowup = $requiresMandatoryNextFollowup ?? $booking->requiresMandatoryNextFollowup();
    $followupScheduleMinAt = $followupScheduleMinAt ?? now()->format('Y-m-d\TH:i');
    $takeFollowupRoutes = $followupsForModals->mapWithKeys(fn ($followup) => [
        (string) $followup->id => route('admin.booking.followup.update', [$booking->id, $followup->id]),
    ]);
    $takeFollowupMeta = $followupsForModals->mapWithKeys(fn ($f) => [
        (string) $f->id => [
            'for' => $f->for,
            'date' => $f->date?->format('d M Y, h:i A'),
            'reason' => $f->reason,
            'urgency' => $f->urgency ?: 'medium',
        ],
    ]);
@endphp
@include('bookingmodule::admin.booking.partials._booking-take-followup-modal', [
    'booking' => $booking,
    'requiresMandatoryNextFollowup' => $requiresMandatoryNextFollowup,
    'redirectWebPage' => $redirectWebPage,
    'followupScheduleMinAt' => $followupScheduleMinAt,
])
@include('bookingmodule::admin.booking.partials._booking-edit-followup-modal', [
    'booking' => $booking,
    'redirectWebPage' => $redirectWebPage,
])
@include('bookingmodule::admin.booking.partials._booking-followup-delete-modal', [
    'redirectWebPage' => $redirectWebPage,
])
<script type="application/json" id="booking-take-followup-routes">@json($takeFollowupRoutes)</script>
<script type="application/json" id="booking-take-followup-meta">@json($takeFollowupMeta)</script>
