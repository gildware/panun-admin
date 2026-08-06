@extends('adminmodule::layouts.master')

@section('title',translate('customer_list'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/jquery.dataTables.min.css')}}"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module/plugins/dataTables/select.dataTables.min.css')}}"/>
    <style>
        .customer-list-header {
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

        .customer-list-header__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .customer-list-header__title-count {
            font-weight: 500;
            opacity: 0.55;
            font-size: 0.9em;
        }

        .customer-list-header__search {
            flex: 1 1 10rem;
            min-width: 9rem;
            max-width: 15rem;
            margin: 0;
        }

        .customer-list-header__search .input-group {
            min-height: 2rem;
        }

        .customer-list-header__search .search-form__input {
            min-height: 2rem;
            height: 2rem;
            font-size: 0.75rem;
            padding-top: 0.2rem;
            padding-bottom: 0.2rem;
        }

        .customer-list-header__search .search-form__icon {
            padding: 0 0.35rem;
        }

        .customer-list-header__search .search-form__icon .material-icons {
            font-size: 1rem;
        }

        .customer-list-header__actions {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex: 0 0 auto;
            margin-inline-start: auto;
        }

        .customer-list-header__actions .btn {
            min-height: 2rem;
            height: 2rem;
            font-size: 0.75rem;
            padding: 0.2rem 0.55rem;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }

        .customer-list-header__actions .btn .material-icons {
            font-size: 1rem;
        }

        .customer-list-filters-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .customer-list-filters-toggle .filter-count-badge {
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

        .customer-list-filters-offcanvas {
            width: min(100vw, 24rem);
            max-width: 100%;
        }

        .customer-list-filters-offcanvas .offcanvas-header {
            padding: 0.85rem 1rem;
        }

        .customer-list-filters-offcanvas .offcanvas-title {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .customer-list-filters-offcanvas.offcanvas {
            display: flex;
            flex-direction: column;
        }

        .customer-list-filters-offcanvas #customer-list-filter-form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }

        .customer-list-filters-offcanvas .offcanvas-body {
            padding: 1rem;
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .customer-list-filters-offcanvas .form-label {
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.35rem;
        }

        .customer-list-filters-offcanvas .form-control,
        .customer-list-filters-offcanvas .form-select {
            min-height: 2.25rem;
            font-size: 0.8125rem;
            background-color: #fff;
            border: 1px solid var(--bs-border-color, #dee2e6);
            color: var(--bs-body-color, #212529);
            border-radius: 0.375rem;
        }

        .customer-list-filters-offcanvas .form-control:focus,
        .customer-list-filters-offcanvas .form-select:focus {
            background-color: #fff;
            border-color: rgba(13, 110, 253, 0.55);
            color: var(--bs-body-color, #212529);
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
        }

        .customer-list-filters-offcanvas .form-control::placeholder {
            color: rgba(33, 37, 41, 0.45);
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-selection--single {
            background-color: #fff !important;
            border: 1px solid var(--bs-border-color, #dee2e6) !important;
            min-height: 2.25rem;
            height: 2.25rem;
            border-radius: 0.375rem;
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--bs-body-color, #212529) !important;
            line-height: 2.25rem !important;
            padding-left: 0.75rem;
            padding-right: 2rem;
            font-size: 0.8125rem;
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgba(33, 37, 41, 0.45) !important;
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.25rem;
            right: 0.45rem;
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: rgba(33, 37, 41, 0.55) transparent transparent transparent;
        }

        .customer-list-filters-offcanvas .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent rgba(33, 37, 41, 0.55) transparent;
        }

        .customer-list-filters-offcanvas .select2-dropdown {
            background-color: #fff !important;
            border: 1px solid var(--bs-border-color, #dee2e6) !important;
            border-radius: 0.375rem;
            overflow: hidden;
            box-shadow: 0 0.35rem 0.85rem rgba(15, 23, 42, 0.08);
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-results__option {
            color: var(--bs-body-color, #212529);
            font-size: 0.8125rem;
            padding: 0.45rem 0.75rem;
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: rgba(13, 110, 253, 0.08) !important;
            color: var(--bs-body-color, #212529) !important;
        }

        .customer-list-filters-offcanvas .select2-container--default .select2-results__option--selected {
            background-color: rgba(13, 110, 253, 0.12) !important;
            color: var(--bs-primary, #0d6efd) !important;
        }

        .customer-list-filters-offcanvas .select2-container--default.select2-container--focus .select2-selection--single,
        .customer-list-filters-offcanvas .select2-container--default.select2-container--open .select2-selection--single {
            border-color: rgba(13, 110, 253, 0.55) !important;
            box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
        }

        .customer-list-filters-offcanvas .select2-search--dropdown .select2-search__field {
            background-color: #fff !important;
            border: 1px solid var(--bs-border-color, #dee2e6) !important;
            color: var(--bs-body-color, #212529) !important;
        }

        .customer-list-filters-offcanvas .offcanvas-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.85rem 1rem;
            border-top: 1px solid var(--bs-border-color, #dee2e6);
            background: var(--bs-gray-100, #f8f9fa);
        }

        .customer-list-filters-offcanvas .select2-container {
            width: 100% !important;
        }

        .customer-list-filters-offcanvas .filter-section + .filter-section {
            padding-top: 0.85rem;
            border-top: 1px solid var(--bs-border-color, #dee2e6);
        }

        .customer-list-filters-offcanvas .filter-section {
            padding-bottom: 0.15rem;
        }

        .customer-list-filters-offcanvas .filter-section__title {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: rgba(33, 37, 41, 0.55);
            margin-bottom: 0.55rem;
        }

        .customer-list-filters-offcanvas .filter-section__hint {
            font-size: 0.7rem;
            color: rgba(33, 37, 41, 0.55);
            margin-top: 0.25rem;
            margin-bottom: 0;
            line-height: 1.35;
        }

        .customer-list-filters-offcanvas .customer-filter-segmented {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.2rem;
            padding: 0.2rem;
            background: var(--bs-gray-100, #f1f3f5);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.5rem;
        }

        .customer-list-filters-offcanvas .customer-filter-segmented__option {
            position: relative;
            margin: 0;
        }

        .customer-list-filters-offcanvas .customer-filter-segmented__option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .customer-list-filters-offcanvas .customer-filter-segmented__label {
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

        .customer-list-filters-offcanvas .customer-filter-segmented__option input:focus-visible + .customer-filter-segmented__label {
            outline: 2px solid rgba(13, 110, 253, 0.35);
            outline-offset: 1px;
        }

        .customer-list-filters-offcanvas .customer-filter-segmented__option input:checked + .customer-filter-segmented__label {
            background: #fff;
            color: var(--bs-primary, #0d6efd);
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        .customer-list-filters-offcanvas .customer-filter-segmented__option:hover .customer-filter-segmented__label {
            color: rgba(33, 37, 41, 0.9);
        }

        .customer-list-filters-offcanvas .customer-filter-segmented__option input:checked + .customer-filter-segmented__label:hover {
            color: var(--bs-primary, #0d6efd);
        }

        .customer-list-table-wrap .table.customer-list-table {
            font-size: 0.8125rem;
            margin-bottom: 0;
        }

        .customer-list-table-wrap .table.customer-list-table > :not(caption) > * > * {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }

        .customer-list-table-wrap .table.customer-list-table thead th {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
            color: rgba(33, 37, 41, 0.72);
            background: var(--bs-gray-100, #f8f9fa);
            border-bottom-width: 1px;
        }

        .customer-list-table-wrap .table.customer-list-table tbody td {
            line-height: 1.35;
        }

        .customer-list-table-wrap .customer-info-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }

        .customer-list-table-wrap .customer-info-cell__avatar {
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            border-radius: 0.35rem;
            overflow: hidden;
            background: var(--bs-gray-200, #e9ecef);
        }

        .customer-list-table-wrap .customer-info-cell__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .customer-list-table-wrap .customer-info-cell__body {
            min-width: 0;
            flex: 1 1 auto;
        }

        .customer-list-table-wrap .customer-info-cell__name {
            font-weight: 500;
            font-size: 0.8125rem;
            line-height: 1.25;
            display: block;
            max-width: 14rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .customer-list-table-wrap .customer-contact-link {
            font-size: 0.72rem;
            line-height: 1.25;
            display: inline-block;
            max-width: 11rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .customer-list-table-wrap .customer-list-rating {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
        }

        .customer-list-table-wrap .customer-list-rating__stars {
            display: inline-flex;
            align-items: center;
            gap: 0;
            line-height: 1;
        }

        .customer-list-table-wrap .customer-list-rating__stars .material-icons,
        .customer-list-table-wrap .customer-list-rating__stars .material-symbols-outlined {
            font-size: 0.78rem;
            color: #f5a623;
        }

        .customer-list-table-wrap .customer-list-rating__count {
            font-size: 0.72rem;
            font-weight: 600;
            color: #758590;
            white-space: nowrap;
        }

        .customer-list-table-wrap .customer-app-badge {
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

        .customer-list-table-wrap .customer-app-badge--active {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            border-color: rgba(25, 135, 84, 0.2);
        }

        .customer-list-table-wrap .customer-app-badge--registered {
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            border-color: rgba(13, 110, 253, 0.18);
        }

        .customer-list-table-wrap .customer-app-badge--none {
            background: rgba(108, 117, 125, 0.08);
            color: #6c757d;
            border-color: rgba(108, 117, 125, 0.16);
        }

        .customer-list-table-wrap .customer-list-actions {
            display: flex;
            gap: 0.25rem;
            justify-content: center;
        }

        .customer-list-table-wrap .customer-list-actions .action-btn {
            --size: 26px;
        }

        .customer-list-table-wrap .switcher {
            transform: scale(0.88);
            transform-origin: center;
        }

        .customer-list-card > .card-body {
            padding: 0.85rem;
        }

        @media (max-width: 991.98px) {
            .customer-list-header {
                flex-wrap: wrap;
                overflow-x: visible;
            }

            .customer-list-header__search {
                order: 10;
                flex: 1 1 100%;
                max-width: none;
            }

            .customer-list-header__actions {
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
            !empty($queryParam['from']),
            !empty($queryParam['to']),
            !empty($queryParam['sort_by']) && ($queryParam['sort_by'] ?? '') !== 'latest',
            !empty($queryParam['limit']),
        ])->filter()->count();
    @endphp

    <div class="main-content customer-list-page">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card customer-list-card">
                        <div class="card-body">
                            <div class="customer-list-header">
                                <h2 class="customer-list-header__title page-title">
                                    {{translate('customer_list')}}
                                    <span class="customer-list-header__title-count">({{$customers->total()}})</span>
                                </h2>

                                <form action="{{url()->current()}}"
                                      class="search-form search-form_style-two customer-list-header__search"
                                      method="GET"
                                      id="customer-list-search-form">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search"
                                               class="theme-input-style search-form__input"
                                               value="{{$search}}"
                                               name="search"
                                               id="customer-list-search-input"
                                               placeholder="{{translate('search_here')}}"
                                               autocomplete="off">
                                    </div>
                                    <input type="hidden" name="from" value="{{ $queryParam['from'] ?? '' }}">
                                    <input type="hidden" name="to" value="{{ $queryParam['to'] ?? '' }}">
                                    <input type="hidden" name="sort_by" value="{{ $queryParam['sort_by'] ?? '' }}">
                                    <input type="hidden" name="limit" value="{{ $queryParam['limit'] ?? '' }}">
                                    <input type="hidden" name="rating_filter" value="{{ $queryParam['rating_filter'] ?? 'all' }}">
                                    <input type="hidden" name="app_filter" value="{{ $queryParam['app_filter'] ?? 'all' }}">
                                    <input type="hidden" name="status" value="{{ $status }}">
                                </form>

                                <div class="customer-list-header__actions">
                                    <button type="button"
                                            class="btn btn--secondary customer-list-filters-toggle"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#customerListFiltersOffcanvas"
                                            aria-controls="customerListFiltersOffcanvas">
                                        <span class="material-icons">filter_list</span>
                                        {{ translate('filter') }}
                                        @if($activeFilterCount > 0)
                                            <span class="filter-count-badge">{{ $activeFilterCount }}</span>
                                        @endif
                                    </button>

                                    @can('customer_export')
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
                                                       href="{{env('APP_ENV') !='demo' ?route('admin.customer.download', '?search='. ($queryParam['search'] ?? '') .
                                                                 '&from='. ($queryParam['from'] ?? '') .
                                                                 '&to='. ($queryParam['to'] ?? '') .
                                                                 '&limit='. ($queryParam['limit'] ?? '') .
                                                                 '&status='. ($queryParam['status'] ?? '') .
                                                                 '&sort_by='. ($queryParam['sort_by'] ?? '') .
                                                                 '&rating_filter='. ($queryParam['rating_filter'] ?? 'all') .
                                                                 '&app_filter='. ($queryParam['app_filter'] ?? 'all') ).'?search='.$search:'javascript:demo_mode()'}}">
                                                        {{translate('excel')}}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan

                                    @can('customer_add')
                                        <a href="{{route('admin.customer.create')}}" class="btn btn--primary">
                                            <span class="material-icons">add</span>
                                            {{translate('add_customer')}}
                                        </a>
                                    @endcan
                                </div>
                            </div>

                            <div class="table-responsive customer-list-table-wrap">
                                <table id="example" class="table table-sm align-middle customer-list-table">
                                    <thead>
                                    <tr>
                                        <th>{{translate('Customer_Info')}}</th>
                                        <th class="text-center">{{translate('Rating')}}</th>
                                        <th>{{translate('phone')}}</th>
                                        <th class="text-center">{{translate('Total_Bookings')}}</th>
                                        <th class="text-center">{{translate('App')}}</th>
                                        <th class="text-center">{{translate('Joined')}}</th>
                                        @can('customer_manage_status')
                                            <th class="text-center">{{translate('status')}}</th>
                                        @endcan
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($customers as $customer)
                                        <tr>
                                            <td>
                                                <div class="customer-info-cell">
                                                    <a href="{{route('admin.customer.detail',[$customer->id, 'web_page'=>'overview'])}}"
                                                       class="customer-info-cell__avatar">
                                                        <img src="{{ $customer->profile_image_full_path }}"
                                                             alt="{{ $customer->first_name }} {{ $customer->last_name }}"
                                                             onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                                    </a>
                                                    <div class="customer-info-cell__body">
                                                        <a href="{{route('admin.customer.detail',[$customer->id, 'web_page'=>'overview'])}}"
                                                           class="customer-info-cell__name text-decoration-none title-color"
                                                           title="{{$customer->first_name}} {{$customer->last_name}}">
                                                            {{$customer->first_name}} {{$customer->last_name}}
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $avgRating = round((float) ($customer->received_avg_rating ?? 0), 1);
                                                    $ratingCount = (int) ($customer->received_rating_count ?? 0);
                                                @endphp
                                                <div class="customer-list-rating"
                                                     title="{{ $avgRating }}/5 · {{ $ratingCount }} {{ translate('ratings') }}">
                                                    <div class="customer-list-rating__stars" aria-hidden="true">
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
                                                    <span class="customer-list-rating__count">({{ $ratingCount }})</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if(env('APP_ENV')=='demo')
                                                    <span class="badge badge-primary">{{translate('protected')}}</span>
                                                @else
                                                    <a href="tel:{{$customer->phone}}"
                                                       class="customer-contact-link text-decoration-none"
                                                       title="{{$customer->phone}}">
                                                        {{ $customer->phone ?: '—' }}
                                                    </a>
                                                @endif
                                            </td>
                                            <td class="text-center">{{$customer->bookings_count}}</td>
                                            <td class="text-center">
                                                @php
                                                    $hasAppDevice = ((int) ($customer->fcm_devices_count ?? 0)) > 0
                                                        || (function_exists('is_valid_fcm_token') && is_valid_fcm_token($customer->fcm_token ?? null));
                                                    $hasActiveSession = ((int) ($customer->active_app_sessions_count ?? 0)) > 0;
                                                    $hasLoggedInBefore = $hasAppDevice
                                                        || ((int) ($customer->app_login_sessions_count ?? 0)) > 0;
                                                @endphp
                                                @if($hasActiveSession)
                                                    <span class="customer-app-badge customer-app-badge--active"
                                                          title="{{ translate('customer_app_status_active_hint') }}">
                                                        {{ translate('customer_app_status_active') }}
                                                    </span>
                                                @elseif($hasLoggedInBefore)
                                                    <span class="customer-app-badge customer-app-badge--registered"
                                                          title="{{ translate('customer_app_status_registered_hint') }}">
                                                        {{ translate('customer_app_status_registered') }}
                                                    </span>
                                                @else
                                                    <span class="customer-app-badge customer-app-badge--none"
                                                          title="{{ translate('customer_app_status_not_in_app_hint') }}">
                                                        {{ translate('customer_app_status_not_in_app') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center text-nowrap">{{date('d M Y', strtotime($customer->created_at))}}</td>
                                            @can('customer_manage_status')
                                                <td class="text-center">
                                                    <label class="switcher mx-auto" data-bs-toggle="modal"
                                                           data-bs-target="#deactivateAlertModal">
                                                        <input class="switcher_input"
                                                               type="checkbox"
                                                               {{$customer->is_active ? 'checked' : ''}}
                                                               data-status="{{$customer->id}}">
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>
                                            @endcan
                                            <td>
                                                <div class="customer-list-actions">
                                                    @can('customer_update')
                                                        <a href="{{env('APP_ENV') !='demo' ? route('admin.customer.edit',[$customer->id]) : 'javascript:demo_mode()'}}"
                                                           class="action-btn btn--light-primary"
                                                           title="{{ translate('edit') }}">
                                                            <span class="material-icons">edit</span>
                                                        </a>
                                                    @endcan
                                                    @can('customer_delete')
                                                        <button type="button"
                                                                data-delete="{{$customer->id}}"
                                                                data-id="delete-{{$customer->id}}"
                                                                data-message="{{translate('want_to_delete_this_customer')}}?"
                                                                class="action-btn btn--danger {{ env('APP_ENV') != 'demo' ? 'form-alert' : 'demo_check' }}"
                                                                title="{{ translate('delete') }}">
                                                            <span class="material-symbols-outlined">delete</span>
                                                        </button>
                                                    @endcan
                                                    <a href="{{route('admin.customer.detail',[$customer->id, 'web_page'=>'overview'])}}"
                                                       class="action-btn btn--light-primary"
                                                       title="{{ translate('view') }}">
                                                        <span class="material-icons">visibility</span>
                                                    </a>
                                                </div>
                                                <form action="{{route('admin.customer.delete',[$customer->id])}}"
                                                      method="post"
                                                      id="delete-{{$customer->id}}"
                                                      class="hidden">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()->can('customer_manage_status') ? 8 : 7 }}" class="text-center py-4 text-muted">
                                                {{ translate('no_data_found') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-2">
                                {!! $customers->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end customer-list-filters-offcanvas border-start shadow-sm"
         tabindex="-1"
         id="customerListFiltersOffcanvas"
         aria-labelledby="customerListFiltersOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title mb-0" id="customerListFiltersOffcanvasLabel">
                {{ translate('filter') }}
            </h5>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="{{ translate('close') }}"></button>
        </div>
        <form action="{{ url()->current() }}"
              method="GET"
              id="customer-list-filter-form"
              class="d-flex flex-column h-100">
            <input type="hidden" name="search" value="{{ $queryParam['search'] ?? '' }}">
            <div class="offcanvas-body d-flex flex-column gap-0">
                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('customer_filter_section_account') }}</div>
                    <label class="form-label">{{ translate('status') }}</label>
                    <div class="customer-filter-segmented" role="radiogroup" aria-label="{{ translate('status') }}">
                        <label class="customer-filter-segmented__option">
                            <input type="radio"
                                   name="status"
                                   value="all"
                                   {{ ($status ?? 'all') === 'all' ? 'checked' : '' }}>
                            <span class="customer-filter-segmented__label">{{ translate('all') }}</span>
                        </label>
                        <label class="customer-filter-segmented__option">
                            <input type="radio"
                                   name="status"
                                   value="active"
                                   {{ ($status ?? '') === 'active' ? 'checked' : '' }}>
                            <span class="customer-filter-segmented__label">{{ translate('active') }}</span>
                        </label>
                        <label class="customer-filter-segmented__option">
                            <input type="radio"
                                   name="status"
                                   value="inactive"
                                   {{ ($status ?? '') === 'inactive' ? 'checked' : '' }}>
                            <span class="customer-filter-segmented__label">{{ translate('inactive') }}</span>
                        </label>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('App') }}</div>
                    <label class="form-label visually-hidden" for="customer-filter-app">{{ translate('customer_filter_app_status') }}</label>
                    <select class="form-select js-select" id="customer-filter-app" name="app_filter">
                        <option value="all" {{ ($queryParam['app_filter'] ?? 'all') === 'all' ? 'selected' : '' }}>
                            {{ translate('customer_filter_app_all') }}
                        </option>
                        <option value="active" {{ ($queryParam['app_filter'] ?? '') === 'active' ? 'selected' : '' }}>
                            {{ translate('customer_app_status_active') }}
                        </option>
                        <option value="registered" {{ ($queryParam['app_filter'] ?? '') === 'registered' ? 'selected' : '' }}>
                            {{ translate('customer_app_status_registered') }}
                        </option>
                        <option value="not_in_app" {{ ($queryParam['app_filter'] ?? '') === 'not_in_app' ? 'selected' : '' }}>
                            {{ translate('customer_app_status_not_in_app') }}
                        </option>
                    </select>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('Rating') }}</div>
                    <label class="form-label visually-hidden" for="customer-filter-rating">{{ translate('customer_filter_minimum_rating') }}</label>
                    <select class="form-select js-select" id="customer-filter-rating" name="rating_filter">
                        <option value="all" {{ ($queryParam['rating_filter'] ?? 'all') === 'all' ? 'selected' : '' }}>
                            {{ translate('customer_filter_rating_all') }}
                        </option>
                        <option value="4_plus" {{ ($queryParam['rating_filter'] ?? '') === '4_plus' ? 'selected' : '' }}>
                            {{ translate('customer_filter_rating_4_plus') }}
                        </option>
                        <option value="3_plus" {{ ($queryParam['rating_filter'] ?? '') === '3_plus' ? 'selected' : '' }}>
                            {{ translate('customer_filter_rating_3_plus') }}
                        </option>
                        <option value="2_plus" {{ ($queryParam['rating_filter'] ?? '') === '2_plus' ? 'selected' : '' }}>
                            {{ translate('customer_filter_rating_2_plus') }}
                        </option>
                        <option value="1_plus" {{ ($queryParam['rating_filter'] ?? '') === '1_plus' ? 'selected' : '' }}>
                            {{ translate('customer_filter_rating_1_plus') }}
                        </option>
                        <option value="unrated" {{ ($queryParam['rating_filter'] ?? '') === 'unrated' ? 'selected' : '' }}>
                            {{ translate('customer_filter_rating_unrated') }}
                        </option>
                    </select>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('customer_filter_section_dates') }}</div>
                    <p class="filter-section__hint mb-2">{{ translate('customer_filter_joined_hint') }}</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label" for="customer-filter-from">{{ translate('customer_filter_joined_from') }}</label>
                            <input type="date"
                                   class="form-control"
                                   id="customer-filter-from"
                                   name="from"
                                   value="{{ $queryParam['from'] ?? '' }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="customer-filter-to">{{ translate('customer_filter_joined_to') }}</label>
                            <input type="date"
                                   class="form-control"
                                   id="customer-filter-to"
                                   name="to"
                                   value="{{ $queryParam['to'] ?? '' }}">
                        </div>
                    </div>
                </section>

                <section class="filter-section">
                    <div class="filter-section__title">{{ translate('customer_filter_section_display') }}</div>
                    <div class="mb-3">
                        <label class="form-label" for="customer-filter-sort">{{ translate('sort_by') }}</label>
                        <select class="form-select js-select" id="customer-filter-sort" name="sort_by">
                            <option value="latest" {{ ($queryParam['sort_by'] ?? 'latest') == 'latest' ? 'selected' : '' }}>{{ translate('latest') }}</option>
                            <option value="oldest" {{ ($queryParam['sort_by'] ?? '') == 'oldest' ? 'selected' : '' }}>{{ translate('oldest') }}</option>
                            <option value="ascending" {{ ($queryParam['sort_by'] ?? '') == 'ascending' ? 'selected' : '' }}>{{ translate('ascending') }}</option>
                            <option value="descending" {{ ($queryParam['sort_by'] ?? '') == 'descending' ? 'selected' : '' }}>{{ translate('descending') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="customer-filter-limit">{{ translate('customer_filter_max_results') }}</label>
                        <input class="form-control"
                               type="number"
                               id="customer-filter-limit"
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
                <button type="submit" class="btn btn--primary btn-sm">{{translate('filter')}}</button>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        "use strict";

        $(document).ready(function () {
            var filterOffcanvas = document.getElementById('customerListFiltersOffcanvas');

            function initCustomerFilterSelect2() {
                var $selects = $('#customerListFiltersOffcanvas .js-select');
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

            initCustomerFilterSelect2();

            if (filterOffcanvas) {
                filterOffcanvas.addEventListener('shown.bs.offcanvas', initCustomerFilterSelect2);
            }
        });

        $('.switcher_input').on('click', function () {
            let itemId = $(this).data('status');
            let route = '{{ route('admin.customer.status-update', ['id' => ':itemId']) }}';
            route = route.replace(':itemId', itemId);
            route_alert(route, '{{ translate('want_to_update_status') }}');
        });

        (function () {
            var form = document.getElementById('customer-list-search-form');
            var input = document.getElementById('customer-list-search-input');
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
