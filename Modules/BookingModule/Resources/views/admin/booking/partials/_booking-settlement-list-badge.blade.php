{{-- Booking scenario tags for list views; expects $booking (Booking). Optional bookingListTagStacked: omit mt-1 (parent uses gap). --}}
@php
    $bfsListTagGapClass = !empty($bookingListTagStacked) ? '' : 'mt-1';
    $bfsListOutcome = trim((string) ($booking->settlement_outcome ?? ''));
    $bfsListStatusNorm = strtolower((string) ($booking->booking_status ?? ''));
    $bfsListIsClosed = in_array($bfsListStatusNorm, ['completed', 'canceled', 'cancelled', 'refunded'], true);
    $bfsCfg = is_array($booking->settlement_config ?? null) ? $booking->settlement_config : [];
    $bfsHasWriteoff = $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS
        && isset($bfsCfg['scaled_loss_writeoff_amount'])
        && is_numeric($bfsCfg['scaled_loss_writeoff_amount'])
        && (float) $bfsCfg['scaled_loss_writeoff_amount'] > 0.009;
    $hasDisputedSnapshot = !empty($booking->reopen_disputed_snapshot) && is_array($booking->reopen_disputed_snapshot);
    $isCaseClosed = !empty($booking->reopen_resolved_at);
    $isRefunded = (string) ($booking->booking_status ?? '') === 'refunded';
@endphp
@if($hasDisputedSnapshot)
    <span class="badge bg-danger text-nowrap text-start {{ $bfsListTagGapClass }} d-inline-block lh-sm"
          title="{{ translate('Disputed_bookings_tab_hint') }}">{{ translate('Booking_tag_disputed') }}</span>
@endif
@if($isRefunded)
    <span class="badge bg-info text-dark text-nowrap text-start {{ $bfsListTagGapClass }} d-inline-block lh-sm"
          title="{{ translate('Refunded') }}">{{ translate('Refunded') }}</span>
@endif
@if($isCaseClosed)
    <span class="badge bg-success text-nowrap text-start {{ $bfsListTagGapClass }} d-inline-block lh-sm"
          title="{{ translate('Reopen_case_resolved') }}">{{ translate('Booking_tag_case_closed') }}</span>
@endif
@if($bfsListOutcome !== '' && ! ($bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT && ! $bfsListIsClosed))
    @php
        [$bfsBadgeClass, $bfsBadgeShort, $bfsBadgeFull] = match (true) {
            $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_RETAINED_CANCEL => [
                'bg-danger',
                translate('Bfs_list_badge_cancelled_after_visit'),
                translate('Bfs_label_cancel_keep_visit'),
            ],
            $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_VISIT_FEE_SPLIT => [
                'bg-success',
                translate('Bfs_list_badge_little_or_no_service'),
                translate('Bfs_label_complete_visit_only'),
            ],
            $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_CUSTOM_COMMISSION => [
                'bg-primary',
                translate('Bfs_list_badge_custom_commission'),
                translate('Bfs_label_custom_commission'),
            ],
            $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS => $booking->isScaledSettlementLossRecovered()
                ? [
                    'bg-success',
                    translate('Bfs_list_badge_loss_recovered'),
                    translate('Bfs_label_loss_recovered_booking'),
                ]
                : [
                    'bg-secondary',
                    translate('Bfs_list_badge_loss_making'),
                    translate('Bfs_label_scaled_partial_or_bad_debt'),
                ],
            $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_STANDARD => [
                'bg-light text-dark border',
                translate('Bfs_label_standard'),
                translate('Bfs_label_standard'),
            ],
            default => ['bg-dark', str_replace('_', ' ', $bfsListOutcome), str_replace('_', ' ', $bfsListOutcome)],
        };
    @endphp
    <span class="badge {{ $bfsBadgeClass }} text-nowrap text-start {{ $bfsListTagGapClass }} d-inline-block lh-sm"
          title="{{ $bfsBadgeFull }}">{{ $bfsBadgeShort }}</span>
@endif
@if($bfsHasWriteoff)
    @php
        $bfsWriteCo = isset($bfsCfg['scaled_loss_writeoff_company_amount']) && is_numeric($bfsCfg['scaled_loss_writeoff_company_amount'])
            ? (float) $bfsCfg['scaled_loss_writeoff_company_amount'] : 0.0;
        $bfsWritePr = isset($bfsCfg['scaled_loss_writeoff_provider_amount']) && is_numeric($bfsCfg['scaled_loss_writeoff_provider_amount'])
            ? (float) $bfsCfg['scaled_loss_writeoff_provider_amount'] : 0.0;
        $bfsWriteTitle = translate('Write_off_amount') . ': ' . with_currency_symbol((float) $bfsCfg['scaled_loss_writeoff_amount'])
            . ' — ' . translate('Write_off_company_amount') . ': ' . with_currency_symbol($bfsWriteCo)
            . ', ' . translate('Write_off_provider_amount') . ': ' . with_currency_symbol($bfsWritePr);
    @endphp
    <span class="badge bg-danger text-nowrap text-start {{ $bfsListTagGapClass }} d-inline-block lh-sm"
          title="{{ $bfsWriteTitle }}">{{ translate('Settled') }}</span>
@endif
