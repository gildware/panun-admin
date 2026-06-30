@php
    $userSearch = request('user_search', '');
    $userTypeFilter = request('user_type', 'all');
    $showCustomerSection = $customerUsersWithDevices !== null;
    $showProviderSection = $providerUsersWithDevices !== null;
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
            <label class="form-label fz-12">{{ translate('account_filter') }}</label>
            <select name="user_type" class="form-control js-select">
                <option value="all" {{ $userTypeFilter === 'all' ? 'selected' : '' }}>{{ translate('customer_and_provider_accounts') }}</option>
                <option value="customer" {{ $userTypeFilter === 'customer' ? 'selected' : '' }}>{{ translate('customer_accounts_only') }}</option>
                <option value="provider-admin" {{ $userTypeFilter === 'provider-admin' ? 'selected' : '' }}>{{ translate('provider_accounts_only') }}</option>
                <option value="provider-serviceman" {{ $userTypeFilter === 'provider-serviceman' ? 'selected' : '' }}>{{ translate('serviceman_accounts_only') }}</option>
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

    @if($userSearch === '' && $userTypeFilter === 'all')
        <p class="text-muted fz-12 mb-4">{{ translate('device_check_separate_accounts_hint') }}</p>
    @endif

    @if($showCustomerSection)
        <div class="mb-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h6 class="fw-semibold mb-0">{{ translate('customer_accounts') }}</h6>
                <span class="badge notification-device-badge-customer rounded-pill px-2 py-1">{{ translate('customer') }}</span>
                @if($userSearch === '' && $userTypeFilter === 'all')
                    <span class="text-muted fz-12">{{ translate('recently_logged_in_users') }}</span>
                @endif
            </div>
            @if(($customerUsersWithDevices ?? null) && $customerUsersWithDevices->count() > 0)
                @include('businesssettingsmodule::admin.partials.notification-user-device-accordions', [
                    'usersWithDevices' => $customerUsersWithDevices,
                    'accountKind' => 'customer',
                ])
            @else
                <div class="border rounded bg-light text-center text-muted py-4 px-3 fz-12">
                    {{ translate('no_customer_accounts_with_devices') }}
                </div>
            @endif
        </div>
    @endif

    @if($showProviderSection)
        <div class="mb-2">
            <div class="d-flex align-items-center gap-2 mb-3">
                <h6 class="fw-semibold mb-0">{{ translate('provider_accounts') }}</h6>
                <span class="badge notification-device-badge-provider rounded-pill px-2 py-1">{{ translate('provider') }}</span>
                @if($userTypeFilter === 'provider-serviceman')
                    <span class="badge notification-device-badge-serviceman rounded-pill px-2 py-1">{{ translate('serviceman') }}</span>
                @endif
                @if($userSearch === '' && $userTypeFilter === 'all')
                    <span class="text-muted fz-12">{{ translate('recently_logged_in_users') }}</span>
                @endif
            </div>
            @if(($providerUsersWithDevices ?? null) && $providerUsersWithDevices->count() > 0)
                @include('businesssettingsmodule::admin.partials.notification-user-device-accordions', [
                    'usersWithDevices' => $providerUsersWithDevices,
                    'accountKind' => 'provider',
                ])
            @else
                <div class="border rounded bg-light text-center text-muted py-4 px-3 fz-12">
                    {{ translate('no_provider_accounts_with_devices') }}
                </div>
            @endif
        </div>
    @endif

    @if(!$showCustomerSection && !$showProviderSection)
        <div class="border rounded bg-light text-center text-muted py-5 px-3">
            {{ translate('no_users_found_for_device_search') }}
        </div>
    @endif
</div>
