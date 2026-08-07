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
        @endphp
        <div class="outcome-timing-card {{ $tone }}">
            <div class="otc-head">
                <div class="otc-icon">@include('adminmodule::partials._material-icon', ['name' => $iconByKey[$key] ?? 'insights'])</div>
                <div class="otc-title">{{ $row['label'] ?? '' }}</div>
            </div>
            <div class="otc-hero">
                <div class="otc-rate">{{ rtrim(rtrim(number_format($success, 1), '0'), '.') }}%</div>
                <div class="otc-rate-label">{{ $successLabel }}</div>
            </div>
            <div class="otc-mix" aria-hidden="true">
                <span class="otc-seg success" style="width: {{ min(100, $success) }}%"></span>
                <span class="otc-seg danger" style="width: {{ min(100, $cancel) }}%"></span>
                <span class="otc-seg warning" style="width: {{ min(100, $pending) }}%"></span>
            </div>
            <div class="otc-legend">
                <span><i class="success"></i>{{ $successLabel }} {{ rtrim(rtrim(number_format($success, 1), '0'), '.') }}%</span>
                <span><i class="danger"></i>{{ translate('Cancelled') }} {{ rtrim(rtrim(number_format($cancel, 1), '0'), '.') }}%</span>
                <span><i class="warning"></i>{{ translate('Pending') }} {{ rtrim(rtrim(number_format($pending, 1), '0'), '.') }}%</span>
            </div>
            <div class="otc-foot">{{ number_format($total) }} {{ translate('Total') }}</div>
        </div>
    @empty
        <div class="outcome-timing-empty">{{ translate('No_data_available') }}</div>
    @endforelse
</div>
