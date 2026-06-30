@php
    $search = request('search', '');
    $statusFilter = request('status', 'all');
    $userTypeFilter = request('user_type', 'all');
@endphp

<div class="notification-logs-status-panel">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-semibold">{{ translate('notification_logs_and_status') }}</h5>
            <p class="text-muted fz-12 mb-0">{{ translate('notification_logs_and_status_hint') }}</p>
        </div>
        <div class="alert alert-info py-2 px-3 mb-0 fz-12">
            {{ translate('notifications_sent_to_all_logged_in_devices') }}
        </div>
    </div>

    @if(!empty($deviceStats))
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('registered_devices') }}</div>
                    <div class="h4 mb-0">{{ $deviceStats['total_devices'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('configured_devices') }}</div>
                    <div class="h4 mb-0 text-success">{{ $deviceStats['configured_devices'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('not_configured_devices') }}</div>
                    <div class="h4 mb-0 text-warning">{{ $deviceStats['not_configured_devices'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('legacy_token_users') }}</div>
                    <div class="h4 mb-0">{{ $deviceStats['legacy_only_users'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('sent_last_24h') }}</div>
                    <div class="h4 mb-0 text-success">{{ $deviceStats['sent_last_24h'] }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-white">
                    <div class="text-muted fz-12">{{ translate('failed_last_24h') }}</div>
                    <div class="h4 mb-0 text-danger">{{ $deviceStats['failed_last_24h'] }}</div>
                </div>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.configuration.get-notification-setting') }}" class="row g-2 align-items-end mb-4">
        <input type="hidden" name="tab" value="logs_and_status">
        <div class="col-md-4">
            <label class="form-label fz-12">{{ translate('search') }}</label>
            <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="{{ translate('search_by_user_title_or_device') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fz-12">{{ translate('delivery_status') }}</label>
            <select name="status" class="form-control js-select">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                <option value="sent" {{ $statusFilter === 'sent' ? 'selected' : '' }}>{{ translate('sent') }}</option>
                <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>{{ translate('failed') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fz-12">{{ translate('user_type') }}</label>
            <select name="user_type" class="form-control js-select">
                <option value="all" {{ $userTypeFilter === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                <option value="customer" {{ $userTypeFilter === 'customer' ? 'selected' : '' }}>{{ translate('customer') }}</option>
                <option value="provider-admin" {{ $userTypeFilter === 'provider-admin' ? 'selected' : '' }}>{{ translate('provider') }}</option>
                <option value="provider-serviceman" {{ $userTypeFilter === 'provider-serviceman' ? 'selected' : '' }}>{{ translate('serviceman') }}</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn--primary w-100">{{ translate('filter') }}</button>
        </div>
    </form>

    <div class="mb-4">
        <h6 class="fw-semibold mb-3">{{ translate('notification_delivery_logs') }}</h6>
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
                                <div class="text-muted">{{ ucfirst(str_replace('-', ' ', (string) ($log->user->user_type ?? ''))) }}</div>
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

    <div>
        <h6 class="fw-semibold mb-3">{{ translate('logged_in_devices') }}</h6>
        <div class="table-responsive border rounded">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>{{ translate('user') }}</th>
                    <th>{{ translate('device_id') }}</th>
                    <th>{{ translate('platform') }}</th>
                    <th>{{ translate('push_configured') }}</th>
                    <th>{{ translate('last_seen') }}</th>
                    <th>{{ translate('token_preview') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($userFcmDevices ?? collect()) as $device)
                    @php
                        $isConfigured = is_valid_fcm_token($device->fcm_token);
                    @endphp
                    <tr>
                        <td class="small">
                            @if($device->user)
                                <div class="fw-medium">{{ trim(($device->user->first_name ?? '') . ' ' . ($device->user->last_name ?? '')) ?: '—' }}</div>
                                <div class="text-muted">{{ $device->user->phone ?? $device->user->email ?? '' }}</div>
                                <div class="text-muted">{{ ucfirst(str_replace('-', ' ', (string) ($device->user->user_type ?? ''))) }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="small"><code>{{ $device->device_id }}</code></td>
                        <td class="small">{{ strtoupper((string) ($device->platform ?? '—')) }}</td>
                        <td>
                            @if($isConfigured)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ translate('configured') }}</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ translate('not_configured') }}</span>
                            @endif
                        </td>
                        <td class="small text-nowrap">{{ $device->last_seen_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="small text-muted">{{ mask_fcm_token($device->fcm_token) ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ translate('no_registered_devices_yet') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($userFcmDevices && $userFcmDevices->hasPages())
            <div class="d-flex justify-content-end mt-3">
                {!! $userFcmDevices->links() !!}
            </div>
        @endif
    </div>
</div>
