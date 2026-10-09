@php
    $rows = $rows ?? [];
    $highlightEmployeeId = $highlightEmployeeId ?? null;
    $rankMetricPeriodParams = $rankMetricPeriodParams ?? [];
    $rankMetricEmployeeQuery = $rankMetricEmployeeQuery ?? [];
    $rankMetricLinksEnabled = ! empty($rankMetricLinksEnabled);

    $legend = collect(\Modules\AdminModule\Services\EmployeeProgressScoreService::weightLegend())->keyBy('key');
    $groups = \Modules\AdminModule\Services\EmployeeProgressRankMetricDetailService::metricGroups();
    $columns = [];
    foreach ($groups as $group) {
        foreach ($group['metric_keys'] as $metricKey) {
            $item = $legend->get($metricKey, []);
            $sign = (string) ($item['sign'] ?? '+');
            $columns[] = [
                'key' => $metricKey,
                'group' => (string) ($group['key'] ?? ''),
                'label' => (string) ($item['label'] ?? $metricKey),
                'points' => (int) ($item['points'] ?? 0),
                'positive' => $sign !== '−' && $sign !== '-',
            ];
        }
    }
    $groupOrder = [];
    foreach ($columns as $column) {
        $groupKey = $column['group'];
        if (! isset($groupOrder[$groupKey])) {
            $groupLabel = collect($groups)->firstWhere('key', $groupKey)['label'] ?? $groupKey;
            $groupOrder[$groupKey] = [
                'key' => $groupKey,
                'label' => $groupLabel,
                'span' => 0,
            ];
        }
        $groupOrder[$groupKey]['span']++;
    }

    $formatPoints = static function (int $points): string {
        if ($points > 0) {
            return '+'.$points;
        }
        if ($points < 0) {
            return (string) $points;
        }

        return '0';
    };
@endphp
@if($rows === [])
    <div class="progress-empty">{{ translate('Progress_solo_team') }}</div>
