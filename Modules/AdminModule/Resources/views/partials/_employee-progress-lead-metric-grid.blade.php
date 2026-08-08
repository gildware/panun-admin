@php
    $rows = $rows ?? [];
    $helpKeyPrefix = $helpKeyPrefix ?? '';
    $toneClass = ['brand' => '', 'good' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
    $gridClass = $gridClass ?? 'lead-metric-grid';
    $layout = $layout ?? 'card';
    $isInline = $layout === 'inline';
@endphp

<div class="{{ $gridClass }}{{ $isInline ? ' lead-metric-grid--inline' : '' }}">
    @forelse($rows as $row)
        @php
            $cardTone = $toneClass[$row['tone'] ?? 'brand'] ?? '';
            $rowHelpKey = $row['help_key'] ?? ($helpKeyPrefix !== '' ? $helpKeyPrefix.($row['key'] ?? '') : null);
            $hasPct = array_key_exists('pct', $row) && $row['pct'] !== null;
            $pct = $hasPct ? min(100, (float) $row['pct']) : 0.0;
            if ($hasPct && ! empty($row['sublabel'])) {
                $footerText = ($row['pct'] ?? 0).'% · '.$row['sublabel'];
            } elseif ($hasPct) {
                $footerText = ($row['pct'] ?? 0).'%';
            } elseif (! empty($row['sublabel'])) {
                $footerText = $row['sublabel'];
            } else {
                $footerText = '';
            }
        @endphp
        @if($isInline)
            <div class="followup-metric-card followup-metric-card--inline {{ $cardTone }}">
                <div class="fmc-icon">@include('adminmodule::partials._material-icon', ['name' => $row['icon'] ?? 'insights'])</div>
                <div class="fmc-lbl">
                    <span>{{ $row['label'] ?? '' }}</span>
                    @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $rowHelpKey, 'size' => 'xs'])
                </div>
                <div class="fmc-val">
                    @include('adminmodule::partials._employee-progress-metric-value', [
                        'count' => $row['count'] ?? 0,
                        'total' => array_key_exists('value', $row) ? null : ($row['total'] ?? null),
                        'displayValue' => array_key_exists('value', $row) ? (string) $row['value'] : null,
                        'ofClass' => 'fmc-of',
                    ])
                </div>
            </div>
        @else
            <div class="followup-metric-card {{ $cardTone }}">
                <div class="fmc-top">
                    <div class="fmc-main">
                        <div class="fmc-lbl">
                            <span>{{ $row['label'] ?? '' }}</span>
                            @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $rowHelpKey, 'size' => 'xs'])
                        </div>
                        <div class="fmc-val">
                            @include('adminmodule::partials._employee-progress-metric-value', [
                                'count' => $row['count'] ?? 0,
                                'total' => array_key_exists('value', $row) ? null : ($row['total'] ?? null),
                                'displayValue' => array_key_exists('value', $row) ? (string) $row['value'] : null,
                                'ofClass' => 'fmc-of',
                            ])
                        </div>
                    </div>
                    <div class="fmc-icon">@include('adminmodule::partials._material-icon', ['name' => $row['icon'] ?? 'insights'])</div>
                </div>
                <div class="fmc-foot">
                    @if($footerText !== '')
                        <span class="fmc-share">{{ $footerText }}</span>
                    @endif
                    @if($hasPct)
                        <div class="fmc-bar" aria-hidden="true"><span style="width: {{ $pct }}%"></span></div>
                    @endif
                </div>
            </div>
        @endif
    @empty
        <div class="outcome-timing-empty">{{ translate('No_data_available') }}</div>
    @endforelse
</div>
