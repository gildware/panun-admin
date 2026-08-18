@php
    $rows = $rows ?? [];
    $highlightEmployeeId = $highlightEmployeeId ?? null;
    $variant = $variant ?? 'dash';
    $isOverview = $variant === 'overview';
    $isPanel = $variant === 'panel';
    $maxScore = max(1, ...(array_map(static fn ($r) => abs((int) ($r['score'] ?? 0)), $rows) ?: [1]));
    $wrapperClass = match ($variant) {
        'overview' => 'rank-row team-rank-cards team-rank-cards--overview',
        'panel' => 'team-rank-cards team-rank-cards--panel',
        default => 'team-rank-cards',
    };
    $cardClass = match ($variant) {
        'overview' => 'rank-item rank-item--scored rank-item--card',
        'panel' => 'rank-item rank-item--scored',
        default => 'rank-item rank-item--scored rank-item--dash',
    };
@endphp
@if($rows === [])
    <div class="progress-empty">{{ translate('Progress_solo_team') }}</div>
@else
    <div class="{{ $wrapperClass }}">
        @foreach($rows as $index => $row)
            @php
                $isHighlighted = $highlightEmployeeId && (string) $highlightEmployeeId === (string) ($row['employee_id'] ?? '');
                $initials = collect(explode(' ', $row['label'] ?? ''))->filter()->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
                $avatarClass = match ($index) { 1 => 'silver', 2 => 'bronze', default => '' };
                $barPct = min(100, round((abs((int) ($row['score'] ?? 0)) / $maxScore) * 100));
                $rowEmployeeId = (string) ($row['employee_id'] ?? '');
                $canLinkRankMetrics = ! empty($rankMetricLinksEnabled)
                    && $rowEmployeeId !== ''
                    && (! is_admin_employee() || (string) auth()->id() === $rowEmployeeId);
                $canViewEmployeeReport = $rowEmployeeId !== ''
                    && (! is_admin_employee() || (string) auth()->id() === $rowEmployeeId);
                $employeeReportUrl = $canViewEmployeeReport && ($rankMetricPeriodParams ?? []) !== []
                    ? \Modules\AdminModule\Services\EmployeeProgressRankMetricDetailService::employeeReportUrl(
                        $rowEmployeeId,
                        $rankMetricPeriodParams,
                        $rankMetricEmployeeQuery ?? [],
                    )
                    : null;
            @endphp
            <div class="{{ $cardClass }} {{ $isHighlighted ? 'is-highlighted' : '' }} {{ $employeeReportUrl ? 'rank-item--with-report' : '' }}">
                <div class="rank-item-main">
                    <div class="avatar {{ $avatarClass }}">{{ $initials ?: '#'.($row['rank'] ?? ($index + 1)) }}</div>
                    <div class="rank-meta">
                        <div class="rank-name">{{ $row['label'] }}</div>
                        <div class="rank-sub">
                            {{ translate('Quantity') ?? 'Quantity' }} {{ (int) ($row['quantity_score'] ?? 0) }}
                            @if((int) ($row['helped_score'] ?? 0) > 0)
                                · {{ translate('Progress_helped_others') ?? 'Helped other' }} {{ (int) ($row['helped_score'] ?? 0) }}
                            @endif
                            · {{ translate('Penalties') ?? 'Penalties' }} {{ (int) ($row['penalty_score'] ?? 0) }}
                        </div>
                        @php
                            $activeOpenLeads = (int) ($row['active_open_leads'] ?? 0);
                            $activeBookings = (int) ($row['active_bookings'] ?? 0);
                        @endphp
                        @if($activeOpenLeads > 0 || $activeBookings > 0)
                            <div class="rank-sub rank-sub--active-assignments">
                                {{ translate('Progress_active_assignments') ?? 'Active assignments' }}
                                · {{ $activeOpenLeads }} {{ translate('Progress_open_leads_short') ?? 'open leads' }}
                                · {{ $activeBookings }} {{ translate('Progress_active_bookings_short') ?? 'active bookings' }}
                            </div>
                        @endif
                        <div class="rank-bar"><i style="width: {{ $barPct }}%"></i></div>
                    </div>
                    <div class="rank-item-aside">
                        @if($employeeReportUrl)
                            <a href="{{ $employeeReportUrl }}" class="rank-item-report-btn" data-turbo="false" title="{{ translate('View_full_report') ?? 'View full report' }}">
                                <span class="material-symbols-outlined">description</span>
                                <span class="rank-item-report-btn-label">{{ translate('View_full_report') ?? 'View full report' }}</span>
                            </a>
                        @endif
                        <div class="rank-val">{{ (int) ($row['score'] ?? 0) }}</div>
                    </div>
                </div>
                @include('adminmodule::partials._employee-progress-rank-marks', [
                    'marks' => $row['marks'] ?? [],
                    'helpedMarks' => $row['helped_marks'] ?? [],
                    'quantityScore' => (int) ($row['quantity_score'] ?? 0),
                    'helpedScore' => (int) ($row['helped_score'] ?? 0),
                    'penaltyScore' => (int) ($row['penalty_score'] ?? 0),
                    'grandScore' => (int) ($row['score'] ?? 0),
                    'activeOpenLeads' => (int) ($row['active_open_leads'] ?? 0),
                    'activeBookings' => (int) ($row['active_bookings'] ?? 0),
                    'rankMetricEmployeeId' => $rowEmployeeId,
                    'rankMetricPeriodParams' => $rankMetricPeriodParams ?? [],
                    'rankMetricEmployeeQuery' => $rankMetricEmployeeQuery ?? [],
                    'rankMetricLinksEnabled' => $canLinkRankMetrics,
                ])
            </div>
        @endforeach
    </div>
@endif
