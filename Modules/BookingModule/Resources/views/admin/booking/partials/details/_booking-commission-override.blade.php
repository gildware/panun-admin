{{-- Single booking: fixed company commission override (replaces tier math for this job). --}}
@php
    $__bcoIsSubscription = \Modules\BookingModule\Entities\SubscriptionBookingType::where('booking_id', $booking->id)->where('type', 'subscription')->exists();
    $__bcoTierDefault = round((float) ($bfsDefaultCustomAdminCommission ?? 0), 2);
    $__bcoHasOverride = $booking->admin_commission_override !== null;
    $__bcoGrandTotal = round((float) get_booking_total_amount($booking), 2);
    $__bcoExtraFee = round((float) ($booking->extra_fee ?? 0), 2);
    $__bcoServiceAmount = round(max(0.0, $__bcoGrandTotal - $__bcoExtraFee), 2);
    $__bcoBreakdown = get_commission_breakdown_for_booking($booking);
    $__bcoPromoAdmin = round((float) ($__bcoBreakdown['promotional_cost_by_admin'] ?? 0), 2);
    $__bcoSettlement = get_booking_received_and_settlement($booking);
    $__bcoReceivedCompany = round((float) ($__bcoSettlement['amount_received_by_company'] ?? 0), 2);
    $__bcoReceivedProvider = round((float) ($__bcoSettlement['amount_received_by_provider'] ?? 0), 2);
    $__bcoTotalPaid = round((float) ($__bcoSettlement['total_paid'] ?? 0), 2);
    $__bcoCurrency = function_exists('currency_symbol') ? currency_symbol() : '₹';
    $__bcoCurrencyPosition = (string) (business_config('currency_symbol_position', 'business_information')?->live_values ?? 'left');
    $__bcoDecimalPoint = (int) (business_config('currency_decimal_point', 'business_information')?->live_values ?? 2);
    $__bcoInputDefault = old(
        'admin_commission_override',
        $__bcoHasOverride ? round((float) $booking->admin_commission_override, 2) : $__bcoTierDefault
    );
