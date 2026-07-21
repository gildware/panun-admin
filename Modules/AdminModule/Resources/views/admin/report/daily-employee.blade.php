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
            min-width: 1280px;
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
            background: #f1f3f5;
            font-weight: 700;
            border-top: 2px solid #dee2e6;
        }

        .daily-employee-report-table tfoot td.sticky-col,
        .daily-employee-report-table tfoot td.sticky-col-2 {
            background: #f1f3f5;
            z-index: 5;
        }

        .daily-employee-report-table tfoot td.sticky-col-2 {
            left: 110px;
            position: sticky;
            text-align: left;
            box-shadow: 1px 0 0 #e9ecef;
        }

        .daily-employee-report-table a.employee-day-link {
            color: inherit;
            text-decoration: none;
            border-bottom: 1px dashed #6c757d;
        }

        .daily-employee-report-table a.employee-day-link:hover {
            color: var(--bs-primary, #0d6efd);
            border-bottom-color: currentColor;
        }

        .metric-group-leads { background: #f0f7ff !important; }
        .metric-group-whatsapp { background: #f3faf3 !important; }
        .metric-group-bookings { background: #fff8f0 !important; }

        .daily-employee-date-range {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 13px;
            color: #6c757d;
        }

        .daily-employee-date-range label {
            margin: 0;
            white-space: nowrap;
        }

        .daily-employee-date-range input[type="date"] {
            width: auto;
            min-width: 145px;
            height: 38px;
            padding: 0.25rem 0.5rem;
            font-size: 13px;
        }

        .daily-employee-date-range .date-sep {
            color: #adb5bd;
        }
    </style>
@endpush

@section('content')
    @php
        $metricColumns = $metricColumns ?? [];
        $totals = $totals ?? [];
        $allDetailUrl = ($dateFrom === $dateTo)
            ? route('admin.report.daily-employee.detail', ['date' => $dateFrom])
            : null;
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

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="mb-0">{{ translate('Daily_Activity_Breakdown') }}</h4>
                        <form action="{{ route('admin.report.daily-employee') }}" method="GET" id="daily-employee-date-range-form" class="daily-employee-date-range mb-0">
                            <label for="daily-employee-date-from">{{ translate('Date_Range') }}</label>
                            <input type="date"
                                   id="daily-employee-date-from"
                                   name="date_from"
                                   class="form-control"
                                   value="{{ $dateFrom }}">
                            <span class="date-sep">–</span>
                            <input type="date"
                                   id="daily-employee-date-to"
                                   name="date_to"
                                   class="form-control"
                                   value="{{ $dateTo }}">
                        </form>
                    </div>

                    @if(!empty($rows))
                        <div class="daily-employee-report-scroll">
                            <table class="table table-hover align-middle daily-employee-report-table">
                                <thead>
                                    <tr>
                                        <th class="sticky-col" style="min-width: 110px;">{{ translate('Date') }}</th>
                                        <th class="sticky-col sticky-col-2" style="min-width: 160px;">{{ translate('Employee') }}</th>
                                        @foreach($metricColumns as $column)
                                            <th class="metric-group-{{ $column['group'] ?? 'other' }}" title="{{ $column['label'] }}">{{ $column['short'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        @php
                                            $detailUrl = route('admin.report.daily-employee.detail', [
                                                'date' => $row['date'],
                                                'employee_ids' => [$row['employee_id']],
                                            ]);
                                        @endphp
                                        <tr class="{{ empty($row['has_activity']) ? 'table-light' : '' }}">
                                            <td class="sticky-col">{{ $row['date_label'] }}</td>
                                            <td class="sticky-col sticky-col-2 fw-medium">
                                                <a class="employee-day-link" href="{{ $detailUrl }}" title="{{ translate('View_Day_Detail') }}">
                                                    {{ $row['employee_name'] }}
                                                </a>
                                            </td>
                                            @foreach($metricColumns as $column)
                                                @php $value = (int) ($row[$column['key']] ?? 0); @endphp
                                                <td class="{{ $value === 0 ? 'metric-zero' : '' }}">{{ $value }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="sticky-col">{{ $dateFrom === $dateTo ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : '—' }}</td>
                                        <td class="sticky-col sticky-col-2">
                                            @if($allDetailUrl)
                                                <a class="employee-day-link" href="{{ $allDetailUrl }}" title="{{ translate('View_Day_Detail') }}">
                                                    {{ translate('All') }}
                                                </a>
                                            @else
                                                {{ translate('All') }}
                                            @endif
                                        </td>
                                        @foreach($metricColumns as $column)
                                            @php $value = (int) ($totals[$column['key']] ?? 0); @endphp
                                            <td class="{{ $value === 0 ? 'metric-zero' : '' }}">{{ $value }}</td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">{{ translate('No_employees_found') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        (function () {
            var form = document.getElementById('daily-employee-date-range-form');
            if (!form) {
                return;
            }
            form.querySelectorAll('input[type="date"]').forEach(function (input) {
                input.addEventListener('change', function () {
                    form.submit();
                });
            });
        })();
    </script>
@endpush
