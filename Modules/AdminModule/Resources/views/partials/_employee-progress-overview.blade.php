@php
    $analytics = $analytics ?? [];
    $kpis = $analytics['kpis'] ?? [];
    $charts = $analytics['charts'] ?? [];
    $insights = $analytics['insights'] ?? [];
    $topPerformers = $analytics['top_performers'] ?? [];
    $bookingStatusBreakdown = $analytics['booking_status_breakdown'] ?? [];
    $leadAnalytics = $leadAnalytics ?? [];
    $followupAnalytics = $followupAnalytics ?? [];
    $fullReport = $fullReport ?? [];
    $leaderboard = $fullReport['leaderboard'] ?? [];
    $activityTotals = $activityTotals ?? [];
    $activityMetricColumns = $activityMetricColumns ?? [];
    $viewingAllEmployees = ! empty($viewingAllEmployees);
    $toneMap = ['good' => 'success', 'warning' => 'warning', 'warn' => 'warning', 'danger' => 'danger', 'brand' => ''];

    $kpiByKey = collect($kpis)->keyBy('key');
    $pickKpi = function (string $key, ?string $fallbackLabel = null, $fallbackValue = 0, string $icon = 'insights', string $tone = 'brand') use ($kpiByKey) {
        if ($kpiByKey->has($key)) {
            return $kpiByKey->get($key);
        }

        return [
            'key' => $key,
            'label' => $fallbackLabel ?? $key,
            'value' => is_numeric($fallbackValue) ? number_format((int) $fallbackValue) : $fallbackValue,
            'icon' => $icon,
            'tone' => $tone,
            'spark' => [],
            'footer' => '',
        ];
    };

    $leadSection = $followupAnalytics['leads'] ?? [];
    $bookingFuSection = $followupAnalytics['bookings'] ?? [];
    $overallFu = $followupAnalytics['overall'] ?? [];
    $customer = $leadAnalytics['customer'] ?? [];
    $provider = $leadAnalytics['provider'] ?? [];

    $overviewKpis = [
        $pickKpi('leads_added', translate('Leads_added'), $activityTotals['leads_added'] ?? 0, 'contact_page'),
        $pickKpi('bookings_created', translate('Bookings_created'), $activityTotals['bookings_created'] ?? 0, 'event'),
        $pickKpi('completed_bookings', translate('Bookings_completed'), 0, 'check_circle', 'good'),
        $pickKpi('completion_rate', translate('completion_rate'), ($analytics['summary']['completion_rate'] ?? 0).'%', 'percent', 'good'),
        $pickKpi('lead_followups', translate('Lead_followups'), $activityTotals['lead_followups'] ?? ($leadSection['total_done'] ?? 0), 'schedule'),
        $pickKpi('booking_followups', translate('Booking_Followups'), $activityTotals['booking_followups'] ?? ($bookingFuSection['total_done'] ?? 0), 'event_repeat'),
        $pickKpi('followup_accuracy', translate('Follow_up_accuracy'), ($overallFu['accuracy_pct'] ?? ($analytics['summary']['discipline_pct'] ?? 100)).'%', 'task_alt', 'good'),
        $pickKpi('missed_followups', translate('Progress_missed_followups'), $overallFu['missed'] ?? 0, 'warning', ((int) ($overallFu['missed'] ?? 0)) > 0 ? 'danger' : 'good'),
    ];

    if ((int) ($kpiByKey->get('cancelled_bookings')['value'] ?? ($activityTotals['bookings_cancelled'] ?? 0)) > 0
        || (int) ($activityTotals['bookings_cancelled'] ?? 0) > 0
    ) {
        $overviewKpis[] = $pickKpi(
            'cancelled_bookings',
            translate('Cancelled'),
            $activityTotals['bookings_cancelled'] ?? 0,
            'cancel',
            'danger'
        );
    }

    $breakdownByKey = collect($bookingStatusBreakdown)->keyBy('key');
    $bookingPending = (int) ($breakdownByKey->get('pending')['count'] ?? 0);
    $bookingCompleted = (int) ($breakdownByKey->get('completed')['count'] ?? 0);
    $bookingCancelled = (int) ($breakdownByKey->get('cancelled')['count'] ?? ($activityTotals['bookings_cancelled'] ?? 0));
    $bookingOnHold = (int) ($breakdownByKey->get('on_hold')['count'] ?? 0) + (int) ($breakdownByKey->get('hold_after_visit')['count'] ?? 0);

    $leadsHandled = (int) ($leadAnalytics['total_handled'] ?? ($activityTotals['leads_handled'] ?? 0));
    $leadsAdded = (int) ($activityTotals['leads_added'] ?? 0);
    $customerBooked = (int) ($customer['booked'] ?? 0);
    $customerCancelled = (int) ($customer['cancelled'] ?? 0);
    $customerConversion = (float) ($customer['conversion_rate'] ?? 0);

    $leadFuDone = (int) ($leadSection['total_done'] ?? 0);
    $leadFuAccuracy = (float) ($leadSection['accuracy_pct'] ?? 100);
    $leadFuMissed = (int) ($leadSection['missed'] ?? 0);
    $leadFuLate = (int) ($leadSection['late'] ?? 0);
    $leadFuResched = (int) ($leadSection['rescheduled'] ?? ($activityTotals['lead_followups_rescheduled'] ?? 0));

    $bkFuDone = (int) ($bookingFuSection['total_done'] ?? 0);
    $bkFuAccuracy = (float) ($bookingFuSection['accuracy_pct'] ?? 100);
    $bkFuMissed = (int) ($bookingFuSection['missed'] ?? 0);
    $bkFuLate = (int) ($bookingFuSection['late'] ?? 0);
    $bkFuResched = (int) ($bookingFuSection['rescheduled'] ?? ($activityTotals['booking_followups_rescheduled'] ?? 0));

    $activityKeys = collect($activityMetricColumns)->pluck('key')->all();
    $totalActions = collect($activityKeys)->sum(fn ($key) => (int) ($activityTotals[$key] ?? 0));
    $waAssigned = (int) ($activityTotals['whatsapp_assigned'] ?? 0);
    $waReplies = (int) ($activityTotals['whatsapp_replies'] ?? 0);
    $callLogs = (int) ($activityTotals['call_logs'] ?? 0);

    $snapshotCards = [
        [
            'tab' => 'bookings',
            'title' => translate('Bookings'),
            'icon' => 'event_note',
            'helpKey' => 'overview_snap_bookings',
            'tone' => $bookingCancelled > $bookingCompleted ? 'danger' : ($bookingPending > 0 ? 'warning' : 'good'),
            'primary' => number_format((int) ($activityTotals['bookings_created'] ?? ($breakdownByKey->get('handled')['count'] ?? 0))),
            'primary_label' => translate('Bookings_created'),
            'stats' => [
                ['label' => translate('Bookings_completed'), 'value' => number_format($bookingCompleted)],
                ['label' => translate('Cancelled'), 'value' => number_format($bookingCancelled)],
                ['label' => translate('Pending'), 'value' => number_format($bookingPending)],
                ['label' => translate('On_hold') ?? 'On hold', 'value' => number_format($bookingOnHold)],
            ],
            'insight' => $bookingCancelled > 0 && $bookingCancelled >= max(1, $bookingCompleted)
                ? (translate('Progress_overview_booking_cancel_insight') ?? 'Cancellations are high vs completions — review booking outcomes.')
                : ($bookingPending > 0
                    ? (translate('Progress_overview_booking_pending_insight') ?? 'Open bookings still need follow-through.')
                    : (translate('Progress_overview_booking_good_insight') ?? 'Booking pipeline looks steady for this period.')),
        ],
        [
            'tab' => 'leads',
            'title' => translate('Leads'),
            'icon' => 'group',
            'helpKey' => 'overview_snap_leads',
            'tone' => $customerConversion >= 30 ? 'good' : ($customerCancelled > $customerBooked ? 'danger' : 'warning'),
            'primary' => number_format(max($leadsHandled, $leadsAdded)),
            'primary_label' => $leadsHandled > 0
                ? (translate('Progress_leads_handled') ?? translate('Leads_Handled'))
                : translate('Leads_added'),
            'stats' => [
                ['label' => translate('New_Leads_Added'), 'value' => number_format($leadsAdded)],
                ['label' => translate('Progress_converted') ?? 'Converted', 'value' => number_format($customerBooked)],
                ['label' => translate('Cancelled'), 'value' => number_format($customerCancelled)],
                ['label' => translate('completion_rate'), 'value' => rtrim(rtrim(number_format($customerConversion, 1), '0'), '.').'%'],
            ],
            'insight' => $customerConversion < 20 && ($customer['total'] ?? 0) > 0
                ? (translate('Progress_overview_lead_conversion_insight') ?? 'Customer lead conversion is low — check pending and cancelled reasons.')
                : (($provider['cancelled'] ?? 0) > ($provider['registered'] ?? 0) && ($provider['total'] ?? 0) > 0
                    ? (translate('Progress_overview_provider_cancel_insight') ?? 'Provider lead cancellations need attention.')
                    : (translate('Progress_overview_lead_good_insight') ?? 'Lead handling is on track for this period.')),
        ],
        [
            'tab' => 'lead-followups',
            'title' => translate('Lead_followups'),
            'icon' => 'task_alt',
            'helpKey' => 'overview_snap_lead_fu',
            'tone' => $leadFuMissed > 0 ? 'danger' : ($leadFuLate > 0 || $leadFuAccuracy < 80 ? 'warning' : 'good'),
            'primary' => number_format($leadFuDone),
            'primary_label' => translate('Progress_followups_done') ?? translate('Follow_ups'),
            'stats' => [
                ['label' => translate('Follow_up_accuracy'), 'value' => rtrim(rtrim(number_format($leadFuAccuracy, 1), '0'), '.').'%'],
                ['label' => translate('Progress_late_followups') ?? 'Late', 'value' => number_format($leadFuLate)],
                ['label' => translate('Progress_missed_followups'), 'value' => number_format($leadFuMissed)],
                ['label' => translate('Reschedule'), 'value' => number_format($leadFuResched)],
            ],
            'insight' => $leadFuMissed > 0
                ? (translate('Progress_overview_lead_fu_missed_insight') ?? 'Missed lead follow-ups need catch-up today.')
                : ($leadFuAccuracy < 80
                    ? (translate('Progress_overview_lead_fu_late_insight') ?? 'Lead follow-up accuracy is below target — reduce late work.')
                    : (translate('Progress_overview_lead_fu_good_insight') ?? 'Lead follow-ups are mostly on time.')),
        ],
        [
            'tab' => 'booking-followups',
            'title' => translate('Booking_Followups'),
            'icon' => 'event_repeat',
            'helpKey' => 'overview_snap_booking_fu',
            'tone' => $bkFuMissed > 0 ? 'danger' : ($bkFuLate > 0 || $bkFuAccuracy < 80 ? 'warning' : 'good'),
            'primary' => number_format($bkFuDone),
            'primary_label' => translate('Progress_followups_done') ?? translate('Follow_ups'),
            'stats' => [
                ['label' => translate('Follow_up_accuracy'), 'value' => rtrim(rtrim(number_format($bkFuAccuracy, 1), '0'), '.').'%'],
                ['label' => translate('Progress_late_followups') ?? 'Late', 'value' => number_format($bkFuLate)],
                ['label' => translate('Progress_missed_followups'), 'value' => number_format($bkFuMissed)],
                ['label' => translate('Reschedule'), 'value' => number_format($bkFuResched)],
            ],
            'insight' => $bkFuMissed > 0
                ? (translate('Progress_overview_booking_fu_missed_insight') ?? 'Missed booking follow-ups need catch-up.')
                : ($bkFuAccuracy < 80
                    ? (translate('Progress_overview_booking_fu_late_insight') ?? 'Booking follow-up accuracy needs improvement.')
                    : (translate('Progress_overview_booking_fu_good_insight') ?? 'Booking follow-ups look healthy.')),
        ],
        [
            'tab' => 'daily-basis',
            'title' => translate('Daily_Basis_Report') ?? 'Daily Basis Report',
            'icon' => 'calendar_month',
            'helpKey' => 'overview_snap_daily',
            'tone' => $totalActions > 0 ? 'brand' : 'warning',
            'primary' => number_format($totalActions),
            'primary_label' => translate('Total_actions') ?? 'Total actions',
            'stats' => [
                ['label' => translate('WhatsApp_Chats_Assigned'), 'value' => number_format($waAssigned)],
                ['label' => translate('WhatsApp_Replies'), 'value' => number_format($waReplies)],
                ['label' => translate('Call_Logs_Added'), 'value' => number_format($callLogs)],
                ['label' => translate('Online'), 'value' => $activityTotals['online_hours'] ?? '—'],
            ],
            'insight' => $totalActions === 0
                ? (translate('Progress_overview_daily_empty_insight') ?? 'No recorded daily activity in this period yet.')
                : (translate('Progress_overview_daily_good_insight') ?? 'See Daily Basis for the full day-by-day activity breakdown.'),
        ],
    ];

    $crossInsights = collect($insights);
    foreach ($snapshotCards as $card) {
        $priority = match ($card['tone']) {
            'danger' => 'high',
            'warning' => 'medium',
            default => 'low',
        };
        if ($priority === 'low') {
            continue;
        }
        $crossInsights->push([
            'priority' => $priority,
            'title' => $card['title'],
            'detail' => $card['insight'],
            'tab' => $card['tab'],
        ]);
    }
    $crossInsights = $crossInsights
        ->unique(fn ($item) => ($item['title'] ?? '').'|'.($item['detail'] ?? ''))
        ->sortBy(fn ($item) => match ($item['priority'] ?? 'low') {
            'high' => 0,
            'medium' => 1,
            default => 2,
        })
        ->take(6)
        ->values()
        ->all();
