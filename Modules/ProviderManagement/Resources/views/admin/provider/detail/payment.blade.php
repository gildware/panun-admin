@extends('adminmodule::layouts.master')

@section('title', translate('provider_details') . ' - ' . translate('Payment'))

@push('css_or_js')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Provider_Details') }}</h2>
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
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id }}">{{ translate('Business Plan') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body p-30">
                    @php
                        $providerPayable = (float) ($provider->owner->account->account_payable ?? 0);
                        $providerReceivable = (float) ($provider->owner->account->account_receivable ?? 0);
                        $netPayableAmount = $providerReceivable - $providerPayable;
                        $companyPaysProvider = $netPayableAmount > 0;
                        $providerPaysCompany = $netPayableAmount < 0;
                    @endphp

                    {{-- Net Payable --}}
                    <div class="row g-3 mb-30">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 h-100 {{ $companyPaysProvider ? 'statistics-card__collect-cash' : '' }}">
                                <h3>{{ translate('Net_Payable') }}</h3>
                                <h2>{{ with_currency_symbol(abs($netPayableAmount)) }}</h2>
                                @if($companyPaysProvider)
                                    <p class="small text-muted mb-1">{{ translate('Company_has_to_pay_to_provider') }}</p>
                                    @can('provider_update')
                                        <button type="button" class="btn btn--primary text-capitalize w-100 btn--lg mw-75" data-bs-toggle="modal" data-bs-target="#addPaymentToProviderModal">
                                            {{ translate('Add_Payment_to_Provider') }}
                                        </button>
                                    @endcan
                                @elseif($providerPaysCompany)
                                    <p class="small text-muted mb-1">{{ translate('Provider_has_to_pay_to_company') }}</p>
                                    @can('provider_update')
                                        <button type="button" class="btn btn--primary text-capitalize w-100 btn--lg mw-75" data-bs-toggle="modal" data-bs-target="#collectAmountFromProviderModal">
                                            {{ translate('Collect_Amount') }}
                                        </button>
                                    @endcan
                                @else
                                    <p class="small text-muted mb-1">{{ translate('No_outstanding_balance') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Pending Withdrawn, Already Withdrawn, Withdrawable Amount, Total Earning --}}
                    <div class="row g-3 mb-30">
                        <div class="col-12">
                            <h4 class="mb-2">{{ translate('Withdrawal_Summary') }}</h4>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="statistics-card statistics-card__style2 statistics-card__pending-withdraw h-100">
                                <h3>{{ translate('Pending_Withdrawn') }}</h3>
                                <h2>{{ with_currency_symbol($provider->owner->account->balance_pending ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="statistics-card statistics-card__style2 statistics-card__already-withdraw h-100">
                                <h3>{{ translate('Already_Withdrawn') }}</h3>
                                <h2>{{ with_currency_symbol($provider->owner->account->total_withdrawn ?? 0) }}</h2>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="statistics-card statistics-card__style2 statistics-card__withdrawable-amount h-100">
                                <h3>{{ translate('Withdrawable_Amount') }}</h3>
                                <h2>{{ with_currency_symbol($provider->owner->account->account_receivable ?? 0) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Revenue Summary: Total Revenue, Provider Net Earning, Total Company Commission --}}
                    <div class="row g-3 mb-30">
                        <div class="col-12">
                            <h4 class="mb-2">{{ translate('Revenue_Summary') }}</h4>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 h-100 border border-primary">
                                <h3>{{ translate('Total_Revenue') }}</h3>
                                <h2 class="text-primary">{{ with_currency_symbol($totalRevenue ?? 0) }}</h2>
                                <p class="small text-muted mb-0">{{ translate('Grand_total_of_all_completed_bookings') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 h-100">
                                <h3>{{ translate('Provider_Net_Earning') }}</h3>
                                <h2>{{ with_currency_symbol($providerNetEarning ?? 0) }}</h2>
                                <p class="small text-muted mb-0">{{ translate('Total_revenue_minus_company_commission') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 h-100">
                                <h3>{{ translate('Total_Company_Commission') }}</h3>
                                <h2>{{ with_currency_symbol($totalCompanyCommission ?? 0) }}</h2>
                                <p class="small text-muted mb-0">{{ translate('Company_commission_of_completed_bookings') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- 1. Provider Ledger: company sent to provider / company received from provider --}}
                    <h4 class="mb-3">{{ translate('Provider_Ledger') }}</h4>
                    <p class="text-muted small mb-3">{{ translate('Money_company_sent_to_provider_and_money_company_received_from_provider') }}</p>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Type') }}</th>
                                    <th class="text-end">{{ translate('Amount') }}</th>
                                    <th>{{ translate('Transaction_ID') }}</th>
                                    <th>{{ translate('Reference') }}</th>
                                    <th>{{ translate('Entry_by') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($providerLedger as $entry)
                                    <tr>
                                        <td>{{ $entry->date ? $entry->date->format('d M Y') : '—' }}</td>
                                        <td>
                                            @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_OUT)
                                                <span class="badge bg-danger">{{ translate('Out') }} ({{ translate('Company_sent_to_provider') }})</span>
                                            @else
                                                <span class="badge bg-success">{{ translate('In') }} ({{ translate('Company_received_from_provider') }})</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">{{ with_currency_symbol($entry->amount) }}</td>
                                        <td>{{ $entry->transaction_id ?: '—' }}</td>
                                        <td>
                                            {{ $entry->reference_note ?: '—' }}
                                            @if($entry->booking_id && $entry->booking)
                                                <br><small class="text-muted">{{ translate('Booking') }}: {{ $entry->booking->readable_id ?? $entry->booking_id }}</small>
                                            @endif
                                            @if($entry->booking_repeat_id && $entry->repeat)
                                                <br><small class="text-muted">{{ translate('Repeat') }}: {{ $entry->repeat->readable_id ?? $entry->booking_repeat_id }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $entry->creator ? ($entry->creator->first_name ?? $entry->creator->email ?? $entry->creator->id) : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">{{ translate('No_ledger_entries') }}</td>
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
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookingEarningReportPaginated as $row)
                                    <tr>
                                        <td>{{ $row->readable_id }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->total_amount) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->service_charges) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->extra_service_charges ?? 0) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->parts_charges) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->provider_earning) }}</td>
                                        <td class="text-end">{{ with_currency_symbol($row->admin_commission) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">{{ translate('No_completed_bookings') }}</td>
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

                    @if($providerPaysCompany && abs($netPayableAmount) > 0)
                        <div class="modal fade" id="collectAmountFromProviderModal" tabindex="-1" aria-labelledby="collectAmountFromProviderModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('admin.provider.details.collect_amount', $provider->id) }}" method="post">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="collectAmountFromProviderModalLabel">{{ translate('Collect_Amount') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small mb-3">{{ translate('Record_amount_collected_from_provider._It_will_be_added_as_IN_in_ledger.') }}</p>
                                            <div class="mb-3">
                                                <label for="collect_amount_amount" class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" max="{{ round(abs($netPayableAmount), 2) }}" name="amount" id="collect_amount_amount" class="form-control" required placeholder="0.00">
                                                <small class="text-muted">{{ translate('Max') }}: {{ with_currency_symbol(abs($netPayableAmount)) }}</small>
                                            </div>
                                            <div class="mb-3">
                                                <label for="collect_amount_transaction_id" class="form-label">{{ translate('Transaction_ID') }}</label>
                                                <input type="text" name="transaction_id" id="collect_amount_transaction_id" class="form-control" maxlength="255" placeholder="{{ translate('e.g._bank_reference') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="collect_amount_reference_note" class="form-label">{{ translate('Reference_Note') }}</label>
                                                <textarea name="reference_note" id="collect_amount_reference_note" class="form-control" rows="2" maxlength="500" placeholder="{{ translate('Optional_note') }}"></textarea>
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
                    @endif

                    @if($companyPaysProvider && $netPayableAmount > 0)
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
                                            <div class="mb-3">
                                                <label for="add_payment_amount" class="form-label">{{ translate('Amount') }} <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" max="{{ round($netPayableAmount, 2) }}" name="amount" id="add_payment_amount" class="form-control" required placeholder="0.00">
                                                <small class="text-muted">{{ translate('Max') }}: {{ with_currency_symbol($netPayableAmount) }}</small>
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
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
