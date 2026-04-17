{{-- Single booking only; expects: $booking, $financialSettlementOutcomes, $defaultVisitFeeCompanyPercent, $bookingCancellationReasons (optional) --}}
@can('booking_can_manage_status')
@if((int)($booking->is_repeated ?? 0) === 0)
@php
    $bfsCurOutcome = trim((string) ($booking->settlement_outcome ?? ''));
    $bfsCfg = is_array($booking->settlement_config ?? null) ? $booking->settlement_config : [];
    $bfsDefVisitPaid = array_key_exists('visit_charges_paid', $bfsCfg) ? $bfsCfg['visit_charges_paid'] : (float) ($booking->extra_fee ?? 0);
    $bfsDefClosing = (float) ($bfsCfg['closing_amount_paid'] ?? 0);
    $bfsHasSavedClosingShares = (array_key_exists('closing_company_share', $bfsCfg) && is_numeric($bfsCfg['closing_company_share']))
        || (array_key_exists('closing_provider_share', $bfsCfg) && is_numeric($bfsCfg['closing_provider_share']));
    $bfsDefClosingCo = '';
    $bfsDefClosingPr = '';
    if ($bfsHasSavedClosingShares) {
        if (array_key_exists('closing_company_share', $bfsCfg) && is_numeric($bfsCfg['closing_company_share'])) {
            $bfsDefClosingCo = $bfsCfg['closing_company_share'];
        }
        if (array_key_exists('closing_provider_share', $bfsCfg) && is_numeric($bfsCfg['closing_provider_share'])) {
            $bfsDefClosingPr = $bfsCfg['closing_provider_share'];
        }
    } elseif ($bfsDefClosing > 0) {
        $__bfsTier = app(\Modules\BookingModule\Services\BookingFinancialSettlementService::class);
        [$__bfsCoCl, $__bfsPrCl] = $__bfsTier->resolveClosingCompanyProviderShares($booking, $bfsDefClosing, [], $booking->provider_id);
        $bfsDefClosingCo = $__bfsCoCl;
        $bfsDefClosingPr = $__bfsPrCl;
    }
    $bfsSymCur = function_exists('currency_symbol') ? currency_symbol() : '₹';
    $bfsHasSavedVisitAmounts = (array_key_exists('visit_company_amount', $bfsCfg) && is_numeric($bfsCfg['visit_company_amount']))
        || (array_key_exists('visit_provider_amount', $bfsCfg) && is_numeric($bfsCfg['visit_provider_amount']));
    $__bfsVisitBasis = (float) $bfsDefVisitPaid;
    $__bfsCoPctForVisit = is_numeric($bfsCfg['visit_fee_company_percent'] ?? null)
        ? (float) $bfsCfg['visit_fee_company_percent']
        : (float) $defaultVisitFeeCompanyPercent;
    if ($bfsHasSavedVisitAmounts) {
        $bfsDefVisitCoAmt = (array_key_exists('visit_company_amount', $bfsCfg) && is_numeric($bfsCfg['visit_company_amount']))
            ? round((float) $bfsCfg['visit_company_amount'], 2) : '';
        $bfsDefVisitPrAmt = (array_key_exists('visit_provider_amount', $bfsCfg) && is_numeric($bfsCfg['visit_provider_amount']))
            ? round((float) $bfsCfg['visit_provider_amount'], 2) : '';
    } else {
        $bfsDefVisitCoAmt = $__bfsVisitBasis > 0
            ? round($__bfsVisitBasis * ($__bfsCoPctForVisit / 100.0), 2)
            : 0;
        $bfsDefVisitPrAmt = $__bfsVisitBasis > 0
            ? round(max(0.0, $__bfsVisitBasis - (float) $bfsDefVisitCoAmt), 2)
            : 0;
    }
    $bfsPopoverByOutcome = [
        \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL => translate('Bfs_popover_cancel_keep_visit'),
        \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT => translate('Bfs_popover_complete_visit_only'),
        \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS => translate('Bfs_popover_scaled'),
    ];
    $bfsDefScaledPaid = array_key_exists('scaled_customer_paid_amount', $bfsCfg) && is_numeric($bfsCfg['scaled_customer_paid_amount'])
        ? round((float) $bfsCfg['scaled_customer_paid_amount'], 2)
        : round((float) get_booking_total_paid($booking), 2);
    $bfsDefScaledLossCo = (array_key_exists('scaled_loss_company_amount', $bfsCfg) && is_numeric($bfsCfg['scaled_loss_company_amount']))
        ? round((float) $bfsCfg['scaled_loss_company_amount'], 2) : '';
    $bfsDefScaledLossPr = (array_key_exists('scaled_loss_provider_amount', $bfsCfg) && is_numeric($bfsCfg['scaled_loss_provider_amount']))
        ? round((float) $bfsCfg['scaled_loss_provider_amount'], 2) : '';
