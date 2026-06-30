@if(($usersWithDevices ?? null) && $usersWithDevices->count() > 0)
    <div class="notification-user-devices-list d-flex flex-column gap-3">
        @foreach($usersWithDevices as $user)
            @php
                $deviceCount = notification_logs_user_device_count($user);
                $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: translate('user');
                $typeLabel = notification_logs_user_type_label($user->user_type);
                $typeBadgeClass = notification_user_account_badge_class($user);
                $accountSubtitle = notification_user_account_subtitle($user);
                $accountKind = $accountKind ?? notification_user_account_kind($user);
                $hasLegacyOnly = $user->fcmDevices->isEmpty() && is_valid_fcm_token($user->fcm_token);
                $subtitleLabel = $accountKind === 'provider'
                    ? translate('provider')
                    : ($accountKind === 'serviceman' ? translate('serviceman') : translate('customer'));
            @endphp
            <div class="notification-scenario-accordion notification-user-device-accordion notification-user-device-accordion--{{ $accountKind }}" id="user-devices-{{ $user->id }}">
                <div class="d-flex align-items-center gap-3 p-3 cursor-pointer transition {{ $loop->first ? 'active' : '' }} bg-white border cus-shadow notification-scenario-toggle-header">
                    <span class="rounded-full bg-light w-28 h-28 fz-14 d-inline-flex align-items-center justify-content-center flex-shrink-0 notification-scenario-toggle-chevron">
                        <i class="material-symbols-outlined">keyboard_arrow_down</i>
                    </span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h6 class="mb-0 fw-semibold text-dark">{{ $userName }}</h6>
                            <span class="badge {{ $typeBadgeClass }} rounded-pill px-2 py-1 fw-semibold">
                                {{ $typeLabel }}
                            </span>
                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                {{ $deviceCount }} {{ translate('devices') }}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 fz-12 text-muted">
                            @if($accountSubtitle && $accountKind !== 'customer')
                                <span class="fw-medium text-dark">{{ $accountSubtitle }}</span>
                                <span>·</span>
                            @endif
                            @if($user->phone)
                                <span>{{ $user->phone }}</span>
                            @elseif($user->email)
                                <span>{{ $user->email }}</span>
                            @endif
                            <span>·</span>
                            <span>{{ translate('account_id') }}: <code class="fz-11">{{ Str::limit($user->id, 13, '…') }}</code></span>
                        </div>
                    </div>
                </div>

                <div class="table-custom-wrap bg-white border border-top-0 cus-shadow p-3 notification-scenario-toggle-body"
                     @if(!$loop->first) style="display: none;" @endif>
                    <div class="alert alert-light border py-2 px-3 mb-3 fz-12">
                        <strong>{{ translate('account_type') }}:</strong> {{ $typeLabel }}
                        @if($accountSubtitle && $accountKind !== 'customer')
                            · <strong>{{ $subtitleLabel }}:</strong> {{ $accountSubtitle }}
                        @endif
                        · <strong>{{ translate('account_id') }}:</strong> <code>{{ $user->id }}</code>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 notification-scenario-audience-table">
                            <thead>
                            <tr>
                                <th>{{ translate('device_id') }}</th>
                                <th>{{ translate('platform') }}</th>
                                <th>{{ translate('device_manufacturer') }}</th>
                                <th>{{ translate('device_model') }}</th>
                                <th>{{ translate('os_version') }}</th>
                                <th>{{ translate('push_configured') }}</th>
                                <th>{{ translate('last_seen') }}</th>
                                <th>{{ translate('last_login_at') }}</th>
                                <th>{{ translate('token_preview') }}</th>
                                @can('notification_message_update')
                                    <th class="text-end">{{ translate('action') }}</th>
                                @endcan
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($user->fcmDevices as $device)
                                @php $isConfigured = is_valid_fcm_token($device->fcm_token); @endphp
                                <tr>
                                    <td><code class="fz-11">{{ $device->device_id }}</code></td>
                                    <td>{{ strtoupper((string) ($device->platform ?? '—')) }}</td>
                                    <td class="small">{{ $device->device_manufacturer ?? '—' }}</td>
                                    <td class="small">{{ $device->device_model ?? '—' }}</td>
                                    <td class="small text-nowrap">{{ $device->os_version ?? '—' }}</td>
                                    <td>
                                        @if($isConfigured)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ translate('configured') }}</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ translate('not_configured') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap small">{{ $device->last_seen_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                    <td class="text-nowrap small">{{ $device->updated_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                    <td class="small text-muted">{{ mask_fcm_token($device->fcm_token) ?? '—' }}</td>
                                    @can('notification_message_update')
                                        <td class="text-end">
                                            <form method="POST"
                                                  action="{{ route('admin.configuration.deregister-notification-device') }}"
                                                  class="d-inline notification-deregister-device-form"
                                                  data-device-label="{{ notification_device_display_name($device) }}">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                <input type="hidden" name="device_id" value="{{ $device->device_id }}">
                                                @foreach(request()->only(['section', 'user_search', 'user_type', 'customers_page', 'providers_page']) as $key => $value)
                                                    @if(filled($value))
                                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                    @endif
                                                @endforeach
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    {{ translate('deregister_device') }}
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                @if($hasLegacyOnly)
                                    <tr>
                                        <td><code class="fz-11">legacy</code></td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td>—</td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ translate('configured') }}</span>
                                        </td>
                                        <td class="text-nowrap small">{{ $user->last_seen_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                        <td class="text-nowrap small">{{ $user->updated_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                        <td class="small text-muted">{{ mask_fcm_token($user->fcm_token) ?? '—' }}</td>
                                        @can('notification_message_update')
                                            <td class="text-end">
                                                <form method="POST"
                                                      action="{{ route('admin.configuration.deregister-notification-device') }}"
                                                      class="d-inline notification-deregister-device-form"
                                                      data-device-label="{{ translate('legacy_token_device') }}">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                                    <input type="hidden" name="device_id" value="legacy">
                                                    @foreach(request()->only(['section', 'user_search', 'user_type', 'customers_page', 'providers_page']) as $key => $value)
                                                        @if(filled($value))
                                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                        @endif
                                                    @endforeach
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        {{ translate('deregister_device') }}
                                                    </button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @endif
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @if($usersWithDevices->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {!! $usersWithDevices->links() !!}
        </div>
    @endif
@else
    <div class="border rounded bg-light text-center text-muted py-5 px-3">
        {{ translate('no_users_found_for_device_search') }}
    </div>
@endif

@once
    @push('script')
        <script>
            (function () {
                if (window.__notificationDeregisterBound) {
                    return;
                }
                window.__notificationDeregisterBound = true;

                document.addEventListener('submit', function (e) {
                    var form = e.target;
                    if (!form || !form.classList.contains('notification-deregister-device-form')) {
                        return;
                    }
                    e.preventDefault();

                    var deviceLabel = form.getAttribute('data-device-label') || '';
                    var message = @json(translate('deregister_device_confirm'));
                    if (deviceLabel) {
                        message = message.replace(':device', deviceLabel);
                    }

                    if (typeof Swal === 'undefined') {
                        if (confirm(message)) {
                            form.submit();
                        }
                        return;
                    }

                    Swal.fire({
                        title: @json(translate('are_you_sure')),
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--bs-danger)',
                        cancelButtonColor: 'var(--bs-secondary)',
                        confirmButtonText: @json(translate('deregister_device')),
                        cancelButtonText: @json(translate('cancel')),
                        reverseButtons: true,
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            })();
        </script>
    @endpush
@endonce
