@extends('adminmodule::layouts.new-master')

@php
    $pageTitle = translate('Progress_employee_marks_report') ?? 'Employee marks report';
    $marksByKey = collect($scoreRow['marks'] ?? [])->keyBy('key');
    $helpedByKey = collect($scoreRow['helped_marks'] ?? [])->keyBy('key');
    $allMarksByKey = $marksByKey->merge($helpedByKey);
    $headTitle = $employeeName;
@endphp

@section('title', $pageTitle.' · '.$employeeName)

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=20260814hdr5">
@endpush

@section('content')
<div class="main-content emp-progress-report rank-metric-report employee-ranking-report">
    <div class="container-fluid premium-wrap">
        <div class="report-shell">
            @include('adminmodule::partials._employee-progress-ranking-report-header', [
                'headTitle' => $headTitle,
                'headSubtitle' => $periodLabel,
                'teamRank' => $teamRank ?? null,
                'employee' => $employee,
                'employeeOptions' => $employeeOptions ?? [],
                'viewingAsAdmin' => $viewingAsAdmin ?? false,
                'period' => $period,
                'periodLabel' => $periodLabel,
                'dayLabel' => $dayLabel ?? null,
                'date' => $date,
                'month' => $month ?? null,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ])

            <div class="score-summary score-summary--in-shell">
                <span class="score-summary-pill is-quantity">
                    <span class="pill-label">{{ translate('Quantity') ?? 'Quantity' }}</span>
                    <span class="pill-value">{{ (int) ($scoreRow['quantity_score'] ?? 0) }}</span>
                </span>
                <span class="score-summary-pill is-helped">
                    <span class="pill-label">{{ translate('Progress_helped_others') ?? 'Helped other' }}</span>
                    <span class="pill-value">{{ (int) ($scoreRow['helped_score'] ?? 0) }}</span>
                </span>
                <span class="score-summary-pill is-penalty">
                    <span class="pill-label">{{ translate('Penalties') ?? 'Penalties' }}</span>
                    <span class="pill-value">{{ (int) ($scoreRow['penalty_score'] ?? 0) }}</span>
                </span>
                <span class="score-summary-pill is-grand">
                    <span class="pill-label">{{ translate('Progress_grand_total') ?? 'Grand total' }}</span>
                    <span class="pill-value">{{ (int) ($scoreRow['score'] ?? 0) }}</span>
                </span>
            </div>

            <div class="shell-body">
                @foreach($fullReport['groups'] ?? [] as $group)
                    <div class="marks-group">
                        <h2 class="marks-group-title">{{ $group['title'] ?? '' }}</h2>
                        <div class="marks-sections-grid">
                            @foreach($group['sections'] ?? [] as $section)
                                @php
                                    $metricKey = $section['metric_key'] ?? '';
                                    $markMeta = $allMarksByKey->get($metricKey, []);
                                    $count = (int) ($section['count'] ?? 0);
                                    $markCount = (int) ($markMeta['count'] ?? $count);
                                    $points = (int) ($markMeta['points'] ?? 0);
                                    $isPlus = array_key_exists('positive', $markMeta) ? ! empty($markMeta['positive']) : $points >= 0;
                                    $pointsDisplay = $points > 0 ? '+'.$points : (string) $points;
                                @endphp
                                <article class="marks-section">
                                    <div class="marks-section-head">
                                        <h3>{{ $section['label'] ?? '' }}</h3>
                                        <div class="marks-section-meta">
                                            <span class="marks-qty-pill">{{ translate('Qty') ?? 'Qty' }} {{ $markCount }}</span>
                                            <span class="marks-total-pill {{ $isPlus ? 'is-plus' : 'is-minus' }}">{{ translate('Total') ?? 'Total' }} {{ $pointsDisplay }}</span>
                                        </div>
                                    </div>
                                    <div class="marks-section-body">
                                        @include('adminmodule::partials._employee-progress-rank-metric-table', ['section' => $section])
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
