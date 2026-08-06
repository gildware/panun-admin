@extends('adminmodule::layouts.new-master')

@section('title', translate('Operations'))

@push('css_or_js')
    @include('adminmodule::partials._dashboard-work-widget-styles')
    <style>
        .operations-kpi-sections {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
            align-items: start;
        }
        @media (max-width: 1199px) {
            .operations-kpi-sections {
                grid-template-columns: 1fr;
            }
        }
        .operations-kpi-section {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 8px 8px 6px;
            border-top: 3px solid var(--oks-accent, #43466e);
        }
        .operations-kpi-section--customers { --oks-accent: #2563eb; }
        .operations-kpi-section--providers { --oks-accent: #0891b2; }
        .operations-kpi-section--apps { --oks-accent: #16a34a; }
        .operations-kpi-section__title {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 0 0 6px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #374151;
        }
        .operations-kpi-section__title .material-symbols-outlined {
            font-size: 15px;
            color: var(--oks-accent);
        }
        .operations-kpi-grid {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1 1 auto;
        }
        .operations-kpi-card {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            min-height: 0;
            padding: 5px 7px;
            border: 1px solid var(--okc-border, #e5e7eb);
            border-radius: 7px;
            background: var(--okc-soft, #f8fafc);
        }
        .operations-kpi-card__icon {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border: 1px solid var(--okc-border, #e5e7eb);
            border-radius: 6px;
            background: #fff;
            color: var(--okc-tone, #64748b);
            font-size: 14px;
            line-height: 1;
        }
        .operations-kpi-card__body { flex: 1 1 auto; min-width: 0; }
        .operations-kpi-card__value {
            font-size: clamp(0.72rem, 1.05vw, 0.82rem);
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .operations-kpi-card__label {
            margin-top: 2px;
            font-size: clamp(0.58rem, 0.85vw, 0.65rem);
            font-weight: 600;
            line-height: 1.2;
            color: #64748b;
        }
        .operations-kpi-card--blue { --okc-soft: #eff6ff; --okc-border: #bfdbfe; --okc-tone: #1d4ed8; }
        .operations-kpi-card--cyan { --okc-soft: #ecfeff; --okc-border: #a5f3fc; --okc-tone: #0e7490; }
        .operations-kpi-card--green { --okc-soft: #f0fdf4; --okc-border: #bbf7d0; --okc-tone: #15803d; }
        .operations-kpi-card--orange { --okc-soft: #fff7ed; --okc-border: #fed7aa; --okc-tone: #c2410c; }
        .operations-kpi-card--violet { --okc-soft: #f5f3ff; --okc-border: #ddd6fe; --okc-tone: #6d28d9; }
        .operations-kpi-card--amber { --okc-soft: #fffbeb; --okc-border: #fde68a; --okc-tone: #b45309; }
        .operations-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 6px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .operations-status-badge.is-approved { background: #dcfce7; color: #15803d; }
        .operations-status-badge.is-pending { background: #fef3c7; color: #b45309; }
        .operations-status-badge.is-denied { background: #fee2e2; color: #b91c1c; }
        .operations-app-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 6px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            white-space: nowrap;
        }
        .operations-app-type-badge.is-customer { background: #eff6ff; color: #1d4ed8; }
        .operations-app-type-badge.is-provider { background: #ecfeff; color: #0e7490; }
        .operations-app-type-badge.is-serviceman { background: #f5f3ff; color: #6d28d9; }
        .operations-app-type-badge.is-other { background: #f8fafc; color: #64748b; }
        #dashboard-recent-app-downloads .work-queue-table .col-type { width: 14%; }
        #dashboard-recent-app-downloads .work-queue-table .col-name { width: 28%; }
        #dashboard-recent-app-downloads .work-queue-table .col-phone { width: 18%; }
        #dashboard-recent-app-downloads .work-queue-table .col-platform { width: 16%; }
        #dashboard-recent-app-downloads .work-queue-table .col-datetime { width: 24%; }
    </style>
@endpush

@section('content')
    @can('dashboard')
    <div class="main-content emp-dash">
        <div class="container-fluid">
            <div class="emp-dash-topbar">
                @include('adminmodule::partials._admin-dashboard-switcher', ['active' => 'operations'])
            </div>

            @if(access_checker('dashboard'))
                @php
                    $summary = $data['summary'] ?? [];
                    $topCustomers = $data['top_customers'] ?? collect();
                    $topProviders = $data['top_providers'] ?? collect();
                    $newCustomers = $data['new_customers'] ?? collect();
                    $newProviders = $data['new_providers'] ?? collect();
                    $recentAppDevices = $data['recent_app_devices'] ?? collect();
                @endphp

                <div class="operations-kpi-sections">
                    <section class="operations-kpi-section operations-kpi-section--customers">
                        <h2 class="operations-kpi-section__title">
                            <span class="material-symbols-outlined">groups</span>
                            <span>{{ translate('Customers') }}</span>
                        </h2>
                        <div class="operations-kpi-grid">
                            <div class="operations-kpi-card operations-kpi-card--blue">
                                <span class="operations-kpi-card__icon material-symbols-outlined">person</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['total_customers'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('total_customers') }}</div>
                                </div>
                            </div>
                            <div class="operations-kpi-card operations-kpi-card--cyan">
                                <span class="operations-kpi-card__icon material-symbols-outlined">person_add</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['new_customers_this_month'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('New_customers_this_month') }}</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="operations-kpi-section operations-kpi-section--providers">
                        <h2 class="operations-kpi-section__title">
                            <span class="material-symbols-outlined">engineering</span>
                            <span>{{ translate('providers') }}</span>
                        </h2>
                        <div class="operations-kpi-grid">
                            <div class="operations-kpi-card operations-kpi-card--cyan">
                                <span class="operations-kpi-card__icon material-symbols-outlined">storefront</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['total_providers'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('Approved_providers') }}</div>
                                </div>
                            </div>
                            <div class="operations-kpi-card operations-kpi-card--orange">
                                <span class="operations-kpi-card__icon material-symbols-outlined">hourglass_top</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['pending_providers'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('Pending_approval') }}</div>
                                </div>
                            </div>
                            <div class="operations-kpi-card operations-kpi-card--violet">
                                <span class="operations-kpi-card__icon material-symbols-outlined">person_add</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['new_providers_this_month'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('New_providers_this_month') }}</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="operations-kpi-section operations-kpi-section--apps">
                        <h2 class="operations-kpi-section__title">
                            <span class="material-symbols-outlined">smartphone</span>
                            <span>{{ translate('App_activity') }}</span>
                        </h2>
                        <div class="operations-kpi-grid">
                            <div class="operations-kpi-card operations-kpi-card--green">
                                <span class="operations-kpi-card__icon material-symbols-outlined">devices</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['app_devices_total'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('App_devices') }}</div>
                                </div>
                            </div>
                            <div class="operations-kpi-card operations-kpi-card--amber">
                                <span class="operations-kpi-card__icon material-symbols-outlined">download</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['app_devices_this_month'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('New_app_devices_this_month') }}</div>
                                </div>
                            </div>
                            <div class="operations-kpi-card operations-kpi-card--blue">
                                <span class="operations-kpi-card__icon material-symbols-outlined">home_repair_service</span>
                                <div class="operations-kpi-card__body">
                                    <div class="operations-kpi-card__value">{{ number_format($summary['total_services'] ?? 0) }}</div>
                                    <div class="operations-kpi-card__label">{{ translate('Total_Services') }}</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="mb-3">
                    <div class="lane-boxes-row">
                        <div class="work-queue-box tone-lead" id="dashboard-top-customers">
                            <div class="work-queue-box-header">
                                <div class="work-queue-box-title">
                                    <span class="material-symbols-outlined">leaderboard</span>
                                    <span>{{ translate('top_customers') }}</span>
                                </div>
                                <span class="work-queue-count-badge">{{ $topCustomers->count() }}</span>
                            </div>
                            <div class="work-queue-box-content">
                                <div class="work-queue-box-body active">
                                    @if($topCustomers->isNotEmpty())
                                        <div class="work-queue-table-wrap">
                                            <table class="work-queue-table">
                                                <thead>
                                                <tr>
                                                    <th class="col-name">{{ translate('Customer') }}</th>
                                                    <th class="col-score">{{ translate('Performance_Score') }}</th>
                                                    <th class="col-bookings">{{ translate('Bookings') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($topCustomers as $customer)
                                                    <tr class="is-clickable"
                                                        onclick="window.location='{{ route('admin.customer.detail', [$customer->id, 'web_page' => 'overview']) }}'">
                                                        <td>
                                                            <span class="cell-primary">{{ trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: '—' }}</span>
                                                        </td>
                                                        <td class="text-end">{{ (int) ($customer->performance_score ?? 0) }}</td>
                                                        <td class="text-end">{{ (int) ($customer->completed_bookings_count ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="work-queue-empty">
                                            <span class="material-symbols-outlined">groups</span>
                                            <span>{{ translate('No Record Found') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="work-queue-box-footer">
                                <a href="{{ route('admin.customer.top-customers') }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                            </div>
                        </div>

                        <div class="work-queue-box tone-booking" id="dashboard-top-providers">
                            <div class="work-queue-box-header">
                                <div class="work-queue-box-title">
                                    <span class="material-symbols-outlined">military_tech</span>
                                    <span>{{ translate('top_providers') }}</span>
                                </div>
                                <span class="work-queue-count-badge">{{ $topProviders->count() }}</span>
                            </div>
                            <div class="work-queue-box-content">
                                <div class="work-queue-box-body active">
                                    @if($topProviders->isNotEmpty())
                                        <div class="work-queue-table-wrap">
                                            <table class="work-queue-table">
                                                <thead>
                                                <tr>
                                                    <th class="col-name">{{ translate('Provider') }}</th>
                                                    <th class="col-score">{{ translate('Performance_Score') }}</th>
                                                    <th class="col-bookings">{{ translate('Bookings') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($topProviders as $provider)
                                                    <tr class="is-clickable"
                                                        onclick="window.location='{{ route('admin.provider.details', [$provider->id, 'web_page' => 'overview']) }}'">
                                                        <td>
                                                            <span class="cell-primary">{{ $provider->company_name ?? '—' }}</span>
                                                        </td>
                                                        <td class="text-end">{{ (int) ($provider->performance_score ?? 0) }}</td>
                                                        <td class="text-end">{{ (int) ($provider->completed_bookings_count ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="work-queue-empty">
                                            <span class="material-symbols-outlined">engineering</span>
                                            <span>{{ translate('No Record Found') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="work-queue-box-footer">
                                <a href="{{ route('admin.provider.top-providers') }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                            </div>
                        </div>

                        <div class="work-queue-box tone-task" id="dashboard-new-customers">
                            <div class="work-queue-box-header">
                                <div class="work-queue-box-title">
                                    <span class="material-symbols-outlined">person_add</span>
                                    <span>{{ translate('New_customers') }}</span>
                                </div>
                                <span class="work-queue-count-badge {{ ($summary['new_customers_this_month'] ?? 0) > 0 ? 'is-hot' : '' }}">{{ $newCustomers->count() }}</span>
                            </div>
                            <div class="work-queue-box-content">
                                <div class="work-queue-box-body active">
                                    @if($newCustomers->isNotEmpty())
                                        <div class="work-queue-table-wrap">
                                            <table class="work-queue-table">
                                                <thead>
                                                <tr>
                                                    <th class="col-name">{{ translate('Customer') }}</th>
                                                    <th class="col-datetime">{{ translate('Date') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($newCustomers as $customer)
                                                    <tr class="is-clickable"
                                                        onclick="window.location='{{ route('admin.customer.detail', [$customer->id, 'web_page' => 'overview']) }}'">
                                                        <td>
                                                            <span class="cell-primary">{{ trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: '—' }}</span>
                                                            @if($customer->phone)
                                                                <div class="cell-secondary">{{ $customer->phone }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="datetime-main">{{ $customer->created_at?->format('d M, H:i a') ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="work-queue-empty">
                                            <span class="material-symbols-outlined">person_add</span>
                                            <span>{{ translate('No Record Found') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="work-queue-box-footer">
                                <a href="{{ route('admin.customer.index', ['sort_by' => 'latest']) }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="lane-boxes-row">
                        <div class="work-queue-box tone-unassigned-booking" id="dashboard-new-providers">
                            <div class="work-queue-box-header">
                                <div class="work-queue-box-title">
                                    <span class="material-symbols-outlined">store</span>
                                    <span>{{ translate('New_providers') }}</span>
                                </div>
                                <span class="work-queue-count-badge {{ ($summary['new_providers_this_month'] ?? 0) > 0 ? 'is-hot' : '' }}">{{ $newProviders->count() }}</span>
                            </div>
                            <div class="work-queue-box-content">
                                <div class="work-queue-box-body active">
                                    @if($newProviders->isNotEmpty())
                                        <div class="work-queue-table-wrap">
                                            <table class="work-queue-table">
                                                <thead>
                                                <tr>
                                                    <th class="col-name">{{ translate('Provider') }}</th>
                                                    <th class="col-type">{{ translate('Status') }}</th>
                                                    <th class="col-datetime">{{ translate('Date') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($newProviders as $provider)
                                                    @php
                                                        $approvalStatus = (int) ($provider->is_approved ?? 0);
                                                        $statusClass = $approvalStatus === 1 ? 'is-approved' : ($approvalStatus === 2 ? 'is-denied' : 'is-pending');
                                                        $statusLabel = $approvalStatus === 1
                                                            ? translate('Approved')
                                                            : ($approvalStatus === 2 ? translate('Denied') : translate('Pending'));
                                                    @endphp
                                                    <tr class="is-clickable"
                                                        onclick="window.location='{{ route('admin.provider.details', [$provider->id, 'web_page' => 'overview']) }}'">
                                                        <td>
                                                            <span class="cell-primary">{{ $provider->company_name ?? '—' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="operations-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                                        </td>
                                                        <td class="datetime-main">{{ $provider->created_at?->format('d M, H:i a') ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="work-queue-empty">
                                            <span class="material-symbols-outlined">store</span>
                                            <span>{{ translate('No Record Found') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="work-queue-box-footer">
                                <a href="{{ route('admin.provider.list', ['sort' => 'latest']) }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                            </div>
                        </div>

                        <div class="work-queue-box tone-whatsapp" id="dashboard-recent-app-downloads">
                            <div class="work-queue-box-header">
                                <div class="work-queue-box-title">
                                    <span class="material-symbols-outlined">download</span>
                                    <span>{{ translate('Recent_app_downloads') }}</span>
                                </div>
                                <span class="work-queue-count-badge {{ ($summary['app_devices_this_month'] ?? 0) > 0 ? 'is-hot' : '' }}">{{ $recentAppDevices->count() }}</span>
                            </div>
                            <div class="work-queue-box-content">
                                <div class="work-queue-box-body active">
                                    @if($recentAppDevices->isNotEmpty())
                                        <div class="work-queue-table-wrap">
                                            <table class="work-queue-table">
                                                <thead>
                                                <tr>
                                                    <th class="col-type">{{ translate('user_type') }}</th>
                                                    <th class="col-name">{{ translate('Name') }}</th>
                                                    <th class="col-phone">{{ translate('phone') }}</th>
                                                    <th class="col-platform">{{ translate('platform') }}</th>
                                                    <th class="col-datetime">{{ translate('Date') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($recentAppDevices as $device)
                                                    @php
                                                        $deviceUser = $device->user;
                                                        $userType = $deviceUser?->user_type ?? '';
                                                        $personName = $deviceUser
                                                            ? trim(($deviceUser->first_name ?? '').' '.($deviceUser->last_name ?? ''))
                                                            : '';
                                                        $providerCompany = $deviceUser?->provider?->company_name
                                                            ?? $deviceUser?->serviceman?->provider?->company_name;
                                                        $typeLabel = match ($userType) {
                                                            'customer' => translate('Customer'),
                                                            'provider-admin' => translate('Provider'),
                                                            'provider-serviceman' => translate('Serviceman'),
                                                            default => $userType !== ''
                                                                ? ucfirst(str_replace(['-', '_'], ' ', $userType))
                                                                : '—',
                                                        };
                                                        $typeClass = match ($userType) {
                                                            'customer' => 'is-customer',
                                                            'provider-admin' => 'is-provider',
                                                            'provider-serviceman' => 'is-serviceman',
                                                            default => 'is-other',
                                                        };
                                                        $displayName = match ($userType) {
                                                            'provider-admin' => $providerCompany ?: ($personName ?: '—'),
                                                            'provider-serviceman' => $personName ?: '—',
                                                            default => $personName ?: '—',
                                                        };
                                                        $nameSub = match ($userType) {
                                                            'provider-admin' => $personName !== '' && $providerCompany !== ''
                                                                ? $personName
                                                                : ($deviceUser?->provider?->contact_person_name ?? null),
                                                            'provider-serviceman' => $providerCompany,
                                                            default => null,
                                                        };
                                                        $phone = $deviceUser?->phone ?? '—';
                                                        $isDeleted = $deviceUser?->trashed() ?? false;
                                                        $detailUrl = match ($userType) {
                                                            'customer' => ($deviceUser && ! $isDeleted)
                                                                ? route('admin.customer.detail', [$deviceUser->id, 'web_page' => 'overview'])
                                                                : null,
                                                            'provider-admin' => ($deviceUser?->provider && ! $isDeleted)
                                                                ? route('admin.provider.details', [$deviceUser->provider->id, 'web_page' => 'overview'])
                                                                : null,
                                                            'provider-serviceman' => ($deviceUser?->serviceman?->provider && ! $isDeleted)
                                                                ? route('admin.provider.details', [$deviceUser->serviceman->provider->id, 'web_page' => 'overview'])
                                                                : null,
                                                            default => null,
                                                        };
                                                    @endphp
                                                    <tr @if($detailUrl) class="is-clickable" onclick="window.location='{{ $detailUrl }}'" @endif>
                                                        <td>
                                                            <span class="operations-app-type-badge {{ $typeClass }}">{{ $typeLabel }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="cell-primary">{{ $displayName }}</span>
                                                            @if(! empty($nameSub))
                                                                <span class="cell-secondary">{{ $nameSub }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="cell-primary">{{ $phone ?: '—' }}</span>
                                                            @if($isDeleted)
                                                                <span class="cell-secondary">{{ translate('Deleted') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="cell-primary">{{ strtoupper($device->platform ?? '—') }}</span>
                                                            @if($device->device_model)
                                                                <span class="cell-secondary">{{ $device->device_model }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="datetime-main">{{ ($device->created_at ?? $device->last_seen_at)?->format('d M, H:i a') ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="work-queue-empty">
                                            <span class="material-symbols-outlined">smartphone</span>
                                            <span>{{ translate('No Record Found') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="work-queue-box-footer">
                                <a href="{{ route('admin.customer.index', ['app_filter' => 'registered']) }}" class="work-queue-footer-link is-single">{{ translate('view_all') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="text-center">{{ translate('welcome_to_admin_panel') }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @else
        <div class="main-content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body dashboard-empty d-center">
                        <div class="text-center">
                            <img src="{{ asset('/assets/empty-dashboard.png') }}" alt="">
                            <h3 class="p-2 mt-3">{{ translate('Welcome to') }} {{ business_config('business_name', 'business_information')?->live_values }}</h3>
                            <p>{{ translate('Get started by using the left menu to manage your tasks and tools.') }}</p>
                            <h6>{{ translate('Happy working') }}!</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection
