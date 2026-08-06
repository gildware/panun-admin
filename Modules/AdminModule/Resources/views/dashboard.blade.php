@extends('adminmodule::layouts.new-master')

@section('title',translate('dashboard'))

@push('css_or_js')
    @include('adminmodule::partials._dashboard-work-widget-styles')
    <style>
        .main-content .container-fluid .row .card {
            position: relative;
            z-index: 0;
        }
        .main-content .container-fluid .row.g-4 {
            display: flex;
            flex-wrap: wrap;
        }
        .finance-kpi-sections {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
            align-items: start;
        }
        @media (max-width: 1199px) {
            .finance-kpi-sections {
                grid-template-columns: 1fr;
            }
        }
        .finance-kpi-section {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 8px 6px;
            border-top: 3px solid var(--fks-accent, #43466e);
        }
        .finance-kpi-section--revenue { --fks-accent: #16a34a; }
        .finance-kpi-section--balances { --fks-accent: #2563eb; }
        .finance-kpi-section--losses { --fks-accent: #dc2626; }
        .finance-kpi-section__title {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0 0 6px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #374151;
        }
        .finance-kpi-section__title .material-symbols-outlined {
            font-size: 15px;
            color: var(--fks-accent);
        }
        .finance-kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 5px;
            flex: 1 1 auto;
            align-items: stretch;
        }
        @media (max-width: 1199px) {
            .finance-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 768px) {
            .finance-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 576px) {
            .finance-kpi-grid {
                grid-template-columns: 1fr;
            }
        }
        .finance-kpi-card {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            min-height: 0;
            height: 100%;
            padding: 5px 7px;
            border: 1px solid var(--fkc-border, #e5e7eb);
            border-radius: 7px;
            background: var(--fkc-soft, #f8fafc);
        }
        .finance-kpi-card__icon {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border: 1px solid var(--fkc-border, #e5e7eb);
            border-radius: 6px;
            background: #fff;
            color: var(--fkc-tone, #64748b);
            font-size: 14px;
            line-height: 1;
        }
        .finance-kpi-card__body {
            flex: 1 1 auto;
            min-width: 0;
        }
        .finance-kpi-card__value {
            font-size: clamp(0.72rem, 1.05vw, 0.82rem);
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .finance-kpi-card__label {
            margin-top: 2px;
            font-size: clamp(0.58rem, 0.85vw, 0.65rem);
            font-weight: 600;
            line-height: 1.2;
            color: #64748b;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }
        .finance-kpi-card__meta {
            margin-top: 2px;
            font-size: 0.52rem;
            line-height: 1.25;
            color: #94a3b8;
            white-space: normal;
        }
        .finance-kpi-card__meta span {
            display: block;
        }
        .finance-kpi-card__meta span + span::before {
            content: none;
        }
        .finance-kpi-card--green { --fkc-soft: #f0fdf4; --fkc-border: #bbf7d0; --fkc-tone: #15803d; }
        .finance-kpi-card--teal { --fkc-soft: #f0fdfa; --fkc-border: #99f6e4; --fkc-tone: #0f766e; }
        .finance-kpi-card--blue { --fkc-soft: #eff6ff; --fkc-border: #bfdbfe; --fkc-tone: #1d4ed8; }
        .finance-kpi-card--violet { --fkc-soft: #f5f3ff; --fkc-border: #ddd6fe; --fkc-tone: #6d28d9; }
        .finance-kpi-card--orange { --fkc-soft: #fff7ed; --fkc-border: #fed7aa; --fkc-tone: #c2410c; }
        .finance-kpi-card--cyan { --fkc-soft: #ecfeff; --fkc-border: #a5f3fc; --fkc-tone: #0e7490; }
        .finance-kpi-card--indigo { --fkc-soft: #eef2ff; --fkc-border: #c7d2fe; --fkc-tone: #4338ca; }
        .finance-kpi-card--amber { --fkc-soft: #fffbeb; --fkc-border: #fde68a; --fkc-tone: #b45309; }
        .finance-kpi-card--rose { --fkc-soft: #fff1f2; --fkc-border: #fecdd3; --fkc-tone: #be123c; }
        .finance-kpi-card--red { --fkc-soft: #fef2f2; --fkc-border: #fecaca; --fkc-tone: #b91c1c; }
        .finance-kpi-card--slate { --fkc-soft: #f8fafc; --fkc-border: #e2e8f0; --fkc-tone: #475569; }
        .finance-kpi-card--pink { --fkc-soft: #fdf2f8; --fkc-border: #fbcfe8; --fkc-tone: #be185d; }
    </style>
@endpush

@section('content')
    @can('dashboard')
    <div class="main-content emp-dash">
        <div class="container-fluid">
            <div class="emp-dash-topbar">
                @include('adminmodule::partials._admin-dashboard-switcher', ['active' => 'finance'])
            </div>

            @if(access_checker('dashboard'))
                @include('adminmodule::partials._finance-kpi-sections')
                @php
                    $recentLedgerTransactions = $data[1]['recent_ledger_transactions'] ?? [];
                    $thisMonthLedgerCount = (int) ($data[1]['this_month_ledger_trx_count'] ?? 0);
                    $recentTransactions = $data[3]['recent_transactions'] ?? [];
                    $thisMonthTransactionsCount = (int) ($data[3]['this_month_trx_count'] ?? 0);
                    $recentWalletTransactions = $data[3]['recent_wallet_transactions'] ?? [];
                    $thisMonthWalletTransactionsCount = (int) ($data[3]['this_month_wallet_trx_count'] ?? 0);
                    $recentCompletedBookings = $data[3]['recent_completed_bookings'] ?? [];
                    $earningGraphYear = session('dashboard_earning_graph_year', date('Y'));
                    $earningGraphMonth = session('dashboard_earning_graph_month');
                @endphp
                <div class="mb-3">
                    <div class="lane-boxes-row">
                            <div class="work-queue-box tone-task" id="dashboard-recent-ledger">
                                <div class="work-queue-box-header">
                                    <div class="work-queue-box-title">
                                        <span class="material-symbols-outlined">receipt_long</span>
                                        <span>{{ translate('recent_ledger_transactions') }}</span>
                                    </div>
                                    @if($thisMonthLedgerCount > 0)
                                        <span class="work-queue-count-badge {{ $thisMonthLedgerCount > 0 ? 'is-hot' : '' }}">{{ $thisMonthLedgerCount }}</span>
                                    @endif
                                </div>
                                <div class="work-queue-box-content">
                                    <div class="work-queue-box-body active">
                                        @if($thisMonthLedgerCount > 0)
                                            <div class="finance-ledger-summary">
                                                {{ $thisMonthLedgerCount }} {{ translate('ledger_transactions_this_month') }}
                                            </div>
                                        @endif
                                        @if(count($recentLedgerTransactions) > 0)
                                            <div class="work-queue-table-wrap">
                                                <table class="work-queue-table">
                                                    <thead>
                                                    <tr>
                                                        <th class="col-amount">{{ translate('Amount') }}</th>
                                                        <th class="col-booking-ref">{{ translate('Booking') }}</th>
                                                        <th class="col-datetime">{{ translate('Date') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($recentLedgerTransactions as $entry)
                                                        <tr>
                                                            <td>
                                                                @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                                                    <span class="finance-amount is-credit">+ {{ with_currency_symbol($entry->amount) }}</span>
                                                                @else
                                                                    <span class="finance-amount is-debit">- {{ with_currency_symbol($entry->amount) }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="cell-primary">{{ $entry->booking?->readable_id ?? $entry->booking_id ?? '—' }}</span>
                                                            </td>
                                                            <td class="datetime-main">{{ date('d M, H:i a', strtotime($entry->created_at)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="work-queue-empty">
                                                <span class="material-symbols-outlined">receipt_long</span>
                                                <span>{{ translate('No Recent Ledger Transactions') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="work-queue-box-footer">
                                    <a href="{{ route('admin.ledger.index') }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                                </div>
                            </div>

                            <div class="work-queue-box tone-booking" id="dashboard-recent-transactions">
                                <div class="work-queue-box-header">
                                    <div class="work-queue-box-title">
                                        <span class="material-symbols-outlined">sync_alt</span>
                                        <span>{{ translate('recent_transactions') }}</span>
                                    </div>
                                    @if($thisMonthTransactionsCount > 0)
                                        <span class="work-queue-count-badge {{ $thisMonthTransactionsCount > 0 ? 'is-hot' : '' }}">{{ $thisMonthTransactionsCount }}</span>
                                    @endif
                                </div>
                                <div class="work-queue-box-content">
                                    <div class="work-queue-box-body active">
                                        @if($thisMonthTransactionsCount > 0)
                                            <div class="finance-ledger-summary">
                                                {{ $thisMonthTransactionsCount }} {{ translate('transactions_this_month') }}
                                            </div>
                                        @endif
                                        @if(count($recentTransactions) > 0)
                                            <div class="work-queue-table-wrap">
                                                <table class="work-queue-table">
                                                    <thead>
                                                    <tr>
                                                        <th class="col-amount">{{ translate('Amount') }}</th>
                                                        <th class="col-booking-ref">{{ translate('Booking') }}</th>
                                                        <th class="col-datetime">{{ translate('Date') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($recentTransactions as $entry)
                                                        <tr>
                                                            <td>
                                                                @if($entry->credit > 0)
                                                                    <span class="finance-amount is-credit">+ {{ with_currency_symbol($entry->credit) }}</span>
                                                                @else
                                                                    <span class="finance-amount is-debit">- {{ with_currency_symbol($entry->debit) }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="cell-primary">{{ $entry->booking?->readable_id ?? $entry->booking_id ?? '—' }}</span>
                                                            </td>
                                                            <td class="datetime-main">{{ date('d M, H:i a', strtotime($entry->created_at)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="work-queue-empty">
                                                <span class="material-symbols-outlined">sync_alt</span>
                                                <span>{{ translate('No Recent Transactions') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="work-queue-box-footer">
                                    <a href="{{ route('admin.transaction.list', ['trx_type' => 'all']) }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                                </div>
                            </div>

                            <div class="work-queue-box tone-wallet" id="dashboard-recent-wallet-transactions">
                                <div class="work-queue-box-header">
                                    <div class="work-queue-box-title">
                                        <span class="material-symbols-outlined">account_balance_wallet</span>
                                        <span>{{ translate('recent_wallet_transactions') }}</span>
                                    </div>
                                    @if($thisMonthWalletTransactionsCount > 0)
                                        <span class="work-queue-count-badge {{ $thisMonthWalletTransactionsCount > 0 ? 'is-hot' : '' }}">{{ $thisMonthWalletTransactionsCount }}</span>
                                    @endif
                                </div>
                                <div class="work-queue-box-content">
                                    <div class="work-queue-box-body active">
                                        @if($thisMonthWalletTransactionsCount > 0)
                                            <div class="finance-ledger-summary">
                                                {{ $thisMonthWalletTransactionsCount }} {{ translate('wallet_transactions_this_month') }}
                                            </div>
                                        @endif
                                        @if(count($recentWalletTransactions) > 0)
                                            <div class="work-queue-table-wrap">
                                                <table class="work-queue-table">
                                                    <thead>
                                                    <tr>
                                                        <th class="col-amount">{{ translate('Amount') }}</th>
                                                        <th class="col-name">{{ translate('Customer') }}</th>
                                                        <th class="col-datetime">{{ translate('Date') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($recentWalletTransactions as $entry)
                                                        <tr>
                                                            <td>
                                                                @if($entry->credit > 0)
                                                                    <span class="finance-amount is-credit">+ {{ with_currency_symbol($entry->credit) }}</span>
                                                                @else
                                                                    <span class="finance-amount is-debit">- {{ with_currency_symbol($entry->debit) }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="cell-primary">{{ trim(($entry->to_user?->first_name ?? '').' '.($entry->to_user?->last_name ?? '')) ?: '—' }}</span>
                                                            </td>
                                                            <td class="datetime-main">{{ date('d M, H:i a', strtotime($entry->created_at)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="work-queue-empty">
                                                <span class="material-symbols-outlined">account_balance_wallet</span>
                                                <span>{{ translate('No_recent_wallet_transactions') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="work-queue-box-footer">
                                    <a href="{{ route('admin.customer.wallet.report') }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                                </div>
                            </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="lane-boxes-row">
                            <div class="work-queue-box tone-earning" id="dashboard-earning-statistics">
                                <div class="work-queue-box-header">
                                    <div class="work-queue-box-title">
                                        <span class="material-symbols-outlined">show_chart</span>
                                        <span>{{ translate('earning_statistics') }}</span>
                                    </div>
                                    <div class="dashboard-earning-filter-wrap">
                                        <div class="select-wrap">
                                            <select class="js-select update-chart update-chart-year">
                                                @php($from_year = date('Y'))
                                                @php($to_year = $from_year - 10)
                                                @while($from_year != $to_year)
                                                    <option value="{{ $from_year }}" {{ (string) $earningGraphYear === (string) $from_year ? 'selected' : '' }}>
                                                        {{ $from_year }}
                                                    </option>
                                                    @php($from_year--)
                                                @endwhile
                                            </select>
                                        </div>
                                        <div class="select-wrap">
                                            <select class="js-select update-chart update-chart-month"
                                                    data-placeholder="{{ translate('month') }}"
                                                    data-allow-clear="true">
                                                <option value=""></option>
                                                @foreach(range(1, 12) as $monthNumber)
                                                    <option value="{{ $monthNumber }}"
                                                        {{ (string) $earningGraphMonth === (string) $monthNumber ? 'selected' : '' }}>
                                                        {{ date('M', mktime(0, 0, 0, $monthNumber, 1)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="work-queue-box-content">
                                    <div class="work-queue-box-body active">
                                        <div class="finance-chart-wrap">
                                            <div id="apex_line-chart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="work-queue-box tone-lead" id="dashboard-recent-completed-bookings">
                                <div class="work-queue-box-header">
                                    <div class="work-queue-box-title">
                                        <span class="material-symbols-outlined">task_alt</span>
                                        <span>{{ translate('recent_completed_bookings_with_earning') }}</span>
                                    </div>
                                    <span class="work-queue-count-badge">{{ count($recentCompletedBookings) }}</span>
                                </div>
                                <div class="work-queue-box-content">
                                    <div class="work-queue-box-body active">
                                        @if(count($recentCompletedBookings) > 0)
                                            <div class="work-queue-table-wrap">
                                                <table class="work-queue-table">
                                                    <thead>
                                                    <tr>
                                                        <th class="col-booking-ref">{{ translate('Booking') }}</th>
                                                        <th class="col-name">{{ translate('Provider') }}</th>
                                                        <th class="col-amount">{{ translate('Our_Earning') }}</th>
                                                        <th class="col-datetime">{{ translate('Date') }}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($recentCompletedBookings as $booking)
                                                        <tr class="is-clickable booking-redirect"
                                                            data-route="{{ route('admin.booking.details', [$booking->id]) }}">
                                                            <td>
                                                                <span class="cell-primary">{{ $booking->readable_id ?? '—' }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="cell-primary">{{ $booking->provider?->company_name ?? '—' }}</span>
                                                            </td>
                                                            <td>
                                                                <span class="finance-amount is-credit">{{ with_currency_symbol($booking->our_earning ?? 0) }}</span>
                                                            </td>
                                                            <td class="datetime-main">{{ date('d M, H:i a', strtotime($booking->updated_at)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="work-queue-empty">
                                                <span class="material-symbols-outlined">task_alt</span>
                                                <span>{{ translate('No_completed_bookings') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="work-queue-box-footer">
                                    <a href="{{ route('admin.booking.list', ['booking_status' => 'completed', 'service_type' => 'all']) }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                                </div>
                            </div>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="text-center">
                                    {{translate('welcome_to_admin_panel')}}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @else
        <div class="main-content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body dashboard-empty d-center">
                        <div class="text-center">
                            <img src="{{asset('/assets/empty-dashboard.png')}}" alt="">
                            <h3 class="p-2 mt-3">{{ translate('Welcome to') }} {{ business_config('business_name', 'business_information')?->live_values }}</h3>
                            <p class="">{{ translate('Get started by using the left menu to manage your tasks and tools.') }}</p>
                            <h6 class="">{{ translate('Happy working') }}!</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

@endsection


@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/apex/apexcharts.min.js"></script>

    <script>
        'use strict';

        var earningChartMonthCategories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        function applyEarningChartResponse(response) {
            chart.updateOptions({
                xaxis: {
                    categories: response.categories || earningChartMonthCategories
                }
            });
            chart.updateSeries([{
                name: "{{translate('Total_Revenue')}}",
                data: response.total_earning
            }, {
                name: "{{translate('Our_Earning')}}",
                data: response.commission_earning
            }]);
        }

        function update_chart(year, month) {
            var url = '{{route('admin.update-dashboard-earning-graph')}}?year=' + encodeURIComponent(year);
            if (month) {
                url += '&month=' + encodeURIComponent(month);
            }

            $.getJSON(url, function (response) {
                applyEarningChartResponse(response);
            });
        }

        $(document).off('change.dashboardEarningYear', '.js-select.update-chart-year')
            .on('change.dashboardEarningYear', '.js-select.update-chart-year', function () {
                var selectedYear = $(this).val();
                var $monthSelect = $('.js-select.update-chart-month');

                localStorage.setItem('selectedYear', selectedYear);
                localStorage.removeItem('selectedMonth');
                $monthSelect.val(null);
                if ($monthSelect.data('select2')) {
                    $monthSelect.trigger('change.select2');
                }

                update_chart(selectedYear);
            });

        $(document).off('change.dashboardEarningMonth', '.js-select.update-chart-month')
            .on('change.dashboardEarningMonth', '.js-select.update-chart-month', function () {
                var selectedMonth = $(this).val();
                var selectedYear = $('.js-select.update-chart-year').val();

                if (selectedMonth) {
                    localStorage.setItem('selectedMonth', selectedMonth);
                } else {
                    localStorage.removeItem('selectedMonth');
                }

                update_chart(selectedYear, selectedMonth || null);
            });

        $(document).ready(function() {
            var storedYear = localStorage.getItem('selectedYear');
            var storedMonth = localStorage.getItem('selectedMonth');

            if (storedYear) {
                $('.js-select.update-chart-year').val(storedYear);
            }
            if (storedMonth) {
                $('.js-select.update-chart-month').val(storedMonth);
            }

            if (storedYear || storedMonth) {
                if ($('.js-select.update-chart-year').data('select2')) {
                    $('.js-select.update-chart-year').trigger('change.select2');
                }
                if ($('.js-select.update-chart-month').data('select2')) {
                    $('.js-select.update-chart-month').trigger('change.select2');
                }

                update_chart(
                    storedYear || $('.js-select.update-chart-year').val(),
                    storedMonth || null
                );
            }
        });

        var options = {
            series: [
                {
                    name: "{{translate('Total_Revenue')}}",
                    data: @json($chart_data['total_earning'])
                },
                {
                    name: "{{translate('Our_Earning')}}",
                    data: @json($chart_data['commission_earning'])
                }
            ],
            chart: {
                height: 220,
                type: 'line',
                dropShadow: {
                    enabled: true,
                    color: '#000',
                    top: 18,
                    left: 7,
                    blur: 10,
                    opacity: 0.2
                },
                toolbar: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    offsetX: 0,
                    formatter: function (value) {
                        return Math.abs(value)
                    }
                },
            },
            colors: ['#4FA7FF', '#82C662'],
            dataLabels: {
                enabled: false,
            },
            stroke: {
                curve: 'smooth',
            },
            grid: {
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                borderColor: '#CAD2FF',
                strokeDashArray: 5,
            },
            markers: {
                size: 1
            },
            theme: {
                mode: 'light',
            },
            xaxis: {
                categories: @json($chart_data['categories'])
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                floating: false,
                offsetY: 0,
                offsetX: 0,
                fontSize: '10px',
                itemMargin: {
                    horizontal: 8,
                    vertical: 4
                },
            },
        };

        if (localStorage.getItem('dir') === 'rtl') {
            options.yaxis.labels.offsetX = -20;
        }

        var chart = new ApexCharts(document.querySelector("#apex_line-chart"), options);
        chart.render();

        function navigateFromDashboardRow($row) {
            var route = $.trim($row.attr('data-route') || '');
            if (route && route !== '#') {
                location.href = route;
            }
        }

        $(document).on('click', '.booking-redirect', function () {
            navigateFromDashboardRow($(this));
        });
    </script>
@endpush
