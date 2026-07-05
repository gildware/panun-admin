@php
    $overallClass = ($result['ok'] ?? false) ? 'success' : 'danger';
@endphp

<div id="in-app-call-signaling-test-panel">
    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-3">
        <div>
            <span class="badge badge-{{ $overallClass }} fs-6 px-3 py-2">
                {{ ($result['ok'] ?? false) ? translate('Signaling_test_passed') : translate('Signaling_test_failed') }}
            </span>
            <span class="text-muted small ms-2">
                {{ translate('Signaling_test_summary', [
                    'passed' => $result['passed'] ?? 0,
                    'failed' => $result['failed'] ?? 0,
                    'ms' => $result['duration_ms'] ?? 0,
                ]) }}
            </span>
        </div>
        @if(!empty($result['call_id']))
            <span class="text-muted small font-monospace">{{ translate('Call_ID') }}: {{ $result['call_id'] }}</span>
        @endif
    </div>

    @if(!empty($result['customer_label']) && !empty($result['provider_label']))
        <p class="text-muted small mb-3">
            {{ translate('Signaling_test_participants') }}:
            <strong>{{ $result['customer_label'] }}</strong>
            →
            <strong>{{ $result['provider_label'] }}</strong>
        </p>
    @endif

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>{{ translate('Step') }}</th>
                <th>{{ translate('status') }}</th>
                <th>{{ translate('Details') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($result['steps'] ?? [] as $step)
                @php
                    $status = $step['status'] ?? 'fail';
                    $badge = $status === 'pass' ? 'success' : 'danger';
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $step['label'] ?? '' }}</td>
                    <td><span class="badge badge-{{ $badge }}">{{ $status === 'pass' ? translate('Pass') : translate('Fail') }}</span></td>
                    <td class="text-muted small">{{ $step['detail'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted text-center py-4">{{ translate('No_results_found') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-muted small mt-3 mb-0">{{ translate('Signaling_test_help') }}</p>
</div>
