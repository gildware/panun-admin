@php
    $analytics = $analytics ?? [];
    $charts = $analytics['charts'] ?? [];
    $kpis = $analytics['kpis'] ?? [];
    $topPerformers = $analytics['top_performers'] ?? [];
    $insights = $analytics['insights'] ?? [];
    $agingBuckets = $analytics['aging_buckets'] ?? [];
    $recentBookings = $analytics['recent_bookings'] ?? [];
    $employeeSummary = $analytics['employee_summary'] ?? [];
    $scoreTiles = $analytics['score_tiles'] ?? [];
    $revenueRows = $analytics['revenue_rows'] ?? [];
    $leadAnalytics = $leadAnalytics ?? [];
    $leadCharts = $leadAnalytics['charts'] ?? [];
    $followupAnalytics = $followupAnalytics ?? [];
    $followupCharts = $followupAnalytics['charts'] ?? [];
    $fullReport = $fullReport ?? [];
    $leaderboard = $fullReport['leaderboard'] ?? [];
    $contribution = $fullReport['contribution'] ?? [];
    $teamRankRows = $fullReport['team_rank_rows'] ?? [];
    $viewingTeam = ! empty($fullReport['viewing_team']);
    $activeSection = $activeSection ?? (function () {
        $section = request('section');
        if ($section === 'operations') {
            return 'bookings';
        }
        if ($section === 'followups') {
            return 'lead-followups';
        }
        if ($section === 'reports') {
            return 'daily-basis';
        }

        return in_array($section, ['overview', 'bookings', 'leads', 'lead-followups', 'booking-followups', 'daily-basis'], true) ? $section : 'overview';
    })();
    $overviewKpiKeys = ['leads_added', 'bookings_created', 'completed_bookings', 'completed_amount', 'completion_rate', 'cancelled_bookings', 'data_quality'];
    $overviewKpis = collect($kpis)->filter(fn ($kpi) => in_array($kpi['key'] ?? '', $overviewKpiKeys, true))->values()->all();
    $toneMap = ['good' => 'success', 'warning' => 'warning', 'warn' => 'warning', 'danger' => 'danger'];
@endphp

{{-- OVERVIEW --}}
<div class="tab-panel {{ $activeSection === 'overview' ? 'on' : '' }}" id="tab-overview">
    @include('adminmodule::partials._employee-progress-overview', [
        'analytics' => $analytics,
        'leadAnalytics' => $leadAnalytics,
        'followupAnalytics' => $followupAnalytics,
        'fullReport' => $fullReport,
        'activityTotals' => $activityTotals ?? [],
        'activityTeamTotals' => $activityTeamTotals ?? [],
        'activityMetricColumns' => $activityMetricColumns ?? [],
        'viewingAllEmployees' => $viewingAllEmployees ?? false,
        'showContributionTotals' => $showContributionTotals ?? ! ($viewingAllEmployees ?? false),
        'tab' => $tab ?? 'monthly',
        'date' => $date ?? null,
        'dateFrom' => $dateFrom ?? null,
        'dateTo' => $dateTo ?? null,
        'employeeQuery' => $employeeQuery ?? [],
    ])
</div>

{{-- BOOKINGS --}}
<div class="tab-panel {{ $activeSection === 'bookings' ? 'on' : '' }}" id="tab-bookings">
    @include('adminmodule::partials._employee-progress-booking-status', ['analytics' => $analytics])

    <div class="chart-card">
        @include('adminmodule::partials._employee-progress-chart-head', [
            'icon' => 'show_chart',
            'title' => translate('Progress_booking_trend') ?? 'Booking Trend',
            'subtitle' => translate('Progress_booking_trend_sub') ?? (translate('Pending').' · '.(translate('On_hold') ?? 'On hold').' · '.(translate('Hold_after_visit') ?? 'Hold after visit').' · '.translate('Cancelled').' · '.(translate('Disputed') ?? 'Disputed').' · '.(translate('Loss_making') ?? 'Loss')),
            'helpKey' => 'chart_booking_trend',
        ])
        <div class="chart-card-body"><div id="chart-bookings-trend" class="chart-trend"></div></div>
    </div>

    @include('adminmodule::partials._employee-progress-booking-reason-reports', ['analytics' => $analytics])
</div>

{{-- LEADS --}}
<div class="tab-panel {{ $activeSection === 'leads' ? 'on' : '' }}" id="tab-leads">
    @include('adminmodule::partials._employee-progress-lead-analytics', [
        'leadAnalytics' => $leadAnalytics ?? [],
        'periodLabel' => $periodLabel ?? null,
        'dateLabel' => $dateLabel ?? null,
    ])
</div>

{{-- LEAD FOLLOW-UPS --}}
<div class="tab-panel {{ $activeSection === 'lead-followups' ? 'on' : '' }}" id="tab-lead-followups">
    @include('adminmodule::partials._employee-progress-lead-followup-analytics', [
        'followupAnalytics' => $followupAnalytics ?? [],
        'periodLabel' => $periodLabel ?? null,
        'dateLabel' => $dateLabel ?? null,
    ])
</div>

{{-- BOOKING FOLLOW-UPS --}}
<div class="tab-panel {{ $activeSection === 'booking-followups' ? 'on' : '' }}" id="tab-booking-followups">
    @include('adminmodule::partials._employee-progress-booking-followup-analytics', [
        'followupAnalytics' => $followupAnalytics ?? [],
        'periodLabel' => $periodLabel ?? null,
        'dateLabel' => $dateLabel ?? null,
    ])
