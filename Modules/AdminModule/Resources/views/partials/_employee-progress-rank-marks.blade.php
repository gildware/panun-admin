@php
    $allMarks = $marks ?? [];
    $selfMarks = array_values(array_filter(
        $allMarks,
        static fn (array $mark): bool => ! empty($mark['positive']),
    ));
    $penaltyMarks = array_values(array_filter(
        $allMarks,
        static fn (array $mark): bool => empty($mark['positive']),
    ));
    $helpedMarks = array_values($helpedMarks ?? []);
    $quantityScore = (int) ($quantityScore ?? 0);
    $helpedScore = (int) ($helpedScore ?? 0);
    $penaltyScore = (int) ($penaltyScore ?? 0);
    $grandScore = (int) ($grandScore ?? ($quantityScore + $helpedScore + $penaltyScore));
    $activeOpenLeads = (int) ($activeOpenLeads ?? 0);
    $activeBookings = (int) ($activeBookings ?? 0);
    $showActiveAssignments = $activeOpenLeads > 0 || $activeBookings > 0;
    $rankMetricEmployeeId = (string) ($rankMetricEmployeeId ?? '');
    $rankMetricPeriodParams = $rankMetricPeriodParams ?? [];
    $rankMetricEmployeeQuery = $rankMetricEmployeeQuery ?? [];
    $rankMetricLinksEnabled = ! empty($rankMetricLinksEnabled)
        && $rankMetricEmployeeId !== ''
        && $rankMetricPeriodParams !== [];
    $formatPoints = static function (int $points): string {
        if ($points > 0) {
            return '+'.$points;
        }
        if ($points < 0) {
            return (string) $points;
        }

        return '0';
    };
    $metricDetailUrl = static function (array $mark) use (
        $rankMetricLinksEnabled,
        $rankMetricEmployeeId,
        $rankMetricPeriodParams,
        $rankMetricEmployeeQuery,
    ): ?string {
        if (! $rankMetricLinksEnabled || (int) ($mark['count'] ?? 0) <= 0) {
            return null;
        }
        $metricKey = (string) ($mark['key'] ?? '');
        if ($metricKey === '') {
            return null;
        }

        return \Modules\AdminModule\Services\EmployeeProgressRankMetricDetailService::detailUrl(
            $metricKey,
            $rankMetricEmployeeId,
            $rankMetricPeriodParams,
            $rankMetricEmployeeQuery,
        );
    };
    $markSections = [
        [
            'title' => translate('Progress_marks_self') ?? 'Self',
            'items' => $selfMarks,
            'modifier' => ' rank-marks--self',
        ],
        [
            'title' => translate('Progress_helped_others') ?? 'Helped other',
            'items' => $helpedMarks,
            'modifier' => ' rank-marks--helped',
        ],
        [
            'title' => translate('Penalties') ?? 'Penalties',
            'items' => $penaltyMarks,
            'modifier' => ' rank-marks--penalties',
        ],
    ];
@endphp
@if($showActiveAssignments)
    <div class="rank-marks rank-marks--context">
        <div class="rank-marks-section-title">{{ translate('Progress_active_assignments') ?? 'Active assignments' }}</div>
        <div class="rank-active-assignments">
            <span>{{ $activeOpenLeads }} {{ translate('Progress_open_leads_short') ?? 'open leads' }}</span>
            <span>{{ $activeBookings }} {{ translate('Progress_active_bookings_short') ?? 'active bookings' }}</span>
        </div>
        <p class="rank-active-assignments-hint">
            {{ translate('Progress_active_assignments_hint') ?? 'Currently assigned to you now. Late follow-up penalties apply to these, even when nothing new was received this period.' }}
        </p>
    </div>
@endif
@foreach($markSections as $section)
    <div class="rank-marks{{ $section['modifier'] ?? '' }}">
        @if(! empty($section['title']))
            <div class="rank-marks-section-title">{{ $section['title'] }}</div>
        @endif
        <table class="rank-marks-table">
            <colgroup>
                <col class="col-type">
                <col class="col-qty">
                <col class="col-marks">
                <col class="col-total">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">{{ translate('Type') ?? 'Type' }}</th>
                    <th scope="col">{{ translate('Qty') ?? 'Qty' }}</th>
                    <th scope="col">{{ translate('Marks') ?? 'Marks' }}</th>
                    <th scope="col">{{ translate('Total') ?? 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($section['items'] as $mark)
                    @php
                        $isPlus = ! empty($mark['positive']);
                        $unit = (int) ($mark['unit_points'] ?? 0);
                        $points = (int) ($mark['points'] ?? 0);
                        $unitDisplay = ($isPlus ? '+' : '−').abs($unit);
                        $pointsDisplay = $formatPoints($points);
                        $detailUrl = $metricDetailUrl($mark);
                    @endphp
                    <tr class="{{ $isPlus ? 'is-plus' : 'is-minus' }}{{ $detailUrl ? ' rank-mark-row--link' : '' }}">
                        <td class="rank-mark-type">
                            @if($detailUrl)
                                <a href="{{ $detailUrl }}" class="rank-mark-detail-link" data-turbo="false" title="{{ translate('View_details') ?? 'View details' }}">
                                    {{ $mark['label'] ?? '' }}
                                </a>
                            @else
                                {{ $mark['label'] ?? '' }}
                            @endif
                        </td>
                        <td class="rank-mark-qty">
                            @if($detailUrl)
                                <a href="{{ $detailUrl }}" class="rank-mark-detail-link" data-turbo="false">{{ (int) ($mark['count'] ?? 0) }}</a>
                            @else
                                {{ (int) ($mark['count'] ?? 0) }}
                            @endif
                        </td>
                        <td class="rank-mark-unit">{{ $unitDisplay }}</td>
                        <td class="rank-mark-total">{{ $pointsDisplay }}</td>
                    </tr>
                @empty
                    <tr class="rank-marks-empty-row">
                        <td class="rank-mark-type" colspan="4">{{ translate('No_data_available') ?? 'No data' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endforeach

<div class="rank-marks rank-marks--summary">
    <div class="rank-marks-section-title">{{ translate('Progress_marks_summary') ?? 'Summary' }}</div>
    <table class="rank-marks-table rank-marks-table--summary">
        <colgroup>
            <col class="col-type">
            <col class="col-qty">
            <col class="col-marks">
            <col class="col-total">
        </colgroup>
        <tbody>
            <tr class="is-plus rank-marks-summary-row">
                <td class="rank-mark-type" colspan="3">{{ translate('Progress_marks_total_positive_self') ?? 'Total positive · Self' }}</td>
                <td class="rank-mark-total">{{ $formatPoints($quantityScore) }}</td>
            </tr>
            <tr class="is-plus rank-marks-summary-row rank-marks-summary-row--help">
                <td class="rank-mark-type" colspan="3">{{ translate('Progress_marks_total_positive_help') ?? 'Total positive · Help' }}</td>
                <td class="rank-mark-total">{{ $formatPoints($helpedScore) }}</td>
            </tr>
            <tr class="is-minus rank-marks-summary-row">
                <td class="rank-mark-type" colspan="3">{{ translate('Progress_marks_total_negative') ?? 'Total negative' }}</td>
                <td class="rank-mark-total">{{ $formatPoints($penaltyScore) }}</td>
            </tr>
            <tr class="rank-marks-summary-row rank-marks-summary-row--grand">
                <td class="rank-mark-type" colspan="3">{{ translate('Progress_grand_total') ?? 'Grand total' }}</td>
                <td class="rank-mark-total">{{ $formatPoints($grandScore) }}</td>
            </tr>
        </tbody>
    </table>
</div>
