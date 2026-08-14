@extends('adminmodule::layouts.new-master')

@section('title', ($detail['label'] ?? translate('Metric_detail')).' · '.$employeeName)

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=20260814hdr4">
@endpush

@section('content')
<div class="main-content emp-progress-report rank-metric-report rank-metric-report--single">
    <div class="container-fluid premium-wrap">
        <div class="report-shell">
            @include('adminmodule::partials._employee-progress-ranking-report-header', [
                'formAction' => route('admin.my-progress.rank-metric'),
                'linkRoute' => 'admin.my-progress.rank-metric',
                'headTitle' => $detail['label'] ?? translate('Metric_detail'),
                'headSubtitle' => $employeeName.' · '.$periodLabel,
                'employee' => $employee,
                'employeeOptions' => $employeeOptions ?? [],
                'viewingAsAdmin' => $viewingAsAdmin ?? false,
                'metric' => $metric ?? null,
                'period' => $period,
                'periodLabel' => $periodLabel,
                'dayLabel' => $dayLabel ?? null,
                'date' => $date,
                'month' => $month ?? null,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ])

            <div class="shell-body shell-body--metric-detail">
                <span class="detail-count detail-count--inline">{{ (int) ($detail['count'] ?? 0) }} {{ translate('records') ?? 'records' }}</span>
                @include('adminmodule::partials._employee-progress-rank-metric-table', ['section' => $detail])
            </div>
        </div>
    </div>
</div>
@endsection
