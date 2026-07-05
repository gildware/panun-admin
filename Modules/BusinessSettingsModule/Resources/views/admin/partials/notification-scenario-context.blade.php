@if(!empty($scenarioContext))
    <div class="notification-scenario-context-bar mb-3 p-2 px-3 rounded border bg-white">
        <div class="fz-11 text-muted mb-1">{{ translate('Scenario') }}</div>
        <div class="fz-12 fw-semibold text-dark mb-2">{{ $scenarioContext['scenario_title'] ?? '' }}</div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge notification-scenario-badge-actor rounded-pill px-2 py-1">
                {{ translate('Trigger') }}: {{ $scenarioContext['actor'] ?? '' }}
            </span>
            <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                {{ translate('Audience') }}: {{ $scenarioContext['audience'] ?? '' }}
            </span>
        </div>
    </div>
@endif
