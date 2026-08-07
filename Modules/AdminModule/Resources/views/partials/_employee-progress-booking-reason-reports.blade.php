@php
    $reports = $analytics['booking_reason_reports'] ?? [];
@endphp

@if($reports !== [])
    @include('adminmodule::partials._employee-progress-section-label', [
        'label' => translate('Progress_booking_reason_reports') ?? 'Status reason reports',
        'helpKey' => 'booking_reason_reports_section',
    ])
    <div class="booking-reason-reports">
        @foreach($reports as $report)
            <div class="data-table-wrap booking-reason-report-card">
                @include('adminmodule::partials._employee-progress-section-label', [
                    'label' => $report['label'] ?? '',
                    'helpKey' => $report['help_key'] ?? null,
                ])
                <table class="data-table" style="min-width:0">
                    <thead>
                        <tr>
                            <th>{{ translate('Reason') ?? 'Reason' }}</th>
                            <th>{{ translate('Total') }}</th>
                            <th>{{ translate('Share') ?? '%' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($report['rows'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>
                                    @include('adminmodule::partials._employee-progress-metric-value', [
                                        'count' => $row['count'] ?? 0,
                                        'total' => $row['total'] ?? null,
                                        'ofClass' => 'mc-of',
                                    ])
                                </td>
                                <td>
                                    <div class="cell-bar">
                                        <div class="cell-bar-track">
                                            <div class="cell-bar-fill" style="width: {{ min(100, (float) ($row['pct'] ?? 0)) }}%"></div>
                                        </div>
                                        {{ $row['pct'] }}%
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center;color:#64748b;padding:16px">{{ translate('No_data_available') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif
