{{-- Unified admin booking status tags: same markup for booking details header and list columns. Expects $booking (\Modules\BookingModule\Entities\Booking). --}}
@php
    use Modules\BookingModule\Services\BookingFinancialSettlementService as Bfs;
    $bfsTagGapClass = !empty($bookingListTagStacked) ? '' : 'mt-1';
    $bfsTagVariant = $bookingStatusTagsVariant ?? 'header';
    $bfsTagFz = $bfsTagVariant === 'compact' ? ' fz-12' : '';
    $bfsRefundTag = booking_admin_classify_refund_ui_tag($booking);
    $bfsRefundTotals = booking_admin_refund_display_totals($booking);
    $bfsHasDisputed = booking_admin_has_disputed_reopen_snapshot($booking);
    $bfsHasCompensated = booking_admin_has_compensation($booking);
    $bfsIsCaseClosed = !empty($booking->reopen_resolved_at);
    $bfsShowCancelAfterVisit = booking_admin_should_show_cancel_after_visit_tag($booking);
    $bfsShowCompleteNoService = booking_admin_should_show_complete_no_service_tag($booking);
    $bfsListOutcome = trim((string) ($booking->settlement_outcome ?? ''));
    $bfsListStatusNorm = strtolower((string) ($booking->booking_status ?? ''));
    $bfsListIsClosed = in_array($bfsListStatusNorm, ['completed', 'canceled', 'cancelled', 'refunded'], true);
    $bfsCfg = is_array($booking->settlement_config ?? null) ? $booking->settlement_config : [];
    $bfsHasWriteoff = $bfsListOutcome === Bfs::OUTCOME_SCALED_TO_PAYMENTS
        && isset($bfsCfg['scaled_loss_writeoff_amount'])
        && is_numeric($bfsCfg['scaled_loss_writeoff_amount'])
        && (float) $bfsCfg['scaled_loss_writeoff_amount'] > 0.009;
    $bfsSkipOutcomeDuplicate = in_array($bfsListOutcome, [
        Bfs::OUTCOME_VISIT_RETAINED_CANCEL,
        Bfs::OUTCOME_VISIT_FEE_SPLIT,
    ], true);
    $bfsShowSettlementOutcomeBadge = $bfsListOutcome !== ''
        && ! ($bfsListOutcome === Bfs::OUTCOME_VISIT_FEE_SPLIT && ! $bfsListIsClosed)
        && ! $bfsSkipOutcomeDuplicate;
@endphp
@if($bfsRefundTag === 'pending')
    <span class="badge bg-warning text-dark text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Remaining_refundable') }}: {{ with_currency_symbol((float) ($bfsRefundTotals['refundable_remaining'] ?? 0)) }}">{{ translate('Pending_refund') }}</span>
@elseif($bfsRefundTag === 'full')
    <span class="badge bg-success text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Refunded') }}: {{ with_currency_symbol((float) ($bfsRefundTotals['refunded_total'] ?? 0)) }}">{{ translate('Refunded') }}</span>
@elseif($bfsRefundTag === 'partial')
    <span class="badge bg-info text-dark text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Booking_tag_refund_partial') }}: {{ with_currency_symbol((float) ($bfsRefundTotals['refunded_total'] ?? 0)) }} / {{ with_currency_symbol((float) ($bfsRefundTotals['max_eligible'] ?? 0)) }}">{{ translate('Booking_tag_refund_partial') }}</span>
@endif
@if($bfsHasDisputed)
    <span class="badge bg-danger text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Disputed_bookings_tab_hint') }}">{{ translate('Booking_tag_disputed') }}</span>
@endif
@if($bfsHasCompensated)
    <span class="badge bg-primary text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Booking_tag_compensated') }}">{{ translate('Booking_tag_compensated') }}</span>
@endif
@if($bfsShowCancelAfterVisit)
    <span class="badge bg-danger text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Bfs_label_cancel_keep_visit') }}">{{ translate('Booking_tag_cancel_after_visit') }}</span>
@elseif($bfsShowCompleteNoService)
    <span class="badge bg-success text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Bfs_label_complete_visit_only') }}">{{ translate('Booking_tag_complete_no_service') }}</span>
@endif
@if($bfsIsCaseClosed)
    <span class="badge bg-success text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ translate('Reopen_case_resolved') }}">{{ translate('Booking_tag_case_closed') }}</span>
@endif
@if(empty($booking->is_repeated))
    @if($booking->isOpenReopenTicket())
        <span class="badge bg-warning text-dark text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}">{{ translate('Reopened') }}</span>
    @elseif($booking->isReopenedTagged() && (empty($booking->reopen_disputed_snapshot) || !is_array($booking->reopen_disputed_snapshot)))
        <span class="badge bg-success text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}">{{ translate('Resolved') }}</span>
    @endif
@endif
@if($bfsShowSettlementOutcomeBadge)
    @php
        [$bfsBadgeClass, $bfsBadgeShort, $bfsBadgeFull] = match (true) {
            $bfsListOutcome === Bfs::OUTCOME_CUSTOM_COMMISSION => [
                'bg-primary',
                translate('Bfs_list_badge_custom_commission'),
                translate('Bfs_label_custom_commission'),
            ],
            $bfsListOutcome === Bfs::OUTCOME_SCALED_TO_PAYMENTS => $booking->isScaledSettlementLossRecovered()
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
            $bfsListOutcome === Bfs::OUTCOME_STANDARD => [
                'bg-light text-dark border',
                translate('Bfs_label_standard'),
                translate('Bfs_label_standard'),
            ],
            default => ['bg-dark', str_replace('_', ' ', $bfsListOutcome), str_replace('_', ' ', $bfsListOutcome)],
        };
    @endphp
    <span class="badge {{ $bfsBadgeClass }} text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
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
    <span class="badge bg-danger text-nowrap text-start {{ $bfsTagGapClass }} d-inline-block lh-sm{{ $bfsTagFz }}"
          title="{{ $bfsWriteTitle }}">{{ translate('Settled') }}</span>
@endif
