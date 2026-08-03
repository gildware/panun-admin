@php
    $followupDetailMeta = $followupDetailMeta ?? null;
    $bookingRef = $booking ?? null;
@endphp
@if(!empty($followupDetailMeta['has_any_pending']) && $bookingRef)
    @foreach(['customer' => translate('Customer'), 'provider' => translate('Provider')] as $partyKey => $partyLabel)
        @php $partyMeta = $followupDetailMeta[$partyKey] ?? null; @endphp
        @if(!empty($partyMeta['has_pending']) && !empty($partyMeta['followup']?->date))
            <div class="booking-followup-alert booking-followup-alert--{{ !empty($partyMeta['is_overdue']) ? 'missed' : 'pending' }}" role="alert">
                <div class="booking-followup-alert__content">
                    <span class="material-icons booking-followup-alert__icon">{{ !empty($partyMeta['is_overdue']) ? 'error' : 'schedule' }}</span>
                    <div>
                        @if(!empty($partyMeta['is_overdue']))
                            <strong>{{ translate('Missed_Follow_up') }}</strong>
                            — {{ translate('Follow_up_for') }} {{ $partyLabel }} {{ translate('was_due_on') }}
                            {{ $partyMeta['followup']->date->format('d M Y, h:i A') }}.
                        @else
                            <strong>{{ translate('Pending_Follow_up') }}</strong>
                            — {{ translate('Follow_up_for') }} {{ $partyLabel }} {{ translate('due') }}
                            {{ $partyMeta['followup']->date->format('d M Y, h:i A') }}.
                        @endif
                        {{ translate('Please_take_action') }}
                    </div>
                </div>
                <button type="button"
                        class="btn btn-sm {{ !empty($partyMeta['is_overdue']) ? 'btn-danger' : 'btn-warning' }}"
                        data-bs-toggle="modal"
                        data-bs-target="#takeFollowupModal"
                        data-booking-take-followup
                        data-followup-id="{{ $partyMeta['followup']->id }}">
                    <span class="material-icons">event_available</span>
                    {{ translate('Take_Follow_up') }}
                </button>
            </div>
        @endif
    @endforeach
@endif
