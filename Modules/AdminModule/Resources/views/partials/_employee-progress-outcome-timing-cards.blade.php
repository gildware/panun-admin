@php
    $rows = $rows ?? [];
    $successLabel = $successLabel ?? (translate('Progress_converted') ?? 'Converted');
    $toneByKey = [
        'on_time' => 'success',
        'late' => 'warning',
        'missed' => 'danger',
    ];
    $iconByKey = [
        'on_time' => 'schedule',
        'late' => 'running_with_errors',
        'missed' => 'warning',
    ];
    $fmtRate = static function (float $rate): string {
        return rtrim(rtrim(number_format($rate, 1), '0'), '.').'%';
    };
@endphp

<div class="outcome-timing-grid">
    @forelse($rows as $row)
        @php
            $key = $row['key'] ?? '';
            $tone = $toneByKey[$key] ?? '';
            $success = (float) ($row['success_rate'] ?? 0);
            $cancel = (float) ($row['cancel_rate'] ?? 0);
            $pending = (float) ($row['pending_rate'] ?? 0);
            $total = (int) ($row['total'] ?? 0);
            $successCount = (int) ($row['success_count'] ?? 0);
            $cancelCount = (int) ($row['cancel_count'] ?? 0);
            $pendingCount = (int) ($row['pending_count'] ?? 0);
        @endphp
        <div class="outcome-timing-card {{ $tone }}">
            <div class="otc-head">
                <div class="otc-icon">@include('adminmodule::partials._material-icon', ['name' => $iconByKey[$key] ?? 'insights'])</div>
                <div class="otc-title">{{ $row['label'] ?? '' }}</div>
            </div>
            <div class="otc-hero">
                <div class="otc-rate">
                    @include('adminmodule::partials._employee-progress-metric-value', [
                        'count' => $successCount,
                        'total' => $row['team_success_count'] ?? null,
                        'ofClass' => 'mc-of',
                    ])
                </div>
                <div class="otc-rate-label">{{ $successLabel }} · {{ $fmtRate($success) }}</div>
            </div>
            <div class="otc-mix" aria-hidden="true">
                <span class="otc-seg success" style="width: {{ min(100, $success) }}%"></span>
                <span class="otc-seg danger" style="width: {{ min(100, $cancel) }}%"></span>
                <span class="otc-seg warning" style="width: {{ min(100, $pending) }}%"></span>
            </div>
            <div class="otc-legend">
                <span><i class="success"></i>{{ $successLabel }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $successCount, 'total' => $row['team_success_count'] ?? null, 'ofClass' => 'mc-of']) <em class="otc-pct">({{ $fmtRate($success) }})</em></span>
                <span><i class="danger"></i>{{ translate('Cancelled') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $cancelCount, 'total' => $row['team_cancel_count'] ?? null, 'ofClass' => 'mc-of']) <em class="otc-pct">({{ $fmtRate($cancel) }})</em></span>
                <span><i class="warning"></i>{{ translate('Pending') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $pendingCount, 'total' => $row['team_pending_count'] ?? null, 'ofClass' => 'mc-of']) <em class="otc-pct">({{ $fmtRate($pending) }})</em></span>
            </div>
            <div class="otc-foot">
                @include('adminmodule::partials._employee-progress-metric-value', [
                    'count' => $total,
                    'total' => $row['team_total'] ?? null,
                    'ofClass' => 'mc-of',
                ])
                {{ translate('Total') }}
            </div>
        </div>
    @empty
        <div class="outcome-timing-empty">{{ translate('No_data_available') }}</div>
    @endforelse
</div>
