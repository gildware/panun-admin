@php
    $logSearch = request('log_search', '');
    $statusFilter = request('status', 'all');
@endphp

<div class="notification-delivery-logs-panel">
    <div class="mb-4">
        <h5 class="mb-1 fw-semibold">{{ translate('notification_delivery_logs') }}</h5>
        <p class="text-muted fz-12 mb-0">{{ translate('notification_delivery_logs_hint') }}</p>
    </div>

    @if(!empty($deviceStats))
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('sent_last_24h') }}</div>
                    <div class="h4 mb-0 text-success">{{ $deviceStats['sent_last_24h'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('failed_last_24h') }}</div>
                    <div class="h4 mb-0 text-danger">{{ $deviceStats['failed_last_24h'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('registered_devices') }}</div>
                    <div class="h4 mb-0">{{ $deviceStats['total_devices'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.configuration.get-notification-setting') }}" class="row g-2 align-items-end mb-4">
        <input type="hidden" name="section" value="logs">
        <div class="col-md-5">
            <label class="form-label fz-12">{{ translate('search') }}</label>
            <input type="text" name="log_search" class="form-control" value="{{ $logSearch }}"
                   placeholder="{{ translate('search_by_user_title_or_device') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fz-12">{{ translate('delivery_status') }}</label>
            <select name="status" class="form-control js-select">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                <option value="sent" {{ $statusFilter === 'sent' ? 'selected' : '' }}>{{ translate('sent') }}</option>
                <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>{{ translate('failed') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn--primary w-100">{{ translate('filter') }}</button>
        </div>
    </form>

    <div class="table-responsive border rounded">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>{{ translate('time') }}</th>
                <th>{{ translate('status') }}</th>
                <th>{{ translate('user') }}</th>
                <th>{{ translate('device') }}</th>
                <th>{{ translate('type') }}</th>
                <th>{{ translate('title') }}</th>
                <th>{{ translate('target') }}</th>
                <th>{{ translate('error') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse(($notificationDeliveryLogs ?? collect()) as $log)
                <tr>
                    <td class="small text-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                    <td>
                        @if($log->status === 'sent')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ translate('sent') }}</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ translate('failed') }}</span>
                        @endif
                    </td>
                    <td class="small">
                        @if($log->user)
                            <div class="fw-medium">{{ trim(($log->user->first_name ?? '') . ' ' . ($log->user->last_name ?? '')) ?: '—' }}</div>
                            <div class="text-muted">{{ $log->user->phone ?? $log->user->email ?? '' }}</div>
                            <div class="text-muted">{{ notification_logs_user_type_label($log->user->user_type) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">
                        <div>{{ $log->device_id ?? '—' }}</div>
                        <div class="text-muted">{{ $log->fcm_token_preview ?? '—' }}</div>
                    </td>
                    <td class="small"><code>{{ $log->notification_type ?? '—' }}</code></td>
                    <td class="small">{{ \Illuminate\Support\Str::limit((string) ($log->title ?? '—'), 60) }}</td>
                    <td class="small">
                        @if($log->delivery_target === 'topic')
                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ translate('topic') }}</span>
                            <div class="text-muted">{{ $log->topic }}</div>
                        @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ translate('device') }}</span>
                        @endif
                    </td>
                    <td class="small text-danger">{{ \Illuminate\Support\Str::limit((string) ($log->error_message ?? ''), 80) ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">{{ translate('no_notification_logs_yet') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($notificationDeliveryLogs && $notificationDeliveryLogs->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {!! $notificationDeliveryLogs->links() !!}
        </div>
    @endif
</div>
