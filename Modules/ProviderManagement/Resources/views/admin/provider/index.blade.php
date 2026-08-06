@extends('adminmodule::layouts.master')

@section('title',translate('provider_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/select.dataTables.min.css')}}"/>
    <style>
        .provider-list-header {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.5rem 0.65rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid var(--bs-border-color, #dee2e6);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .provider-list-header__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .provider-list-header__title-count {
            font-weight: 500;
            opacity: 0.55;
            font-size: 0.9em;
        }

        .provider-list-header__search {
            flex: 1 1 10rem;
            min-width: 9rem;
            max-width: 15rem;
            margin: 0;
        }

        .provider-list-header__search .input-group {
            min-height: 2rem;
        }

        .provider-list-header__search .search-form__input {
            min-height: 2rem;
            height: 2rem;
            font-size: 0.75rem;
            padding-top: 0.2rem;
            padding-bottom: 0.2rem;
        }

        .provider-list-header__search .search-form__icon {
            padding: 0 0.35rem;
        }

        .provider-list-header__search .search-form__icon .material-icons {
            font-size: 1rem;
        }

        .provider-list-header__actions {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex: 0 0 auto;
            margin-inline-start: auto;
        }

        .provider-list-header__actions .btn {
            min-height: 2rem;
            height: 2rem;
            font-size: 0.75rem;
            padding: 0.2rem 0.55rem;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }

        .provider-list-header__actions .btn .material-icons {
            font-size: 1rem;
        }

        .provider-list-filters-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .provider-list-filters-toggle .filter-count-badge {
            min-width: 1.125rem;
            height: 1.125rem;
            padding: 0 0.3rem;
            border-radius: 999px;
            font-size: 0.65rem;
            font-weight: 600;
            line-height: 1.125rem;
            background: var(--bs-primary, #0d6efd);
            color: #fff;
        }

        .provider-list-filters-offcanvas {
            width: min(100vw, 24rem);
            max-width: 100%;
        }

        .provider-list-filters-offcanvas .offcanvas-header {
            padding: 0.85rem 1rem;
        }

        .provider-list-filters-offcanvas .offcanvas-title {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .provider-list-filters-offcanvas.offcanvas {
            display: flex;
            flex-direction: column;
        }

        .provider-list-filters-offcanvas #provider-list-filter-form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }

        .provider-list-filters-offcanvas .offcanvas-body {
            padding: 1rem;
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .provider-list-filters-offcanvas .form-label {
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.35rem;
        }

        .provider-list-filters-offcanvas .form-control,
        .provider-list-filters-offcanvas .form-select {
            min-height: 2.25rem;
            font-size: 0.8125rem;
            background-color: #fff;
            border: 1px solid var(--bs-border-color, #dee2e6);
            color: var(--bs-body-color, #212529);
            border-radius: 0.375rem;
        }

        .provider-list-filters-offcanvas .form-control:focus,
        .provider-list-filters-offcanvas .form-select:focus {
            background-color: #fff;
            border-color: rgba(13, 110, 253, 0.55);
            color: var(--bs-body-color, #212529);
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
        }

        .provider-list-filters-offcanvas .form-control::placeholder {
            color: rgba(33, 37, 41, 0.45);
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-selection--single {
            background-color: #fff !important;
            border: 1px solid var(--bs-border-color, #dee2e6) !important;
            min-height: 2.25rem;
            height: 2.25rem;
            border-radius: 0.375rem;
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--bs-body-color, #212529) !important;
            line-height: 2.25rem !important;
            padding-left: 0.75rem;
            padding-right: 2rem;
            font-size: 0.8125rem;
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgba(33, 37, 41, 0.45) !important;
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.25rem;
            right: 0.45rem;
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: rgba(33, 37, 41, 0.55) transparent transparent transparent;
        }

        .provider-list-filters-offcanvas .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent rgba(33, 37, 41, 0.55) transparent;
        }

        .provider-list-filters-offcanvas .select2-dropdown {
            background-color: #fff !important;
            border: 1px solid var(--bs-border-color, #dee2e6) !important;
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 0.35rem 0.85rem rgba(15, 23, 42, 0.08);
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-results__option {
            color: var(--bs-body-color, #212529);
            font-size: 0.8125rem;
            padding: 0.45rem 0.75rem;
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: rgba(13, 110, 253, 0.08) !important;
            color: var(--bs-body-color, #212529) !important;
        }

        .provider-list-filters-offcanvas .select2-container--default .select2-results__option--selected {
            background-color: rgba(13, 110, 253, 0.12) !important;
            color: var(--bs-primary, #0d6efd) !important;
        }

        .provider-list-filters-offcanvas .select2-container--default.select2-container--focus .select2-selection--single,
        .provider-list-filters-offcanvas .select2-container--default.select2-container--open .select2-selection--single {
            border-color: rgba(13, 110, 253, 0.55) !important;
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
        }

        .provider-list-filters-offcanvas .select2-search--dropdown .select2-search__field {
            background-color: #fff !important;
            border: 1px solid var(--bs-border-color, #dee2e6) !important;
            color: var(--bs-body-color, #212529) !important;
        }

        .provider-list-filters-offcanvas .offcanvas-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.85rem 1rem;
            border-top: 1px solid var(--bs-border-color, #dee2e6);
            background: var(--bs-gray-100, #f8f9fa);
        }

        .provider-list-filters-offcanvas .select2-container {
            width: 100% !important;
        }

        .provider-list-filters-offcanvas .filter-section + .filter-section {
            padding-top: 0.85rem;
            border-top: 1px solid var(--bs-border-color, #dee2e6);
        }

        .provider-list-filters-offcanvas .filter-section {
            padding-bottom: 0.15rem;
        }

        .provider-list-filters-offcanvas .filter-section__title {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: rgba(33, 37, 41, 0.55);
            margin-bottom: 0.55rem;
        }

        .provider-list-filters-offcanvas .filter-section__hint {
            font-size: 0.7rem;
            color: rgba(33, 37, 41, 0.55);
            margin-top: 0.25rem;
            margin-bottom: 0;
            line-height: 1.35;
        }

        .provider-list-filters-offcanvas .provider-filter-segmented {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.2rem;
            padding: 0.2rem;
            background: var(--bs-gray-100, #f1f3f5);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.5rem;
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__option {
            position: relative;
            margin: 0;
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__label {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            padding: 0.35rem 0.45rem;
            margin: 0;
            border-radius: 0.35rem;
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.2;
            text-align: center;
            color: rgba(33, 37, 41, 0.68);
            background: transparent;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__option input:focus-visible + .provider-filter-segmented__label {
            outline: 2px solid rgba(13, 110, 253, 0.35);
            outline-offset: 1px;
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__option input:checked + .provider-filter-segmented__label {
            background: #fff;
            color: var(--bs-primary, #0d6efd);
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__option:hover .provider-filter-segmented__label {
            color: rgba(33, 37, 41, 0.9);
        }

        .provider-list-filters-offcanvas .provider-filter-segmented__option input:checked + .provider-filter-segmented__label:hover {
            color: var(--bs-primary, #0d6efd);
        }

        .provider-list-table-wrap .table.provider-list-table {
            font-size: 0.8125rem;
            margin-bottom: 0;
        }

        .provider-list-table-wrap .table.provider-list-table > :not(caption) > * > * {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }

        .provider-list-table-wrap .table.provider-list-table thead th {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            color: rgba(33, 37, 41, 0.72);
            background: var(--bs-gray-100, #f8f9fa);
            border-bottom-width: 1px;
        }

        .provider-list-table-wrap .provider-info-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        .provider-list-table-wrap .provider-info-cell__avatar {
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            border-radius: 0.35rem;
            overflow: hidden;
            background: var(--bs-gray-200, #e9ecef);
        }

        .provider-list-table-wrap .provider-info-cell__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .provider-list-table-wrap .provider-info-cell__body {
            min-width: 0;
            flex: 1 1 auto;
        }

        .provider-list-table-wrap .provider-info-cell__name {
            font-weight: 500;
            font-size: 0.8125rem;
            line-height: 1.25;
            display: block;
            max-width: 14rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .provider-list-table-wrap .provider-contact-link {
            font-size: 0.72rem;
            line-height: 1.25;
            display: inline-block;
            max-width: 11rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .provider-list-table-wrap .provider-list-categories {
            font-size: 0.72rem;
            line-height: 1.35;
            max-width: 11rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
            color: rgba(33, 37, 41, 0.78);
        }

        .provider-list-table-wrap .provider-list-rating {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
        }

        .provider-list-table-wrap .provider-list-rating__stars {
            display: inline-flex;
            align-items: center;
            gap: 0;
            line-height: 1;
        }

        .provider-list-table-wrap .provider-list-rating__stars .material-icons,
        .provider-list-table-wrap .provider-list-rating__stars .material-symbols-outlined {
            font-size: 0.78rem;
            color: #f5a623;
        }

        .provider-list-table-wrap .provider-list-rating__count {
            font-size: 0.72rem;
            font-weight: 600;
            color: #758590;
            white-space: nowrap;
        }

        .provider-list-table-wrap .provider-app-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-size: 0.65rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .provider-list-table-wrap .provider-app-badge--active {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            border-color: rgba(25, 135, 84, 0.2);
        }

        .provider-list-table-wrap .provider-app-badge--registered {
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.18);
        }

        .provider-list-table-wrap .provider-app-badge--none {
            background: rgba(108, 117, 125, 0.08);
            color: #6c757d;
            border-color: rgba(108, 117, 125, 0.16);
        }

        .provider-list-table-wrap .provider-list-actions {
            display: flex;
            gap: 0.25rem;
            justify-content: center;
        }

        .provider-list-table-wrap .provider-list-actions .action-btn {
            --size: 26px;
        }

        .provider-list-table-wrap .switcher {
            transform: scale(0.88);
            transform-origin: center;
        }

        .provider-list-table-wrap .provider-list-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-size: 0.65rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .provider-list-table-wrap .provider-list-status-badge--on {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            border-color: rgba(25, 135, 84, 0.2);
        }

        .provider-list-table-wrap .provider-list-status-badge--off {
            background: rgba(108, 117, 125, 0.08);
            color: #6c757d;
            border-color: rgba(108, 117, 125, 0.16);
        }

        .provider-list-card > .card-body {
            padding: 0.85rem;
        }

        @media (max-width: 991.98px) {
            .provider-list-header {
                flex-wrap: wrap;
                overflow-x: visible;
            }

            .provider-list-header__search {
                order: 10;
                flex: 1 1 100%;
                max-width: none;
            }

            .provider-list-header__actions {
                order: 11;
                margin-inline-start: 0;
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $activeFilterCount = collect([
            ($status ?? 'all') !== 'all',
            ($queryParam['app_filter'] ?? 'all') !== 'all',
            ($queryParam['rating_filter'] ?? 'all') !== 'all',
            ($performanceFilter ?? 'all') !== 'all',
            !empty($queryParam['category_id']),
            !empty($queryParam['zone_id']),
            !empty($queryParam['from']),
            !empty($queryParam['to']),
            !empty($queryParam['sort']) && ($queryParam['sort'] ?? '') !== 'latest',
            !empty($queryParam['limit']),
        ])->filter()->count();

        $canManageStatus = auth()->user()->can('provider_manage_status');
        $canUpdate = auth()->user()->can('provider_update');
        $tableColspan = 11 + ($canUpdate ? 1 : 0);
    @endphp

    <div class="main-content provider-list-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card provider-list-card">
                        <div class="card-body">
                            <div class="provider-list-header">
                                <h2 class="provider-list-header__title page-title">
                                    {{translate('provider_list')}}
                                    <span class="provider-list-header__title-count">({{$providers->total()}})</span>
                                </h2>

                                <form action="{{url()->current()}}"
                                      class="search-form search-form_style-two provider-list-header__search"
                                      method="GET"
                                      id="provider-list-search-form">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search"
                                               class="theme-input-style search-form__input"
                                               value="{{$search}}"
                                               name="search"
                                               id="provider-list-search-input"
                                               placeholder="{{translate('search_here')}}"
                                               autocomplete="off">
                                    </div>
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <input type="hidden" name="performance_filter" value="{{ $queryParam['performance_filter'] ?? 'all' }}">
                                    <input type="hidden" name="category_id" value="{{ $queryParam['category_id'] ?? '' }}">
                                    <input type="hidden" name="zone_id" value="{{ $queryParam['zone_id'] ?? '' }}">
                                    <input type="hidden" name="sort" value="{{ $queryParam['sort'] ?? 'latest' }}">
                                    <input type="hidden" name="from" value="{{ $queryParam['from'] ?? '' }}">
                                    <input type="hidden" name="to" value="{{ $queryParam['to'] ?? '' }}">
                                    <input type="hidden" name="limit" value="{{ $queryParam['limit'] ?? '' }}">
                                    <input type="hidden" name="rating_filter" value="{{ $queryParam['rating_filter'] ?? 'all' }}">
                                    <input type="hidden" name="app_filter" value="{{ $queryParam['app_filter'] ?? 'all' }}">
                                </form>

                                <div class="provider-list-header__actions">
                                    <button type="button"
                                            class="btn btn--secondary provider-list-filters-toggle"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#providerListFiltersOffcanvas"
                                            aria-controls="providerListFiltersOffcanvas">
                                        <span class="material-icons">filter_list</span>
                                        {{ translate('filter') }}
                                        @if($activeFilterCount > 0)
                                            <span class="filter-count-badge">{{ $activeFilterCount }}</span>
                                        @endif
                                    </button>

                                    @can('provider_export')
                                        <div class="dropdown">
                                            <button type="button"
                                                    class="btn btn--secondary dropdown-toggle"
                                                    data-bs-toggle="dropdown"
                                                    title="{{ translate('download') }}">
                                                <span class="material-icons">file_download</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="{{ env('APP_ENV') != 'demo' ? route('admin.provider.download') . '?' . http_build_query(array_filter([
                                                           'search' => $queryParam['search'] ?? null,
                                                           'status' => $queryParam['status'] ?? null,
                                                           'performance_filter' => ($queryParam['performance_filter'] ?? 'all') !== 'all' ? $queryParam['performance_filter'] : null,
                                                           'category_id' => $queryParam['category_id'] ?? null,
                                                           'zone_id' => $queryParam['zone_id'] ?? null,
                                                           'sort' => ($queryParam['sort'] ?? 'latest') !== 'latest' ? $queryParam['sort'] : null,
                                                           'from' => $queryParam['from'] ?? null,
                                                           'to' => $queryParam['to'] ?? null,
                                                           'limit' => $queryParam['limit'] ?? null,
                                                           'rating_filter' => ($queryParam['rating_filter'] ?? 'all') !== 'all' ? $queryParam['rating_filter'] : null,
                                                           'app_filter' => ($queryParam['app_filter'] ?? 'all') !== 'all' ? $queryParam['app_filter'] : null,
                                                       ])) : 'javascript:demo_mode()' }}">
                                                        {{translate('excel')}}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan

                                    @can('provider_add')
                                        <a href="{{ route('admin.provider.create') }}" class="btn btn--primary" data-turbo="false">
                                            <span class="material-icons">add</span>
                                            {{ translate('Add_New_Provider') }}
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="table-responsive provider-list-table-wrap">
                                <table id="example" class="table table-sm align-middle provider-list-table">
                                    <thead>
                                    <tr>
                                        <th>{{translate('Provider_Info')}}</th>
                                        <th>{{translate('Category')}}</th>
                                        <th class="text-center">{{translate('Rating')}}</th>
                                        <th>{{translate('phone')}}</th>
                                        <th class="text-center">{{translate('Total_Bookings')}}</th>
                                        <th class="text-center">{{translate('App')}}</th>
                                        <th class="text-center">{{translate('Joined')}}</th>
                                        <th class="text-center">{{translate('Performance_Status')}}</th>
                                        <th class="text-center">{{translate('Service_Availability')}}</th>
                                        <th class="text-center">{{translate('status')}}</th>
                                        <th class="text-center">{{translate('App_Availability')}}</th>
                                        @can('provider_update')
                                            <th class="text-center">{{translate('action')}}</th>
                                        @endcan
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($providers as $provider)
                                        @php
                                            $owner = $provider->owner;
                                            $hasAppDevice = $owner && (
                                                ((int) ($owner->fcm_devices_count ?? 0)) > 0
                                                || (function_exists('is_valid_fcm_token') && is_valid_fcm_token($owner->fcm_token ?? null))
                                            );
                                            $hasActiveSession = $owner && ((int) ($owner->active_app_sessions_count ?? 0)) > 0;
                                            $hasLoggedInBefore = $hasAppDevice
                                                || ($owner && ((int) ($owner->app_login_sessions_count ?? 0)) > 0);
                                            $providerPhone = $provider->company_phone ?: $provider->contact_person_phone;
                                            $avgRating = round((float) ($provider->avg_rating ?? 0), 1);
                                            $ratingCount = (int) ($provider->rating_count ?? 0);
                                            $providerListPerformance = \Modules\ProviderManagement\Services\ProviderManualPerformanceEnforcement::providerListPerformance($provider);
                                            $subscribedCategories = $provider->subscribed_services
                                                ? $provider->subscribed_services->pluck('category.name')->filter()->unique()->values()
                                                : collect();
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="provider-info-cell">
                                                    <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}"
                                                       class="provider-info-cell__avatar">
                                                        <img src="{{ $provider->list_avatar_full_path }}"
                                                             alt="{{ $provider->company_name }}"
                                                             onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                                    </a>
                                                    <div class="provider-info-cell__body">
                                                        <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}"
                                                           class="provider-info-cell__name text-decoration-none title-color"
                                                           title="{{ $provider->company_name }}">
                                                            {{ $provider->company_name }}
                                                            @php($restrictionLabel = \Modules\ProviderManagement\Services\ProviderManualPerformanceEnforcement::primaryRestrictionLabel($provider))
                                                            @if($restrictionLabel)
                                                                <span class="text-danger fz-12">({{ $restrictionLabel }})</span>
                                                            @endif
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="provider-list-categories"
                                                      title="{{ $subscribedCategories->implode(', ') }}">
                                                    {{ $subscribedCategories->isNotEmpty() ? $subscribedCategories->implode(', ') : '—' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="provider-list-rating"
                                                     title="{{ $avgRating }}/5 · {{ $ratingCount }} {{ translate('ratings') }}">
                                                    <div class="provider-list-rating__stars" aria-hidden="true">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            @if($avgRating >= $i)
                                                                <span class="material-icons">star</span>
                                                            @elseif($avgRating >= ($i - 0.5))
                                                                <span class="material-icons">star_half</span>
                                                            @else
                                                                <span class="material-symbols-outlined">grade</span>
                                                            @endif
                                                        @endfor
                                                    </div>
                                                    <span class="provider-list-rating__count">({{ $ratingCount }})</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if(env('APP_ENV')=='demo')
                                                    <span class="badge badge-primary">{{translate('protected')}}</span>
                                                @else
                                                    <a href="tel:{{ $providerPhone }}"
                                                       class="provider-contact-link text-decoration-none"
                                                       title="{{ $providerPhone }}">
                                                        {{ $providerPhone ?: '—' }}
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $provider->bookings_count }}</td>
                                            <td class="text-center">
                                                @if($hasActiveSession)
                                                    <span class="provider-app-badge provider-app-badge--active"
                                                          title="{{ translate('customer_app_status_active_hint') }}">
                                                        {{ translate('customer_app_status_active') }}
                                                    </span>
                                                @elseif($hasLoggedInBefore)
                                                    <span class="provider-app-badge provider-app-badge--registered"
                                                          title="{{ translate('customer_app_status_registered_hint') }}">
                                                        {{ translate('customer_app_status_registered') }}
                                                    </span>
                                                @else
                                                    <span class="provider-app-badge provider-app-badge--none"
                                                          title="{{ translate('customer_app_status_not_in_app_hint') }}">
                                                        {{ translate('customer_app_status_not_in_app') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center text-nowrap">{{ date('d M Y', strtotime($provider->created_at)) }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $providerListPerformance['badge'] }}">{{ $providerListPerformance['label'] }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($canManageStatus)
                                                    <label class="switcher mx-auto" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.service_availability', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider->service_availability?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                @else
                                                    <span class="provider-list-status-badge {{ $provider->service_availability ? 'provider-list-status-badge--on' : 'provider-list-status-badge--off' }}">
                                                        {{ $provider->service_availability ? translate('active') : translate('inactive') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($canManageStatus)
                                                    <label class="switcher mx-auto" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.status_update', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider?->owner?->is_active?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                @else
                                                    <span class="provider-list-status-badge {{ $provider?->owner?->is_active ? 'provider-list-status-badge--on' : 'provider-list-status-badge--off' }}">
                                                        {{ $provider?->owner?->is_active ? translate('active') : translate('inactive') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($canManageStatus)
                                                    <label class="switcher mx-auto" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input route-alert"
                                                               data-route="{{route('admin.provider.app_availability', [$provider->id])}}"
                                                               data-message="{{translate('want_to_update_status')}}"
                                                               type="checkbox" {{$provider->app_availability?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                @else
                                                    <span class="provider-list-status-badge {{ $provider->app_availability ? 'provider-list-status-badge--on' : 'provider-list-status-badge--off' }}">
                                                        {{ $provider->app_availability ? translate('active') : translate('inactive') }}
                                                    </span>
                                                @endif
                                            </td>
                                            @can('provider_update')
                                                <td>
                                                    <div class="provider-list-actions">
                                                        <a href="{{route('admin.provider.edit',[$provider->id])}}"
                                                           class="action-btn btn--light-primary"
                                                           title="{{ translate('edit') }}">
                                                            <span class="material-icons">edit</span>
                                                        </a>
                                                        <a href="{{route('admin.provider.details',[$provider->id, 'web_page'=>'overview'])}}"
                                                           class="action-btn btn--light-primary"
                                                           title="{{ translate('view') }}">
                                                            <span class="material-icons">visibility</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $tableColspan }}" class="text-center py-4 text-muted">
                                                {{ translate('No Provider Found') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                {!! $providers->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end provider-list-filters-offcanvas border-start shadow-sm"
         tabindex="-1"
         id="providerListFiltersOffcanvas"
         aria-labelledby="providerListFiltersOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title mb-0" id="providerListFiltersOffcanvasLabel">
                {{ translate('filter') }}
            </h5>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="{{ translate('close') }}"></button>
        </div>
        <form action="{{ url()->current() }}"
              method="GET"
              id="provider-list-filter-form"
              class="d-flex flex-column h-100">
            <input type="hidden" name="search" value="{{ $queryParam['search'] ?? '' }}">
            <div class="offcanvas-body d-flex flex-column gap-0">
                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('customer_filter_section_account') }}</div>
                    <label class="form-label">{{ translate('status') }}</label>
                    <div class="provider-filter-segmented" role="radiogroup" aria-label="{{ translate('status') }}">
                        <label class="provider-filter-segmented__option">
                            <input type="radio" name="status" value="all" {{ ($status ?? 'all') === 'all' ? 'checked' : '' }}>
                            <span class="provider-filter-segmented__label">{{ translate('all') }}</span>
                        </label>
                        <label class="provider-filter-segmented__option">
                            <input type="radio" name="status" value="active" {{ ($status ?? '') === 'active' ? 'checked' : '' }}>
                            <span class="provider-filter-segmented__label">{{ translate('active') }}</span>
                        </label>
                        <label class="provider-filter-segmented__option">
                            <input type="radio" name="status" value="inactive" {{ ($status ?? '') === 'inactive' ? 'checked' : '' }}>
                            <span class="provider-filter-segmented__label">{{ translate('inactive') }}</span>
                        </label>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('App') }}</div>
                    <label class="form-label visually-hidden" for="provider-filter-app">{{ translate('customer_filter_app_status') }}</label>
                    <select class="form-select js-select" id="provider-filter-app" name="app_filter">
                        <option value="all" {{ ($queryParam['app_filter'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ translate('customer_filter_app_all') }}</option>
                        <option value="active" {{ ($queryParam['app_filter'] ?? '') === 'active' ? 'selected' : '' }}>{{ translate('customer_app_status_active') }}</option>
                        <option value="registered" {{ ($queryParam['app_filter'] ?? '') === 'registered' ? 'selected' : '' }}>{{ translate('customer_app_status_registered') }}</option>
                        <option value="not_in_app" {{ ($queryParam['app_filter'] ?? '') === 'not_in_app' ? 'selected' : '' }}>{{ translate('customer_app_status_not_in_app') }}</option>
                    </select>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('Rating') }}</div>
                    <label class="form-label visually-hidden" for="provider-filter-rating">{{ translate('customer_filter_minimum_rating') }}</label>
                    <select class="form-select js-select" id="provider-filter-rating" name="rating_filter">
                        <option value="all" {{ ($queryParam['rating_filter'] ?? 'all') === 'all' ? 'selected' : '' }}>{{ translate('customer_filter_rating_all') }}</option>
                        <option value="4_plus" {{ ($queryParam['rating_filter'] ?? '') === '4_plus' ? 'selected' : '' }}>{{ translate('customer_filter_rating_4_plus') }}</option>
                        <option value="3_plus" {{ ($queryParam['rating_filter'] ?? '') === '3_plus' ? 'selected' : '' }}>{{ translate('customer_filter_rating_3_plus') }}</option>
                        <option value="2_plus" {{ ($queryParam['rating_filter'] ?? '') === '2_plus' ? 'selected' : '' }}>{{ translate('customer_filter_rating_2_plus') }}</option>
                        <option value="1_plus" {{ ($queryParam['rating_filter'] ?? '') === '1_plus' ? 'selected' : '' }}>{{ translate('customer_filter_rating_1_plus') }}</option>
                        <option value="unrated" {{ ($queryParam['rating_filter'] ?? '') === 'unrated' ? 'selected' : '' }}>{{ translate('customer_filter_rating_unrated') }}</option>
                    </select>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('Performance_Status') }}</div>
                    <label class="form-label visually-hidden" for="provider-filter-performance">{{ translate('Performance_Status') }}</label>
                    <select class="form-select js-select" id="provider-filter-performance" name="performance_filter">
                        <option value="all" {{ ($performanceFilter ?? 'all') === 'all' ? 'selected' : '' }}>{{ translate('all') }}</option>
                        <option value="warning" {{ ($performanceFilter ?? '') === 'warning' ? 'selected' : '' }}>{{ translate('Warning') }}</option>
                        <option value="blacklisted" {{ ($performanceFilter ?? '') === 'blacklisted' ? 'selected' : '' }}>{{ translate('Blacklisted') }}</option>
                    </select>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('Category') }} & {{ translate('Zone') }}</div>
                    <div class="mb-3">
                        <label class="form-label" for="provider-filter-category">{{ translate('Category') }}</label>
                        <select class="form-select js-select" id="provider-filter-category" name="category_id">
                            <option value="">{{ translate('all') }}</option>
                            <option value="none" {{ ($categoryId ?? '') === 'none' ? 'selected' : '' }}>{{ translate('No_Category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ ($categoryId ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="provider-filter-zone">{{ translate('Zone') }}</label>
                        <select class="form-select js-select" id="provider-filter-zone" name="zone_id">
                            <option value="">{{ translate('all') }}</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ ($zoneId ?? '') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('customer_filter_section_dates') }}</div>
                    <p class="filter-section__hint mb-2">{{ translate('customer_filter_joined_hint') }}</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="provider-filter-from">{{ translate('customer_filter_joined_from') }}</label>
                            <input type="date" class="form-control" id="provider-filter-from" name="from" value="{{ $queryParam['from'] ?? '' }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="provider-filter-to">{{ translate('customer_filter_joined_to') }}</label>
                            <input type="date" class="form-control" id="provider-filter-to" name="to" value="{{ $queryParam['to'] ?? '' }}">
                        </div>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('customer_filter_section_display') }}</div>
                    <div class="mb-3">
                        <label class="form-label" for="provider-filter-sort">{{ translate('sort_by') }}</label>
                        <select class="form-select js-select" id="provider-filter-sort" name="sort">
                            <option value="latest" {{ ($sort ?? 'latest') === 'latest' ? 'selected' : '' }}>{{ translate('Newest') }}</option>
                            <option value="oldest" {{ ($sort ?? '') === 'oldest' ? 'selected' : '' }}>{{ translate('Oldest') }}</option>
                            <option value="name_asc" {{ ($sort ?? '') === 'name_asc' ? 'selected' : '' }}>{{ translate('Name_A_Z') }}</option>
                            <option value="name_desc" {{ ($sort ?? '') === 'name_desc' ? 'selected' : '' }}>{{ translate('Name_Z_A') }}</option>
                            <option value="rating_desc" {{ ($sort ?? '') === 'rating_desc' ? 'selected' : '' }}>{{ translate('Highest_Rating') }}</option>
                            <option value="bookings_desc" {{ ($sort ?? '') === 'bookings_desc' ? 'selected' : '' }}>{{ translate('Most_Bookings') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="provider-filter-limit">{{ translate('customer_filter_max_results') }}</label>
                        <input class="form-control"
                               type="number"
                               id="provider-filter-limit"
                               name="limit"
                               min="1"
                               placeholder="{{ translate('customer_filter_max_results_hint') }}"
                               value="{{ $queryParam['limit'] ?? '' }}">
                        <p class="filter-section__hint">{{ translate('customer_filter_max_results_hint') }}</p>
                    </div>
                </section>
            </div>
            <div class="offcanvas-footer">
                <a href="{{ url()->current() . '?' . http_build_query(array_filter(['search' => $search ?: null])) }}"
                   class="btn btn--secondary btn-sm">
                    {{ translate('Clear_all_Filter') }}
                </a>
                <button type="submit" class="btn btn--primary btn-sm">{{ translate('filter') }}</button>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        "use strict";

        $(document).ready(function () {
            var filterOffcanvas = document.getElementById('providerListFiltersOffcanvas');

            function initProviderFilterSelect2() {
                var $selects = $('#providerListFiltersOffcanvas .js-select');
                if (!$selects.length) {
                    return;
                }

                $selects.each(function () {
                    var $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        width: '100%',
                        minimumResultsForSearch: Infinity,
                        dropdownParent: filterOffcanvas ? $(filterOffcanvas) : $('body')
                    });
                });
            }

            initProviderFilterSelect2();

            if (filterOffcanvas) {
                filterOffcanvas.addEventListener('shown.bs.offcanvas', initProviderFilterSelect2);
            }
        });

        (function () {
            var form = document.getElementById('provider-list-search-form');
            var input = document.getElementById('provider-list-search-input');
            if (!form || !input) {
                return;
            }

            var debounceTimer = null;
            var lastSubmitted = (input.value || '').trim();

            function submitSearch() {
                var next = (input.value || '').trim();
                if (next === lastSubmitted) {
                    return;
                }
                lastSubmitted = next;
                form.submit();
            }

            input.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitSearch, 450);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    submitSearch();
                }
            });
        })();
    </script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('assets/admin-module/plugins/dataTables/dataTables.select.min.js')}}"></script>
@endpush
