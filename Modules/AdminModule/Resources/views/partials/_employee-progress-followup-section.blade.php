@php
    $sectionTitle = $sectionTitle ?? '';
    $section = $section ?? [];
    $rows = $section['widget_rows'] ?? ($section['summary_rows'] ?? []);
    $helpKeyPrefix = $helpKeyPrefix ?? 'lead_followup';
    $sectionHelpKey = $sectionHelpKey ?? ($helpKeyPrefix.'_summary');
    $toneClass = ['brand' => '', 'good' => 'success', 'warning' => 'warning', 'danger' => 'danger'];
@endphp

<div class="followup-section-block">
    @include('adminmodule::partials._employee-progress-section-label', [
        'label' => $sectionTitle,
        'helpKey' => $sectionHelpKey,
    ])
    <div class="followup-metric-grid">
        @foreach($rows as $row)
            @php
                $cardTone = $toneClass[$row['tone'] ?? 'brand'] ?? '';
                $rowHelpKey = $row['help_key'] ?? ($helpKeyPrefix.'_'.($row['key'] ?? ''));
                $isPercent = ($row['display'] ?? '') === 'percent';
                $pct = min(100, (float) ($row['pct'] ?? 0));
                $footerText = $isPercent
                    ? (translate('Progress_of_due_followups') ?? translate('Follow_up_accuracy'))
                    : (($row['pct'] ?? 0).'% '.(translate('Progress_of_done') ?? translate('Progress_followups_done') ?? translate('Follow_ups')));
                if (! empty($row['sublabel'])) {
                    $footerText = $row['sublabel'];
                } elseif (! empty($section['avg_delay_label']) && ($row['key'] ?? '') === 'late') {
                    $footerText = (translate('Progress_avg_delay') ?? 'Avg delay').': '.$section['avg_delay_label'];
                }
            @endphp
            <div class="followup-metric-card {{ $cardTone }}">
                <div class="fmc-top">
                    <div class="fmc-main">
                        <div class="fmc-lbl">
                            <span>{{ $row['label'] }}</span>
                            @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $rowHelpKey, 'size' => 'xs'])
                        </div>
                        <div class="fmc-val">
                            @if($isPercent)
                                {{ rtrim(rtrim(number_format((float) ($row['count'] ?? 0), 1), '0'), '.') }}%
                            @else
                                {{ number_format((int) ($row['count'] ?? 0)) }}
                            @endif
                        </div>
                    </div>
                    <div class="fmc-icon">@include('adminmodule::partials._material-icon', ['name' => $row['icon'] ?? 'schedule'])</div>
                </div>
                <div class="fmc-foot">
                    <span class="fmc-share">{{ $footerText }}</span>
                    <div class="fmc-bar" aria-hidden="true"><span style="width: {{ $pct }}%"></span></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
