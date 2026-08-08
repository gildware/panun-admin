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
    $showContributionTotals = ! empty($showContributionTotals);
    $activityTeamTotals = $showContributionTotals ? ($activityTeamTotals ?? []) : [];
    $activityMetricColumns = $activityMetricColumns ?? [];
    $viewingAllEmployees = ! empty($viewingAllEmployees);
    $toneMap = ['good' => 'success', 'warning' => 'warning', 'warn' => 'warning', 'danger' => 'danger', 'brand' => ''];

    $kpiByKey = collect($kpis)->keyBy('key');
    $pickKpi = function (string $key, ?string $fallbackLabel = null, $fallbackValue = 0, string $icon = 'insights', string $tone = 'brand', $total = null) use ($kpiByKey, $showContributionTotals) {
        if ($kpiByKey->has($key)) {
            $kpi = $kpiByKey->get($key);
            if ($showContributionTotals && $total !== null) {
                $kpi['total'] = $total;
            } elseif (! $showContributionTotals) {
                $kpi['total'] = null;
            }

            return $kpi;
        }

        return [
            'key' => $key,
            'label' => $fallbackLabel ?? $key,
            'value' => is_numeric($fallbackValue) ? number_format((int) $fallbackValue) : $fallbackValue,
            'raw' => $fallbackValue,
            'total' => $showContributionTotals ? $total : null,
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
        $pickKpi(
            'leads_added',
            translate('Leads_added'),
            $activityTotals['leads_added'] ?? 0,
            'contact_page',
            'brand',
            $showContributionTotals ? ($activityTeamTotals['leads_added'] ?? null) : null
        ),
        $pickKpi(
            'bookings_created',
            translate('Bookings_created'),
            $activityTotals['bookings_created'] ?? 0,
            'event',
            'brand',
            $showContributionTotals ? ($activityTeamTotals['bookings_created'] ?? null) : null
        ),
        $pickKpi('completion_rate', translate('completion_rate'), ($analytics['summary']['completion_rate'] ?? 0).'%', 'percent', 'good'),
        $pickKpi('followup_accuracy', translate('Follow_up_accuracy'), ($overallFu['accuracy_pct'] ?? ($analytics['summary']['discipline_pct'] ?? 100)).'%', 'task_alt', 'good'),
    ];

    $breakdownByKey = collect($bookingStatusBreakdown)->keyBy('key');
    $statusCount = fn (string $key): int => (int) ($breakdownByKey->get($key)['count'] ?? 0);
    $bookingPending = $statusCount('pending');
    $bookingCompleted = $statusCount('completed');
    $bookingCancelled = $statusCount('canceled') + $statusCount('cancelled_after_visit');
    $bookingOnHold = $statusCount('on_hold');
    $bookingHoldAfterVisit = $statusCount('hold_after_visit');
    $bookingDisputed = $statusCount('disputed') + $statusCount('disputed_cancelled') + $statusCount('disputed_completed');
    $bookingLoss = $statusCount('loss_making') + $statusCount('loss_recovered') + $statusCount('loss_settled');
    $bookingCreated = (int) ($activityTotals['bookings_created'] ?? ($breakdownByKey->get('handled')['count'] ?? 0));

    $leadsHandled = (int) ($leadAnalytics['total_handled'] ?? ($activityTotals['leads_handled'] ?? 0));
    $leadsAdded = (int) ($activityTotals['leads_added'] ?? 0);
    $customerBooked = (int) ($customer['booked'] ?? 0);
    $customerCancelled = (int) ($customer['cancelled'] ?? 0);
    $customerConversion = (float) ($customer['conversion_rate'] ?? 0);

    $leadFuDone = (int) ($leadSection['total_done'] ?? 0);
    $leadFuAccuracy = (float) ($leadSection['accuracy_pct'] ?? 100);
    $leadFuMissed = (int) ($leadSection['missed'] ?? 0);
    $leadFuLate = (int) ($leadSection['late'] ?? 0);

    $bkFuDone = (int) ($bookingFuSection['total_done'] ?? 0);
    $bkFuAccuracy = (float) ($bookingFuSection['accuracy_pct'] ?? 100);
    $bkFuMissed = (int) ($bookingFuSection['missed'] ?? 0);
    $bkFuLate = (int) ($bookingFuSection['late'] ?? 0);

    $activityKeys = collect($activityMetricColumns)->pluck('key')->all();
    $totalActions = collect($activityKeys)->sum(fn ($key) => (int) ($activityTotals[$key] ?? 0));
    $waAssigned = (int) ($activityTotals['whatsapp_assigned'] ?? 0);
    $callLogs = (int) ($activityTotals['call_logs'] ?? 0);

    $teamBookingCreated = $showContributionTotals
        ? (int) ($breakdownByKey->get('handled')['total'] ?? ($activityTeamTotals['bookings_created'] ?? 0))
        : null;
    $teamBookingCompleted = $showContributionTotals
        ? (int) ($breakdownByKey->get('completed')['total'] ?? 0)
        : null;
    $teamBookingPending = $showContributionTotals
        ? (int) ($breakdownByKey->get('pending')['total'] ?? 0)
        : null;
    $teamLeadsHandled = $showContributionTotals
        ? (int) ($leadAnalytics['team_total_handled'] ?? ($activityTeamTotals['leads_handled'] ?? 0))
        : null;
    $teamLeadsAdded = $showContributionTotals
        ? (int) ($activityTeamTotals['leads_added'] ?? 0)
        : null;
    $teamCustomerBooked = $showContributionTotals
        ? (int) ($customer['team_booked'] ?? 0)
        : null;
    $teamLeadFuDone = $showContributionTotals
        ? (int) ($leadSection['team_total_done'] ?? 0)
        : null;
    $teamLeadFuMissed = $showContributionTotals
        ? (int) ($leadSection['team_missed'] ?? 0)
        : null;
    $teamBkFuDone = $showContributionTotals
        ? (int) ($bookingFuSection['team_total_done'] ?? 0)
        : null;
    $teamBkFuMissed = $showContributionTotals
        ? (int) ($bookingFuSection['team_missed'] ?? 0)
        : null;
    $teamTotalActions = $showContributionTotals
        ? collect($activityKeys)->sum(fn ($key) => (int) ($activityTeamTotals[$key] ?? 0))
        : null;

    $snapshotCards = [
        [
            'tab' => 'bookings',
            'title' => translate('Bookings'),
            'icon' => 'event_note',
            'helpKey' => 'overview_snap_bookings',
            'tone' => ($bookingDisputed + $bookingLoss + $bookingCancelled) > $bookingCompleted
                ? 'danger'
                : (($bookingPending + $bookingOnHold + $bookingHoldAfterVisit) > 0 ? 'warning' : 'good'),
            'primary' => number_format($bookingCreated),
            'primary_total' => $teamBookingCreated,
            'primary_label' => translate('Bookings_created'),
            'stats' => [
                ['label' => translate('Bookings_completed'), 'value' => $bookingCompleted, 'total' => $teamBookingCompleted],
                ['label' => translate('Pending'), 'value' => $bookingPending, 'total' => $teamBookingPending],
            ],
            'insight' => $bookingDisputed > 0
                ? (translate('Progress_overview_booking_dispute_insight') ?? 'Disputed bookings need review.')
                : ($bookingLoss > 0
                    ? (translate('Progress_overview_booking_loss_insight') ?? 'Loss-making bookings need recovery follow-up.')
                    : ($bookingCancelled > 0 && $bookingCancelled >= max(1, $bookingCompleted)
                        ? (translate('Progress_overview_booking_cancel_insight') ?? 'Cancellations are high vs completions — review booking outcomes.')
                        : ($bookingPending + $bookingOnHold + $bookingHoldAfterVisit > 0
                            ? (translate('Progress_overview_booking_pending_insight') ?? 'Open bookings still need follow-through.')
                            : (translate('Progress_overview_booking_good_insight') ?? 'Booking pipeline looks steady for this period.')))),
        ],
        [
            'tab' => 'leads',
            'title' => translate('Leads'),
            'icon' => 'group',
            'helpKey' => 'overview_snap_leads',
            'tone' => $customerConversion >= 30 ? 'good' : ($customerCancelled > $customerBooked ? 'danger' : 'warning'),
            'primary' => number_format(max($leadsHandled, $leadsAdded)),
            'primary_total' => $leadsHandled > 0 ? $teamLeadsHandled : $teamLeadsAdded,
            'primary_label' => $leadsHandled > 0
                ? (translate('Progress_leads_handled') ?? translate('Leads_Handled'))
                : translate('Leads_added'),
            'stats' => [
                ['label' => translate('Progress_converted') ?? 'Converted', 'value' => $customerBooked, 'total' => $teamCustomerBooked],
                ['label' => translate('completion_rate'), 'value' => rtrim(rtrim(number_format($customerConversion, 1), '0'), '.').'%', 'is_percent' => true],
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
            'primary_total' => $teamLeadFuDone,
            'primary_label' => translate('Progress_followups_done') ?? translate('Follow_ups'),
            'stats' => [
                ['label' => translate('Follow_up_accuracy'), 'value' => rtrim(rtrim(number_format($leadFuAccuracy, 1), '0'), '.').'%', 'is_percent' => true],
                ['label' => translate('Progress_missed_followups'), 'value' => $leadFuMissed, 'total' => $teamLeadFuMissed],
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
            'primary_total' => $teamBkFuDone,
            'primary_label' => translate('Progress_followups_done') ?? translate('Follow_ups'),
            'stats' => [
                ['label' => translate('Follow_up_accuracy'), 'value' => rtrim(rtrim(number_format($bkFuAccuracy, 1), '0'), '.').'%', 'is_percent' => true],
                ['label' => translate('Progress_missed_followups'), 'value' => $bkFuMissed, 'total' => $teamBkFuMissed],
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
            'primary_total' => $teamTotalActions,
            'primary_label' => translate('Total_actions') ?? 'Total actions',
            'stats' => [
                ['label' => translate('WhatsApp_Chats_Assigned'), 'value' => $waAssigned, 'total' => $showContributionTotals ? (int) ($activityTeamTotals['whatsapp_assigned'] ?? 0) : null],
                ['label' => translate('Call_Logs_Added'), 'value' => $callLogs, 'total' => $showContributionTotals ? (int) ($activityTeamTotals['call_logs'] ?? 0) : null],
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
        ->take(3)
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
                    <div class="overview-snap-val">
                        @include('adminmodule::partials._employee-progress-metric-value', [
                            'count' => (int) str_replace(',', '', (string) $card['primary']),
                            'total' => $card['primary_total'] ?? null,
                            'displayValue' => str_contains((string) $card['primary'], '%') ? (string) $card['primary'] : null,
                            'ofClass' => 'mc-of',
                        ])
                    </div>
                    <div class="overview-snap-lbl">{{ $card['primary_label'] }}</div>
                </div>
                <div class="overview-snap-stats">
                    @foreach($card['stats'] as $stat)
                        <div class="overview-snap-stat">
                            <span>{{ $stat['label'] }}</span>
                            <strong>
                                @include('adminmodule::partials._employee-progress-metric-value', [
                                    'count' => is_numeric($stat['value'] ?? null) ? (int) $stat['value'] : 0,
                                    'total' => ! empty($stat['is_percent']) ? null : ($stat['total'] ?? null),
                                    'displayValue' => ! empty($stat['is_percent']) || (isset($stat['value']) && ! is_numeric($stat['value']))
                                        ? (string) $stat['value']
                                        : null,
                                    'ofClass' => 'mc-of',
                                ])
                            </strong>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</div>

@php
    $scoreWeights = $analytics['score_weights'] ?? \Modules\AdminModule\Services\EmployeeProgressScoreService::weightLegend();
    $rankRows = collect($topPerformers);
    if (! $viewingAllEmployees) {
        $rankRows = $rankRows->take(1);
    }
    $maxScore = max(1, (int) ($rankRows->first()['score'] ?? 1), abs((int) ($rankRows->min('score') ?? 0)));
@endphp

<div class="layout-main {{ $viewingAllEmployees ? '' : 'layout-main--half' }}">
    <div class="chart-card">
        @include('adminmodule::partials._employee-progress-chart-head', [
            'icon' => 'show_chart',
            'title' => translate('Progress_booking_trend') ?? 'Booking Trend',
            'subtitle' => translate('Progress_booking_trend_sub') ?? (translate('Pending').' · '.(translate('On_hold') ?? 'On hold').' · '.(translate('Hold_after_visit') ?? 'Hold after visit').' · '.translate('Cancelled').' · '.(translate('Disputed') ?? 'Disputed').' · '.(translate('Loss_making') ?? 'Loss')),
            'helpKey' => 'chart_booking_trend',
        ])
        <div class="chart-card-body"><div id="chart-overview-booking-trend" class="chart-trend"></div></div>
    </div>

    @if(! $viewingAllEmployees)
        <div class="rank-card rank-card--panel">
            <div class="rank-head">
                <span>{{ translate('Progress_team_ranking') }}</span>
                @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'team_ranking', 'size' => 'xs'])
            </div>
            @if($scoreWeights !== [])
                <div class="rank-score-legend">
                    @foreach($scoreWeights as $weight)
                        <span class="{{ ($weight['sign'] ?? '+') === '+' ? 'is-plus' : 'is-minus' }}">
                            {{ $weight['sign'] ?? '+' }}{{ $weight['points'] ?? 0 }} {{ $weight['label'] ?? '' }}
                        </span>
                    @endforeach
                </div>
            @endif
            @forelse($rankRows as $index => $performer)
                @php
                    $initials = collect(explode(' ', $performer['name'] ?? ''))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                    $avatarClass = match ($index) { 1 => 'silver', 2 => 'bronze', default => '' };
                    $barPct = min(100, round((abs((int) ($performer['score'] ?? 0)) / $maxScore) * 100));
                @endphp
                <div class="rank-item rank-item--scored">
                    <div class="rank-item-main">
                        <div class="avatar {{ $avatarClass }}">{{ $initials ?: '#'.($performer['rank'] ?? ($index + 1)) }}</div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ $performer['name'] }}</div>
                            <div class="rank-sub">
                                {{ translate('Quantity') ?? 'Quantity' }} {{ (int) ($performer['quantity_score'] ?? 0) }}
                                @if((int) ($performer['helped_score'] ?? 0) > 0)
                                    · {{ translate('Progress_helped_others') ?? 'Helped other' }} {{ (int) ($performer['helped_score'] ?? 0) }}
                                @endif
                                · {{ translate('Penalties') ?? 'Penalties' }} {{ (int) ($performer['penalty_score'] ?? 0) }}
                            </div>
                            <div class="rank-bar"><i style="width: {{ $barPct }}%"></i></div>
                        </div>
                        <div class="rank-val">{{ (int) ($performer['score'] ?? 0) }}</div>
                    </div>
                    @include('adminmodule::partials._employee-progress-rank-marks', [
                        'marks' => $performer['marks'] ?? [],
                        'helpedMarks' => $performer['helped_marks'] ?? [],
                        'quantityScore' => (int) ($performer['quantity_score'] ?? 0),
                        'helpedScore' => (int) ($performer['helped_score'] ?? 0),
                        'penaltyScore' => (int) ($performer['penalty_score'] ?? 0),
                        'grandScore' => (int) ($performer['score'] ?? 0),
                    ])
                </div>
            @empty
                <div class="rank-item"><div class="rank-meta"><div class="rank-name">{{ translate('No_data_available') }}</div></div></div>
            @endforelse
        </div>
    @elseif($crossInsights !== [])
        <div class="side-stack">
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
        </div>
    @endif
</div>

@if(! $viewingAllEmployees && $crossInsights !== [])
    <div class="insight-list insight-list--below">
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

@if($viewingAllEmployees)
    <div class="rank-card rank-card--team-row">
        <div class="rank-head">
            <span>{{ translate('Progress_team_ranking') }}</span>
            @include('adminmodule::partials._employee-progress-info-btn', ['helpKey' => 'team_ranking', 'size' => 'xs'])
        </div>
        @if($scoreWeights !== [])
            <div class="rank-score-legend">
                @foreach($scoreWeights as $weight)
                    <span class="{{ ($weight['sign'] ?? '+') === '+' ? 'is-plus' : 'is-minus' }}">
                        {{ $weight['sign'] ?? '+' }}{{ $weight['points'] ?? 0 }} {{ $weight['label'] ?? '' }}
                    </span>
                @endforeach
            </div>
        @endif
        <div class="rank-row">
            @forelse($rankRows as $index => $performer)
                @php
                    $initials = collect(explode(' ', $performer['name'] ?? ''))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                    $avatarClass = match ($index) { 1 => 'silver', 2 => 'bronze', default => '' };
                    $barPct = min(100, round((abs((int) ($performer['score'] ?? 0)) / $maxScore) * 100));
                @endphp
                <div class="rank-item rank-item--scored rank-item--card">
                    <div class="rank-item-main">
                        <div class="avatar {{ $avatarClass }}">{{ $initials ?: '#'.($performer['rank'] ?? ($index + 1)) }}</div>
                        <div class="rank-meta">
                            <div class="rank-name">{{ $performer['name'] }}</div>
                            <div class="rank-sub">
                                {{ translate('Quantity') ?? 'Quantity' }} {{ (int) ($performer['quantity_score'] ?? 0) }}
                                @if((int) ($performer['helped_score'] ?? 0) > 0)
                                    · {{ translate('Progress_helped_others') ?? 'Helped other' }} {{ (int) ($performer['helped_score'] ?? 0) }}
                                @endif
                                · {{ translate('Penalties') ?? 'Penalties' }} {{ (int) ($performer['penalty_score'] ?? 0) }}
                            </div>
                            <div class="rank-bar"><i style="width: {{ $barPct }}%"></i></div>
                        </div>
                        <div class="rank-val">{{ (int) ($performer['score'] ?? 0) }}</div>
                    </div>
                    @include('adminmodule::partials._employee-progress-rank-marks', [
                        'marks' => $performer['marks'] ?? [],
                        'helpedMarks' => $performer['helped_marks'] ?? [],
                        'quantityScore' => (int) ($performer['quantity_score'] ?? 0),
                        'helpedScore' => (int) ($performer['helped_score'] ?? 0),
                        'penaltyScore' => (int) ($performer['penalty_score'] ?? 0),
                        'grandScore' => (int) ($performer['score'] ?? 0),
                    ])
                </div>
            @empty
                <div class="rank-item rank-item--card"><div class="rank-meta"><div class="rank-name">{{ translate('No_data_available') }}</div></div></div>
            @endforelse
        </div>
    </div>
@endif
