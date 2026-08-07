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
                            <strong>{{ translate('Follow_up_due') }}</strong>
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
                        data-followup-id="{{ $partyMeta['followup']->id }}"
                        data-followup-update-url="{{ route('admin.booking.followup.update', [$bookingRef->id, $partyMeta['followup']->id]) }}"
                        data-followup-for="{{ $partyMeta['followup']->for }}"
                        data-followup-date="{{ $partyMeta['followup']->date?->format('d M Y, h:i A') }}"
                        data-followup-urgency="{{ $partyMeta['followup']->urgency ?: 'medium' }}"
                        data-followup-reason="{{ $partyMeta['followup']->reason }}">
                    <span class="material-icons">event_available</span>
                    {{ translate('Take_Follow_up') }}
                </button>
            </div>
        @endif
    @endforeach
@endif