@endphp
<div class="modal fade" id="bookingFinancialSettlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title mb-0">{{ translate('Special_financial_settlement') }}</h5>
                    <p class="text-muted small mb-0 mt-1">{{ translate('Financial_settlement_help_intro') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-2">{{ translate('Scenario') }}</label>
                    <div class="d-flex flex-column gap-2" id="bfs-scenario-radios">
                        @php
                            $bfsScaledOutcomeVal = \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
                            $bfsDisableScaledWhenDueZero = $booking instanceof \Modules\BookingModule\Entities\Booking
                                && \Modules\BookingModule\Services\BookingFinancialSettlementService::scaledLossMakingSelectionUnavailableDueToZeroDue($booking);
                        @endphp
                        @foreach($financialSettlementOutcomes ?? [] as $value => $label)
                            @php
                                $bfsIsScaled = (string) $value === (string) $bfsScaledOutcomeVal;
                                $bfsOutcomeDisabled = $bfsIsScaled && $bfsDisableScaledWhenDueZero;
                            @endphp
                            <div class="d-flex align-items-start gap-2 p-2 rounded border border-light bg-white">
                                <div class="form-check flex-grow-1 mb-0 pt-1">
                                    <input class="form-check-input bfs-outcome-radio" type="radio" name="bfs_outcome_radio" id="bfs-outcome-{{ $value }}" value="{{ $value }}"
                                        @if($bfsCurOutcome !== '' && $bfsCurOutcome === (string) $value) checked @endif
                                        @if($bfsOutcomeDisabled) disabled title="{{ translate('Bfs_scaled_disabled_when_due_zero') }}" @endif>
                                    <label class="form-check-label w-100" for="bfs-outcome-{{ $value }}">
                                        <span class="fw-medium d-block">{{ $label }}</span>
                                        @if($bfsOutcomeDisabled)
                                            <span class="small text-muted d-block mt-1">{{ translate('Bfs_scaled_disabled_when_due_zero') }}</span>
                                        @endif
                                    </label>
                                </div>
                                <button type="button" class="btn btn-link text-info p-0 mt-1 flex-shrink-0 bfs-info-btn lh-1"
                                        data-bfs-outcome="{{ $value }}"
                                        aria-label="{{ translate('Scenario_info') }}"
                                        title="{{ translate('Scenario_info') }}">
                                    <span class="material-icons fz-20" aria-hidden="true">info</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="bfs-fields-decided-charges" class="d-none border rounded p-3 mb-3 bg-light">
                    <p id="bfs-decided-subtitle-cancel" class="small fw-semibold text-uppercase text-muted mb-2 d-none">{{ translate('Bfs_label_cancel_keep_visit') }}</p>
                    <p id="bfs-decided-subtitle-complete" class="small fw-semibold text-uppercase text-muted mb-2 d-none">{{ translate('Bfs_label_complete_visit_only') }}</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-visit-charges-paid">{{ translate('Bfs_visit_charges_paid_by_customer') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control bfs-preview-trigger" id="bfs-visit-charges-paid"
                                   value="{{ old('visit_charges_paid', $bfsDefVisitPaid) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-visit-company-amt">{{ translate('Bfs_company_share_visit_percent') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control bfs-preview-trigger" id="bfs-visit-company-amt"
                                   value="{{ old('visit_company_amount', $bfsDefVisitCoAmt) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-visit-provider-amt">{{ translate('Bfs_provider_share_visit_percent') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control bfs-preview-trigger" id="bfs-visit-provider-amt"
                                   value="{{ old('visit_provider_amount', $bfsDefVisitPrAmt) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-closing-amount">{{ translate('Bfs_closing_amount_paid_by_customer') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control bfs-preview-trigger" id="bfs-closing-amount"
                                   value="{{ old('closing_amount_paid', $bfsDefClosing) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-closing-company">{{ translate('Bfs_closing_company_share_input') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control bfs-preview-trigger" id="bfs-closing-company"
                                   value="{{ old('closing_company_share', $bfsDefClosingCo) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-closing-provider">{{ translate('Bfs_closing_provider_share_input') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control bfs-preview-trigger" id="bfs-closing-provider"
                                   value="{{ old('closing_provider_share', $bfsDefClosingPr) }}">
                        </div>
                    </div>
                    <p class="small text-muted mt-2 mb-3">{{ translate('Bfs_closing_shares_tier_hint') }}</p>
                    <div id="bfs-cancel-reason-wrap" class="d-none">
                        <label class="form-label" for="bfs-cancel-reason">{{ translate('Cancellation_Reason') }}</label>
                        <select class="form-select bfs-preview-trigger" id="bfs-cancel-reason">
                            <option value="">{{ translate('Select') }}</option>
                            @foreach(($bookingCancellationReasons ?? collect()) as $reason)
                                <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="bfs-fields-scaled" class="d-none border rounded p-3 mb-3 bg-light">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-scaled-customer-paid">{{ translate('Bfs_scaled_amount_paid_by_customer') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="bfs-scaled-customer-paid"
                                   value="{{ old('scaled_customer_paid_amount', $bfsDefScaledPaid) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-scaled-loss-company">{{ translate('Bfs_scaled_loss_company_share') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="bfs-scaled-loss-company"
                                   value="{{ old('scaled_loss_company_amount', $bfsDefScaledLossCo) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bfs-scaled-loss-provider">{{ translate('Bfs_scaled_loss_provider_share') }} ({{ $bfsSymCur }})</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="bfs-scaled-loss-provider"
                                   value="{{ old('scaled_loss_provider_amount', $bfsDefScaledLossPr) }}">
                        </div>
                    </div>
                    <p class="small text-muted mb-2 mt-1">{{ translate('Bfs_scaled_loss_split_hint') }}</p>
                    <small class="text-muted d-block mt-2 mb-3">{{ translate('Scaled_settlement_explain') }}</small>
                </div>

                <div class="mb-3" id="bfs-notes-wrap">
                    <label class="form-label">{{ translate('Notes') }}</label>
                    <textarea class="form-control bfs-preview-trigger" id="bfs-remarks" rows="2" maxlength="2000" placeholder="{{ translate('Optional') }}">{{ $booking->settlement_remarks }}</textarea>
                </div>

                <div class="border rounded p-3 mb-0 bg-light" id="bfs-preview-wrap">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">{{ translate('Preview') }}</span>
                        <span class="small text-muted d-none" id="bfs-preview-loading">{{ translate('Bfs_preview_loading') }}</span>
                    </div>
                    <div id="bfs-preview-kv" class="bfs-preview-kv small">
                        <div class="text-muted py-2" id="bfs-preview-placeholder">{{ translate('Bfs_preview_empty') }}</div>
                    </div>
                </div>

                @if(!empty($bfsAllowCollectPayment))
                @php
                    $bfsEmbedPayLossCaps = $bfsAddPayLossCaps ?? null;
                    if ($bfsEmbedPayLossCaps === null && isset($booking) && trim((string) ($booking->settlement_outcome ?? '')) === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS) {
                        $bfsEmbedPayLossCaps = booking_admin_loss_recovery_split_caps($booking);
                    }
                @endphp
                <div id="bfs-payment-embed-wrap" class="d-none border rounded p-3 mt-3 bg-white">
                    <h6 class="fw-semibold mb-1" id="bfs-collect-payment-title">{{ translate('Bfs_collect_payment_section_title') }}</h6>
                    <p class="small text-muted mb-3" id="bfs-collect-payment-hint">{{ translate('Bfs_collect_payment_section_hint') }}</p>
                    <form method="post" action="{{ route('admin.booking.add-payment', [$booking->id]) }}" class="add-payment-form bfs-add-payment-form" data-due-amount="0" data-default-date="{{ date('Y-m-d') }}" id="bfs-add-payment-form" data-bfs-outcome-scaled="{{ \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS }}" data-loss-allocation="{{ $booking->isLossMakingFinancialSettlement() ? '1' : '0' }}" @if(!empty($bfsEmbedPayLossCaps)) data-loss-cap-provider="{{ $bfsEmbedPayLossCaps['provider'] }}" data-loss-cap-company="{{ $bfsEmbedPayLossCaps['company'] }}" @endif novalidate>
                        @csrf
                        <input type="hidden" name="bfs_decided_charges_cap" id="bfs-cap-decided-charges" value="0">
                        <input type="hidden" name="bfs_scaled_loss_cap" id="bfs-cap-scaled-loss" value="0">
                        <input type="hidden" name="scaled_customer_paid_amount" id="bfs-cap-scaled-customer-paid" value="">
                        <input type="hidden" name="bfs_settlement_outcome" id="bfs-cap-settlement-outcome" value="">
                        <input type="hidden" name="visit_charges_paid" id="bfs-cap-visit-charges" value="">
                        <input type="hidden" name="closing_amount_paid" id="bfs-cap-closing" value="">
                        <div class="alert alert-danger d-none add-payment-modal-errors mb-3" role="alert"></div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span> <small class="text-muted">(<span id="bfs-embed-due-label">{{ translate('Due amount') }}</span>: <span id="bfs-embed-due-val">—</span>)</small></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control add-payment-amount bfs-embed-pay-amount" required id="bfs-embed-pay-amount" value="">
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">{{ translate('Received by') }} <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                @if(((string) ($booking->booking_status ?? '')) === 'ongoing')
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="received_by" id="bfsEmbedPayRcvdProvider" value="provider" checked>
                                        <label class="form-check-label" for="bfsEmbedPayRcvdProvider">{{ translate('Provider') }}</label>
                                    </div>
                                @endif
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="received_by" id="bfsEmbedPayRcvdCompany" value="company" @if(((string) ($booking->booking_status ?? '')) !== 'ongoing') checked @endif>
                                    <label class="form-check-label" for="bfsEmbedPayRcvdCompany">{{ translate('Company') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 add-payment-company-inflow-wrap d-none">
                            <div class="row g-2">
                                @include('bookingmodule::admin.booking.partials._admin-company-inflow-payment-method', [
                                    'instanceId' => 'bfs-addpay-' . $booking->id,
                                    'advancePaymentMethodGroups' => $advancePaymentMethodGroups ?? [],
                                    'advancePmDisabled' => false,
                                    'advancePmSelected' => '',
                                ])
                            </div>
                            <div class="mb-0 mt-2">
                                <label class="form-label">{{ translate('Reference_Note') }} <span class="text-muted small">({{ translate('Optional') }})</span></label>
                                <textarea name="company_inflow_note" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Optional_note') }}"></textarea>
                                <small class="text-muted d-block mt-1 fz-11">{{ translate('Reference_note_can_fill_transaction_field') }}</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Date') }}</label>
                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        @if($booking->isLossMakingFinancialSettlement())
                            <div class="mb-3 add-payment-loss-allocation-wrap border rounded p-3 bg-light">
                                <div class="fw-semibold fz-12 mb-2">{{ translate('Bfs_loss_recovery_split_title') }}</div>
                                <p class="small text-muted mb-2">{{ translate('Bfs_loss_recovery_split_intro') }}</p>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">{{ translate('Bfs_add_payment_split_provider') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" @if(!empty($bfsEmbedPayLossCaps)) max="{{ $bfsEmbedPayLossCaps['provider'] }}" @endif name="split_amount_provider" class="form-control add-payment-split-provider" value="0" id="bfs-embed-split-provider">
                                        @if(!empty($bfsEmbedPayLossCaps))
                                            <div class="small text-muted mt-1 mb-0">{{ translate('Bfs_add_payment_split_max_to_provider_loss') }} {{ with_currency_symbol($bfsEmbedPayLossCaps['provider']) }}</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-1">{{ translate('Bfs_add_payment_split_company') }} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" @if(!empty($bfsEmbedPayLossCaps)) max="{{ $bfsEmbedPayLossCaps['company'] }}" @endif name="split_amount_company" class="form-control add-payment-split-company" value="0" id="bfs-embed-split-company">
                                        @if(!empty($bfsEmbedPayLossCaps))
                                            <div class="small text-muted mt-1 mb-0">{{ translate('Bfs_add_payment_split_max_to_company_loss') }} {{ with_currency_symbol($bfsEmbedPayLossCaps['company']) }}</div>
                                        @endif
                                    </div>
                                </div>
                                <p class="small text-muted mt-2 mb-0">{{ translate('Bfs_add_payment_split_sum_hint') }}</p>
                            </div>
                        @endif
                        <button type="submit" class="btn btn--primary">{{ translate('Add payment') }}</button>
                    </form>
                </div>
                @endif
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                <button type="button" class="btn btn--primary" id="bfs-save-btn">{{ translate('Save') }}</button>
                <button type="button" class="btn btn-success d-none" id="bfs-save-complete-btn">{{ translate('Bfs_save_and_complete') }}</button>
                <button type="button" class="btn btn-danger d-none" id="bfs-save-cancel-btn">{{ translate('Bfs_save_and_cancel') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Nested confirmation (same pattern as admin confirmChangeModal); shown when closing settlement modal after embedded add-payment without saving. --}}
<div class="modal fade" id="bfsUnsavedPaymentConfirmModal" tabindex="-1" role="dialog" aria-labelledby="bfsUnsavedPaymentConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body mb-30 pb-0 text-center">
                <img width="80" src="{{ asset('assets/admin-module/img/icons/status-on.png') }}" alt="" class="mb-20" aria-hidden="true">
                <h3 class="mb-3" id="bfsUnsavedPaymentConfirmModalLabel">{{ translate('Are_you_sure') }}</h3>
                <p class="mb-0 text-start text-break">{{ translate('Bfs_close_modal_unsaved_embedded_payment_confirm') }}</p>
                <div class="btn--container mt-30 justify-content-center gap-2">
                    <button type="button" class="btn btn--secondary min-w-120 rounded" data-bs-dismiss="modal">{{ translate('No') }}</button>
                    <button type="button" class="btn btn--primary min-w-120 rounded" id="bfs-unsaved-payment-confirm-proceed">{{ translate('Bfs_close_modal_confirm_remove_embedded_payment') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bfs-preview-kv .bfs-kv-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.35rem 0;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .bfs-preview-kv .bfs-kv-row:last-child { border-bottom: 0; }
    .bfs-preview-kv .bfs-kv-key { color: #6c757d; flex: 1 1 45%; }
    .bfs-preview-kv .bfs-kv-val { font-weight: 500; text-align: right; flex: 1 1 50%; word-break: break-word; }
    .popover.bfs-popover-wide { max-width: min(380px, 92vw); }
    .popover.bfs-popover-wide .popover-body { font-size: 0.875rem; }
    .popover.bfs-popover-wide .popover-body p:last-child { margin-bottom: 0; }
</style>

@push('script')
<script>
(function () {
    const bfsPreviewUrl = @json(route('admin.booking.financial_settlement.preview', [$booking->id]));
    const bfsSaveUrl = @json(route('admin.booking.financial_settlement.save', [$booking->id]));
    const bfsSaveCancelUrl = @json(route('admin.booking.financial_settlement.save_and_cancel', [$booking->id]));
    const bfsSaveCompleteUrl = @json(route('admin.booking.financial_settlement.save_and_complete', [$booking->id]));
    const bfsRevertModalPartialsUrl = @json(route('admin.booking.financial_settlement.revert_modal_partials', [$booking->id]));
    const bfsCsrf = $('meta[name="csrf-token"]').attr('content');
    const bfsPopovers = @json($bfsPopoverByOutcome);
    const bfsPopoverTitle = @json(translate('Scenario_info'));

    const bfsHasSavedClosingShares = @json($bfsHasSavedClosingShares ?? false);
    const bfsHasSavedVisitAmounts = @json($bfsHasSavedVisitAmounts ?? false);
    const bfsAllowCollectPayment = @json(!empty($bfsAllowCollectPayment));
    const bfsBookingGrandTotal = @json(round((float) get_booking_total_amount($booking), 2));

    const OUTCOMES = {
        STANDARD: @json(\Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_STANDARD),
        VISIT_SPLIT: @json(\Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT),
        SCALED: @json(\Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS),
        VISIT_CANCEL: @json(\Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL),
    };

    const L = {
        bookingTotal: @json(translate('Booking_total')),
        visitFee: @json(translate('Visit_extra_fee')),
        collectedFromCustomer: @json(translate('Bfs_preview_collected_from_customer')),
        receivedCompany: @json(translate('Bfs_preview_received_by_company')),
        receivedProvider: @json(translate('Bfs_preview_received_by_provider')),
        companyShare: @json(translate('Bfs_preview_company_share')),
        providerShare: @json(translate('Bfs_preview_provider_share')),
        amountToCollect: @json(translate('Bfs_preview_amount_to_collect')),
        refundCustomer: @json(translate('Bfs_preview_refund_to_customer')),
        previewError: @json(translate('Bfs_preview_error')),
        cancellationReasonRequired: @json(translate('Bfs_cancellation_reason_required')),
        vcVisitingCharges: @json(translate('Bfs_preview_visiting_charges')),
        vcClosingAmount: @json(translate('Bfs_preview_closing_amount')),
        vcTotalProvider: @json(translate('Bfs_preview_total_provider_earning')),
        vcCompanyEarnings: @json(translate('Bfs_preview_total_company_earnings')),
        vcPaidByCustomer: @json(translate('Bfs_preview_total_paid_by_customer')),
        vcStillDue: @json(translate('Bfs_preview_still_due_from_customer')),
        vcRecvCompany: @json(translate('Bfs_preview_amount_received_company')),
        vcRecvProvider: @json(translate('Bfs_preview_amount_received_provider')),
        vcBookingFinal: @json(translate('Bfs_preview_booking_final_amount')),
        saveCancelBlockedDue: @json(translate('Bfs_save_cancel_disabled_due')),
        saveCompleteBlockedDue: @json(translate('Bfs_save_complete_disabled_due')),
        saveCompleteScaledBlockedGap: @json(translate('Bfs_save_complete_scaled_blocked_gap')),
        closeModalRevertPaymentFailed: @json(translate('Bfs_close_modal_revert_embedded_payment_failed')),
        collectPaymentHintDefault: @json(translate('Bfs_collect_payment_section_hint')),
        collectPaymentHintScaled: @json(translate('Bfs_collect_payment_section_hint_scaled')),
        collectPaymentTitleDefault: @json(translate('Bfs_collect_payment_section_title')),
        collectPaymentTitleScaled: @json(translate('Bfs_collect_payment_section_title_scaled')),
        scaledTotalBooking: @json(translate('Bfs_preview_scaled_total_booking')),
        scaledPaidByCustomer: @json(translate('Bfs_scaled_amount_paid_by_customer')),
        scaledLossAmount: @json(translate('Bfs_preview_scaled_loss_amount')),
        scaledLossCompany: @json(translate('Bfs_scaled_loss_company_share')),
        scaledLossProvider: @json(translate('Bfs_scaled_loss_provider_share')),
    };

    let bfsPreviewTimer = null;
    let bfsPreviewSeq = 0;
    let bfsClosingSharesUserEdited = !!bfsHasSavedClosingShares;
    let bfsClosingPairSync = false;
    let bfsVisitSharesUserEdited = !!bfsHasSavedVisitAmounts;
    let bfsVisitPairSync = false;
    let bfsLastDecidedPreview = null;
    let bfsScaledLossSync = false;
    let bfsScaledPaidSplitTimer = null;
    window.bfsModalSessionPartialPaymentIds = window.bfsModalSessionPartialPaymentIds || [];
    let bfsModalSkipCloseGuard = false;

    function bfsSelectedOutcome() {
        var $r = $('#bookingFinancialSettlementModal input[name="bfs_outcome_radio"]:checked');
        return String($r.val() || '').trim();
    }

    function bfsDecidedChargesScenarioSelected() {
        var v = bfsSelectedOutcome();
        return v === OUTCOMES.VISIT_SPLIT || v === OUTCOMES.VISIT_CANCEL;
    }

    function bfsPreviewOutcomeIsDecided(pv) {
        if (!pv || pv.outcome == null) {
            return false;
        }
        var o = String(pv.outcome).trim();
        return o === String(OUTCOMES.VISIT_CANCEL) || o === String(OUTCOMES.VISIT_SPLIT);
    }

    /** True when server built preview with visit+closing decided-charges math (not only outcome string match). */
    function bfsPreviewUsesDecidedVisitCharges(pv) {
        if (!pv) {
            return false;
        }
        if (pv.decided_visit_charges_mode === true || pv.decided_visit_charges_mode === 1) {
            return true;
        }
        return bfsPreviewOutcomeIsDecided(pv);
    }

    /** When UI is on a decided scenario, derive due from form + preview paid if preview flag is missing (edge cases). */
    function bfsClientDecidedDueFromFormAndPreview(pv) {
        var visit = parseFloat($('#bfs-visit-charges-paid').val()) || 0;
        var closing = parseFloat($('#bfs-closing-amount').val()) || 0;
        var retained = bfsRound2(visit + closing);
        var paid = bfsRound2(parseFloat(pv.collected_from_customer != null ? pv.collected_from_customer : pv.total_paid) || 0);
        return bfsRound2(Math.max(0, retained - paid));
    }

    function bfsEffectiveAmountDueFromCustomer(pv) {
        if (!pv) {
            return 0;
        }
        if (bfsDecidedChargesScenarioSelected()) {
            if (bfsPreviewUsesDecidedVisitCharges(pv)) {
                return bfsRound2(parseFloat(pv.amount_to_collect_from_customer) || 0);
            }
            return bfsClientDecidedDueFromFormAndPreview(pv);
        }
        return bfsRound2(parseFloat(pv.amount_to_collect_from_customer) || 0);
    }

    function bfsToggleFields() {
        const v = bfsSelectedOutcome();
        $('#bfs-fields-decided-charges, #bfs-fields-scaled').addClass('d-none');
        $('#bfs-decided-subtitle-cancel, #bfs-decided-subtitle-complete').addClass('d-none');
        if (v === OUTCOMES.VISIT_SPLIT || v === OUTCOMES.VISIT_CANCEL) {
            $('#bfs-fields-decided-charges').removeClass('d-none');
            if (v === OUTCOMES.VISIT_CANCEL) {
                $('#bfs-notes-wrap').addClass('d-none');
                $('#bfs-decided-subtitle-cancel').removeClass('d-none');
                $('#bfs-cancel-reason-wrap').removeClass('d-none');
            } else {
                $('#bfs-notes-wrap').removeClass('d-none');
                $('#bfs-decided-subtitle-complete').removeClass('d-none');
                $('#bfs-cancel-reason-wrap').addClass('d-none');
            }
        } else {
            $('#bfs-notes-wrap').removeClass('d-none');
            $('#bfs-cancel-reason-wrap').addClass('d-none');
        }
        if (v === OUTCOMES.SCALED) {
            $('#bfs-fields-scaled').removeClass('d-none');
        }
        $('#bfs-save-btn').removeClass('d-none');
        $('#bfs-save-cancel-btn').addClass('d-none');
        $('#bfs-save-complete-btn').addClass('d-none');
        if (v === OUTCOMES.VISIT_CANCEL) {
            $('#bfs-save-btn').addClass('d-none');
            $('#bfs-save-cancel-btn').removeClass('d-none').prop('disabled', true).attr('title', '');
        } else if (v === OUTCOMES.VISIT_SPLIT) {
            $('#bfs-save-btn').addClass('d-none');
            $('#bfs-save-complete-btn').removeClass('d-none').prop('disabled', true).attr('title', '');
        } else if (v === OUTCOMES.SCALED) {
            $('#bfs-save-btn').addClass('d-none');
            $('#bfs-save-complete-btn').removeClass('d-none').prop('disabled', true).attr('title', '');
        }
    }

    function bfsPayload() {
        const v = bfsSelectedOutcome();
        const base = {
            _token: bfsCsrf,
            settlement_outcome: v,
            settlement_remarks: v === OUTCOMES.VISIT_CANCEL ? null : ($('#bfs-remarks').val() || null),
        };
        if (v === OUTCOMES.VISIT_SPLIT || v === OUTCOMES.VISIT_CANCEL) {
            base.visit_charges_paid = $('#bfs-visit-charges-paid').val() || null;
            base.closing_amount_paid = $('#bfs-closing-amount').val() || null;
            var vCo = $('#bfs-visit-company-amt').val();
            var vPr = $('#bfs-visit-provider-amt').val();
            if (vCo !== '' && vCo !== null) {
                base.visit_company_amount = vCo;
            }
            if (vPr !== '' && vPr !== null) {
                base.visit_provider_amount = vPr;
            }
            if (bfsClosingSharesUserEdited) {
                var cCo = $('#bfs-closing-company').val();
                var cPr = $('#bfs-closing-provider').val();
                if (cCo !== '' && cCo !== null) {
                    base.closing_company_share = cCo;
                }
                if (cPr !== '' && cPr !== null) {
                    base.closing_provider_share = cPr;
                }
            }
        }
        if (v === OUTCOMES.SCALED) {
            base.scaled_customer_paid_amount = $('#bfs-scaled-customer-paid').val() || null;
            var slc = $('#bfs-scaled-loss-company').val();
            var slp = $('#bfs-scaled-loss-provider').val();
            base.scaled_loss_company_amount = (slc !== '' && slc != null) ? slc : '0';
            base.scaled_loss_provider_amount = (slp !== '' && slp != null) ? slp : '0';
        }
        return base;
    }

    function bfsPayloadSaveAndCancel() {
        const o = bfsPayload();
        o.booking_cancellation_reason_id = $('#bfs-cancel-reason').val() || '';
        return o;
    }

    function bfsPayloadSaveAndComplete() {
        const o = bfsPayload();
        var r = ($('#bfs-remarks').val() || '').trim();
        o.settlement_remarks = r !== '' ? r : null;
        return o;
    }

    function bfsSym(x) {
        if (typeof with_currency_symbol === 'function') {
            return with_currency_symbol(parseFloat(x) || 0);
        }
        return String(parseFloat(x) || 0);
    }

    function bfsRound2(x) {
        return Math.round((parseFloat(x) || 0) * 100) / 100;
    }

    function bfsScaledPaymentGap(pv) {
        if (!pv || bfsSelectedOutcome() !== OUTCOMES.SCALED) {
            return 0;
        }
        var declared = bfsRound2(parseFloat($('#bfs-scaled-customer-paid').val()) || 0);
        var grand = bfsRound2(parseFloat(bfsBookingGrandTotal) || 0);
        declared = bfsRound2(Math.max(0, Math.min(declared, grand)));
        var actual = bfsRound2(parseFloat(pv.collected_from_customer != null ? pv.collected_from_customer : 0) || 0);
        return bfsRound2(Math.max(0, declared - actual));
    }

    function bfsResetEmbeddedPaymentCapHidden() {
        $('#bfs-cap-decided-charges').val('0');
        $('#bfs-cap-scaled-loss').val('0');
        $('#bfs-cap-scaled-customer-paid').val('');
        $('#bfs-cap-visit-charges').val('0');
        $('#bfs-cap-closing').val('');
        $('#bfs-cap-settlement-outcome').val('');
    }

    function bfsScaledLossTotalFromInputs() {
        var grand = bfsRound2(parseFloat(bfsBookingGrandTotal) || 0);
        var paid = bfsRound2(parseFloat($('#bfs-scaled-customer-paid').val()) || 0);
        paid = bfsRound2(Math.max(0, Math.min(paid, grand)));
        return bfsRound2(Math.max(0, grand - paid));
    }

    function bfsApplyScaledLossHalfHalf() {
        if (bfsSelectedOutcome() !== OUTCOMES.SCALED) {
            return;
        }
        var loss = bfsScaledLossTotalFromInputs();
        bfsScaledLossSync = true;
        if (loss <= 0) {
            $('#bfs-scaled-loss-company').val('0');
            $('#bfs-scaled-loss-provider').val('0');
        } else {
            var a = bfsRound2(loss / 2);
            var b = bfsRound2(loss - a);
            $('#bfs-scaled-loss-company').val(a);
            $('#bfs-scaled-loss-provider').val(b);
        }
        bfsScaledLossSync = false;
    }

    function bfsReconcileScaledLossIfMismatch() {
        if (bfsSelectedOutcome() !== OUTCOMES.SCALED) {
            return;
        }
        var loss = bfsScaledLossTotalFromInputs();
        var y = parseFloat($('#bfs-scaled-loss-company').val());
        var z = parseFloat($('#bfs-scaled-loss-provider').val());
        if (isNaN(y)) y = 0;
        if (isNaN(z)) z = 0;
        if (bfsRound2(Math.abs(bfsRound2(y + z) - loss)) > 0.01) {
            bfsApplyScaledLossHalfHalf();
        }
    }

    function bfsRenderPreviewKv(p) {
        const $wrap = $('#bfs-preview-kv');
        if (!p) {
            $wrap.html('<div class="text-muted py-2" id="bfs-preview-placeholder">' + @json(translate('Bfs_preview_empty')) + '</div>');
            return;
        }

        if (bfsPreviewUsesDecidedVisitCharges(p)) {
            const total = p.booking_total != null ? p.booking_total : p.grand_total;
            const collected = p.collected_from_customer != null ? p.collected_from_customer : p.total_paid;
            const due = p.amount_to_collect_from_customer != null ? p.amount_to_collect_from_customer : 0;
            const tProv = p.total_provider_earning_applied != null
                ? p.total_provider_earning_applied
                : bfsRound2((parseFloat(p.provider_amount_from_visit) || 0) + (parseFloat(p.provider_amount_from_closing) || 0));
            const tCo = p.total_company_earning_applied != null
                ? p.total_company_earning_applied
                : bfsRound2((parseFloat(p.company_amount_from_visit) || 0) + (parseFloat(p.company_amount_from_closing) || 0));
            const rows = [
                [L.vcVisitingCharges, bfsSym(p.visit_charges_paid != null ? p.visit_charges_paid : 0)],
                [L.vcClosingAmount, bfsSym(p.closing_amount_paid != null ? p.closing_amount_paid : 0)],
                [L.vcTotalProvider, bfsSym(tProv)],
                [L.vcCompanyEarnings, bfsSym(tCo)],
                [L.vcPaidByCustomer, bfsSym(collected)],
                [L.vcStillDue, bfsSym(due)],
                [L.vcRecvCompany, bfsSym(p.amount_received_by_company)],
                [L.vcRecvProvider, bfsSym(p.amount_received_by_provider)],
                [L.vcBookingFinal, bfsSym(total)],
            ];
            let htmlVc = '';
            rows.forEach(function (pair) {
                htmlVc += '<div class="bfs-kv-row"><span class="bfs-kv-key">' + pair[0] + '</span><span class="bfs-kv-val">' + pair[1] + '</span></div>';
            });
            $wrap.html(htmlVc);
            return;
        }

        if (p.scaled_loss_mode === true || p.scaled_loss_mode === 1) {
            const rowsSc = [
                [L.scaledTotalBooking, bfsSym(p.scaled_total_booking_amount)],
                [L.scaledPaidByCustomer, bfsSym(p.scaled_customer_paid_amount)],
                [L.scaledLossAmount, bfsSym(p.scaled_loss_amount)],
                [L.scaledLossCompany, bfsSym(p.scaled_loss_company_share)],
                [L.scaledLossProvider, bfsSym(p.scaled_loss_provider_share)],
                [L.receivedCompany, bfsSym(p.amount_received_by_company)],
                [L.receivedProvider, bfsSym(p.amount_received_by_provider)],
                [L.companyShare, bfsSym(p.company_share)],
                [L.providerShare, bfsSym(p.provider_share)],
                [L.amountToCollect, bfsSym(p.amount_to_collect_from_customer)],
                [L.refundCustomer, bfsSym(p.refund_to_customer != null ? p.refund_to_customer : p.suggested_customer_refund)],
            ];
            let htmlSc = '';
            rowsSc.forEach(function (pair) {
                htmlSc += '<div class="bfs-kv-row"><span class="bfs-kv-key">' + pair[0] + '</span><span class="bfs-kv-val">' + pair[1] + '</span></div>';
            });
            $wrap.html(htmlSc);
            return;
        }

        const total = p.booking_total != null ? p.booking_total : p.grand_total;
        const visit = p.visit_extra_fee != null ? p.visit_extra_fee : p.visit_fee;
        const collected = p.collected_from_customer != null ? p.collected_from_customer : p.total_paid;
        const rows = [];
        rows.push([L.bookingTotal, bfsSym(total)]);
        rows.push([L.visitFee, bfsSym(visit)]);
        rows.push([L.collectedFromCustomer, bfsSym(collected)]);
        rows.push([L.receivedCompany, bfsSym(p.amount_received_by_company)]);
        rows.push([L.receivedProvider, bfsSym(p.amount_received_by_provider)]);
        rows.push([L.companyShare, bfsSym(p.company_share)]);
        rows.push([L.providerShare, bfsSym(p.provider_share)]);
        rows.push([L.amountToCollect, bfsSym(p.amount_to_collect_from_customer)]);
        rows.push([L.refundCustomer, bfsSym(p.refund_to_customer != null ? p.refund_to_customer : p.suggested_customer_refund)]);

        let html = '';
        rows.forEach(function (pair) {
            html += '<div class="bfs-kv-row"><span class="bfs-kv-key">' + pair[0] + '</span><span class="bfs-kv-val">' + pair[1] + '</span></div>';
        });
        $wrap.html(html);
    }

    function bfsSyncEmbeddedPaymentForm(due, mode) {
        var $form = $('#bfs-add-payment-form');
        if (!$form.length) {
            return;
        }
        due = bfsRound2(Math.max(0, due));
        $form.attr('data-due-amount', due);
        $form.data('due-amount', due);
        var $amt = $('#bfs-embed-pay-amount');
        if (due > 0) {
            $amt.attr('max', due);
        } else {
            $amt.removeAttr('max');
        }
        var sym = typeof with_currency_symbol === 'function' ? with_currency_symbol(due) : String(due);
        $('#bfs-embed-due-val').text(sym);
        var $bfsSp = $('#bfs-embed-split-provider');
        var $bfsSc = $('#bfs-embed-split-company');
        if ($bfsSp.length && $bfsSc.length && due > 0.009) {
            $bfsSp.val(due.toFixed(2));
            $bfsSc.val('0');
        }
        if (mode === 'scaled') {
            $('#bfs-cap-decided-charges').val('0');
            $('#bfs-cap-scaled-loss').val('1');
            $('#bfs-cap-scaled-customer-paid').val($('#bfs-scaled-customer-paid').val() || '0');
            $('#bfs-cap-settlement-outcome').val(OUTCOMES.SCALED);
            $('#bfs-cap-visit-charges').val('0');
            $('#bfs-cap-closing').val('');
            $('#bfs-collect-payment-title').text(L.collectPaymentTitleScaled);
            $('#bfs-collect-payment-hint').text(L.collectPaymentHintScaled);
        } else {
            $('#bfs-cap-decided-charges').val('1');
            $('#bfs-cap-scaled-loss').val('0');
            $('#bfs-cap-scaled-customer-paid').val('');
            $('#bfs-cap-visit-charges').val($('#bfs-visit-charges-paid').val() || '0');
            $('#bfs-cap-closing').val($('#bfs-closing-amount').val() || '');
            $('#bfs-cap-settlement-outcome').val(bfsSelectedOutcome());
            $('#bfs-collect-payment-title').text(L.collectPaymentTitleDefault);
            $('#bfs-collect-payment-hint').text(L.collectPaymentHintDefault);
        }
        if (typeof toggleAddPaymentTransactionField === 'function') {
            toggleAddPaymentTransactionField($form);
        }
    }

    function bfsUpdatePaymentEmbedVisibility(pv) {
        if (!bfsAllowCollectPayment) {
            return;
        }
        var $wrap = $('#bfs-payment-embed-wrap');
        if (!$wrap.length) {
            return;
        }
        if (!pv) {
            $wrap.addClass('d-none');
            bfsResetEmbeddedPaymentCapHidden();
            $('#bfs-collect-payment-title').text(L.collectPaymentTitleDefault);
            $('#bfs-collect-payment-hint').text(L.collectPaymentHintDefault);
            return;
        }
        var mode = null;
        var due = 0;
        if (bfsDecidedChargesScenarioSelected()) {
            due = bfsEffectiveAmountDueFromCustomer(pv);
            if (due < 0.01) {
                due = 0;
            }
            if (due >= 0.01) {
                mode = 'decided';
            }
        } else if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
            due = bfsScaledPaymentGap(pv);
            if (due >= 0.01) {
                mode = 'scaled';
            }
        }
        if (mode) {
            $wrap.removeClass('d-none');
            bfsSyncEmbeddedPaymentForm(due, mode);
        } else {
            $wrap.addClass('d-none');
            bfsResetEmbeddedPaymentCapHidden();
            $('#bfs-collect-payment-title').text(L.collectPaymentTitleDefault);
            $('#bfs-collect-payment-hint').text(L.collectPaymentHintDefault);
        }
    }

    function bfsUpdateScaledCompleteButton(pv) {
        if (bfsSelectedOutcome() !== OUTCOMES.SCALED) {
            return;
        }
        var $btn = $('#bfs-save-complete-btn');
        if (!$btn.length || $btn.hasClass('d-none')) {
            return;
        }
        if (!pv) {
            $btn.prop('disabled', true).attr('title', L.previewError);
            return;
        }
        var gap = bfsScaledPaymentGap(pv);
        if (gap >= 0.01) {
            $btn.prop('disabled', true).attr('title', L.saveCompleteScaledBlockedGap);
            return;
        }
        $btn.prop('disabled', false).removeAttr('title');
    }

    function bfsUpdateDecidedChargesActions(pv) {
        if (!bfsDecidedChargesScenarioSelected() || !pv) {
            return;
        }
        bfsLastDecidedPreview = pv;
        var due = bfsEffectiveAmountDueFromCustomer(pv);
        if (due < 0.01) {
            due = 0;
        }
        var blockedDue = due >= 0.01;
        var sel = bfsSelectedOutcome();
        if (sel === OUTCOMES.VISIT_CANCEL) {
            var hasReason = !!$('#bfs-cancel-reason').val();
            var $bc = $('#bfs-save-cancel-btn');
            $bc.prop('disabled', blockedDue);
            if (blockedDue) {
                $bc.attr('title', L.saveCancelBlockedDue);
            } else if (!hasReason) {
                $bc.attr('title', L.cancellationReasonRequired);
            } else {
                $bc.removeAttr('title');
            }
        }
        if (sel === OUTCOMES.VISIT_SPLIT) {
            var $bco = $('#bfs-save-complete-btn');
            $bco.prop('disabled', blockedDue);
            if (blockedDue) {
                $bco.attr('title', L.saveCompleteBlockedDue);
            } else {
                $bco.removeAttr('title');
            }
        }
        bfsUpdatePaymentEmbedVisibility(pv);
    }

    function bfsRunPreview() {
        if (!bfsSelectedOutcome()) {
            $('#bfs-preview-loading').addClass('d-none');
            $('#bfs-preview-kv').html('<div class="text-muted py-2" id="bfs-preview-placeholder">' + @json(translate('Bfs_preview_select_scenario_first')) + '</div>');
            bfsLastDecidedPreview = null;
            bfsUpdatePaymentEmbedVisibility(null);
            return;
        }
        const seq = ++bfsPreviewSeq;
        $('#bfs-preview-loading').removeClass('d-none');
        $.post(bfsPreviewUrl, bfsPayload())
            .done(function (res) {
                if (seq !== bfsPreviewSeq) return;
                if (res && res.preview) {
                    bfsRenderPreviewKv(res.preview);
                    var pv = res.preview;
                    if (bfsPreviewUsesDecidedVisitCharges(pv)) {
                        var visitBasis = parseFloat($('#bfs-visit-charges-paid').val()) || 0;
                        if (!bfsVisitSharesUserEdited) {
                            if (visitBasis <= 0) {
                                $('#bfs-visit-company-amt').val('');
                                $('#bfs-visit-provider-amt').val('');
                            } else {
                                bfsVisitPairSync = true;
                                if (pv.company_amount_from_visit != null) {
                                    $('#bfs-visit-company-amt').val(pv.company_amount_from_visit);
                                }
                                if (pv.provider_amount_from_visit != null) {
                                    $('#bfs-visit-provider-amt').val(pv.provider_amount_from_visit);
                                }
                                bfsVisitPairSync = false;
                            }
                        }
                        var clAmt = parseFloat($('#bfs-closing-amount').val()) || 0;
                        if (!bfsClosingSharesUserEdited) {
                            if (clAmt <= 0) {
                                $('#bfs-closing-company').val('');
                                $('#bfs-closing-provider').val('');
                            } else {
                                bfsClosingPairSync = true;
                                if (pv.company_amount_from_closing != null) {
                                    $('#bfs-closing-company').val(pv.company_amount_from_closing);
                                }
                                if (pv.provider_amount_from_closing != null) {
                                    $('#bfs-closing-provider').val(pv.provider_amount_from_closing);
                                }
                                bfsClosingPairSync = false;
                            }
                        }
                    }
                    if (bfsDecidedChargesScenarioSelected()) {
                        bfsUpdateDecidedChargesActions(pv);
                    } else {
                        bfsUpdatePaymentEmbedVisibility(pv);
                        if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
                            bfsUpdateScaledCompleteButton(pv);
                        }
                    }
                }
            })
            .fail(function (xhr) {
                if (seq !== bfsPreviewSeq) return;
                var errMsg = L.previewError;
                if (xhr && xhr.responseJSON) {
                    // Our API uses response_formatter() which always includes `errors` (often empty) and `message`.
                    // Prefer a concrete field error if present; otherwise fall back to the top-level message.
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        var ev = xhr.responseJSON.errors;
                        if (Array.isArray(ev)) {
                            if (ev.length && ev[0] && ev[0].message) {
                                errMsg = ev[0].message;
                            }
                        } else if (typeof ev === 'object') {
                            var fv = Object.values(ev || {})[0];
                            if (Array.isArray(fv) && fv[0]) {
                                errMsg = fv[0];
                            }
                        }
                    }
                    if ((errMsg === L.previewError || !errMsg) && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                } else if (xhr && xhr.responseText) {
                    // Fallback: show something actionable even if server returned non-JSON.
                    var t = String(xhr.responseText || '');
                    if (t && t.length < 300) {
                        errMsg = t;
                    }
                }
                $('#bfs-preview-kv').html('<div class="text-danger small py-2">' + errMsg + '</div>');
                bfsLastDecidedPreview = null;
                if (bfsDecidedChargesScenarioSelected()) {
                    $('#bfs-save-cancel-btn').prop('disabled', true).attr('title', L.previewError);
                    $('#bfs-save-complete-btn').prop('disabled', true).attr('title', L.previewError);
                }
                bfsUpdatePaymentEmbedVisibility(null);
                if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
                    $('#bfs-save-complete-btn').prop('disabled', true).attr('title', L.previewError);
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(errMsg);
                }
            })
            .always(function () {
                if (seq === bfsPreviewSeq) {
                    $('#bfs-preview-loading').addClass('d-none');
                }
            });
    }

    function bfsSchedulePreview() {
        clearTimeout(bfsPreviewTimer);
        bfsPreviewTimer = setTimeout(bfsRunPreview, 320);
    }

    function bfsInitPopovers() {
        var modal = document.getElementById('bookingFinancialSettlementModal');
        document.querySelectorAll('#bookingFinancialSettlementModal .bfs-info-btn').forEach(function (el) {
            var inst = bootstrap.Popover.getInstance(el);
            if (inst) inst.dispose();
            var k = el.getAttribute('data-bfs-outcome');
            var html = bfsPopovers[k] || '';
            new bootstrap.Popover(el, {
                title: bfsPopoverTitle,
                content: html,
                html: true,
                sanitize: false,
                placement: 'left',
                trigger: 'click',
                customClass: 'bfs-popover-wide',
                container: '#bookingFinancialSettlementModal'
            });
        });

        if (modal && !modal._bfsPopoverExclusiveBound) {
            modal._bfsPopoverExclusiveBound = true;
            modal.addEventListener('show.bs.popover', function (e) {
                var t = e.target && e.target.closest ? e.target.closest('.bfs-info-btn') : null;
                if (!t) {
                    return;
                }
                modal.querySelectorAll('.bfs-info-btn').forEach(function (btn) {
                    if (btn !== t) {
                        var p = bootstrap.Popover.getInstance(btn);
                        if (p) {
                            p.hide();
                        }
                    }
                });
            });
        }
    }

    function bfsDisposePopovers() {
        document.querySelectorAll('#bookingFinancialSettlementModal .bfs-info-btn').forEach(function (el) {
            var inst = bootstrap.Popover.getInstance(el);
            if (inst) inst.dispose();
        });
    }

    $(document).on('change', '.bfs-outcome-radio', function () {
        bfsToggleFields();
        if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
            clearTimeout(bfsScaledPaidSplitTimer);
            bfsApplyScaledLossHalfHalf();
        }
        bfsSchedulePreview();
    });

    $(document).on('input change', '.bfs-preview-trigger', function () {
        bfsSchedulePreview();
    });

    $(document).on('input change', '#bfs-visit-charges-paid', function () {
        bfsVisitSharesUserEdited = false;
    });

    $(document).on('input', '#bfs-visit-company-amt', function () {
        if (bfsVisitPairSync) {
            return;
        }
        bfsVisitSharesUserEdited = true;
        var V = parseFloat($('#bfs-visit-charges-paid').val()) || 0;
        var co = parseFloat($(this).val());
        if (isNaN(co)) {
            bfsSchedulePreview();
            return;
        }
        co = bfsRound2(Math.max(0, Math.min(V, co)));
        $(this).val(co);
        bfsVisitPairSync = true;
        $('#bfs-visit-provider-amt').val(bfsRound2(Math.max(0, V - co)));
        bfsVisitPairSync = false;
        bfsSchedulePreview();
    });

    $(document).on('input', '#bfs-visit-provider-amt', function () {
        if (bfsVisitPairSync) {
            return;
        }
        bfsVisitSharesUserEdited = true;
        var V = parseFloat($('#bfs-visit-charges-paid').val()) || 0;
        var pr = parseFloat($(this).val());
        if (isNaN(pr)) {
            bfsSchedulePreview();
            return;
        }
        pr = bfsRound2(Math.max(0, Math.min(V, pr)));
        $(this).val(pr);
        bfsVisitPairSync = true;
        $('#bfs-visit-company-amt').val(bfsRound2(Math.max(0, V - pr)));
        bfsVisitPairSync = false;
        bfsSchedulePreview();
    });

    $(document).on('change', '#bfs-cancel-reason', function () {
        if (bfsLastDecidedPreview) {
            bfsUpdateDecidedChargesActions(bfsLastDecidedPreview);
        }
    });

    $(document).on('input change', '#bfs-closing-amount', function () {
        bfsClosingSharesUserEdited = false;
    });

    $(document).on('input', '#bfs-closing-company', function () {
        if (bfsClosingPairSync) {
            return;
        }
        bfsClosingSharesUserEdited = true;
        var cl = parseFloat($('#bfs-closing-amount').val()) || 0;
        var co = parseFloat($(this).val());
        if (isNaN(co)) {
            bfsSchedulePreview();
            return;
        }
        co = bfsRound2(Math.max(0, Math.min(cl, co)));
        $(this).val(co);
        bfsClosingPairSync = true;
        $('#bfs-closing-provider').val(bfsRound2(Math.max(0, cl - co)));
        bfsClosingPairSync = false;
        bfsSchedulePreview();
    });

    $(document).on('input', '#bfs-closing-provider', function () {
        if (bfsClosingPairSync) {
            return;
        }
        bfsClosingSharesUserEdited = true;
        var cl = parseFloat($('#bfs-closing-amount').val()) || 0;
        var pr = parseFloat($(this).val());
        if (isNaN(pr)) {
            bfsSchedulePreview();
            return;
        }
        pr = bfsRound2(Math.max(0, Math.min(cl, pr)));
        $(this).val(pr);
        bfsClosingPairSync = true;
        $('#bfs-closing-company').val(bfsRound2(Math.max(0, cl - pr)));
        bfsClosingPairSync = false;
        bfsSchedulePreview();
    });

    $(document).on('change', '#bfs-scaled-customer-paid', function () {
        clearTimeout(bfsScaledPaidSplitTimer);
        if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
            bfsApplyScaledLossHalfHalf();
            bfsSchedulePreview();
        }
    });

    $(document).on('input', '#bfs-scaled-customer-paid', function () {
        clearTimeout(bfsScaledPaidSplitTimer);
        bfsScaledPaidSplitTimer = setTimeout(function () {
            if (bfsSelectedOutcome() !== OUTCOMES.SCALED) {
                return;
            }
            bfsApplyScaledLossHalfHalf();
            bfsSchedulePreview();
        }, 320);
    });

    $(document).on('input', '#bfs-scaled-loss-company', function () {
        if (bfsScaledLossSync || bfsSelectedOutcome() !== OUTCOMES.SCALED) {
            return;
        }
        var loss = bfsScaledLossTotalFromInputs();
        var co = parseFloat($(this).val());
        if (isNaN(co)) {
            bfsSchedulePreview();
            return;
        }
        co = bfsRound2(Math.max(0, Math.min(loss, co)));
        bfsScaledLossSync = true;
        $(this).val(co);
        $('#bfs-scaled-loss-provider').val(bfsRound2(Math.max(0, loss - co)));
        bfsScaledLossSync = false;
        bfsSchedulePreview();
    });

    $(document).on('input', '#bfs-scaled-loss-provider', function () {
        if (bfsScaledLossSync || bfsSelectedOutcome() !== OUTCOMES.SCALED) {
            return;
        }
        var loss = bfsScaledLossTotalFromInputs();
        var pr = parseFloat($(this).val());
        if (isNaN(pr)) {
            bfsSchedulePreview();
            return;
        }
        pr = bfsRound2(Math.max(0, Math.min(loss, pr)));
        bfsScaledLossSync = true;
        $(this).val(pr);
        $('#bfs-scaled-loss-company').val(bfsRound2(Math.max(0, loss - pr)));
        bfsScaledLossSync = false;
        bfsSchedulePreview();
    });

    $('#bookingFinancialSettlementModal').on('shown.bs.modal', function () {
        window.bfsModalSessionPartialPaymentIds = [];
        bfsClosingSharesUserEdited = !!bfsHasSavedClosingShares;
        bfsVisitSharesUserEdited = !!bfsHasSavedVisitAmounts;
        bfsLastDecidedPreview = null;
        clearTimeout(bfsScaledPaidSplitTimer);
        bfsInitPopovers();
        bfsToggleFields();
        if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
            bfsReconcileScaledLossIfMismatch();
        }
        var $payWrap = $('#bfs-payment-embed-wrap');
        if ($payWrap.length) {
            $payWrap.addClass('d-none');
        }
        bfsResetEmbeddedPaymentCapHidden();
        $('#bfs-collect-payment-title').text(L.collectPaymentTitleDefault);
        $('#bfs-collect-payment-hint').text(L.collectPaymentHintDefault);
        bfsRunPreview();
        if (typeof toggleAddPaymentTransactionField === 'function') {
            var $bf = $('#bfs-add-payment-form');
            if ($bf.length) {
                toggleAddPaymentTransactionField($bf);
            }
        }
    });
    $('#bookingFinancialSettlementModal').on('hide.bs.modal', function (e) {
        if (bfsModalSkipCloseGuard) {
            return;
        }
        var ids = window.bfsModalSessionPartialPaymentIds || [];
        if (!ids.length) {
            return;
        }
        e.preventDefault();
        var confirmEl = document.getElementById('bfsUnsavedPaymentConfirmModal');
        if (!confirmEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }
        var confirmInst = bootstrap.Modal.getOrCreateInstance(confirmEl, { backdrop: 'static', keyboard: true });
        confirmInst.show();
    });

    function bfsRevertEmbeddedPaymentsThenCloseSettlementModal() {
        var ids = window.bfsModalSessionPartialPaymentIds || [];
        if (!ids.length) {
            return;
        }
        var $proceed = $('#bfs-unsaved-payment-confirm-proceed');
        $proceed.prop('disabled', true);
        $.ajax({
            url: bfsRevertModalPartialsUrl,
            method: 'POST',
            data: {
                _token: bfsCsrf,
                partial_payment_ids: ids,
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        }).done(function () {
            window.bfsModalSessionPartialPaymentIds = [];
            var confirmEl = document.getElementById('bfsUnsavedPaymentConfirmModal');
            if (confirmEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var cInst = bootstrap.Modal.getInstance(confirmEl);
                if (cInst) {
                    cInst.hide();
                }
            }
            bfsModalSkipCloseGuard = true;
            var el = document.getElementById('bookingFinancialSettlementModal');
            if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var inst = bootstrap.Modal.getInstance(el);
                if (inst) {
                    inst.hide();
                }
            }
            if (typeof window.bfsRunPreviewAfterEmbeddedPayment === 'function') {
                window.bfsRunPreviewAfterEmbeddedPayment();
            }
        }).fail(function (xhr) {
            var msg = L.closeModalRevertPaymentFailed;
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                var ev = xhr.responseJSON.errors;
                var fv = Object.values(ev)[0];
                if (Array.isArray(fv) && fv[0]) {
                    msg = fv[0];
                }
            }
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            }
        }).always(function () {
            $proceed.prop('disabled', false);
        });
    }

    $(document).on('click', '#bfs-unsaved-payment-confirm-proceed', function () {
        bfsRevertEmbeddedPaymentsThenCloseSettlementModal();
    });

    $('#bfsUnsavedPaymentConfirmModal').on('hidden.bs.modal', function () {
        $('#bfs-unsaved-payment-confirm-proceed').prop('disabled', false);
    });

    $('#bookingFinancialSettlementModal').on('hidden.bs.modal', function () {
        bfsModalSkipCloseGuard = false;
        bfsDisposePopovers();
        clearTimeout(bfsPreviewTimer);
        clearTimeout(bfsScaledPaidSplitTimer);
    });

    bfsToggleFields();
    if (bfsSelectedOutcome() === OUTCOMES.SCALED) {
        bfsReconcileScaledLossIfMismatch();
    }

    function bfsPostSave($btn, url, payload) {
        $btn.prop('disabled', true);
        $.post(url, payload)
            .done(function (res) {
                window.bfsModalSessionPartialPaymentIds = [];
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || @json(translate('Saved')));
                }
                var $st = $('#booking_status');
                if ($st.length && res.snapshot && res.snapshot.allow_complete_without_full_payment) {
                    $st.data('can-complete', '1');
                    $st.find('option[value="completed"]').prop('disabled', false);
                }
                setTimeout(function () { location.reload(); }, 600);
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : @json(translate('Something went wrong'));
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var e = xhr.responseJSON.errors;
                    var first = Object.values(e)[0];
                    if (Array.isArray(first) && first[0]) msg = first[0];
                }
                if (typeof toastr !== 'undefined') toastr.error(msg);
                $btn.prop('disabled', false);
            });
    }

    $('#bfs-save-btn').on('click', function () {
        if (!bfsSelectedOutcome()) {
            if (typeof toastr !== 'undefined') {
                toastr.error(@json(translate('Bfs_select_scenario_required')));
            }
            return;
        }
        bfsPostSave($(this), bfsSaveUrl, bfsPayload());
    });

    $('#bfs-save-cancel-btn').on('click', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        if (!$('#bfs-cancel-reason').val()) {
            if (typeof toastr !== 'undefined') {
                toastr.error(L.cancellationReasonRequired);
            }
            return;
        }
        bfsPostSave($(this), bfsSaveCancelUrl, bfsPayloadSaveAndCancel());
    });

    $('#bfs-save-complete-btn').on('click', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        bfsPostSave($(this), bfsSaveCompleteUrl, bfsPayloadSaveAndComplete());
    });

    window.bfsRunPreviewAfterEmbeddedPayment = function () {
        bfsRunPreview();
    };
    window.bfsSelectedOutcome = bfsSelectedOutcome;
})();
</script>
@endpush
@endif
@endcan
