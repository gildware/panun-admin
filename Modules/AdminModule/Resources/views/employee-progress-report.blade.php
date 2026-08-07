@extends('adminmodule::layouts.new-master')

@php
    $selectedEmployeeName = ! empty($viewingAllEmployees)
        ? translate('All_Employees')
        : (trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? ''));
    $pageTitle = ! empty($viewingAsAdmin)
        ? translate('Progress_Report').' of '.$selectedEmployeeName
        : translate('My_Progress_Report');
@endphp
@section('title', $pageTitle)

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=20260807aj">
<style>
    .emp-progress-report .page-head-employee {
        margin-left: auto;
        flex: 0 0 auto;
    }
    .emp-progress-report .page-head-employee .form-select {
        min-width: 180px; max-width: 240px;
        padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px;
        font-size: 12px; font-family: Outfit, sans-serif; min-height: auto;
        background: #fff; color: #0f172a;
    }
    .emp-progress-report .shell-filter .form-select,
    .emp-progress-report .shell-filter input[type="date"] {
        padding: 5px 8px; border: 1px solid #e2e8f0; border-radius: 6px;
        font-size: 10px; font-family: Outfit, sans-serif; min-height: auto;
    }
    .emp-progress-report .shell-filter .btn-brand,
    .emp-progress-report .shell-filter .period-link {
        padding: 5px 10px; border-radius: 6px; font-size: 10px; font-weight: 700;
        text-decoration: none;
    }
    .emp-progress-report .shell-filter .btn-brand { background: #43466e; border-color: #43466e; color: #fff; }
    .emp-progress-report .shell-filter .period-link {
        border: 1px solid #e2e8f0; color: #64748b; background: #fff;
    }
    .emp-progress-report .shell-filter .period-link.on { background: #43466e; color: #fff; border-color: #43466e; }
    .emp-progress-report .shell-tab { text-decoration: none; display: inline-block; border: none; }
    .emp-progress-report .material-symbols-outlined.mso { font-size: 18px; vertical-align: middle; }
</style>
@endpush

@section('content')
@php
    $requestedSection = request('section');
    if ($requestedSection === 'followups') {
        $activeSection = 'lead-followups';
    } elseif ($requestedSection === 'operations') {
        $activeSection = 'bookings';
    } elseif ($requestedSection === 'reports') {
        $activeSection = 'daily-basis';
    } elseif (in_array($requestedSection, ['overview', 'bookings', 'leads', 'lead-followups', 'booking-followups', 'daily-basis'], true)) {
        $activeSection = $requestedSection;
    } else {
        $activeSection = 'overview';
    }
    $completionRate = min(100, (float) ($analytics['summary']['completion_rate'] ?? 0));
    $disciplinePct = (float) ($analytics['summary']['discipline_pct'] ?? 0);
    $periodSubtitle = $tab === 'daily'
        ? ($dateLabel ?? translate('Daily_Report'))
        : ($periodLabel ?? translate('Monthly_Report'));
@endphp
<div class="main-content emp-progress-report">
    <div class="container-fluid premium-wrap">
        <nav class="admin-crumb">
            <a href="{{ route('admin.dashboard') }}">{{ translate('dashboard') }}</a>
            <span class="material-symbols-outlined mso">chevron_right</span>
            <span>{{ $pageTitle }}</span>
        </nav>

        <div class="page-head">
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p>
                    {{ $periodSubtitle }}
                    · {{ translate('Follow_up_accuracy') }} {{ $disciplinePct }}%
                    · {{ translate('completion_rate') }} {{ $completionRate }}%
                </p>
            </div>
            @if(! empty($viewingAsAdmin) && ! empty($employeeOptions))
                <form method="get" action="{{ route('admin.my-progress') }}" class="page-head-employee" data-turbo="false">
                    <input type="hidden" name="section" value="{{ $activeSection }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @if($tab === 'daily')
                        <input type="hidden" name="date" value="{{ $date }}">
                    @else
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    @endif
                    <label class="visually-hidden" for="progress-employee">{{ translate('Select_employee') }}</label>
                    <select id="progress-employee" name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($employeeOptions as $option)
                            <option value="{{ $option['id'] }}"
                                @selected(
                                    (! empty($viewingAllEmployees) && $option['id'] === '__all__')
                                    || (empty($viewingAllEmployees) && $user && (string) $user->id === (string) $option['id'])
                                )>{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        <div class="report-shell">
            <div class="shell-head">
                <div class="shell-tabs">
                    <button type="button" class="shell-tab {{ $activeSection === 'overview' ? 'on' : '' }}" data-tab="overview">{{ translate('Overview') }}</button>
                    <button type="button" class="shell-tab {{ $activeSection === 'bookings' ? 'on' : '' }}" data-tab="bookings">{{ translate('Bookings') }}</button>
                    <button type="button" class="shell-tab {{ $activeSection === 'leads' ? 'on' : '' }}" data-tab="leads">{{ translate('Leads') }}</button>
                    <button type="button" class="shell-tab {{ $activeSection === 'lead-followups' ? 'on' : '' }}" data-tab="lead-followups">{{ translate('Lead_followups') }}</button>
                    <button type="button" class="shell-tab {{ $activeSection === 'booking-followups' ? 'on' : '' }}" data-tab="booking-followups">{{ translate('Booking_Followups') }}</button>
                    <button type="button" class="shell-tab {{ $activeSection === 'daily-basis' ? 'on' : '' }}" data-tab="daily-basis">{{ translate('Daily_Basis_Report') ?? 'Daily Basis Report' }}</button>
                </div>

                <form method="get" action="{{ route('admin.my-progress') }}" class="shell-filter" data-turbo="false">
                    <input type="hidden" name="section" value="{{ $activeSection }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @foreach($employeeQuery ?? [] as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    @if($tab === 'daily')
                        <input type="date" name="date" value="{{ $date }}">
                    @else
                        <input type="date" name="date_from" value="{{ $dateFrom }}">
                        <span style="color:#94a3b8">–</span>
                        <input type="date" name="date_to" value="{{ $dateTo }}">
                    @endif
                    <a href="{{ route('admin.my-progress', array_merge($employeeQuery ?? [], ['tab' => 'daily', 'section' => $activeSection, 'date' => $date ?? today()->toDateString()])) }}"
                       class="period-link {{ $tab === 'daily' ? 'on' : '' }}"
                       data-turbo="false">{{ translate('Daily_Report') }}</a>
                    <a href="{{ route('admin.my-progress', array_merge($employeeQuery ?? [], ['tab' => 'monthly', 'section' => $activeSection])) }}"
                       class="period-link {{ $tab === 'monthly' ? 'on' : '' }}"
                       data-turbo="false">{{ translate('Monthly_Report') }}</a>
                    <button type="submit" class="btn btn-brand">{{ translate('Apply') }}</button>
                </form>
            </div>
            <div class="shell-body">
                @if(! empty($analytics))
                    @include('adminmodule::partials._employee-progress-tab-panels', [
                        'analytics' => $analytics,
                        'fullReport' => $fullReport ?? [],
                        'viewingAllEmployees' => $viewingAllEmployees ?? false,
                        'activeSection' => $activeSection,
                        'tab' => $tab,
                        'detail' => $detail ?? null,
                        'dateLabel' => $dateLabel ?? null,
                        'periodLabel' => $periodLabel ?? null,
                        'date' => $date ?? null,
                        'metricColumns' => $metricColumns ?? [],
                        'activityMetricColumns' => $activityMetricColumns ?? [],
                        'activityTotals' => $activityTotals ?? [],
                        'activityDailyRows' => $activityDailyRows ?? [],
                        'sectionDefs' => $sectionDefs ?? [],
                        'dailyRows' => $dailyRows ?? [],
                        'monthly' => $monthly ?? [],
                        'employeeQuery' => $employeeQuery ?? [],
                        'leadAnalytics' => $leadAnalytics ?? [],
                        'followupAnalytics' => $followupAnalytics ?? [],
                        'metricHelpRegistry' => $metricHelpRegistry ?? [],
                    ])
                @endif
            </div>
        </div>
    </div>
</div>
@include('adminmodule::partials._employee-progress-info-dropdown')
@endsection
