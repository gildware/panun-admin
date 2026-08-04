@extends('adminmodule::layouts.master')

@section('title', translate('Booking_List'))

@push('css_or_js')
    @include('bookingmodule::admin.booking.partials._booking-followup-styles')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-list-compact.css') }}?v={{ filemtime(public_path('assets/admin-module/css/booking-list-compact.css')) }}">
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    @php
        $isCancelledByProviderList = request()->routeIs('admin.booking.list.cancelled_by_provider');
        $isCancelledByCustomerList = request()->routeIs('admin.booking.list.cancelled_by_customer');
        $bookingListFilterAction = $isCancelledByProviderList
            ? route('admin.booking.list.cancelled_by_provider', ['service_type' => $queryParams['service_type'] ?? 'all'])
            : ($isCancelledByCustomerList
                ? route('admin.booking.list.cancelled_by_customer', ['service_type' => $queryParams['service_type'] ?? 'all'])
                : route('admin.booking.list', [
                'booking_status' => $queryParams['booking_status'],
                'service_type' => $queryParams['service_type'],
                'booking_type' => $queryParams['booking_type'],
                'provider_assigned' => $queryParams['provider_assigned'],
            ]));
    @endphp
    <div class="filter-aside filter-aside--booking-compact">
        <div class="filter-aside__header d-flex justify-content-between align-items-center">
            <h3 class="filter-aside__title mb-0">{{ translate('Filter_your_Booking') }}</h3>
            <button type="button" class="btn-close p-2 btn-close-white"></button>
        </div>
        <form action="{{ $bookingListFilterAction }}" method="POST"
            enctype="multipart/form-data" id="filter-form" class="filter-aside__form">
            @csrf
            <div class="filter-aside__body d-flex flex-column">
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Booked_Date_Range') }}</label>
                    <div class="filter-aside__date-row">
                        <div class="filter-aside__field">
                            <label class="filter-aside__field-label" for="filter-booked-start-date">{{ translate('Start_Date') }}</label>
                            <input type="date" id="filter-booked-start-date" class="form-control filter-aside__date-input"
                                name="start_date" value="{{ $queryParams['start_date'] }}">
                        </div>
                        <div class="filter-aside__field">
                            <label class="filter-aside__field-label" for="filter-booked-end-date">{{ translate('End_Date') }}</label>
                            <input type="date" id="filter-booked-end-date" class="form-control filter-aside__date-input"
                                name="end_date" value="{{ $queryParams['end_date'] }}">
                        </div>
                    </div>
                </div>

                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Scheduled_Date_Range') }}</label>
                    <div class="filter-aside__date-row">
                        <div class="filter-aside__field">
                            <label class="filter-aside__field-label" for="filter-scheduled-start-date">{{ translate('Start_Date') }}</label>
                            <input type="date" id="filter-scheduled-start-date" class="form-control filter-aside__date-input"
                                name="schedule_start_date" value="{{ $queryParams['schedule_start_date'] }}">
                        </div>
                        <div class="filter-aside__field">
                            <label class="filter-aside__field-label" for="filter-scheduled-end-date">{{ translate('End_Date') }}</label>
                            <input type="date" id="filter-scheduled-end-date" class="form-control filter-aside__date-input"
                                name="schedule_end_date" value="{{ $queryParams['schedule_end_date'] }}">
                        </div>
                    </div>
                </div>

                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Select_Categories') }}</label>
                    <div class="filter-aside__field">
                        <select class="category-select theme-input-style w-100" name="category_ids[]" multiple="multiple"
                            id="category_selector__select">
                            <option value="all">{{ translate('Select All') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ in_array($category->id, $queryParams['category_ids'] ?? []) ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Select_Sub_Categories') }}</label>
                    <div class="filter-aside__field">
                        <select class="subcategory-select theme-input-style w-100" name="sub_category_ids[]"
                            multiple="multiple" id="sub_category_selector__select">
                            <option value="all">{{ translate('Select All') }}</option>
                            @foreach ($subCategories as $subCategory)
                                <option value="{{ $subCategory->id }}"
                                    {{ in_array($subCategory->id, $queryParams['sub_category_ids'] ?? []) ? 'selected' : '' }}>
                                    {{ $subCategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Select_Zones') }}</label>
                    <div class="filter-aside__field">
                        <select class="zone-select theme-input-style w-100" name="zone_ids[]" multiple="multiple"
                            id="zone_selector__select">
                            <option value="all">{{ translate('Select All') }}</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}"
                                    {{ in_array($zone->id, $queryParams['zone_ids'] ?? []) ? 'selected' : '' }}>
                                    {{ $zone->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-aside__section">
                    <label class="filter-aside__section-label">{{ translate('Select_Assignee') }}</label>
                    <div class="filter-aside__field">
                        <select class="assignee-select theme-input-style w-100" name="assignee_ids[]" multiple="multiple"
                            id="assignee_selector__select">
                            <option value="all">{{ translate('Select All') }}</option>
                            <option value="__unassigned__"
                                {{ in_array('__unassigned__', $queryParams['assignee_ids'] ?? [], true) ? 'selected' : '' }}>
                                {{ translate('Unassigned') }}
                            </option>
                            @foreach ($assigneeUsers ?? [] as $assigneeUser)
                                <option value="{{ $assigneeUser->id }}"
                                    {{ in_array($assigneeUser->id, $queryParams['assignee_ids'] ?? [], true) ? 'selected' : '' }}>
                                    {{ $assigneeUser->first_name }} {{ $assigneeUser->last_name }}
                                    ({{ $assigneeUser->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }})
                                    — {{ $assigneeUser->email ?? $assigneeUser->phone }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="filter-aside__bottom_btns">
                <button class="btn btn--secondary text-capitalize" id="reset-btn"
                    type="reset">{{ translate('Clear_all_Filter') }}</button>
                <button class="btn btn--primary text-capitalize" type="submit">{{ translate('Filter') }}</button>
            </div>
        </form>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div
                        class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2">
                        @if(($queryParams['booking_status'] ?? '') === 'reopened')
                            <h2 class="page-title">{{ translate('Reopened_bookings') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'on_hold')
                            <h2 class="page-title">{{ translate('On_hold_bookings') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'disputed_cancelled')
                            <h2 class="page-title">{{ translate('Disputed_and_Cancelled') }}</h2>
                        @elseif($isCancelledByProviderList)
                            <h2 class="page-title">{{ translate('Cancelled_by_provider') }}</h2>
                        @elseif($isCancelledByCustomerList)
                            <h2 class="page-title">{{ translate('Cancelled_by_customer') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'disputed_completed')
                            <h2 class="page-title">{{ translate('Disputed_and_Completed') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'loss_making_pending')
                            <h2 class="page-title">{{ translate('Bfs_list_badge_loss_making') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'loss_recovered')
                            <h2 class="page-title">{{ translate('Bfs_list_badge_loss_recovered') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'loss_settled')
                            <h2 class="page-title">{{ translate('Settled') }}</h2>
                        @elseif(($queryParams['booking_status'] ?? '') === 'all')
                            <h2 class="page-title">{{ translate('Booking_Requests') }}</h2>
                        @elseif($queryParams['booking_status'] ?? null)
                            <h2 class="page-title">{{ ucwords(str_replace('_', ' ', $queryParams['booking_status'])) }}</h2>
                        @else
                            <h2 class="page-title">{{ translate('Booking_Requests') }}</h2>
                        @endif

                        <div class="d-flex flex-wrap align-items-center gap-3 fw-medium">
                            <div class="d-flex gap-2 align-items-center">
                                <span class="opacity-75">{{ translate('Total_Request') }}:</span>
                                <span class="title-color">{{ $bookings->total() }}</span>
                            </div>
                            @can('booking_export')
                                <div class="dropdown">
                                    <button type="button"
                                        class="btn btn--secondary text-capitalize dropdown-toggle btn-sm h-45"
                                        data-bs-toggle="dropdown">
                                        <span class="material-icons">file_download</span>
                                        {{ translate('download') }}
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                                        <li><a class="dropdown-item"
                                                href="{{ route('admin.booking.download', $queryParams) }}">{{ translate('excel') }}</a>
                                        </li>
                                    </ul>
                                </div>
                            @endcan
                        </div>
                    </div>
                    @if($isCancelledByProviderList)
                        <p class="text-muted mb-3">{{ translate('Cancelled_by_provider_list_help') }}</p>
                    @endif
                    @if($isCancelledByCustomerList)
                        <p class="text-muted mb-3">{{ translate('Cancelled_by_customer_list_help') }}</p>
                    @endif
                    @php
                        $bookingListTabStatus = $queryParams['booking_status'] ?? 'all';
                        if ($bookingListTabStatus === '') {
                            $bookingListTabStatus = 'all';
                        }
                        $bookingStatusExtraTabs = [
                            'reopened',
                            'resolved',
                            'disputed_cancelled',
                            'disputed_completed',
                            'on_hold',
                            'hold_after_visit',
                            'completed_no_or_little',
                            'cancelled_after_visit',
                            'loss_making_pending',
                            'loss_making',
                            'loss_recovered',
                            'loss_settled',
                        ];
                        $bookingStatusExtraTabActive = in_array($bookingListTabStatus, $bookingStatusExtraTabs, true);
                        $isStandardListMode = ! $isCancelledByProviderList && ! $isCancelledByCustomerList;
                    @endphp

                    @if($isStandardListMode)
                    <div class="booking-list-compact-tabs booking-status-tabs-wrap {{ $bookingStatusExtraTabActive ? 'is-expanded' : '' }}" id="tabs-wrap">
                        <ul class="booking-list-tabs" id="booking-status-tabs">
                            <li>
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'all' ? 'active' : '' }}"
                                   data-status="all"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'all'])) }}">
                                    {{ translate('All Booking') }}
                                    <span class="count">{{ $bookingTabCounts['all'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'pending' ? 'active' : '' }}"
                                   data-status="pending"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'pending'])) }}">
                                    {{ translate('Pending_Booking') }}
                                    <span class="count">{{ $bookingTabCounts['pending'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'accepted' ? 'active' : '' }}"
                                   data-status="accepted"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'accepted'])) }}">
                                    {{ translate('Accepted') }}
                                    <span class="count">{{ $bookingTabCounts['accepted'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'canceled' ? 'active' : '' }}"
                                   data-status="canceled"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'canceled'])) }}">
                                    {{ translate('Cancelled') }}
                                    <span class="count">{{ $bookingTabCounts['canceled'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'ongoing' ? 'active' : '' }}"
                                   data-status="ongoing"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'ongoing'])) }}">
                                    {{ translate('Ongoing') }}
                                    <span class="count">{{ $bookingTabCounts['ongoing'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'completed' ? 'active' : '' }}"
                                   data-status="completed"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'completed'])) }}">
                                    {{ translate('Completed') }}
                                    <span class="count">{{ $bookingTabCounts['completed'] }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'reopened' ? 'active' : '' }}"
                                   data-status="reopened"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'reopened'])) }}">
                                    {{ translate('Reopened') }}
                                    <span class="count">{{ $bookingTabCounts['reopened'] }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'resolved' ? 'active' : '' }}"
                                   data-status="resolved"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'resolved'])) }}">
                                    {{ translate('Resolved') }}
                                    <span class="count">{{ $bookingTabCounts['resolved'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'disputed_cancelled' ? 'active' : '' }}"
                                   data-status="disputed_cancelled"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'disputed_cancelled'])) }}">
                                    {{ translate('Disputed_and_Cancelled') }}
                                    <span class="count">{{ $bookingTabCounts['disputed_cancelled'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'disputed_completed' ? 'active' : '' }}"
                                   data-status="disputed_completed"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'disputed_completed'])) }}">
                                    {{ translate('Disputed_and_Completed') }}
                                    <span class="count">{{ $bookingTabCounts['disputed_completed'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'on_hold' ? 'active' : '' }}"
                                   data-status="on_hold"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'on_hold'])) }}">
                                    {{ translate('On_hold') }}
                                    <span class="count">{{ $bookingTabCounts['on_hold'] }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'hold_after_visit' ? 'active' : '' }}"
                                   data-status="hold_after_visit"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'hold_after_visit'])) }}">
                                    {{ translate('Hold_after_visit') }}
                                    <span class="count">{{ $bookingTabCounts['hold_after_visit'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'completed_no_or_little' ? 'active' : '' }}"
                                   data-status="completed_no_or_little"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'completed_no_or_little'])) }}">
                                    {{ translate('Booking_tag_complete_no_service') }}
                                    <span class="count">{{ $bookingTabCounts['completed_no_or_little'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'cancelled_after_visit' ? 'active' : '' }}"
                                   data-status="cancelled_after_visit"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'cancelled_after_visit'])) }}">
                                    {{ translate('Booking_tag_cancel_after_visit') }}
                                    <span class="count">{{ $bookingTabCounts['cancelled_after_visit'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ in_array($bookingListTabStatus, ['loss_making_pending', 'loss_making'], true) ? 'active' : '' }}"
                                   data-status="loss_making_pending"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'loss_making_pending'])) }}">
                                    {{ translate('Bfs_list_badge_loss_making') }}
                                    <span class="count">{{ $bookingTabCounts['loss_making_pending'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'loss_recovered' ? 'active' : '' }}"
                                   data-status="loss_recovered"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'loss_recovered'])) }}">
                                    {{ translate('Bfs_list_badge_loss_recovered') }}
                                    <span class="count">{{ $bookingTabCounts['loss_recovered'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li class="booking-status-tab-extra">
                                <a class="booking-list-tab {{ $bookingListTabStatus === 'loss_settled' ? 'active' : '' }}"
                                   data-status="loss_settled"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'loss_settled'])) }}">
                                    {{ translate('Settled') }}
                                    <span class="count">{{ $bookingTabCounts['loss_settled'] ?? 0 }}</span>
                                </a>
                            </li>
                            <li>
                                <button type="button"
                                        class="booking-list-tab booking-status-tabs-toggle"
                                        aria-expanded="{{ $bookingStatusExtraTabActive ? 'true' : 'false' }}"
                                        aria-controls="booking-status-tabs-extra">
                                    <span class="toggle-state-more {{ $bookingStatusExtraTabActive ? 'd-none' : '' }}">{{ translate('View more') }} ▼</span>
                                    <span class="toggle-state-less {{ $bookingStatusExtraTabActive ? '' : 'd-none' }}">{{ translate('Hide more') }} ▲</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">

                                <form
                                    action="{{ $bookingListFilterAction }}"
                                    id="booking-list-search-form"
                                    class="search-form search-form_style-two booking-list-search-form" method="POST">
                                    @csrf
                                    @foreach (['start_date', 'end_date', 'schedule_start_date', 'schedule_end_date'] as $dateParam)
                                        @if(!empty($queryParams[$dateParam]))
                                            <input type="hidden" name="{{ $dateParam }}" value="{{ $queryParams[$dateParam] }}">
                                        @endif
                                    @endforeach
                                    @foreach (['category_ids', 'sub_category_ids', 'zone_ids', 'assignee_ids'] as $arrayParam)
                                        @foreach ($queryParams[$arrayParam] ?? [] as $selectedId)
                                            <input type="hidden" name="{{ $arrayParam }}[]" value="{{ $selectedId }}">
                                        @endforeach
                                    @endforeach
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search" id="booking-list-search-input"
                                            class="theme-input-style search-form__input"
                                            value="{{ $queryParams['search'] ?? '' }}" name="search"
                                            placeholder="{{ translate('Search_admin_booking_list') }}"
                                            autocomplete="off">
                                    </div>
                                </form>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <button type="button" class="btn text-capitalize filter-btn border px-3">
                                        <span class="material-icons">filter_list</span> {{ translate('Filter') }}
                                        <span class="count">{{ $filterCounter ?? 0 }}</span>
                                    </button>
                                </div>
                            </div>

                            @php $bookingListReasonTab = $queryParams['booking_status'] ?? ''; @endphp
                            <div class="booking-compact-list">
                                @forelse ($bookings as $key => $booking)
                                    @include('bookingmodule::admin.booking.partials._booking-list-compact-card', [
                                        'booking' => $booking,
                                        'index' => $key + $bookings->firstItem(),
                                        'isCancelledByProviderList' => $isCancelledByProviderList,
                                        'isCancelledByCustomerList' => $isCancelledByCustomerList,
                                        'bookingListReasonTab' => $bookingListReasonTab,
                                        'queryParams' => $queryParams,
                                        'followupListMeta' => $followupListMeta ?? [],
                                    ])
                                @empty
                                    <div class="text-center py-5 text-muted">
                                        @if($isCancelledByProviderList)
                                            {{ translate('Cancelled_by_provider_list_empty') }}
                                        @elseif($isCancelledByCustomerList)
                                            {{ translate('Cancelled_by_customer_list_empty') }}
                                        @else
                                            {{ translate('no data available') }}
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                            <div class="d-flex justify-content-end">
                                {!! $bookings->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    @if(request('booking_status') === 'reopened')
        @include('bookingmodule::admin.booking.partials._reopen-resolve-modal', [
            'modalId' => 'reopenResolveModalGlobal',
            'formId' => 'reopenResolveFormGlobal',
            'formAction' => '#',
        ])
    @endif
@endsection

@push('css_or_js')
    <style>
        .booking-status-tabs-toggle {
            cursor: pointer;
            white-space: nowrap;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $('#category_selector__select').on('change', function() {
                var selectedValues = $(this).val();
                if (selectedValues !== null && selectedValues.includes('all')) {
                    $(this).find('option').not(':disabled').prop('selected', 'selected');
                    $(this).find('option[value="all"]').prop('selected', false);
                }
            });

            $('#sub_category_selector__select').on('change', function() {
                var selectedValues = $(this).val();
                if (selectedValues !== null && selectedValues.includes('all')) {
                    $(this).find('option').not(':disabled').prop('selected', 'selected');
                    $(this).find('option[value="all"]').prop('selected', false);
                }
            });

            $('#zone_selector__select').on('change', function() {
                var selectedValues = $(this).val();
                if (selectedValues !== null && selectedValues.includes('all')) {
                    $(this).find('option').not(':disabled').prop('selected', 'selected');
                    $(this).find('option[value="all"]').prop('selected', false);
                }
            });

            $('#assignee_selector__select').on('change', function() {
                var selectedValues = $(this).val();
                if (selectedValues !== null && selectedValues.includes('all')) {
                    $(this).find('option').not(':disabled').prop('selected', 'selected');
                    $(this).find('option[value="all"]').prop('selected', false);
                }
            });

            $('.category-select').select2({
                placeholder: "{{ translate('Select Category') }}"
            });
            $('.subcategory-select').select2({
                placeholder: "{{ translate('Select Subcategory') }}"
            });
            $('.zone-select').select2({
                placeholder: "{{ translate('Select Zone') }}"
            });
            $('.assignee-select').select2({
                placeholder: "{{ translate('Select_Assignee') }}"
            });

        })(jQuery);
    </script>

    @if(request('booking_status') === 'reopened')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('reopenResolveModalGlobal');
            if (!modalEl) return;
            var hasOldRemarks = @json(strlen((string) (old('reopen_resolve_remarks') ?? '')) > 0);
            modalEl.addEventListener('show.bs.modal', function (e) {
                var btn = e.relatedTarget;
                var url = btn && btn.getAttribute('data-resolve-action');
                var form = modalEl.querySelector('#reopenResolveFormGlobal');
                if (form && url) {
                    form.setAttribute('action', url);
                }
                var ta = modalEl.querySelector('textarea[name="reopen_resolve_remarks"]');
                if (ta && !hasOldRemarks) {
                    ta.value = '';
                }
            });
        });
    </script>
    @endif

    <script>
        (function () {
            'use strict';

            var storageKey = 'adminBookingListExtraStatusTabsExpanded';

            function getBookingStatusTabsRoot(root) {
                if (root && root.querySelector) {
                    return root.querySelector('.booking-status-tabs-wrap') ? root : document;
                }
                return document;
            }

            function setExtraTabsExpanded(toggleBtn, tabsWrap, expanded) {
                if (tabsWrap) {
                    tabsWrap.classList.toggle('is-expanded', expanded);
                }

                var stateMore = toggleBtn.querySelector('.toggle-state-more');
                var stateLess = toggleBtn.querySelector('.toggle-state-less');
                if (stateMore) {
                    stateMore.classList.toggle('d-none', expanded);
                }
                if (stateLess) {
                    stateLess.classList.toggle('d-none', !expanded);
                }

                toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                try {
                    localStorage.setItem(storageKey, expanded ? '1' : '0');
                } catch (e) {}
            }

            function initBookingStatusTabsToggle(root) {
                root = getBookingStatusTabsRoot(root);
                var toggleBtn = root.querySelector('.booking-status-tabs-toggle');
                var tabsWrap = root.querySelector('.booking-status-tabs-wrap');
                if (!toggleBtn || !tabsWrap || toggleBtn.dataset.tabsToggleInit === '1') {
                    return;
                }

                toggleBtn.dataset.tabsToggleInit = '1';

                var initiallyExpanded = tabsWrap.classList.contains('is-expanded');
                if (!initiallyExpanded) {
                    try {
                        initiallyExpanded = localStorage.getItem(storageKey) === '1';
                    } catch (e) {}
                }

                setExtraTabsExpanded(toggleBtn, tabsWrap, initiallyExpanded);
            }

            function handleBookingStatusTabsToggleClick(event) {
                var toggleBtn = event.target.closest('.booking-status-tabs-toggle');
                if (!toggleBtn) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                var tabsWrap = toggleBtn.closest('.booking-status-tabs-wrap');
                if (!tabsWrap) {
                    return;
                }

                var isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                setExtraTabsExpanded(toggleBtn, tabsWrap, !isExpanded);
            }

            if (!window.__bookingStatusTabsToggleBound) {
                window.__bookingStatusTabsToggleBound = true;
                document.addEventListener('click', handleBookingStatusTabsToggleClick);
                document.addEventListener('admin:page-loaded', function (event) {
                    initBookingStatusTabsToggle(event.detail && event.detail.root ? event.detail.root : document);
                });
            }

            initBookingStatusTabsToggle(document);
        })();
    </script>

    <script>
        $(document).ready(function() {
            // $('#reset-btn').on('click', function() {
            //     $('#filter-form')[0].reset();
            //     $('.subcategory-select').val([]).trigger('change');
            //     $('.category-select').val([]).trigger('change');
            //     $('.zone-select').val([]).trigger('change');
            // });

            $('#reset-btn').on('click', function() {
                @if($isCancelledByProviderList)
                window.location.href = '{{ route('admin.booking.list.cancelled_by_provider', ['service_type' => 'all']) }}';
                @else
                let bookingStatus = '{{ $queryParams['booking_status'] ?? 'all' }}';
                window.location.href = `{{ route('admin.booking.list') }}?booking_status=${bookingStatus}&service_type=all`;
                @endif
            });
        });
    </script>

    <script>
        (function () {
            'use strict';

            function isBookingCardInteractiveTarget(target) {
                return !!target.closest('a, button, input, select, textarea, label, .dropdown-menu, [data-bs-toggle]');
            }

            function handleBookingCompactCardClick(event) {
                var card = event.target.closest('.booking-compact-card.bc-card-navigable[data-href]');
                if (!card || isBookingCardInteractiveTarget(event.target)) {
                    return;
                }

                var href = card.getAttribute('data-href');
                if (href) {
                    window.location.href = href;
                }
            }

            if (!window.__bookingCompactCardNavBound) {
                window.__bookingCompactCardNavBound = true;
                document.addEventListener('click', handleBookingCompactCardClick);
            }
        })();
    </script>

    <script>
        (function () {
            var form = document.getElementById('booking-list-search-form');
            var input = document.getElementById('booking-list-search-input');
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
                debounceTimer = setTimeout(submitSearch, 400);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    submitSearch();
                }
            });

            if (input.value) {
                input.focus();
                var len = input.value.length;
                try {
                    input.setSelectionRange(len, len);
                } catch (err) {}
            }
        })();
    </script>
@endpush
