@extends('adminmodule::layouts.master')

@section('title', translate('Referral_Report'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Refer_And_Earn_Report') }}</h2>
                            <p class="fz-12 text-muted mb-0">{{ translate('Track customers who registered with a referral code and referrer rewards.') }}</p>
                        </div>
                        <a href="{{ route('admin.customer.settings', ['web_page' => 'referral_earning']) }}"
                           class="btn btn-outline--primary btn-sm d-inline-flex align-items-center gap-1">
                            <span class="material-icons fz-16">settings</span>
                            {{ translate('Referral_Settings') }}
                        </a>
                    </div>

                    <div class="d-flex flex-column flex-sm-row flex-wrap gap-3 mb-3">
                        <div class="statistics-card statistics-card__total-orders border flex-grow-1">
                            <h2>{{ $stats['total_referred'] }}</h2>
                            <h3>{{ translate('Total_Referred_Users') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Customers who signed up using a referral code') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__ongoing border flex-grow-1">
                            <h2>{{ $stats['active_referrers'] }}</h2>
                            <h3>{{ translate('Active_Referrers') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Unique customers whose referral code was used') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__subscribed-providers border flex-grow-1">
                            <h2>{{ $stats['completed_first_booking'] }}</h2>
                            <h3>{{ translate('Completed_First_Booking') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Referred users who completed their first booking') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__canceled border flex-grow-1">
                            <h2>{{ $stats['pending_first_booking'] }}</h2>
                            <h3>{{ translate('Pending_First_Booking') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Referred users who have not completed a booking yet') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                        <div class="statistics-card statistics-card__primary border flex-grow-1">
                            <h2>{{ with_currency_symbol($stats['total_earned']) }}</h2>
                            <h3>{{ translate('Total_Referral_Earnings_Paid') }}</h3>
                            <div class="absolute-img" data-bs-toggle="tooltip"
                                 data-bs-title="{{ translate('Total wallet rewards released to referrers after first booking completion') }}">
                                <img src="{{ asset('assets/admin-module') }}/img/icons/info.svg" class="svg" alt="">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3 fz-16 fw-medium">{{ translate('Filter_Data') }}</div>
                            <form action="{{ route('admin.customer.referral-earning.report') }}" method="GET">
                                @if(!empty($statusFilter) && $statusFilter !== 'all')
                                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                                @endif
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
                                        <select class="js-select referrer__select" name="referrer_ids[]" multiple id="referrer_selector__select">
                                            <option value="all">{{ translate('Select All') }}</option>
                                            @foreach($referrers as $referrer)
                                                <option value="{{ $referrer['id'] }}"
                                                    {{ array_key_exists('referrer_ids', $queryParams) && in_array($referrer['id'], (array) $queryParams['referrer_ids']) ? 'selected' : '' }}>
                                                    {{ $referrer['first_name'].' '.$referrer['last_name'] }} ({{ $referrer['ref_code'] }})
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
                                        <a href="{{ route('admin.customer.referral-earning.report') }}" class="btn btn-secondary btn-sm">{{ translate('reset') }}</a>
                                        <button type="submit" class="btn btn--primary btn-sm">{{ translate('Filter') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mx-lg-2 mb-10 gap-3 pb-3">
                                <ul class="nav nav--tabs">
                                    <li class="nav-item">
                                        <a class="nav-link {{ $statusFilter === 'all' ? 'active' : '' }}"
                                           href="{{ route('admin.customer.referral-earning.report', array_merge($queryParams, ['status' => 'all'])) }}">
                                            {{ translate('All') }}
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ $statusFilter === 'completed' ? 'active' : '' }}"
                                           href="{{ route('admin.customer.referral-earning.report', array_merge($queryParams, ['status' => 'completed'])) }}">
                                            {{ translate('Completed_First_Booking') }}
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link {{ $statusFilter === 'pending' ? 'active' : '' }}"
                                           href="{{ route('admin.customer.referral-earning.report', array_merge($queryParams, ['status' => 'pending'])) }}">
                                            {{ translate('Pending_First_Booking') }}
                                        </a>
                                    </li>
                                </ul>
                                <div class="d-flex gap-2 fw-medium">
                                    <span class="opacity-75">{{ translate('Total_Records') }}:</span>
                                    <span class="title-color">{{ $referrals->total() }}</span>
                                </div>
                            </div>

                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                                <form action="{{ route('admin.customer.referral-earning.report') }}"
                                      class="search-form search-form_style-two" method="GET">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                               value="{{ $queryParams['search'] ?? '' }}" name="search"
                                               placeholder="{{ translate('Search_by_referrer_or_referred_user') }}">
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
                                                   href="{{ route('admin.customer.referral-earning.report.download').'?'.http_build_query($queryParams) }}">
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
                                        <th>{{ translate('Referred_User') }}</th>
                                        <th>{{ translate('Registration_Date') }}</th>
                                        <th>{{ translate('Referrer') }}</th>
                                        <th>{{ translate('Referral_Code') }}</th>
                                        <th>{{ translate('First_Booking_Status') }}</th>
                                        <th>{{ translate('First_Booking_Date') }}</th>
                                        <th class="text-end">{{ translate('Referrer_Earned_Amount') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($referrals as $key => $referral)
                                        @php
                                            $referrer = $referral->referred_by_user;
                                            $hasCompletedBooking = (int) ($referral->completed_bookings_count ?? 0) > 0;
                                            $earnedAmount = $hasCompletedBooking ? $referralReward : 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $referrals->firstItem() + $key }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <a href="{{ route('admin.customer.detail', [$referral->id, 'web_page' => 'overview']) }}"
                                                       class="fw-medium text-dark">
                                                        {{ $referral->first_name.' '.$referral->last_name }}
                                                    </a>
                                                    <span class="fz-12 text-muted">{{ $referral->phone }}</span>
                                                </div>
                                            </td>
                                            <td>{{ date('d M Y, h:i A', strtotime($referral->created_at)) }}</td>
                                            <td>
                                                @if($referrer)
                                                    <div class="d-flex flex-column">
                                                        <a href="{{ route('admin.customer.detail', [$referrer->id, 'web_page' => 'overview']) }}"
                                                           class="fw-medium text-dark">
                                                            {{ $referrer->first_name.' '.$referrer->last_name }}
                                                        </a>
                                                        <span class="fz-12 text-muted">{{ $referrer->phone }}</span>
                                                    </div>
                                                @else
                                                    <span class="badge badge-danger">{{ translate('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($referrer?->ref_code)
                                                    <span class="badge badge-primary text-uppercase">{{ $referrer->ref_code }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($hasCompletedBooking)
                                                    <span class="badge badge-success">{{ translate('Completed') }}</span>
                                                @else
                                                    <span class="badge badge-warning text-dark">{{ translate('Pending') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($referral->first_completed_booking_at)
                                                    {{ date('d M Y, h:i A', strtotime($referral->first_completed_booking_at)) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-semibold {{ $earnedAmount > 0 ? 'text-success' : 'text-muted' }}">
                                                    {{ with_currency_symbol($earnedAmount) }}
                                                </span>
                                                @if(!$hasCompletedBooking && $referralReward > 0)
                                                    <div class="fz-11 text-muted">{{ translate('Pending') }}: {{ with_currency_symbol($referralReward) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8">
                                                <div class="text-center py-5 text-muted">
                                                    {{ translate('No_referral_records_found') }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                {!! $referrals->links() !!}
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

        $('.referrer__select').select2({
            placeholder: "{{ translate('Select_Referrer') }}",
            allowClear: true
        });
    </script>
@endpush