@endphp
@if((int)($booking->is_repeated ?? 0) === 0 && ! $__bcoIsSubscription && (string) ($booking->booking_status ?? '') === 'completed')
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
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="post" action="{{ route('admin.booking.admin_commission_override.update', $booking->id) }}"
                          id="bookingCommissionOverrideForm--{{ $booking->id }}"
                          data-bco-preview
                          data-grand-total="{{ $__bcoGrandTotal }}"
                          data-promo-admin="{{ $__bcoPromoAdmin }}"
                          data-received-company="{{ $__bcoReceivedCompany }}"
                          data-received-provider="{{ $__bcoReceivedProvider }}"
                          data-currency="{{ $__bcoCurrency }}"
                          data-currency-position="{{ $__bcoCurrencyPosition }}"
                          data-decimal-point="{{ $__bcoDecimalPoint }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Booking_commission_override_modal_title') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="admin_commission_override--{{ $booking->id }}">{{ translate('Booking_commission_override_amount_label') }}</label>
                                <input type="number" step="0.01" min="0" class="form-control @error('admin_commission_override') is-invalid @enderror"
                                       id="admin_commission_override--{{ $booking->id }}" name="admin_commission_override" required
                                       data-bco-commission-input
                                       value="{{ $__bcoInputDefault }}">
                                @error('admin_commission_override')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <p class="text-muted small mb-0 mt-1">
                                    {{ translate('Booking_commission_override_tier_default') }}:
                                    {{ with_currency_symbol($__bcoTierDefault) }}
                                </p>
                            </div>

                            <div class="border rounded p-3 bg-light" data-bco-preview-panel>
                                <h6 class="fz-13 fw-semibold mb-1">{{ translate('Booking_commission_override_preview_title') }}</h6>
                                <p class="text-muted small mb-3">{{ translate('Booking_commission_override_preview_help') }}</p>
                                <dl class="row mb-0 gy-2 fz-13">
                                    <dt class="col-7 text-muted mb-0">{{ translate('Grand_Total') }}</dt>
                                    <dd class="col-5 text-end mb-0 fw-semibold">{{ with_currency_symbol($__bcoGrandTotal) }}</dd>

                                    @if($__bcoServiceAmount > 0)
                                        <dt class="col-7 text-muted mb-0">{{ translate('service_amount') }}</dt>
                                        <dd class="col-5 text-end mb-0">{{ with_currency_symbol($__bcoServiceAmount) }}</dd>
                                    @endif
                                    @if($__bcoExtraFee > 0)
                                        <dt class="col-7 text-muted mb-0">{{ translate('Visit_extra_fee') }}</dt>
                                        <dd class="col-5 text-end mb-0">{{ with_currency_symbol($__bcoExtraFee) }}</dd>
                                    @endif

                                    <dt class="col-7 text-muted mb-0">{{ translate('Company_share') }} ({{ translate('Commission') }})</dt>
                                    <dd class="col-5 text-end mb-0 fw-semibold" data-bco-company-share>—</dd>

                                    <dt class="col-7 text-muted mb-0">{{ translate('Provider_share') }}</dt>
                                    <dd class="col-5 text-end mb-0 fw-semibold" data-bco-provider-share>—</dd>

                                    @if($__bcoPromoAdmin > 0.009)
                                        <dt class="col-7 text-muted mb-0">{{ translate('Booking_commission_override_promo_admin') }}</dt>
                                        <dd class="col-5 text-end mb-0">{{ with_currency_symbol($__bcoPromoAdmin) }}</dd>
                                    @endif

                                    <dt class="col-7 text-muted mb-0 pt-2 border-top">{{ translate('Total_paid') }}</dt>
                                    <dd class="col-5 text-end mb-0 pt-2 border-top">{{ with_currency_symbol($__bcoTotalPaid) }}</dd>
                                    <dt class="col-7 text-muted mb-0">{{ translate('Received_by_company') }}</dt>
                                    <dd class="col-5 text-end mb-0">{{ with_currency_symbol($__bcoReceivedCompany) }}</dd>
                                    <dt class="col-7 text-muted mb-0">{{ translate('Received_by_provider') }}</dt>
                                    <dd class="col-5 text-end mb-0">{{ with_currency_symbol($__bcoReceivedProvider) }}</dd>

                                    <dt class="col-7 text-muted mb-0" data-bco-pay-label>{{ translate('Pay_to_provider') }}</dt>
                                    <dd class="col-5 text-end mb-0 fw-semibold text-info" data-bco-pay-to-provider>—</dd>
                                    <dt class="col-7 text-muted mb-0" data-bco-owe-label>{{ translate('Provider_owes_you') }}</dt>
                                    <dd class="col-5 text-end mb-0 fw-semibold text-warning" data-bco-provider-owes>—</dd>
                                </dl>
                                <p class="text-danger small mb-0 mt-2 d-none" data-bco-exceed-hint>
                                    {{ translate('Booking_commission_override_exceeds_bill') }}
                                </p>
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

        @once
            @push('script')
                <script>
                    (function () {
                        function money(form, amount) {
                            var n = Math.round((Number(amount) + Number.EPSILON) * 100) / 100;
                            if (!isFinite(n)) n = 0;
                            var symbol = form.getAttribute('data-currency') || '₹';
                            var position = (form.getAttribute('data-currency-position') || 'left').toLowerCase();
                            var decimals = parseInt(form.getAttribute('data-decimal-point') || '2', 10);
                            if (!isFinite(decimals) || decimals < 0) decimals = 2;
                            var formatted = n.toLocaleString('en-IN', {
                                minimumFractionDigits: decimals,
                                maximumFractionDigits: decimals
                            });
                            return position === 'right' ? (formatted + symbol) : (symbol + formatted);
                        }

                        function refreshPreview(form) {
                            if (!form) return;
                            var input = form.querySelector('[data-bco-commission-input]');
                            var grand = parseFloat(form.getAttribute('data-grand-total') || '0') || 0;
                            var promo = parseFloat(form.getAttribute('data-promo-admin') || '0') || 0;
                            var receivedCompany = parseFloat(form.getAttribute('data-received-company') || '0') || 0;
                            var receivedProvider = parseFloat(form.getAttribute('data-received-provider') || '0') || 0;
                            var raw = input ? parseFloat(input.value) : NaN;
                            var companyGross = isFinite(raw) && raw >= 0 ? raw : 0;
                            var companyShare = Math.max(0, Math.round((companyGross - promo) * 100) / 100);
                            var providerShare = Math.max(0, Math.round((grand - companyShare) * 100) / 100);
                            var companyKeep = Math.max(0, companyShare);
                            var payToProvider = Math.max(0, Math.round((receivedCompany - companyKeep) * 100) / 100);
                            var shortfall = Math.max(0, companyKeep - receivedCompany);
                            var providerOwes = receivedProvider > 0
                                ? Math.min(receivedProvider, Math.round(shortfall * 100) / 100)
                                : 0;
                            providerOwes = Math.round(providerOwes * 100) / 100;

                            var companyEl = form.querySelector('[data-bco-company-share]');
                            var providerEl = form.querySelector('[data-bco-provider-share]');
                            var payEl = form.querySelector('[data-bco-pay-to-provider]');
                            var oweEl = form.querySelector('[data-bco-provider-owes]');
                            var exceedEl = form.querySelector('[data-bco-exceed-hint]');
                            if (companyEl) companyEl.textContent = money(form, companyShare);
                            if (providerEl) providerEl.textContent = money(form, providerShare);
                            if (payEl) payEl.textContent = money(form, payToProvider);
                            if (oweEl) oweEl.textContent = money(form, providerOwes);
                            if (exceedEl) {
                                exceedEl.classList.toggle('d-none', !(companyGross > grand + 0.009));
                            }
                        }

                        function bindAll() {
                            document.querySelectorAll('form[data-bco-preview]').forEach(function (form) {
                                refreshPreview(form);
                                var modal = form.closest('.modal');
                                if (modal && modal.dataset.bcoModalBound !== '1') {
                                    modal.dataset.bcoModalBound = '1';
                                    modal.addEventListener('shown.bs.modal', function () {
                                        refreshPreview(form);
                                    });
                                }
                            });
                        }

                        if (!window.__bcoPreviewDelegated) {
                            window.__bcoPreviewDelegated = true;
                            document.addEventListener('input', function (e) {
                                var input = e.target && e.target.closest ? e.target.closest('[data-bco-commission-input]') : null;
                                if (!input) return;
                                var form = input.closest('form[data-bco-preview]');
                                if (form) refreshPreview(form);
                            });
                            document.addEventListener('change', function (e) {
                                var input = e.target && e.target.closest ? e.target.closest('[data-bco-commission-input]') : null;
                                if (!input) return;
                                var form = input.closest('form[data-bco-preview]');
                                if (form) refreshPreview(form);
                            });
                        }

                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', bindAll);
                        } else {
                            bindAll();
                        }
                        document.addEventListener('turbo:load', bindAll);
                        document.addEventListener('turbo:frame-load', bindAll);
                    })();
                </script>
            @endpush
        @endonce
    @endcan
@endif
