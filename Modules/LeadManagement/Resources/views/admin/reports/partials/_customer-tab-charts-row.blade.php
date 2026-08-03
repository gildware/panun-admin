@php
    $charts = $charts ?? [];
    $rowClass = $rowClass ?? 'customer-breakdown-charts';
@endphp
<div class="row g-3 {{ $rowClass }} customer-breakdown-charts">
    @foreach($charts as $index => $chart)
        @include('leadmanagement::admin.reports.partials._donut-chart-card', [
            'chartId' => $chart['chartId'],
            'title' => $chart['title'],
            'subtitle' => $chart['subtitle'] ?? null,
            'colClass' => $chart['colClass'] ?? ($index === 2 ? 'col-lg-4 col-md-12' : 'col-lg-4 col-md-6'),
            'chartHeight' => $chart['chartHeight'] ?? 260,
        ])
    @endforeach
</div>
