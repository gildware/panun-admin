@extends('adminmodule::layouts.new-master')

@section('title', ! empty($viewingAsAdmin) ? translate('Progress_Report') : translate('My_Progress_Report'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=20260807ac">
<style>
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
    $employeeName = ! empty($viewingAllEmployees)
        ? translate('All_Employees').' · '.($analytics['summary']['employee_count'] ?? 0)
        : (trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? ''));
    $completionRate = min(100, (float) ($analytics['summary']['completion_rate'] ?? 0));
    $disciplinePct = (float) ($analytics['summary']['discipline_pct'] ?? 0);
    $completedAmount = $analytics['summary']['completed_amount'] ?? '';
    $ringDash = round(min(100, $completionRate) * 2.26);
    $periodSubtitle = $tab === 'daily'
        ? ($dateLabel ?? translate('Daily_Report'))
        : ($periodLabel ?? translate('Monthly_Report'));
@endphp
<div class="main-content emp-progress-report">
    <div class="container-fluid premium-wrap">
        <nav class="admin-crumb">
            <a href="{{ route('admin.dashboard') }}">{{ translate('dashboard') }}</a>
            <span class="material-symbols-outlined mso">chevron_right</span>
            <span>{{ ! empty($viewingAsAdmin) ? translate('Progress_Report') : translate('My_Progress_Report') }}</span>
        </nav>

        <div class="page-head">
            <div>
                <h1>{{ ! empty($viewingAsAdmin) ? translate('Progress_Report') : translate('My_Progress_Report') }}</h1>
                <p>
                    {{ $employeeName }}
                    · {{ $periodSubtitle }}
                    · {{ translate('Follow_up_accuracy') }} {{ $disciplinePct }}%
                    · {{ translate('completion_rate') }} {{ $completionRate }}%
                </p>
            </div>
        </div>

        @if($completedAmount !== '')
            <div class="target-ring-wrap">
                @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'completion_summary_ring'])
                <svg class="target-ring" viewBox="0 0 88 88" aria-hidden="true">
                    <circle cx="44" cy="44" r="36" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                    <circle cx="44" cy="44" r="36" fill="none" stroke="#43466e" stroke-width="8"
                            stroke-dasharray="{{ $ringDash }} 226" stroke-linecap="round"/>
                </svg>
                <div class="target-copy">
                    <h3>{{ $completionRate }}% {{ translate('completion_rate') }}</h3>
                    <p>{{ $completedAmount }} {{ translate('Completed_amount') }} · {{ translate('Follow_up_accuracy') }} {{ $disciplinePct }}%</p>
                </div>
                <div class="target-stat-wrap" style="margin-left:auto;text-align:right">
                    <div class="target-stat">{{ $completedAmount }}</div>
                    <div class="target-stat-sub">{{ translate('Completed_amount') }}</div>
                </div>
            </div>
        @endif

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

                <form method="get" action="{{ route('admin.my-progress') }}" class="shell-filter">
                    <input type="hidden" name="section" value="{{ $activeSection }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @foreach($employeeQuery ?? [] as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    @if(! empty($viewingAsAdmin) && ! empty($employeeOptions))
                        <select id="progress-employee" name="employee_id" class="form-select form-select-sm">
                            @foreach($employeeOptions as $option)
                                <option value="{{ $option['id'] }}"
                                    @selected(
                                        (! empty($viewingAllEmployees) && $option['id'] === '__all__')
                                        || (empty($viewingAllEmployees) && $user && (string) $user->id === (string) $option['id'])
                                    )>{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if($tab === 'daily')
                        <input type="date" name="date" value="{{ $date }}">
                    @else
                        <input type="date" name="date_from" value="{{ $dateFrom }}">
                        <span style="color:#94a3b8">–</span>
                        <input type="date" name="date_to" value="{{ $dateTo }}">
                    @endif
                    <a href="{{ route('admin.my-progress', array_merge($employeeQuery ?? [], ['tab' => 'daily', 'section' => $activeSection, 'date' => $date ?? today()->toDateString()])) }}"
                       class="period-link {{ $tab === 'daily' ? 'on' : '' }}">{{ translate('Daily_Report') }}</a>
                    <a href="{{ route('admin.my-progress', array_merge($employeeQuery ?? [], ['tab' => 'monthly', 'section' => $activeSection])) }}"
                       class="period-link {{ $tab === 'monthly' ? 'on' : '' }}">{{ translate('Monthly_Report') }}</a>
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
