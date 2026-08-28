@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/booking-detail-redesign.css') }}">
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    @php
        $bookingHasTax = (float)($booking->total_tax_amount ?? 0) > 0;
        $maxBookingAmount = business_config('max_booking_amount', 'booking_setup')->live_values ?? 0;
        $max_booking_amount = $maxBookingAmount;
        $hasServiceDiscount = ($booking->total_discount_amount ?? 0) > 0;
        $hasCouponDiscount = ($booking->total_coupon_discount_amount ?? 0) > 0;
        $hasCampaignDiscount = ($booking->total_campaign_discount_amount ?? 0) > 0;
        $hasReferralDiscount = ($booking->total_referral_discount_amount ?? 0) > 0;
        $hasNegativeAdditionalCharge = $booking->payment_method != 'cash_after_service' && ($booking->additional_charge ?? 0) < 0;
        $serviceAtProviderPlaceRaw = business_config('service_at_provider_place', 'provider_config');
        $serviceAtProviderPlace = (int) ($serviceAtProviderPlaceRaw->live_values ?? 0);
        $customer_name = booking_display_customer_name($booking, $customerAddress);
        $customer_phone = booking_display_customer_phone($booking, $customerAddress);
        $customerName = $customer_name;
        $customerPhone = $customer_phone;
        $name = $customer_name;
        $phone = $customer_phone;
        $partyBooking = $booking->booking ?? $booking;
        $visitExtras = $visitExtras ?? collect();
        $visitExtrasTotal = $visitExtrasTotal ?? 0;
        $visitHasExtras = ! empty($visitHasExtras);
        $visitGrandTotal = $visitGrandTotal ?? $booking->total_booking_amount;
        $visitCanEditExtras = ! empty($visitCanEditExtras);
        $visitExtraStoreUrl = $visitExtraStoreUrl ?? '';
        $repeatChromeEntity = $booking;
        $repeatChromeBackUrl = route('admin.booking.repeat_details', [$booking->booking_id]);
        $repeatChromeParentUrl = $repeatChromeBackUrl;
        $repeatChromeParentCrumb = '#' . ($booking->booking->readable_id ?? '');
        $repeatChromeInvoiceUrl = route('admin.booking.single_invoice', [$booking->id]);
        $repeatChromeStatusClass = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($booking->booking_status ?? 'pending'))) ?: 'pending';
        $repeatChromeTitle = translate('Visit') . ' #' . $booking->readable_id;
        $repeatChromeSubtitle = translate('Repeat_Booking') . ' #' . ($booking->booking->readable_id ?? '');
        $repeatChromeSchedule = $booking->service_schedule ? date('d-M-Y h:ia', strtotime($booking->service_schedule)) : '';
        $repeatChromeCustomerName = $customer_name;
        $repeatChromeCustomerPhone = $customer_phone;
        $repeatChromeCrumb = '#' . $booking->readable_id;
        $repeatChromeBackLabel = translate('Repeat_Booking');
        extract(repeat_admin_detail_chrome_vars($booking, $customerAddress ?? null, false, $customer_name ?? null, $customer_phone ?? null));
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }} </h2>
            </div>
            <div class="row">
                <div class="col-12 booking-detail-v2 booking-detail-v2--{{ $repeatChromeStatusClass }}">
                    <div class="booking-detail-v2__wrap">
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-topbar')
                        @include('bookingmodule::admin.booking.partials._repeat-detail-compact-header')

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4 booking-detail-nav-wrap">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
                           href="{{ url()->current() }}?web_page=details">{{ translate('details') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'history' ? 'active' : '' }}"
                           href="{{ url()->current() }}?web_page=history">{{ translate('History') }}</a>
                    </li>
                </ul>
                @if (
                    $booking->is_verified == 2 &&
                        $booking->payment_method == 'cash_after_service' &&
                        $max_booking_amount <= $booking->total_booking_amount)
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span class="text-danger"># {{ translate('Note: ') }}</span>
                        <span>{{ $booking?->bookingDeniedNote?->value }}</span>
                    </div>
                @endif

                @if ($booking->is_paid == 0 && $booking->payment_method == 'offline_payment')
                    <div class="border border-danger-light bg-soft-danger rounded py-3 px-3 text-dark">
                        <span>
                            <span class="text-danger fw-semibold"> # {{ translate('Note: ') }} </span>
                            {{ translate('Please Check & Verify the payment information weather it is correct or not before confirm the booking. ') }}
                        </span>
                    </div>
                @endif

            </div>

            <div class="booking-overview-trio mb-3">
                <div class="booking-overview-trio__cell">
                    @include('bookingmodule::admin.booking.partials._booking-overview-party-customer', [
                        'booking' => $partyBooking,
                        'customerName' => $customerName ?? null,
                        'customerPhone' => $customerPhone ?? null,
                        'customerAddress' => $customerAddress ?? null,
                        'followupDetailMeta' => $followupDetailMeta ?? null,
                        'nextFollowupCustomer' => $nextFollowupCustomer ?? null,
                    ])
                </div>
                <div class="booking-overview-trio__cell">
                    @include('bookingmodule::admin.booking.partials._booking-overview-party-provider', [
                        'booking' => $partyBooking,
                        'followupDetailMeta' => $followupDetailMeta ?? null,
                        'nextFollowupProvider' => $nextFollowupProvider ?? null,
                        'bookingNotEditable' => $bookingNotEditable ?? false,
                    ])
                </div>
                <div class="booking-overview-trio__cell">
                    @include('bookingmodule::admin.booking.partials._repeat-payment-snapshot', [
                        'booking' => $booking,
                        'viewAllHref' => '',
                    ])
                </div>
            </div>

            <div class="row gy-3">
                <div class="col-lg-8">
                    <section class="summary-panel booking-summary-panel" id="booking-summary">
                        <div class="summary-panel__head">
                            <h2 class="summary-panel__title">
                                <span class="material-icons">receipt_long</span>
                                {{ translate('Booking_Summary') }}
                            </h2>
                            <div class="summary-panel__head-actions">
                                @if (booking_admin_can_correct_line_items($booking))
                                    @can('booking_edit')
                                        <button type="button" class="btn btn-demo-outline btn-sm flex-shrink-0" data-bs-toggle="modal"
                                                data-bs-target="#serviceUpdateModal--{{ $booking['id'] }}" data-toggle="tooltip"
                                                title="{{ translate('Correct_missed_or_wrong_items') }}">
                                            <span class="material-symbols-outlined">edit</span>{{ translate('Edit Services') }}
                                        </button>
                                    @endcan
                                @endif
                                @if($visitCanEditExtras)
                                    @can('booking_edit')
                                        <button type="button" class="btn btn-demo-outline btn-sm flex-shrink-0" data-bs-toggle="modal"
                                                data-bs-target="#addVisitExtraServiceModal">
                                            <span class="material-symbols-outlined">add</span>{{ translate('Add_Extra_Service') }}
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </div>
                        <div class="summary-meta">
                            <span><strong>{{ translate('Payment_Method') }}:</strong> {{ ucwords(str_replace(['_', '-'], ' ', $booking->payment_method)) }}</span>
                            <span><strong>{{ translate('Amount') }}:</strong> {{ with_currency_symbol($visitGrandTotal) }}</span>
                            <span><strong>{{ translate('Payment_Status') }}:</strong> {{ $booking->is_paid ? translate('Paid') : translate('Unpaid') }}</span>
                            <span><strong>{{ translate('Booking_Otp') }}:</strong> {{ $booking->booking_otp ?? '' }}</span>
                        </div>
                        <div class="summary-panel__body">
                            @if(in_array((string) ($booking->booking_status ?? ''), ['completed'], true) && $visitCanEditExtras)
                                <p class="fz-12 text-muted mb-2 px-3 pt-2">{{ translate('Completed_booking_line_item_correction_hint') }}</p>
                            @endif
                            <div class="summary-panel__split">
                            <div class="summary-panel__services">
                            <div class="summary-table-wrap table-responsive">
                                <table class="table text-nowrap align-middle mb-0">
                                    <thead>
                                    <tr>
                                        <th class="ps-lg-3">{{ translate('Service') }}</th>
                                        <th>{{ translate('Price') }}</th>
                                        <th>{{ translate('Qty') }}</th>
                                        <th>{{ translate('Discount') }}</th>
                                        @if($bookingHasTax)
                                        <th>{{ company_default_tax_label() }}</th>
                                        @endif
                                        <th class="text--end">{{ translate('Total') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $subTotal = 0;
                                    @endphp
                                    @foreach ($booking->detail as $detail)
                                        <tr>
                                            <td class="text-wrap ps-lg-3">
                                                @if (isset($detail->service))
                                                    <div class="d-flex flex-column">
                                                        <a href="{{ route('admin.service.detail', [$detail->service->id]) }}"
                                                           class="fw-bold">{{ Str::limit($detail->service->name, 30) }}</a>
                                                        <div class="text-capitalize">
                                                            {{ Str::limit($detail ? $detail->variant_key : '', 50) }}
                                                        </div>
                                                        @if ($detail->overall_coupon_discount_amount > 0)
                                                            <small
                                                                class="fz-10 text-capitalize">{{ translate('coupon_discount') }}
                                                                :
                                                                -{{ with_currency_symbol($detail->overall_coupon_discount_amount) }}</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span
                                                        class="badge badge-pill badge-danger">{{ translate('Service_unavailable') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ with_currency_symbol($detail->service_cost) }}</td>
                                            <td>
                                                <span>{{ $detail->quantity }}</span>
                                            </td>
                                            <td>
                                                @if ($detail?->discount_amount > 0)
                                                    {{ with_currency_symbol($detail->discount_amount) }}
                                                @elseif($detail?->campaign_discount_amount > 0)
                                                    {{ with_currency_symbol($detail->campaign_discount_amount) }}
                                                    <br><span
                                                        class="fz-12 text-capitalize">{{ translate('campaign') }}</span>
                                                @endif
                                            </td>
                                            @if($bookingHasTax)
                                            <td>{{ with_currency_symbol($detail->tax_amount) }}</td>
                                            @endif
                                            <td class="text--end">{{ with_currency_symbol($detail->total_cost) }}</td>
                                        </tr>
                                        @php
                                            $subTotal += $detail->service_cost * $detail->quantity;
                                        @endphp
                                    @endforeach
                                    @foreach ($visitExtras as $extra)
                                        <tr class="table-light">
                                            <td class="text-wrap ps-lg-3">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold">{{ $extra['title'] }}</span>
                                                    @if(!empty($extra['details']))
                                                        <small class="text-muted">{{ $extra['details'] }}</small>
                                                    @endif
                                                    <span class="badge badge-{{ !empty($extra['is_spare']) ? 'info' : 'primary' }} mt-1" style="width: fit-content;">
                                                        {{ $extra['type_label'] }}
                                                    </span>
                                                    @if($visitCanEditExtras)
                                                        @can('booking_edit')
                                                            <div class="d-flex flex-wrap gap-2 mt-1">
                                                                <button type="button"
                                                                        class="btn btn-sm btn-link p-0 js-edit-visit-extra-service"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#addVisitExtraServiceModal"
                                                                        data-update-url="{{ $extra['update_url'] }}"
                                                                        data-title="{{ e($extra['title']) }}"
                                                                        data-details="{{ e($extra['details'] ?? '') }}"
                                                                        data-type="{{ e($extra['type'] ?? ($extra['is_spare'] ? 'spare_part' : 'service')) }}"
                                                                        data-quantity="{{ (int) $extra['quantity'] }}"
                                                                        data-price="{{ $extra['price'] }}"
                                                                        data-discount="{{ $extra['discount'] }}">
                                                                    {{ translate('Edit') }}
                                                                </button>
                                                                <form method="post" action="{{ $extra['destroy_url'] }}" class="d-inline" onsubmit="return confirm('{{ translate('Remove_this_item') }}');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">{{ translate('Remove') }}</button>
                                                                </form>
                                                            </div>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ with_currency_symbol($extra['price']) }}</td>
                                            <td>{{ $extra['quantity'] }}</td>
                                            <td>{{ with_currency_symbol($extra['discount']) }}</td>
                                            @if($bookingHasTax)
                                            <td>—</td>
                                            @endif
                                            <td class="text--end">{{ with_currency_symbol($extra['total']) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            </div>
                            <div class="summary-panel__breakdown">
                            <div class="summary-breakdown-wrap">
                                        <table class="booking-summary-breakdown breakdown-table mb-0">
                                            <tbody>
                                            <tr>
                                                <td class="text-capitalize">
                                                    {{ translate('service_amount') }}
                                                    @if($bookingHasTax)
                                                        <small class="fz-12">{{ booking_tax_excluded_bracket_hint() }}</small>
                                                    @endif
                                                </td>
                                                <td class="text--end pe--4">{{ with_currency_symbol($subTotal) }}
                                                </td>
                                            </tr>
                                            @if($hasServiceDiscount)
                                            <tr>
                                                <td class="text-capitalize">{{ translate('service_discount') }}</td>
                                                <td class="text--end pe--4">
                                                    {{ with_currency_symbol($booking->total_discount_amount) }}</td>
                                            </tr>
                                            @endif
                                            @if($hasCouponDiscount)
                                            <tr>
                                                <td class="text-capitalize">{{ translate('coupon_discount') }}</td>
                                                <td class="text--end pe--4">
                                                    {{ with_currency_symbol($booking->total_coupon_discount_amount) }}
                                                </td>
                                            </tr>
                                            @endif
                                            @if($hasCampaignDiscount)
                                            <tr>
                                                <td class="text-capitalize">{{ translate('campaign_discount') }}</td>
                                                <td class="text--end pe--4">
                                                    {{ with_currency_symbol($booking->total_campaign_discount_amount) }}
                                                </td>
                                            </tr>
                                            @endif
                                            @if($hasReferralDiscount)
                                            <tr>
                                                <td class="text-capitalize">{{ translate('Referral Discount') }}</td>
                                                <td class="text--end pe--4">
                                                    {{ with_currency_symbol($booking->total_referral_discount_amount) }}
                                                </td>
                                            </tr>
                                            @endif
                                            @if($bookingHasTax)
                                            <tr>
                                                <td>{{ company_default_tax_label() }}</td>
                                                <td class="text--end pe--4">
                                                    {{ with_currency_symbol($booking->total_tax_amount) }}</td>
                                            </tr>
                                            @endif
                                            @if ($booking->extra_fee > 0)
                                                @if(is_array($booking->additional_charges_breakdown) && count($booking->additional_charges_breakdown))
                                                    @foreach($booking->additional_charges_breakdown as $acRow)
                                                        @if(($acRow['amount'] ?? 0) > 0)
                                                        <tr>
                                                            <td class="text-capitalize">{{ $acRow['name'] ?? translate('Additional_charges') }}</td>
                                                            <td class="text--end pe--4">{{ with_currency_symbol($acRow['amount'] ?? 0) }}</td>
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td class="text-capitalize">{{ translate('Additional_charges') }}</td>
                                                        <td class="text--end pe--4">{{ with_currency_symbol($booking->extra_fee) }}</td>
                                                    </tr>
                                                @endif
                                            @endif

                                            @if($visitHasExtras)
                                                <tr>
                                                    <td class="text-capitalize">{{ translate('Extra_Services') }}</td>
                                                    <td class="text--end pe--4">{{ with_currency_symbol($visitExtrasTotal) }}</td>
                                                </tr>
                                            @endif

                                            <tr>
                                                <td><strong>{{ translate('Grand_Total') }}</strong></td>
                                                <td class="text--end pe--4">
                                                    <strong>{{ with_currency_symbol($visitGrandTotal) }}</strong>
                                                </td>
                                            </tr>

                                            @if($hasNegativeAdditionalCharge)
                                                <tr>
                                                    <td>{{ translate('Refund') }}</td>
                                                    <td class="text--end pe--4">
                                                        {{ with_currency_symbol(abs($booking->additional_charge)) }}
                                                    </td>
                                                </tr>
                                            @endif
                                            </tbody>
                                        </table>
                            </div>
                            </div>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="c1">{{ translate('Booking Setup') }}</h3>
                            <hr>
                            @can('booking_can_manage_status')
                                <div class="d-flex justify-content-between align-items-center gap-10 form-control h-45">
                                    <span class="title-color">{{ translate('Payment Status') }}</span>

                                    <div class="on-off-toggle">
                                        <input class="on-off-toggle__input switcher_input"
                                               value="{{ $booking['is_paid'] ? '1' : '0' }}"
                                               {{ $booking['is_paid'] ? 'checked' : '' }} type="checkbox"
                                               id="payment_status" />
                                        <label for="payment_status" class="on-off-toggle__slider">
                                            <span class="on-off-toggle__on">
                                                <span class="on-off-toggle__text">{{ translate('Paid') }}</span>
                                                <span class="on-off-toggle__circle"></span>
                                            </span>
                                            <span class="on-off-toggle__off">
                                                <span class="on-off-toggle__circle"></span>
                                                <span class="on-off-toggle__text">{{ translate('Unpaid') }}</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            @endcan

                            @can('booking_can_manage_status')
                                <div class="mt-3">
                                    @if ($booking->booking_status != 'pending')
                                        @php
                                            $__rebookStatusNext = booking_admin_allowed_next_statuses_for_booking($booking);
                                            $maxBookingAmountRebook = business_config('max_booking_amount', 'booking_setup')->live_values ?? 0;
                                            $__rebookStatusCashBlock = $booking['payment_method'] == 'cash_after_service' && $booking->is_verified == '2' && $booking->total_booking_amount >= $maxBookingAmountRebook;
                                        @endphp
                                        <select class="js-select without-search" id="booking_status" data-current="{{ $booking->booking_status }}" data-can-complete="{{ booking_can_be_completed($booking) ? '1' : '0' }}">
                                            <option value="0" disabled selected>{{ translate('Booking_Status') }}: {{ ucwords(str_replace('_', ' ', $booking->booking_status)) }}</option>
                                            @foreach ($__rebookStatusNext as $__selSt)
                                                @php
                                                    $__rebookOptDisabled = $__rebookStatusCashBlock && in_array($__selSt, ['pending', 'ongoing', 'completed'], true);
                                                    if ($__selSt === 'ongoing' && ! booking_can_mark_ongoing_by_service_schedule($booking)) {
                                                        $__rebookOptDisabled = true;
                                                    }
                                                    if ($__selSt === 'completed' && ! booking_can_be_completed($booking)) {
                                                        $__rebookOptDisabled = true;
                                                    }
                                                    $__rebookOptLabel = match ($__selSt) {
                                                        'accepted' => translate('Accept_Booking'),
                                                        'pending' => translate('Mark_as_Pending'),
                                                        'ongoing' => translate('Mark_as_Ongoing'),
                                                        'on_hold' => ($booking->booking_status ?? '') === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold'),
                                                        'completed' => translate('Complete_Booking'),
                                                        'canceled', 'cancelled' => translate('Cancel_Booking'),
                                                        default => ucwords(str_replace('_', ' ', $__selSt)),
                                                    };
                                                @endphp
                                                <option value="{{ $__selSt }}" @if($__rebookOptDisabled) disabled @endif>{{ $__rebookOptLabel }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endcan
                            <div class="mt-3">
                                @if (!in_array($booking->booking_status, ['ongoing', 'completed']))
                                    @can('booking_can_manage_status')
                                        <input type="datetime-local" class="form-control h-45"
                                               name="service_schedule"
                                               value="{{ $booking->service_schedule }}"
                                               id="service_schedule"
                                               data-original="{{ $booking->service_schedule }}"
                                               onchange="service_schedule_update()">
                                    @endcan
                                @endif
                            </div>

                            <div class="py-3 d-flex flex-column gap-3 mb-2">
                                @if ($booking->evidence_photos)
                                    <div class="c1-light-bg radius-10 py-3 px-4">
                                        <div class="d-flex justify-content-start gap-2">
                                            <h4 class="mb-2">{{ translate('uploaded_Images') }}</h4>
                                        </div>

                                        <div class="py-3 px-4">
                                            <div class="d-flex flex-wrap gap-3 justify-content-lg-start">
                                                @foreach ($booking->evidence_photos_full_path ?? [] as $key => $img)
                                                    <img width="100" class="max-height-100"
                                                         src="{{ $img }}"
                                                         alt="{{ translate('evidence-photo') }}">
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                    <div class="c1-light-bg radius-10">
                                        <div class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                            <h4 class="d-flex align-items-center gap-2">
                                                <span class="material-icons title-color">map</span>
                                                {{ translate('Service_location') }}
                                            </h4>
                                            <div class="d-flex align-items-center gap-2">
                                                @can('booking_edit')
                                                    <div class="cursor-pointer" data-bs-toggle="modal"
                                                         data-bs-target="#serviceAddressModal--{{ $booking['id'] }}"
                                                         title="{{ translate('Edit_Details') }}">
                                                        <span class="material-symbols-outlined">edit_square</span>
                                                    </div>
                                                @endcan
                                                @if($serviceAtProviderPlace == 1)
                                                    @can('booking_edit')
                                                        <div class="cursor-pointer" data-bs-toggle="modal"
                                                             data-bs-target="#repeatServiceLocationModal--{{ $booking['id'] }}"
                                                             title="{{ translate('Service_location') }}">
                                                            <span class="material-symbols-outlined">map</span>
                                                        </div>
                                                    @endcan
                                                @endif
                                            </div>
                                        </div>

                                        <div class="py-3 px-4">
                                            @if($booking->service_location == 'provider')
                                                <div class="bg-warning p-3 rounded">
                                                    <h5>{{ translate('Customer has to go to the Provider Location to receive the service') }}</h5>
                                                </div>
                                                <div class="mt-3">
                                                    @if($booking->provider_id != null)
                                                        @if($booking->provider)
                                                            <h5 class="mb-1">{{ translate('Service Location') }}:</h5>
                                                            <div class="d-flex justify-content-between">
                                                                <p>{{ Str::limit($booking?->provider?->company_address ?? translate('not_available'), 100) }}</p>
                                                                <span class="material-icons">map</span>
                                                            </div>
                                                        @else
                                                            <p>{{ translate('Provider Unavailable') }}</p>
                                                        @endif
                                                    @else
                                                        <h5 class="mb-1">{{ translate('Service Location') }}:</h5>
                                                        <p>{{ translate('The Service Location will be available after this booking accepts or assign to a provider') }}</p>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="bg-warning p-3 rounded">
                                                    <h5>{{ translate('Provider has to go to the Customer Location to provide the service') }}</h5>
                                                </div>
                                                <div class="mt-3">
                                                    <h5 class="mb-1">{{ translate('Service Location') }}:</h5>
                                                    <div class="d-flex justify-content-between">
                                                        <p>{{ Str::limit($booking?->service_address?->address ?? translate('not_available'), 100) }}</p>
                                                        <span class="material-icons">map</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                <div class="c1-light-bg radius-10 serviceman-information">
                                    <div
                                        class="border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4 mb-2">
                                        <h4 class="d-flex align-items-center gap-2">
                                            <span class="material-icons title-color">person</span>
                                            {{ translate('Serviceman_Information') }}
                                        </h4>
                                        @if (isset($booking->serviceman) && in_array($booking->booking_status, ['ongoing', 'accepted']))
                                            <div class="btn-group">
                                                <div class="cursor-pointer" data-bs-toggle="dropdown"
                                                     aria-expanded="false">
                                                    <span class="material-symbols-outlined">more_vert</span>
                                                </div>
                                                <ul class="dropdown-menu dropdown-menu__custom border-none dropdown-menu-end">
                                                    <li>
                                                        <div
                                                            class="d-flex align-items-center gap-2 cursor-pointer provider-chat">
                                                            <span class="material-symbols-outlined">chat</span>
                                                            {{ translate('chat_with_Serviceman') }}
                                                            <form action="{{ route('admin.chat.create-channel') }}"
                                                                  method="post" id="chatForm-{{ $booking->id }}">
                                                                @csrf
                                                                <input type="hidden" name="serviceman_id"
                                                                       value="{{ $booking?->serviceman?->user?->id }}">
                                                                <input type="hidden" name="type" value="booking">
                                                                <input type="hidden" name="user_type"
                                                                       value="provider-serviceman">
                                                            </form>
                                                        </div>
                                                    </li>
                                                    @can('booking_can_manage_status')
                                                        <li>
                                                            <div class="d-flex align-items-center gap-2"
                                                                 data-bs-target="#servicemanModal" data-bs-toggle="modal">
                                                                <span
                                                                    class="material-symbols-outlined">manage_history</span>
                                                                {{ translate('change serviceman') }}
                                                            </div>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                    @if (isset($booking->serviceman))
                                        <div class="py-3 px-4">
                                            <div class="media gap-2 flex-wrap">
                                                <img width="58" height="58"
                                                     class="rounded-circle border border-white aspect-square object-fit-cover"
                                                     src="{{ $booking?->serviceman?->user?->profile_image_full_path }}"
                                                     alt="{{ translate('serviceman') }}">
                                                <div class="media-body">
                                                    <h5 class="c1 mb-3">
                                                        {{ Str::limit($booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->first_name . ' ' . $booking->serviceman->user->last_name : '', 30) }}
                                                    </h5>
                                                    <ul class="list-info">
                                                        <li>
                                                            <span class="material-icons">phone_iphone</span>
                                                            <a
                                                                href="tel:{{ $booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone : '' }}">
                                                                {{ $booking->serviceman && $booking->serviceman->user ? $booking->serviceman->user->phone : '' }}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-2 mt-30 align-items-center">
                                            <span class="material-icons text-muted fs-2">account_circle</span>
                                            <p class="text-muted text-center fw-medium mb-3">
                                                {{ translate('No Serviceman Information') }}</p>
                                        </div>

                                        <div class="text-center pb-4">
                                            <button
                                                class="btn btn--primary"
                                                data-bs-target="#servicemanModal"
                                                data-bs-toggle="modal"
                                                @if($booking['booking_status'] == 'completed' || $booking['booking_status'] == 'canceled' || !isset($booking->provider))
                                                    disabled
                                                @endif>
                                                {{ translate('assign Serviceman') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serviceAddressModal--{{$booking['id']}}" tabindex="-1" aria-labelledby="serviceAddressModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form action="{{route('admin.booking.service_address_update', [$booking->booking->service_address_id])}}"
                  method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0 m-4">
                        <div class="d-flex flex-column gap-2 align-items-center">
                            <img width="75" class="mb-2"
                                 src="{{asset('assets/provider-module')}}/img/media/address.jpg"
                                 alt="">
                            <h3>{{translate('Update customer service address')}}</h3>

                            <div class="row mt-4">
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="city"
                                                   placeholder="{{translate('city')}} *"
                                                   value="{{$customerAddress?->city}}" required>
                                            <label>{{translate('city')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="street"
                                                   placeholder="{{translate('street')}} *"
                                                   value="{{$customerAddress?->street}}" required>
                                            <label>{{translate('street')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="zip_code"
                                                   placeholder="{{translate('zip_code')}} *"
                                                   value="{{$customerAddress?->zip_code}}" required>
                                            <label>{{translate('zip_code')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="country"
                                                   placeholder="{{translate('country')}} *"
                                                   value="{{$customerAddress?->country}}" required>
                                            <label>{{translate('country')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="address" id="address"
                                                   placeholder="{{translate('address')}} *"
                                                   value="{{$customerAddress?->address}}" required>
                                            <label>{{translate('address')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="contact_person_name"
                                                   placeholder="{{translate('contact_person_name')}} *"
                                                   value="{{$customerAddress?->contact_person_name}}" required>
                                            <label>{{translate('contact_person_name')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control"
                                                   name="contact_person_number"
                                                   id="contact_person_number"
                                                   placeholder="{{translate('contact_person_number')}} *"
                                                   value="{{$customerAddress?->contact_person_number}}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <select class="js-select theme-input-style w-100" name="address_label">
                                            <option selected disabled>{{translate('Select_address_label')}}*</option>
                                            <option
                                                value="home" {{$customerAddress?->address_label == 'home' ? 'selected' : ''}}>{{translate('Home')}}</option>
                                            <option
                                                value="office" {{$customerAddress?->address_label == 'office' ? 'selected' : ''}}>{{translate('Office')}}</option>
                                            <option
                                                value="others" {{$customerAddress?->address_label == 'others' ? 'selected' : ''}}>{{translate('others')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <select class="js-select select-zone theme-input-style w-100" name="zone_id">
                                            <option value="" disabled>{{translate('Select zone')}}</option>
                                            @foreach($zones as $zone)
                                                <option
                                                    value="{{$zone?->id}}" {{$zone?->id == $customerAddress?->zone_id ? 'selected' : null}}>{{$zone?->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="latitude" id="latitude"
                                                   placeholder="{{translate('lat')}} *"
                                                   value="{{$customerAddress?->lat}}" required readonly
                                                   data-bs-toggle="tooltip" data-bs-placement="top"
                                                   title="{{translate('Select from map')}}">
                                            <label>{{translate('lat')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-30">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" name="longitude" id="longitude"
                                                   placeholder="{{translate('lon')}} *"
                                                   value="{{$customerAddress?->lon}}" required readonly
                                                   data-bs-toggle="tooltip" data-bs-placement="top"
                                                   title="{{translate('Select from map')}}">
                                            <label>{{translate('lon')}} *</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="location_map_div">
                                    <input id="pac-input" class="controls rounded" data-toggle="tooltip"
                                           data-placement="right"
                                           data-original-title="{{ translate('search_your_location_here') }}"
                                           type="text" placeholder="{{ translate('search_here') }}"/>
                                    <div id="location_map_canvas" class="overflow-hidden rounded mt-4"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-end gap-3 border-0 pt-0 pb-4 m-4">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal" aria-label="Close">
                            {{translate('Cancel')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('Update')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data" id="modal-data-info">
                @include('bookingmodule::admin.booking.partials.details.provider-info-modal-data')
            </div>
        </div>
    </div>

    <div class="modal fade" id="servicemanModal" tabindex="-1" aria-labelledby="servicemanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-content-data1" id="modal-data-info1">
                @include('bookingmodule::admin.booking.partials.details.serviceman-info-modal-data')
            </div>
        </div>
    </div>

    @include('providermanagement::admin.partials.provider-performance-feedback-modal')

    @if($visitCanEditExtras)
    <div class="modal fade" id="addVisitExtraServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ $visitExtraStoreUrl }}" method="POST" id="visit-extra-service-form" data-store-url="{{ $visitExtraStoreUrl }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST" class="js-visit-extra-service-method">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addVisitExtraServiceModalLabel">{{ translate('Add_Extra_Service') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="255" placeholder="{{ translate('Title') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Details_of_Service') }}</label>
                            <textarea name="details" class="form-control" rows="2" maxlength="2000" placeholder="{{ translate('Details_of_Service') }}"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="service" selected>{{ translate('Service') }}</option>
                                <option value="spare_part">{{ translate('Spare_Part') }}</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Qty') }} <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" value="1" required min="1" step="1">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-control" value="0" required min="0" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Discount') }}</label>
                                <input type="number" name="discount" class="form-control" value="0" min="0" step="0.01">
                            </div>
                        </div>
                        <p class="mb-0 small text-muted">{{ translate('Total') }} = ({{ translate('Qty') }} × {{ translate('Price') }}) − {{ translate('Discount') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary js-visit-extra-service-submit">{{ translate('Add') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="modal fade" id="serviceUpdateModal--{{ $booking['id'] }}" tabindex="-1"
         aria-labelledby="serviceUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header px-4 pt-4 border-0 pb-1">
                    <h3 class="text-capitalize">{{ translate('update_booking') }}</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap mb-4">
                        <div>
                            <h5 class="mb-2">
                                {{ translate('Booking') }} # {{ $booking['readable_id'] }}
                            </h5>
                            <h3 class="c1 fw-bold mb-2">{{ translate('Sub-Booking') }} # {{ $booking['readable_id']}}
                            </h3>
                        </div>
                        <h5 class="d-flex gap-1 flex-wrap align-items-center justify-content-end fw-normal mb-2">
                            <div>{{ translate('Schedule_Date') }} :</div>
                            <div id="service_schedule__span">
                                <div class="fw-semibold">{{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</div>
                            </div>
                        </h5>
                    </div>

                    <div class="bg-F8F8F8 p-3 mb-3">
                        <h4 class="mb-3"> {{ translate('Service') }} : {{ translate('AC_Repairing') }}
                        </h4>
                        <div class="d-flex flex-wrap gap-3">
                            <h4> {{ translate('Category') }} : {{ $booking->booking->category->name }}</h4>
                            <h4> {{ translate('SubCategory') }} : {{ $booking->booking->subCategory->name }}</h4>
                        </div>
                    </div>

{{--                    <div class="mb-30">--}}
{{--                        <span class="c1 fw-semibold"> # {{ translate('Note') }}:</span>--}}
{{--                        <span class="title-color">--}}
{{--                        {{ translate('Please provide extra layer in the packaging') }}</span>--}}
{{--                    </div>--}}

                    <form action="{{ route('admin.booking.service.update_repeat_booking_service') }}" method="POST"
                          id="booking-edit-table" class="mb-30">
                        <div class="table-responsive">
                            <table class="table text-nowrap align-middle mb-0" id="service-edit-table">
                                @csrf
                                @method('put')
                                <thead>
                                <tr>
                                    <th class="ps-lg-3 fw-bold">{{ translate('Service') }}</th>
                                    <th class="fw-bold text--end">{{ translate('Price') }}</th>
                                    <th class="fw-bold text-center">{{ translate('Qty') }}</th>
                                    <th class="fw-bold text--end">{{ translate('Discount') }}</th>
                                    <th class="fw-bold text--end">{{ translate('Total') }}</th>
                                </tr>
                                </thead>

                                <tbody id="service-edit-tbody">
                                @php
                                    $sub_total = 0;
                                @endphp
                                @foreach ($booking->detail as $key => $detail)
                                    <tr id="service-row--{{ $detail?->variant_key }}">
                                        <td class="text-wrap ps-lg-3">
                                            @if (isset($detail->service))
                                                <div class="d-flex flex-column">
                                                    <a href="{{ route('admin.service.detail', [$detail->service->id]) }}"
                                                       class="fw-bold">{{ Str::limit($detail->service->name, 30) }}</a>
                                                    <div>{{ Str::limit($detail ? $detail->variant_key : '', 50) }}
                                                    </div>
                                                </div>
                                            @else
                                                <span
                                                    class="badge badge-pill badge-danger">{{ translate('Service_unavailable') }}</span>
                                            @endif
                                        </td>
                                        <td class="text--end" id="service-cost-{{ $detail?->variant_key }}">
                                            {{ currency_symbol() . ' ' . $detail->service_cost }}</td>
                                        <td>
                                            <input type="number" min="1" name="qty[]"
                                                   class="form-control qty-width dark-color-bo m-auto"
                                                   id="qty-{{ $detail?->variant_key }}"
                                                   value="{{ $detail->quantity }}"
                                                   oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                        </td>
                                        <td class="text--end" id="discount-amount-{{ $detail?->variant_key }}">
                                            {{ currency_symbol() . ' ' . $detail->discount_amount }}</td>
                                        <td class="text--end" id="total-cost-{{ $detail?->variant_key }}">
                                            {{ currency_symbol() . ' ' . $detail->total_cost }}
                                        </td>
                                        <input type="hidden" name="service_ids[]"
                                               value="{{ $detail->service->id }}">
                                        <input type="hidden" name="variant_keys[]"
                                               value="{{ $detail->variant_key }}">
                                    </tr>
                                    @php
                                        $sub_total += $detail->service_cost * $detail->quantity;
                                    @endphp
                                @endforeach
                                <input type="hidden" name="zone_id" value="{{ $booking->booking->zone_id }}">
                                <input type="hidden" name="booking_id" value="{{ $booking->booking_id }}">
                                <input type="hidden" name="booking_repeat_id" value="{{ $booking->id }}">
                                </tbody>
                            </table>
                        </div>
                    </form>

                </div>
                <div class="modal-footer d-flex justify-content-end gap-3 border-0 pt-0 pb-4">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal"
                            aria-label="Close">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary"
                            form="booking-edit-table">{{ translate('update_cart') }}</button>
                </div>
            </div>
        </div>
    </div>

    @include('bookingmodule::admin.booking.partials.details._repeat-ongoing-service-location-modal')

    @include('bookingmodule::admin.booking.partials.details._update-customer-address-modal')

    @include('bookingmodule::admin.booking.partials._booking-status-reason-modal')
@endsection

@push('script')
    <script>
        "use strict";

        (function () {
            var form = document.getElementById('visit-extra-service-form');
            if (!form) {
                return;
            }
            var storeUrl = form.getAttribute('data-store-url') || form.getAttribute('action');
            var titleEl = document.getElementById('addVisitExtraServiceModalLabel');
            var submitBtn = form.querySelector('.js-visit-extra-service-submit');
            var methodInput = form.querySelector('.js-visit-extra-service-method');
            var modalEl = document.getElementById('addVisitExtraServiceModal');
            var addLabel = @json(translate('Add_Extra_Service'));
            var editLabel = @json(translate('Edit_Extra_Service'));
            var addBtnLabel = @json(translate('Add'));
            var updateBtnLabel = @json(translate('Update'));

            function setField(name, value) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field) {
                    field.value = value;
                }
            }

            function resetToAdd() {
                form.setAttribute('action', storeUrl);
                if (methodInput) {
                    methodInput.value = 'POST';
                }
                form.reset();
                setField('quantity', '1');
                setField('price', '0');
                setField('discount', '0');
                setField('type', 'service');
                if (titleEl) {
                    titleEl.textContent = addLabel;
                }
                if (submitBtn) {
                    submitBtn.textContent = addBtnLabel;
                }
            }

            function fillFromButton(btn) {
                form.setAttribute('action', btn.getAttribute('data-update-url'));
                if (methodInput) {
                    methodInput.value = 'PUT';
                }
                setField('title', btn.getAttribute('data-title') || '');
                setField('details', btn.getAttribute('data-details') || '');
                setField('type', btn.getAttribute('data-type') || 'service');
                setField('quantity', btn.getAttribute('data-quantity') || '1');
                setField('price', btn.getAttribute('data-price') || '0');
                setField('discount', btn.getAttribute('data-discount') || '0');
                if (titleEl) {
                    titleEl.textContent = editLabel;
                }
                if (submitBtn) {
                    submitBtn.textContent = updateBtnLabel;
                }
            }

            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function (event) {
                    var trigger = event.relatedTarget;
                    if (trigger && trigger.classList.contains('js-edit-visit-extra-service')) {
                        fillFromButton(trigger);
                    } else {
                        resetToAdd();
                    }
                });
            }
        })();

        // Provider performance feedback must be submitted before proceeding with completion/cancellation/provider reassign.
        let pendingReassignProviderId = null;
        let pendingPostFeedbackAction = null; // 'reload' | 'reassign'

        const bookingContextId = @json($booking->id);
        const bookingCurrentProviderId = @json($booking->provider_id);

        function openProviderPerformanceFeedbackModal(evaluatedProviderId, actionType = 'completed') {
            $('#providerPerformanceContextBookingId').val(bookingContextId);
            $('#providerPerformanceProviderId').val(evaluatedProviderId);
            $('#providerPerformanceActionType').val(actionType === 'canceled' ? 'cancelled' : actionType);
            $('#providerPerformanceNotes').val('');
            $('#providerPerformanceFeedbackForm input[type="radio"]').prop('checked', false);
            $('#providerPerformanceFeedbackForm input[type="checkbox"]').prop('checked', false);

            // Prevent multiple Bootstrap modal backdrops from blocking clicks
            // (e.g. provider reassign modal still open when opening feedback).
            document.querySelectorAll('.modal.show').forEach((m) => {
                if (m?.id !== 'providerPerformanceFeedbackModal') {
                    bootstrap.Modal.getInstance(m)?.hide();
                }
            });
            $('.modal-backdrop').remove();

            const modalEl = document.getElementById('providerPerformanceFeedbackModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function reassignProviderAfterFeedback(providerId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/provider/reassign-provider') }}' + '/' + bookingId;
            const sortOption = document.querySelector('input[name="sort"]:checked')?.value ?? 'default';
            const searchTerm = $('.search-form-input').val() ?? '';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    sort_by: sortOption,
                    booking_id: bookingId,
                    search: searchTerm,
                    provider_id: providerId
                },
                beforeSend: function () {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function (response) {
                    if (response?.view) {
                        $('.modal-content-data').html(response.view);
                    }
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function () {
                        location.reload();
                    }, 600);
                },
                error: function () {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        document.addEventListener('pk:provider-feedback-stored', function (e) {
            if (pendingPostFeedbackAction !== 'reassign') {
                pendingReassignProviderId = null;
                pendingPostFeedbackAction = null;
                return;
            }

            const providerId = pendingReassignProviderId;
            pendingReassignProviderId = null;
            pendingPostFeedbackAction = null;

            if (!providerId) {
                return;
            }

            e.preventDefault();
            if (typeof window.updateProvider === 'function') {
                window.updateProvider(providerId);
            } else {
                reassignProviderAfterFeedback(providerId);
            }
        });

        $('.switcher_input').on('click', function() {
            let paymentStatus = $(this).is(':checked') === true ? 1 : 0;
            payment_status_change(paymentStatus)
        })

        $(document).on('click', '.reassign-provider', function() {
            let newProviderId = $(this).data('provider-reassign');
            if (!newProviderId) {
                toastr.error('{{ translate('Provider not found for feedback.') }}');
                return;
            }

            if (!bookingCurrentProviderId) {
                if (typeof window.updateProvider === 'function') {
                    window.updateProvider(newProviderId);
                } else {
                    reassignProviderAfterFeedback(newProviderId);
                }
                return;
            }

            pendingReassignProviderId = newProviderId;
            pendingPostFeedbackAction = 'reassign';
            openProviderPerformanceFeedbackModal(bookingCurrentProviderId, 'provider_changed');
        })

        $('.reassign-serviceman').on('click', function() {
            let id = $(this).data('serviceman-reassign');
            updateServiceman(id)
        })

        $('.offline-payment').on('click', function() {
            let route = '{{ route('admin.booking.offline-payment.verify', ['booking_id' => $booking->id]) }}';
            route_alert_reload(route, '{{ translate('Want to verify the payment') }}', true);
        })

        @if ($booking->booking_status == 'pending')
        $(document).ready(function() {
            selectElementVisibility('serviceman_assign', false);
            selectElementVisibility('payment_status', false);
        });
        @endif

        $("#booking_status").change(function() {
            var $sel = $("#booking_status");
            var booking_status = $sel.val();
            var previous_status = $sel.data('current');
            if (booking_status && booking_status !== '0' && parseInt(booking_status, 10) !== 0) {
                if (typeof bookingAdminStatusNeedsReason === 'function' && bookingAdminStatusNeedsReason(booking_status, previous_status)) {
                    $sel.val(previous_status);
                    if ($sel.next('.select2-container').length) {
                        $sel.next('.select2-container').find('.select2-selection__rendered').text($sel.find('option:selected').text());
                    }
                    if (typeof bookingAdminOpenStatusReasonModal === 'function') {
                        bookingAdminOpenStatusReasonModal(booking_status, previous_status);
                    }
                    return;
                }
                var route = '{{ route('admin.booking.status_update', [$booking->id]) }}' + '?booking_status=' + booking_status;
                update_booking_details(route, '{{ translate('want_to_update_status') }}', 'booking_status', booking_status);
            } else {
                toastr.error('{{ translate('choose_proper_status') }}');
            }
        });

        $("#serviceman_assign").change(function() {
            var serviceman_id = $("#serviceman_assign option:selected").val();
            if (serviceman_id !== 'no_serviceman') {
                var route = '{{ route('admin.booking.serviceman_update', [$booking->id]) }}' + '?serviceman_id=' +
                    serviceman_id;

                update_booking_details(route, '{{ translate('want_to_assign_the_serviceman') }}?',
                    'serviceman_assign', serviceman_id);
            } else {
                toastr.error('{{ translate('choose_proper_serviceman') }}');
            }
        });

        function payment_status_change(payment_status) {
            var route = '{{ route('admin.booking.payment_update', [$booking->id]) }}' + '?payment_status=' +
                payment_status;
            update_booking_details(route, '{{ translate('want_to_update_status') }}', 'payment_status', payment_status);
        }

        function service_schedule_update() {
            var $input = $("#service_schedule");
            var service_schedule = $input.val();
            var original = $input.data('original');

            if (!service_schedule) {
                $input.val(original);
                return;
            }

            var route = '{{ route('admin.booking.schedule_update', [$booking->id]) }}' + '?service_schedule=' + service_schedule;

            update_booking_details(route, '{{ translate('want_to_update_the_booking_schedule') }}', 'service_schedule', service_schedule);
        }

        function update_booking_details(route, message, componentId, updatedValue) {
            Swal.fire({
                title: "{{ translate('are_you_sure') }}?",
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'var(--bs-secondary)',
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonText: '{{ translate('Cancel') }}',
                confirmButtonText: '{{ translate('Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    var revertStatus = componentId === 'booking_status' ? $('#booking_status').data('current') : undefined;
                    var ajaxOpts = {
                        dataType: 'json',
                        beforeSend: function() {},
                        success: function(data) {
                            if (componentId === 'booking_status') {
                                $('#booking_status').data('current', updatedValue);
                            }
                            update_component(componentId, updatedValue);
                            toastr.success(data.message, {
                                CloseButton: true,
                                ProgressBar: true
                            });

                            var finish = function () {
                                if (componentId === 'booking_status' && (updatedValue === 'completed' || updatedValue === 'canceled' || updatedValue === 'cancelled')) {
                                    if (bookingCurrentProviderId) {
                                        pendingPostFeedbackAction = 'reload';
                                        openProviderPerformanceFeedbackModal(bookingCurrentProviderId, updatedValue);
                                        return;
                                    }
                                }

                                if (componentId === 'booking_status' || componentId === 'payment_status' ||
                                    componentId === 'service_schedule' || componentId ===
                                    'serviceman_assign') {
                                    location.reload();
                                }
                            };

                            if (typeof window.waAdminAfterAjaxWithOptionalWhatsAppPrompt === 'function') {
                                window.waAdminAfterAjaxWithOptionalWhatsAppPrompt(data, finish);
                            } else {
                                finish();
                            }
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Something went wrong. Please try again.') }}';
                            if (componentId === 'booking_status' && revertStatus !== undefined) {
                                $('#booking_status').val(revertStatus);
                                $('#booking_status').data('current', revertStatus);
                                if ($('#booking_status').next('.select2-container').length) {
                                    $('#booking_status').next('.select2-container').find('.select2-selection__rendered').text($('#booking_status option:selected').text());
                                }
                            }
                            toastr.error(msg, { CloseButton: true, ProgressBar: true });
                        },
                        complete: function() {},
                    };
                    if (componentId === 'booking_status') {
                        ajaxOpts.url = '{{ route('admin.booking.status_update', [$booking->id]) }}';
                        ajaxOpts.method = 'POST';
                        ajaxOpts.data = {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            booking_status: updatedValue
                        };
                        ajaxOpts.headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
                        $.ajax(ajaxOpts);
                    } else {
                        ajaxOpts.url = route;
                        ajaxOpts.method = 'GET';
                        ajaxOpts.data = {};
                        $.ajax(ajaxOpts);
                    }
                }
            })
        }

        function update_component(componentId, updatedValue) {

            if (componentId === 'booking_status') {
                $("#booking_status__span").html(updatedValue);

                selectElementVisibility('serviceman_assign', true);
                selectElementVisibility('payment_status', true);

            } else if (componentId === 'payment_status') {
                $("#payment_status__span").html(updatedValue);
                if (updatedValue === 'paid') {
                    $("#payment_status__span").addClass('text-success').removeClass('text-danger');
                } else if (updatedValue === 'unpaid') {
                    $("#payment_status__span").addClass('text-danger').removeClass('text-success');
                }

            }
        }

        function selectElementVisibility(componentId, visibility) {
            if (visibility === true) {
                $('#' + componentId).next(".select2-container").show();
            } else if (visibility === false) {
                $('#' + componentId).next(".select2-container").hide();
            } else {}
        }
    </script>

    <script>
        $(document).ready(function() {
            $('#category_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
            $('#sub_category_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
            $('#service_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
            $('#service_variation_selector__select').select2({
                dropdownParent: "#serviceUpdateModal--{{ $booking['id'] }}"
            });
        });

    </script>


    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ business_config('google_map', 'third_party')?->live_values['map_api_key_client'] }}&libraries=places&v=3.45.8">
    </script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this);
        });

        $(document).ready(function() {
            function initAutocomplete() {
                let myLatLng = {
                    lat: {{ $customerAddress->lat ?? 23.811842872190343 }},
                    lng: {{ $customerAddress->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                    center: myLatLng,
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                let marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('latitude').value = coordinates['lat'];
                    document.getElementById('longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address').value = results[1]
                                    .formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];

                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];

                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('latitude').value = this.position.lat();
                            document.getElementById('longitude').value = this.position
                                .lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            initAutocomplete();
        });


        $('.__right-eye').on('click', function() {
            if ($(this).hasClass('active')) {
                $(this).removeClass('active')
                $(this).find('i').removeClass('tio-invisible')
                $(this).find('i').addClass('tio-hidden-outlined')
                $(this).siblings('input').attr('type', 'password')
            } else {
                $(this).addClass('active')
                $(this).siblings('input').attr('type', 'text')


                $(this).find('i').addClass('tio-invisible')
                $(this).find('i').removeClass('tio-hidden-outlined')
            }
        })
    </script>

    <script>
        $(document).ready(function() {

            $(document).on('click', '.sort-by-class', function() {
                console.log('hi')
                const route = '{{ url('admin/provider/available-provider') }}'
                var sortOption = document.querySelector('input[name="sort"]:checked').value;
                var bookingId = "{{ $booking->id }}"

                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        sort_by: sortOption,
                        booking_id: bookingId
                    },
                    beforeSend: function() {

                    },
                    success: function(response) {
                        $('.modal-content-data').html(response.view);
                    },
                    complete: function() {},
                    error: function() {
                        toastr.error('{{ translate('Failed to load') }}')
                    }
                });
            })
        });

        $(document).ready(function() {
            $(document).on('keyup', '.search-form-input', function() {
                const route = '{{ url('admin/provider/available-provider') }}';
                let sortOption = document.querySelector('input[name="sort"]:checked').value;
                let bookingId = "{{ $booking->id }}";
                let searchTerm = $('.search-form-input').val();

                $.get({
                    url: route,
                    dataType: 'json',
                    data: {
                        sort_by: sortOption,
                        booking_id: bookingId,
                        search: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        $('.modal-content-data').html(response.view);


                        var cursorPosition = searchTerm.lastIndexOf(searchTerm.charAt(searchTerm
                            .length - 1)) + 1;
                        $('.search-form-input').focus().get(0).setSelectionRange(cursorPosition,
                            cursorPosition);
                    },
                    complete: function() {},
                    error: function() {
                        toastr.error('{{ translate('Failed to load') }}');
                    }
                });
            });
        });

        function updateProvider(providerId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/provider/reassign-provider') }}' + '/' + bookingId;
            const sortOption = document.querySelector('input[name="sort"]:checked').value;
            const searchTerm = $('.search-form-input').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    sort_by: sortOption,
                    booking_id: bookingId,
                    search: searchTerm,
                    provider_id: providerId
                },
                beforeSend: function() {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
                complete: function() {},
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }



        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).on('keyup', '.search-form-input1', function() {
                const route = '{{ url('admin/booking/serviceman-update', $booking->id) }}';
                let searchTerm = $('.search-form-input1').val();

                $.ajax({
                    url: route,
                    type: 'PUT',
                    dataType: 'json',
                    data: {
                        booking_id: "{{ $booking->id }}",
                        search: searchTerm,
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        $('.modal-content-data1').html(response.view);
                    },
                    complete: function() {},
                    error: function(xhr) {
                        if (xhr.status === 419) {
                            toastr.error('{{ translate('Session expired, please refresh the page.') }}');
                        } else {
                            toastr.error('{{ translate('Failed to load') }}');
                        }
                    }
                });
            });
        });


        function updateServiceman(servicemanId) {
            const bookingId = "{{ $booking->id }}";
            const route = '{{ url('admin/booking/serviceman-update') }}' + '/' + bookingId;
            const searchTerm = $('.search-form-input1').val();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: route,
                type: 'PUT',
                dataType: 'json',
                data: {
                    booking_id: bookingId,
                    search: searchTerm,
                    serviceman_id: servicemanId
                },
                beforeSend: function() {
                    toastr.info('{{ translate('Processing request...') }}');
                },
                success: function(response) {
                    $('.modal-content-data').html(response.view);
                    toastr.success('{{ translate('Successfully reassign provider') }}');
                    setTimeout(function() {
                        location.reload()
                    }, 600);
                },
                complete: function() {},
                error: function() {
                    toastr.error('{{ translate('Failed to load') }}');
                }
            });
        }

        $(document).ready(function() {
            $('.your-button-selector').on('click', function() {
                updateSearchResults();
            });

            $('.cancellation-note').hide();

            $('.deny-request').click(function() {
                $('.cancellation-note').show();
            });

            $('.approve-request').click(function() {
                $('.cancellation-note').hide();
            });
        });

        $('.customer-chat').on('click', function() {
            $(this).find('form').submit();
        });

        $('.provider-chat').on('click', function() {
            $(this).find('form').submit();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const denyRequestRadio = document.querySelector('.deny-request');
            const cancellationNote = document.querySelector('.cancellation-note');

            denyRequestRadio.addEventListener('change', function() {
                if (this.checked) {
                    cancellationNote.style.display = 'block';
                    document.querySelector('textarea[name="booking_deny_note"]').required = true;
                } else {
                    cancellationNote.style.display = 'none';
                    document.querySelector('textarea[name="booking_deny_note"]').required = false;
                }
            });
        });

        // for update service location from update customer address modal
        $(document).ready(function() {
            function addressMap() {
                let myLatLng = {
                    lat: {{ $booking->service_address?->lat ?? 23.811842872190343 }},
                    lng: {{ $booking->service_address?->lon ?? 90.356331 }}
                };
                const map = new google.maps.Map(document.getElementById("address_location_map_canvas"), {
                    center: myLatLng,
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                let marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('address_latitude').value = coordinates['lat'];
                    document.getElementById('address_longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address_address').value = results[1].formatted_address;
                            }
                        }
                    });
                });

                const input = document.getElementById("address_pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);

                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];

                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }

                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];

                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('address_latitude').value = this.position.lat();
                            document.getElementById('address_longitude').value = this.position.lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            addressMap();
        });

        $(document).ready(function() {
            // Get booking ID dynamically
            var bookingId = "{{ $booking['id'] }}";

            function toggleServiceLocation() {
                if ($('#customer_location').is(':checked')) {
                    $('.customer-details').show();
                    $('.provider-details').hide();
                } else {
                    $('.customer-details').hide();
                    $('.provider-details').show();
                }
            }

            // Run toggle function on radio button change
            $('input[name="service_location"]').on('change', function() {
                toggleServiceLocation();
            });

            // Run toggle function when the modal is opened
            $('#repeatServiceLocationModal--' + bookingId).on('shown.bs.modal', function () {
                toggleServiceLocation();
            });

            // When the address modal opens, hide the first modal
            $('#customerAddressModal--' + bookingId).on('show.bs.modal', function () {
                $('#repeatServiceLocationModal--' + bookingId).modal('hide'); // Hide the first modal
            });

            // When the address modal closes, reopen the service location modal and update the address
            $('#customerAddressModal--' + bookingId).on('hidden.bs.modal', function () {
                $('#repeatServiceLocationModal--' + bookingId).modal('show'); // Show the first modal again
            });
        });

        $(document).ready(function () {
            $("#customerAddressModalSubmit").on("submit", function (e) {
                e.preventDefault(); // Prevent form submission

                var bookingId = "{{ $booking['id'] }}";

                let customerAddressModal = $("#customerAddressModal--" + bookingId);
                let repeatServiceLocationModal = $("#repeatServiceLocationModal--" + bookingId);

                // Copy updated data from customerAddressModal inputs
                let contactPersonName = customerAddressModal.find("input[name='contact_person_name']").val();
                let contactPersonNumber = customerAddressModal.find("input[name='contact_person_number']").val();
                let addressLabel = customerAddressModal.find("select[name='address_label']").val();
                let address = customerAddressModal.find("input[name='address']").val();
                let latitude = customerAddressModal.find("input[name='latitude']").val();
                let longitude = customerAddressModal.find("input[name='longitude']").val();
                let city = customerAddressModal.find("input[name='city']").val();
                let street = customerAddressModal.find("input[name='street']").val();
                let zipCode = customerAddressModal.find("input[name='zip_code']").val();
                let country = customerAddressModal.find("input[name='country']").val();

                // Update the corresponding hidden inputs in repeatServiceLocationModal
                repeatServiceLocationModal.find("input[name='contact_person_name']").val(contactPersonName);
                repeatServiceLocationModal.find("input[name='contact_person_number']").val(contactPersonNumber);
                repeatServiceLocationModal.find("input[name='address_label']").val(addressLabel);
                repeatServiceLocationModal.find("input[name='address']").val(address);
                repeatServiceLocationModal.find("input[name='latitude']").val(latitude);
                repeatServiceLocationModal.find("input[name='longitude']").val(longitude);
                repeatServiceLocationModal.find("input[name='city']").val(city);
                repeatServiceLocationModal.find("input[name='street']").val(street);
                repeatServiceLocationModal.find("input[name='zip_code']").val(zipCode);
                repeatServiceLocationModal.find("input[name='country']").val(country);

                $('.updated_customer_name').text(contactPersonName); // Update the customer name
                $('#updated_customer_phone').text(contactPersonNumber); // Update the customer
                $('#customer_service_location').removeClass('text-danger'); // Update the customer service location
                $('#customer_service_location').text(address); // Update the customer service location
                $('.customer-address-update-btn').removeAttr('disabled'); // Update the customer service location update button

                // Close the customerAddressModal
                customerAddressModal.modal("hide");

                // Open the repeatServiceLocationModal to show updated data
                repeatServiceLocationModal.modal("show");
            });
        });

        $(".customer-address-reset-btn").on("click", function (e) {
            e.preventDefault(); // prevent default behavior

            // Reset the form (visible inputs)
            $("#customerAddressModalSubmit")[0].reset();

            // Restore hidden inputs to original values from server
            $("input[name='contact_person_name']").val("{{ $booking->service_address->contact_person_name ?? '' }}");

            $("input[name='contact_person_number']").val("{{ $booking->service_address->contact_person_number ?? '' }}");
            $("input[name='address_label']").val("{{ $booking->service_address->label ?? '' }}");
            $("input[name='address']").val("{{ $booking->service_address->address ?? '' }}");
            $("input[name='latitude']").val("{{ $booking->service_address->latitude ?? '' }}");
            $("input[name='longitude']").val("{{ $booking->service_address->longitude ?? '' }}");
            $("input[name='city']").val("{{ $booking->service_address->city ?? '' }}");
            $("input[name='street']").val("{{ $booking->service_address->street ?? '' }}");
            $("input[name='zip_code']").val("{{ $booking->service_address->zip_code ?? '' }}");
            $("input[name='country']").val("{{ $booking->service_address->country ?? '' }}");

            // Update the UI
            let name = "{{ $customer_name }}";
            let phone = "{{ $customer_phone }}";
            let customerAddress = "{{ $booking?->service_address?->address }}";

            $('.updated_customer_name').text(name); // Update the customer name
            $('#updated_customer_phone').text(phone); // Update the customer phone

            if (customerAddress) {
                $('#customer_service_location').text(customerAddress);
                $('#customer_service_location').removeClass('text-danger');
                $('.customer-address-update-btn').removeAttr('disabled');
            } else {
                $('#customer_service_location').text("No address found");
                $('#customer_service_location').addClass('text-danger');
                $('.customer-address-update-btn').attr('disabled', true);
            }
        });

    </script>

    <script>
        $(document).ready(function() {
            $('.without-search').select2({
                minimumResultsForSearch: Infinity
            });
        });
    </script>
@endpush