@else
    <div class="team-rank-table-wrap">
        <table class="team-rank-table">
            <thead>
                <tr class="team-rank-table-groups">
                    <th class="team-rank-sticky team-rank-sticky--rank" rowspan="2" scope="col">#</th>
                    <th class="team-rank-sticky team-rank-sticky--name" rowspan="2" scope="col">{{ translate('Employee') ?? 'Employee' }}</th>
                    <th class="team-rank-sticky team-rank-sticky--score" rowspan="2" scope="col">{{ translate('Score') ?? 'Score' }}</th>
                    @foreach($groupOrder as $group)
                        <th class="team-rank-group team-rank-group--{{ $group['key'] }}" colspan="{{ $group['span'] }}" scope="colgroup">{{ $group['label'] }}</th>
                    @endforeach
                    <th class="team-rank-group team-rank-group--active" colspan="2" scope="colgroup">{{ translate('Progress_active_assignments') ?? 'Active assignments' }}</th>
                    <th class="team-rank-group team-rank-group--summary" colspan="3" scope="colgroup">{{ translate('Progress_marks_summary') ?? 'Summary' }}</th>
                </tr>
                <tr class="team-rank-table-metrics">
                    @foreach($columns as $column)
                        <th class="team-rank-metric-head team-rank-metric-head--{{ $column['group'] }}" scope="col" title="{{ $column['label'] }} · {{ $column['positive'] ? '+' : '−' }}{{ $column['points'] }}">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                    <th class="team-rank-metric-head team-rank-metric-head--active" scope="col">{{ translate('Progress_open_leads_short') ?? 'Open leads' }}</th>
                    <th class="team-rank-metric-head team-rank-metric-head--active" scope="col">{{ translate('Progress_active_bookings_short') ?? 'Active bookings' }}</th>
                    <th class="team-rank-metric-head team-rank-metric-head--summary" scope="col">{{ translate('Quantity') ?? 'Quantity' }}</th>
                    <th class="team-rank-metric-head team-rank-metric-head--summary" scope="col">{{ translate('Progress_helped_others') ?? 'Helped other' }}</th>
                    <th class="team-rank-metric-head team-rank-metric-head--summary" scope="col">{{ translate('Penalties') ?? 'Penalties' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                    @php
                        $rowEmployeeId = (string) ($row['employee_id'] ?? '');
                        $isHighlighted = $highlightEmployeeId && (string) $highlightEmployeeId === $rowEmployeeId;
                        $initials = collect(explode(' ', $row['label'] ?? ''))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                        $canLinkRankMetrics = $rankMetricLinksEnabled
                            && $rowEmployeeId !== ''
                            && $rankMetricPeriodParams !== []
                            && (! is_admin_employee() || (string) auth()->id() === $rowEmployeeId);
                        $canViewEmployeeReport = $rowEmployeeId !== ''
                            && (! is_admin_employee() || (string) auth()->id() === $rowEmployeeId);
                        $employeeReportUrl = $canViewEmployeeReport && $rankMetricPeriodParams !== []
                            ? \Modules\AdminModule\Services\EmployeeProgressRankMetricDetailService::employeeReportUrl(
                                $rowEmployeeId,
                                $rankMetricPeriodParams,
                                $rankMetricEmployeeQuery,
                            )
                            : null;
                        $markMap = [];
                        foreach (array_merge($row['marks'] ?? [], $row['helped_marks'] ?? []) as $mark) {
                            $markKey = (string) ($mark['key'] ?? '');
                            if ($markKey !== '') {
                                $markMap[$markKey] = $mark;
                            }
                        }
                        $score = (int) ($row['score'] ?? 0);
                    @endphp
                    <tr class="{{ $isHighlighted ? 'is-highlighted' : '' }}">
                        <th class="team-rank-sticky team-rank-sticky--rank team-rank-num" scope="row">{{ (int) ($row['rank'] ?? ($index + 1)) }}</th>
                        <td class="team-rank-sticky team-rank-sticky--name">
                            <div class="team-rank-person">
                                <span class="avatar">{{ $initials !== '' ? $initials : '#'.($row['rank'] ?? ($index + 1)) }}</span>
                                <span class="team-rank-person-name" title="{{ $row['label'] ?? '' }}">{{ $row['label'] ?? '' }}</span>
                                @if($employeeReportUrl)
                                    <a href="{{ $employeeReportUrl }}" class="team-rank-report-link" data-turbo="false" title="{{ translate('View_full_report') ?? 'View full report' }}">
                                        <span class="material-symbols-outlined">description</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="team-rank-sticky team-rank-sticky--score team-rank-score {{ $score > 0 ? 'is-plus' : ($score < 0 ? 'is-minus' : '') }}">{{ $formatPoints($score) }}</td>
                        @foreach($columns as $column)
                            @php
                                $mark = $markMap[$column['key']] ?? null;
                                $count = (int) ($mark['count'] ?? 0);
                                $points = (int) ($mark['points'] ?? 0);
                                $detailUrl = null;
                                if ($canLinkRankMetrics && $count > 0) {
                                    $detailUrl = \Modules\AdminModule\Services\EmployeeProgressRankMetricDetailService::detailUrl(
                                        $column['key'],
                                        $rowEmployeeId,
                                        $rankMetricPeriodParams,
                                        $rankMetricEmployeeQuery,
                                    );
                                }
                                $tone = $points > 0 ? 'is-plus' : ($points < 0 ? 'is-minus' : 'is-zero');
                                if ($column['group'] === 'helped' && $points > 0) {
                                    $tone .= ' is-help';
                                }
                            @endphp
                            <td class="team-rank-metric {{ $tone }} {{ $column['group'] === 'penalties' ? 'is-penalty-col' : '' }}">
                                @if($detailUrl)
                                    <a href="{{ $detailUrl }}" class="team-rank-metric-link" data-turbo="false" title="{{ translate('View_details') ?? 'View details' }}">
                                        <span class="team-rank-pts">{{ $formatPoints($points) }}</span>
                                        <span class="team-rank-qty">{{ $count }}</span>
                                    </a>
                                @else
                                    <span class="team-rank-pts">{{ $formatPoints($points) }}</span>
                                    @if($count > 0)
                                        <span class="team-rank-qty">{{ $count }}</span>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                        <td class="team-rank-count">{{ (int) ($row['active_open_leads'] ?? 0) }}</td>
                        <td class="team-rank-count">{{ (int) ($row['active_bookings'] ?? 0) }}</td>
                        <td class="team-rank-subtotal is-plus">{{ $formatPoints((int) ($row['quantity_score'] ?? 0)) }}</td>
                        <td class="team-rank-subtotal is-help">{{ $formatPoints((int) ($row['helped_score'] ?? 0)) }}</td>
                        <td class="team-rank-subtotal is-minus">{{ $formatPoints((int) ($row['penalty_score'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
