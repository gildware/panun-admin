@extends('adminmodule::layouts.master')

@section('title', translate('Add_New_Booking'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
    <style>
        .select2-results__option {
            padding: 10px 12px !important;
            border-bottom: 1px solid #eee;
        }
        .select2-results__option:last-child {
            border-bottom: none;
        }
        .select2-results__option--highlighted {
            background-color: #f8f9fa !important;
        }
        .select2-results__option--highlighted * {
            color: inherit !important;
            font-weight: bold !important;
        }
        .select2-results__option * {
            color: inherit !important;
        }
        #provider-select + .select2-container .select2-selection__rendered {
            line-height: 1.5;
        }
        /* Override Select2 default hover styles */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #f8f9fa !important;
            color: inherit !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f8f9fa !important;
            color: inherit !important;
        }
        .select2-container.booking-select2-invalid .select2-selection {
            border-color: #dc3545 !important;
        }
        @media (min-width: 992px) {
            .booking-create-top-row .booking-create-customer-col {
                flex: 0 0 70%;
                max-width: 70%;
            }
            .booking-create-top-row .booking-create-source-col {
                flex: 0 0 30%;
                max-width: 30%;
            }
            .booking-create-provider-schedule-row .booking-create-provider-col {
                flex: 0 0 70%;
                max-width: 70%;
            }
            .booking-create-provider-schedule-row .booking-create-schedule-col {
                flex: 0 0 30%;
                max-width: 30%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ translate('Add_New_Booking') }}</h2>
            <a href="{{ $bookingGoBackUrl ?? route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']) }}"
               class="btn btn-secondary">
                {{ translate('Go_back') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                @if(!empty($reopenNewBookingDraft))
                    <div class="alert alert-info mb-4" role="alert">
                        {{ translate('Reopen_follow_up_banner') }}
                        <strong>#{{ $reopenNewBookingDraft['source_readable_id'] ?? $reopenNewBookingDraft['source_booking_id'] }}</strong>.
                        {{ translate('Reopen_follow_up_banner_hint') }}
                    </div>
                @endif
                <form action="{{ route('admin.booking.preview') }}" method="POST" id="booking-form"
                      data-currency="{{ currency_symbol() ?? '' }}" novalidate>
                    @csrf
                    <input type="hidden" name="booking_go_back_url"
                           value="{{ old('booking_go_back_url', $bookingGoBackUrl ?? request('booking_go_back_url')) }}">
                    @if(old('lead_id', request('lead_id')))
                        <input type="hidden" name="lead_id" value="{{ old('lead_id', request('lead_id')) }}">
                        <input type="hidden" name="in_modal" value="{{ old('in_modal', request('in_modal', 1)) }}">
                    @endif
                    @if(!empty($reopenNewBookingDraft['source_booking_id']))
                        <input type="hidden" name="reopen_source_booking_id" value="{{ $reopenNewBookingDraft['source_booking_id'] }}">
                    @endif
                    @if(old('whatsapp_reserved_readable_id', request('whatsapp_reserved_readable_id')))
                        <input type="hidden" name="whatsapp_reserved_readable_id"
                               value="{{ old('whatsapp_reserved_readable_id', request('whatsapp_reserved_readable_id')) }}">
                    @endif

                    @if(old('whatsapp_reserved_readable_id', request('whatsapp_reserved_readable_id')))
                        <div class="alert alert-info mb-4" role="alert">
                            {{ translate('WhatsApp_booking_prefill_banner') }}
                            <strong>#{{ old('whatsapp_reserved_readable_id', request('whatsapp_reserved_readable_id')) }}</strong>.
                            {{ translate('WhatsApp_booking_prefill_banner_hint') }}
                        </div>
                    @endif

                    {{-- 1. Customer (70%) + Source and Assignee (30%) --}}
                    <div class="row g-3 mb-4 align-items-stretch booking-create-top-row">
                        <div class="col-12 col-lg booking-create-customer-col">
                            <div class="border rounded-3 p-3 h-100">
                                <h4 class="mb-3">{{ translate('Customer_information') }}</h4>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Customer') }}</label>
                                            <select name="customer_id" class="form-control js-select" id="customer-select" required>
                                                <option value="">{{ translate('Select_Customer') }}</option>
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}"
                                                        {{ (old('customer_id', request('customer_id')) == $customer->id || ($customers->count() === 1 && $loop->first)) ? 'selected' : '' }}>
                                                        {{ $customer->first_name }} {{ $customer->last_name }} - {{ $customer->phone }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('customer_id')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-primary w-100" id="open-add-customer">
                                                {{ translate('Add_New') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $bookingCanEditCustomerAddress = auth()->user()->can('customer_add') || auth()->user()->can('customer_update');
                                @endphp
                                <div class="row">
                                    <div class="col-md-{{ $bookingCanEditCustomerAddress ? 8 : 10 }}">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Service_Address') }}</label>
                                            <select name="service_address_id" id="customer-address-select" class="form-control" disabled>
                                                <option value="">{{ translate('Select_Address') }}</option>
                                            </select>
                                            @error('service_address_id')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-primary w-100" id="open-add-address" disabled>
                                                {{ translate('Add_Address') }}
                                            </button>
                                        </div>
                                    </div>
                                    @if($bookingCanEditCustomerAddress)
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-secondary w-100" id="open-edit-address" disabled>
                                                {{ translate('Edit_Address') }}
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <div id="booking-customer-info-alert" class="d-none mt-2">
                                    <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                                        <div class="media gap-2 flex-grow-1">
                                            <img src="{{ asset('assets/admin-module/img/WarningOctagon.svg') }}" class="svg" alt="">
                                            <div class="media-body" id="booking-customer-info-alert-body"></div>
                                        </div>
                                        <button type="button" class="btn-close shadow-none booking-customer-info-alert-close" aria-label="{{ translate('Close') }}"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg booking-create-source-col">
                            <div class="border rounded-3 p-3 h-100">
                                <h4 class="mb-3">{{ translate('Source_and_Assignee') }}</h4>
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Booking_Source') }}</label>
                                    <select name="booking_source" class="form-control" required>
                                        <option value="">{{ translate('Select_Booking_Source') }}</option>
                                        @foreach($sources as $source)
                                            @php
                                                $value = $source->name;
                                                $selected = old('booking_source', request('booking_source')) == $value ? 'selected' : '';
                                            @endphp
                                            <option value="{{ $value }}" {{ $selected }}>
                                                {{ $source->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('booking_source')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">{{ translate('Assignee') }}</label>
                                    <select name="assignee_id" id="assignee-select" class="form-control js-select">
                                        @if(isset($currentAdmin))
                                            <option value="{{ $currentAdmin->id }}"
                                                {{ (old('assignee_id', request('assignee_id', $currentAdmin->id)) == $currentAdmin->id) ? 'selected' : '' }}>
                                                {{ translate('Assign_to_me') }}
                                                ({{ $currentAdmin->first_name }} {{ $currentAdmin->last_name }}
                                                - {{ $currentAdmin->email ?? $currentAdmin->phone }})
                                            </option>
                                        @endif
                                        @foreach($assignees as $assignee)
                                            @if(!isset($currentAdmin) || $assignee->id !== $currentAdmin->id)
                                                <option value="{{ $assignee->id }}"
                                                    {{ old('assignee_id', request('assignee_id')) == $assignee->id ? 'selected' : '' }}>
                                                    {{ $assignee->first_name }} {{ $assignee->last_name }}
                                                    ({{ $assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }})
                                                    - {{ $assignee->email ?? $assignee->phone }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        {{ translate('Select_an_admin_or_employee_responsible_for_this_booking_or_leave_unassigned') }}.
                                    </small>
                                    @error('assignee_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Service (zone + category only; subcategory / service / variant filled via Booking Summary modal) --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Service_information') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Zone') }}</label>
                                    <select name="zone_id" id="service-zone-select" class="form-control js-select" required>
                                        <option value="">{{ translate('Select_Zone') }}</option>
                                        @foreach($zoneTreeOptions as $zOpt)
                                            <option value="{{ $zOpt['id'] }}"
                                                {{ (old('zone_id', request('zone_id')) == $zOpt['id'] || (count($zoneTreeOptions) === 1 && $loop->first)) ? 'selected' : '' }}>
                                                {{ $zOpt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('zone_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label">{{ translate('Category') }}</label>
                                    <select name="category_id" id="service-category-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select_Category') }}</option>
                                    </select>
                                    <p class="small text-muted mt-2 mb-0">{{ translate('Add_services_from_Booking_Summary_using_Add_service') }}</p>
                                    @error('category_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div id="booking-create-hidden-service-fields" class="d-none" aria-hidden="true">
                            <select name="sub_category_id" id="service-subcategory-select" class="form-control js-select" disabled>
                                <option value="">{{ translate('Select_Sub_Category') }}</option>
                            </select>
                            <select name="service_id" id="service-select" class="form-control js-select" disabled>
                                <option value="">{{ translate('Select_Service') }}</option>
                            </select>
                            <select name="variant_key" id="service-variant-select" class="form-control js-select" disabled>
                                <option value="">{{ translate('Select Service Variant') }}</option>
                            </select>
                            @error('sub_category_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                            @error('service_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                            @error('variant_key')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div id="booking-service-info-alert" class="d-none mt-2">
                            <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                                <div class="media gap-2 flex-grow-1">
                                    <img src="{{ asset('assets/admin-module/img/WarningOctagon.svg') }}" class="svg" alt="">
                                    <div class="media-body" id="booking-service-info-alert-body"></div>
                                </div>
                                <button type="button" class="btn-close shadow-none booking-service-info-alert-close" aria-label="{{ translate('Close') }}"></button>
                            </div>
                        </div>
                    </div>

                    {{-- Provider (70%) + Date & Time / Service Schedule (30%) --}}
                    <div class="row g-3 mb-4 align-items-stretch booking-create-provider-schedule-row">
                        <div class="col-12 col-lg booking-create-provider-col">
                            <div class="border rounded-3 p-3 h-100">
                                <h4 class="mb-3">{{ translate('Provider_information') }}</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Provider') }}</label>
                                            <select name="provider_id" id="provider-select" class="form-control js-select" required disabled>
                                                <option value="">{{ translate('Select_Provider') }}</option>
                                            </select>
                                            <small class="text-muted d-block mt-1">
                                                {{ translate('Shows_only_subscribed_providers_with_Company_Name_Contact_Person_Name_and_Contact_Person_Phone') }}.
                                            </small>
                                            @error('provider_id')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Service Location Radio --}}
                                <div class="row" id="service-location-section" style="display: none;">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Where_Service_will_be_Provided') }}</label>
                                            <div class="d-flex gap-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="service_location_type" id="service-location-customer" value="customer" checked>
                                                    <label class="form-check-label" for="service-location-customer">
                                                        {{ translate('Customer_Location') }}
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="service_location_type" id="service-location-provider" value="provider">
                                                    <label class="form-check-label" for="service-location-provider">
                                                        {{ translate('Provider_Location') }}
                                                    </label>
                                                </div>
                                            </div>
                                            <input type="hidden" name="service_location" id="service-location-hidden" value="customer">
                                            @error('service_location')
                                            <span class="text-danger d-block mt-1">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg booking-create-schedule-col">
                            <div class="border rounded-3 p-3 h-100">
                                <h4 class="mb-3">{{ translate('Date_&_Time') }}</h4>
                                <div class="mb-0">
                                    <label class="form-label">{{ translate('Service_Schedule') }}</label>
                                    <input type="datetime-local" name="service_schedule" class="form-control"
                                           value="{{ old('service_schedule', request('service_schedule')) }}" required>
                                    @error('service_schedule')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('bookingmodule::admin.booking.partials.create._booking-summary-cart')

                    <div class="mb-4 border rounded-3 p-3" id="service-additional-details-section">
                        <h4 class="mb-3">{{ translate('Service_Additional_Details_(Optional)') }}</h4>
                        <div class="mb-0">
                            <textarea name="service_description" id="service-description-field" class="form-control" rows="3"
                                      placeholder="{{ translate('Add_any_extra_information_or_requirements_for_this_service') }}"
                                      aria-label="{{ translate('Service_Additional_Details_(Optional)') }}">{{ old('service_description', request('service_description')) }}</textarea>
                            @error('service_description')
                            <span class="text-danger d-block mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    @php
                        $adminAdvancePaymentMethodFieldConfig = \Modules\BookingModule\Services\AdminCompanyInflowPaymentService::fieldConfigMapFromGroups($advancePaymentMethodGroups ?? []);
                        $advancePmSelected = (string) old('advance_payment_method', request('advance_payment_method', ''));
                        $advancePmDisabled = (float) old('advance_paid_amount', request('advance_paid_amount', 0)) <= 0;
                    @endphp
                    <div class="mb-4 border rounded-3 p-3" id="advance-payment-section">
                        <h4 class="mb-3">{{ translate('Advance_Payment') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Advance_Paid_Amount') }}</label>
                                    <input type="number" step="0.01" min="0" name="advance_paid_amount" id="advance-paid-amount"
                                           class="form-control" value="{{ old('advance_paid_amount', request('advance_paid_amount')) }}"
                                           placeholder="0">
                                    @error('advance_paid_amount')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div id="due-balance-row" class="small mt-1" style="display: none;">
                                        <strong>{{ translate('Due_Balance') }}:</strong> <span id="due-balance-amount">0</span>
                                    </div>
                                </div>
                            </div>
                            @include('bookingmodule::admin.booking.partials._admin-company-inflow-payment-method', [
                                'instanceId' => 'booking-create',
                                'advancePaymentMethodGroups' => $advancePaymentMethodGroups ?? [],
                                'advancePmDisabled' => $advancePmDisabled,
                                'advancePmSelected' => $advancePmSelected,
                            ])
                        </div>
                        <p class="text-muted mb-0 small" id="advance-payment-help-no-advance">
                            {{ translate('Payment_method_will_be_set_as_Cash_After_Service_and_final_payment_will_be_taken_from_customer_at_completion.') }}
                        </p>
                        <p class="text-muted mb-0 small d-none" id="advance-payment-help-with-advance">
                            {{ translate('Advance_payment_method_help') }}
                        </p>
                    </div>

                    {{-- Payment summary (totals) --}}
                    <div class="mb-4 border rounded-3 p-3" id="payment-section">
                        <h4 class="mb-3">{{ translate('Payment_information') }}</h4>

                        {{-- Total billing (cart + extras + additional charges) --}}
                        <div id="billing-summary-box" class="mb-0 p-3 bg-light rounded" style="display: none;">
                            <h5 class="mb-3">{{ translate('Total_Billing') }}</h5>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                <tr>
                                    <td class="text-wrap">{{ translate('Total_service_charges') }}</td>
                                    <td class="text-end text-nowrap" id="billing-total-service-charges">—</td>
                                </tr>
                                <tr id="billing-spare-part-charges-row" class="d-none">
                                    <td class="text-wrap">{{ translate('Total_spare_part_charges') }}</td>
                                    <td class="text-end text-nowrap" id="billing-total-spare-part-charges">—</td>
                                </tr>
                                </tbody>
                                <tbody id="billing-additional-charges-rows"></tbody>
                                <tbody>
                                <tr class="border-top">
                                    <td class="text-wrap"><strong>{{ translate('Additional_charges_total') }}</strong></td>
                                    <td class="text-end text-nowrap" id="billing-additional-charges-sum">—</td>
                                </tr>
                                <tr id="billing-tax-summary-row" class="d-none">
                                    <td class="text-wrap small text-muted">{{ company_default_tax_label() }}</td>
                                    <td class="text-end text-nowrap small text-muted" id="billing-tax-sum">—</td>
                                </tr>
                                <tr>
                                    <td class="text-wrap">{{ translate('Total_discount') }}</td>
                                    <td class="text-end text-nowrap" id="billing-discount-total">—</td>
                                </tr>
                                <tr class="fw-bold fs-6">
                                    <td class="text-wrap">{{ translate('Total_charges') }}</td>
                                    <td class="text-end text-nowrap c1" id="billing-grand-total">—</td>
                                </tr>
                                <tr>
                                    <td class="text-wrap">{{ translate('Paid') }}</td>
                                    <td class="text-end text-nowrap" id="billing-paid-display">—</td>
                                </tr>
                                <tr class="fw-bold">
                                    <td class="text-wrap">{{ translate('Due_Balance') }}</td>
                                    <td class="text-end text-nowrap" id="billing-due-display">—</td>
                                </tr>
                                <tr class="billing-commission-preview-row d-none">
                                    <td class="text-wrap">{{ translate('Company_commission') }}</td>
                                    <td class="text-end text-nowrap" id="billing-company-commission">—</td>
                                </tr>
                                <tr class="billing-commission-preview-row d-none">
                                    <td class="text-wrap">{{ translate('Provider_commission') }}</td>
                                    <td class="text-end text-nowrap" id="billing-provider-commission">—</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="booking-form-validation-alert" class="d-none mt-3">
                        <div class="alert alert-danger d-flex align-items-start mb-0" role="alert">
                            <div class="media gap-2 flex-grow-1">
                                <img src="{{ asset('assets/admin-module/img/WarningOctagon.svg') }}" class="svg mt-1" alt="">
                                <div class="media-body" id="booking-form-validation-alert-body"></div>
                            </div>
                            <button type="button" class="btn-close shadow-none booking-form-validation-alert-close" aria-label="{{ translate('Close') }}"></button>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            {{ translate('Continue_to_Preview') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add New Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">{{ translate('Add_New_Customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="quick-customer-modal-alert" class="d-none mb-3">
                        <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                            <div class="media gap-2 flex-grow-1">
                                <img src="{{ asset('assets/admin-module/img/WarningOctagon.svg') }}" class="svg" alt="">
                                <div class="media-body" id="quick-customer-modal-alert-body"></div>
                            </div>
                            <button type="button" class="btn-close shadow-none quick-customer-modal-alert-close" aria-label="{{ translate('Close') }}"></button>
                        </div>
                    </div>
                    <form id="quick-customer-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('first_name') }}</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('last_name') }}</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('email') }} ({{ translate('Optional') }})</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('phone') }}</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <p class="text-muted mb-0">
                            {{ translate('A_default_password_will_be_set_for_this_customer_and_they_can_change_it_later.') }}
                        </p>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="save-customer-btn">{{ translate('Save_Customer') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">{{ translate('Add_Customer_Address') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div id="quick-address-modal-alert" class="d-none mb-3">
                        <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                            <div class="media gap-2 flex-grow-1">
                                <img src="{{ asset('assets/admin-module/img/WarningOctagon.svg') }}" class="svg" alt="">
                                <div class="media-body" id="quick-address-modal-alert-body"></div>
                            </div>
                            <button type="button" class="btn-close shadow-none quick-address-modal-alert-close" aria-label="{{ translate('Close') }}"></button>
                        </div>
                    </div>
                    <form id="quick-address-form">
                        @csrf
                        <input type="hidden" id="quick-address-edit-id" value="">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Address') }}</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ translate('Address_Label') }}</label>
                            <input type="text" name="address_label" class="form-control" placeholder="{{ translate('Home/Office/Others') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ translate('Landmark') }} ({{ translate('Optional') }})</label>
                            <input type="text" name="landmark" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('lat') }} ({{ translate('Optional') }})</label>
                                    <input type="text" name="lat" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('long') }} ({{ translate('Optional') }})</label>
                                    <input type="text" name="lon" class="form-control">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="save-address-btn">{{ translate('Save_Address') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{asset('assets/admin-module')}}/plugins/select2/select2.min.js"></script>
    <script>
        "use strict";
        $(document).ready(function () {
            $('.js-select').select2();

            function renderBookingCustomerAlert(messages) {
                var $wrap = $('#booking-customer-info-alert');
                var $body = $('#booking-customer-info-alert-body');
                var list = Array.isArray(messages) ? messages : (messages ? [messages] : []);
                if (list.length === 0) {
                    $wrap.addClass('d-none');
                    $body.empty();
                    return;
                }
                $body.html(list.map(function (text) {
                    return '<p class="mb-1 mb-md-0">' + $('<div/>').text(text).html() + '</p>';
                }).join(''));
                $wrap.removeClass('d-none');
                var el = $wrap[0];
                if (el && el.scrollIntoView) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            function resetBookingCustomerAlert() {
                $('#booking-customer-info-alert').addClass('d-none');
                $('#booking-customer-info-alert-body').empty();
            }

            $('#booking-customer-info-alert').on('click', '.booking-customer-info-alert-close', function () {
                resetBookingCustomerAlert();
            });

            function renderBookingFormValidationAlert(intro, messages) {
                var $wrap = $('#booking-form-validation-alert');
                var $body = $('#booking-form-validation-alert-body');
                var list = Array.isArray(messages) ? messages : [];
                if (list.length === 0) {
                    $wrap.addClass('d-none');
                    $body.empty();
                    return;
                }
                var introHtml = '<p class="fw-semibold mb-2">' + $('<div/>').text(intro || '').html() + '</p>';
                var listHtml = '<ul class="mb-0 ps-3">' + list.map(function (text) {
                    return '<li>' + $('<div/>').text(text).html() + '</li>';
                }).join('') + '</ul>';
                $body.html(introHtml + listHtml);
                $wrap.removeClass('d-none');
                var el = $wrap[0];
                if (el && el.scrollIntoView) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            function resetBookingFormValidationAlert() {
                $('#booking-form-validation-alert').addClass('d-none');
                $('#booking-form-validation-alert-body').empty();
            }

            $('#booking-form-validation-alert').on('click', '.booking-form-validation-alert-close', function () {
                resetBookingFormValidationAlert();
            });

            function renderQuickModalAlert(wrapId, bodyId, messages) {
                var $wrap = $(wrapId);
                var $body = $(bodyId);
                var list = Array.isArray(messages) ? messages : (messages ? [messages] : []);
                if (list.length === 0) {
                    $wrap.addClass('d-none');
                    $body.empty();
                    return;
                }
                $body.html(list.map(function (text) {
                    return '<p class="mb-1 mb-md-0">' + $('<div/>').text(text).html() + '</p>';
                }).join(''));
                $wrap.removeClass('d-none');
            }

            function resetQuickCustomerModalAlert() {
                renderQuickModalAlert('#quick-customer-modal-alert', '#quick-customer-modal-alert-body', []);
            }

            function resetQuickAddressModalAlert() {
                renderQuickModalAlert('#quick-address-modal-alert', '#quick-address-modal-alert-body', []);
            }

            $('#quick-customer-modal-alert').on('click', '.quick-customer-modal-alert-close', function () {
                resetQuickCustomerModalAlert();
            });
            $('#quick-address-modal-alert').on('click', '.quick-address-modal-alert-close', function () {
                resetQuickAddressModalAlert();
            });

            $('#addCustomerModal').on('show.bs.modal', function () {
                resetQuickCustomerModalAlert();
            });
            $('#addAddressModal').on('show.bs.modal', function () {
                resetQuickAddressModalAlert();
            });
            $('#addAddressModal').on('hidden.bs.modal', function () {
                const $f = $('#quick-address-form')[0];
                if ($f) {
                    $f.reset();
                }
                $('#quick-address-edit-id').val('');
                $('#addAddressModalLabel').text('{{ translate('Add_Customer_Address') }}');
                resetQuickAddressModalAlert();
            });

            const $customerSelect = $('#customer-select');
            const $addressSelect = $('#customer-address-select');
            const $addAddressBtn = $('#open-add-address');
            const $editAddressBtn = $('#open-edit-address');
            const $serviceLocationHidden = $('#service-location-hidden');
            const $addressRow = $('#customer-address-select').closest('.row');
            const $quickAddressForm = $('#quick-address-form');

            function syncEditAddressButtonState() {
                if (!$editAddressBtn.length) {
                    return;
                }
                if ($addressSelect.prop('disabled') || !$addressSelect.val()) {
                    $editAddressBtn.prop('disabled', true);
                } else {
                    $editAddressBtn.prop('disabled', false);
                }
            }

            // Function to enable/disable address controls
            function toggleAddressControls(enabled) {
                $addressSelect.prop('disabled', !enabled);
                $addAddressBtn.prop('disabled', !enabled);
                if (!enabled && $editAddressBtn.length) {
                    $editAddressBtn.prop('disabled', true);
                } else {
                    syncEditAddressButtonState();
                }
            }

            // Initially disable address controls
            toggleAddressControls(false);

            $('#open-add-customer').on('click', function () {
                $('#addCustomerModal').modal('show');
            });

            // Load addresses when customer changes
            $customerSelect.on('change', function () {
                const customerId = $(this).val();
                const locationType = $serviceLocationHidden.val() || 'customer';
                
                $addressSelect.empty().append(
                    new Option('{{ translate('Select_Address') }}', '', true, true)
                );

                if (!customerId) {
                    toggleAddressControls(false);
                    $addressRow.hide();
                    return;
                }

                // Only enable address controls if service location is customer
                if (locationType === 'customer') {
                    toggleAddressControls(true);
                    $addressRow.show();
                    
                    let route = '{{ route('admin.customer.addresses', ['id' => ':id']) }}';
                    route = route.replace(':id', customerId);

                    $.get(route, function (addresses) {
                        addresses.forEach(function (addr) {
                            const text = (addr.address_label ? addr.address_label + ' - ' : '') + addr.address;
                            $addressSelect.append(new Option(text, addr.id, false, false));
                        });
                        $addressSelect.trigger('change');
                    }).fail(function() {
                        // If no addresses found, still enable the controls
                        toggleAddressControls(true);
                    }).always(function () {
                        syncEditAddressButtonState();
                    });
                } else {
                    // Provider location - hide address fields
                    toggleAddressControls(false);
                    $addressRow.hide();
                }
            });

            $addressSelect.on('change', function () {
                syncEditAddressButtonState();
            });

            $('#open-add-address').on('click', function () {
                if (!$customerSelect.val()) {
                    renderBookingCustomerAlert('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                resetBookingCustomerAlert();
                $('#quick-address-edit-id').val('');
                $('#addAddressModalLabel').text('{{ translate('Add_Customer_Address') }}');
                if ($quickAddressForm[0]) {
                    $quickAddressForm[0].reset();
                }
                $('#addAddressModal').modal('show');
            });

            $editAddressBtn.on('click', function () {
                if (!$editAddressBtn.length) {
                    return;
                }
                const customerId = $customerSelect.val();
                const addressId = $addressSelect.val();
                if (!customerId) {
                    renderBookingCustomerAlert('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                if (!addressId) {
                    renderBookingCustomerAlert('{{ translate('Select_Address') }}');
                    return;
                }
                resetBookingCustomerAlert();
                let showUrl = '{{ route('admin.customer.address-quick-show', ['id' => '__CID__', 'addressId' => '__AID__']) }}';
                showUrl = showUrl.replace('__CID__', encodeURIComponent(customerId)).replace('__AID__', encodeURIComponent(addressId));
                $.get(showUrl, function (addr) {
                    $('#quick-address-edit-id').val(addr.id);
                    $('#addAddressModalLabel').text('{{ translate('Edit_Address') }}');
                    $quickAddressForm.find('[name="address"]').val(addr.address || '');
                    $quickAddressForm.find('[name="address_label"]').val(addr.address_label || '');
                    $quickAddressForm.find('[name="landmark"]').val(addr.landmark || '');
                    $quickAddressForm.find('[name="lat"]').val(addr.lat || '');
                    $quickAddressForm.find('[name="lon"]').val(addr.lon || '');
                    $('#addAddressModal').modal('show');
                }).fail(function (xhr) {
                    if (xhr.status === 404) {
                        renderBookingCustomerAlert('{{ translate('not_found') }}');
                    } else {
                        renderBookingCustomerAlert('{{ translate('Something_went_wrong') }}');
                    }
                });
            });

            $('#save-customer-btn').on('click', function () {
                const $form = $('#quick-customer-form');
                const formData = $form.serialize();
                resetQuickCustomerModalAlert();

                $.ajax({
                    url: '{{ route('admin.customer.quick-store') }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        resetBookingCustomerAlert();
                        resetQuickCustomerModalAlert();
                        const newOption = new Option(
                            response.name + ' - ' + response.phone,
                            response.id,
                            true,
                            true
                        );
                        $customerSelect.append(newOption).trigger('change');
                        // Address controls will be enabled by the change event handler
                        $('#addCustomerModal').modal('hide');
                        $form[0].reset();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            let messages = [];
                            Object.values(xhr.responseJSON.errors).forEach(function (errs) {
                                messages = messages.concat(errs);
                            });
                            renderQuickModalAlert('#quick-customer-modal-alert', '#quick-customer-modal-alert-body', messages);
                        } else {
                            renderQuickModalAlert('#quick-customer-modal-alert', '#quick-customer-modal-alert-body', '{{ translate('Something_went_wrong') }}');
                        }
                    }
                });
            });

            $('#save-address-btn').on('click', function () {
                const $form = $('#quick-address-form');
                const customerId = $customerSelect.val();
                const editId = $('#quick-address-edit-id').val();
                resetQuickAddressModalAlert();
                if (!customerId) {
                    renderBookingCustomerAlert('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }

                let route;
                let payload;
                if (editId) {
                    route = '{{ route('admin.customer.address-quick-update', ['id' => '__CID__', 'addressId' => '__AID__']) }}';
                    route = route.replace('__CID__', encodeURIComponent(customerId)).replace('__AID__', encodeURIComponent(editId));
                    payload = $form.serialize() + '&_method=PUT';
                } else {
                    route = '{{ route('admin.customer.address-quick-store', ['id' => ':id']) }}';
                    route = route.replace(':id', customerId);
                    payload = $form.serialize();
                }

                $.ajax({
                    url: route,
                    method: 'POST',
                    data: payload,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        resetBookingCustomerAlert();
                        resetQuickAddressModalAlert();
                        if (editId) {
                            const summaryLine = (response.label ? response.label + ' - ' : '') +
                                ($form.find('textarea[name="address"]').val() || '');
                            const $opt = $addressSelect.find('option[value="' + response.id + '"]');
                            if ($opt.length) {
                                $opt.text(summaryLine);
                            }
                            $addressSelect.val(String(response.id)).trigger('change');
                        } else {
                            const option = new Option(
                                response.label + ' - ' + response.full_address,
                                response.id,
                                true,
                                true
                            );
                            $addressSelect.append(option).trigger('change');
                        }
                        $('#addAddressModal').modal('hide');
                        $form[0].reset();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            let messages = [];
                            Object.values(xhr.responseJSON.errors).forEach(function (errs) {
                                messages = messages.concat(errs);
                            });
                            renderQuickModalAlert('#quick-address-modal-alert', '#quick-address-modal-alert-body', messages);
                        } else {
                            renderQuickModalAlert('#quick-address-modal-alert', '#quick-address-modal-alert-body', '{{ translate('Something_went_wrong') }}');
                        }
                    }
                });
            });

            // Service section cascading dropdowns
            const $zoneSelect = $('#service-zone-select');
            const $categorySelect = $('#service-category-select');
            const $subCategorySelect = $('#service-subcategory-select');
            const $serviceSelect = $('#service-select');
            const $variantSelect = $('#service-variant-select');

            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                const results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }

            const oldValues = {
                customer_id: @json(old('customer_id', request('customer_id'))) || getUrlParameter('customer_id'),
                zone_id: @json(old('zone_id', request('zone_id'))) || getUrlParameter('zone_id'),
                category_id: @json(old('category_id', request('category_id'))) || getUrlParameter('category_id'),
                sub_category_id: @json(old('sub_category_id', request('sub_category_id'))) || getUrlParameter('sub_category_id'),
                service_id: @json(old('service_id', request('service_id'))) || getUrlParameter('service_id'),
                variant_key: @json(old('variant_key', request('variant_key'))) || getUrlParameter('variant_key'),
                provider_id: @json(old('provider_id', request('provider_id'))) || getUrlParameter('provider_id'),
                service_address_id: @json(old('service_address_id', request('service_address_id'))) || getUrlParameter('service_address_id'),
                service_location: @json(old('service_location', request('service_location'))) || getUrlParameter('service_location') || 'customer',
                service_schedule: @json(old('service_schedule', request('service_schedule'))) || getUrlParameter('service_schedule'),
                advance_paid_amount: @json(old('advance_paid_amount', request('advance_paid_amount'))),
                advance_payment_method: @json(old('advance_payment_method', request('advance_payment_method'))),
                advance_transaction_id: @json(old('advance_transaction_id', request('advance_transaction_id'))),
                advance_method_fields: @json(old('advance_method_fields', request('advance_method_fields', [])) ?? []),
                assignee_id: @json(old('assignee_id', request('assignee_id'))) || getUrlParameter('assignee_id'),
                booking_source: @json(old('booking_source', request('booking_source')))
            };

            var adminAdvanceMethodConfig = @json($adminAdvancePaymentMethodFieldConfig ?? []);

            function getAdvanceDynamicFieldInitialValue(f, useInitial) {
                if (!useInitial) {
                    return '';
                }
                if (f.input_name === 'advance_transaction_id') {
                    return oldValues.advance_transaction_id != null ? String(oldValues.advance_transaction_id) : '';
                }
                var m = String(f.input_name || '').match(/^advance_method_fields\[([^\]]+)\]$/);
                if (m && oldValues.advance_method_fields && typeof oldValues.advance_method_fields === 'object') {
                    var v = oldValues.advance_method_fields[m[1]];
                    return v != null ? String(v) : '';
                }
                return '';
            }

            function renderAdminAdvancePaymentDynamicFields(selectedKey, opts) {
                opts = opts || {};
                var useInitial = !!opts.useInitial;
                var $box = $('#advance-payment-section .pk-apm-dynamic-fields');
                if (!$box.length) {
                    return;
                }
                $box.empty();
                if (!selectedKey || !adminAdvanceMethodConfig[selectedKey]) {
                    return;
                }
                var cfg = adminAdvanceMethodConfig[selectedKey];
                var fields = cfg.fields || [];
                fields.forEach(function (f) {
                    var fid = 'advance-dyn-' + String(f.name || '').replace(/[^a-zA-Z0-9_-]/g, '_');
                    var val = getAdvanceDynamicFieldInitialValue(f, useInitial);
                    var $col = $('<div class="col-md-6"></div>');
                    var $grp = $('<div class="mb-0"></div>');
                    var req = !!f.required;
                    var $label = $('<label class="form-label" for="' + fid + '"></label>').text(f.label || '');
                    if (req) {
                        $label.append(' <span class="text-danger">*</span>');
                    }
                    var $input = $('<input type="text" class="form-control" autocomplete="off">')
                        .attr('id', fid)
                        .attr('name', f.input_name || '')
                        .attr('placeholder', f.placeholder || '')
                        .val(val);
                    if (req) {
                        $input.attr('required', 'required');
                    }
                    $grp.append($label).append($input);
                    $col.append($grp);
                    $box.append($col);
                });
            }

            function advancePmApplyTier2Visibility() {
                var t1 = $('#advance-payment-section .pk-apm-tier1:checked').val();
                $('#advance-payment-section .pk-apm-tier2-digital-wrap').toggleClass('d-none', t1 !== 'digital');
                $('#advance-payment-section .pk-apm-tier2-offline-wrap').toggleClass('d-none', t1 !== 'offline');
            }

            /** Updates hidden advance_payment_method only — does not re-render dynamic fields (avoids wiping typed values). */
            function advancePmUpdateHiddenOnly() {
                var t1 = $('#advance-payment-section .pk-apm-tier1:checked').val();
                var $h = $('#advance-payment-section .pk-apm-hidden');
                var v = '';
                if (t1 === 'cas') {
                    v = 'cash_after_service';
                } else if (t1 === 'digital') {
                    v = ($('#advance-payment-section .pk-apm-tier2-digital:checked').val() || '').trim();
                } else if (t1 === 'offline') {
                    v = ($('#advance-payment-section .pk-apm-tier2-offline:checked').val() || '').trim();
                }
                $h.val(v);
            }

            function advancePmSyncFinalValue(opts) {
                opts = opts || {};
                var hydrate = !!opts.hydrateFromInitial;
                advancePmUpdateHiddenOnly();
                var v = ($('#advance-payment-section .pk-apm-hidden').val() || '').trim();
                if (typeof renderAdminAdvancePaymentDynamicFields === 'function') {
                    renderAdminAdvancePaymentDynamicFields(v, { useInitial: hydrate });
                }
            }

            $('#advance-payment-section').on('change', '.pk-apm-tier1', function (e, payload) {
                var hydrate = payload && payload.hydrateFromInitial;
                if (!hydrate) {
                    $('#advance-payment-section .pk-apm-tier2-digital').prop('checked', false);
                    $('#advance-payment-section .pk-apm-tier2-offline').prop('checked', false);
                }
                var t1 = $('#advance-payment-section .pk-apm-tier1:checked').val();
                advancePmApplyTier2Visibility();
                if (!hydrate && t1 === 'digital' && $('#advance-payment-section .pk-apm-tier2-digital').length === 1) {
                    $('#advance-payment-section .pk-apm-tier2-digital').first().prop('checked', true);
                }
                if (!hydrate && t1 === 'offline' && $('#advance-payment-section .pk-apm-tier2-offline').length === 1) {
                    $('#advance-payment-section .pk-apm-tier2-offline').first().prop('checked', true);
                }
                advancePmSyncFinalValue({ hydrateFromInitial: !!hydrate });
            });

            $('#advance-payment-section').on('change', '.pk-apm-tier2-digital, .pk-apm-tier2-offline', function (e, payload) {
                var hydrate = payload && payload.hydrateFromInitial;
                advancePmSyncFinalValue({ hydrateFromInitial: !!hydrate });
            });

            setTimeout(function () {
                var adv0 = parseFloat($('#advance-paid-amount').val()) || 0;
                var hv = ($('#advance-payment-section .pk-apm-hidden').val() || '').trim();
                advancePmApplyTier2Visibility();
                if (adv0 > 0 && hv && $('#advance-payment-section .pk-apm-dynamic-fields').children().length === 0) {
                    renderAdminAdvancePaymentDynamicFields(hv, { useInitial: true });
                }
            }, 0);

            // --- Admin create booking: multi-line cart + extras (booking summary) ---
            var bookingCreateCart = { lines: [], extras: [] };
            var lastCartSummaryContent = null;
            var cartSummaryRoute = '{{ route('admin.booking.service.ajax-create-booking-cart-summary') }}';

            function parseBookingCreateCartInitial() {
                var j = ($('#booking-create-cart-json').val() || '').trim();
                if (j) {
                    try {
                        var parsed = JSON.parse(j);
                        if (Array.isArray(parsed)) {
                            bookingCreateCart.lines = parsed.map(function (r) {
                                var up = r.unit_price;
                                var upNum = (up != null && up !== '' && !isNaN(parseFloat(up))) ? parseFloat(up) : null;
                                return {
                                    service_id: String(r.service_id || ''),
                                    variant_key: String(r.variant_key || ''),
                                    quantity: Math.max(1, parseInt(r.quantity, 10) || 1),
                                    service_name: r.service_name || '',
                                    variant_label: r.variant_label || '',
                                    category_id: r.category_id ? String(r.category_id) : null,
                                    sub_category_id: r.sub_category_id ? String(r.sub_category_id) : null,
                                    unit_price: upNum,
                                    line_discount: Math.max(0, parseFloat(r.line_discount) || 0),
                                    line_discount_cost_bearer: (function (b) {
                                        var v = String(b || 'none').toLowerCase();
                                        return (v === 'admin' || v === 'provider' || v === 'none' || v === 'both') ? v : 'none';
                                    })(r.line_discount_cost_bearer),
                                    catalog_unit_price: (r.catalog_unit_price != null && r.catalog_unit_price !== '' && !isNaN(parseFloat(r.catalog_unit_price)))
                                        ? parseFloat(r.catalog_unit_price) : null
                                };
                            }).filter(function (r) {
                                return r.service_id && r.variant_key;
                            });
                        }
                    } catch (e) { /* ignore */ }
                }
                var ej = ($('#booking-create-extra-services-json').val() || '').trim();
                if (ej) {
                    try {
                        var ep = JSON.parse(ej);
                        if (Array.isArray(ep)) {
                            bookingCreateCart.extras = ep;
                        }
                    } catch (e2) { /* ignore */ }
                }
            }

            function persistBookingCreateCart() {
                $('#booking-create-cart-json').val(JSON.stringify(bookingCreateCart.lines));
                $('#booking-create-extra-services-json').val(
                    bookingCreateCart.extras.length ? JSON.stringify(bookingCreateCart.extras) : ''
                );
                var q = bookingCreateCart.lines[0] ? bookingCreateCart.lines[0].quantity : 1;
                $('#service-quantity-field').val(q);
            }

            function syncBookingCreateCartLineZeroFromMainForm() {
                var sid = $serviceSelect.val();
                var vk = $variantSelect.val();
                var qty = Math.max(1, parseInt($('#service-quantity-field').val(), 10) || 1);
                if (!sid || !vk) {
                    return;
                }
                var sn = ($serviceSelect.find('option:selected').text() || '').trim();
                var vn = ($variantSelect.find('option:selected').text() || '').trim();
                var prev = bookingCreateCart.lines[0] ? {
                    service_id: String(bookingCreateCart.lines[0].service_id || ''),
                    variant_key: String(bookingCreateCart.lines[0].variant_key || ''),
                    unit_price: bookingCreateCart.lines[0].unit_price,
                    line_discount: bookingCreateCart.lines[0].line_discount,
                    line_discount_cost_bearer: bookingCreateCart.lines[0].line_discount_cost_bearer,
                    catalog_unit_price: bookingCreateCart.lines[0].catalog_unit_price
                } : null;
                var sameVariant = prev && prev.service_id === String(sid) && prev.variant_key === String(vk);
                if (!bookingCreateCart.lines.length) {
                    var catNew = parseFloat($variantSelect.find('option:selected').attr('data-catalog-price')) || 0;
                    bookingCreateCart.lines.push({
                        service_id: String(sid),
                        variant_key: String(vk),
                        quantity: qty,
                        service_name: sn,
                        variant_label: vn,
                        category_id: $categorySelect.val() || null,
                        sub_category_id: $subCategorySelect.val() || null,
                        unit_price: null,
                        line_discount: 0,
                        catalog_unit_price: catNew > 0 ? catNew : null,
                        line_discount_cost_bearer: 'none'
                    });
                    return;
                }
                bookingCreateCart.lines[0].service_id = String(sid);
                bookingCreateCart.lines[0].variant_key = String(vk);
                bookingCreateCart.lines[0].quantity = qty;
                bookingCreateCart.lines[0].service_name = sn;
                bookingCreateCart.lines[0].variant_label = vn;
                bookingCreateCart.lines[0].category_id = $categorySelect.val() || null;
                bookingCreateCart.lines[0].sub_category_id = $subCategorySelect.val() || null;
                if (sameVariant) {
                    bookingCreateCart.lines[0].unit_price = prev.unit_price;
                    bookingCreateCart.lines[0].line_discount = prev.line_discount;
                    bookingCreateCart.lines[0].line_discount_cost_bearer = prev.line_discount_cost_bearer || 'none';
                    bookingCreateCart.lines[0].catalog_unit_price = prev.catalog_unit_price;
                } else {
                    bookingCreateCart.lines[0].unit_price = null;
                    bookingCreateCart.lines[0].line_discount = 0;
                    bookingCreateCart.lines[0].line_discount_cost_bearer = 'none';
                    var catSw = parseFloat($variantSelect.find('option:selected').attr('data-catalog-price')) || 0;
                    bookingCreateCart.lines[0].catalog_unit_price = catSw > 0 ? catSw : null;
                }
            }

            function syncMainFormFromBookingCreateCartLineZero() {
                if (!bookingCreateCart.lines.length) {
                    return;
                }
                var l0 = bookingCreateCart.lines[0];
                if (l0.service_id && $serviceSelect.find('option[value="' + l0.service_id + '"]').length) {
                    $serviceSelect.val(String(l0.service_id)).trigger('change');
                }
            }

            function collectAcOverridesForCart() {
                var ac = {};
                $('#booking-create-ac-seed .js-ac-seed').each(function () {
                    var id = String($(this).data('ac-type-id'));
                    if (!id || id === 'undefined') {
                        return;
                    }
                    ac[id] = $(this).val();
                });
                $('#billing-additional-charges-rows .js-ac-line-input').each(function () {
                    var name = $(this).attr('name');
                    if (!name) {
                        return;
                    }
                    var m = name.match(/ac_line_amount\[([^\]]+)\]/);
                    if (m) {
                        ac[m[1]] = $(this).val();
                    }
                });
                return ac;
            }

            function clearBookingCreateServiceModalErrors() {
                $('#booking-create-service-modal-error, #booking-create-add-service-error').addClass('d-none').text('');
            }

            function showBookingCreateServiceModalError(msg) {
                clearBookingCreateServiceModalErrors();
                if ($('#booking-create-summary-empty-cta').hasClass('d-none')) {
                    $('#booking-create-service-modal-error').removeClass('d-none').text(msg);
                } else {
                    $('#booking-create-add-service-error').removeClass('d-none').text(msg);
                }
            }

            function syncHiddenSelectsFromCartLineZero(done) {
                var l0 = bookingCreateCart.lines[0];
                var zoneId = $zoneSelect.val();
                var provId = $providerSelect.val();
                if (!l0 || !l0.service_id || !l0.variant_key || !zoneId) {
                    if (typeof done === 'function') {
                        done();
                    }
                    return;
                }
                var catId = l0.category_id || $categorySelect.val();
                if (!catId) {
                    if (typeof done === 'function') {
                        done();
                    }
                    return;
                }
                if (l0.category_id && $categorySelect.find('option[value="' + l0.category_id + '"]').length) {
                    $categorySelect.val(String(l0.category_id));
                }
                var subUrl = '{{ route('admin.booking.service.ajax-get-subcategories') }}';
                var svcUrl = '{{ route('admin.booking.service.ajax-get-services') }}';
                var varUrl = '{{ route('admin.booking.service.ajax-get-variant') }}';
                $.get(subUrl, { category_id: catId, provider_id: provId || '' }, function (subRes) {
                    $subCategorySelect.empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    (subRes.content || []).forEach(function (sc) {
                        $subCategorySelect.append(new Option(sc.name, sc.id, false, false));
                    });
                    reinitializeSelect2($subCategorySelect);
                    $subCategorySelect.prop('disabled', false);
                    if (l0.sub_category_id && $subCategorySelect.find('option[value="' + l0.sub_category_id + '"]').length) {
                        $subCategorySelect.val(String(l0.sub_category_id));
                    }
                    var subId = $subCategorySelect.val();
                    if (!subId) {
                        if (typeof done === 'function') {
                            done();
                        }
                        return;
                    }
                    $.get(svcUrl, { sub_category_id: subId }, function (svcRes) {
                        $serviceSelect.empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                        (svcRes.content || []).forEach(function (s) {
                            $serviceSelect.append(new Option(s.name, s.id, false, false));
                        });
                        reinitializeSelect2($serviceSelect);
                        $serviceSelect.prop('disabled', false);
                        if (l0.service_id && $serviceSelect.find('option[value="' + l0.service_id + '"]').length) {
                            $serviceSelect.val(String(l0.service_id));
                        }
                        $.get(varUrl, { service_id: l0.service_id, zone_id: zoneId }, function (vRes) {
                            $variantSelect.empty().append(new Option('{{ translate('Select Service Variant') }}', '', true, true));
                            (vRes.content || []).forEach(function (variation) {
                                var label = variation.variant + ' — ' + formatPrice(variation.price);
                                var $opt = $('<option/>').val(variation.variant_key).text(label)
                                    .attr('data-catalog-price', variation.price != null ? String(variation.price) : '0');
                                $variantSelect.append($opt);
                            });
                            reinitializeSelect2($variantSelect);
                            $variantSelect.prop('disabled', false);
                            if (l0.variant_key && $variantSelect.find('option[value="' + l0.variant_key + '"]').length) {
                                $variantSelect.val(String(l0.variant_key));
                            }
                            if (typeof done === 'function') {
                                done();
                            }
                        }).fail(function () {
                            if (typeof done === 'function') {
                                done();
                            }
                        });
                    }).fail(function () {
                        if (typeof done === 'function') {
                            done();
                        }
                    });
                }).fail(function () {
                    if (typeof done === 'function') {
                        done();
                    }
                });
            }

            function setBookingCreateSummaryLayoutMode(hasLines) {
                var $cta = $('#booking-create-summary-empty-cta');
                var $tbl = $('#booking-create-summary-table-wrap');
                var $actions = $('#booking-create-summary-actions');
                if (hasLines) {
                    $cta.addClass('d-none');
                    $tbl.removeClass('d-none');
                    $actions.removeClass('d-none');
                } else {
                    $cta.removeClass('d-none');
                    $tbl.addClass('d-none');
                    $actions.addClass('d-none');
                    var z = $zoneSelect.val();
                    var c = $categorySelect.val();
                    var p = $providerSelect.val();
                    var hint = (!z || !c || !p)
                        ? "{{ translate('Select_zone_category_and_provider_first') }}"
                        : "{{ translate('Booking_summary_no_services_yet') }}";
                    $('#booking-create-summary-empty-cta-text').text(hint);
                }
            }

            function renderBookingCreateSummaryPlaceholder() {
                $('#booking-create-summary-tbody').html(
                    '<tr id="booking-create-summary-empty"><td colspan="6"></td></tr>'
                );
                $('#booking-create-summary-tax-head').addClass('d-none');
                $('#booking-create-summary-hint').text('');
                setBookingCreateSummaryLayoutMode(false);
            }

            function renderBookingCreateSummaryFromServer(content) {
                if (!content || !content.lines || !content.lines.length) {
                    renderBookingCreateSummaryPlaceholder();
                    return;
                }
                var showTax = false;
                content.lines.forEach(function (ln) {
                    if (parseFloat(ln.tax_amount) > 0.0001) {
                        showTax = true;
                    }
                });
                if (content.extras && content.extras.length) {
                    /* extras typically no VAT line in this table */
                }
                var th = showTax ? '' : 'd-none';
                $('#booking-create-summary-tax-head').toggleClass('d-none', !showTax);
                var rows = [];
                content.lines.forEach(function (ln) {
                    var taxCell = showTax
                        ? '<td>' + formatPrice(ln.tax_amount) + '</td>'
                        : '';
                    rows.push(
                        '<tr><td class="ps-3 text-wrap"><div class="fw-semibold">' + $('<div/>').text(ln.service_name || '').html() +
                        '</div><div class="small text-muted">' + $('<div/>').text(ln.variant_label || '').html() + '</div></td>' +
                        '<td>' + formatPrice(ln.unit_price) + '</td>' +
                        '<td>' + $('<div/>').text(String(ln.quantity)).html() + '</td>' +
                        '<td>' + formatPrice(ln.discount_total) + '</td>' +
                        taxCell +
                        '<td class="text-end pe-3">' + formatPrice(ln.line_total) + '</td></tr>'
                    );
                });
                (content.extras || []).forEach(function (ex) {
                    var typeLabel = ex.type === 'spare_part' ? '{{ translate('Spare_Part') }}' : '{{ translate('Service') }}';
                    var taxCell = showTax ? '<td>—</td>' : '';
                    rows.push(
                        '<tr class="table-light"><td class="ps-3 text-wrap"><div class="fw-semibold">' + $('<div/>').text(ex.title || '').html() +
                        '</div><span class="badge bg-secondary">' + typeLabel + '</span></td>' +
                        '<td>' + formatPrice(ex.price) + '</td>' +
                        '<td>' + String(ex.quantity) + '</td>' +
                        '<td>' + formatPrice(ex.discount) + '</td>' +
                        taxCell +
                        '<td class="text-end pe-3">' + formatPrice(ex.total) + '</td></tr>'
                    );
                });
                $('#booking-create-summary-tbody').html(rows.join(''));
                var hintParts = [];
                if (parseFloat(content.extra_fee) > 0) {
                    hintParts.push("{{ translate('Additional_charges') }}: " + formatPrice(content.extra_fee));
                }
                if (parseFloat(content.extras_total) > 0) {
                    hintParts.push("{{ translate('Extra_Services') }}: " + formatPrice(content.extras_total));
                }
                if (parseFloat(content.grand_total) >= 0) {
                    hintParts.push("{{ translate('Total_Billing') }}: " + formatPrice(content.grand_total));
                }
                $('#booking-create-summary-hint').text(hintParts.join(' · '));
                setBookingCreateSummaryLayoutMode(true);
            }

            function updatePaymentBillingFromCartContent(content) {
                if (!content || content.grand_total == null) {
                    $('#billing-summary-box').hide();
                    currentBillingTotal = null;
                    billingBaseWithoutAc = null;
                    $('#billing-paid-display').text('—');
                    $('#billing-due-display').text('—');
                    $('.billing-commission-preview-row').addClass('d-none');
                    return;
                }
                var extraFee = parseFloat(content.extra_fee) || 0;
                var grand = parseFloat(content.grand_total) || 0;
                billingBaseWithoutAc = grand - extraFee;
                currentBillingTotal = grand;

                var totalService = parseFloat(content.total_service_charges);
                if (isNaN(totalService)) {
                    totalService = 0;
                    (content.lines || []).forEach(function (ln) {
                        totalService += parseFloat(ln.line_total) || 0;
                    });
                    (content.extras || []).forEach(function (ex) {
                        if (ex.type !== 'spare_part') {
                            totalService += parseFloat(ex.total) || 0;
                        }
                    });
                }
                $('#billing-total-service-charges').text(formatPrice(totalService));

                var totalSpare = parseFloat(content.total_spare_part_charges);
                if (isNaN(totalSpare)) {
                    totalSpare = 0;
                    (content.extras || []).forEach(function (ex) {
                        if (ex.type === 'spare_part') {
                            totalSpare += parseFloat(ex.total) || 0;
                        }
                    });
                }
                var showSpare = totalSpare > 0.0001;
                $('#billing-spare-part-charges-row').toggleClass('d-none', !showSpare);
                $('#billing-total-spare-part-charges').text(formatPrice(totalSpare));

                var $acBody = $('#billing-additional-charges-rows');
                $acBody.empty();
                var lines = content.additional_charges_lines || [];
                if (lines.length > 0) {
                    $acBody.append(
                        '<tr class="billing-ac-section-heading"><td colspan="2" class="pt-3 pb-1 small text-uppercase text-muted fw-semibold">' +
                        $('<div/>').text("{{ translate('Additional_charges') }}").html() +
                        '</td></tr>'
                    );
                }
                lines.forEach(function (row) {
                    var cust = row.customizable === true || row.customizable === 1;
                    var idEsc = $('<div/>').text(row.id || '').html();
                    var nameEsc = $('<div/>').text(additionalChargeRowDisplayName(row)).html();
                    var amt = parseFloat(row.amount) || 0;
                    if (cust) {
                        $acBody.append(
                            '<tr class="billing-ac-line"><td class="text-wrap ps-3 fw-medium">' + nameEsc + '</td><td class="text-end">' +
                            '<input type="number" class="form-control form-control-sm text-end js-ac-line-input d-inline-block" style="max-width:8rem" min="0" step="0.01" name="ac_line_amount[' + idEsc + ']" value="' + amt + '">' +
                            '</td></tr>'
                        );
                    } else {
                        $acBody.append(
                            '<tr class="billing-ac-line"><td class="text-wrap ps-3 fw-medium">' + nameEsc + '</td><td class="text-end text-nowrap">' + formatPrice(row.amount) + '</td></tr>'
                        );
                    }
                });
                $('#billing-additional-charges-sum').text(formatPrice(extraFee));

                var sumTax = parseFloat(content.sum_tax);
                if (isNaN(sumTax)) {
                    sumTax = 0;
                    (content.lines || []).forEach(function (ln) {
                        sumTax += parseFloat(ln.tax_amount) || 0;
                    });
                }
                if (sumTax > 0.0001) {
                    $('#billing-tax-summary-row').removeClass('d-none');
                    $('#billing-tax-sum').text(formatPrice(sumTax));
                } else {
                    $('#billing-tax-summary-row').addClass('d-none');
                }

                var totalDisc = parseFloat(content.total_discount_amount);
                if (isNaN(totalDisc)) {
                    totalDisc = 0;
                    (content.lines || []).forEach(function (ln) {
                        totalDisc += parseFloat(ln.discount_total) || 0;
                    });
                    (content.extras || []).forEach(function (ex) {
                        totalDisc += parseFloat(ex.discount) || 0;
                    });
                }
                $('#billing-discount-total').text(totalDisc > 0.0001 ? ('- ' + formatPrice(totalDisc)) : formatPrice(0));

                $('#billing-grand-total').text(formatPrice(grand));
                $('#billing-summary-box').show();
                $('#advance-paid-amount').attr('max', currentBillingTotal);
                updateDueBalance();

                var co = content.company_commission;
                var pr = content.provider_commission;
                if (co != null && pr != null && !isNaN(parseFloat(co)) && !isNaN(parseFloat(pr))) {
                    $('#billing-company-commission').text(formatPrice(co));
                    $('#billing-provider-commission').text(formatPrice(pr));
                    $('.billing-commission-preview-row').removeClass('d-none');
                } else {
                    $('.billing-commission-preview-row').addClass('d-none');
                }
            }

            function refreshBookingCreateCartSummary() {
                var zoneId = $zoneSelect.val();
                var providerId = $providerSelect.val();
                var sid = $serviceSelect.val();
                var vk = $variantSelect.val();
                // Provider is often chosen after service/variant; cart may still be empty — build line 0 from the main form.
                if (zoneId && providerId && sid && vk && bookingCreateCart.lines.length === 0) {
                    syncBookingCreateCartLineZeroFromMainForm();
                    persistBookingCreateCart();
                }
                if (!zoneId || !providerId || !bookingCreateCart.lines.length) {
                    renderBookingCreateSummaryPlaceholder();
                    $('#billing-summary-box').hide();
                    currentBillingTotal = null;
                    billingBaseWithoutAc = null;
                    $('#advance-paid-amount').removeAttr('max');
                    $('#due-balance-row').hide();
                    $('.billing-commission-preview-row').addClass('d-none');
                    updateDueBalance();
                    return;
                }
                // application/json so Laravel receives nested lines[] / extras[] (jQuery default encoding drops nested arrays)
                var cartPayload = JSON.stringify({
                    zone_id: zoneId,
                    provider_id: providerId,
                    lines: bookingCreateCart.lines.map(function (ln) {
                        var row = {
                            service_id: ln.service_id,
                            variant_key: ln.variant_key,
                            quantity: ln.quantity,
                            line_discount: Math.max(0, parseFloat(ln.line_discount) || 0),
                            line_discount_cost_bearer: (function (b) {
                                var v = String(b || 'none').toLowerCase();
                                return (v === 'admin' || v === 'provider' || v === 'none' || v === 'both') ? v : 'none';
                            })(ln.line_discount_cost_bearer)
                        };
                        if (ln.unit_price != null && ln.unit_price !== '' && !isNaN(parseFloat(ln.unit_price)) && parseFloat(ln.unit_price) > 0) {
                            row.unit_price = parseFloat(ln.unit_price);
                        }
                        return row;
                    }),
                    extras: bookingCreateCart.extras,
                    ac_line_amount: collectAcOverridesForCart()
                });
                $.ajax({
                    url: cartSummaryRoute,
                    method: 'POST',
                    contentType: 'application/json; charset=UTF-8',
                    dataType: 'json',
                    data: cartPayload,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json'
                    },
                    success: function (res) {
                        if (!res || res.response_code !== 'default_200' || !res.content) {
                            renderBookingCreateSummaryPlaceholder();
                            $('.billing-commission-preview-row').addClass('d-none');
                            return;
                        }
                        lastCartSummaryContent = res.content;
                        (res.content.lines || []).forEach(function (srvLn, idx) {
                            if (bookingCreateCart.lines[idx]) {
                                bookingCreateCart.lines[idx].service_name = srvLn.service_name || bookingCreateCart.lines[idx].service_name;
                                bookingCreateCart.lines[idx].variant_label = srvLn.variant_label || bookingCreateCart.lines[idx].variant_label;
                                if (srvLn.catalog_unit_price != null && !isNaN(parseFloat(srvLn.catalog_unit_price))) {
                                    bookingCreateCart.lines[idx].catalog_unit_price = parseFloat(srvLn.catalog_unit_price);
                                }
                            }
                        });
                        persistBookingCreateCart();
                        renderBookingCreateSummaryFromServer(res.content);
                        updatePaymentBillingFromCartContent(res.content);
                    },
                    error: function () {
                        renderBookingCreateSummaryPlaceholder();
                        $('.billing-commission-preview-row').addClass('d-none');
                    }
                });
            }

            parseBookingCreateCartInitial();

            var SERVICE_INFO_ALERT_KEYS = [
                'categories', 'categoryLoadError',
                'subcategories', 'subcategoryLoadError',
                'services', 'serviceLoadError',
                'variants', 'variantLoadError',
                'providers', 'providerLoadError'
            ];

            function emptyServiceInfoAlertState() {
                var o = {};
                SERVICE_INFO_ALERT_KEYS.forEach(function (k) {
                    o[k] = null;
                });
                return o;
            }

            var serviceInfoAlertState = emptyServiceInfoAlertState();

            // Ignore out-of-order AJAX responses when the user changes zone/category/subcategory/service quickly
            var ajaxBookingZoneGen = 0;
            var ajaxBookingCategoryGen = 0;
            var ajaxBookingSubcatServicesGen = 0;
            var ajaxBookingProvidersByCategoryGen = 0;
            var ajaxBookingVariantGen = 0;

            function resetBookingServiceInfoAlert() {
                serviceInfoAlertState = emptyServiceInfoAlertState();
                $('#booking-service-info-alert').addClass('d-none');
                $('#booking-service-info-alert-body').empty();
            }

            function renderBookingServiceInfoAlert() {
                var $wrap = $('#booking-service-info-alert');
                var $body = $('#booking-service-info-alert-body');
                var parts = [];
                SERVICE_INFO_ALERT_KEYS.forEach(function (k) {
                    if (serviceInfoAlertState[k]) {
                        parts.push(serviceInfoAlertState[k]);
                    }
                });
                if (parts.length === 0) {
                    $wrap.addClass('d-none');
                    $body.empty();
                    return;
                }
                $body.html(parts.map(function (text) {
                    return '<p class="mb-1 mb-md-0">' + $('<div/>').text(text).html() + '</p>';
                }).join(''));
                $wrap.removeClass('d-none');
                var el = $wrap[0];
                if (el && el.scrollIntoView) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            $('#booking-service-info-alert').on('click', '.booking-service-info-alert-close', function () {
                resetBookingServiceInfoAlert();
            });

            var currentBillingTotal = null;
            var billingBaseWithoutAc = null;
            var currencySymbol = $('#booking-form').data('currency') || '';
            function formatPrice(price) {
                if (price == null || price === '') return '—';
                var n = parseFloat(price);
                return isNaN(n) ? '—' : (currencySymbol + ' ' + n.toFixed(2));
            }

            function additionalChargeRowDisplayName(row) {
                if (!row) {
                    return "{{ translate('Additional_charges') }}";
                }
                var raw = '';
                if (row.name != null && String(row.name).trim() !== '') {
                    raw = String(row.name).trim();
                } else if (row.title != null && String(row.title).trim() !== '') {
                    raw = String(row.title).trim();
                } else if (row.label != null && String(row.label).trim() !== '') {
                    raw = String(row.label).trim();
                }
                if (raw) {
                    return raw;
                }
                var id = row.id != null ? String(row.id) : '';
                return id ? ("{{ translate('Additional_charge') }} · " + id) : "{{ translate('Additional_charges') }}";
            }

            function updateAdvancePaymentMethodUi() {
                var adv = parseFloat($('#advance-paid-amount').val()) || 0;
                var $wrap = $('#advance-payment-method-wrap-booking-create');
                if (!$wrap.length) {
                    return;
                }
                $wrap.show();
                var $allPm = $('#advance-payment-section .pk-apm-tier1, #advance-payment-section .pk-apm-tier2-digital, #advance-payment-section .pk-apm-tier2-offline');
                if ($allPm.length) {
                    if (adv > 0) {
                        $allPm.prop('disabled', false);
                        advancePmApplyTier2Visibility();
                    } else {
                        $allPm.prop('disabled', true).prop('checked', false);
                        $('#advance-payment-section .pk-apm-hidden').val('');
                        $('#advance-payment-section .pk-apm-tier2-digital-wrap, #advance-payment-section .pk-apm-tier2-offline-wrap').addClass('d-none');
                        if (typeof renderAdminAdvancePaymentDynamicFields === 'function') {
                            renderAdminAdvancePaymentDynamicFields('');
                        }
                    }
                }
                if (adv > 0) {
                    $('#advance-payment-help-no-advance').addClass('d-none');
                    $('#advance-payment-help-with-advance').removeClass('d-none');
                } else {
                    $('#advance-payment-help-no-advance').removeClass('d-none');
                    $('#advance-payment-help-with-advance').addClass('d-none');
                }
            }

            function updateDueBalance() {
                var advance = parseFloat($('#advance-paid-amount').val()) || 0;
                if (currentBillingTotal != null && currentBillingTotal >= 0) {
                    var due = Math.max(0, currentBillingTotal - advance);
                    $('#due-balance-amount').text(formatPrice(due));
                    $('#due-balance-row').toggle(due >= 0 && currentBillingTotal > 0).show();
                    $('#billing-paid-display').text(formatPrice(advance));
                    $('#billing-due-display').text(formatPrice(due));
                } else {
                    $('#due-balance-row').hide();
                    $('#billing-paid-display').text('—');
                    $('#billing-due-display').text('—');
                }
                updateAdvancePaymentMethodUi();
            }

            // Helper function to reinitialize Select2
            function reinitializeSelect2($select) {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                if (!$select.prop('disabled')) {
                    $select.select2();
                }
            }

            // Function to enable/disable service controls
            function toggleServiceControls(level) {
                // level: 0 = all disabled, 1 = category enabled, 2 = subcategory enabled, 3 = service enabled, 4 = variant enabled
                $categorySelect.prop('disabled', level < 1);
                $subCategorySelect.prop('disabled', level < 2);
                $serviceSelect.prop('disabled', level < 3);
                $variantSelect.prop('disabled', level < 4);

                // Clear dependent dropdowns when disabled
                if (level < 1) {
                    $categorySelect.empty().append(new Option('{{ translate('Select_Category') }}', '', true, true));
                    $subCategorySelect.empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    $serviceSelect.empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                    $variantSelect.empty().append(new Option('{{ translate('Select Service Variant') }}', '', true, true));
                    reinitializeSelect2($categorySelect);
                    reinitializeSelect2($subCategorySelect);
                    reinitializeSelect2($serviceSelect);
                    reinitializeSelect2($variantSelect);
                }
                if (level < 2) {
                    $subCategorySelect.empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    $serviceSelect.empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                    $variantSelect.empty().append(new Option('{{ translate('Select Service Variant') }}', '', true, true));
                    reinitializeSelect2($subCategorySelect);
                    reinitializeSelect2($serviceSelect);
                    reinitializeSelect2($variantSelect);
                }
                if (level < 3) {
                    $serviceSelect.empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                    $variantSelect.empty().append(new Option('{{ translate('Select Service Variant') }}', '', true, true));
                    reinitializeSelect2($serviceSelect);
                    reinitializeSelect2($variantSelect);
                }
                if (level < 4) {
                    $variantSelect.empty().append(new Option('{{ translate('Select Service Variant') }}', '', true, true));
                    reinitializeSelect2($variantSelect);
                }
            }

            // Initially disable all service controls
            toggleServiceControls(0);

            // Load categories when zone changes
            $zoneSelect.on('change', function () {
                const zoneId = $(this).val();
                clearBookingCreateServiceModalErrors();
                if (!zoneId) {
                    ajaxBookingZoneGen++;
                    ajaxBookingProvidersByCategoryGen++;
                    toggleServiceControls(0);
                    resetBookingServiceInfoAlert();
                    resetBookingProviderSelectState();
                    return;
                }

                resetBookingServiceInfoAlert();
                toggleServiceControls(0);
                var zoneGen = ++ajaxBookingZoneGen;
                $categorySelect.prop('disabled', false);
                $categorySelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );
                
                reinitializeSelect2($categorySelect);

                let route = '{{ route('admin.booking.service.ajax-get-categories') }}';
                $.get(route, {zone_id: zoneId}, function (response) {
                    if (zoneGen !== ajaxBookingZoneGen) {
                        return;
                    }
                    $categorySelect.empty().append(
                        new Option('{{ translate('Select_Category') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            serviceInfoAlertState.categories = null;
                            serviceInfoAlertState.categoryLoadError = null;
                            renderBookingServiceInfoAlert();
                            response.content.forEach(function (category) {
                                $categorySelect.append(
                                    new Option(category.name, category.id, false, false)
                                );
                            });
                            reinitializeSelect2($categorySelect);
                            var wantCat = oldValues.category_id;
                            var catMatched = wantCat && response.content.some(function (c) {
                                return String(c.id) === String(wantCat);
                            });
                            if (catMatched) {
                                $categorySelect.val(String(wantCat)).trigger('change');
                            } else if (response.content.length === 1) {
                                $categorySelect.val(response.content[0].id).trigger('change');
                            }
                        } else {
                            serviceInfoAlertState.categories = '{{ translate('No_categories_found_for_this_zone') }}';
                            renderBookingServiceInfoAlert();
                        }
                    }
                }).fail(function(xhr) {
                    if (zoneGen !== ajaxBookingZoneGen) {
                        return;
                    }
                    console.error('Failed to load categories:', xhr);
                    $categorySelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    serviceInfoAlertState.categoryLoadError = '{{ translate('Failed_to_load_categories') }}';
                    renderBookingServiceInfoAlert();
                });
            });

            // Load subcategories when category changes
            $categorySelect.on('change', function () {
                const categoryId = $(this).val();
                clearBookingCreateServiceModalErrors();
                if (!categoryId) {
                    ajaxBookingCategoryGen++;
                    ajaxBookingProvidersByCategoryGen++;
                    toggleServiceControls(1);
                    resetBookingServiceInfoAlert();
                    resetBookingProviderSelectState();
                    return;
                }

                resetBookingServiceInfoAlert();
                toggleServiceControls(1);
                var categoryGen = ++ajaxBookingCategoryGen;
                $subCategorySelect.prop('disabled', false);
                $subCategorySelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );
                
                reinitializeSelect2($subCategorySelect);

                let route = '{{ route('admin.booking.service.ajax-get-subcategories') }}';
                $.get(route, {category_id: categoryId}, function (response) {
                    if (categoryGen !== ajaxBookingCategoryGen) {
                        return;
                    }
                    $subCategorySelect.empty().append(
                        new Option('{{ translate('Select_Sub_Category') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            serviceInfoAlertState.subcategories = null;
                            serviceInfoAlertState.subcategoryLoadError = null;
                            renderBookingServiceInfoAlert();
                            response.content.forEach(function (subCategory) {
                                $subCategorySelect.append(
                                    new Option(subCategory.name, subCategory.id, false, false)
                                );
                            });
                            reinitializeSelect2($subCategorySelect);
                            var wantSub = oldValues.sub_category_id;
                            var subMatched = wantSub && response.content.some(function (sc) {
                                return String(sc.id) === String(wantSub);
                            });
                            if (subMatched) {
                                $subCategorySelect.val(String(wantSub)).trigger('change');
                            } else if (response.content.length === 1) {
                                $subCategorySelect.val(response.content[0].id).trigger('change');
                            }
                        } else {
                            serviceInfoAlertState.subcategories = '{{ translate('No_subcategories_found_for_this_category') }}';
                            renderBookingServiceInfoAlert();
                        }
                    }
                    loadProvidersForCategory(categoryId, categoryGen);
                }).fail(function(xhr) {
                    if (categoryGen !== ajaxBookingCategoryGen) {
                        return;
                    }
                    console.error('Failed to load subcategories:', xhr);
                    $subCategorySelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    serviceInfoAlertState.subcategoryLoadError = '{{ translate('Failed_to_load_subcategories') }}';
                    renderBookingServiceInfoAlert();
                    resetBookingProviderSelectState();
                });
            });

            // Load services when subcategory changes
            $subCategorySelect.on('change', function () {
                const subCategoryId = $(this).val();
                if (!subCategoryId) {
                    ajaxBookingSubcatServicesGen++;
                    toggleServiceControls(2);
                    resetBookingServiceInfoAlert();
                    return;
                }

                resetBookingServiceInfoAlert();
                toggleServiceControls(2);
                var subcatServicesGen = ++ajaxBookingSubcatServicesGen;
                $serviceSelect.prop('disabled', false);
                $serviceSelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );

                reinitializeSelect2($serviceSelect);

                let route = '{{ route('admin.booking.service.ajax-get-services') }}';
                $.get(route, {sub_category_id: subCategoryId}, function (response) {
                    if (subcatServicesGen !== ajaxBookingSubcatServicesGen) {
                        return;
                    }
                    $serviceSelect.empty().append(
                        new Option('{{ translate('Select_Service') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            serviceInfoAlertState.services = null;
                            serviceInfoAlertState.serviceLoadError = null;
                            renderBookingServiceInfoAlert();
                            response.content.forEach(function (service) {
                                $serviceSelect.append(
                                    new Option(service.name, service.id, false, false)
                                );
                            });
                            reinitializeSelect2($serviceSelect);
                            var wantSvc = oldValues.service_id;
                            var svcMatched = wantSvc && response.content.some(function (s) {
                                return String(s.id) === String(wantSvc);
                            });
                            if (svcMatched) {
                                $serviceSelect.val(String(wantSvc)).trigger('change');
                            } else if (response.content.length === 1) {
                                $serviceSelect.val(response.content[0].id).trigger('change');
                            }
                        } else {
                            serviceInfoAlertState.services = '{{ translate('No_services_found_for_this_subcategory') }}';
                            renderBookingServiceInfoAlert();
                        }
                    }
                }).fail(function(xhr) {
                    if (subcatServicesGen !== ajaxBookingSubcatServicesGen) {
                        return;
                    }
                    console.error('Failed to load services:', xhr);
                    $serviceSelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    serviceInfoAlertState.serviceLoadError = '{{ translate('Failed_to_load_services') }}';
                    renderBookingServiceInfoAlert();
                });
            });

            // Load variants when service changes
            $serviceSelect.on('change', function () {
                ajaxBookingVariantGen++;
                var variantGen = ajaxBookingVariantGen;
                const serviceId = $(this).val();
                const zoneId = $zoneSelect.val();

                serviceInfoAlertState.variants = null;
                serviceInfoAlertState.variantLoadError = null;
                renderBookingServiceInfoAlert();

                if (!serviceId || !zoneId) {
                    toggleServiceControls(3);
                    return;
                }

                toggleServiceControls(3);
                $variantSelect.prop('disabled', false);
                $variantSelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );

                reinitializeSelect2($variantSelect);

                let route = '{{ route('admin.booking.service.ajax-get-variant') }}';
                $.get(route, {service_id: serviceId, zone_id: zoneId}, function (response) {
                    if (variantGen !== ajaxBookingVariantGen) {
                        return;
                    }
                    $variantSelect.empty().append(
                        new Option('{{ translate('Select Service Variant') }}', '', true, true)
                    );

                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            serviceInfoAlertState.variants = null;
                            serviceInfoAlertState.variantLoadError = null;
                            renderBookingServiceInfoAlert();
                            response.content.forEach(function (variation) {
                                var label = variation.variant + ' — ' + formatPrice(variation.price);
                                var $opt = $('<option/>').val(variation.variant_key).text(label)
                                    .attr('data-catalog-price', variation.price != null ? String(variation.price) : '0');
                                $variantSelect.append($opt);
                            });
                            reinitializeSelect2($variantSelect);
                            toggleServiceControls(4);
                            var wantVar = oldValues.variant_key;
                            var varMatched = wantVar && response.content.some(function (v) {
                                return String(v.variant_key) === String(wantVar);
                            });
                            if (varMatched) {
                                $variantSelect.val(String(wantVar)).trigger('change');
                            } else if (response.content.length === 1) {
                                $variantSelect.val(response.content[0].variant_key).trigger('change');
                            }
                        } else {
                            serviceInfoAlertState.variants = '{{ translate('No_service_variants_for_this_zone') }}';
                            renderBookingServiceInfoAlert();
                        }
                    }
                }).fail(function(xhr) {
                    if (variantGen !== ajaxBookingVariantGen) {
                        return;
                    }
                    console.error('Failed to load variants:', xhr);
                    $variantSelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    serviceInfoAlertState.variantLoadError = '{{ translate('Failed_to_load_variants') }}';
                    renderBookingServiceInfoAlert();
                });
            });

            function recalcBookingBillingTotal() {
                if (billingBaseWithoutAc == null) {
                    return;
                }
                var acTotal = 0;
                $('#billing-additional-charges-rows .js-ac-line-input').each(function () {
                    acTotal += parseFloat($(this).val()) || 0;
                });
                var total = billingBaseWithoutAc + acTotal;
                currentBillingTotal = total;
                $('#billing-grand-total').text(formatPrice(total));
                $('#billing-additional-charges-sum').text(formatPrice(acTotal));
                $('#advance-paid-amount').attr('max', currentBillingTotal);
                updateDueBalance();
            }

            var acRefreshTimer = null;
            $(document).on('input change', '#billing-additional-charges-rows .js-ac-line-input', function () {
                if (acRefreshTimer) {
                    clearTimeout(acRefreshTimer);
                }
                acRefreshTimer = setTimeout(function () {
                    refreshBookingCreateCartSummary();
                }, 350);
            });

            function fetchBillingSummary() {
                var variantKey = $variantSelect.val();
                var serviceId = $serviceSelect.val();
                var zoneId = $zoneSelect.val();
                if (!variantKey || !serviceId || !zoneId) return;
                var billingRoute = '{{ route('admin.booking.service.ajax-get-billing-summary') }}';
                $.get(billingRoute, { zone_id: zoneId, service_id: serviceId, variant_key: variantKey, quantity: 1 }, function (res) {
                    if (res.content && res.content.total_cost != null) {
                        var serverTotal = parseFloat(res.content.total_cost);
                        var extraFee = parseFloat(res.content.extra_fee) || 0;
                        billingBaseWithoutAc = serverTotal - extraFee;
                        currentBillingTotal = serverTotal;
                        var svcCost = parseFloat(res.content.service_cost) || 0;
                        $('#billing-total-service-charges').text(formatPrice(svcCost));
                        $('#billing-spare-part-charges-row').addClass('d-none');
                        var discAmt = parseFloat(res.content.total_discount_amount) || 0;
                        $('#billing-discount-total').text(discAmt > 0.0001 ? ('- ' + formatPrice(discAmt)) : formatPrice(0));
                        var taxAmt = parseFloat(res.content.tax_amount) || 0;
                        if (taxAmt > 0.0001) {
                            $('#billing-tax-summary-row').removeClass('d-none');
                            $('#billing-tax-sum').text(formatPrice(taxAmt));
                        } else {
                            $('#billing-tax-summary-row').addClass('d-none');
                        }
                        var $acBody = $('#billing-additional-charges-rows');
                        $acBody.empty();
                        var lines = res.content.additional_charges_lines || [];
                        if (lines.length) {
                            lines.forEach(function (row) {
                                var cust = row.customizable === true || row.customizable === 1;
                                var idEsc = $('<div/>').text(row.id || '').html();
                                var nameEsc = $('<div/>').text(row.name || '').html();
                                if (cust) {
                                    var amt = parseFloat(row.amount) || 0;
                                    $acBody.append(
                                        '<tr class="billing-ac-line"><td class="ps-3 small text-muted">' + nameEsc + '</td><td class="text-end">' +
                                        '<input type="number" class="form-control form-control-sm text-end js-ac-line-input d-inline-block" style="max-width:8rem" min="0" step="0.01" name="ac_line_amount[' + idEsc + ']" value="' + amt + '">' +
                                        '</td></tr>'
                                    );
                                } else {
                                    $acBody.append(
                                        '<tr class="billing-ac-line"><td class="ps-3 small text-muted">' + nameEsc + '</td><td class="text-end">' + formatPrice(row.amount) + '</td></tr>'
                                    );
                                }
                            });
                        }
                        $('#billing-additional-charges-sum').text(formatPrice(extraFee));
                        $('#billing-grand-total').text(formatPrice(res.content.total_cost));
                        $('#billing-summary-box').show();
                        $('#advance-paid-amount').attr('max', currentBillingTotal);
                        updateDueBalance();
                        $('.billing-commission-preview-row').addClass('d-none');
                    }
                });
            }

            // When variant is selected, sync cart line 0 and refresh booking summary + billing box
            $variantSelect.on('change', function () {
                var variantKey = $(this).val();
                var serviceId = $serviceSelect.val();
                var zoneId = $zoneSelect.val();
                $('#billing-summary-box').hide();
                currentBillingTotal = null;
                billingBaseWithoutAc = null;
                $('#advance-paid-amount').removeAttr('max');
                $('#due-balance-row').hide();

                if (!variantKey || !serviceId || !zoneId) {
                    renderBookingCreateSummaryPlaceholder();
                    return;
                }
                syncBookingCreateCartLineZeroFromMainForm();
                persistBookingCreateCart();
                refreshBookingCreateCartSummary();
            });

            $('#advance-paid-amount').on('input change', function () {
                updateDueBalance();
            });

            // Provider section
            const $providerSelect = $('#provider-select');
            const $serviceLocationSection = $('#service-location-section');
            const $serviceLocationCustomer = $('#service-location-customer');
            const $serviceLocationProvider = $('#service-location-provider');
            const $serviceLocationTextRow = $('input[name="service_location"]').closest('.row');

            function resetBookingProviderSelectState() {
                $providerSelect.prop('disabled', true);
                $providerSelect.empty().append(
                    new Option('{{ translate('Select_Provider') }}', '', true, true)
                );
                if ($providerSelect.hasClass('select2-hidden-accessible')) {
                    $providerSelect.select2('destroy');
                    $providerSelect.select2();
                }
                $serviceLocationSection.hide();
            }

            function loadProvidersForCategory(categoryId, categoryRequestGen) {
                if (!categoryId) {
                    return;
                }
                var preserveProviderId = $providerSelect.val() || null;
                var gen = ++ajaxBookingProvidersByCategoryGen;
                serviceInfoAlertState.providers = null;
                serviceInfoAlertState.providerLoadError = null;
                renderBookingServiceInfoAlert();
                $providerSelect.prop('disabled', false);
                if ($providerSelect.hasClass('select2-hidden-accessible')) {
                    $providerSelect.select2('destroy');
                }
                $providerSelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );
                initializeProviderSelect2();
                var route = '{{ route('admin.booking.service.ajax-get-providers') }}';
                $.get(route, { category_id: categoryId, zone_id: $zoneSelect.val() || '' }, function (response) {
                    if (categoryRequestGen !== ajaxBookingCategoryGen) {
                        return;
                    }
                    if (gen !== ajaxBookingProvidersByCategoryGen) {
                        return;
                    }
                    $providerSelect.empty().append(
                        new Option('{{ translate('Select_Provider') }}', '', true, true)
                    );
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            serviceInfoAlertState.providers = null;
                            serviceInfoAlertState.providerLoadError = null;
                            renderBookingServiceInfoAlert();
                            response.content.forEach(function (provider) {
                                var option = $('<option></option>')
                                    .attr('value', provider.id)
                                    .attr('data-company-name', provider.company_name || '')
                                    .attr('data-contact-name', provider.contact_person_name || '')
                                    .attr('data-contact-phone', provider.contact_person_phone || '')
                                    .text(provider.company_name || 'Provider #' + provider.id);
                                $providerSelect.append(option);
                            });
                            $providerSelect.select2('destroy');
                            initializeProviderSelect2();
                            $serviceLocationSection.show();
                            var stillSubscribed = preserveProviderId && response.content.some(function (p) {
                                return String(p.id) === String(preserveProviderId);
                            });
                            if (stillSubscribed) {
                                $providerSelect.val(String(preserveProviderId)).trigger('change');
                            } else {
                                var wantProv = oldValues.provider_id;
                                var provMatched = wantProv && response.content.some(function (p) {
                                    return String(p.id) === String(wantProv);
                                });
                                if (provMatched) {
                                    $providerSelect.val(String(wantProv)).trigger('change');
                                } else if (response.content.length === 1) {
                                    $providerSelect.val(response.content[0].id).trigger('change');
                                }
                            }
                        } else {
                            serviceInfoAlertState.providers = '{{ translate('No_providers_found_for_this_subcategory') }}';
                            renderBookingServiceInfoAlert();
                            $serviceLocationSection.hide();
                        }
                    }
                }).fail(function (xhr) {
                    if (categoryRequestGen !== ajaxBookingCategoryGen) {
                        return;
                    }
                    if (gen !== ajaxBookingProvidersByCategoryGen) {
                        return;
                    }
                    console.error('Failed to load providers:', xhr);
                    $providerSelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    serviceInfoAlertState.providerLoadError = '{{ translate('Failed_to_load_providers') }}';
                    renderBookingServiceInfoAlert();
                    $serviceLocationSection.hide();
                });
            }

            // Initialize Select2 for provider dropdown when it becomes enabled
            function initializeProviderSelect2() {
                if ($providerSelect.hasClass('select2-hidden-accessible')) {
                    $providerSelect.select2('destroy');
                }
                $providerSelect.select2({
                    templateResult: formatProviderOption,
                    templateSelection: formatProviderSelection,
                    escapeMarkup: function(m) { return m; }
                });
            }

            // Format provider option for dropdown display
            function formatProviderOption(provider) {
                if (!provider.id) {
                    return provider.text;
                }
                
                const $provider = $(provider.element);
                const companyName = $provider.attr('data-company-name') || '';
                const contactName = $provider.attr('data-contact-name') || '';
                const contactPhone = $provider.attr('data-contact-phone') || '';
                
                const html = '<div style="padding: 5px 0; line-height: 1.6;">' +
                    '<div style="font-weight: bold; font-size: 1em; margin-bottom: 3px; color: inherit;">' +
                        (companyName || 'N/A') +
                    '</div>' +
                    '<div style="font-size: 0.9em; color: inherit; margin-bottom: 2px;">' +
                        '<strong>Contact Person:</strong> ' + (contactName || 'N/A') +
                    '</div>' +
                    '<div style="font-size: 0.9em; color: inherit;">' +
                        '<strong>Phone:</strong> ' + (contactPhone || 'N/A') +
                    '</div>' +
                '</div>';
                
                return $(html);
            }

            // Format provider selection (what shows in the input)
            function formatProviderSelection(provider) {
                if (!provider.id) {
                    return provider.text;
                }
                const $provider = $(provider.element);
                const companyName = $provider.attr('data-company-name') || '';
                return companyName;
            }

            // Handle provider selection change
            $providerSelect.on('change', function() {
                clearBookingCreateServiceModalErrors();
                const providerId = $(this).val();
                if (providerId) {
                    $serviceLocationSection.show();
                } else {
                    $serviceLocationSection.hide();
                }
                syncBookingCreateCartLineZeroFromMainForm();
                persistBookingCreateCart();
                refreshBookingCreateCartSummary();
            });
            
            // Handle service location radio button change
            $('input[name="service_location_type"]').on('change', function() {
                const locationType = $(this).val();
                $serviceLocationHidden.val(locationType);
                
                // Show/hide address fields based on selection
                if (locationType === 'customer') {
                    // Show address fields if customer is selected
                    if ($customerSelect.val()) {
                        $addressRow.show();
                        // Re-enable address select if customer is selected
                        if ($customerSelect.val()) {
                            $addressSelect.prop('disabled', false);
                            $addAddressBtn.prop('disabled', false);
                            syncEditAddressButtonState();
                        }
                    }
                } else {
                    // Hide address fields for provider location
                    $addressRow.hide();
                    $addressSelect.val('').prop('disabled', true);
                    $addAddressBtn.prop('disabled', true);
                    if ($editAddressBtn.length) {
                        $editAddressBtn.prop('disabled', true);
                    }
                }
            });
            

            // Restore service location radio button
            if (oldValues.service_location) {
                $serviceLocationHidden.val(oldValues.service_location);
                if (oldValues.service_location === 'provider') {
                    $('#service-location-provider').prop('checked', true);
                } else {
                    $('#service-location-customer').prop('checked', true);
                }
                // Trigger change to update UI
                setTimeout(function() {
                    $('input[name="service_location_type"]:checked').trigger('change');
                }, 100);
            }

            // Customer prefill (merged request / session old): set value then load addresses
            if (oldValues.customer_id) {
                $customerSelect.val(String(oldValues.customer_id));
            }
            if ($customerSelect.val()) {
                $customerSelect.trigger('change');
            }

            // Zone prefill: merged request (e.g. create-from-lead) is not in session old(); ensure value + cascade via AJAX handlers
            if (oldValues.zone_id) {
                $zoneSelect.val(String(oldValues.zone_id));
            }
            if ($zoneSelect.val()) {
                $zoneSelect.trigger('change');
            }

            // Restore address selection
            if (oldValues.service_address_id && oldValues.customer_id) {
                setTimeout(function() {
                    $addressSelect.val(oldValues.service_address_id).trigger('change');
                }, 500);
            }

            // Restore text inputs
            if (oldValues.service_schedule) {
                $('input[name="service_schedule"]').val(oldValues.service_schedule);
            }
            if (oldValues.advance_paid_amount) {
                $('input[name="advance_paid_amount"]').val(oldValues.advance_paid_amount);
            }

            // Restore assignee selection
            if (oldValues.assignee_id) {
                $('#assignee-select').val(oldValues.assignee_id).trigger('change');
            }

            (function restoreBookingSourceSelect() {
                var raw = oldValues.booking_source;
                if (raw == null || raw === '') {
                    return;
                }
                var $bs = $('select[name="booking_source"]');
                if (!$bs.length) {
                    return;
                }
                var want = String(raw);
                var matchVal = null;
                $bs.find('option').each(function () {
                    var v = $(this).val();
                    if (!v) {
                        return;
                    }
                    if (v === want) {
                        matchVal = v;
                        return false;
                    }
                    if (String(v).toLowerCase() === want.toLowerCase()) {
                        matchVal = v;
                        return false;
                    }
                });
                if (matchVal != null) {
                    $bs.val(matchVal);
                }
            })();

            if (oldValues.advance_paid_amount !== null && oldValues.advance_paid_amount !== undefined) {
                $('#advance-paid-amount').val(oldValues.advance_paid_amount);
            }
            if (oldValues.advance_payment_method) {
                var wantApm = String(oldValues.advance_payment_method);
                if (wantApm === 'cash_after_service' && $('#pk-apm-t1-cas-booking-create').length) {
                    $('#pk-apm-t1-cas-booking-create').prop('checked', true).trigger('change', { hydrateFromInitial: true });
                } else if (wantApm.indexOf('offline:') === 0 && $('#pk-apm-t1-offline-booking-create').length) {
                    $('#pk-apm-t1-offline-booking-create').prop('checked', true);
                    advancePmApplyTier2Visibility();
                    $('#advance-payment-section .pk-apm-tier2-offline').each(function () {
                        if ($(this).val() === wantApm) {
                            $(this).prop('checked', true);
                        }
                    });
                    advancePmSyncFinalValue({ hydrateFromInitial: true });
                } else if ($('#pk-apm-t1-digital-booking-create').length) {
                    $('#pk-apm-t1-digital-booking-create').prop('checked', true);
                    advancePmApplyTier2Visibility();
                    $('#advance-payment-section .pk-apm-tier2-digital').each(function () {
                        if ($(this).val() === wantApm) {
                            $(this).prop('checked', true);
                        }
                    });
                    advancePmSyncFinalValue({ hydrateFromInitial: true });
                }
            }
            if (typeof updateAdvancePaymentMethodUi === 'function') {
                updateAdvancePaymentMethodUi();
            }
            setTimeout(function () {
                var advN = parseFloat($('#advance-paid-amount').val()) || 0;
                var hv = ($('#advance-payment-section .pk-apm-hidden').val() || '').trim();
                advancePmApplyTier2Visibility();
                if (advN > 0 && hv && $('#advance-payment-section .pk-apm-dynamic-fields').children().length === 0) {
                    renderAdminAdvancePaymentDynamicFields(hv, { useInitial: true });
                }
            }, 0);

            setTimeout(function () {
                if (bookingCreateCart.lines.length) {
                    persistBookingCreateCart();
                } else {
                    syncBookingCreateCartLineZeroFromMainForm();
                    persistBookingCreateCart();
                }
                refreshBookingCreateCartSummary();
            }, 2200);

            function clearSingleFieldError($field) {
                if (!$field || !$field.length) return;
                $field.removeClass('is-invalid');
                if ($field.hasClass('select2-hidden-accessible')) {
                    $field.next('.select2-container').removeClass('booking-select2-invalid');
                    $field.next('.select2-container').next('.booking-js-invalid-feedback').remove();
                } else {
                    $field.next('.booking-js-invalid-feedback').remove();
                }
            }

            function clearBookingFieldErrors() {
                $('#booking-form .booking-js-invalid-feedback').remove();
                $('#booking-form select.is-invalid, #booking-form input.is-invalid, #booking-form textarea.is-invalid').removeClass('is-invalid');
                $('#booking-form .select2-container.booking-select2-invalid').removeClass('booking-select2-invalid');
            }

            function markFieldInvalid($field, hint) {
                if (!$field || !$field.length) return;
                clearSingleFieldError($field);
                $field.addClass('is-invalid');
                var $fb = $('<div class="invalid-feedback d-block booking-js-invalid-feedback small"></div>').text(hint);
                if ($field.hasClass('select2-hidden-accessible')) {
                    var $c = $field.next('.select2-container');
                    $c.addClass('booking-select2-invalid');
                    $fb.insertAfter($c);
                } else {
                    $fb.insertAfter($field);
                }
            }

            var $mCat = $('#create-modal-category');
            var $mSub = $('#create-modal-subcategory');
            var $mSvc = $('#create-modal-service');
            var $mVar = $('#create-modal-variant');
            var $mQty = $('#create-modal-qty');
            var $createModalAddLine = $('#create-modal-add-line');

            function updateCreateModalAddLineEnabled() {
                var cat = $mCat.val();
                var sub = $mSub.val();
                var svc = $mSvc.val();
                var vk = $mVar.val();
                var qty = parseInt($mQty.val(), 10);
                var qtyOk = !isNaN(qty) && qty >= 1;
                var ok = !!cat && !!sub && !!svc && !!vk && qtyOk;
                $createModalAddLine.prop('disabled', !ok);
            }

            function updateCreateModalRowSubtotal($tr) {
                if (!$tr || !$tr.length) {
                    return;
                }
                var catalog = parseFloat($tr.attr('data-catalog-unit-price')) || 0;
                var upRaw = ($tr.find('.create-modal-unit-price').val() || '').trim();
                var up = upRaw === '' ? catalog : (parseFloat(upRaw) || 0);
                var qty = Math.max(1, parseInt($tr.find('.create-modal-line-qty').val(), 10) || 1);
                var disc = Math.max(0, parseFloat($tr.find('.create-modal-line-discount').val()) || 0);
                var gross = Math.max(0, up * qty - disc);
                $tr.find('.create-modal-row-subtotal').text(formatPrice(gross));
            }

            function readCreateModalLinesIntoCart() {
                var next = [];
                $('#create-modal-lines-tbody tr').each(function () {
                    var $tr = $(this);
                    var sid = $tr.attr('data-service-id');
                    var vk = $tr.attr('data-variant-key');
                    if (!sid || !vk) {
                        return;
                    }
                    var catalog = parseFloat($tr.attr('data-catalog-unit-price')) || 0;
                    var qty = Math.max(1, parseInt($tr.find('.create-modal-line-qty').val(), 10) || 1);
                    var upRaw = ($tr.find('.create-modal-unit-price').val() || '').trim();
                    var upNum = upRaw === '' ? null : parseFloat(upRaw);
                    if (upNum != null && (isNaN(upNum) || upNum <= 0)) {
                        upNum = catalog > 0 ? catalog : null;
                    }
                    var unitPrice = null;
                    if (upNum != null && !isNaN(upNum) && upNum > 0) {
                        if (catalog <= 0 || Math.abs(upNum - catalog) > 0.0001) {
                            unitPrice = upNum;
                        }
                    }
                    var disc = Math.max(0, parseFloat($tr.find('.create-modal-line-discount').val()) || 0);
                    var bear = ($tr.find('.create-modal-line-discount-bearer').val() || 'none').toLowerCase();
                    if (bear !== 'admin' && bear !== 'provider' && bear !== 'none' && bear !== 'both') {
                        bear = 'none';
                    }
                    next.push({
                        service_id: String(sid),
                        variant_key: String(vk),
                        quantity: qty,
                        service_name: $tr.attr('data-service-name') || '',
                        variant_label: $tr.attr('data-variant-label') || '',
                        category_id: $tr.attr('data-category-id') || null,
                        sub_category_id: $tr.attr('data-sub-category-id') || null,
                        unit_price: unitPrice,
                        line_discount: disc,
                        line_discount_cost_bearer: bear,
                        catalog_unit_price: catalog > 0 ? catalog : null
                    });
                });
                bookingCreateCart.lines = next;
            }

            function renderCreateModalLinesTbody() {
                var $tb = $('#create-modal-lines-tbody').empty();
                bookingCreateCart.lines.forEach(function (line, idx) {
                    var sn = line.service_name || line.service_id;
                    var vn = line.variant_label || line.variant_key;
                    var catalog = (line.catalog_unit_price != null && !isNaN(parseFloat(line.catalog_unit_price)))
                        ? parseFloat(line.catalog_unit_price) : 0;
                    var showUnit = (line.unit_price != null && line.unit_price !== '' && !isNaN(parseFloat(line.unit_price)))
                        ? String(line.unit_price)
                        : (catalog > 0 ? String(catalog) : '');
                    var disc = Math.max(0, parseFloat(line.line_discount) || 0);
                    var bearVal = String(line.line_discount_cost_bearer || 'none').toLowerCase();
                    if (bearVal !== 'admin' && bearVal !== 'provider' && bearVal !== 'none' && bearVal !== 'both') {
                        bearVal = 'none';
                    }
                    var tr = $('<tr/>')
                        .attr('data-idx', idx)
                        .attr('data-service-id', line.service_id)
                        .attr('data-variant-key', line.variant_key)
                        .attr('data-service-name', sn)
                        .attr('data-variant-label', vn)
                        .attr('data-category-id', line.category_id || '')
                        .attr('data-sub-category-id', line.sub_category_id || '')
                        .attr('data-catalog-unit-price', catalog > 0 ? String(catalog) : '0');
                    tr.append($('<td/>').addClass('text-wrap').text(sn));
                    tr.append($('<td/>').addClass('text-wrap').text(vn));
                    var $unitIn = $('<input type="number" class="form-control form-control-sm create-modal-unit-price" min="0" step="0.01"/>').val(showUnit);
                    tr.append($('<td style="min-width:7rem"/>').append($unitIn));
                    var $qtyIn = $('<input type="number" class="form-control form-control-sm create-modal-line-qty" min="1" step="1"/>').val(String(Math.max(1, parseInt(line.quantity, 10) || 1)));
                    tr.append($('<td style="min-width:5rem"/>').append($qtyIn));
                    var $discIn = $('<input type="number" class="form-control form-control-sm create-modal-line-discount" min="0" step="0.01"/>').val(disc > 0 ? String(disc) : '');
                    tr.append($('<td style="min-width:6rem"/>').append($discIn));
                    var $bearSel = $('<select class="form-control form-control-sm create-modal-line-discount-bearer"/>');
                    [
                        { v: 'none', t: "{{ translate('Discount_bearer_label_no_one') }}" },
                        { v: 'admin', t: "{{ translate('Discount_bearer_admin') }}" },
                        { v: 'provider', t: "{{ translate('Discount_bearer_provider') }}" },
                        { v: 'both', t: "{{ translate('Discount_bearer_both') }}" }
                    ].forEach(function (o) {
                        $bearSel.append($('<option/>').val(o.v).text(o.t).prop('selected', o.v === bearVal));
                    });
                    tr.append($('<td style="min-width:10rem"/>').append($bearSel));
                    tr.append($('<td class="create-modal-row-subtotal text-nowrap"/>'));
                    var $rm = $('<button type="button" class="btn btn-sm btn-outline-danger"/>').text("{{ translate('Remove') }}");
                    $rm.on('click', function () {
                        var i = parseInt($(this).closest('tr').attr('data-idx'), 10);
                        if (!isNaN(i)) {
                            bookingCreateCart.lines.splice(i, 1);
                            renderCreateModalLinesTbody();
                        }
                    });
                    tr.append($('<td class="text-center"/>').append($rm));
                    $tb.append(tr);
                    updateCreateModalRowSubtotal(tr);
                });
            }

            $('#create-modal-lines-tbody').on('input change', '.create-modal-unit-price, .create-modal-line-qty, .create-modal-line-discount, .create-modal-line-discount-bearer', function () {
                updateCreateModalRowSubtotal($(this).closest('tr'));
            });

            function loadCreateModalCategories() {
                var z = $zoneSelect.val();
                var pid = $providerSelect.val();
                if (!z) {
                    return;
                }
                var params = { zone_id: z };
                if (pid) {
                    params.provider_id = pid;
                }
                $.get('{{ route('admin.booking.service.ajax-get-categories') }}', params, function (res) {
                    $mCat.empty().append(new Option("{{ translate('Select_Category') }}", '', true, true));
                    (res.content || []).forEach(function (c) {
                        $mCat.append(new Option(c.name, c.id, false, false));
                    });
                    var mainCat = $categorySelect.val();
                    if (mainCat && $mCat.find('option[value="' + mainCat + '"]').length) {
                        $mCat.val(String(mainCat)).trigger('change');
                    }
                    updateCreateModalAddLineEnabled();
                });
            }

            $mCat.on('change', function () {
                var cid = $(this).val();
                $mSub.empty().append(new Option("{{ translate('Select_Sub_Category') }}", '', true, true));
                $mSvc.empty().append(new Option("{{ translate('Select_Service') }}", '', true, true));
                $mVar.empty().append(new Option("{{ translate('Select Service Variant') }}", '', true, true));
                if (!cid) {
                    updateCreateModalAddLineEnabled();
                    return;
                }
                $.get('{{ route('admin.booking.service.ajax-get-subcategories') }}', {
                    category_id: cid,
                    provider_id: $providerSelect.val() || ''
                }, function (res) {
                    (res.content || []).forEach(function (s) {
                        $mSub.append(new Option(s.name, s.id, false, false));
                    });
                    updateCreateModalAddLineEnabled();
                });
            });

            $mSub.on('change', function () {
                var sid = $(this).val();
                $mSvc.empty().append(new Option("{{ translate('Select_Service') }}", '', true, true));
                $mVar.empty().append(new Option("{{ translate('Select Service Variant') }}", '', true, true));
                if (!sid) {
                    updateCreateModalAddLineEnabled();
                    return;
                }
                $.get('{{ route('admin.booking.service.ajax-get-services') }}', { sub_category_id: sid }, function (res) {
                    (res.content || []).forEach(function (s) {
                        $mSvc.append(new Option(s.name, s.id, false, false));
                    });
                    updateCreateModalAddLineEnabled();
                });
            });

            $mSvc.on('change', function () {
                var svcId = $(this).val();
                var zoneId = $zoneSelect.val();
                $mVar.empty().append(new Option("{{ translate('Select Service Variant') }}", '', true, true));
                if (!svcId || !zoneId) {
                    updateCreateModalAddLineEnabled();
                    return;
                }
                $.get('{{ route('admin.booking.service.ajax-get-variant') }}', { service_id: svcId, zone_id: zoneId }, function (res) {
                    (res.content || []).forEach(function (v) {
                        var label = v.variant + ' — ' + formatPrice(v.price);
                        var $opt = $('<option/>').val(v.variant_key).text(label).attr('data-catalog-price', v.price != null ? String(v.price) : '0');
                        $mVar.append($opt);
                    });
                    updateCreateModalAddLineEnabled();
                });
            });

            $mVar.on('change', function () {
                updateCreateModalAddLineEnabled();
            });

            $mQty.on('input change', function () {
                updateCreateModalAddLineEnabled();
            });

            function openBookingCreateServiceModal() {
                clearBookingCreateServiceModalErrors();
                if (!$zoneSelect.val() || !$categorySelect.val() || !$providerSelect.val()) {
                    showBookingCreateServiceModalError("{{ translate('Select_zone_category_and_provider_first') }}");
                    return;
                }
                loadCreateModalCategories();
                renderCreateModalLinesTbody();
                var el = document.getElementById('serviceUpdateModal--create');
                if (el && window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                } else {
                    $('#serviceUpdateModal--create').modal('show');
                }
                updateCreateModalAddLineEnabled();
            }

            $('#serviceUpdateModal--create').on('shown.bs.modal', function () {
                updateCreateModalAddLineEnabled();
            });

            $('#btn-booking-create-add-service, #btn-open-create-service-modal').on('click', function (e) {
                e.preventDefault();
                openBookingCreateServiceModal();
            });

            $('#create-modal-add-line').on('click', function () {
                if ($(this).prop('disabled')) {
                    return;
                }
                var sid = $mSvc.val();
                var vk = $mVar.val();
                var qty = Math.max(1, parseInt($mQty.val(), 10) || 1);
                if (!sid || !vk) {
                    return;
                }
                var sn = ($mSvc.find('option:selected').text() || '').trim();
                var vn = ($mVar.find('option:selected').text() || '').trim();
                var catPrice = parseFloat($mVar.find('option:selected').attr('data-catalog-price')) || 0;
                bookingCreateCart.lines.push({
                    service_id: String(sid),
                    variant_key: String(vk),
                    quantity: qty,
                    service_name: sn,
                    variant_label: vn,
                    category_id: $mCat.val() || null,
                    sub_category_id: $mSub.val() || null,
                    unit_price: null,
                    line_discount: 0,
                    line_discount_cost_bearer: 'none',
                    catalog_unit_price: catPrice > 0 ? catPrice : null
                });
                renderCreateModalLinesTbody();
                $mSub.val('').trigger('change');
                $mQty.val(1);
                updateCreateModalAddLineEnabled();
            });

            $('#create-modal-save-cart').on('click', function () {
                readCreateModalLinesIntoCart();
                if (!bookingCreateCart.lines.length) {
                    return;
                }
                persistBookingCreateCart();
                refreshBookingCreateCartSummary();
                var $modalEl = $('#serviceUpdateModal--create');
                if (window.bootstrap && bootstrap.Modal && $modalEl.length) {
                    bootstrap.Modal.getOrCreateInstance($modalEl[0]).hide();
                } else {
                    $modalEl.modal('hide');
                }
                syncHiddenSelectsFromCartLineZero();
            });

            $('#create-extra-add-btn').on('click', function () {
                var title = ($('#create-extra-title').val() || '').trim();
                if (!title) {
                    return;
                }
                var qty = Math.max(1, parseInt($('#create-extra-qty').val(), 10) || 1);
                var price = Math.max(0, parseFloat($('#create-extra-price').val()) || 0);
                var discount = Math.max(0, parseFloat($('#create-extra-discount').val()) || 0);
                bookingCreateCart.extras.push({
                    title: title,
                    details: ($('#create-extra-details').val() || '').trim() || null,
                    type: $('#create-extra-type').val() === 'spare_part' ? 'spare_part' : 'service',
                    quantity: qty,
                    price: price,
                    discount: discount
                });
                $('#create-extra-title').val('');
                $('#create-extra-details').val('');
                $('#create-extra-qty').val('1');
                $('#create-extra-price').val('0');
                $('#create-extra-discount').val('0');
                persistBookingCreateCart();
                refreshBookingCreateCartSummary();
                var exEl = document.getElementById('addExtraServiceModal--create');
                if (exEl && window.bootstrap && bootstrap.Modal) {
                    var mEx = bootstrap.Modal.getInstance(exEl);
                    if (mEx) {
                        mEx.hide();
                    }
                } else {
                    $('#addExtraServiceModal--create').modal('hide');
                }
            });

            $('#booking-form').on('input change', 'select, input, textarea', function () {
                clearSingleFieldError($(this));
            });

            function validateBookingFormBeforePreview() {
                var msgs = [];
                var fieldErrors = [];
                var $first = null;

                function pushError(label, detail, $field) {
                    msgs.push(label + ': ' + detail);
                    if ($field && $field.length) {
                        fieldErrors.push({ $el: $field, hint: detail });
                        if (!$first) {
                            $first = $field;
                        }
                    }
                }

                var req = '{{ translate('This_field_required') }}';
                var loc = ($serviceLocationHidden.val() || 'customer');

                if (!$customerSelect.val()) {
                    pushError('{{ translate('Customer') }}', req, $customerSelect);
                }
                if (loc === 'customer' && $addressRow.is(':visible') && !$addressSelect.prop('disabled') && !$addressSelect.val()) {
                    pushError('{{ translate('Service_Address') }}', req, $addressSelect);
                }
                if (!$zoneSelect.val()) {
                    pushError('{{ translate('Zone') }}', req, $zoneSelect);
                }
                if (!$categorySelect.val()) {
                    pushError('{{ translate('Category') }}', req, $categorySelect);
                }
                var cartHasServices = bookingCreateCart.lines.length > 0 && bookingCreateCart.lines.every(function (ln) {
                    return ln.service_id && ln.variant_key;
                });
                if (!cartHasServices) {
                    showBookingCreateServiceModalError('{{ translate('Add_at_least_one_service_to_continue') }}');
                    pushError('{{ translate('Service') }}', '{{ translate('Add_at_least_one_service_to_continue') }}', $('#btn-booking-create-add-service'));
                }

                var $schedule = $('input[name="service_schedule"]');
                var scheduleVal = ($schedule.val() || '').trim();
                if (!scheduleVal) {
                    pushError('{{ translate('Service_Schedule') }}', req, $schedule);
                } else {
                    var t = Date.parse(scheduleVal);
                    if (isNaN(t)) {
                        pushError('{{ translate('Service_Schedule') }}', '{{ translate('Please_enter_a_valid_service_schedule') }}', $schedule);
                    }
                }

                var $bookingSource = $('select[name="booking_source"]');
                if (!$bookingSource.val()) {
                    pushError('{{ translate('Booking_Source') }}', req, $bookingSource);
                }

                if (!$providerSelect.val()) {
                    pushError('{{ translate('Provider') }}', req, $providerSelect);
                }

                var advRaw = ($('#advance-paid-amount').val() || '').trim();
                var advParsed = advRaw === '' ? 0 : parseFloat(advRaw);
                var advanceAmountValid = true;
                if (advRaw !== '') {
                    if (isNaN(advParsed) || advParsed < 0) {
                        advanceAmountValid = false;
                        pushError('{{ translate('Advance_Paid_Amount') }}', '{{ translate('Enter_a_valid_non_negative_number') }}', $('#advance-paid-amount'));
                    } else if (currentBillingTotal != null && currentBillingTotal >= 0 && advParsed > currentBillingTotal) {
                        advanceAmountValid = false;
                        pushError('{{ translate('Advance_Paid_Amount') }}', '{{ translate('Advance_amount_cannot_exceed_total_billing_amount') }}', $('#advance-paid-amount'));
                    }
                }
                if (advanceAmountValid && advParsed > 0) {
                    if (typeof advancePmUpdateHiddenOnly === 'function') {
                        advancePmUpdateHiddenOnly();
                    }
                    var finalApm = ($('#advance-payment-section .pk-apm-hidden').val() || '').trim();
                    var $apmFocus = $('#advance-payment-section .pk-apm-tier1:checked').length ? $('#advance-payment-section .pk-apm-tier1:checked') : $('#advance-payment-section .pk-apm-tier1').first();
                    if (!finalApm) {
                        pushError('{{ translate('Advance_payment_method') }}', '{{ translate('Advance_payment_method_is_required_when_advance_amount_is_set') }}', $apmFocus.length ? $apmFocus : $('#advance-payment-method-wrap-booking-create'));
                    } else {
                        var advCfg = adminAdvanceMethodConfig[finalApm];
                        if (advCfg && advCfg.fields && advCfg.fields.length) {
                            advCfg.fields.forEach(function (f) {
                                if (!f.required) {
                                    return;
                                }
                                var $inp = $('#advance-payment-section .pk-apm-dynamic-fields input').filter(function () {
                                    return $(this).attr('name') === f.input_name;
                                }).first();
                                var v = ($inp.val() || '').trim();
                                if (!v) {
                                    pushError(f.label || '{{ translate('This field is required.') }}', '{{ translate('This field is required.') }}', $inp.length ? $inp : $('#advance-payment-section .pk-apm-hidden'));
                                }
                            });
                        }
                    }
                }

                var desc = ($('textarea[name="service_description"]').val() || '');
                if (desc.length > 2000) {
                    pushError('{{ translate('Service_Additional_Details_(Optional)') }}', '{{ translate('Service_additional_details_must_not_exceed_2000_characters') }}', $('textarea[name="service_description"]'));
                }

                return { msgs: msgs, $first: $first, fieldErrors: fieldErrors };
            }

            $('#booking-form').on('submit', function (e) {
                e.preventDefault();
                if (!bookingCreateCart.lines.length) {
                    syncBookingCreateCartLineZeroFromMainForm();
                }
                persistBookingCreateCart();
                clearBookingFieldErrors();
                var result = validateBookingFormBeforePreview();
                if (result.msgs.length) {
                    result.fieldErrors.forEach(function (fe) {
                        markFieldInvalid(fe.$el, fe.hint);
                    });
                    renderBookingFormValidationAlert('{{ translate('Please_fill_the_booking_form_correctly') }}', result.msgs);
                    if (result.$first && result.$first.length && result.$first[0]) {
                        result.$first[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (result.$first.hasClass('js-select') && result.$first.hasClass('select2-hidden-accessible')) {
                            result.$first.select2('open');
                        } else {
                            result.$first.trigger('focus');
                        }
                    }
                    return false;
                }
                clearBookingFieldErrors();
                resetBookingFormValidationAlert();
                $(this).find('select').each(function () {
                    var $s = $(this);
                    if ($s.val() && $s.prop('disabled')) {
                        $s.prop('disabled', false);
                    }
                });
                $(this).find('.pk-apm-hidden, .pk-apm-tier1, .pk-apm-tier2-digital, .pk-apm-tier2-offline').prop('disabled', false);
                $(this).find('button[type="submit"], input[type="submit"]').prop('disabled', true);
                this.submit();
            });
        });
    </script>
@endpush

