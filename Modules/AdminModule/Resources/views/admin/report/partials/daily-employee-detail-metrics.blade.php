<div id="day-detail-metrics" class="row g-2">
    @foreach($metricColumns as $column)
        <div class="col-xl-2 col-lg-3 col-sm-4 col-6">
            <div class="border rounded p-2 h-100">
                <div class="fz-18 fw-semibold">{{ (int) ($totals[$column['key']] ?? 0) }}</div>
                <div class="fz-11 text-muted">{{ $column['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>
