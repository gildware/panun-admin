{{-- Single-booking special financial settlement summary; optional bfsIncludeSettlementModal when #bookingFinancialSettlementModal exists on page --}}
@php
    /** @var \Modules\BookingModule\Entities\Booking $booking */
    use Modules\BookingModule\Services\BookingFinancialSettlementService;

    $bfsService = app(BookingFinancialSettlementService::class);
    $isSingleBooking = (int) ($booking->is_repeated ?? 0) === 0;
@endphp
@if ($isSingleBooking && $bfsService->usesNonStandardSettlement($booking))
    @php
        $bfsIncludeSettlementModal = (bool) ($bfsIncludeSettlementModal ?? false);
        $booking->loadMissing('booking_partial_payments');
        $outcome = trim((string) ($booking->settlement_outcome ?? ''));
        $snap = is_array($booking->settlement_snapshot ?? null) ? $booking->settlement_snapshot : [];
        if ($snap === []) {
            $snap = $bfsService->buildPreview($booking);
        }
        $bfsScaledLive = $outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
            ? $bfsService->buildPreview($booking)
            : null;
        $bfsScaledWriteoff = $bfsScaledLive !== null
            ? round(max(0.0, (float) ($bfsScaledLive['scaled_loss_writeoff_amount'] ?? 0)), 2)
            : 0.0;
        $status = (string) ($booking->booking_status ?? '');
        $bookingNotEditable = in_array($status, ['completed', 'canceled', 'cancelled', 'refunded'], true);
        $canConfigureSettlement = ($status === 'ongoing' || booking_on_hold_is_after_visit_from_ongoing($booking)) && ! $bookingNotEditable;
        $settlementLockedMessage = $bookingNotEditable
            ? translate('Financial_settlement_cannot_be_changed_for_this_status')
            : translate('Bfs_financial_settlement_only_while_ongoing');
        $bfsDecided = BookingFinancialSettlementService::outcomeUsesDecidedVisitCharges($outcome);
        $bfsScaledOutcome = $outcome === BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS;
        $bfsCfg = is_array($booking->settlement_config ?? null) ? $booking->settlement_config : [];
        $bfsScaledWriteoffCo = $bfsScaledOutcome
            ? round(max(0.0, (float) ($bfsCfg['scaled_loss_writeoff_company_amount'] ?? 0)), 2)
            : 0.0;
        $bfsScaledWriteoffPr = $bfsScaledOutcome
            ? round(max(0.0, (float) ($bfsCfg['scaled_loss_writeoff_provider_amount'] ?? 0)), 2)
            : 0.0;
        $bfsCustom = $outcome === BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION;
        $bfsAddPaymentModalOnPage = (bool) ($bfsAddPaymentModalOnPage ?? false);
        $bfsInvoiceRecoveryRemaining = 0.0;
        $bfsShowSettleAdditionalCustomerPayment = false;
        if ($bfsScaledOutcome) {
            $bfsInvoiceRecoveryRemaining = round((float) get_booking_admin_add_payment_remaining_amount($booking), 2);
            $bfsShowSettleAdditionalCustomerPayment = ! in_array($status, ['canceled', 'cancelled', 'refunded'], true)
                && $bfsInvoiceRecoveryRemaining > 0.009;
        }
        $bfsDetailsTabUrl = route('admin.booking.details', [$booking->id, 'web_page' => 'details']) . '#bfs-settle-additional-customer-payment';
        $scenarioLabel = match ($outcome) {
            BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL => translate('Bfs_label_cancel_keep_visit'),
            BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT => translate('Bfs_label_complete_visit_only'),
            BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS => $booking->isScaledSettlementLossRecovered()
                ? translate('Bfs_label_loss_recovered_booking')
                : translate('Bfs_label_scaled_partial_or_bad_debt'),
            BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION => translate('Bfs_label_custom_commission'),
            default => str_replace('_', ' ', $outcome),
        };
    @endphp
    <div class="card mb-4 border border-primary border-opacity-25 shadow-sm" id="special-financial-settlement-banner">
        <div class="card-body py-3 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1 d-flex align-items-center gap-2">
                        <span class="material-icons title-color fz-20" aria-hidden="true">account_balance</span>
                        {{ translate('Special_financial_settlement') }}
                    </h3>
                    <p class="text-muted small mb-0">{{ translate('Financial_settlement_card_hint') }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
                    @if (! empty($booking->after_visit_cancel))
                        <span class="badge bg-info text-dark">{{ translate('Bfs_list_badge_cancelled_after_visit') }}</span>
                    @endif
                    @if ($bfsScaledOutcome && $bfsScaledWriteoff > 0.009)
                        <span class="badge bg-danger text-nowrap">{{ translate('Settled') }}</span>
                    @endif
                    @can('booking_can_manage_status')
                        @if ($bfsScaledOutcome && $bfsScaledWriteoff > 0.009)
                            <button type="button" class="btn btn-outline-danger btn-sm text-nowrap"
                                data-bs-toggle="modal" data-bs-target="#bfsWriteoffRevertConfirmModal-{{ $booking->id }}">
                                {{ translate('Revert_write_off') }}
                            </button>
                        @endif
                        @if ($canConfigureSettlement && $bfsIncludeSettlementModal)
                            <button type="button" class="btn btn--primary btn-sm text-nowrap" data-bs-toggle="modal"
                                data-bs-target="#bookingFinancialSettlementModal">{{ translate('Configure_special_scenarios') }}</button>
                        @elseif ($canConfigureSettlement)
                            <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}#special-financial-settlement-banner"
                                class="btn btn--primary btn-sm text-nowrap">{{ translate('Configure_special_scenarios') }}</a>
                        @else
                            <span class="small text-muted" style="max-width: 18rem;">{{ $settlementLockedMessage }}</span>
                        @endif
                    @endcan
                </div>
            </div>
            <dl class="row small mb-0 gx-2">
                <dt class="col-sm-4 col-md-3">{{ translate('Scenario') }}</dt>
                <dd class="col-sm-8 col-md-9 fw-semibold">{{ $scenarioLabel }}</dd>

                @if ($bfsCustom)
                    <dt class="col-sm-4 col-md-3">{{ translate('Company_commission_amount') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['custom_admin_commission'] ?? 0)) }}</dd>
                @endif

                @if ($bfsDecided)
                    <dt class="col-sm-4 col-md-3">{{ translate('Bfs_preview_visiting_charges') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['visit_charges_paid'] ?? 0)) }}</dd>
                    <dt class="col-sm-4 col-md-3">{{ translate('Bfs_preview_closing_amount') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['closing_amount_paid'] ?? 0)) }}</dd>
                    @if (((float) ($snap['visit_charges_paid'] ?? 0)) > 0)
                        <dt class="col-sm-4 col-md-3">{{ translate('Bfs_company_commission_visit_line') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['company_amount_from_visit'] ?? 0)) }}</dd>
                        <dt class="col-sm-4 col-md-3">{{ translate('Bfs_provider_share_visit_line') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['provider_amount_from_visit'] ?? 0)) }}</dd>
                    @endif
                    @if (((float) ($snap['closing_amount_paid'] ?? 0)) > 0)
                        <dt class="col-sm-4 col-md-3">{{ translate('Bfs_company_commission_closing_line') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['company_amount_from_closing'] ?? 0)) }}</dd>
                        <dt class="col-sm-4 col-md-3">{{ translate('Bfs_provider_share_closing_line') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($snap['provider_amount_from_closing'] ?? 0)) }}</dd>
                    @endif
                @endif

                @if ($bfsScaledOutcome && $bfsScaledLive !== null)
                    @php
                        $sx = (float) ($bfsScaledLive['scaled_customer_paid_amount'] ?? 0);
                        $sloss = (float) ($bfsScaledLive['scaled_loss_amount'] ?? 0);
                        $sy = (float) ($bfsScaledLive['scaled_loss_company_share'] ?? 0);
                        $sz = (float) ($bfsScaledLive['scaled_loss_provider_share'] ?? 0);
                    @endphp
                    <dt class="col-sm-4 col-md-3">{{ translate('Bfs_preview_scaled_total_booking') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) get_booking_total_amount($booking)) }}</dd>
                    <dt class="col-sm-4 col-md-3">{{ translate('Bfs_scaled_amount_paid_by_customer') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol($sx) }}</dd>
                    <dt class="col-sm-4 col-md-3">{{ translate('Bfs_preview_scaled_loss_amount') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol($sloss) }}</dd>
                    <dt class="col-sm-4 col-md-3">{{ translate('Loss_to_company') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol($sy) }}</dd>
                    <dt class="col-sm-4 col-md-3">{{ translate('Loss_to_provider') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol($sz) }}</dd>
                    @if ($bfsScaledWriteoff > 0.009)
                        <dt class="col-sm-4 col-md-3">{{ translate('Write_off_amount') }}</dt>
                        <dd class="col-sm-8 col-md-9 fw-semibold">{{ with_currency_symbol($bfsScaledWriteoff) }}</dd>
                        <dt class="col-sm-4 col-md-3">{{ translate('Write_off_company_amount') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol($bfsScaledWriteoffCo) }}</dd>
                        <dt class="col-sm-4 col-md-3">{{ translate('Write_off_provider_amount') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol($bfsScaledWriteoffPr) }}</dd>
                    @endif
                    @if($bookingNotEditable)
                        <dt class="col-sm-4 col-md-3">{{ translate('Bfs_gross_company_commission_full_booking') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($bfsScaledLive['company_commission'] ?? 0)) }}</dd>
                        <dt class="col-sm-4 col-md-3">{{ translate('Bfs_gross_provider_share_full_booking') }}</dt>
                        <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) ($bfsScaledLive['scaled_gross_provider_share'] ?? 0)) }}</dd>
                    @endif
                @endif

                @if($bookingNotEditable)
                    <dt class="col-sm-4 col-md-3">{{ $bfsScaledOutcome ? translate('Net_company_share_after_loss') : translate('Company_commission') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) data_get($bfsScaledLive, 'company_commission_after_promos', (float) data_get($snap, 'company_commission_after_promos', 0))) }}</dd>
                    <dt class="col-sm-4 col-md-3">{{ $bfsScaledOutcome ? translate('Net_provider_share_after_loss') : translate('Provider_earning') }}</dt>
                    <dd class="col-sm-8 col-md-9">{{ with_currency_symbol((float) data_get($bfsScaledLive, 'provider_earning', (float) data_get($snap, 'provider_earning', 0))) }}</dd>
                @endif

                @if (! empty($booking->settlement_remarks))
                    <dt class="col-sm-4 col-md-3">{{ translate('Notes') }}</dt>
                    <dd class="col-sm-8 col-md-9 text-break">{{ $booking->settlement_remarks }}</dd>
                @endif
            </dl>

            @if ($bfsShowSettleAdditionalCustomerPayment)
                @can('booking_can_manage_status')
                    <div class="border-top pt-3 mt-3" id="bfs-settle-additional-customer-payment">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h4 class="h6 mb-1 text-uppercase text-muted fz-12">{{ translate('Bfs_settle_additional_customer_payment') }}</h4>
                                <p class="small text-muted mb-0">{{ translate('Bfs_settle_additional_customer_payment_hint') }}</p>
                            </div>
                            @if ($bfsAddPaymentModalOnPage)
                                <button type="button" class="btn btn-outline--primary btn-sm text-nowrap flex-shrink-0"
                                    data-bs-toggle="modal" data-bs-target="#addPaymentModal-{{ $booking->id }}">
                                    {{ translate('Bfs_settle_additional_customer_payment_cta') }}
                                </button>
                            @else
                                <a href="{{ $bfsDetailsTabUrl }}" class="btn btn-outline--primary btn-sm text-nowrap flex-shrink-0">
                                    {{ translate('Bfs_settle_additional_customer_payment_open_details') }}
                                </a>
                            @endif
                        </div>
                        <dl class="row small mb-0 gx-2">
                            <dt class="col-sm-4 col-md-3">{{ translate('Due_Balance') }} <span class="text-muted fw-normal">({{ translate('Invoice') }})</span></dt>
                            <dd class="col-sm-8 col-md-9 fw-semibold d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>{{ with_currency_symbol($bfsInvoiceRecoveryRemaining) }}</span>
                                @if ($bfsInvoiceRecoveryRemaining > 0.009)
                                    <button type="button"
                                        class="btn btn-outline--primary btn-sm text-nowrap"
                                        data-bs-toggle="modal"
                                        data-bs-target="#lossWriteoffModal-{{ $booking->id }}">
                                        {{ translate('Settle_remaining_amount_as_discount') }}
                                    </button>
                                @endif
                            </dd>
                        </dl>
                        @if (!empty($bfsScaledLive['scaled_loss_writeoff_amount']) && (float) $bfsScaledLive['scaled_loss_writeoff_amount'] > 0.009)
                            <dl class="row small mb-0 gx-2 mt-1">
                                <dt class="col-sm-4 col-md-3">{{ translate('Settlement_amount') }}</dt>
                                <dd class="col-sm-8 col-md-9 fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <span>{{ with_currency_symbol((float) $bfsScaledLive['scaled_loss_writeoff_amount']) }}</span>
                                    @can('booking_can_manage_status')
                                        <form method="post" action="{{ route('admin.booking.loss_writeoff.revert', $booking->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm">{{ translate('Revert_write_off') }}</button>
                                        </form>
                                    @endcan
                                </dd>
                            </dl>
                        @endif
                    </div>
                @endcan
            @endif
        </div>
    </div>

    @if ($bfsScaledOutcome && $bfsScaledWriteoff > 0.009)
        <div class="modal fade" id="bfsWriteoffRevertConfirmModal-{{ $booking->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Revert_write_off') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0 text-muted">{{ translate('Revert_write_off_confirm') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <form method="post" action="{{ route('admin.booking.loss_writeoff.revert', $booking->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">{{ translate('Revert_write_off') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
