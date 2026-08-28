<div class="party-card party-card--detail-panel party-card--booking-info w-100">
    <div class="party-card__head">
        <span class="party-card__head-text">{{ translate('Booking_Information') }}</span>
        @can('booking_edit')
            @if($bookingCanCorrectLineItems ?? !$bookingNotEditable)
                <button type="button" class="party-card__head-action" data-bs-toggle="modal"
                        data-bs-target="#bookingInfoModal--{{ $booking->id }}">
                    {{ translate('Update') }}
                </button>
            @endif
        @endcan
    </div>
    <div class="party-card__body party-card__body--stats">
        <dl class="detail-kv">
            <div class="detail-kv__row">
                <dt>{{ translate('Assignee') }}</dt>
                <dd id="booking-assignee-display">
                    @if($booking->assignee_id && $booking->assignee)
                        @php $assigneeName = trim($booking->assignee->first_name . ' ' . $booking->assignee->last_name); @endphp
                        <span class="detail-kv__assignee">
                            <img class="detail-kv__assignee-avatar" src="{{ $booking->assignee->profile_image_full_path }}" alt="{{ $assigneeName }}">
                            <span class="detail-kv__assignee-name">{{ $assigneeName }}</span>
                        </span>
                    @else
                        {{ translate('Unassigned') }}
                    @endif
                </dd>
            </div>
            <div class="detail-kv__row">
                <dt>{{ translate('Source') }}</dt>
                <dd id="booking-source-display">
                    {{ booking_source_display_label($booking->booking_source) }}
                </dd>
            </div>
            <div class="detail-kv__row">
                <dt>{{ translate('Service_Additional_Details') }}</dt>
                <dd id="booking-service-description-display">{{ $booking->service_description ?: translate('Not_specified') }}</dd>
            </div>
        </dl>
    </div>
</div>
