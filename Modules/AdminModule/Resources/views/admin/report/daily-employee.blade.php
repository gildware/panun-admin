@extends('adminmodule::layouts.new-master')

@section('title', translate('Daily_Employee_Report'))

@push('css_or_js')
    <style>
        .daily-employee-report-scroll {
            overflow: auto;
            max-height: 70vh;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
        }

        .daily-employee-report-table {
            width: 100%;
            min-width: 980px;
            margin-bottom: 0;
        }

        .daily-employee-report-table thead th {
            position: sticky;
            top: 0;
            z-index: 4;
            background: #f8f9fa;
            font-size: 12px;
            font-weight: 600;
            white-space: normal;
            line-height: 1.35;
            vertical-align: middle;
            padding: 0.65rem 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .daily-employee-report-table tbody td,
        .daily-employee-report-table tfoot td {
            font-size: 13px;
            padding: 0.6rem 0.5rem;
            vertical-align: middle;
            text-align: center;
        }

        .daily-employee-report-table tbody td.sticky-col,
        .daily-employee-report-table tfoot td.sticky-col,
        .daily-employee-report-table thead th.sticky-col {
            position: sticky;
            left: 0;
            z-index: 5;
            background: #fff;
            text-align: left;
            box-shadow: 1px 0 0 #e9ecef;
        }

        .daily-employee-report-table tbody td.sticky-col-2,
        .daily-employee-report-table thead th.sticky-col-2 {
            left: 110px;
            z-index: 5;
            background: #fff;
            text-align: left;
            box-shadow: 1px 0 0 #e9ecef;
        }

        .daily-employee-report-table thead th.sticky-col {
            z-index: 6;
            background: #f8f9fa;
        }

        .daily-employee-report-table thead th.sticky-col-2 {
            z-index: 6;
            background: #f8f9fa;
        }

        .daily-employee-report-table .metric-zero {
            color: #adb5bd;
        }

        .daily-employee-report-table tfoot td {
            background: #f8f9fa;
            font-weight: 600;
        }

        .daily-employee-report-table tfoot td.sticky-col {
            background: #f8f9fa;
        }
    </style>
@endpush

@section('content')
    @php
        $metricColumns = [
            ['key' => 'leads_added', 'label' => translate('Leads_Added'), 'short' => translate('Leads_Added_short')],
            ['key' => 'leads_handled', 'label' => translate('Leads_Handled'), 'short' => translate('Leads_Handled_short')],
            ['key' => 'lead_followups', 'label' => translate('Lead_Followups_Taken'), 'short' => translate('Lead_Followups_short')],
            ['key' => 'booking_followups', 'label' => translate('Booking_Followups_Taken'), 'short' => translate('Booking_Followups_short')],
            ['key' => 'bookings_added', 'label' => translate('Bookings_Added'), 'short' => translate('Bookings_Added_short')],
            ['key' => 'whatsapp_chats', 'label' => translate('WhatsApp_Chats_Handled'), 'short' => translate('WhatsApp_Chats_short')],
            ['key' => 'whatsapp_replies', 'label' => translate('WhatsApp_Replies_Sent'), 'short' => translate('WhatsApp_Replies_short')],
            ['key' => 'outbound_enquiries', 'label' => translate('Outbound_Enquiries'), 'short' => translate('Outbound_short')],
        ];

        $summaryCards = [
            ['key' => 'leads_added', 'label' => translate('Leads_Added'), 'icon' => 'total_expense.png'],
            ['key' => 'leads_handled', 'label' => translate('Leads_Handled'), 'icon' => 'commission_earning.png'],
            ['key' => 'lead_followups', 'label' => translate('Lead_Followups_Taken'), 'icon' => 'total_expense.png'],
            ['key' => 'booking_followups', 'label' => translate('Booking_Followups_Taken'), 'icon' => 'net_profit.png'],
            ['key' => 'bookings_added', 'label' => translate('Bookings_Added'), 'icon' => 'net_profit.png'],
            ['key' => 'whatsapp_chats', 'label' => translate('WhatsApp_Chats_Handled'), 'icon' => 'total_expense.png'],
            ['key' => 'whatsapp_replies', 'label' => translate('WhatsApp_Replies_Sent'), 'icon' => 'commission_earning.png'],
            ['key' => 'outbound_enquiries', 'label' => translate('Outbound_Enquiries'), 'icon' => 'total_expense.png'],
        ];
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Daily_Employee_Report') }}</h2>
                    <p class="text-muted mb-0 fs-13">{{ translate('Daily_Employee_Report_description') }}</p>
                </div>
                <span class="badge bg-light text-dark border px-3 py-2">
                    {{ (int) ($employeeCount ?? 0) }} {{ translate('Employees') }}
                </span>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3 fz-16">{{ translate('Search_Data') }}</div>
                    <form action="{{ route('admin.report.daily-employee') }}" method="GET">
                        <div class="row g-3 align-items-end">
                            <div class="col-xl-2 col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('From_Date') }}</label>
                                <input type="date" name="date_from" class="form-control h-45" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-xl-2 col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('To_Date') }}</label>
                                <input type="date" name="date_to" class="form-control h-45" value="{{ $dateTo }}">
                            </div>
                            <div class="col-xl-4 col-lg-6">
                                <label class="mb-2">{{ translate('Employee') }}</label>
                                <select name="employee_ids[]" class="js-select form-select" multiple>
                                    @foreach($filterEmployees as $employee)
                                        @php
                                            $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                            $label = $fullName ?: $employee->email;
                                            $isSelected = in_array((string) $employee->id, array_map('strval', $selectedEmployeeIds), true);
                                        @endphp
                                        <option value="{{ $employee->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">{{ translate('Leave_empty_for_all_employees') }}</small>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-sm-6">
                                <label class="mb-2">{{ translate('View') }}</label>
                                <select name="view_mode" class="form-select h-45">
                                    <option value="daily" {{ ($viewMode ?? 'daily') === 'daily' ? 'selected' : '' }}>{{ translate('Daily_Breakdown') }}</option>
                                    <option value="summary" {{ ($viewMode ?? 'daily') === 'summary' ? 'selected' : '' }}>{{ translate('Employee_Summary') }}</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-sm-6 d-flex gap-2">
                                <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('Filter') }}</button>
                                <a href="{{ route('admin.report.daily-employee') }}" class="btn btn--secondary flex-grow-1">{{ translate('Reset') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row gy-3 mb-3">
                @foreach($summaryCards as $card)
                    <div class="col-xl-3 col-lg-4 col-sm-6">
                        <div class="card flex-row gap-3 p-3 flex-wrap align-items-center h-100">
                            <img width="32" class="avatar" src="{{ asset('assets/admin-module/img/icons/' . $card['icon']) }}" alt="">
                            <div>
                                <h3 class="fz-22 mb-0">{{ (int) ($totals[$card['key']] ?? 0) }}</h3>
                                <span class="fz-12 text-muted">{{ $card['label'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-xl-3 col-lg-4 col-sm-6">
                    <div class="card flex-row gap-3 p-3 flex-wrap align-items-center h-100">
                        <img width="32" class="avatar" src="{{ asset('assets/admin-module/img/icons/net_profit.png') }}" alt="">
                        <div>
                            <h3 class="fz-22 mb-0">{{ $totals['online_hours'] ?? '0m' }}</h3>
                            <span class="fz-12 text-muted">{{ translate('Total_Online_Hours') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="mb-0">
                            @if(($viewMode ?? 'daily') === 'summary')
                                {{ translate('Employee_Summary') }}
                            @else
                                {{ translate('Daily_Activity_Breakdown') }}
                            @endif
                        </h4>
                        <span class="text-muted fs-13">{{ translate('Date_Range') }}: {{ $dateFrom }} - {{ $dateTo }}</span>
                    </div>

                    @if(($viewMode ?? 'daily') === 'summary')
                        @if(!empty($employeeTotals))
                            <div class="daily-employee-report-scroll">
                                <table class="table table-hover align-middle daily-employee-report-table">
                                    <thead>
                                        <tr>
                                            <th class="sticky-col" style="min-width: 160px;">{{ translate('Employee') }}</th>
                                            <th title="{{ translate('Active_Days') }}">{{ translate('Active_Days_short') }}</th>
                                            @foreach($metricColumns as $column)
                                                <th title="{{ $column['label'] }}">{{ $column['short'] }}</th>
                                            @endforeach
                                            <th title="{{ translate('Total_Online_Hours') }}">{{ translate('Online_short') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employeeTotals as $row)
                                            <tr>
                                                <td class="sticky-col fw-medium">{{ $row['employee_name'] }}</td>
                                                <td>{{ (int) $row['active_days'] }}</td>
                                                @foreach($metricColumns as $column)
                                                    @php $value = (int) ($row[$column['key']] ?? 0); @endphp
                                                    <td class="{{ $value === 0 ? 'metric-zero' : '' }}">{{ $value }}</td>
                                                @endforeach
                                                <td>{{ $row['online_hours'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td class="sticky-col">{{ translate('Total') }}</td>
                                            <td>—</td>
                                            @foreach($metricColumns as $column)
                                                <td>{{ (int) ($totals[$column['key']] ?? 0) }}</td>
                                            @endforeach
                                            <td>{{ $totals['online_hours'] ?? '0m' }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">{{ translate('No_employees_found') }}</div>
                        @endif
                    @else
                        @if(!empty($rows))
                            <div class="daily-employee-report-scroll">
                                <table class="table table-hover align-middle daily-employee-report-table">
                                    <thead>
                                        <tr>
                                            <th class="sticky-col" style="min-width: 110px;">{{ translate('Date') }}</th>
                                            <th class="sticky-col sticky-col-2" style="min-width: 160px;">{{ translate('Employee') }}</th>
                                            @foreach($metricColumns as $column)
                                                <th title="{{ $column['label'] }}">{{ $column['short'] }}</th>
                                            @endforeach
                                            <th title="{{ translate('Online_Time') }}">{{ translate('Online_short') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rows as $row)
                                            <tr class="{{ empty($row['has_activity']) ? 'table-light' : '' }}">
                                                <td class="sticky-col">{{ $row['date_label'] }}</td>
                                                <td class="sticky-col sticky-col-2 fw-medium">{{ $row['employee_name'] }}</td>
                                                @foreach($metricColumns as $column)
                                                    @php $value = (int) ($row[$column['key']] ?? 0); @endphp
                                                    <td class="{{ $value === 0 ? 'metric-zero' : '' }}">{{ $value }}</td>
                                                @endforeach
                                                <td>{{ $row['online_hours'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">{{ translate('No_employees_found') }}</div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        $(document).ready(function () {
            $('.js-select').select2({
                placeholder: "{{ translate('Select_employee') }}",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush
