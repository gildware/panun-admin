@extends('adminmodule::layouts.new-master')

@section('title', translate('Export_Leads'))

@push('css_or_js')
    <style>
        .lead-export-page .main-content {
            min-height: calc(100vh - 4.5rem);
        }
        .lead-export-wrap {
            max-width: 1120px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-export-head {
            text-align: center;
            margin-bottom: 12px;
        }
        .lead-export-head .page-title {
            margin-bottom: 4px;
        }
        .lead-wizard {
            display: flex;
            align-items: stretch;
            justify-content: center;
            gap: 0;
            margin: 0 auto 14px;
            padding: 0;
            list-style: none;
        }
        .lead-wizard li {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-width: 0;
            padding: 8px 10px;
            background: #fff;
            border: 1px solid #e6e8ef;
            color: #98a2b3;
            text-align: center;
        }
        .lead-wizard li + li {
            border-left: 0;
        }
        .lead-wizard li:first-child {
            border-radius: 14px 0 0 14px;
        }
        .lead-wizard li:last-child {
            border-radius: 0 14px 14px 0;
        }
        .lead-wizard a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: inherit;
            text-decoration: none;
            min-width: 0;
            width: 100%;
        }
        .lead-wizard .num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            background: #f2f4f7;
            color: #98a2b3;
            flex-shrink: 0;
        }
        .lead-wizard strong {
            display: block;
            font-size: 14px;
            line-height: 1.2;
        }
        .lead-wizard small {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-top: 2px;
        }
        .lead-wizard li.is-current {
            background: #f5f7ff;
            border-color: #c9d4fb;
            color: #24356b;
        }
        .lead-wizard li.is-current .num,
        .lead-wizard li.is-done .num {
            background: #4158d0;
            color: #fff;
        }
        .lead-wizard li.is-done {
            color: #344054;
        }
        .lead-export-card {
            background: #fff;
            border: 1px solid #e6e8ef;
            border-radius: 16px;
            padding: 18px 18px 14px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-export-card h2 {
            font-size: 22px;
            margin-bottom: 4px;
        }
        .lead-export-narrow {
            max-width: 720px;
        }
        .lead-section-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #667085;
            margin: 10px 0 8px;
            text-align: center;
        }
        .lead-filter-grid .form-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .lead-filter-grid .form-control,
        .lead-filter-grid .form-select {
            min-height: 36px;
            font-size: 13px;
        }
        .lead-report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        .lead-report-card {
            display: block;
            margin: 0;
            cursor: pointer;
        }
        .lead-report-card input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .lead-report-card span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 8px 10px;
            border: 1px solid #e6e8ef;
            border-radius: 12px;
            background: #fff;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.3;
            text-align: center;
        }
        .lead-report-card input:checked + span {
            border-color: #4158d0;
            background: #f5f7ff;
            box-shadow: inset 0 0 0 1px #4158d0;
        }
        .lead-report-card:hover span {
            border-color: #b9c6f5;
        }
        .lead-export-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #eef0f4;
        }
        .lead-step-intro {
            text-align: center;
            margin-bottom: 18px;
        }
        #lead-export-column-search {
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-column-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }
        .lead-column-group {
            border: 1px solid #e6e8ef;
            border-radius: 12px;
            padding: 14px 14px 6px;
            background: #fafbfe;
        }
        .lead-column-group h3 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #667085;
            margin-bottom: 10px;
        }
        .lead-export-option {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            cursor: pointer;
        }
        .lead-preview-table thead th {
            position: sticky;
            top: 0;
            background: #f6f8fc;
            white-space: nowrap;
            z-index: 1;
        }
        .lead-preview-table td {
            white-space: nowrap;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lead-preview-scroll {
            overflow: auto;
            max-height: calc(100vh - 320px);
            border: 1px solid #e6e8ef;
            border-radius: 12px;
        }
    </style>
@endpush

@section('content')
    <script>document.body.classList.add('lead-export-page');</script>
    @php
        $stepTitles = [
            1 => 'Choose the report type',
            2 => 'Set the filters for this report',
            3 => 'Choose the columns for the file',
            4 => 'Preview the leads, then download',
        ];
        $stepOneQuery = array_merge($sharedQuery, ['step' => 1]);
        $stepTwoQuery = array_merge($sharedQuery, ['step' => 2]);
        $stepThreeQuery = array_merge($sharedQuery, ['step' => 3, 'column_selection' => 1, 'columns' => $selectedKeys]);
    @endphp
    <div class="main-content">
            <div class="container-fluid lead-export-wrap">
            <div class="lead-export-head">
                <h2 class="page-title mb-1">{{ translate('Export_Leads') }}</h2>
                <p class="mb-2 text-muted">{{ $stepTitles[$step] ?? '' }}</p>
                <a href="{{ $backUrl }}" class="btn btn--secondary btn-sm" data-turbo="false">{{ translate('Back_to_report') }}</a>
            </div>

            <ol class="lead-wizard">
                <li class="{{ $step === 1 ? 'is-current' : 'is-done' }}">
                    @if($step > 1)
                        <a href="{{ route('admin.lead.reports.inbound.export', $stepOneQuery) }}" data-turbo="false">
                    @endif
                        <span class="num">1</span>
                        <span>
                            <strong>Report</strong>
                            <small>Which leads</small>
                        </span>
                    @if($step > 1)
                        </a>
                    @endif
                </li>
                <li class="{{ $step === 2 ? 'is-current' : ($step > 2 ? 'is-done' : '') }}">
                    @if($step > 2)
                        <a href="{{ route('admin.lead.reports.inbound.export', $stepTwoQuery) }}" data-turbo="false">
                    @endif
                        <span class="num">2</span>
                        <span>
                            <strong>{{ translate('Filter') }}</strong>
                            <small>For this report</small>
                        </span>
                    @if($step > 2)
                        </a>
                    @endif
                </li>
                <li class="{{ $step === 3 ? 'is-current' : ($step > 3 ? 'is-done' : '') }}">
                    @if($step > 3)
                        <a href="{{ route('admin.lead.reports.inbound.export', $stepThreeQuery) }}" data-turbo="false">
                    @endif
                        <span class="num">3</span>
                        <span>
                            <strong>{{ translate('Choose_columns') }}</strong>
                            <small>What goes in the file</small>
                        </span>
                    @if($step > 3)
                        </a>
                    @endif
                </li>
                <li class="{{ $step === 4 ? 'is-current' : '' }}">
                    <span class="num">4</span>
                    <span>
                        <strong>{{ translate('Preview') }}</strong>
                        <small>Check, then download</small>
                    </span>
                </li>
            </ol>

            @if($step === 1)
                <form method="GET" action="{{ route('admin.lead.reports.inbound.export') }}" class="lead-export-card lead-export-narrow" data-turbo="false" id="lead-export-step-form">
                    <input type="hidden" name="step" value="2">
                    @foreach($sharedQuery as $name => $value)
                        @if($name === 'inbound_report')
                            @continue
                        @endif
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <div class="lead-section-label">Report</div>
                    <div class="lead-report-grid">
                        @foreach($reportLabels as $reportKey => $reportLabel)
                            <label class="lead-report-card">
                                <input type="radio" name="inbound_report" value="{{ $reportKey }}" {{ $inboundReport === $reportKey ? 'checked' : '' }}>
                                <span>{{ $reportLabel }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="lead-export-actions">
                        <button type="submit" class="btn btn--primary">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 2)
                <form method="GET" action="{{ route('admin.lead.reports.inbound.export') }}" class="lead-export-card" data-turbo="false" id="lead-export-step-form">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="inbound_report" value="{{ $inboundReport }}">

                    <p class="text-muted mb-0 text-center">{{ $reportLabels[$inboundReport] ?? '' }}</p>
                    <div class="lead-section-label">{{ translate('Filter') }}</div>
                    <div class="row g-2 lead-filter-grid">
                        <div class="col-md-6">
                            <label class="form-label" for="lead-export-search">{{ translate('Search') }}</label>
                            <input type="text" id="lead-export-search" name="search" class="form-control" value="{{ $search }}" placeholder="{{ translate('Search_by_name_phone_or_lead_id') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="lead-export-from">{{ translate('From_Date') }}</label>
                            <input type="date" id="lead-export-from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="lead-export-to">{{ translate('To_Date') }}</label>
                            <input type="date" id="lead-export-to" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Handled_By') }}</label>
                            <select name="handled_by_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['employees'] as $employee)
                                    @php
                                        $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                        $label = $fullName !== '' ? $fullName : $employee->email;
                                    @endphp
                                    <option value="{{ $employee->id }}" {{ in_array((string) $employee->id, $selectedHandledByIds, true) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Source') }}</label>
                            <select name="source_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['sources'] as $source)
                                    <option value="{{ $source->id }}" {{ in_array((string) $source->id, $selectedSourceIds, true) ? 'selected' : '' }}>{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Ad_Source') }}</label>
                            <select name="ad_source_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['adSources'] as $adSource)
                                    <option value="{{ $adSource->id }}" {{ in_array((string) $adSource->id, $selectedAdSourceIds, true) ? 'selected' : '' }}>{{ $adSource->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="lead-export-open">{{ translate('Lead_Status') }}</label>
                            <select id="lead-export-open" name="open_status" class="form-select">
                                <option value="all" {{ $openStatus === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                                <option value="open" {{ $openStatus === 'open' ? 'selected' : '' }}>{{ translate('Open') }}</option>
                                <option value="closed" {{ $openStatus === 'closed' ? 'selected' : '' }}>{{ translate('Closed') }}</option>
                            </select>
                        </div>
                        @if(in_array($inboundReport, ['general', 'customer', 'provider'], true))
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Lead_Temperature') }}</label>
                            <select name="lead_temperature[]" class="form-select js-select" multiple>
                                @foreach(\Modules\LeadManagement\Entities\Lead::leadTemperatures() as $value => $temperatureLabel)
                                    <option value="{{ $value }}" {{ in_array($value, $selectedTemperatures, true) ? 'selected' : '' }}>{{ translate($temperatureLabel) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if($inboundReport === 'customer')
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Customer_Status') }}</label>
                            <select name="customer_status_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['customerStatuses'] as $status)
                                    <option value="{{ $status->id }}" {{ in_array((string) $status->id, $selectedCustomerStatusIds, true) ? 'selected' : '' }}>{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if($inboundReport === 'provider')
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Provider_Status') }}</label>
                            <select name="provider_status_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['providerStatuses'] as $status)
                                    <option value="{{ $status->id }}" {{ in_array((string) $status->id, $selectedProviderStatusIds, true) ? 'selected' : '' }}>{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if($inboundReport === 'future_customer')
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Future_Customer_Reason') }}</label>
                            <select name="future_customer_reason_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['futureReasons'] as $reason)
                                    <option value="{{ $reason->id }}" {{ in_array((string) $reason->id, $selectedFutureReasonIds, true) ? 'selected' : '' }}>{{ $reason->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if($inboundReport === 'invalid')
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Invalid_Reason') }}</label>
                            <select name="invalid_reason_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['invalidReasons'] as $reason)
                                    <option value="{{ $reason->id }}" {{ in_array((string) $reason->id, $selectedInvalidReasonIds, true) ? 'selected' : '' }}>{{ $reason->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Category') }}</label>
                            <select name="category_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['categories'] as $category)
                                    <option value="{{ $category->id }}" {{ in_array((string) $category->id, $selectedCategoryIds, true) ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Sub_Category') }}</label>
                            <select name="subcategory_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['subcategories'] as $subcategory)
                                    @php
                                        $parentName = trim((string) ($subcategory->parent->name ?? ''));
                                        $subLabel = $parentName !== '' ? $parentName.' — '.$subcategory->name : $subcategory->name;
                                    @endphp
                                    <option value="{{ $subcategory->id }}" {{ in_array((string) $subcategory->id, $selectedSubcategoryIds, true) ? 'selected' : '' }}>{{ $subLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Zones') }}</label>
                            <select name="zone_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['zones'] as $zone)
                                    <option value="{{ $zone->id }}" {{ in_array((string) $zone->id, $selectedZoneIds, true) ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Area') }}</label>
                            <select name="area_ids[]" class="form-select js-select" multiple>
                                @foreach($filterOptions['areas'] as $area)
                                    <option value="{{ $area->id }}" {{ in_array((string) $area->id, $selectedAreaIds, true) ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ translate('Cancellation_Reason') }}</label>
                            <select name="cancellation_reason_ids[]" class="form-select js-select" multiple>
                                @if($filterOptions['cancelReasons']->isNotEmpty())
                                    <optgroup label="{{ translate('Customer') }}">
                                        @foreach($filterOptions['cancelReasons'] as $reason)
                                            @php $token = 'c:'.$reason->id; @endphp
                                            <option value="{{ $token }}" {{ in_array($token, $selectedCancellationReasonIds, true) ? 'selected' : '' }}>{{ $reason->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if($filterOptions['providerCancelReasons']->isNotEmpty())
                                    <optgroup label="{{ translate('Provider') }}">
                                        @foreach($filterOptions['providerCancelReasons'] as $reason)
                                            @php $token = 'p:'.$reason->id; @endphp
                                            <option value="{{ $token }}" {{ in_array($token, $selectedCancellationReasonIds, true) ? 'selected' : '' }}>{{ $reason->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="lead-export-sort">{{ translate('Sort_by') }}</label>
                            <select id="lead-export-sort" name="sort" class="form-select">
                                @foreach($sortOptions as $sortKey => $sortLabel)
                                    <option value="{{ $sortKey }}" {{ $sort === $sortKey ? 'selected' : '' }}>{{ $sortLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="lead-export-direction">{{ translate('Sort_order') }}</label>
                            <select id="lead-export-direction" name="direction" class="form-select">
                                <option value="desc" {{ $direction === 'desc' ? 'selected' : '' }}>{{ translate('descending') }}</option>
                                <option value="asc" {{ $direction === 'asc' ? 'selected' : '' }}>{{ translate('ascending') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="lead-export-actions">
                        <a href="{{ route('admin.lead.reports.inbound.export', $stepOneQuery) }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 3)
                <form method="GET" action="{{ route('admin.lead.reports.inbound.export') }}" class="lead-export-card" data-turbo="false" id="lead-export-step-form">
                    <input type="hidden" name="step" value="4">
                    <input type="hidden" name="column_selection" value="1">
                    @foreach($sharedQuery as $name => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <div class="lead-step-intro">
                        <p class="mb-2 text-muted">Name, status, cancellation reason, area, district, category, and sub category are selected. Uncheck anything you do not want.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn--secondary btn-sm" id="lead-export-all">{{ translate('Select_all') }}</button>
                            <button type="button" class="btn btn--secondary btn-sm" id="lead-export-none">{{ translate('clear') }}</button>
                        </div>
                    </div>
                    <input type="search" class="form-control mb-3" id="lead-export-column-search" placeholder="{{ translate('Search') }}">

                    <div class="lead-column-layout">
                        @foreach($groups as $groupName => $groupColumns)
                            <section class="lead-column-group">
                                <h3>
                                    {{ $groupName }}
                                    <button type="button" class="btn btn-link btn-sm p-0 lead-export-group-toggle" data-group="{{ $groupName }}">{{ translate('All') }}</button>
                                </h3>
                                @foreach($groupColumns as $key => $label)
                                    <label class="lead-export-option">
                                        <input type="checkbox" name="columns[]" value="{{ $key }}" data-export-group="{{ $groupName }}" {{ in_array($key, $selectedKeys, true) ? 'checked' : '' }}>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </section>
                        @endforeach
                    </div>

                    <div class="lead-export-actions">
                        <a href="{{ route('admin.lead.reports.inbound.export', $stepTwoQuery) }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary" id="lead-export-columns-next">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 4)
                <form method="POST" action="{{ route('admin.lead.reports.inbound.export.download') }}" class="lead-export-card" data-turbo="false">
                    @csrf
                    <input type="hidden" name="column_selection" value="1">
                    @foreach($sharedQuery as $name => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    @foreach($selectedKeys as $key)
                        <input type="hidden" name="columns[]" value="{{ $key }}">
                    @endforeach

                    <div class="lead-step-intro">
                        <div>
                            <h2 class="mb-1">{{ $reportLabels[$inboundReport] ?? translate('Preview') }}</h2>
                            <p class="mb-3 text-muted">
                                @if($total === 0)
                                    {{ translate('No_leads_found') }}
                                @else
                                    Showing the first {{ count($previewRows) }} of {{ $total }}. The download includes every matching lead, in this order.
                                @endif
                            </p>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('admin.lead.reports.inbound.export', $stepThreeQuery) }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                            <button type="submit" class="btn btn--primary" @disabled($total === 0)>{{ translate('Download_Excel') }}</button>
                        </div>
                    </div>

                    <div class="lead-preview-scroll">
                        <table class="table table-hover align-middle mb-0 lead-preview-table">
                            <thead>
                                <tr>
                                    @foreach($selectedKeys as $key)
                                        <th>{{ $columns[$key] ?? $key }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($previewRows as $row)
                                    <tr>
                                        @foreach($selectedKeys as $key)
                                            @php $cell = $row[$key] ?? ''; @endphp
                                            <td title="{{ $cell }}">{{ $cell !== '' ? $cell : '—' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($selectedKeys), 1) }}" class="text-center text-muted py-5">{{ translate('No_leads_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('lead-export-step-form');
            if (!form) {
                return;
            }

            if (window.jQuery && jQuery.fn.select2) {
                jQuery(form).find('.js-select').each(function () {
                    var $el = jQuery(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        width: '100%',
                        placeholder: @json(translate('All')),
                        allowClear: true
                    });
                });
            }

            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="columns[]"]'));
            var next = document.getElementById('lead-export-columns-next');
            if (boxes.length && next) {
                function syncColumns() {
                    next.disabled = !boxes.some(function (box) { return box.checked; });
                }
                boxes.forEach(function (box) {
                    box.addEventListener('change', syncColumns);
                });
                var all = document.getElementById('lead-export-all');
                var none = document.getElementById('lead-export-none');
                if (all) {
                    all.addEventListener('click', function () {
                        boxes.forEach(function (box) { box.checked = true; });
                        syncColumns();
                    });
                }
                if (none) {
                    none.addEventListener('click', function () {
                        boxes.forEach(function (box) { box.checked = false; });
                        syncColumns();
                    });
                }
                form.querySelectorAll('.lead-export-group-toggle').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var groupBoxes = boxes.filter(function (box) {
                            return box.getAttribute('data-export-group') === button.getAttribute('data-group');
                        });
                        var allChecked = groupBoxes.every(function (box) { return box.checked; });
                        groupBoxes.forEach(function (box) { box.checked = !allChecked; });
                        syncColumns();
                    });
                });
                var search = document.getElementById('lead-export-column-search');
                if (search) {
                    search.addEventListener('input', function () {
                        var query = search.value.trim().toLowerCase();
                        form.querySelectorAll('.lead-export-option').forEach(function (label) {
                            label.hidden = query !== '' && (label.textContent || '').toLowerCase().indexOf(query) === -1;
                        });
                    });
                }
                syncColumns();
            }
        })();
    </script>
@endpush
