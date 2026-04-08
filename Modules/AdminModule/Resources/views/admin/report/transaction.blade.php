@extends('adminmodule::layouts.master')

@section('title',translate('Transaction_Report'))

@push('css_or_js')
    <style>
        .table-responsive--ledger {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-ledger-nowrap th,
        .table-ledger-nowrap td {
            white-space: nowrap;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Transaction_Reports')}}</h2>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <div class="statistics-card statistics-card__primary border flex-grow-1 d-flex gap-2 justify-content-between h-100 align-items-center">
                                        <div>
                                            <h2>{{with_currency_symbol($adminTotalEarning ?? 0)}}</h2>
                                            <h3>{{translate('Admin_earning')}}</h3>
                                        </div>
                                        <div class="absolute-img position-static align-self-start"  data-bs-toggle="tooltip" data-bs-title="{{translate('Admin balance means total Earning of the admin')}}">
                                            <img src="{{asset('assets/admin-module')}}/img/icons/info.svg" class="svg" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="statistics-card statistics-card__info border flex-grow-1 d-flex gap-2 justify-content-between h-100 align-items-center">
                                        <div>
                                            <h2>{{with_currency_symbol($commission_earning??0)}}</h2>
                                            <h3>{{translate('Admin_commission')}}</h3>
                                        </div>
                                        <div class="absolute-img position-static align-self-start"  data-bs-toggle="tooltip" data-bs-title="{{translate('Admin balance means total Earning of the admin')}}">
                                            <img src="{{asset('assets/admin-module')}}/img/icons/info.svg" class="svg" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="statistics-card statistics-card__ongoing border flex-grow-1 flex-grow-1 d-flex gap-2 justify-content-between h-100 align-items-center">
                                        <div>
                                            <h2>{{with_currency_symbol($extra_fee)}}</h2>
                                            <h3>{{translate('extra_fee')}}</h3>
                                        </div>
                                        <div class="absolute-img position-static align-self-start"  data-bs-toggle="tooltip" data-bs-title="{{translate('extra fee means the earning from booking extra fee')}}">
                                            <img src="{{asset('assets/admin-module')}}/img/icons/info.svg" class="svg" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="statistics-card statistics-card__subscribed-providers border flex-grow-1 flex-grow-1 d-flex gap-2 justify-content-between h-100 align-items-center">
                                        <div>
                                            <h2>{{with_currency_symbol(($adminAccount->balance_pending??0))}}</h2>
                                            <h3>{{translate('Pending_Balance')}}</h3>
                                        </div>
                                        <div class="absolute-img position-static align-self-start"  data-bs-toggle="tooltip" data-bs-title="{{translate('Pending balance means digitally placed booking amount which is yet to disperse')}}">
                                            <img src="{{asset('assets/admin-module')}}/img/icons/info.svg" class="svg" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="statistics-card statistics-card__canceled border flex-grow-1 flex-grow-1 d-flex gap-2 justify-content-between h-100 align-items-center">
                                        <div>
                                            <h2>{{with_currency_symbol($adminAccount->account_payable??0)}}</h2>
                                            <h3>{{translate('Account_Payable')}}</h3>
                                        </div>
                                        <div class="absolute-img position-static align-self-start"  data-bs-toggle="tooltip" data-bs-title="{{translate('Account payable means the booking amount that the admin has to pay to the providers')}}">
                                            <img src="{{asset('assets/admin-module')}}/img/icons/info.svg" class="svg" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="statistics-card statistics-card__purple border flex-grow-1 flex-grow-1 d-flex gap-2 justify-content-between h-100 align-items-center">
                                        <div>
                                            <h2>{{with_currency_symbol($adminAccount->account_receivable??0)}}</h2>
                                            <h3>{{translate('Account_Receivable')}}</h3>
                                        </div>
                                        <div class="absolute-img position-static align-self-start"  data-bs-toggle="tooltip" data-bs-title="{{translate('Account receivable means the booking commission that the admin will get from the providers for Cash After the Services')}}">
                                            <img src="{{asset('assets/admin-module')}}/img/icons/info.svg" class="svg" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="mb-3 fz-16">{{translate('Search_Data')}}</div>
                            <form action="{{ route('admin.report.transaction') }}" method="GET">
                                <input type="hidden" name="transaction_type" value="{{ $queryParams['transaction_type'] ?? 'all' }}">
                                @if(!empty($queryParams['search']))
                                    <input type="hidden" name="search" value="{{ $queryParams['search'] }}">
                                @endif
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('zone')}}</label>
                                        <select class="js-select zone__select" name="zone_ids[]"
                                                multiple="multiple" id="zone_selector__select">
                                            <option value="0" disabled>{{translate('Select Zone')}}</option>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($zones as $zone)
                                                <option value="{{$zone['id']}}" {{array_key_exists('zone_ids', $queryParams) && in_array($zone['id'], $queryParams['zone_ids']) ? 'selected' : '' }}>{{$zone['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('provider')}}</label>
                                        <select class="js-select provider__select" name="provider_ids[]"  id="provider_selector__select"  multiple>
                                            <option value="all">{{translate('Select All')}}</option>
                                            @foreach($providers as $provider)
                                                <option value="{{$provider['id']}}" {{array_key_exists('provider_ids', $queryParams) && in_array($provider['id'], $queryParams['provider_ids']) ? 'selected' : '' }}>{{$provider['company_name']}} ({{$provider['company_phone']}})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30 d-none">
                                        <label class="mb-2">{{translate('type')}}</label>
                                        <select class="js-select type__select" id="filter-by" name="filter_by">
                                            <option value="all" {{array_key_exists('filter_by', $queryParams) && $queryParams['filter_by']=='all'?'selected':''}}>{{translate('All')}}</option>
                                            <option value="collect_cash" {{array_key_exists('filter_by', $queryParams) && $queryParams['filter_by']=='collect_cash'?'selected':''}}>{{translate('Collect Cash')}}</option>
                                            <option value="withdraw" {{array_key_exists('filter_by', $queryParams) && $queryParams['filter_by']=='withdraw'?'selected':''}}>{{translate('withdraw')}}</option>
                                            <option value="payment" {{array_key_exists('filter_by', $queryParams) && $queryParams['filter_by']=='payment'?'selected':''}}>{{translate('payment')}}</option>
                                            <option value="commission" {{array_key_exists('filter_by', $queryParams) && $queryParams['filter_by']=='commission'?'selected':''}}>{{translate('commission')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 mb-30">
                                        <label class="mb-2">{{translate('date_range')}}</label>
                                        <select class="js-select" id="date-range" name="date_range">
                                            <option value="0" disabled selected>{{translate('Select Date Range')}}</option>
                                            <option value="all_time" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='all_time'?'selected':''}}>{{translate('All_Time')}}</option>
                                            <option value="this_week" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_week'?'selected':''}}>{{translate('This_Week')}}</option>
                                            <option value="last_week" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_week'?'selected':''}}>{{translate('Last_Week')}}</option>
                                            <option value="this_month" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_month'?'selected':''}}>{{translate('This_Month')}}</option>
                                            <option value="last_month" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_month'?'selected':''}}>{{translate('Last_Month')}}</option>
                                            <option value="last_15_days" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_15_days'?'selected':''}}>{{translate('Last_15_Days')}}</option>
                                            <option value="this_year" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year'?'selected':''}}>{{translate('This_Year')}}</option>
                                            <option value="last_year" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_year'?'selected':''}}>{{translate('Last_Year')}}</option>
                                            <option value="last_6_month" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='last_6_month'?'selected':''}}>{{translate('Last_6_Month')}}</option>
                                            <option value="this_year_1st_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_1st_quarter'?'selected':''}}>{{translate('This_Year_1st_Quarter')}}</option>
                                            <option value="this_year_2nd_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_2nd_quarter'?'selected':''}}>{{translate('This_Year_2nd_Quarter')}}</option>
                                            <option value="this_year_3rd_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_3rd_quarter'?'selected':''}}>{{translate('This_Year_3rd_Quarter')}}</option>
                                            <option value="this_year_4th_quarter" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='this_year_4th_quarter'?'selected':''}}>{{translate('this_year_4th_quarter')}}</option>
                                            <option value="custom_date" {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='custom_date'?'selected':''}}>{{translate('Custom_Date')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='custom_date'?'':'d-none'}}" id="from-filter__div">
                                        <div class="form-floating mb-30">
                                            <input type="date" class="form-control" id="from" name="from" value="{{array_key_exists('from', $queryParams)?$queryParams['from']:''}}">
                                            <label for="from">{{translate('From')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 {{array_key_exists('date_range', $queryParams) && $queryParams['date_range']=='custom_date'?'':'d-none'}}" id="to-filter__div">
                                        <div class="form-floating mb-30">
                                            <input type="date" class="form-control" id="to" name="to" value="{{array_key_exists('to', $queryParams)?$queryParams['to']:''}}">
                                            <label for="to">{{translate('To')}}</label>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn--primary btn-sm">{{translate('Filter')}}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            @php
                                $tabQueryAll = $queryParams;
                                $tabQueryAll['transaction_type'] = 'all';
                                $tabQueryIn = $queryParams;
                                $tabQueryIn['transaction_type'] = 'credit';
                                $tabQueryOut = $queryParams;
                                $tabQueryOut['transaction_type'] = 'debit';
                            @endphp
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="card border-0 bg-success bg-opacity-10">
                                        <div class="card-body">
                                            <p class="text-muted small mb-1">{{ translate('Total_In') }}</p>
                                            <h4 class="mb-0 text-success">{{ with_currency_symbol($ledgerTotalIn ?? 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 bg-danger bg-opacity-10">
                                        <div class="card-body">
                                            <p class="text-muted small mb-1">{{ translate('Total_Out') }}</p>
                                            <h4 class="mb-0 text-danger">{{ with_currency_symbol($ledgerTotalOut ?? 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 bg-primary bg-opacity-10">
                                        <div class="card-body">
                                            <p class="text-muted small mb-1">{{ translate('Net') }}</p>
                                            <h4 class="mb-0 text-primary">{{ with_currency_symbol(round($ledgerNet ?? 0, 2)) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-4 mb-10 gap-3">
                                <ul class="nav nav--tabs">
                                    <li class="nav-item">
                                        <a class="nav-link {{ !isset($queryParams['transaction_type']) || $queryParams['transaction_type']=='all' ? 'active' : '' }}"
                                           href="{{ route('admin.report.transaction') }}?{{ http_build_query($tabQueryAll) }}">{{ translate('all') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($queryParams['transaction_type']) && $queryParams['transaction_type']=='credit' ? 'active' : '' }}"
                                           href="{{ route('admin.report.transaction') }}?{{ http_build_query($tabQueryIn) }}">{{ translate('In') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ isset($queryParams['transaction_type']) && $queryParams['transaction_type']=='debit' ? 'active' : '' }}"
                                           href="{{ route('admin.report.transaction') }}?{{ http_build_query($tabQueryOut) }}">{{ translate('Out') }}</a>
                                    </li>
                                </ul>

                                <div class="d-flex gap-2 fw-medium">
                                    <span class="opacity-75">{{translate('Total_Transactions')}}: </span>
                                    <span class="title-color">{{$filteredTransactions->total()}}</span>
                                </div>
                            </div>

                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{ route('admin.report.transaction') }}"
                                        class="search-form search-form_style-two"
                                        method="GET">
                                    <input type="hidden" name="transaction_type" value="{{ $queryParams['transaction_type'] ?? 'all' }}">
                                    <input type="hidden" name="filter_by" value="{{ $queryParams['filter_by'] ?? 'all' }}">
                                    @if(!empty($queryParams['date_range']))
                                        <input type="hidden" name="date_range" value="{{ $queryParams['date_range'] }}">
                                    @endif
                                    @if(!empty($queryParams['from']))
                                        <input type="hidden" name="from" value="{{ $queryParams['from'] }}">
                                    @endif
                                    @if(!empty($queryParams['to']))
                                        <input type="hidden" name="to" value="{{ $queryParams['to'] }}">
                                    @endif
                                    @if(!empty($queryParams['zone_ids']) && is_array($queryParams['zone_ids']))
                                        @foreach($queryParams['zone_ids'] as $zid)
                                            <input type="hidden" name="zone_ids[]" value="{{ $zid }}">
                                        @endforeach
                                    @endif
                                    @if(!empty($queryParams['provider_ids']) && is_array($queryParams['provider_ids']))
                                        @foreach($queryParams['provider_ids'] as $pid)
                                            <input type="hidden" name="provider_ids[]" value="{{ $pid }}">
                                        @endforeach
                                    @endif
                                    <div class="input-group search-form__input_group">
                                    <span class="search-form__icon">
                                        <span class="material-icons">search</span>
                                    </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                                value="{{ $queryParams['search'] ?? '' }}" name="search"
                                                placeholder="{{ translate('search_by_trx_id') }}, {{ translate('Booking_ID') }}…">
                                    </div>
                                    <button type="submit"
                                            class="btn btn--primary">{{translate('search')}}</button>
                                </form>

                                @can('report_export')
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <div class="dropdown">
                                        <button type="button"
                                            class="btn btn--secondary text-capitalize dropdown-toggle"
                                            data-bs-toggle="dropdown">
                                            <span class="material-icons">file_download</span> {{translate('download')}}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{route('admin.report.transaction.download').'?'.http_build_query($queryParams)}}">
                                                    {{translate('Excel')}}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                @endcan
                            </div>

                            <div class="table-responsive table-responsive--ledger">
                                <table class="table align-middle table-hover table-ledger-nowrap">
                                    <thead class="text-nowrap">
                                        <tr>
                                            <th>{{ translate('SL') }}</th>
                                            <th>{{ translate('Date') }}</th>
                                            <th>{{ translate('Type') }}</th>
                                            <th>{{ translate('Description') }}</th>
                                            <th>{{ translate('Flow') }}</th>
                                            <th>{{ translate('Channel') }}</th>
                                            <th>{{ translate('Received_by') }}</th>
                                            <th>{{ translate('Booking_ID') }}</th>
                                            <th>{{ translate('Transaction_ID') }}</th>
                                            <th>{{ translate('Entry_by') }}</th>
                                            <th class="text-end">{{ translate('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($filteredTransactions as $key => $entry)
                                            <tr>
                                                <td>{{ $filteredTransactions->firstItem() + $key }}</td>
                                                <td>{{ $entry->created_at ? $entry->created_at->format('jS F Y : g i A') : '—' }}</td>
                                                <td>
                                                    @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                        <span class="badge bg-success">{{ translate('In') }}</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ translate('Out') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                        @if($entry->payment_method === 'collect_from_provider')
                                                            {{ translate('Cash_collected_from_provider') }}
                                                        @elseif($entry->payment_method === 'advance_on_booking_create')
                                                            {{ translate('Advance_payment_on_booking_create') }}
                                                        @elseif($entry->booking)
                                                            {{ translate('Booking_payment') }}
                                                        @else
                                                            {{ translate('Payment_received') }}{{ $entry->payment_method ? ' (' . str_replace('_', ' ', $entry->payment_method) . ')' : '' }}
                                                        @endif
                                                    @else
                                                        @if($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
                                                            {{ translate('Refund') }}
                                                        @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_PROVIDER_PAYOUT)
                                                            {{ translate('Provider_payout') }}{{ $entry->provider?->company_name ? ' — ' . $entry->provider->company_name : '' }}{{ $entry->reference_note ? ' — ' . $entry->reference_note : '' }}
                                                        @else
                                                            {{ $entry->reason ? str_replace('_', ' ', $entry->reason) : translate('Out') }}
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                        @if($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_PROVIDER)
                                                            {{ translate('Customer_paid_to_provider') }}
                                                        @elseif($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_COMPANY)
                                                            {{ translate('Customer_paid_to_company') }}
                                                        @else
                                                            —
                                                        @endif
                                                    @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_REFUND)
                                                        {{ translate('Company_paid_to_customer') }}
                                                    @elseif($entry->reason === \Modules\TransactionModule\Entities\LedgerTransaction::REASON_PROVIDER_PAYOUT)
                                                        {{ translate('Provider_payout') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-nowrap">
                                                    {{ $entry->formatPaymentMethodForDisplay() }}
                                                </td>
                                                <td>
                                                    @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                        @if($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_PROVIDER)
                                                            {{ translate('Received_by_provider') }}
                                                        @elseif($entry->received_by === \Modules\TransactionModule\Entities\LedgerTransaction::RECEIVED_BY_COMPANY)
                                                            {{ translate('Received_by_company') }}
                                                        @else
                                                            —
                                                        @endif
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($entry->booking_id && $entry->relationLoaded('booking') && $entry->booking)
                                                        <a href="{{ route('admin.booking.details', [$entry->booking_id]) }}"
                                                           class="text-primary text-decoration-none">{{ $entry->booking->readable_id ?? $entry->booking_id }}</a>
                                                    @elseif($entry->booking_id)
                                                        {{ $entry->booking_id }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $entry->transaction_id ?: '—' }}</td>
                                                <td>{{ $entry->resolvedEntryByLabel() }}</td>
                                                <td class="text-end fw-medium">
                                                    @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                        <span class="text-success">+ {{ with_currency_symbol($entry->amount) }}</span>
                                                    @else
                                                        <span class="text-danger">- {{ with_currency_symbol($entry->amount) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td class="text-center py-4 text-muted" colspan="11">{{ translate('Data_not_available') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end">
                                {!! $filteredTransactions->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict"

        $('#zone_selector__select').on('change', function() {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

        $('#provider_selector__select').on('change', function() {
            var selectedValues = $(this).val();
            if (selectedValues !== null && selectedValues.includes('all')) {
                $(this).find('option').not(':disabled').prop('selected', 'selected');
                $(this).find('option[value="all"]').prop('selected', false);
            }
        });

        $(document).ready(function () {
            $('.zone__select').select2({
                placeholder: "{{translate('Select_zone')}}",
            });
            $('.provider__select').select2({
                placeholder: "{{translate('Select_provider')}}",
            });
            $('.type__select').select2({
                placeholder: "{{translate('Select_Type')}}",
            });
        });

        $(document).ready(function () {
            $('#date-range').on('change', function() {
                if(this.value === 'custom_date') {
                    $('#from-filter__div').removeClass('d-none');
                    $('#to-filter__div').removeClass('d-none');
                }
                if(this.value !== 'custom_date') {
                    $('#from-filter__div').addClass('d-none');
                    $('#to-filter__div').addClass('d-none');
                }
            });
        });
    </script>
@endpush
