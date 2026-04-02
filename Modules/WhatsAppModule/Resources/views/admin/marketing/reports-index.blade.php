@extends('adminmodule::layouts.master')

@section('title', translate('Reports'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('WhatsApp_Marketing') }} — {{ translate('Reports') }}</h2>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100"><div class="card-body py-3 text-center">
                        <div class="text-muted small">{{ translate('campaigns') }}</div>
                        <h4 class="mb-0">{{ $totalCampaigns }}</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100"><div class="card-body py-3 text-center">
                        <div class="text-muted small">{{ translate('messages') }}</div>
                        <h4 class="mb-0">{{ $totalMessages }}</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100"><div class="card-body py-3 text-center">
                        <div class="text-muted small">{{ translate('Delivery') }} %</div>
                        <h4 class="mb-0">{{ $deliveryPct }}%</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100"><div class="card-body py-3 text-center">
                        <div class="text-muted small">{{ translate('Read') }} %</div>
                        <h4 class="mb-0">{{ $readPct }}%</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card h-100"><div class="card-body py-3 text-center">
                        <div class="text-muted small">{{ translate('Failure') }} %</div>
                        <h4 class="mb-0">{{ $failurePct }}%</h4>
                    </div></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Messages_sent_per_day') }}</h4>
                            <div id="marketing-messages-line-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="mb-3">{{ translate('Top_campaigns') }}</h4>
                            <div id="marketing-top-campaigns-chart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="mb-3">{{ translate('Top_templates') }}</h4>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Templates') }}</th>
                                <th>{{ translate('language') }}</th>
                                <th>{{ translate('campaigns') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($topTemplates as $i => $t)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $t->name }}</td>
                                    <td>{{ $t->language }}</td>
                                    <td>{{ $t->campaigns_count }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/apex/apexcharts.css') }}"/>
@endpush

@push('script')
    <script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
    <script>
        'use strict';
        const dayLabels = @json($messagesPerDay->pluck('d')->values());
        const dayCounts = @json($messagesPerDay->pluck('c')->values());
        const campLabels = @json($topCampaigns->pluck('name')->values());
        const campSeries = @json($topCampaigns->pluck('delivered_total')->values());

        if (document.querySelector('#marketing-messages-line-chart')) {
            new ApexCharts(document.querySelector('#marketing-messages-line-chart'), {
                chart: {type: 'line', height: 320, toolbar: {show: false}},
                series: [{name: @json(translate('Sent')), data: dayCounts.map(v => parseInt(v, 10) || 0)}],
                xaxis: {categories: dayLabels},
                colors: ['#4153B3'],
                stroke: {width: 3, curve: 'smooth'},
            }).render();
        }

        if (document.querySelector('#marketing-top-campaigns-chart')) {
            new ApexCharts(document.querySelector('#marketing-top-campaigns-chart'), {
                chart: {type: 'bar', height: 320, toolbar: {show: false}},
                plotOptions: {bar: {horizontal: true, borderRadius: 4}},
                series: [{name: @json(translate('Delivered')), data: campSeries.map(v => parseInt(v, 10) || 0)}],
                xaxis: {categories: campLabels},
                colors: ['#F3C278'],
            }).render();
        }
    </script>
@endpush
