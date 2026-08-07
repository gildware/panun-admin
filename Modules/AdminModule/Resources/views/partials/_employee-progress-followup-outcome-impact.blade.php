@php
    $scope = $scope ?? 'leads';
    $outcomeImpact = $followupAnalytics['outcome_impact'] ?? [];
    $isLeads = $scope === 'leads';
@endphp

@if($isLeads)
    @php
        $leadImpact = $outcomeImpact['leads'] ?? [];
        $generalRows = $leadImpact['general_by_timing'] ?? [];
        $customerRows = $leadImpact['customer']['comparison_rows'] ?? [];
        $providerRows = $leadImpact['provider']['comparison_rows'] ?? [];
        $toneByKey = [
            'on_time' => 'success',
            'late' => 'warning',
            'missed' => 'danger',
        ];
        $iconByKey = [
            'on_time' => 'schedule',
            'late' => 'running_with_errors',
            'missed' => 'warning',
        ];
    @endphp

    @include('adminmodule::partials._employee-progress-section-label', [
        'label' => translate('Progress_followup_outcome_impact') ?? 'Result after follow-up',
        'helpKey' => 'lead_followup_outcome_impact',
    ])
    <p class="section-sub">{{ translate('Progress_lead_followup_outcome_sub') }}</p>

    <div class="outcome-scope-block">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => translate('Progress_general_result') ?? 'General result',
            'helpKey' => 'lead_followup_outcome_general',
        ])
        <p class="section-sub">{{ translate('Progress_general_result_sub') ?? 'After on-time, late, or missed follow-ups — how many leads are now customer, provider, future customer, or invalid.' }}</p>

        <div class="outcome-timing-grid">
            @forelse($generalRows as $row)
                @php
                    $key = $row['key'] ?? '';
                    $tone = $toneByKey[$key] ?? '';
                    $total = (int) ($row['total'] ?? 0);
                    $customer = (int) ($row['customer'] ?? 0);
                    $provider = (int) ($row['provider'] ?? 0);
                    $future = (int) ($row['future_customer'] ?? 0);
                    $invalid = (int) ($row['invalid'] ?? 0);
                    $unknown = (int) ($row['unknown'] ?? 0);
                    $denom = max(1, $total);
                @endphp
                <div class="outcome-timing-card {{ $tone }}">
                    <div class="otc-head">
                        <div class="otc-icon">@include('adminmodule::partials._material-icon', ['name' => $iconByKey[$key] ?? 'category'])</div>
                        <div class="otc-title">{{ $row['label'] ?? '' }}</div>
                    </div>
                    <div class="otc-hero">
                        <div class="otc-rate">
                            @include('adminmodule::partials._employee-progress-metric-value', [
                                'count' => $total,
                                'total' => $row['team_total'] ?? null,
                                'ofClass' => 'mc-of',
                            ])
                        </div>
                        <div class="otc-rate-label">{{ translate('Leads') }}</div>
                    </div>
                    <div class="otc-mix" aria-hidden="true">
                        <span class="otc-seg brand" style="width: {{ round(($customer / $denom) * 100, 1) }}%"></span>
                        <span class="otc-seg success" style="width: {{ round(($provider / $denom) * 100, 1) }}%"></span>
                        <span class="otc-seg warning" style="width: {{ round(($future / $denom) * 100, 1) }}%"></span>
                        <span class="otc-seg danger" style="width: {{ round(($invalid / $denom) * 100, 1) }}%"></span>
                    </div>
                    <div class="otc-legend">
                        <span><i class="brand"></i>{{ translate('Customer') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $customer, 'total' => $row['team_customer'] ?? null, 'ofClass' => 'mc-of'])</span>
                        <span><i class="success"></i>{{ translate('Provider') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $provider, 'total' => $row['team_provider'] ?? null, 'ofClass' => 'mc-of'])</span>
                        <span><i class="warning"></i>{{ translate('Future_Customer') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $future, 'total' => $row['team_future_customer'] ?? null, 'ofClass' => 'mc-of'])</span>
                        <span><i class="danger"></i>{{ translate('Invalid') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $invalid, 'total' => $row['team_invalid'] ?? null, 'ofClass' => 'mc-of'])</span>
                        @if($unknown > 0)
                            <span><i class="danger"></i>{{ translate('Unknown') }} @include('adminmodule::partials._employee-progress-metric-value', ['count' => $unknown, 'total' => $row['team_unknown'] ?? null, 'ofClass' => 'mc-of'])</span>
                        @endif
                    </div>
                    <div class="otc-foot">@include('adminmodule::partials._employee-progress-metric-value', ['count' => $total, 'total' => $row['team_total'] ?? null, 'ofClass' => 'mc-of']) {{ translate('Total') }}</div>
                </div>
            @empty
                <div class="outcome-timing-empty">{{ translate('No_data_available') }}</div>
            @endforelse
        </div>

        <div class="data-table-wrap outcome-timing-table">
            <table class="data-table" style="min-width:0">
                <thead>
                    <tr>
                        <th>{{ translate('Progress_followup_timing') ?? 'Follow-up timing' }}</th>
                        <th>{{ translate('Total') }}</th>
                        <th>{{ translate('Customer') }}</th>
                        <th>{{ translate('Provider') }}</th>
                        <th>{{ translate('Future_Customer') }}</th>
                        <th>{{ translate('Invalid') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($generalRows as $row)
                        <tr>
                            <td><strong>{{ $row['label'] ?? '' }}</strong></td>
                            <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['total'] ?? 0), 'total' => $row['team_total'] ?? null, 'ofClass' => 'mc-of'])</td>
                            <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['customer'] ?? 0), 'total' => $row['team_customer'] ?? null, 'ofClass' => 'mc-of'])</td>
                            <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['provider'] ?? 0), 'total' => $row['team_provider'] ?? null, 'ofClass' => 'mc-of'])</td>
                            <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['future_customer'] ?? 0), 'total' => $row['team_future_customer'] ?? null, 'ofClass' => 'mc-of'])</td>
                            <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['invalid'] ?? 0), 'total' => $row['team_invalid'] ?? null, 'ofClass' => 'mc-of'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;color:#64748b;padding:12px">{{ translate('No_data_available') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="outcome-scope-block">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => (translate('Customer') ?? 'Customer').' '.translate('Leads'),
            'helpKey' => 'lead_followup_outcome_customer',
        ])
        <p class="section-sub">{{ translate('Progress_customer_followup_outcome_sub') }}</p>
        @include('adminmodule::partials._employee-progress-outcome-timing-cards', [
            'rows' => $customerRows,
            'successLabel' => translate('Progress_converted') ?? 'Converted',
        ])
    </div>

    <div class="outcome-scope-block">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => (translate('Provider') ?? 'Provider').' '.translate('Leads'),
            'helpKey' => 'lead_followup_outcome_provider',
        ])
        <p class="section-sub">{{ translate('Progress_provider_followup_outcome_sub') }}</p>
        @include('adminmodule::partials._employee-progress-outcome-timing-cards', [
            'rows' => $providerRows,
            'successLabel' => translate('customer_app_status_registered') ?? (translate('Progress_converted') ?? 'Converted'),
        ])
    </div>
