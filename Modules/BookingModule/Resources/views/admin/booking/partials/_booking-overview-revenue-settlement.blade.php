<div class="party-card party-card--detail-panel party-card--revenue w-100">
    <div class="party-card__head">
        <span class="party-card__head-text">{{ translate('Revenue_&_Settlement') }}</span>
    </div>
    <div class="party-card__body party-card__body--stats">
        @php
            $__rev = $revenueSettlement ?? [];
            $__revDisputedOverride = false;
            if (!empty($__headerHasDisputedSnapshot) && $__headerHasDisputedSnapshot) {
                $__revDisputedOverride = true;
                $__rev = array_merge($__rev, [
                    'company_share' => $__dsFinalAdmin,
                    'provider_share' => $__dsFinalProvider,
                    'amount_received_by_company' => $__dsCompanyAfter,
                    'amount_received_by_provider' => $__dsProviderAfter,
                    'total_paid' => $__dsCustomerPaid,
                    'pay_to_provider' => $__dsCompanyPaysProvider,
                    'provider_owes_company' => $__dsProviderPaysCompany,
                ]);
            }
        @endphp
        @if(booking_should_show_admin_revenue_settlement_breakdown($booking))
            <dl class="detail-kv">
                <div class="detail-kv__row">
                    <dt>
                        @if ($__bfsScaledLive !== null && (float) ($__rev['company_share'] ?? 0) < -0.009)
                            {{ translate('Company_loss') }} ({{ translate('Net') }})
                        @else
                            {{ translate('Company_share') }} ({{ translate('Commission') }})
                        @endif
                    </dt>
                    <dd @class(['detail-kv__value--danger' => (float) ($__rev['company_share'] ?? 0) < -0.009])>
                        {{ with_currency_symbol($__rev['company_share'] ?? 0) }}
                    </dd>
                </div>
                <div class="detail-kv__row">
                    <dt>
                        @if ($__bfsScaledLive !== null && (float) ($__rev['provider_share'] ?? 0) < -0.009)
                            {{ translate('Provider_loss') }} ({{ translate('Net') }})
                        @else
                            {{ translate('Provider_share') }}
                        @endif
                    </dt>
                    <dd @class(['detail-kv__value--danger' => (float) ($__rev['provider_share'] ?? 0) < -0.009])>
                        {{ with_currency_symbol($__rev['provider_share'] ?? 0) }}
                    </dd>
                </div>
                <div class="detail-kv__row detail-kv__row--muted">
                    <dt>{{ translate('Received_by_company') }}</dt>
                    <dd>{{ with_currency_symbol($__rev['amount_received_by_company'] ?? 0) }}</dd>
                </div>
                <div class="detail-kv__row detail-kv__row--muted">
                    <dt>{{ translate('Received_by_provider') }}</dt>
                    <dd>{{ with_currency_symbol($__rev['amount_received_by_provider'] ?? 0) }}</dd>
                </div>
                @if($__bfsScaledLive !== null && !empty($__bfsScaledLive['scaled_loss_writeoff_amount']) && (float) $__bfsScaledLive['scaled_loss_writeoff_amount'] > 0.009)
                    <div class="detail-kv__row detail-kv__row--muted">
                        <dt>{{ translate('Write_off_amount') }}</dt>
                        <dd>{{ with_currency_symbol((float) $__bfsScaledLive['scaled_loss_writeoff_amount']) }}</dd>
                    </div>
                @endif
            </dl>
            @if(!empty($__rev['net_revenue_zeroed_after_refund']))
                <div class="detail-kv__settlement-stack">
                    <div class="detail-kv__settlement-pill alert alert-secondary mb-0 py-2 px-2 fz-12">
                        {{ translate('Net_settlement_zero_after_full_refund_hint') }}
                    </div>
                    @if((float) ($__rev['pay_to_provider'] ?? 0) > 0.009)
                        <div class="detail-kv__settlement-pill alert alert-info mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center mt-2">
                            <span>{{ translate('Pay_to_provider') }} <span class="text-muted">({{ translate('Reopen_disputed_settlement_snapshot') }})</span>:</span>
                            <strong>{{ with_currency_symbol($__rev['pay_to_provider'] ?? 0) }}</strong>
                        </div>
                    @endif
                    @if((float) ($__rev['provider_owes_company'] ?? 0) > 0.009)
                        <div class="detail-kv__settlement-pill alert alert-warning mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center mt-2">
                            <span>{{ translate('Provider_owes_you') }} <span class="text-muted">({{ translate('Reopen_disputed_settlement_snapshot') }})</span>:</span>
                            <strong>{{ with_currency_symbol($__rev['provider_owes_company'] ?? 0) }}</strong>
                        </div>
                    @endif
                </div>
            @elseif(($__rev['pay_to_provider'] ?? 0) > 0)
                <div class="detail-kv__settlement-stack">
                    <div class="detail-kv__settlement-pill alert alert-info mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center">
                        <span>{{ translate('Pay_to_provider') }}{{ $__revDisputedOverride ? ' ' . translate('Reopen_disputed_settlement_snapshot') : '' }}:</span>
                        <strong>{{ with_currency_symbol($__rev['pay_to_provider'] ?? 0) }}</strong>
                    </div>
                </div>
            @elseif(($__rev['provider_owes_company'] ?? 0) > 0)
                <div class="detail-kv__settlement-stack">
                    <div class="detail-kv__settlement-pill alert alert-warning mb-0 py-2 px-2 fz-12 d-flex justify-content-between align-items-center">
                        <span>{{ translate('Provider_owes_you') }}{{ $__revDisputedOverride ? ' ' . translate('Reopen_disputed_settlement_snapshot') : '' }}:</span>
                        <strong>{{ with_currency_symbol($__rev['provider_owes_company'] ?? 0) }}</strong>
                    </div>
                </div>
            @else
                @php $__revSettled = (float) ($__rev['total_paid'] ?? 0) >= (float) $bookingTotalForPayment; @endphp
                <div class="detail-kv__settlement-stack">
                    <div class="detail-kv__settlement-pill alert alert-secondary mb-0 py-2 px-2 fz-12">
                        {{ $__revSettled ? translate('Settled') : translate('Unpaid_or_partially_paid') }}
                    </div>
                </div>
            @endif
        @else
            <div class="detail-kv__settlement-stack">
                <div class="detail-kv__settlement-pill alert alert-light border mb-0 fz-12 text-muted">
                    {{ translate('No_revenue_cancelled_before_service') }}
                </div>
            </div>
        @endif
    </div>
</div>
