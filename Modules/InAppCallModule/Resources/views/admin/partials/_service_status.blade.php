@php
    $overall = $healthReport['overall'] ?? 'unknown';
    $overallClass = match ($overall) {
        'healthy' => 'success',
        'degraded' => 'warning',
        'unhealthy' => 'danger',
        default => 'secondary',
    };
    $summary = $healthReport['summary'] ?? [];
@endphp

<div id="in-app-call-health-panel">
    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-3">
        <div>
            <span class="badge badge-{{ $overallClass }} fs-6 px-3 py-2" id="in-app-call-health-overall">
                {{ translate('health_overall_' . $overall) }}
            </span>
            <span class="text-muted small ms-2" id="in-app-call-health-checked-at">
                {{ translate('Last_checked') }}: {{ $healthReport['checked_at_label'] ?? '—' }}
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2 small">
            <span class="badge badge-success">{{ $summary['ok'] ?? 0 }} {{ translate('OK') }}</span>
            <span class="badge badge-warning">{{ $summary['warning'] ?? 0 }} {{ translate('Warnings') }}</span>
            <span class="badge badge-danger">{{ $summary['error'] ?? 0 }} {{ translate('Errors') }}</span>
            <span class="badge badge-secondary">{{ $summary['disabled'] ?? 0 }} {{ translate('Disabled') }}</span>
        </div>
    </div>

    @if(!empty($healthReport['recommendations']))
        <div class="alert alert-info mb-3" id="in-app-call-health-recommendations">
            <div class="fw-semibold mb-1">{{ translate('Recommendations') }}</div>
            <ul class="mb-0 ps-3">
                @foreach($healthReport['recommendations'] as $tip)
                    <li>{{ $tip }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>{{ translate('Service') }}</th>
                <th>{{ translate('Category') }}</th>
                <th>{{ translate('status') }}</th>
                <th>{{ translate('Message') }}</th>
                <th>{{ translate('Details') }}</th>
            </tr>
            </thead>
            <tbody id="in-app-call-health-rows">
            @foreach($healthReport['checks'] ?? [] as $check)
                @php
                    $status = $check['status'] ?? 'warning';
                    $badge = match ($status) {
                        'ok' => 'success',
                        'warning' => 'warning',
                        'error' => 'danger',
                        'disabled' => 'secondary',
                        default => 'secondary',
                    };
                @endphp
                <tr data-health-id="{{ $check['id'] ?? '' }}">
                    <td class="fw-semibold">
                        {{ $check['name'] ?? '' }}
                        @if($check['required'] ?? false)
                            <span class="text-danger" title="{{ translate('Required') }}">*</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $check['category_label'] ?? $check['category'] ?? '' }}</td>
                    <td>
                        <span class="badge badge-{{ $badge }}">{{ $check['status_label'] ?? $status }}</span>
                    </td>
                    <td>{{ $check['message'] ?? '' }}</td>
                    <td class="text-muted small">{{ $check['detail'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-muted small mt-3 mb-0">
        {{ translate('In_App_Call_Health_help') }}
        <span class="d-block">{{ translate('Auto_refreshes_every_30_seconds') }}</span>
    </p>
</div>