</div>

{{-- DAILY BASIS REPORT --}}
<div class="tab-panel {{ $activeSection === 'daily-basis' ? 'on' : '' }}" id="tab-daily-basis">
    @include('adminmodule::partials._employee-progress-daily-basis', [
        'tab' => $tab ?? 'monthly',
        'activityMetricColumns' => $activityMetricColumns ?? [],
        'activityTotals' => $activityTotals ?? [],
        'activityTeamTotals' => $activityTeamTotals ?? [],
        'showContributionTotals' => $showContributionTotals ?? ! ($viewingAllEmployees ?? false),
        'activityDailyRows' => $activityDailyRows ?? [],
        'dateLabel' => $dateLabel ?? null,
        'periodLabel' => $periodLabel ?? null,
        'employeeQuery' => $employeeQuery ?? [],
    ])
</div>

@push('script')
<script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/admin-module/js/employee-progress-charts.js') }}?v=20260807cg" data-always-activate="1"></script>
<script src="{{ asset('assets/admin-module/js/employee-progress-info.js') }}?v=20260807ae" data-always-activate="1"></script>
<script>
window.PanunProgressHelp = @json($metricHelpRegistry ?? []);
window.PanunProgressCharts = window.PanunProgressCharts || {};
window.PanunProgressCharts.config = {
    charts: @json($charts),
    leadCharts: @json($leadCharts),
    followupCharts: @json($followupCharts),
    kpis: @json($kpis),
    labels: {
        bookings: @json(translate('Bookings_created')),
        completed: @json(translate('Bookings_completed')),
        cancelled: @json(translate('Cancelled')),
        leads: @json(translate('Leads_added')),
        leadFollowups: @json(translate('Lead_followups')),
        bookingFollowups: @json(translate('Booking_Followups')),
        done: @json(translate('Progress_followups_done') ?? 'Done'),
        onTime: @json(translate('Progress_on_time_followups') ?? translate('Follow_up_accuracy')),
        late: @json(translate('Progress_late_followups') ?? 'Late'),
        missed: @json(translate('Progress_missed_followups')),
        followups: @json(translate('Follow_ups')),
        total: @json(translate('Total')),
        you: @json(translate('Progress_yours')),
        teamAvg: @json(translate('Progress_team_avg')),
        converted: @json(translate('Progress_converted') ?? translate('Bookings_completed')),
        pending: @json(translate('Pending')),
    }
};

(function () {
    var bound = false;

    function normalizeSection(id) {
        if (id === 'operations') return 'bookings';
        if (id === 'followups') return 'lead-followups';
        if (id === 'reports') return 'daily-basis';
        return id;
    }

    function activateProgressTab(id) {
        id = normalizeSection(id);
        var root = document.querySelector('.emp-progress-report');
        if (!root || !id) return;

        root.querySelectorAll('.shell-tab[data-tab]').forEach(function (t) {
            t.classList.toggle('on', t.getAttribute('data-tab') === id);
        });
        root.querySelectorAll('.tab-panel').forEach(function (p) {
            p.classList.toggle('on', p.id === 'tab-' + id);
        });

        var sectionInputs = root.querySelectorAll('form input[name="section"]');
        sectionInputs.forEach(function (input) {
            input.value = id;
        });

        if (window.PanunProgressCharts && window.PanunProgressCharts.refreshVisible) {
            window.PanunProgressCharts.refreshVisible();
        } else if (window.PanunProgressCharts && window.PanunProgressCharts.init) {
            window.PanunProgressCharts.init();
        }

        if (history.replaceState) {
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('section', id);
                history.replaceState({}, '', url.toString());
            } catch (e) {}
        }
    }

    function bindProgressUi() {
        if (!document.querySelector('.emp-progress-report')) return;

        if (!bound) {
            bound = true;
            document.addEventListener('click', function (e) {
                var root = document.querySelector('.emp-progress-report');
                if (!root || !root.contains(e.target)) return;

                var tab = e.target.closest('.shell-tab[data-tab]');
                if (tab && root.contains(tab)) {
                    e.preventDefault();
                    activateProgressTab(tab.getAttribute('data-tab'));
                    return;
                }

                var jump = e.target.closest('[data-jump-tab]');
                if (jump && root.contains(jump)) {
                    if (e.target.closest('.progress-metric-info-btn')) return;
                    var id = jump.getAttribute('data-jump-tab');
                    if (id) activateProgressTab(id);
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var jump = e.target.closest('[data-jump-tab]');
                if (!jump) return;
                if (e.target.closest('.progress-metric-info-btn')) return;
                var root = document.querySelector('.emp-progress-report');
                if (!root || !root.contains(jump)) return;
                e.preventDefault();
                var id = jump.getAttribute('data-jump-tab');
                if (id) activateProgressTab(id);
            });
        }

        if (window.PanunProgressCharts && window.PanunProgressCharts.scheduleInit) {
            window.PanunProgressCharts.scheduleInit(60);
        } else if (window.PanunProgressCharts && window.PanunProgressCharts.init) {
            window.PanunProgressCharts.init();
        }
    }

    window.PanunProgressActivateTab = activateProgressTab;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindProgressUi);
    } else {
        bindProgressUi();
    }
    document.addEventListener('admin:page-loaded', bindProgressUi);
})();
</script>
@endpush
