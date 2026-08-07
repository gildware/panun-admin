@php
    $followupAnalytics = $followupAnalytics ?? [];
    $section = $followupAnalytics['bookings'] ?? [];
    $lateRows = collect($section['late_rows'] ?? [])->sortByDesc('delay_minutes')->take(15)->values()->all();
    $periodLabel = $followupAnalytics['period_label'] ?? ($periodLabel ?? ($dateLabel ?? ''));
    $delayBuckets = [];
    foreach ([
        ['label' => translate('Progress_delay_under_1h') ?? '< 1 hour', 'crit' => false],
        ['label' => translate('Progress_delay_1_24h') ?? '1–24 hours', 'crit' => false],
        ['label' => translate('Progress_delay_1_3d') ?? '1–3 days', 'crit' => true],
        ['label' => translate('Progress_delay_over_3d') ?? '3+ days', 'crit' => true],
    ] as $def) {
        $delayBuckets[] = ['label' => $def['label'], 'count' => 0, 'crit' => $def['crit']];
    }
    foreach ($lateRows as $row) {
        $minutes = (int) ($row['delay_minutes'] ?? 0);
        if ($minutes < 60) {
            $delayBuckets[0]['count']++;
        } elseif ($minutes < 1440) {
            $delayBuckets[1]['count']++;
        } elseif ($minutes < 4320) {
            $delayBuckets[2]['count']++;
        } else {
            $delayBuckets[3]['count']++;
        }
    }
@endphp

@if($periodLabel !== '')
    <div class="compact-banner">
        @include('adminmodule::partials._material-icon', ['name' => 'date_range', 'class' => 'mso'])
        <div>{{ translate('Booking_Followups') }} · {{ translate('Progress_followup_analytics_period') ?? translate('Follow_ups') }}: <strong>{{ $periodLabel }}</strong></div>
    </div>
@endif

@include('adminmodule::partials._employee-progress-followup-section', [
    'sectionTitle' => translate('Progress_followup_summary') ?? translate('Booking_Followups'),
    'section' => $section,
    'helpKeyPrefix' => 'booking_followup',
    'sectionHelpKey' => 'booking_followup_summary',
])

@include('adminmodule::partials._employee-progress-followup-outcome-impact', [
    'followupAnalytics' => $followupAnalytics,
    'scope' => 'bookings',
])

@include('adminmodule::partials._employee-progress-section-label', [
    'label' => translate('Progress_delay_breakdown') ?? translate('Progress_late_followups'),
    'helpKey' => 'booking_delay_breakdown',
])
<div class="aging-row">
    @foreach($delayBuckets as $bucket)
        <div class="aging-cell {{ ! empty($bucket['crit']) ? 'crit' : '' }}">
            <strong>{{ $bucket['count'] ?? 0 }}</strong><span>{{ $bucket['label'] ?? '' }}</span>
        </div>
    @endforeach
</div>

<div class="chart-card">
    @include('adminmodule::partials._employee-progress-chart-head', [
        'icon' => 'bar_chart',
        'title' => translate('Booking_Followups'),
        'subtitle' => (translate('Progress_followups_done') ?? 'Done').' · '.(translate('Progress_late_followups') ?? 'Late').' · '.translate('Progress_missed_followups'),
        'helpKey' => 'chart_followup_booking_trend',
    ])
    <div class="chart-card-body"><div id="chart-followup-booking-trend" class="chart-bar chart-followup-daily"></div></div>
</div>
