@php
    $todayItems = $todayDone['items'] ?? [];
    $todayTotal = (int) ($todayDone['total'] ?? 0);
    $monthStats = $monthly['stats'] ?? [];
    $monthLabel = $monthly['period_label'] ?? '';
    $qualityStatsDaily = $qualityStatsDaily ?? ($quality_stats_daily ?? []);
    $qualityStatsMonthly = $qualityStatsMonthly ?? ($quality_stats_monthly ?? ($qualityStats ?? ($monthly['quality_stats'] ?? [])));
    $todayLabel = translate('Today');
    $progressTitle = $progressTitle ?? translate('My_Progress');
    $progressSubtitle = $progressSubtitle ?? translate('Progress_dashboard_sub');
    $viewReportUrl = $viewReportUrl ?? route('admin.my-progress');

    $toneMap = [
        'good' => 'good',
        'warn' => 'warning',
        'brand' => 'brand',
        'neutral' => 'brand',
        'lead' => 'brand',
        'booking' => 'brand',
        'outbound' => 'warning',
        'whatsapp' => 'good',
        'whatsapp-closed' => 'good',
    ];
    $toMetricRows = static function (array $items) use ($toneMap): array {
        return collect($items)->map(function ($item) use ($toneMap) {
            $tone = $toneMap[$item['tone'] ?? 'brand'] ?? 'brand';
            $hasPct = array_key_exists('pct', $item) && $item['pct'] !== null;

            return [
                'key' => $item['key'] ?? '',
                'label' => $item['label'] ?? '',
                'icon' => $item['icon'] ?? 'insights',
                'count' => (int) ($item['raw'] ?? $item['count'] ?? 0),
                'value' => (string) ($item['value'] ?? ($item['count'] ?? 0)),
                'pct' => $hasPct ? (float) $item['pct'] : null,
                'tone' => $tone,
                'sublabel' => $item['sub'] ?? null,
            ];
        })->all();
    };
@endphp

<div class="progress-shell js-progress-shell">
    <div class="progress-shell-header">
        <div>
            <h5 class="progress-shell-title">{{ $progressTitle }}</h5>
            <span class="progress-shell-sub">{{ $progressSubtitle }}</span>
        </div>
        <a href="{{ $viewReportUrl }}" class="progress-view-report-btn" data-turbo="false">
            <span class="material-symbols-outlined">analytics</span>
            {{ translate('View_full_progress_report') }}
        </a>
    </div>

    <div class="progress-shell-body emp-progress-report">
        <div class="row g-3 progress-cards-row progress-cards-row--metrics">
            {{-- Quality metrics --}}
            <div class="col-lg-6">
                <div class="progress-card progress-card--compact progress-card--quality h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ translate('Quality') }}</span>
                        </div>
                        <div class="progress-card-header-action">
                            <div class="progress-tabs" data-tabs="quality">
                                <button type="button" class="progress-tab active" data-tab="quality-daily">{{ translate('Daily') }}</button>
                                <button type="button" class="progress-tab" data-tab="quality-monthly">{{ translate('Monthly') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-card-body">
                        <div data-panel="quality-daily" class="activity-panel active">
                            <div class="activity-panel-meta">
                                <span>{{ $todayLabel }}</span>
                            </div>
                            @include('adminmodule::partials._employee-progress-lead-metric-grid', [
                                'rows' => $toMetricRows($qualityStatsDaily),
                                'gridClass' => 'lead-metric-grid lead-metric-grid--dashboard',
                            ])
                        </div>
                        <div data-panel="quality-monthly" class="activity-panel">
                            @if($monthLabel !== '')
                                <div class="activity-panel-meta">
                                    <span>{{ $monthLabel }}</span>
                                </div>
                            @endif
                            @include('adminmodule::partials._employee-progress-lead-metric-grid', [
                                'rows' => $toMetricRows($qualityStatsMonthly),
                                'gridClass' => 'lead-metric-grid lead-metric-grid--dashboard',
                            ])
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quantity metrics --}}
            <div class="col-lg-6">
                <div class="progress-card progress-card--compact progress-card--quantity h-100">
                    <div class="progress-card-header">
                        <div class="progress-card-header-main">
                            <span class="progress-card-title">{{ translate('Quantity') }}</span>
                        </div>
                        <div class="progress-card-header-action">
                            <div class="progress-tabs" data-tabs="quantity">
                                <button type="button" class="progress-tab active" data-tab="quantity-daily">{{ translate('Daily') }}</button>
                                <button type="button" class="progress-tab" data-tab="quantity-monthly">{{ translate('Monthly') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="progress-card-body">
                        <div data-panel="quantity-daily" class="activity-panel active">
                            <div class="activity-panel-meta">
                                <span>{{ $todayLabel }}</span>
                                <span class="progress-summary-badge {{ $todayTotal > 0 ? 'is-active' : '' }}">{{ $todayTotal }}</span>
                            </div>
                            @include('adminmodule::partials._employee-progress-lead-metric-grid', [
                                'rows' => $toMetricRows($todayItems),
                                'gridClass' => 'lead-metric-grid lead-metric-grid--dashboard',
                            ])
                        </div>
                        <div data-panel="quantity-monthly" class="activity-panel">
                            @if($monthLabel !== '')
                                <div class="activity-panel-meta">
                                    <span>{{ $monthLabel }}</span>
                                </div>
                            @endif
                            @include('adminmodule::partials._employee-progress-lead-metric-grid', [
                                'rows' => $toMetricRows($monthStats),
                                'gridClass' => 'lead-metric-grid lead-metric-grid--dashboard',
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
