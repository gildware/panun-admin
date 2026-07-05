@extends('adminmodule::layouts.master')

@section('title', translate('Welcome_Bonus_Report'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Welcome_Bonus_Report') }}</h2>
                            <p class="fz-12 text-muted mb-0">{{ translate('Track welcome wallet bonuses credited to new customers on app registration.') }}</p>
                        </div>
                        <a href="{{ route('admin.customer.settings', ['web_page' => 'welcome_bonus']) }}"
                           class="btn btn-outline--primary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons fz-16">settings</span>
                            {{ translate('Welcome_Bonus_Settings') }}
                        </a>
                    </div>

                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-3 mb-3">
                        <div class="statistics-card statistics-card__total-orders border flex-grow-1">
                            <h2>{{ $stats['total_granted'] }}</h2>
                            <h3>{{ translate('Total_Welcome_Bonuses_Granted') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Number of customers who received a welcome bonus') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__primary border flex-grow-1">
                            <h2>{{ with_currency_symbol($stats['total_amount']) }}</h2>
                            <h3>{{ translate('Total_Welcome_Bonus_Paid') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Total wallet amount credited as welcome bonus') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__ongoing border flex-grow-1">
                            <h2>{{ $stats['this_month_granted'] }}</h2>
                            <h3>{{ translate('This_Month_Granted') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Welcome bonuses granted in the current month') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__subscribed-providers border flex-grow-1">
                            <h2>{{ with_currency_symbol($stats['this_month_amount']) }}</h2>
                            <h3>{{ translate('This_Month_Amount') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Total welcome bonus amount credited in the current month') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__canceled border flex-grow-1">
                            <h2>{{ $welcomeBonusEnabled ? with_currency_symbol($configuredAmount) : translate('Disabled') }}</h2>
                            <h3>{{ translate('Current_Bonus_Amount') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Configured welcome bonus amount for new registrations') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3 fz-16 fw-medium">{{ translate('Filter_Data') }}</div>
                            <form action="{{ route('admin.customer.welcome-bonus.report') }}" method="GET">
                                <div class="row g-3">
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="js-select zone__select" name="zone_ids[]" multiple id="zone_selector__select">
                                            <option value="0" disabled>{{ translate('Select Zone') }}</option>
                                            <option value="all">{{ translate('Select All') }}</option>
                                            @foreach($zones as $zone)
                                                <option value="{{ $zone['id'] }}"
                                                    {{ array_key_exists('zone_ids', $queryParams) && in_array($zone['id'], (array) $queryParams['zone_ids']) ? 'selected' : '' }}>
                                                    {{ $zone['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="js-select customer__select" name="customer_ids[]" multiple id="customer_selector__select">
                                            <option value="all">{{ translate('Select All') }}</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer['id'] }}"
                                                    {{ array_key_exists('customer_ids', $queryParams) && in_array($customer['id'], (array) $queryParams['customer_ids']) ? 'selected' : '' }}>
                                                    {{ $customer['first_name'].' '.$customer['last_name'] }} ({{ $customer['phone'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <select class="js-select" id="date-range" name="date_range">
                                            <option value="0" disabled selected>{{ translate('Date_Range') }}</option>
                                            <option value="all_time" {{ ($queryParams['date_range'] ?? '') === 'all_time' ? 'selected' : '' }}>{{ translate('All_Time') }}</option>
                                            <option value="this_week" {{ ($queryParams['date_range'] ?? '') === 'this_week' ? 'selected' : '' }}>{{ translate('This_Week') }}</option>
                                            <option value="last_week" {{ ($queryParams['date_range'] ?? '') === 'last_week' ? 'selected' : '' }}>{{ translate('Last_Week') }}</option>
                                            <option value="this_month" {{ ($queryParams['date_range'] ?? '') === 'this_month' ? 'selected' : '' }}>{{ translate('This_Month') }}</option>
                                            <option value="last_month" {{ ($queryParams['date_range'] ?? '') === 'last_month' ? 'selected' : '' }}>{{ translate('Last_Month') }}</option>
                                            <option value="last_15_days" {{ ($queryParams['date_range'] ?? '') === 'last_15_days' ? 'selected' : '' }}>{{ translate('Last_15_Days') }}</option>
                                            <option value="this_year" {{ ($queryParams['date_range'] ?? '') === 'this_year' ? 'selected' : '' }}>{{ translate('This_Year') }}</option>
                                            <option value="last_year" {{ ($queryParams['date_range'] ?? '') === 'last_year' ? 'selected' : '' }}>{{ translate('Last_Year') }}</option>
                                            <option value="last_6_month" {{ ($queryParams['date_range'] ?? '') === 'last_6_month' ? 'selected' : '' }}>{{ translate('Last_6_Month') }}</option>
                                            <option value="custom_date" {{ ($queryParams['date_range'] ?? '') === 'custom_date' ? 'selected' : '' }}>{{ translate('Custom_Date') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 {{ ($queryParams['date_range'] ?? '') === 'custom_date' ? '' : 'd-none' }}" id="from-filter__div">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="from" name="from"
                                                   value="{{ $queryParams['from'] ?? '' }}">
                                            <label for="from">{{ translate('From') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 {{ ($queryParams['date_range'] ?? '') === 'custom_date' ? '' : 'd-none' }}" id="to-filter__div">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="to" name="to"
                                                   value="{{ $queryParams['to'] ?? '' }}">
                                            <label for="to">{{ translate('To') }}</label>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.customer.welcome-bonus.report') }}" class="btn btn-secondary btn-sm">{{ translate('reset') }}</a>
                                        <button type="submit" class="btn btn--primary btn-sm">{{ translate('Filter') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-2 mb-10 gap-3 pb-3">
                                <div class="fw-medium">
                                    <span class="opacity-75">{{ translate('Total_Records') }}:</span>
                                    <span class="title-color">{{ $bonuses->total() }}</span>
                                </div>
                            </div>

                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                                <form action="{{ route('admin.customer.welcome-bonus.report') }}"
                                      class="search-form search-form_style-two" method="GET">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{ $queryParams['search'] ?? '' }}" name="search"
                                               placeholder="{{ translate('Search_by_customer_name_or_phone') }}">
                                    </div>
                                    @foreach($queryParams as $key => $value)
                                        @if($key === 'search')
                                            @continue
                                        @endif
                                        @if(is_array($value))
                                            @foreach($value as $item)
                                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                                </form>

                                @can('customer_export')
                                    <div class="dropdown">
                                        <button type="button" class="btn btn--secondary text-capitalize dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                            <span class="material-icons">file_download</span> {{ translate('download') }}
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ route('admin.customer.welcome-bonus.report.download').'?'.http_build_query($queryParams) }}">
                                                    {{ translate('Excel') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                @endcan
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="text-nowrap">
                                    <tr>
                                        <th>{{ translate('SL') }}</th>
                                        <th>{{ translate('Customer') }}</th>
                                        <th>{{ translate('Registration_Date') }}</th>
                                        <th>{{ translate('Bonus_Credited_Date') }}</th>
                                        <th class="text-end">{{ translate('Bonus_Amount') }}</th>
                                        <th class="text-end">{{ translate('Wallet_Balance_After') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($bonuses as $key => $bonus)
                                        @php($customer = $bonus->to_user)
                                        <tr>
                                            <td>{{ $bonuses->firstItem() + $key }}</td>
                                            <td>
                                                @if($customer)
                                                    <div class="d-flex flex-column">
                                                        <a href="{{ route('admin.customer.detail', [$customer->id, 'web_page' => 'overview']) }}"
                                                           class="fw-medium text-dark">
                                                            {{ $customer->first_name.' '.$customer->last_name }}
                                                        </a>
                                                        <span class="fz-12 text-muted">{{ $customer->phone }}</span>
                                                    </div>
                                                @else
                                                    <span class="badge badge-danger">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($customer?->created_at)
                                                    {{ date('d M Y, h:i A', strtotime($customer->created_at)) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ date('d M Y, h:i A', strtotime($bonus->created_at)) }}</td>
                                            <td class="text-end">
                                                <span class="fw-semibold text-success">{{ with_currency_symbol($bonus->credit) }}</span>
                                            </td>
                                            <td class="text-end">{{ with_currency_symbol($bonus->balance) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">
                                                <div class="text-center py-5 text-muted">
                                                    {{ translate('No_welcome_bonus_records_found') }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                {!! $bonuses->links() !!}
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
        "use strict";

        $('#date-range').on('change', function () {
            if ($(this).val() === 'custom_date') {
                $('#from-filter__div, #to-filter__div').removeClass('d-none');
            } else {
                $('#from-filter__div, #to-filter__div').addClass('d-none');
            }
        });

        $('.customer__select').select2({
            placeholder: "{{ translate('Select_Customer') }}",
            allowClear: true
        });
    </script>
@endpush
