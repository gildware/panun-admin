@extends('adminmodule::layouts.master')

@section('title', translate('provider_details') . ' - ' . translate('Payment'))

@push('css_or_js')
    <style>
        .pk-payment-widget-card { position: relative; }
        .pk-payment-widget-card.statistics-card__style2 {
            padding: 0.75rem 1rem 0.875rem;
        }
        .pk-payment-widget-card.statistics-card__style2 h3 {
            margin-block-end: 0.125rem;
            line-height: 1.3;
        }
        .pk-payment-widget-card.statistics-card__style2 h2 {
            font-size: 1.25rem;
            margin-block-end: 0.25rem;
        }
        .statistics-card.pk-payment-widget-card.statistics-card__style2 h2.text-danger {
            color: var(--bs-danger, #dc3545);
        }
        .pk-payment-widget-card.statistics-card__style2 .btn--lg {
            margin-top: 0.375rem !important;
        }
        .pk-payment-widget-info-btn {
            z-index: 2;
            line-height: 1;
            opacity: 0.55;
            text-decoration: none !important;
        }
        .pk-payment-widget-info-btn:hover,
        .pk-payment-widget-info-btn:focus {
            opacity: 1;
        }
        .popover.pk-payment-widget-popover {
            max-width: min(22rem, 92vw);
        }
        .popover.pk-payment-widget-popover .popover-body {
            text-align: start;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'special_bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=special_bookings">{{ translate('Special_Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body p-30">
                    @php
                        $ppLedger = $providerPaymentLedgerContext ?? provider_payment_ledger_context([
                            'collect_in_total' => (float) ($ledgerManualTotals['collect_in_total'] ?? 0),
                            'payout_out_total' => (float) ($ledgerManualTotals['payout_out_total'] ?? 0),
                            'booking_settlement_net_before_ledger' => (float) ($bookingSettlementNetBeforeLedger ?? 0),
                            'booking_settlement_net_after_ledger' => (float) ($bookingSettlementNet ?? 0),
                            'provider_account_payable' => (float) ($provider->owner->account->account_payable ?? 0),
                            'provider_account_receivable' => (float) ($provider->owner->account->account_receivable ?? 0),
                        ]);
                        $providerPayable = (float) ($provider->owner->account->account_payable ?? 0);
                        $providerReceivable = (float) ($provider->owner->account->account_receivable ?? 0);
                        $ledgerNetPayable = $providerReceivable - $providerPayable;
                        $bookingSettlementNet = (float) ($bookingSettlementNet ?? 0);
                        $netPayableAmount = $bookingSettlementNet;
                        $companyPaysProvider = $netPayableAmount > 0.009;
                        $providerPaysCompany = $netPayableAmount < -0.009;
                        $customerRefundDueTotal = (float) ($customerRefundDueTotal ?? 0);
                        $payoutCapWhenCompanyOwes = $companyPaysProvider
                            ? max($providerReceivable, max(0.0, $bookingSettlementNet))
                            : max(0.0, $providerReceivable);
                        $addPaymentFormMax = $companyPaysProvider ? max(0.0, $bookingSettlementNet) : 0.0;
                        $collectFormMax = $providerPaysCompany ? min($providerPayable, max(0.0, -$bookingSettlementNet)) : 0.0;
                        $collectModalMaxLedger = max(0.0, $providerPayable);
                        $addPaymentModalMaxLedger = $payoutCapWhenCompanyOwes;
                        $showProviderPaymentReminderBtn = max(0.0, -$netPayableAmount) > 0.009 || $collectModalMaxLedger > 0.009;
                    @endphp

                    @can('provider_update')
                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
                            @if($showProviderPaymentReminderBtn)
                                <button type="button" class="btn btn-outline--primary text-capitalize" data-bs-toggle="modal" data-bs-target="#providerPaymentReminderWaModal">
                                    {{ translate('Send_payment_reminder_to_provider') }}
                                </button>
                            @endif
                            <button type="button" class="btn btn--primary text-capitalize" data-bs-toggle="modal" data-bs-target="#collectAmountFromProviderModal">
                                {{ translate('Collect_Amount_From_Provider') }}
                            </button>
                            <button type="button" class="btn btn--primary text-capitalize" data-bs-toggle="modal" data-bs-target="#addPaymentToProviderModal">
                                {{ translate('Add_Payment_to_Provider') }}
                            </button>
                        </div>
                    @endcan

                    {{-- Net balance + Revenue Summary (first row) --}}
                    <div class="row g-3 mb-30">
                        <div class="col-12 col-md-6 col-lg">
                            @php
                                $netBalanceIsZero = abs($netPayableAmount) <= 0.009;
                                $netPayableInfoWarn = \Illuminate\Support\Facades\Gate::check('provider_update')
                                    && (($companyPaysProvider && $addPaymentModalMaxLedger <= 0.009) || ($providerPaysCompany && $collectFormMax <= 0.009));
                            @endphp
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 {{ $netPayableInfoWarn ? 'text-warning' : 'text-muted' }}" data-pk-popover-src="pk-pop-body-net-payable" data-pk-popover-title="{{ translate('Net_Balance') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Net_Balance') }}</h3>
                                <h2 @class(['text-danger' => $companyPaysProvider && ! $netBalanceIsZero, 'text-success' => $providerPaysCompany && ! $netBalanceIsZero])>{{ with_currency_symbol(abs($netPayableAmount)) }}</h2>
                                @if($companyPaysProvider && ! $netBalanceIsZero)
                                    <p class="small mb-0 mt-1 text-danger">{{ translate('Company_has_to_pay_to_provider') }}</p>
                                @elseif($providerPaysCompany && ! $netBalanceIsZero)
                                    <p class="small mb-0 mt-1 text-success">{{ translate('Provider_has_to_pay_to_company') }}</p>
                                @endif
                                @can('provider_update')
                                    @if($companyPaysProvider && $addPaymentFormMax > 0.009)
                                        <button type="button" class="btn btn--primary text-capitalize w-100 btn--lg mw-75 mt-2" data-bs-toggle="modal" data-bs-target="#addPaymentToProviderModal">
                                            {{ translate('Add_Payment_to_Provider') }}
                                        </button>
                                    @elseif($providerPaysCompany && $collectFormMax > 0.009)
                                        <button type="button" class="btn btn--primary text-capitalize w-100 btn--lg mw-75 mt-2" data-bs-toggle="modal" data-bs-target="#collectAmountFromProviderModal">
                                            {{ translate('Collect_Amount') }}
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg">
                            <div class="statistics-card statistics-card__style2 h-100 border border-primary pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-total-revenue" data-pk-popover-title="{{ translate('Total_Revenue') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Total_Revenue') }}</h3>
                                <h2 class="text-primary">{{ with_currency_symbol($totalRevenue ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-provider-net" data-pk-popover-title="{{ translate('Provider_Earning') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Provider_Earning') }}</h3>
                                <h2>{{ with_currency_symbol($providerNetEarning ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-company-commission" data-pk-popover-title="{{ translate('Total_Company_Commission') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Total_Company_Commission') }}</h3>
                                <h2>{{ with_currency_symbol($totalCompanyCommission ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card border border-secondary">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-provider-loss-absorbed" data-pk-popover-title="{{ translate('Provider_loss_absorbed_total') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Provider_loss_absorbed_total') }}</h3>
                                <h2 class="text-danger">{{ with_currency_symbol($scaledLossProviderShareTotal ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card border border-secondary">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-company-loss-absorbed" data-pk-popover-title="{{ translate('Company_loss_absorbed_total') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Company_loss_absorbed_total') }}</h3>
                                <h2 class="text-danger">{{ with_currency_symbol($scaledLossCompanyShareTotal ?? 0) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Provider receipts: company ledger vs customer (booking reports) --}}
                    <div class="row g-3 mb-30">
                        <div class="col-12">
                            <h4 class="mb-2">{{ translate('Provider_payment_receipts_section') }}</h4>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card border border-info">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-received-from-company" data-pk-popover-title="{{ translate('Provider_payment_total_from_company') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Provider_payment_total_from_company') }}</h3>
                                <h2>{{ with_currency_symbol($providerReceivedFromCompanyTotal ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card border border-info">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-received-from-customer" data-pk-popover-title="{{ translate('Provider_payment_total_from_customer') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Provider_payment_total_from_customer') }}</h3>
                                <h2>{{ with_currency_symbol($providerReceivedFromCustomerTotal ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="statistics-card statistics-card__style2 h-100 pk-payment-widget-card border border-primary">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-received-total" data-pk-popover-title="{{ translate('Provider_payment_total_received') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Provider_payment_total_received') }}</h3>
                                <h2 class="text-primary">{{ with_currency_symbol($providerReceivedTotalAllSources ?? 0) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Pending Withdrawn, Already Withdrawn, Withdrawable Amount --}}
                    <div class="row g-3 mb-30">
                        <div class="col-12">
                            <h4 class="mb-2">{{ translate('Withdrawal_Summary') }}</h4>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="statistics-card statistics-card__style2 statistics-card__pending-withdraw h-100 pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-pending-withdrawn" data-pk-popover-title="{{ translate('Pending_Withdrawn') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Pending_Withdrawn') }}</h3>
                                <h2>{{ with_currency_symbol($provider->owner->account->balance_pending ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="statistics-card statistics-card__style2 statistics-card__already-withdraw h-100 pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-already-withdrawn" data-pk-popover-title="{{ translate('Already_Withdrawn') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Already_Withdrawn') }}</h3>
                                <h2>{{ with_currency_symbol($provider->owner->account->total_withdrawn ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="statistics-card statistics-card__style2 statistics-card__withdrawable-amount h-100 pk-payment-widget-card">
                                <button type="button" class="btn btn-link pk-payment-widget-info-btn position-absolute top-0 end-0 mt-1 me-1 text-muted" data-pk-popover-src="pk-pop-body-withdrawable" data-pk-popover-title="{{ translate('Withdrawable_Amount') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                                    <i class="material-icons" style="font-size:20px;">info_outline</i>
                                </button>
                                <h3 class="pe-4">{{ translate('Withdrawable_Amount') }}</h3>
                                <h2>{{ with_currency_symbol($provider->owner->account->account_receivable ?? 0) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden HTML sources for widget popovers (Bootstrap reads innerHTML once at init) --}}
                    <div id="pk-pop-body-net-payable" class="d-none" aria-hidden="true">
                        <div class="small text-body">
                            @if($companyPaysProvider)
                                <p class="mb-2"><strong>{{ translate('Company_has_to_pay_to_provider') }}</strong></p>
                            @elseif($providerPaysCompany)
                                <p class="mb-2"><strong>{{ translate('Provider_has_to_pay_to_company') }}</strong></p>
                            @else
                                <p class="mb-2">{{ translate('No_outstanding_balance') }}</p>
                            @endif
                            @if($customerRefundDueTotal > 0.009)
                                <p class="mb-2">{{ translate('Customer_refunds_due_total') }}: {{ with_currency_symbol($customerRefundDueTotal) }}</p>
                            @endif
                            <p class="mb-2">{{ translate('Net_Payable_booking_settlement_explanation') }}</p>
                            @php
                                $ledgerPayoutOut = (float) ($ppLedger['amount_paid_to_provider'] ?? 0);
                                $ledgerCollectIn = (float) ($ppLedger['amount_collected_from_provider'] ?? 0);
                            @endphp
                            @if($ledgerPayoutOut > 0.009 || $ledgerCollectIn > 0.009)
                                <p class="mb-2">
                                    <span class="d-block">{{ translate('Net_balance_provider_ledger_adjustment_hint') }}</span>
                                    @if($ledgerPayoutOut > 0.009)
                                        <span class="d-block">{{ translate('Net_balance_ledger_out_paid_to_provider') }}: {{ with_currency_symbol($ledgerPayoutOut) }}</span>
                                    @endif
                                    @if($ledgerCollectIn > 0.009)
                                        <span class="d-block">{{ translate('Net_balance_ledger_in_collected_from_provider') }}: {{ with_currency_symbol($ledgerCollectIn) }}</span>
                                    @endif
                                </p>
                            @endif
                            @if((float) ($ppLedger['balance_after_payment_collected'] ?? 0) > 0.009 || (float) ($ppLedger['balance_remaining_to_pay_to_provider'] ?? 0) > 0.009)
                                <p class="mb-2 small text-muted">
                                    @if((float) ($ppLedger['balance_after_payment_collected'] ?? 0) > 0.009)
                                        <span class="d-block">{{ translate('Balance_after_payment_collected_hint') }}: {{ with_currency_symbol($ppLedger['balance_after_payment_collected']) }}</span>
                                    @endif
                                    @if((float) ($ppLedger['balance_remaining_to_pay_to_provider'] ?? 0) > 0.009)
                                        <span class="d-block">{{ translate('Balance_remaining_to_pay_provider_hint') }}: {{ with_currency_symbol($ppLedger['balance_remaining_to_pay_to_provider']) }}</span>
                                    @endif
                                </p>
                            @endif
                            <p class="mb-2">{{ translate('Net_Payable_ledger_reference') }}: {{ with_currency_symbol(abs($ledgerNetPayable)) }}
                                @if($ledgerNetPayable > 0.009)({{ translate('Company_has_to_pay_to_provider') }})@elseif($ledgerNetPayable < -0.009)({{ translate('Provider_has_to_pay_to_company') }})@else({{ translate('No_outstanding_balance') }})@endif
                            </p>
                            @can('provider_update')
                                @if($companyPaysProvider && $addPaymentModalMaxLedger <= 0.009)
                                    <p class="mb-0 text-warning">{{ translate('Booking_settlement_no_receivable_for_payout') }}</p>
                                @elseif($providerPaysCompany && $collectFormMax <= 0.009)
                                    <p class="mb-0 text-warning">{{ translate('Booking_settlement_no_payable_for_collect') }}</p>
                                @endif
                            @endcan
                        </div>
                    </div>
                    <div id="pk-pop-body-total-revenue" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Grand_total_of_all_completed_bookings') }}</p>
                    </div>
                    <div id="pk-pop-body-provider-net" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Provider_net_earning_payment_tab_hint') }}</p>
                    </div>
                    <div id="pk-pop-body-company-commission" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Company_commission_of_completed_bookings') }}</p>
                    </div>
                    <div id="pk-pop-body-provider-loss-absorbed" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Provider_loss_absorbed_payment_tab_hint') }}</p>
                    </div>
                    <div id="pk-pop-body-company-loss-absorbed" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Company_loss_absorbed_payment_tab_hint') }}</p>
                    </div>
                    <div id="pk-pop-body-received-from-company" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Provider_payment_total_from_company_hint') }}</p>
                    </div>
                    <div id="pk-pop-body-received-from-customer" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Provider_payment_total_from_customer_hint') }}</p>
                    </div>
                    <div id="pk-pop-body-received-total" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Provider_payment_total_received_hint') }}</p>
                    </div>
                    <div id="pk-pop-body-pending-withdrawn" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Payment_widget_hint_pending_withdrawn') }}</p>
                    </div>
                    <div id="pk-pop-body-already-withdrawn" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Payment_widget_hint_already_withdrawn') }}</p>
                    </div>
                    <div id="pk-pop-body-withdrawable" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Payment_widget_hint_withdrawable_receivable') }}</p>
                    </div>
                    <div id="pk-pop-body-ledger-section" class="d-none" aria-hidden="true">
                        <p class="small text-body mb-0">{{ translate('Payment_widget_hint_ledger_section') }}</p>
                    </div>

                    @php
                        $paymentSubActive = $paymentSub ?? request('payment_sub', 'ledger');
                        $paymentTabBaseQuery = array_merge(
                            request()->except(['ledger_page', 'trx_page', 'booking_page', 'special_booking_page', 'disputed_page']),
                            ['web_page' => 'payment']
                        );
                    @endphp
                    <div class="border-bottom pb-2 mb-4 pk-payment-subtabs-wrap" data-pk-scroll-provider-id="{{ $provider->id }}">
                        <ul class="nav nav--tabs flex-wrap gap-2">
                            <li class="nav-item">
                                <a class="nav-link js-pk-payment-subtab {{ $paymentSubActive === 'ledger' ? 'active' : '' }}" href="{{ url()->current() }}?{{ http_build_query(array_merge($paymentTabBaseQuery, ['payment_sub' => 'ledger'])) }}">{{ translate('Provider_Ledger') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-pk-payment-subtab {{ $paymentSubActive === 'recorded' ? 'active' : '' }}" href="{{ url()->current() }}?{{ http_build_query(array_merge($paymentTabBaseQuery, ['payment_sub' => 'recorded'])) }}">{{ translate('Payment_transactions_all_parties') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-pk-payment-subtab {{ $paymentSubActive === 'earning' ? 'active' : '' }}" href="{{ url()->current() }}?{{ http_build_query(array_merge($paymentTabBaseQuery, ['payment_sub' => 'earning'])) }}">{{ translate('Booking_Earning_Report') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-pk-payment-subtab {{ $paymentSubActive === 'special_earning' ? 'active' : '' }}" href="{{ url()->current() }}?{{ http_build_query(array_merge($paymentTabBaseQuery, ['payment_sub' => 'special_earning'])) }}">{{ translate('Special_Booking_Earning_Report') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-pk-payment-subtab {{ $paymentSubActive === 'disputed' ? 'active' : '' }}" href="{{ url()->current() }}?{{ http_build_query(array_merge($paymentTabBaseQuery, ['payment_sub' => 'disputed'])) }}">{{ translate('Disputed_bookings') }}</a>
                            </li>
                        </ul>
                    </div>

                    @if($paymentSubActive === 'ledger')
                    {{-- 1. Provider Ledger: company sent to provider / company received from provider --}}
                    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-3">
                        <h4 class="mb-0">{{ translate('Provider_Ledger') }}</h4>
                        <button type="button" class="btn btn-link pk-payment-widget-info-btn text-muted p-1" data-pk-popover-src="pk-pop-body-ledger-section" data-pk-popover-title="{{ translate('Provider_Ledger') }}" aria-label="{{ translate('Payment_widget_info_aria') }}">
                            <i class="material-icons" style="font-size:20px;">info_outline</i>
                        </button>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Booking_ID') }}</th>
                                    <th>{{ translate('Type') }}</th>
                                    <th>{{ translate('Payment_ledger_column_payment_type') }}</th>
                                    <th>{{ translate('payment_method') }}</th>
                                    <th class="text-end">{{ translate('Amount') }}</th>
                                    <th>{{ translate('Transaction_ID') }}</th>
                                    <th>{{ translate('Reference') }}</th>
                                    <th>{{ translate('Entry_by') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($providerLedger as $entry)
                                    <tr>
                                        <td class="text-nowrap">{{ $entry->created_at ? $entry->created_at->format('d M Y H:i') : '—' }}</td>
                                        <td>
                                            @if($entry->booking_id && $entry->booking)
                                                <a href="{{ route('admin.booking.details', [$entry->booking_id]) }}" class="fw-semibold text-decoration-none" target="_blank" rel="noopener noreferrer">{{ $entry->booking->readable_id ?? $entry->booking_id }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                <span class="badge bg-success">{{ translate('In') }}</span>
                                            @else
                                                <span class="badge bg-danger">{{ translate('Out') }}</span>
                                            @endif
                                        </td>
                                        <td>{!! payment_counterparty_flow_badge_html($entry->counterpartyFlowKey()) !!}</td>
                                        <td class="text-nowrap">{{ $entry->formatPaymentMethodForDisplay() }}</td>
                                        <td class="text-end fw-semibold">{{ with_currency_symbol($entry->amount) }}</td>
                                        <td>{{ $entry->transaction_id ?: '—' }}</td>
                                        <td>
                                            {{ $entry->reference_note ?: '—' }}
                                            @if($entry->booking_repeat_id && $entry->repeat)
                                                <br><small class="text-muted">{{ translate('Repeat') }}: {{ $entry->repeat->readable_id ?? $entry->booking_repeat_id }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $entry->resolvedEntryByLabel() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">{{ translate('No_ledger_entries') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($providerLedger->hasPages())
                        <div class="d-flex justify-content-end mb-30">
                            {{ $providerLedger->links() }}
                        </div>
                    @endif
                    @endif

                    @if($paymentSubActive === 'recorded')
                    <h4 class="mb-2">{{ translate('Payment_transactions_all_parties') }}</h4>
                    <p class="text-muted small mb-3">{{ translate('Payment_transactions_booking_log_hint') }}</p>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Booking_ID') }}</th>
                                    <th>{{ translate('Payment_transactions_column_company_flow') }}</th>
                                    <th>{{ translate('Payment_ledger_column_payment_type') }}</th>
                                    <th>{{ translate('Channel') }}</th>
                                    <th>{{ translate('Transaction_ID') }}</th>
                                    <th class="text-end">{{ translate('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($providerPaymentEvents as $row)
                                    @php
                                        $cf = (string) ($row->company_flow ?? '');
                                        $cfLabel = match ($cf) {
                                            \Modules\TransactionModule\Entities\Transaction::FLOW_IN => translate('Company_money_flow_in'),
                                            \Modules\TransactionModule\Entities\Transaction::FLOW_OUT => translate('Company_money_flow_out'),
                                            \Modules\TransactionModule\Entities\Transaction::FLOW_NONE => translate('Company_money_flow_none'),
                                            default => '—',
                                        };
                                        $cfBadge = match ($cf) {
                                            \Modules\TransactionModule\Entities\Transaction::FLOW_IN => 'bg-success',
                                            \Modules\TransactionModule\Entities\Transaction::FLOW_OUT => 'bg-danger',
                                            \Modules\TransactionModule\Entities\Transaction::FLOW_NONE => 'bg-secondary',
                                            default => 'bg-light text-dark',
                                        };
                                        $__flowKey = (string) ($row->counterparty_flow ?? 'unknown');
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $row->date ? \Illuminate\Support\Carbon::parse($row->date)->format('d M Y H:i') : '—' }}</td>
                                        <td>
                                            @if(!empty($row->booking_id))
                                                <a href="{{ route('admin.booking.details', [$row->booking_id]) }}" class="fw-semibold text-decoration-none" target="_blank" rel="noopener noreferrer">{{ $row->booking_readable_id ?? $row->booking_id }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $cfBadge }}">{{ $cfLabel }}</span></td>
                                        <td>{!! payment_counterparty_flow_badge_html($__flowKey) !!}</td>
                                        <td class="text-nowrap">{{ $row->channel }}</td>
                                        <td>{{ $row->transaction_id ?: '—' }}</td>
                                        <td class="text-end fw-semibold">{{ with_currency_symbol((float) $row->amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($providerPaymentEvents->hasPages())
                        <div class="d-flex justify-content-end mb-30">
                            {{ $providerPaymentEvents->links() }}
                        </div>
                    @endif
                    @endif

                    @if($paymentSubActive === 'earning')
                    {{-- 2. Booking Earning Report (below, max 20 per page) --}}
                    <h4 class="mb-3">{{ translate('Booking_Earning_Report') }}</h4>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Booking_ID') }}</th>
                                    <th class="text-end">{{ translate('Total_Amount') }}</th>
                                    <th class="text-end">{{ translate('Service_Charges') }}</th>
                                    <th class="text-end">{{ translate('Extra_Service_Charges') }}</th>
                                    <th class="text-end">{{ translate('Parts_Charges') }}</th>
                                    <th class="text-end">{{ translate('Provider_Earning') }}</th>
                                    <th class="text-end">{{ translate('Admin_Commission') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_received_by_company') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_received_by_provider') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_provider_owes_company') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_company_owes_provider') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookingEarningReportPaginated as $row)
                                    <tr>
                                        <td>
                                            @if(!empty($row->booking_id))
                                                <a href="{{ route('admin.booking.details', [$row->booking_id]) }}" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none">{{ $row->readable_id }}</a>
                                            @else
                                                {{ $row->readable_id }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ with_currency_symbol($row->total_amount) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->service_charges) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->extra_service_charges ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->parts_charges) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->provider_earning) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->admin_commission) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->amount_received_by_company ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->amount_received_by_provider ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->provider_owes_company ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->company_owes_provider ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">{{ translate('No_completed_bookings') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($bookingEarningReportPaginated->hasPages())
                        <div class="d-flex justify-content-end">
                            {{ $bookingEarningReportPaginated->links() }}
                        </div>
                    @endif
                    @endif

                    @if($paymentSubActive === 'special_earning')
                    <h4 class="mb-3 mt-4">{{ translate('Special_Booking_Earning_Report') }}</h4>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Booking_ID') }}</th>
                                    <th class="text-end">{{ translate('Total_Amount') }}</th>
                                    <th class="text-end">{{ translate('Bfs_preview_visiting_charges') }}</th>
                                    <th class="text-end">{{ translate('Bfs_preview_closing_amount') }}</th>
                                    <th class="text-end">{{ translate('Provider_Earning') }}</th>
                                    <th class="text-end">{{ translate('Admin_Commission') }}</th>
                                    <th class="text-end">{{ translate('Company_loss_absorbed_line') }}</th>
                                    <th class="text-end">{{ translate('Provider_loss_absorbed_line') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_received_by_company') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_received_by_provider') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_provider_owes_company') }}</th>
                                    <th class="text-end">{{ translate('Earning_report_company_owes_provider') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($specialBookingEarningReportPaginated as $row)
                                    <tr>
                                        <td>
                                            @if(!empty($row->booking_id))
                                                <a href="{{ route('admin.booking.details', [$row->booking_id]) }}" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none">{{ $row->readable_id }}</a>
                                            @else
                                                {{ $row->readable_id }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ with_currency_symbol($row->total_amount ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->visiting_charges ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->closing_amount ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->provider_earning) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->admin_commission) }}</td>
                                        <td class="text-end">
                                            @if(!empty($row->scaled_loss_making_split))
                                                {{ with_currency_symbol($row->scaled_company_loss_line ?? 0) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(!empty($row->scaled_loss_making_split))
                                                {{ with_currency_symbol($row->scaled_provider_loss_line ?? 0) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">{{ with_currency_symbol($row->amount_received_by_company ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->amount_received_by_provider ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->provider_owes_company ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->company_owes_provider ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">{{ translate('No_special_settlement_bookings_in_report') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($specialBookingEarningReportPaginated->hasPages())
                        <div class="d-flex justify-content-end mb-30">
                            {{ $specialBookingEarningReportPaginated->links() }}
                        </div>
                    @endif
                    @endif

                    @if($paymentSubActive === 'disputed')
                    <h4 class="mb-2">{{ translate('Disputed_bookings') }}</h4>
                    <p class="text-muted small mb-2">{{ translate('Disputed_bookings_tab_hint') }}</p>
                    <p class="fw-semibold small mb-3">{{ translate('Reopen_disputed_settlement_snapshot') }}</p>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Booking_ID') }}</th>
                                    <th>{{ translate('status') }}</th>
                                    <th class="text-end">{{ translate('Total_Amount') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Refund_paid_from_company_pool') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Refund_paid_from_provider_pool') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Provider_owes_company_refund_above_pool') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Company_owes_provider_refund_above_pool') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Final_amount_retained_from_customer_after_refunds') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Final_admin_commission_net_basis') }}</th>
                                    <th class="text-end text-nowrap">{{ translate('Final_provider_earning_net_basis') }}</th>
                                    <th>{{ translate('Disputed_recorded_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($disputedBookingsPaginated as $disputedBooking)
                                    @php
                                        $disputedAt = $disputedBooking->reopen_resolved_at ?? $disputedBooking->updated_at;
                                        $snap = is_array($disputedBooking->reopen_disputed_snapshot ?? null) ? $disputedBooking->reopen_disputed_snapshot : [];
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.booking.details', [$disputedBooking->id]) }}" target="_blank" rel="noopener noreferrer" class="fw-semibold text-decoration-none">{{ $disputedBooking->readable_id ?? $disputedBooking->id }}</a>
                                        </td>
                                        <td><span class="text-capitalize">{{ str_replace('_', ' ', (string) $disputedBooking->booking_status) }}</span></td>
                                        <td class="text-end">{{ with_currency_symbol((float) ($disputedBooking->total_booking_amount ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['refund_company_amount'] ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['refund_provider_amount'] ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['provider_owes_company'] ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['company_owes_provider'] ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['retained_from_customer'] ?? $snap['final_net_to_customer'] ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['final_admin_commission'] ?? 0)) }}</td>
                                        <td class="text-end text-nowrap">{{ with_currency_symbol((float) ($snap['final_provider_earning'] ?? 0)) }}</td>
                                        <td class="text-nowrap">{{ $disputedAt ? $disputedAt->format('d M Y H:i') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">{{ translate('No_disputed_bookings_for_provider') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($disputedBookingsPaginated->hasPages())
                        <div class="d-flex justify-content-end mb-30">
                            {{ $disputedBookingsPaginated->links() }}
                        </div>
                    @endif
                    @endif

                    @can('provider_update')
                        <div class="modal fade" id="collectAmountFromProviderModal" tabindex="-1" aria-labelledby="collectAmountFromProviderModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('admin.provider.details.collect_amount', $provider->id) }}" method="post" id="collect-from-provider-form">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="collectAmountFromProviderModalLabel">{{ translate('Collect_Amount') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small mb-3">{{ translate('Record_amount_collected_from_provider._It_will_be_added_as_IN_in_ledger.') }}</p>
                                            <p class="small text-body-secondary mb-3">{{ translate('Collect_from_provider_advance_hint') }}</p>
                                            @if($collectModalMaxLedger > 0.009)
                                                <p class="small mb-2"><span class="text-muted">{{ translate('Provider_ledger_payable_current') }}:</span> <span class="fw-semibold">{{ with_currency_symbol($collectModalMaxLedger) }}</span></p>
                                            @else
                                                <p class="small text-warning mb-2">{{ translate('Collect_from_provider_zero_payable_advance_only') }}</p>
                                            @endif
                                            <div class="mb-3">
                                                <label for="collect_amount_amount" class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" name="amount" id="collect_amount_amount" class="form-control" required placeholder="0.00">
                                                @if($providerPaysCompany && $collectFormMax > 0.009)
                                                    <small class="d-block text-muted mt-1 fz-11">{{ translate('Booking_settlement_suggested_max_collect') }}: {{ with_currency_symbol($collectFormMax) }}</small>
                                                @endif
                                            </div>
                                            <div class="row g-2">
                                                @include('bookingmodule::admin.booking.partials._admin-company-inflow-payment-method', [
                                                    'instanceId' => 'collect-provider-' . $provider->id,
                                                    'advancePaymentMethodGroups' => $advancePaymentMethodGroups ?? [],
                                                    'advancePmDisabled' => false,
                                                    'advancePmSelected' => '',
                                                ])
                                            </div>
                                            <div class="mb-3 mt-2">
                                                <label for="collect_company_inflow_note" class="form-label">{{ translate('Reference_Note') }} <span class="text-muted small">({{ translate('Optional') }})</span></label>
                                                <textarea name="company_inflow_note" id="collect_company_inflow_note" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Optional_note') }}"></textarea>
                                                <small class="text-muted d-block mt-1 fz-11">{{ translate('Reference_note_can_fill_transaction_field') }}</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                            <button type="submit" class="btn btn--primary">{{ translate('Collect_Amount') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="addPaymentToProviderModal" tabindex="-1" aria-labelledby="addPaymentToProviderModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('admin.provider.details.add_payment', $provider->id) }}" method="post">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addPaymentToProviderModalLabel">{{ translate('Add_Payment_to_Provider') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small mb-3">{{ translate('Record_a_payment_sent_to_provider._It_will_be_added_as_OUT_in_ledger.') }}</p>
                                            <p class="small text-body-secondary mb-3">{{ translate('Add_payment_to_provider_advance_hint') }}</p>
                                            @if($addPaymentModalMaxLedger > 0.009)
                                                <p class="small mb-2"><span class="text-muted">{{ translate('Provider_ledger_receivable_current') }}:</span> <span class="fw-semibold">{{ with_currency_symbol($addPaymentModalMaxLedger) }}</span></p>
                                            @else
                                                <p class="small text-warning mb-2">{{ translate('Add_payment_to_provider_zero_receivable_advance') }}</p>
                                            @endif
                                            <div class="mb-3">
                                                <label for="add_payment_amount" class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" name="amount" id="add_payment_amount" class="form-control" required placeholder="0.00">
                                                @if($companyPaysProvider && $addPaymentFormMax > 0.009)
                                                    <small class="d-block text-muted mt-1 fz-11">{{ translate('Booking_settlement_suggested_max_payout') }}: {{ with_currency_symbol($addPaymentFormMax) }}</small>
                                                @endif
                                            </div>
                                            <div class="mb-3">
                                                <label for="add_payment_transaction_id" class="form-label">{{ translate('Transaction_ID') }}</label>
                                                <input type="text" name="transaction_id" id="add_payment_transaction_id" class="form-control" maxlength="255" placeholder="{{ translate('e.g._bank_reference') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="add_payment_reference_note" class="form-label">{{ translate('Reference_Note') }}</label>
                                                <textarea name="reference_note" id="add_payment_reference_note" class="form-control" rows="2" maxlength="500" placeholder="{{ translate('Optional_note') }}"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                            <button type="submit" class="btn btn--primary">{{ translate('Add_Payment') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="providerPaymentReminderWaModal" tabindex="-1" aria-labelledby="providerPaymentReminderWaModalLabel" aria-hidden="true"
                             data-preview-url="{{ route('admin.provider.details.whatsapp.provider_payment_reminder.preview', $provider->id) }}">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="providerPaymentReminderWaModalLabel">{{ translate('Send_payment_reminder_to_provider') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted small mb-2">{{ translate('WhatsApp_payment_reminder_modal_intro') }}</p>
                                        <p class="small mb-2"><span class="text-muted">{{ translate('phone') }}:</span> <span class="fw-semibold" id="pk-provider-reminder-phone">—</span></p>
                                        <div class="border rounded p-3 bg-light" style="white-space: pre-wrap; min-height: 4rem;" id="pk-provider-reminder-preview">{{ translate('Loading…') }}</div>
                                        <p class="text-danger small mt-2 d-none" id="pk-provider-reminder-err"></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                        <form method="post" action="{{ route('admin.provider.details.whatsapp.provider_payment_reminder.send', $provider->id) }}" class="d-inline" id="pk-provider-reminder-send-form">
                                            @csrf
                                            <button type="submit" class="btn btn--primary" id="pk-provider-reminder-submit">{{ translate('Send') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="waLedgerSendResultModal" tabindex="-1" aria-labelledby="waLedgerSendResultModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="waLedgerSendResultModalLabel" data-role="result-title">{{ translate('WhatsApp_message_sent_modal_title') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-0" id="waLedgerSendResultMessage"></p>
                                    </div>
                                    <div class="modal-footer flex-wrap gap-2">
                                        <a href="#" class="btn btn--primary d-none" id="waLedgerSendResultChatBtn">{{ translate('WhatsApp_view_chat_inbox') }}</a>
                                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('close') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        (function () {
            var wrap = document.querySelector('.pk-payment-subtabs-wrap[data-pk-scroll-provider-id]');
            if (wrap) {
                var providerId = String(wrap.getAttribute('data-pk-scroll-provider-id') || '');
                var scrollKey = 'pk_payment_subtab_scroll_' + providerId;
                function applyStoredScroll() {
                    var raw = sessionStorage.getItem(scrollKey);
                    if (raw === null) return;
                    sessionStorage.removeItem(scrollKey);
                    var y = parseInt(raw, 10);
                    if (isNaN(y) || y < 0) return;
                    window.scrollTo(0, y);
                    requestAnimationFrame(function () {
                        window.scrollTo(0, y);
                    });
                }
                if (document.readyState === 'complete') {
                    applyStoredScroll();
                } else {
                    window.addEventListener('load', applyStoredScroll);
                }
                wrap.addEventListener('click', function (e) {
                    var a = e.target && e.target.closest ? e.target.closest('a.js-pk-payment-subtab') : null;
                    if (!a || !wrap.contains(a)) return;
                    var y = window.pageYOffset || document.documentElement.scrollTop || 0;
                    try {
                        sessionStorage.setItem(scrollKey, String(Math.round(y)));
                    } catch (err) { /* ignore */ }
                });
            }
        })();
        (function () {
            if (typeof bootstrap === 'undefined' || !bootstrap.Popover) return;
            document.querySelectorAll('.pk-payment-widget-info-btn[data-pk-popover-src]').forEach(function (btn) {
                var srcId = btn.getAttribute('data-pk-popover-src');
                var title = btn.getAttribute('data-pk-popover-title') || '';
                var srcEl = srcId ? document.getElementById(srcId) : null;
                if (!srcEl) return;
                new bootstrap.Popover(btn, {
                    container: 'body',
                    placement: 'auto',
                    html: true,
                    sanitize: false,
                    trigger: 'click',
                    title: title,
                    content: srcEl.innerHTML,
                    customClass: 'pk-payment-widget-popover',
                });
                btn.addEventListener('show.bs.popover', function () {
                    document.querySelectorAll('.pk-payment-widget-info-btn[data-pk-popover-src]').forEach(function (other) {
                        if (other === btn) return;
                        var inst = bootstrap.Popover.getInstance(other);
                        if (inst) inst.hide();
                    });
                });
            });
        })();
        var pkAdminAdvanceMethodConfig = @json(\Modules\BookingModule\Services\AdminCompanyInflowPaymentService::fieldConfigMapFromGroups($advancePaymentMethodGroups ?? []));

        function pkApmRenderDynamic($scope, selectedKey, $form, opts) {
            opts = opts || {};
            var useInitial = !!opts.useInitial;
            var $box = $scope.find('.pk-apm-dynamic-fields');
            $box.empty();
            if (!selectedKey || !pkAdminAdvanceMethodConfig[selectedKey]) return;
            var fields = (pkAdminAdvanceMethodConfig[selectedKey].fields || []);
            fields.forEach(function (f) {
                var fid = 'pkdyn-col-' + String(f.name || '').replace(/[^a-zA-Z0-9_-]/g, '_');
                var $col = $('<div class="col-md-6"></div>');
                var $grp = $('<div class="mb-0"></div>');
                var req = !!f.required;
                var $label = $('<label class="form-label" for="' + fid + '"></label>').text(f.label || '');
                if (req) $label.append(' <span class="text-danger">*</span>');
                var $input = $('<input type="text" class="form-control" autocomplete="off">')
                    .attr('id', fid).attr('name', f.input_name || '').attr('placeholder', f.placeholder || '');
                if (req) $input.attr('required', 'required');
                $grp.append($label).append($input);
                $col.append($grp);
                $box.append($col);
            });
        }
        function pkApmTier2Visibility($scope) {
            var t1 = $scope.find('.pk-apm-tier1:checked').val();
            $scope.find('.pk-apm-tier2-digital-wrap').toggleClass('d-none', t1 !== 'digital');
            $scope.find('.pk-apm-tier2-offline-wrap').toggleClass('d-none', t1 !== 'offline');
        }
        function pkApmUpdateHidden($scope) {
            var t1 = $scope.find('.pk-apm-tier1:checked').val();
            var v = '';
            if (t1 === 'cas') v = 'cash_after_service';
            else if (t1 === 'digital') v = ($scope.find('.pk-apm-tier2-digital:checked').val() || '').trim();
            else if (t1 === 'offline') v = ($scope.find('.pk-apm-tier2-offline:checked').val() || '').trim();
            $scope.find('.pk-apm-hidden').val(v);
        }
        function pkApmSync($scope, $form, opts) {
            opts = opts || {};
            pkApmUpdateHidden($scope);
            var v = ($scope.find('.pk-apm-hidden').val() || '').trim();
            pkApmRenderDynamic($scope, v, $form, { useInitial: !!opts.hydrateFromInitial });
        }
        function pkApmInitScope($scope, $form) {
            $scope.find('.pk-apm-tier1').off('change.pkApmCol').on('change.pkApmCol', function (e, payload) {
                var hydrate = payload && payload.hydrateFromInitial;
                if (!hydrate) {
                    $scope.find('.pk-apm-tier2-digital').prop('checked', false);
                    $scope.find('.pk-apm-tier2-offline').prop('checked', false);
                }
                var t1 = $scope.find('.pk-apm-tier1:checked').val();
                pkApmTier2Visibility($scope);
                if (!hydrate && t1 === 'digital' && $scope.find('.pk-apm-tier2-digital').length === 1) {
                    $scope.find('.pk-apm-tier2-digital').first().prop('checked', true);
                }
                if (!hydrate && t1 === 'offline' && $scope.find('.pk-apm-tier2-offline').length === 1) {
                    $scope.find('.pk-apm-tier2-offline').first().prop('checked', true);
                }
                pkApmSync($scope, $form, { hydrateFromInitial: !!hydrate });
            });
            $scope.find('.pk-apm-tier2-digital, .pk-apm-tier2-offline').off('change.pkApmCol').on('change.pkApmCol', function () {
                pkApmSync($scope, $form, {});
            });
        }
        function pkApmBindCollectForm() {
            var $form = $('#collect-from-provider-form');
            if (!$form.length) return;
            $form.find('.pk-apm-scope').each(function () {
                var $scope = $(this);
                pkApmInitScope($scope, $form);
                pkApmTier2Visibility($scope);
                pkApmSync($scope, $form, {});
            });
        }
        $('#collectAmountFromProviderModal').on('shown.bs.modal', function () {
            pkApmBindCollectForm();
        });
        $('#collect-from-provider-form').on('submit', function () {
            var $form = $(this);
            var $sc = $form.find('.pk-apm-scope').first();
            if ($sc.length) {
                pkApmUpdateHidden($sc);
            }
        });
        (function () {
            var modal = document.getElementById('providerPaymentReminderWaModal');
            if (!modal || typeof fetch === 'undefined') return;
            var previewEl = document.getElementById('pk-provider-reminder-preview');
            var errEl = document.getElementById('pk-provider-reminder-err');
            var phoneEl = document.getElementById('pk-provider-reminder-phone');
            var submitBtn = document.getElementById('pk-provider-reminder-submit');
            function csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }
            modal.addEventListener('show.bs.modal', function () {
                if (previewEl) previewEl.textContent = @json(translate('Loading…'));
                if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); }
                if (phoneEl) phoneEl.textContent = '—';
                if (submitBtn) submitBtn.disabled = true;
                var url = modal.getAttribute('data-preview-url');
                if (!url || !previewEl) return;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json'
                    },
                    body: '{}'
                }).then(function (r) { return r.json(); }).then(function (data) {
                    if (phoneEl) phoneEl.textContent = data.phone || '—';
                    if (data.ok) {
                        if (previewEl) previewEl.textContent = data.body || '';
                        if (submitBtn) submitBtn.disabled = false;
                    } else {
                        if (previewEl) previewEl.textContent = '';
                        if (errEl) {
                            errEl.textContent = data.message || data.error || '';
                            errEl.classList.remove('d-none');
                        }
                    }
                }).catch(function () {
                    if (previewEl) previewEl.textContent = '';
                    if (errEl) {
                        errEl.textContent = @json(translate('WhatsApp_payment_reminder_failed'));
                        errEl.classList.remove('d-none');
                    }
                });
            });
        })();
        (function () {
            var sendForm = document.getElementById('pk-provider-reminder-send-form');
            var resultModalEl = document.getElementById('waLedgerSendResultModal');
            if (!sendForm || !resultModalEl || typeof bootstrap === 'undefined') return;
            function csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }
            var titleOk = @json(translate('WhatsApp_message_sent_modal_title'));
            var titleFail = @json(translate('WhatsApp_message_send_failed_modal_title'));
            sendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var submitBtn = document.getElementById('pk-provider-reminder-submit');
                if (submitBtn) submitBtn.disabled = true;
                var fd = new FormData(sendForm);
                fetch(sendForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd,
                    credentials: 'same-origin'
                }).then(function (r) {
                    return r.json().then(function (data) {
                        return { status: r.status, data: data };
                    });
                }).then(function (pack) {
                    var data = pack.data || {};
                    var reminderEl = document.getElementById('providerPaymentReminderWaModal');
                    if (reminderEl) {
                        var inst = bootstrap.Modal.getInstance(reminderEl);
                        if (inst) inst.hide();
                    }
                    var titleEl = resultModalEl.querySelector('[data-role="result-title"]');
                    var msgEl = document.getElementById('waLedgerSendResultMessage');
                    var chatBtn = document.getElementById('waLedgerSendResultChatBtn');
                    if (titleEl) titleEl.textContent = data.ok ? titleOk : titleFail;
                    if (msgEl) msgEl.textContent = data.message || '';
                    if (chatBtn) {
                        if (data.ok && data.show_chat_link && data.chat_url) {
                            chatBtn.href = data.chat_url;
                            chatBtn.classList.remove('d-none');
                        } else {
                            chatBtn.classList.add('d-none');
                            chatBtn.removeAttribute('href');
                        }
                    }
                    var rm = bootstrap.Modal.getOrCreateInstance(resultModalEl);
                    rm.show();
                }).catch(function () {
                    var reminderEl = document.getElementById('providerPaymentReminderWaModal');
                    if (reminderEl) {
                        var inst = bootstrap.Modal.getInstance(reminderEl);
                        if (inst) inst.hide();
                    }
                    var titleEl = resultModalEl.querySelector('[data-role="result-title"]');
                    var msgEl = document.getElementById('waLedgerSendResultMessage');
                    var chatBtn = document.getElementById('waLedgerSendResultChatBtn');
                    if (titleEl) titleEl.textContent = titleFail;
                    if (msgEl) msgEl.textContent = @json(translate('WhatsApp_payment_reminder_failed'));
                    if (chatBtn) chatBtn.classList.add('d-none');
                    bootstrap.Modal.getOrCreateInstance(resultModalEl).show();
                }).finally(function () {
                    if (submitBtn) submitBtn.disabled = false;
                });
            });
        })();
    </script>
@endpush
