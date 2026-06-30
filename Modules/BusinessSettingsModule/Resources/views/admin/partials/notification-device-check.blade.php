@php
    $userSearch = request('user_search', '');
    $userTypeFilter = request('user_type', 'all');
@endphp

<div class="notification-device-check-panel">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-semibold">{{ translate('notification_device_check') }}</h5>
            <p class="text-muted fz-12 mb-0">{{ translate('notification_device_check_hint') }}</p>
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
        </div>
    @endif

    <form method="GET" action="{{ route('admin.configuration.get-notification-setting') }}" class="row g-2 align-items-end mb-4">
        <input type="hidden" name="section" value="device_check">
        <div class="col-md-5">
            <label class="form-label fz-12">{{ translate('search_user') }}</label>
            <input type="text" name="user_search" class="form-control" value="{{ $userSearch }}"
                   placeholder="{{ translate('search_user_by_name_phone_email') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label fz-12">{{ translate('user_type') }}</label>
            <select name="user_type" class="form-control js-select">
                <option value="all" {{ $userTypeFilter === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                <option value="customer" {{ $userTypeFilter === 'customer' ? 'selected' : '' }}>{{ translate('customer') }}</option>
                <option value="provider-admin" {{ $userTypeFilter === 'provider-admin' ? 'selected' : '' }}>{{ translate('provider') }}</option>
                <option value="provider-serviceman" {{ $userTypeFilter === 'provider-serviceman' ? 'selected' : '' }}>{{ translate('serviceman') }}</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn--primary w-100">{{ translate('search') }}</button>
            @if($userSearch !== '' || $userTypeFilter !== 'all')
                <a href="{{ route('admin.configuration.get-notification-setting', ['section' => 'device_check']) }}"
                   class="btn btn-outline-secondary">{{ translate('clear') }}</a>
            @endif
        </div>
    </form>

    @if($userSearch === '')
        <p class="text-muted fz-12 mb-3">{{ translate('recently_logged_in_users') }}</p>
    @endif

    @include('businesssettingsmodule::admin.partials.notification-user-device-accordions', [
        'usersWithDevices' => $usersWithDevices,
    ])
</div>