@endphp

@include('adminmodule::partials._employee-progress-kpi-grid', ['gridKpis' => $overviewKpis])

<div class="daily-basis-group overview-snapshots">
    @include('adminmodule::partials._employee-progress-section-label', [
        'label' => translate('Progress_overview_all_tabs') ?? 'Overview across all tabs',
        'helpKey' => 'overview_all_tabs',
    ])
    <p class="section-sub">{{ translate('Progress_overview_all_tabs_sub') ?? 'Quick insights from Bookings, Leads, Follow-ups, and Daily Basis — open a card to drill in.' }}</p>

    <div class="overview-snap-grid">
        @foreach($snapshotCards as $card)
            @php $cardTone = $toneMap[$card['tone'] ?? 'brand'] ?? ''; @endphp
            <article
                    class="overview-snap-card {{ $cardTone }}"
                    data-jump-tab="{{ $card['tab'] }}"
                    role="button"
                    tabindex="0">
                <div class="overview-snap-head">
                    <div class="overview-snap-title">
                        <span class="overview-snap-icon">@include('adminmodule::partials._material-icon', ['name' => $card['icon']])</span>
                        <span>{{ $card['title'] }}</span>
                        @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => $card['helpKey'], 'size' => 'xs'])
                    </div>
                    <span class="overview-snap-open">{{ translate('View') ?? 'View' }}</span>
                </div>
                <div class="overview-snap-primary">
                    <div class="overview-snap-val">{{ $card['primary'] }}</div>
                    <div class="overview-snap-lbl">{{ $card['primary_label'] }}</div>
                </div>
                <div class="overview-snap-stats">
                    @foreach($card['stats'] as $stat)
                        <div class="overview-snap-stat">
                            <span>{{ $stat['label'] }}</span>
                            <strong>{{ $stat['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
                <p class="overview-snap-insight">{{ $card['insight'] }}</p>
            </article>
        @endforeach
    </div>
</div>

<div class="layout-main">
    <div class="chart-card">
        @include('adminmodule::partials._employee-progress-chart-head', [
            'icon' => 'show_chart',
            'title' => translate('Revenue_Overview') ?? translate('Daily_activity_breakdown'),
            'subtitle' => translate('Bookings_created').' · '.translate('Leads_added'),
            'helpKey' => 'chart_revenue_main',
        ])
        <div class="chart-card-body"><div id="chart-revenue-main" class="chart-trend"></div></div>
    </div>
    <div class="side-stack">
        <div class="rank-card">
            <div class="rank-head">
                <span>{{ translate('Progress_team_ranking') }}</span>
                @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'team_ranking', 'size' => 'xs'])
            </div>
            @forelse(($viewingAllEmployees ? $topPerformers : []) as $index => $performer)
                @php
                    $initials = collect(explode(' ', $performer['name']))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                    $avatarClass = match ($index) { 1 => 'silver', 2 => 'bronze', default => '' };
                    $maxScore = max(1, (int) ($topPerformers[0]['score'] ?? 1));
                @endphp
                <div class="rank-item">
                    <div class="avatar {{ $avatarClass }}">{{ $initials ?: '—' }}</div>
                    <div class="rank-meta">
                        <div class="rank-name">{{ $performer['name'] }}</div>
                        <div class="rank-sub">{{ $performer['bookings'] }} {{ translate('Bookings_created') }}</div>
                        <div class="rank-bar"><i style="width: {{ round(((int) $performer['score'] / $maxScore) * 100) }}%"></i></div>
                    </div>
                    <div class="rank-val">{{ $performer['revenue'] ?? $performer['score'] }}</div>
                </div>
            @empty
                @if(($leaderboard['overall_rank'] ?? 0) > 0)
                    <div class="rank-item">
                        <div class="avatar">#{{ $leaderboard['overall_rank'] }}</div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ translate('Progress_overall_team_rank') }}</div>
                            <div class="rank-sub">{{ translate('Progress_out_of') }} {{ $leaderboard['total_employees'] ?? 0 }}</div>
                        </div>
                        <div class="rank-val">#{{ $leaderboard['overall_rank'] }}</div>
                    </div>
                @else
                    <div class="rank-item"><div class="rank-meta"><div class="rank-name">{{ translate('No_data_available') }}</div></div></div>
                @endif
            @endforelse
        </div>

        @if($crossInsights !== [])
            <div class="insight-list">
                <div class="section-label" style="margin:0 0 8px">
                    <span class="section-label-text">{{ translate('Progress_improvements') ?? 'Insights' }}</span>
                    @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'progress_insights', 'size' => 'xs'])
                    <span class="section-label-rule" aria-hidden="true"></span>
                </div>
                @foreach($crossInsights as $insight)
                    @php $cls = match ($insight['priority'] ?? 'low') { 'high' => 'danger', 'medium' => 'warning', default => 'success' }; @endphp
                    <div class="insight-item {{ $cls }} {{ ! empty($insight['tab']) ? 'is-jump' : '' }}"
                         @if(! empty($insight['tab'])) data-jump-tab="{{ $insight['tab'] }}" role="button" tabindex="0" @endif>
                        @include('adminmodule::partials._material-icon', ['name' => 'lightbulb', 'class' => 'mso'])
                        <div><strong>{{ $insight['title'] }}</strong>@if(! empty($insight['detail'])) — {{ $insight['detail'] }}@endif</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
