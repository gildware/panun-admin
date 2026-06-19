@php
    $changeLogs = $changeLogs ?? collect();
@endphp
<ul class="list-unstyled mb-0 booking-change-log-timeline">
    @forelse($changeLogs as $log)
        @php
            $actor = null;
            if ($log->changedBy) {
                $actor = trim(($log->changedBy->first_name ?? '') . ' ' . ($log->changedBy->last_name ?? ''));
            }
            if ($actor === null || $actor === '') {
                $actor = $log->actor_name ?: translate('System');
            }

            $propertyKey = (string) ($log->property_key ?? '');
            $isServiceUpdate = str_contains($propertyKey, 'detail.updated')
                || str_contains($propertyKey, 'extra_service.updated');
            $serviceSummary = $isServiceUpdate
                ? \Modules\BookingModule\Services\BookingAuditLogger::resolveServiceSummaryForDisplay($log)
                : null;
            $displayTitle = $isServiceUpdate
                ? translate('Service_updated')
                : ($log->property_label ?: str_replace('_', ' ', $propertyKey));
        @endphp
        <li class="border-bottom pb-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                <strong class="text-break">{{ $displayTitle }}</strong>
                <span class="text-muted small text-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('d-M-Y h:ia') }}</span>
            </div>
            @if($isServiceUpdate)
                @if($serviceSummary)
                    <p class="mb-1 small text-break fw-medium">{{ $serviceSummary }}</p>
                @endif
                @if(!empty($log->old_value) && !empty($log->new_value) && $log->old_value !== '—')
                    <p class="mb-1 small text-break text-muted">
                        <span>{{ translate('from') }}:</span> {{ $log->old_value }}
                        <span class="ms-1">{{ translate('to') }}:</span> {{ $log->new_value }}
                    </p>
                @endif
            @else
                <p class="mb-1 small text-break">
                    <span class="text-muted">{{ translate('from') }}:</span> {{ $log->old_value ?? '—' }}
                    <span class="text-muted ms-1">{{ translate('to') }}:</span> {{ $log->new_value ?? '—' }}
                </p>
            @endif
            <p class="mb-0 small text-muted">{{ translate('By') }}: {{ $actor }}</p>
        </li>
    @empty
        <li class="text-muted py-4">{{ translate('No_history_entries_yet') }}</li>
    @endforelse
</ul>