@else
    @php
        $impact = $outcomeImpact['bookings'] ?? [];
        $rows = $impact['comparison_rows'] ?? [];
        $successLabel = translate('Bookings_completed');
    @endphp

    @include('adminmodule::partials._employee-progress-section-label', [
        'label' => translate('Progress_followup_outcome_impact') ?? 'Result after follow-up',
        'helpKey' => 'booking_followup_outcome_impact',
    ])
    <p class="section-sub">{{ translate('Progress_booking_followup_outcome_sub') }}</p>

    @include('adminmodule::partials._employee-progress-outcome-timing-cards', [
        'rows' => $rows,
        'successLabel' => $successLabel,
    ])

    <div class="data-table-wrap outcome-timing-table">
        <table class="data-table" style="min-width:0">
            <thead>
                <tr>
                    <th>{{ translate('Progress_followup_timing') ?? 'Follow-up timing' }}</th>
                    <th>{{ translate('Total') }}</th>
                    <th>{{ $successLabel }}</th>
                    <th>{{ translate('Cancelled') }}</th>
                    <th>{{ translate('Pending') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><strong>{{ $row['label'] ?? '' }}</strong></td>
                        <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['total'] ?? 0), 'total' => $row['team_total'] ?? null, 'ofClass' => 'mc-of'])</td>
                        <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['success_count'] ?? 0), 'total' => $row['team_success_count'] ?? null, 'ofClass' => 'mc-of'])</td>
                        <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['cancel_count'] ?? 0), 'total' => $row['team_cancel_count'] ?? null, 'ofClass' => 'mc-of'])</td>
                        <td>@include('adminmodule::partials._employee-progress-metric-value', ['count' => (int) ($row['pending_count'] ?? 0), 'total' => $row['team_pending_count'] ?? null, 'ofClass' => 'mc-of'])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:#64748b;padding:12px">{{ translate('No_data_available') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
