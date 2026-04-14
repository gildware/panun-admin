@extends('adminmodule::layouts.master')

@section('title', translate('Booking_List'))

@section('content')
    <div class="filter-aside">
        <div class="filter-aside__header d-flex justify-content-between align-items-center">
            <h3 class="filter-aside__title">{{ translate('Filter_your_Booking') }}</h3>
            <button type="button" class="btn-close p-2 btn-close-white"></button>
        </div>
        <form action="{{ route('admin.booking.list', ['booking_status' => $queryParams['booking_status'], 'service_type' => $queryParams['service_type'], 'booking_type' => $queryParams['booking_type'], 'provider_assigned' => $queryParams['provider_assigned']]) }}" method="POST"
            enctype="multipart/form-data" id="filter-form">
            @csrf
            <div class="filter-aside__body d-flex flex-column">
                <div class="filter-aside__date_range">
                    <h4 class="fw-normal mb-4">{{ translate('Select_Date_Range') }}</h4>
                    <div class="mb-30">
                        <div class="form-floating">
                            <input type="date" class="form-control" placeholder="{{ translate('start_date') }}"
                                name="start_date" value="{{ $queryParams['start_date'] }}">
                            <label for="floatingInput">{{ translate('Start_Date') }}</label>
                        </div>
                    </div>
                    <div class="fw-normal mb-30">
                        <div class="form-floating">
                            <input type="date" class="form-control" placeholder="{{ translate('end_date') }}"
                                name="end_date" value="{{ $queryParams['end_date'] }}">
                            <label for="floatingInput">{{ translate('End_Date') }}</label>
                        </div>
                    </div>
                </div>

                <div class="filter-aside__category_select">
                    <h4 class="fw-normal mb-2">{{ translate('Select_Categories') }}</h4>
                    <div class="mb-30">
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
                <div class="filter-aside__category_select">
                    <h4 class="fw-normal mb-2">{{ translate('Select_Sub_Categories') }}</h4>
                    <div class="mb-30">
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
                <div class="filter-aside__zone_select">
                    <h4 class="mb-2 fw-normal">{{ translate('Select_Zones') }}</h4>
                    <div class="mb-30">
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
                <div class="filter-aside__assignee_select">
                    <h4 class="mb-2 fw-normal">{{ translate('Select_Assignee') }}</h4>
                    <div class="mb-30">
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
            <div class="filter-aside__bottom_btns p-20">
                <div class="d-flex justify-content-center gap-20">
                    <button class="btn btn--secondary text-capitalize" id="reset-btn"
                        type="reset">{{ translate('Clear_all_Filter') }}</button>
                    <button class="btn btn--primary text-capitalize" type="submit">{{ translate('Filter') }}</button>
                </div>
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
                        @elseif(($queryParams['booking_status'] ?? '') === 'all')
                            <h2 class="page-title">{{ translate('Booking_Requests') }}</h2>
                        @elseif($queryParams['booking_status'] ?? null)
                            <h2 class="page-title">{{ ucwords(str_replace('_', ' ', $queryParams['booking_status'])) }}</h2>
                        @else
                            <h2 class="page-title">{{ translate('Booking_Requests') }}</h2>
                        @endif

                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{ translate('Total_Request') }}:</span>
                            <span class="title-color">{{ $bookings->total() }}</span>
                        </div>
                    </div>
                    @php
                        $bookingListTabStatus = $queryParams['booking_status'] ?? 'all';
                        if ($bookingListTabStatus === '') {
                            $bookingListTabStatus = 'all';
                        }
                    @endphp
                    <div class="mt-30 mb-30">
                        <ul class="nav nav--tabs nav--tabs__style2 nav--tabs__booking-tally flex-wrap gap-2">
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'all' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'all'])) }}">
                                    {{ translate('All Booking') }}
                                    <span class="count">{{ $bookingTabCounts['all'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'pending' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'pending'])) }}">
                                    {{ translate('Pending_Booking') }}
                                    <span class="count">{{ $bookingTabCounts['pending'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'accepted' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'accepted'])) }}">
                                    {{ translate('Accepted') }}
                                    <span class="count">{{ $bookingTabCounts['accepted'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'ongoing' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'ongoing'])) }}">
                                    {{ translate('Ongoing') }}
                                    <span class="count">{{ $bookingTabCounts['ongoing'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'completed' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'completed'])) }}">
                                    {{ translate('Completed') }}
                                    <span class="count">{{ $bookingTabCounts['completed'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'reopened' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'reopened'])) }}">
                                    {{ translate('Reopened') }}
                                    <span class="count">{{ $bookingTabCounts['reopened'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'on_hold' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'on_hold'])) }}">
                                    {{ translate('On_hold') }}
                                    <span class="count">{{ $bookingTabCounts['on_hold'] }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $bookingListTabStatus === 'canceled' ? 'active' : '' }}"
                                   href="{{ route('admin.booking.list', array_merge($queryParams, ['booking_status' => 'canceled'])) }}">
                                    {{ translate('Canceled') }}
                                    <span class="count">{{ $bookingTabCounts['canceled'] }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between">

                                <form
                                    action="{{ url()->current() }}?booking_status={{ $queryParams['booking_status'] }}&service_type={{ $queryParams['service_type'] }}"
                                    class="search-form search-form_style-two" method="POST">
                                    @csrf
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon">
                                            <span class="material-icons">search</span>
                                        </span>
                                        <input type="search" class="theme-input-style search-form__input"
                                            value="{{ $queryParams['search'] ?? '' }}" name="search"
                                            placeholder="{{ translate('search_here') }}">
                                    </div>
                                    <button type="submit"
                                        class="btn btn--primary">{{ translate('search') }}</button>
                                </form>
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    @if(request()->booking_status != 'ongoing' && request()->booking_status != 'on_hold' && request()->booking_status != 'accepted' && request()->booking_status != 'completed')
                                        <div class="">
                                            <select class="custom-select form-select min-w-120" name="provider_assigned" id="providerAssigned">
                                                <option value="all" {{ request('provider_assigned') == 'all' ? 'selected' : '' }}>{{ translate('All Booking') }}</option>
                                                <option value="assigned" {{ request('provider_assigned') == 'assigned' ? 'selected' : '' }}>{{ translate('Assigned') }}</option>
                                                <option value="unassigned" {{ request('provider_assigned') == 'unassigned' ? 'selected' : '' }}>{{ translate('Unassigned') }}</option>
                                            </select>
                                        </div>
                                    @endif
                                    @can('booking_export')
                                        <div class="dropdown">
                                            <button type="button"
                                                class="btn btn--secondary text-capitalize dropdown-toggle h-45"
                                                data-bs-toggle="dropdown">
                                                <span class="material-icons">file_download</span>
                                                {{ translate('download') }}
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                                                <li><a class="dropdown-item"
                                                        href="{{ route('admin.booking.download', $queryParams) }}">{{ translate('excel') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endcan
                                    <button type="button" class="btn text-capitalize filter-btn border px-3">
                                        <span class="material-icons">filter_list</span> {{ translate('Filter') }}
                                        <span class="count">{{ $filterCounter ?? 0 }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="example" class="table align-middle tr-hover">
                                    <thead class="text-nowrap">
                                        <tr>
                                            <th>{{ translate('SL') }}</th>
                                            <th>{{ translate('Booking_ID') }}</th>
                                            @if(request('booking_status') === 'reopened')
                                                <th>{{ translate('Reopened_from') }}</th>
                                            @else
                                                <th>{{ translate('Lead_ID') }}</th>
                                            @endif
                                            <th>{{ translate('Assignee') }}</th>
                                            <th>{{ translate('Fup_Customer') }}</th>
                                            <th>{{ translate('Fup_Provider') }}</th>
                                            <th>{{ translate('Source') }}</th>
                                            <th>{{ translate('Booking_Date') }}</th>
                                            <th>{{ translate('Where_Service_will_be_Provided') }}</th>
                                            <th>{{ translate('Schedule_Date') }}</th>
                                            <th>{{ translate('Customer_Info') }}</th>
                                            <th>{{ translate('Provider_Info') }}</th>
                                            <th>{{ translate('Total_Amount') }}</th>
                                            <th>{{ translate('Payment_Status') }}</th>
                                            @php $bookingListReasonTab = $queryParams['booking_status'] ?? ''; @endphp
                                            @if($bookingListReasonTab === 'canceled')
                                                <th>{{ translate('Booking_list_reason_remarks_column') }}</th>
                                            @elseif($bookingListReasonTab === 'on_hold')
                                                <th>{{ translate('Booking_list_reason_remarks_column') }}</th>
                                            @elseif($bookingListReasonTab === 'reopened')
                                                <th>{{ translate('Booking_list_reason_remarks_column') }}</th>
                                            @endif
                                            <th>{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($bookings as $key => $booking)
                                            <tr>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >{{ $key + $bookings?->firstItem() }}</td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >
                                                    @if($booking->is_repeated)
                                                        <a href="{{ route('admin.booking.repeat_details', [$booking->id, 'web_page' => 'details']) }}">
                                                            {{ $booking->readable_id }}
                                                        </a>
                                                        <img width="34" height="34"
                                                             src="{{ asset('assets/admin-module/img/icons/repeat.svg') }}"
                                                             class="rounded-circle repeat-icon"
                                                             alt="{{ translate('repeat') }}">
                                                    @else
                                                    <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}">
                                                        {{ $booking->readable_id }}</a>
                                                        @if($booking->isOpenReopenTicket())
                                                            <span class="badge bg-warning text-dark ms-1">{{ translate('Reopened') }}</span>
                                                        @elseif($booking->isReopenedTagged() && (empty($booking->reopen_disputed_snapshot) || !is_array($booking->reopen_disputed_snapshot)))
                                                            <span class="badge bg-success ms-1">{{ translate('Resolved') }}</span>
                                                        @endif
                                                    @endif
                                                    @include('bookingmodule::admin.booking.partials._booking-settlement-list-badge', ['booking' => $booking])
                                                </td>
                                                @if(request('booking_status') === 'reopened')
                                                    <td>
                                                        @if(!empty($booking->originated_from_booking_id))
                                                            @php
                                                                $reopenParent = $booking->originatedFromBooking;
                                                            @endphp
                                                            @if($reopenParent)
                                                                <a href="{{ route('admin.booking.details', [$reopenParent->id, 'web_page' => 'details']) }}">
                                                                    #{{ $reopenParent->readable_id ?? $booking->originated_from_booking_id }}
                                                                </a>
                                                            @else
                                                                <span class="text-muted">{{ $booking->originated_from_booking_id }}</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">{{ translate('Reopened_from_self') }}</span>
                                                        @endif
                                                    </td>
                                                @else
                                                    <td>
                                                        @if(!empty($booking->lead_id))
                                                            <a href="{{ route('admin.lead.show', $booking->lead_id) }}">
                                                                #{{ $booking->lead_id }}
                                                            </a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                @endif
                                                <td>
                                                    @if($booking->assignee)
                                                        <div>{{ $booking->assignee->first_name }} {{ $booking->assignee->last_name }}</div>
                                                        <div class="text-muted small">
                                                            {{ $booking->assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }}
                                                            @if($booking->assignee->email)
                                                                — {{ $booking->assignee->email }}
                                                            @elseif($booking->assignee->phone)
                                                                — {{ $booking->assignee->phone }}
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">{{ translate('Unassigned') }}</span>
                                                    @endif
                                                </td>
                                                @php
                                                    $scheduled = ($booking->followups ?? collect())->where('status', 'scheduled')->sortBy('date');
                                                    $nextFuCustomer = $scheduled->where('for', 'customer')->first();
                                                    $nextFuProvider = $scheduled->where('for', 'provider')->first();
                                                @endphp
                                                <td>{{ $nextFuCustomer && $nextFuCustomer->date ? \Carbon\Carbon::parse($nextFuCustomer->date)->format('d-M-Y') : '—' }}</td>
                                                <td>{{ $nextFuProvider && $nextFuProvider->date ? \Carbon\Carbon::parse($nextFuProvider->date)->format('d-M-Y') : '—' }}</td>
                                                <td>
                                                    @switch(strtolower((string)($booking->booking_source ?? 'app')))
                                                        @case('app'){{ translate('App') }}@break
                                                        @case('call'){{ translate('Call') }}@break
                                                        @case('whatsapp'){{ translate('Whatsapp') }}@break
                                                        @case('social_media'){{ translate('Social_Media') }}@break
                                                        @default{{ ucfirst(strtolower((string)($booking->booking_source ?? 'app'))) }}
                                                    @endswitch
                                                </td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >
                                                    <div>{{ date('d-M-Y', strtotime($booking->created_at)) }}</div>
                                                    <div>{{ date('h:ia', strtotime($booking->created_at)) }}</div>
                                                </td>
                                                <td>
                                                    @if($booking->service_location == 'provider')
                                                        {{ translate('Provider Location') }}
                                                    @else
                                                        {{ translate('Customer Location') }}
                                                    @endif
                                                </td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >
                                                    @if($booking->is_repeated)
                                                        @if(empty($booking->nextService))
                                                            <div>{{ date('d-M-Y', strtotime($booking?->lastRepeat?->service_schedule)) }}</div>
                                                            <div>{{ date('h:ia', strtotime($booking?->lastRepeat?->service_schedule)) }}</div>
                                                        @else
                                                            <span>{{translate('Next upcoming')}}</span>
                                                            <div>{{ date('d-M-Y', strtotime($booking?->nextService?->service_schedule)) }}</div>
                                                            <div>{{ date('h:ia', strtotime($booking?->nextService?->service_schedule)) }}</div>
                                                        @endif
                                                    @else
                                                        <div>{{ date('d-M-Y', strtotime($booking->service_schedule)) }}</div>
                                                        <div>{{ date('h:ia', strtotime($booking->service_schedule)) }}</div>
                                                    @endif
                                                </td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >
                                                    <div>
                                                        @if ($booking->customer)
                                                            <a
                                                                href="{{ route('admin.customer.detail', [$booking?->customer?->id, 'web_page' => 'overview']) }}">
                                                                @php
                                                                    $fullName =
                                                                        ($booking?->customer?->first_name ?? '') .
                                                                        ' ' .
                                                                        ($booking?->customer?->last_name ?? '');
                                                                    $limitedFullName = Str::limit($fullName, 30);
                                                                @endphp

                                                                {{ $limitedFullName }}
                                                            </a>
                                                        @else
                                                            <span>
                                                                {{ Str::limit($booking?->service_address?->contact_person_name, 30) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    {{ $booking->customer ? $booking?->customer?->phone : $booking?->service_address?->contact_person_number }}
                                                </td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >
                                                    @if(isset($booking->provider))
                                                        <div>
                                                            <a href="{{route('admin.provider.details',[$booking->provider_id, 'web_page'=>'overview'])}}">{{ $booking->provider->company_name }}</a>
                                                        </div>
                                                        <span class="text-light-gray">{{ $booking->provider->company_phone }}</span>
                                                    @else
                                                        <span class="badge badge badge-danger radius-50">
                                                            {{ translate('unassigned') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip custom"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >{{ with_currency_symbol(get_booking_total_amount($booking)) }}</td>
                                                <td
                                                    @if($booking->is_repeated)
                                                        data-bs-custom-class="review-tooltip"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-html="true"
                                                        data-bs-placement="bottom"
                                                        data-bs-title="{{ translate('This is a repeat booking.') }} <br> {{ translate('Customer has requested total ')}} {{count($booking->repeat)}}<br> {{ translate('bookings under this Bookings.') }} <br> {{ translate('Check the details') }}"
                                                    @endif
                                                >
                                                    <span
                                                        class="badge badge badge-{{ $booking->is_paid ? 'success' : 'danger' }} radius-50">
                                                        <span class="dot"></span>
                                                        {{ $booking->is_paid ? translate('paid') : translate('unpaid') }}
                                                    </span>
                                                </td>
                                                @if($bookingListReasonTab === 'canceled')
                                                    @php $__lc = $booking->latestParentCancellationStatusHistory; @endphp
                                                    <td class="small text-break">
                                                        @if($__lc && ($__lc->cancellationReason || filled($__lc->status_change_remarks)))
                                                            @if($__lc->cancellationReason)
                                                                <div class="fw-semibold">{{ $__lc->cancellationReason->name }}</div>
                                                            @endif
                                                            @if(filled($__lc->status_change_remarks))
                                                                <div class="text-muted mt-1">{{ Str::limit(strip_tags($__lc->status_change_remarks), 200) }}</div>
                                                            @endif
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                @elseif($bookingListReasonTab === 'on_hold')
                                                    @php $__lh = $booking->latestParentHoldStatusHistory; @endphp
                                                    <td class="small text-break">
                                                        @if($__lh && ($__lh->holdReopenReason || filled($__lh->status_change_remarks)))
                                                            @if($__lh->holdReopenReason)
                                                                <div class="fw-semibold">{{ $__lh->holdReopenReason->name }}</div>
                                                            @endif
                                                            @if(filled($__lh->status_change_remarks))
                                                                <div class="text-muted mt-1">{{ Str::limit(strip_tags($__lh->status_change_remarks), 200) }}</div>
                                                            @endif
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                @elseif($bookingListReasonTab === 'reopened')
                                                    @php $__rev = $booking->reopenFromCompletedDisplayEvent(); @endphp
                                                    <td class="small text-break">
                                                        @if($__rev && ($__rev->holdReopenReason || filled($__rev->complaint_notes)))
                                                            @if($__rev->holdReopenReason)
                                                                <div class="fw-semibold">{{ $__rev->holdReopenReason->name }}</div>
                                                            @endif
                                                            @if(filled($__rev->complaint_notes))
                                                                <div class="text-muted mt-1">{{ Str::limit(strip_tags($__rev->complaint_notes), 200) }}</div>
                                                            @endif
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                @endif
                                                <td>
                                                    <div class="table-actions d-flex gap-2">
                                                        @if($booking->is_repeated)
                                                            <div class="dropdown">
                                                                <button type="button"
                                                                        class="action-btn btn--light-primary fw-medium text-capitalize fz-14"
                                                                        style="--size: 30px" data-bs-toggle="dropdown">
                                                                    <span class="material-icons">visibility</span>
                                                                </button>
                                                                <ul
                                                                    class="dropdown-menu border-none dropdown-menu-lg dropdown-menu-right">
                                                                    <li class="mx-2"><a
                                                                            class="dropdown-item d-flex align-items-center gap-1"
                                                                            href="{{ route('admin.booking.repeat_details', [$booking->id, 'web_page' => 'details']) }}">
                                                                                <span
                                                                                    class="material-icons">visibility</span>
                                                                            {{ translate('Full_Booking_Details') }}
                                                                        </a>
                                                                    </li>
                                                                    @if($booking->nextServiceId && $booking['booking_status'] != 'pending')
                                                                    <li class="mx-2"><a
                                                                            class="dropdown-item d-flex align-items-center gap-1"
                                                                            href="{{ route('admin.booking.repeat_single_details', [$booking->nextServiceId, 'web_page' => 'details'])}}">
                                                                                <span
                                                                                    class="material-icons">visibility</span>
                                                                            {{ translate('Ongoing_Booking_Details') }}
                                                                        </a>
                                                                    </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                            <div class="dropdown">
                                                                <button type="button"
                                                                        class="action-btn btn--light-primary fw-medium text-capitalize fz-14"
                                                                        style="--size: 30px" data-bs-toggle="dropdown">
                                                                    <span class="material-icons">download</span>
                                                                </button>
                                                                <ul
                                                                    class="dropdown-menu border-none dropdown-menu-lg dropdown-menu-right">
                                                                    <li class="mx-2"><a
                                                                            class="dropdown-item d-flex align-items-center gap-1"
                                                                            target="_blank"
                                                                            href="{{ route('admin.booking.full_repeat_invoice', [$booking->id]) }}">
                                                                                <span
                                                                                    class="material-icons">download</span>
                                                                            {{ translate('Full invoice') }}
                                                                        </a>
                                                                    </li>
                                                                    @if($booking->nextServiceId && $booking['booking_status'] != 'pending')
                                                                        <li class="mx-2">
                                                                            <a
                                                                                class="dropdown-item d-flex align-items-center gap-1"
                                                                                target="_blank"
                                                                                href="{{ route('admin.booking.single_invoice', [$booking->nextServiceId]) }}">
                                                                                    <span
                                                                                        class="material-icons">download</span>
                                                                                {{ translate('Ongoing Booking invoice') }}
                                                                            </a>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                        @else
                                                            <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}"
                                                                type="button"
                                                                class="action-btn tooltip-hide btn--light-primary fw-medium text-capitalize fz-14"
                                                                style="--size: 30px">
                                                                <span class="material-icons">visibility</span>
                                                            </a>
                                                            <a href="{{ route('admin.booking.invoice', [$booking->id]) }}"
                                                                type="button" target="_blank"
                                                                class="action-btn tooltip-hide btn--light-primary fw-medium text-capitalize fz-14"
                                                                style="--size: 30px">
                                                                <span class="material-icons">download</span>
                                                            </a>
                                                            @can('booking_can_manage_status')
                                                                @if(request('booking_status') === 'reopened' && $booking->canMarkReopenResolved())
                                                                    <button type="button" class="action-btn btn-success fw-medium text-capitalize fz-14" style="--size: 30px"
                                                                        title="{{ translate('Mark_reopen_resolved') }}"
                                                                        data-bs-toggle="modal" data-bs-target="#reopenResolveModalGlobal"
                                                                        data-resolve-action="{{ route('admin.booking.reopen-resolve', $booking->id) }}">
                                                                        <span class="material-icons">check_circle</span>
                                                                    </button>
                                                                @endif
                                                            @endcan
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="text-center">
                                                <td colspan="8">{{translate('no data available')}}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
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

            $('#providerAssigned').change(function() {
                var bookingStatus = '{{$queryParams['booking_status']}}';
                var serviceType = 'all';

                @if(isset($queryParams['search']))
                var search = '{{ $queryParams['search'] }}';
                @endif

                var providerAssigned = $(this).val();

                var baseUrl = '{{ route('admin.booking.list') }}';

                var params = new URLSearchParams({
                    provider_assigned: providerAssigned,
                    booking_status: bookingStatus,
                    service_type: serviceType,
                    @if(isset($queryParams['search']))
                    search: search,
                    @endif
                });

                var urlWithParams = baseUrl + '?' + params.toString();
                window.location.href = urlWithParams;
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
        $(document).ready(function() {
            // $('#reset-btn').on('click', function() {
            //     $('#filter-form')[0].reset();
            //     $('.subcategory-select').val([]).trigger('change');
            //     $('.category-select').val([]).trigger('change');
            //     $('.zone-select').val([]).trigger('change');
            // });

            $('#reset-btn').on('click', function() {
                let bookingStatus = '{{ $queryParams['booking_status'] ?? 'all' }}';

                window.location.href = `{{ route('admin.booking.list') }}?booking_status=${bookingStatus}&service_type=all`;
            });
        });
    </script>
@endpush
