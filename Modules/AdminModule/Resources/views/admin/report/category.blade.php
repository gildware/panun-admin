@extends('adminmodule::layouts.new-master')

@section('title', translate('Category_Reports'))

@push('css_or_js')
    <style>
        .cat-report-chart-card { background: #fafbfc; min-height: 100%; }
        .cat-report-donut { height: 220px; min-height: 220px; }
        .cat-report-legend, .cat-report-share-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 14px;
            padding-top: 8px;
        }
        .cat-report-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #5a5c69;
        }
        .cat-report-legend-swatch {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .cat-report-share-scroll { overflow-x: auto; overflow-y: hidden; height: 320px; }
        .cat-report-table-scroll { max-height: 480px; overflow: auto; }
        .cat-report-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8f9fa;
            box-shadow: 0 1px 0 #e9ecef;
            white-space: nowrap;
        }
        .cat-rec-risk { border-left: 4px solid #e74a3b; }
        .cat-rec-opportunity { border-left: 4px solid #4e73df; }
        .cat-rec-grow { border-left: 4px solid #1cc88a; }
        form.report-inline-filters {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 12px;
            overflow-x: auto;
        }
        form.report-inline-filters .report-inline-field {
            display: flex !important;
            flex-direction: row !important;
            align-items: center;
            gap: 6px;
            flex: 1 1 auto;
            min-width: 180px;
        }
        form.report-inline-filters .report-inline-actions {
            display: flex !important;
            flex-direction: row !important;
            flex: 0 0 auto;
            gap: 8px;
            align-items: center;
        }
        form.report-inline-filters .form-label {
            display: inline-block;
            width: auto;
            margin: 0;
            font-size: 12px;
            white-space: nowrap;
        }
        form.report-inline-filters .form-control,
        form.report-inline-filters .select2-container {
            flex: 1 1 auto;
            width: auto !important;
            min-width: 110px;
        }
        form.report-inline-filters .select2-selection--multiple {
            min-height: 38px;
            max-height: 38px;
            overflow: hidden;
        }
    </style>
@endpush

@section('content')
    @php
        $summary = $report['summary'] ?? [];
        $dimensionLabel = $view === 'subcategory' ? translate('Sub_Category') : translate('Category');
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-2">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Category_Reports') }}</h2>
                    <p class="text-muted fz-12 mb-0">{{ translate('Category_Reports_help') }}</p>
                </div>
            </div>

            <ul class="nav nav--tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $view === 'category' ? 'active' : '' }}"
                       href="{{ route('admin.report.category', array_merge($queryParams, ['view' => 'category'])) }}">
                        {{ translate('Category_Wise') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $view === 'subcategory' ? 'active' : '' }}"
                       href="{{ route('admin.report.category', array_merge($queryParams, ['view' => 'subcategory'])) }}">
                        {{ translate('Subcategory_Wise') }}
                    </a>
                </li>
            </ul>

            <form action="{{ route('admin.report.category') }}" method="GET" class="report-inline-filters card border-0 shadow-sm p-3 mb-3">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="report-inline-field">
                    <label class="form-label">{{ translate('From_Date') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>
                <div class="report-inline-field">
                    <label class="form-label">{{ translate('To_Date') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>
                <div class="report-inline-field">
                    <label class="form-label">{{ translate('Zone') }}</label>
                    <select name="zone_ids[]" class="js-select form-select" multiple>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ in_array((string) $zone->id, array_map('strval', $selectedZoneIds), true) ? 'selected' : '' }}>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-inline-field">
                    <label class="form-label">{{ translate('Category') }}</label>
                    <select name="category_ids[]" class="js-select form-select" multiple>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ in_array((string) $category->id, array_map('strval', $selectedCategoryIds), true) ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="report-inline-field">
                    <label class="form-label">{{ translate('Sub_Category') }}</label>
                    <select name="subcategory_ids[]" class="js-select form-select" multiple>
                        @foreach($subcategories as $subcategory)
                            @php($parent = $categoryNames->get((string) $subcategory->parent_id))
                            <option value="{{ $subcategory->id }}" {{ in_array((string) $subcategory->id, array_map('strval', $selectedSubcategoryIds), true) ? 'selected' : '' }}>
                                {{ $parent?->name ? $parent->name.' — ' : '' }}{{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="report-inline-actions">
                    <a href="{{ route('admin.report.category', ['view' => $view]) }}" class="btn btn--secondary">{{ translate('Reset') }}</a>
                    <button type="submit" class="btn btn--primary">{{ translate('Filter') }}</button>
                </div>
            </form>

            <p class="text-muted fz-12 mb-3">{{ translate('Category_provider_counts_note') }} {{ $dateFrom }} — {{ $dateTo }}</p>

            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-primary">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Bookings') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['bookings'] ?? 0 }}</h3>
                            <span class="fz-12">{{ translate('completed') }}: {{ $summary['booking_completed'] ?? 0 }} · {{ translate('Pending') }}: {{ $summary['booking_pending'] ?? 0 }} · {{ translate('Canceled') }}: {{ $summary['booking_cancelled'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-success">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Completed_amount') }}</span>
                            <h3 class="mb-0 mt-1">{{ with_currency_symbol($summary['booking_amount_completed'] ?? 0) }}</h3>
                            <span class="fz-12">{{ translate('completion_rate') }}: {{ $summary['booking_completion_rate'] ?? 0 }}%</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-info">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Leads') }} / {{ translate('Booked') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['leads'] ?? 0 }} / {{ $summary['booked'] ?? 0 }}</h3>
                            <span class="fz-12">{{ translate('Pending') }}: {{ $summary['pending'] ?? 0 }} · {{ translate('Hold') }}: {{ $summary['hold'] ?? 0 }} · {{ translate('Cancelled') }}: {{ $summary['cancelled_leads'] ?? 0 }} · {{ translate('conversion') }}: {{ $summary['lead_conversion_rate'] ?? 0 }}%</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="card h-100 border-start border-4 border-warning">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Providers') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['providers'] ?? 0 }}</h3>
                            <span class="fz-12">{{ translate('Active') }}: {{ $summary['providers_active'] ?? 0 }} · {{ translate('Approved') }}: {{ $summary['providers_approved'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-sm-6">
                    <div class="card h-100 border-start border-4 border-success">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Category_active_with_recent_booking') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['providers_active_with_booking'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-sm-6">
                    <div class="card h-100 border-start border-4 border-danger">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Category_active_with_no_recent_booking') }}</span>
                            <h3 class="mb-0 mt-1">{{ $summary['providers_active_idle'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card h-100 border-start border-4 border-success">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Total_Revenue') }}</span>
                            <h3 class="mb-0 mt-1">{{ with_currency_symbol($summary['revenue'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card h-100 border-start border-4 border-primary">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Admin_Commission') }}</span>
                            <h3 class="mb-0 mt-1">{{ with_currency_symbol($summary['admin_commission'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="card h-100 border-start border-4 border-info">
                        <div class="card-body py-3">
                            <span class="fz-12 text-muted">{{ translate('Provider_Net_Income') }}</span>
                            <h3 class="mb-0 mt-1">{{ with_currency_symbol($summary['provider_earning'] ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($slice['insights']))
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <p class="fw-semibold mb-2">{{ translate('Business_Insights_Summary') }}</p>
                        @foreach($slice['insights'] as $insight)
                            <p class="mb-1 fz-13 {{ ($insight['type'] ?? '') === 'warning' ? 'text-warning' : ((($insight['type'] ?? '') === 'success') ? 'text-success' : 'text-muted') }}">
                                {{ $insight['text'] }}
                            </p>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-3">{{ translate('Overview') }}</p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="card cat-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Lead_types') }}</div>
                                    <div id="cat-lead-type-chart" class="cat-report-donut"></div>
                                    <div id="cat-lead-type-legend" class="cat-report-legend"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card cat-report-chart-card border">
                                <div class="card-body">
                                    <div class="fz-12 text-muted mb-2">{{ translate('Booking_status') }}</div>
                                    <div id="cat-booking-status-chart" class="cat-report-donut"></div>
                                    <div id="cat-booking-status-legend" class="cat-report-legend"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ $dimensionLabel }} {{ translate('Bookings') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Category_booking_share_help') }}</p>
                    <div id="cat-booking-share-legend" class="cat-report-share-legend"></div>
                    <div class="cat-report-share-scroll">
                        <div id="cat-booking-share-chart"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ translate('Providers') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Category_provider_supply_help') }}</p>
                    <div id="cat-provider-share-legend" class="cat-report-share-legend"></div>
                    <div class="cat-report-share-scroll">
                        <div id="cat-provider-share-chart"></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ translate('Date_Wise') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Category_date_wise_help') }}</p>
                    <div id="cat-daily-bar"></div>
                    @if(!empty($splitDaily))
                        <div class="mt-4">
                            <div class="fz-12 text-muted mb-2">{{ translate('Bookings') }} · {{ $dimensionLabel }}</div>
                            <div id="cat-daily-bookings-legend" class="cat-report-share-legend"></div>
                            <div id="cat-daily-bookings-by-dim"></div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <div>
                            <p class="fw-semibold mb-1">{{ $dimensionLabel }} · {{ translate('Leads') }}</p>
                            <p class="text-muted fz-12 mb-0">{{ translate('Category_lead_analysis_help') }}</p>
                        </div>
                        <button type="button" class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1" data-cat-export="cat-lead-table" data-cat-export-name="{{ $view }}-leads-{{ $dateFrom }}-to-{{ $dateTo }}">
                            <span class="material-icons" style="font-size:18px">file_download</span>
                            {{ translate('Excel') }}
                        </button>
                    </div>
                    <div class="table-responsive cat-report-table-scroll mt-3">
                        <table id="cat-lead-table" class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                @if($view === 'subcategory')
                                    <th>{{ translate('Category') }}</th>
                                @endif
                                <th>{{ $dimensionLabel }}</th>
                                <th class="text-end">{{ translate('Leads') }}</th>
                                <th class="text-end">{{ translate('Unknown') }}</th>
                                <th class="text-end">{{ translate('Customer') }}</th>
                                <th class="text-end">{{ translate('Provider') }}</th>
                                <th class="text-end">{{ translate('Invalid') }}</th>
                                <th class="text-end">{{ translate('Future_Customer') }}</th>
                                <th class="text-end">{{ translate('Pending') }}</th>
                                <th class="text-end">{{ translate('Hold') }}</th>
                                <th class="text-end">{{ translate('Booked') }}</th>
                                <th class="text-end">{{ translate('Cancelled') }}</th>
                                <th class="text-end">{{ translate('cancellation_rate') }} %</th>
                                <th class="text-end">{{ translate('conversion') }} %</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($slice['rows'] ?? [] as $row)
                                <tr>
                                    @if($view === 'subcategory')
                                        <td>{{ $row['category_label'] ?? '—' }}</td>
                                    @endif
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['leads'] }}</td>
                                    <td class="text-end">{{ $row['unknown'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['customer'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['provider'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['invalid'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['future_customer'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['pending'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['hold'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['booked'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['cancelled'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['lead_cancel_rate'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['lead_conversion_rate'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $view === 'subcategory' ? 14 : 13 }}" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <div>
                            <p class="fw-semibold mb-1">{{ $dimensionLabel }} · {{ translate('Bookings') }}</p>
                            <p class="text-muted fz-12 mb-0">{{ translate('Category_booking_analysis_help') }}</p>
                        </div>
                        <button type="button" class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1" data-cat-export="cat-booking-table" data-cat-export-name="{{ $view }}-bookings-{{ $dateFrom }}-to-{{ $dateTo }}">
                            <span class="material-icons" style="font-size:18px">file_download</span>
                            {{ translate('Excel') }}
                        </button>
                    </div>
                    <div class="table-responsive cat-report-table-scroll mt-3">
                        <table id="cat-booking-table" class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                @if($view === 'subcategory')
                                    <th>{{ translate('Category') }}</th>
                                @endif
                                <th>{{ $dimensionLabel }}</th>
                                <th class="text-end">{{ translate('Bookings') }}</th>
                                <th class="text-end">{{ translate('Pending') }}</th>
                                <th class="text-end">{{ translate('Accepted') }}</th>
                                <th class="text-end">{{ translate('Ongoing') }}</th>
                                <th class="text-end">{{ translate('On_hold') }}</th>
                                <th class="text-end">{{ translate('Pending_cancellation') }}</th>
                                <th class="text-end">{{ translate('completed') }}</th>
                                <th class="text-end">{{ translate('Canceled') }}</th>
                                <th class="text-end">{{ translate('Refunded') }}</th>
                                <th class="text-end">{{ translate('Other') }}</th>
                                <th class="text-end">{{ translate('Completed_amount') }}</th>
                                <th class="text-end">{{ translate('Total_Revenue') }}</th>
                                <th class="text-end">{{ translate('Admin_Commission') }}</th>
                                <th class="text-end">{{ translate('Provider_Net_Income') }}</th>
                                <th class="text-end">{{ translate('completion_rate') }} %</th>
                                <th class="text-end">{{ translate('cancellation_rate') }} %</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($slice['rows'] ?? [] as $row)
                                <tr>
                                    @if($view === 'subcategory')
                                        <td>{{ $row['category_label'] ?? '—' }}</td>
                                    @endif
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['bookings'] }}</td>
                                    <td class="text-end">{{ $row['status_pending'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_accepted'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_ongoing'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_on_hold'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_pending_cancellation'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_completed'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_canceled'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_refunded'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['status_other'] ?? 0 }}</td>
                                    <td class="text-end">{{ with_currency_symbol($row['booking_amount_completed'] ?? 0) }}</td>
                                    <td class="text-end">{{ with_currency_symbol($row['revenue'] ?? 0) }}</td>
                                    <td class="text-end">{{ with_currency_symbol($row['admin_commission'] ?? 0) }}</td>
                                    <td class="text-end">{{ with_currency_symbol($row['provider_earning'] ?? 0) }}</td>
                                    <td class="text-end">{{ $row['booking_completion_rate'] ?? 0 }}</td>
                                    <td class="text-end">{{ $row['booking_cancel_rate'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $view === 'subcategory' ? 18 : 17 }}" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <div>
                            <p class="fw-semibold mb-1">{{ translate('Category_why_cancelled') }}</p>
                            <p class="text-muted fz-12 mb-0">{{ translate('Category_cancel_reason_help') }}</p>
                        </div>
                        <button type="button" class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1" data-cat-export="cat-cancel-table" data-cat-export-name="{{ $view }}-cancellations-{{ $dateFrom }}-to-{{ $dateTo }}">
                            <span class="material-icons" style="font-size:18px">file_download</span>
                            {{ translate('Excel') }}
                        </button>
                    </div>
                    <div class="table-responsive cat-report-table-scroll mt-3">
                        <table id="cat-cancel-table" class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                @if($view === 'subcategory')
                                    <th>{{ translate('Category') }}</th>
                                @endif
                                <th>{{ $dimensionLabel }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Cancellation_reason') }}</th>
                                <th>{{ translate('Responsible') }}</th>
                                <th class="text-end">{{ translate('Count') }}</th>
                                <th class="text-end">%</th>
                                <th>{{ translate('Cancellation_Remarks') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php($cancelRows = 0)
                            @foreach($slice['rows'] ?? [] as $row)
                                @foreach(['lead_cancel_reasons', 'booking_cancel_reasons'] as $reasonField)
                                    @foreach($row[$reasonField] ?? [] as $reason)
                                        @php($cancelRows++)
                                        <tr>
                                            @if($view === 'subcategory')
                                                <td>{{ $row['category_label'] ?? '—' }}</td>
                                            @endif
                                            <td>{{ $row['label'] }}</td>
                                            <td>
                                                @if(($reason['source'] ?? '') === 'provider_lead')
                                                    {{ translate('Provider') }} {{ translate('Leads') }}
                                                @elseif(($reason['source'] ?? '') === 'customer_lead')
                                                    {{ translate('Customer') }} {{ translate('Leads') }}
                                                @else
                                                    {{ translate('Bookings') }}
                                                @endif
                                            </td>
                                            <td>{{ $reason['label'] ?? '—' }}</td>
                                            <td>
                                                @switch($reason['responsible'] ?? '')
                                                    @case('customer') {{ translate('Customer') }} @break
                                                    @case('provider') {{ translate('Provider') }} @break
                                                    @case('staff') {{ translate('Staff') }} @break
                                                    @case('no_one') {{ translate('No_one') }} @break
                                                    @default —
                                                @endswitch
                                            </td>
                                            <td class="text-end">{{ $reason['total'] ?? 0 }}</td>
                                            <td class="text-end">{{ $reason['share'] ?? 0 }}</td>
                                            <td>{{ ($reason['remarks_text'] ?? '') !== '' ? $reason['remarks_text'] : '—' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @endforeach
                            @if($cancelRows === 0)
                                <tr>
                                    <td colspan="{{ $view === 'subcategory' ? 8 : 7 }}" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-1">
                        <div>
                            <p class="fw-semibold mb-1">{{ translate('Providers') }}</p>
                            <p class="text-muted fz-12 mb-0">{{ translate('Category_provider_table_help') }}</p>
                        </div>
                        <button type="button" class="btn btn--secondary btn-sm d-inline-flex align-items-center gap-1" data-cat-export="cat-provider-table" data-cat-export-name="{{ $view }}-providers-{{ $dateFrom }}-to-{{ $dateTo }}">
                            <span class="material-icons" style="font-size:18px">file_download</span>
                            {{ translate('Excel') }}
                        </button>
                    </div>
                    <div class="table-responsive cat-report-table-scroll mt-3">
                        <table id="cat-provider-table" class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Category') }}</th>
                                <th>{{ translate('Sub_Category') }}</th>
                                <th>{{ translate('Provider') }}</th>
                                <th class="text-end">{{ translate('Active') }}</th>
                                <th class="text-end">{{ translate('Approved') }}</th>
                                <th class="text-end">{{ translate('Subscribed') }}</th>
                                <th class="text-end">{{ translate('Bookings') }}</th>
                                <th class="text-end">{{ translate('completed') }}</th>
                                <th class="text-end">{{ translate('Pending') }}</th>
                                <th class="text-end">{{ translate('Canceled') }}</th>
                                <th class="text-end">{{ translate('Completed_amount') }}</th>
                                <th class="text-end">{{ translate('Total_Revenue') }}</th>
                                <th class="text-end">{{ translate('Admin_Commission') }}</th>
                                <th class="text-end">{{ translate('Provider_Net_Income') }}</th>
                                <th class="text-end">{{ translate('Took_a_booking') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($slice['providers'] ?? [] as $person)
                                <tr>
                                    <td>{{ $person['category_label'] ?? '—' }}</td>
                                    <td>{{ $view === 'subcategory' ? ($person['subcategory_label'] ?: '—') : ($person['subcategories'] ?? '—') }}</td>
                                    <td>
                                        @if(!empty($person['provider_id']))
                                            <a href="{{ route('admin.provider.details', $person['provider_id']) }}">{{ $person['name'] }}</a>
                                        @else
                                            {{ $person['name'] }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ !empty($person['is_active']) ? translate('Yes') : translate('No') }}</td>
                                    <td class="text-end">{{ !empty($person['is_approved']) ? translate('Yes') : translate('No') }}</td>
                                    <td class="text-end">{{ !empty($person['subscribed']) ? translate('Yes') : translate('No') }}</td>
                                    <td class="text-end">{{ $person['bookings'] }}</td>
                                    <td class="text-end">{{ $person['booking_completed'] }}</td>
                                    <td class="text-end">{{ $person['booking_pending'] }}</td>
                                    <td class="text-end">{{ $person['booking_cancelled'] }}</td>
                                    <td class="text-end">{{ with_currency_symbol($person['booking_amount_completed'] ?? 0) }}</td>
                                    <td class="text-end">{{ with_currency_symbol($person['revenue'] ?? 0) }}</td>
                                    <td class="text-end">{{ with_currency_symbol($person['admin_commission'] ?? 0) }}</td>
                                    <td class="text-end">{{ with_currency_symbol($person['provider_earning'] ?? 0) }}</td>
                                    <td class="text-end">{{ !empty($person['took_booking']) ? translate('Yes') : translate('No') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <p class="fw-semibold mb-1">{{ translate('Where_to_target') }}</p>
                    <p class="text-muted fz-12 mb-3">{{ translate('Category_targeting_help') }}</p>
                    @forelse($slice['targeting'] ?? [] as $row)
                        <div class="p-3 mb-2 bg-light rounded cat-rec-{{ $row['recommendation']['type'] ?? 'opportunity' }}">
                            <div class="fw-semibold fz-13">{{ $row['label'] }}</div>
                            <div class="fz-13">{{ $row['recommendation']['text'] }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ translate('Category_targeting_empty') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var report = {!! json_encode($report) !!};
            var slice = {!! json_encode($slice) !!};
            var view = @json($view);
            var noData = @json(translate('Data_not_available'));
            var palette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#fd7e14', '#6f42c1', '#20c997', '#0dcaf0'];
            var rows = (slice && slice.rows) || [];

            function sum(values) {
                return (values || []).reduce(function (a, b) { return a + (b || 0); }, 0);
            }

            function showEmpty(el) {
                if (!el) return;
                el.innerHTML = '<div class="text-muted text-center py-5 fz-12">' + noData + '</div>';
            }

            function bindChart(el, options) {
                if (!el) return;
                el.innerHTML = '';
                new ApexCharts(el, options).render();
            }

            function fillLegend(legendEl, items) {
                if (!legendEl) return;
                legendEl.innerHTML = '';
                (items || []).forEach(function (item, i) {
                    var node = document.createElement('span');
                    node.className = 'cat-report-legend-item';
                    var swatch = document.createElement('span');
                    swatch.className = 'cat-report-legend-swatch';
                    swatch.style.background = item.color || palette[i % palette.length];
                    node.appendChild(swatch);
                    node.appendChild(document.createTextNode(item.label || item.name || '—'));
                    legendEl.appendChild(node);
                });
            }

            function renderDonut(el, legendEl, chartRows, centerLabel) {
                chartRows = (chartRows || []).filter(function (r) { return (r.total || 0) > 0; });
                if (!chartRows.length) {
                    showEmpty(el);
                    if (legendEl) legendEl.innerHTML = '';
                    return;
                }
                var values = chartRows.map(function (r) { return r.total; });
                bindChart(el, {
                    series: values,
                    chart: { type: 'donut', height: 220, fontFamily: 'inherit', toolbar: { show: false } },
                    labels: chartRows.map(function (r) { return r.label; }),
                    colors: chartRows.map(function (r, i) { return r.color || palette[i % palette.length]; }),
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    stroke: { width: 2, colors: ['#fff'] },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '62%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: centerLabel,
                                        formatter: function () { return String(sum(values)); }
                                    }
                                }
                            }
                        }
                    }
                });
                fillLegend(legendEl, chartRows.map(function (r) {
                    return { label: (r.label || '—') + ' (' + r.total + ')', color: r.color };
                }));
            }

            function renderStacked(el, legendEl, chartRows, seriesDefs, height) {
                chartRows = chartRows || [];
                seriesDefs = (seriesDefs || []).filter(function (s) {
                    return chartRows.some(function (r) { return (r[s.key] || 0) > 0; });
                });
                if (!chartRows.length || !seriesDefs.length) {
                    showEmpty(el);
                    if (legendEl) legendEl.innerHTML = '';
                    return;
                }
                var parentWidth = (el.parentElement && el.parentElement.clientWidth) ? el.parentElement.clientWidth : 640;
                var chartWidth = Math.max(parentWidth, chartRows.length * 88);
                el.style.width = chartWidth + 'px';
                fillLegend(legendEl, seriesDefs.map(function (s) { return { label: s.name, color: s.color }; }));
                bindChart(el, {
                    chart: { type: 'bar', height: height || 300, width: chartWidth, stacked: true, fontFamily: 'inherit', toolbar: { show: false } },
                    series: seriesDefs.map(function (s) {
                        return { name: s.name, data: chartRows.map(function (r) { return r[s.key] || 0; }) };
                    }),
                    xaxis: { categories: chartRows.map(function (r) { return r.label || '—'; }), labels: { rotate: -35, trim: true } },
                    colors: seriesDefs.map(function (s) { return s.color; }),
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
                    legend: { show: false }
                });
            }

            renderDonut(document.querySelector('#cat-lead-type-chart'), document.querySelector('#cat-lead-type-legend'), report.lead_type_breakdown || [], @json(translate('Leads')));
            renderDonut(document.querySelector('#cat-booking-status-chart'), document.querySelector('#cat-booking-status-legend'), report.booking_status_breakdown || [], @json(translate('Bookings')));

            var shareRows = rows.filter(function (r) {
                return (r.bookings || 0) + (r.providers || 0) + (r.leads || 0) > 0;
            });
            renderStacked(document.querySelector('#cat-booking-share-chart'), document.querySelector('#cat-booking-share-legend'), shareRows.filter(function (r) { return (r.bookings || 0) > 0; }), [
                { key: 'booking_completed', name: @json(translate('completed')), color: '#1cc88a' },
                { key: 'booking_pending', name: @json(translate('Pending')), color: '#f6c23e' },
                { key: 'booking_cancelled', name: @json(translate('Canceled')), color: '#e74a3b' }
            ]);
            renderStacked(document.querySelector('#cat-provider-share-chart'), document.querySelector('#cat-provider-share-legend'), shareRows.map(function (r) {
                return {
                    label: r.label,
                    working: r.providers_active_with_booking || 0,
                    idle: r.providers_active_idle || 0,
                    inactive: Math.max(0, (r.providers || 0) - (r.providers_active || 0))
                };
            }).filter(function (r) { return r.working + r.idle + r.inactive > 0; }), [
                { key: 'working', name: @json(translate('Category_active_with_recent_booking')), color: '#1cc88a' },
                { key: 'idle', name: @json(translate('Category_active_with_no_recent_booking')), color: '#f6c23e' },
                { key: 'inactive', name: @json(translate('Inactive')), color: '#858796' }
            ]);

            var daily = report.daily || {};
            var dailyEl = document.querySelector('#cat-daily-bar');
            if (dailyEl) {
                if (!sum(daily.leads) && !sum(daily.bookings)) {
                    showEmpty(dailyEl);
                } else {
                    bindChart(dailyEl, {
                        chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                        series: [
                            { name: @json(translate('Leads')), data: daily.leads || [] },
                            { name: @json(translate('Bookings')), data: daily.bookings || [] }
                        ],
                        xaxis: { categories: daily.labels || [] },
                        colors: ['#4e73df', '#1cc88a'],
                        dataLabels: { enabled: false },
                        plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
                        legend: { position: 'top' }
                    });
                }
            }

            var splitEl = document.querySelector('#cat-daily-bookings-by-dim');
            if (splitEl) {
                var seriesKey = view === 'subcategory' ? 'by_subcategory' : 'by_category';
                var seriesRows = ((daily[seriesKey] || {}).booking_series || []).filter(function (row) { return sum(row.data) > 0; });
                if (!seriesRows.length) {
                    showEmpty(splitEl);
                } else {
                    fillLegend(document.querySelector('#cat-daily-bookings-legend'), seriesRows.map(function (row, i) {
                        return { label: row.label, color: palette[i % palette.length] };
                    }));
                    bindChart(splitEl, {
                        chart: { type: 'bar', height: 360, stacked: true, fontFamily: 'inherit', toolbar: { show: false } },
                        series: seriesRows.map(function (row) { return { name: row.label, data: row.data || [] }; }),
                        xaxis: { categories: daily.labels || [] },
                        colors: palette,
                        dataLabels: { enabled: false },
                        plotOptions: { bar: { columnWidth: '60%', borderRadius: 1 } },
                        legend: { show: false }
                    });
                }
            }

            $(document).off('click.catReportExport').on('click.catReportExport', '[data-cat-export]', function () {
                var table = document.getElementById(this.getAttribute('data-cat-export'));
                if (!table) return;
                var lines = [];
                table.querySelectorAll('tr').forEach(function (tr) {
                    var cells = [];
                    tr.querySelectorAll('th,td').forEach(function (cell) {
                        cells.push('"' + String(cell.innerText || '').replace(/"/g, '""').trim() + '"');
                    });
                    if (cells.length) lines.push(cells.join(','));
                });
                var blob = new Blob(['\ufeff' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = (this.getAttribute('data-cat-export-name') || 'category-report') + '.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            });

        })();
    </script>
@endpush
