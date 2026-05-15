@php
    $chartId = $chartId ?? 'chart-' . uniqid();
    $colClass = $colClass ?? 'col-lg-4 col-md-6';
    $chartHeight = $chartHeight ?? 220;
@endphp
<div class="{{ $colClass }}">
    <div class="card h-100 customer-report-chart-card border">
        <div class="card-body p-3 d-flex flex-column">
            <h6 class="fw-semibold mb-0">{{ $title ?? '' }}</h6>
            @if(!empty($subtitle))
                <p class="text-muted fz-11 mb-2">{{ $subtitle }}</p>
            @endif
            <div id="{{ $chartId }}"
                 class="customer-donut-chart flex-grow-1"
                 style="min-height: {{ $chartHeight }}px;"
                 data-empty-text="{{ $emptyText ?? translate('Data_not_available') }}"></div>
        </div>
    </div>
</div>
