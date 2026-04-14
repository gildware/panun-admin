{{-- Single booking: fixed company commission override (replaces tier math for this job). --}}
@php
    $__bcoIsSubscription = \Modules\BookingModule\Entities\SubscriptionBookingType::where('booking_id', $booking->id)->where('type', 'subscription')->exists();
    $__bcoTierDefault = round((float) ($bfsDefaultCustomAdminCommission ?? 0), 2);
    $__bcoHasOverride = $booking->admin_commission_override !== null;
@endphp
@if((int)($booking->is_repeated ?? 0) === 0 && ! $__bcoIsSubscription)
    @can('booking_edit')
        <div class="card mt-3 border-0 shadow-sm">
            <div class="card-body py-3 px-3">
                <h6 class="mb-2 fz-14 fw-semibold">{{ translate('Booking_commission_override_section_title') }}</h6>
                <p class="text-muted small mb-2">{{ translate('Booking_commission_override_help') }}</p>
                <p class="text-muted small mb-3">
                    {{ translate('Booking_commission_override_tier_default') }}:
                    <strong>{{ with_currency_symbol($__bcoTierDefault) }}</strong>
                </p>
                @if($__bcoHasOverride)
                    <p class="mb-3 small">
                        <span class="text-muted">{{ translate('Booking_commission_override_current') }}:</span>
                        <strong>{{ with_currency_symbol((float) $booking->admin_commission_override) }}</strong>
                    </p>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn--primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#bookingCommissionOverrideModal--{{ $booking->id }}">
                        {{ translate('Booking_commission_override_button') }}
                    </button>
                    @if($__bcoHasOverride)
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#bookingCommissionRevertConfirmModal--{{ $booking->id }}">
                            {{ translate('Booking_commission_override_use_default_button') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal fade" id="bookingCommissionOverrideModal--{{ $booking->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('admin.booking.admin_commission_override.update', $booking->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Booking_commission_override_modal_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-0">
                                <label class="form-label" for="admin_commission_override--{{ $booking->id }}">{{ translate('Booking_commission_override_amount_label') }}</label>
                                <input type="number" step="0.01" min="0" class="form-control @error('admin_commission_override') is-invalid @enderror"
                                       id="admin_commission_override--{{ $booking->id }}" name="admin_commission_override" required
                                       value="{{ old('admin_commission_override', $__bcoHasOverride ? round((float) $booking->admin_commission_override, 2) : $__bcoTierDefault) }}">
                                @error('admin_commission_override')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="bookingCommissionRevertConfirmModal--{{ $booking->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('admin.booking.admin_commission_override.update', $booking->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="revert_to_tier_commission" value="1">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Booking_commission_override_revert_confirm_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0 text-muted">{{ translate('Booking_commission_override_revert_confirm_body') }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Booking_commission_override_revert_confirm_submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endif
