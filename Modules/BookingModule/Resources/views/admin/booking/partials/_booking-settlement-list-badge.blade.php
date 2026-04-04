{{-- Non-standard financial settlement tag for list views; expects $booking (Booking). --}}
@php
    $bfsListOutcome = trim((string) ($booking->settlement_outcome ?? ''));
@endphp
@if($bfsListOutcome !== '')
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
            $bfsListOutcome === \Modules\BookingModule\Services\BookingFinancialSettlementService::OUTCOME_SCALED_TO_PAYMENTS => [
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
    <span class="badge {{ $bfsBadgeClass }} text-wrap text-start mt-1 d-inline-block lh-sm"
          style="max-width: min(100%, 14rem); white-space: normal;"
          title="{{ $bfsBadgeFull }}">{{ $bfsBadgeShort }}</span>
@endif
