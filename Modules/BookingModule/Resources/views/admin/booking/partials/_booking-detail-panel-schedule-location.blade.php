@php
    $serviceScheduleLocalValue = \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d\TH:i');
    $scheduleHistoriesCount = (int) ($booking?->schedule_histories?->count() ?? 0);
@endphp
<div class="party-card party-card--detail-panel party-card--schedule-location w-100">
    <div class="party-card__head">
        <span class="party-card__head-text">{{ translate('Schedule_and_Location') }}</span>
        <div class="party-card__head-actions">
            @can('booking_edit')
                @if(!$bookingNotEditable && app(\Modules\BookingModule\Services\AdminRepeatBookingScheduleService::class)->canConvert($booking))
                    <button type="button" class="party-card__head-action" data-bs-toggle="modal"
                            data-bs-target="#convertToRepeatModal--{{ $booking->id }}">
                        {{ translate('Convert_to_repeat') }}
                    </button>
                @endif
            @endcan
            @can('booking_can_manage_status')
                @if(!$bookingNotEditable && !in_array($booking->booking_status, ['ongoing', 'completed']))
                    <button type="button" class="party-card__head-action" id="booking-schedule-edit-toggle-side">
                        {{ translate('Reschedule') }} {{ translate('Service') }}
                    </button>
                @endif
            @endcan
            @if($serviceAtProviderPlace == 1)
                @if($booking->provider_id)
                    @php
                        $serviceLocationStack = getProviderSettings(providerId: $booking->provider_id, key: 'service_location', type: 'provider_config');
                    @endphp
                    @if(in_array('customer', $serviceLocationStack) && in_array('provider', $serviceLocationStack))
                        @can('booking_edit')
                            @if(!$bookingNotEditable)
                                <button type="button" class="party-card__head-action" data-bs-toggle="modal" data-bs-target="#serviceLocationModal--{{ $booking['id'] }}">
                                    {{ translate('Update') }}
                                </button>
                            @endif
                        @endcan
                    @endif
                @else
                    @can('booking_edit')
                        @if(!$bookingNotEditable)
                            <button type="button" class="party-card__head-action" data-bs-toggle="modal" data-bs-target="#serviceLocationModal--{{ $booking['id'] }}">
                                {{ translate('Update') }}
                            </button>
                        @endif
                    @endcan
                @endif
            @endif
        </div>
    </div>
    <div class="party-card__body party-card__body--stats">
        <dl class="detail-kv">
            <div class="detail-kv__row">
                <dt class="detail-kv__label-with-action">
                    <span>{{ translate('Schedule_Date') }}</span>
                    @can('booking_can_manage_status')
                        @if(!$bookingNotEditable && !in_array($booking->booking_status, ['ongoing', 'completed']))
                            <button type="button" class="detail-kv__edit" id="booking-schedule-edit-toggle" title="{{ translate('Edit') }}" aria-label="{{ translate('Edit') }}">
                                <span class="material-icons">edit</span>
                            </button>
                        @endif
                    @endcan
                </dt>
                <dd id="booking-overview-service-schedule-wrap">
                    @can('booking_can_manage_status')
                        @if(!$bookingNotEditable && !in_array($booking->booking_status, ['ongoing', 'completed']))
                            <span id="booking-schedule-view-mode">
                                <span id="booking-overview-service-schedule">
                                    {{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}
                                    @if($scheduleHistoriesCount > 1)
                                        <span class="detail-kv__hint">({{ translate('Edited') }})</span>
                                    @endif
                                </span>
                            </span>
                            <span id="booking-schedule-edit-mode" class="d-none">
                                <input type="datetime-local" class="form-control form-control-sm"
                                       name="service_schedule"
                                       value="{{ $serviceScheduleLocalValue }}"
                                       id="service_schedule"
                                       data-original="{{ $serviceScheduleLocalValue }}"
                                       onchange="service_schedule_update()">
                            </span>
                        @else
                            <span id="booking-overview-service-schedule">
                                {{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}
                                @if($scheduleHistoriesCount > 1)
                                    <span class="detail-kv__hint">({{ translate('Edited') }})</span>
                                @endif
                            </span>
                        @endif
                    @else
                        <span id="booking-overview-service-schedule">
                            {{ date('d-M-Y h:ia', strtotime($booking->service_schedule)) }}
                            @if($scheduleHistoriesCount > 1)
                                <span class="detail-kv__hint">({{ translate('Edited') }})</span>
                            @endif
                        </span>
                    @endcan
                </dd>
            </div>
            <div class="detail-kv__row">
                <dt>{{ translate('Service_location') }}</dt>
                <dd>{{ $booking->service_location == 'provider' ? translate('Provider_Place') : translate('Customer_Home') }}</dd>
            </div>
            <div class="detail-kv__row">
                <dt>{{ translate('Address') }}</dt>
                <dd>
                    @if($booking->service_location == 'provider')
                        {{ Str::limit($booking?->provider?->company_address ?? translate('not_available'), 280) }}
                    @else
                        {{ Str::limit($booking?->service_address?->address ?? translate('not_available'), 280) }}
                    @endif
                </dd>
            </div>
            <div class="detail-kv__row">
                <dt>{{ translate('Zone') }} / {{ translate('Area') }}</dt>
                <dd>
                    @if($booking?->zone?->name)
                        {{ $booking->zone->name }}@if($booking->zone?->parentZone?->name) ({{ $booking->zone->parentZone->name }})@endif
                    @else
                        {{ translate('not_available') }}
                    @endif
                    @if($booking?->area?->name) — {{ $booking->area->name }}@endif
                </dd>
            </div>
            <div class="detail-kv__row">
                <dt>{{ translate('Booking_Otp') }}</dt>
                <dd id="booking-overview-booking-otp">{{ $booking?->booking_otp !== null && $booking?->booking_otp !== '' ? $booking->booking_otp : '—' }}</dd>
            </div>
            <div class="detail-kv__row detail-kv__row--note {{ $booking->service_location == 'provider' ? 'detail-kv__row--note-at-provider' : 'detail-kv__row--note-at-customer' }}">
                <dd>
                    @if($booking->service_location == 'provider')
                        {{ translate('Customer has to go to the Provider Location to receive the service') }}
                    @else
                        {{ translate('Provider has to go to the Customer Location to provide the service') }}
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</div>
