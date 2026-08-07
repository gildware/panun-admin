@php
    $gridKpis = $gridKpis ?? [];
    $sparkIndexOffset = (int) ($sparkIndexOffset ?? 0);
    $toneMap = ['good' => 'success', 'warning' => 'warning', 'warn' => 'warning', 'danger' => 'danger'];
@endphp

@if($gridKpis !== [])
    <div class="metric-grid">
        @foreach($gridKpis as $index => $kpi)
            @php
                $cardTone = $toneMap[$kpi['tone'] ?? 'brand'] ?? '';
                $sparkColor = match ($kpi['tone'] ?? 'brand') {
                    'good' => '#059669', 'danger' => '#dc2626', 'warning', 'warn' => '#d97706', default => '#43466e',
                };
            @endphp
            <div class="metric-card {{ $cardTone }}">
                <div class="mc-top">
                    <div>
                        <div class="mc-lbl">
                            <span>{{ $kpi['label'] }}</span>
                            @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $kpi['key'] ?? null, 'size' => 'xs'])
                        </div>
                        <div class="mc-val">{{ $kpi['value'] }}</div>
                    </div>
                    <div class="mc-icon">@include('adminmodule::partials._material-icon', ['name' => $kpi['icon'] ?? 'insights'])</div>
                </div>
                <div class="mc-foot">
                    <span class="trend flat">{{ $kpi['footer'] ?? '' }}</span>
                    <div class="mc-spark progress-spark"
                         data-index="{{ $sparkIndexOffset + $index }}"
                         data-spark='@json($kpi['spark'] ?? [])'
                         data-color="{{ $sparkColor }}"></div>
                </div>
            </div>
        @endforeach
    </div>
@endif
