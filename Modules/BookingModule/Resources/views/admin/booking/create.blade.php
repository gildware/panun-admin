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
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ translate('Add_New_Booking') }}</h2>
            <a href="{{ route('admin.booking.list', ['booking_status' => 'pending', 'service_type' => 'all']) }}"
               class="btn btn-secondary">
                {{ translate('Back_to_Booking_List') }}
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
                    @if(request()->has('lead_id'))
                        <input type="hidden" name="lead_id" value="{{ request('lead_id') }}">
                        <input type="hidden" name="in_modal" value="1">
                    @endif
                    @if(!empty($reopenNewBookingDraft['source_booking_id']))
                        <input type="hidden" name="reopen_source_booking_id" value="{{ $reopenNewBookingDraft['source_booking_id'] }}">
                    @endif

                    {{-- 1. Customer --}}
                    <div class="mb-4 border rounded-3 p-3">
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

                        <div class="row">
                            <div class="col-md-10">
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

                    {{-- 2. Service --}}
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
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Category') }}</label>
                                    <select name="category_id" id="service-category-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select_Category') }}</option>
                                    </select>
                                    @error('category_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Sub_Category') }}</label>
                                    <select name="sub_category_id" id="service-subcategory-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select_Sub_Category') }}</option>
                                    </select>
                                    @error('sub_category_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Service') }}</label>
                                    <select name="service_id" id="service-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select_Service') }}</option>
                                    </select>
                                    @error('service_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Select_Service_Variant') }}</label>
                                    <select name="variant_key" id="service-variant-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select Service Variant') }}</option>
                                    </select>
                                    @error('variant_key')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Service_Additional_Details_(Optional)') }}</label>
                                    <textarea name="service_description" class="form-control" rows="3" placeholder="{{ translate('Add_any_extra_information_or_requirements_for_this_service') }}">{{ old('service_description', request('service_description')) }}</textarea>
                                    @error('service_description')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
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

                    {{-- 3. Date & Time --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('3._Date_&_Time') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
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

                    {{-- 3.1 Booking Source --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Booking_Source') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('How_was_this_booking_created?') }}</label>
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
                            </div>
                        </div>
                    </div>

                    {{-- 4. Provider --}}
                    <div class="mb-4 border rounded-3 p-3">
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

                    {{-- Payment summary & Advance Payment --}}
                    <div class="mb-4 border rounded-3 p-3" id="payment-section">
                        <h4 class="mb-3">{{ translate('Payment_information') }}</h4>

                        {{-- Total billing (shown when variant selected) --}}
                        <div id="billing-summary-box" class="mb-4 p-3 bg-light rounded" style="display: none;">
                            <h5 class="mb-2">{{ translate('Total_Billing') }}</h5>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>{{ translate('Service_Charges') }}</td>
                                    <td class="text-end" id="billing-service-charges">—</td>
                                </tr>
                                <tr>
                                    <td>{{ translate('Discount') }}</td>
                                    <td class="text-end" id="billing-discount">—</td>
                                </tr>
                                <tr id="billing-tax-row" style="display: none;">
                                    <td>{{ translate('Tax') }}</td>
                                    <td class="text-end" id="billing-tax">—</td>
                                </tr>
                                @if(!empty($additionalChargeEnabled))
                                <tr id="billing-extra-fee-row">
                                    <td>{{ $additionalChargeLabel }}</td>
                                    <td class="text-end">
                                        <input type="number" step="0.01" min="0" name="extra_fee" id="extra-fee-input"
                                               class="form-control form-control-sm d-inline-block text-end" style="width: 6rem;"
                                               value="{{ old('extra_fee', request('extra_fee', $additionalChargeDefaultAmount)) }}" placeholder="0">
                                    </td>
                                </tr>
                                @endif
                                <tr class="fw-bold">
                                    <td>{{ translate('Total_Amount') }}</td>
                                    <td class="text-end c1" id="billing-total">—</td>
                                </tr>
                            </table>
                        </div>

                        <h5 class="mb-2">{{ translate('Advance_Payment') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Advance_Paid_Amount') }}</label>
                                    <input type="number" step="0.01" min="0" name="advance_paid_amount" id="advance-paid-amount"
                                           class="form-control" value="{{ old('advance_paid_amount', request('advance_paid_amount', 100)) }}"
                                           placeholder="0">
                                    @error('advance_paid_amount')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div id="due-balance-row" class="small mt-1" style="display: none;">
                                        <strong>{{ translate('Due_Balance') }}:</strong> <span id="due-balance-amount">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Advance_Payment_Transaction_ID') }}</label>
                                    <input type="text" name="advance_transaction_id" id="advance-transaction-id" class="form-control"
                                           value="{{ old('advance_transaction_id', request('advance_transaction_id')) }}"
                                           placeholder="{{ translate('Enter_bank_or_wallet_transaction_id_if_available') }}">
                                    @error('advance_transaction_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <p class="text-muted mb-1">
                            {{ translate('Payment_method_will_be_set_as_Cash_After_Service_and_final_payment_will_be_taken_from_customer_at_completion.') }}
                        </p>
                    </div>

                    {{-- 6. Assignment --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Assignment') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Assignee') }}</label>
                                    <select name="assignee_id" id="assignee-select" class="form-control js-select">
                                        {{-- Default: assign to self if no previous selection --}}
                                        @if(isset($currentAdmin))
                                            <option value="{{ $currentAdmin->id }}"
                                                {{ (old('assignee_id', request('assignee_id', $currentAdmin->id)) == $currentAdmin->id) ? 'selected' : '' }}>
                                                {{ translate('Assign_to_me') }}
                                                ({{ $currentAdmin->first_name }} {{ $currentAdmin->last_name }}
                                                - {{ $currentAdmin->email ?? $currentAdmin->phone }})
                                            </option>
                                        @endif

                                        {{-- Other assignees --}}
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('City') }}</label>
                                    <input type="text" name="city" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Street') }}</label>
                                    <input type="text" name="street" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Country') }}</label>
                                    <input type="text" name="country" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Zip_Code') }}</label>
                                    <input type="text" name="zip_code" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ translate('Address') }}</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ translate('Address_Label') }}</label>
                            <input type="text" name="address_label" class="form-control" placeholder="{{ translate('Home/Office/Others') }}">
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

            const $customerSelect = $('#customer-select');
            const $addressSelect = $('#customer-address-select');
            const $addAddressBtn = $('#open-add-address');
            const $serviceLocationHidden = $('#service-location-hidden');
            const $addressRow = $('#customer-address-select').closest('.row');

            // Function to enable/disable address controls
            function toggleAddressControls(enabled) {
                $addressSelect.prop('disabled', !enabled);
                $addAddressBtn.prop('disabled', !enabled);
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
                    });
                } else {
                    // Provider location - hide address fields
                    toggleAddressControls(false);
                    $addressRow.hide();
                }
            });

            $('#open-add-address').on('click', function () {
                if (!$customerSelect.val()) {
                    renderBookingCustomerAlert('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                resetBookingCustomerAlert();
                $('#addAddressModal').modal('show');
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
                resetQuickAddressModalAlert();
                if (!customerId) {
                    renderBookingCustomerAlert('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }

                let route = '{{ route('admin.customer.address-quick-store', ['id' => ':id']) }}';
                route = route.replace(':id', customerId);

                $.ajax({
                    url: route,
                    method: 'POST',
                    data: $form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        resetBookingCustomerAlert();
                        resetQuickAddressModalAlert();
                        const option = new Option(
                            response.label + ' - ' + response.full_address,
                            response.id,
                            true,
                            true
                        );
                        $addressSelect.append(option).trigger('change');
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
                advance_paid_amount: @json(old('advance_paid_amount', request('advance_paid_amount'))) || getUrlParameter('advance_paid_amount'),
                assignee_id: @json(old('assignee_id', request('assignee_id'))) || getUrlParameter('assignee_id')
            };

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
            var ajaxBookingSubcatProvidersGen = 0;
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
            var currencySymbol = $('#booking-form').data('currency') || '';
            function formatPrice(price) {
                if (price == null || price === '') return '—';
                var n = parseFloat(price);
                return isNaN(n) ? '—' : (currencySymbol + ' ' + n.toFixed(2));
            }
            function updateDueBalance() {
                var advance = parseFloat($('#advance-paid-amount').val()) || 0;
                if (currentBillingTotal != null && currentBillingTotal >= 0) {
                    if (advance > currentBillingTotal) {
                        $('#advance-paid-amount').val(currentBillingTotal.toFixed(2));
                        advance = currentBillingTotal;
                    }
                    var due = Math.max(0, currentBillingTotal - advance);
                    $('#due-balance-amount').text(formatPrice(due));
                    $('#due-balance-row').toggle(due >= 0 && currentBillingTotal > 0).show();
                } else {
                    $('#due-balance-row').hide();
                }
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
                if (!zoneId) {
                    ajaxBookingZoneGen++;
                    toggleServiceControls(0);
                    resetBookingServiceInfoAlert();
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
                if (!categoryId) {
                    ajaxBookingCategoryGen++;
                    toggleServiceControls(1);
                    resetBookingServiceInfoAlert();
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
                                $variantSelect.append(
                                    new Option(label, variation.variant_key, false, false)
                                );
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

            function fetchBillingSummary() {
                var variantKey = $variantSelect.val();
                var serviceId = $serviceSelect.val();
                var zoneId = $zoneSelect.val();
                if (!variantKey || !serviceId || !zoneId) return;
                var extraFee = 0;
                if ($('#extra-fee-input').length) {
                    extraFee = parseFloat($('#extra-fee-input').val()) || 0;
                }
                var billingRoute = '{{ route('admin.booking.service.ajax-get-billing-summary') }}';
                $.get(billingRoute, { zone_id: zoneId, service_id: serviceId, variant_key: variantKey, quantity: 1, extra_fee: extraFee }, function (res) {
                    if (res.content && res.content.total_cost != null) {
                        currentBillingTotal = parseFloat(res.content.total_cost);
                        $('#billing-service-charges').text(formatPrice(res.content.service_cost));
                        $('#billing-discount').text('-' + formatPrice(res.content.total_discount_amount));
                        var taxAmt = parseFloat(res.content.tax_amount) || 0;
                        if (taxAmt > 0) {
                            $('#billing-tax-row').show();
                            $('#billing-tax').text(formatPrice(res.content.tax_amount));
                        } else {
                            $('#billing-tax-row').hide();
                        }
                        $('#billing-total').text(formatPrice(res.content.total_cost));
                        $('#billing-summary-box').show();
                        $('#advance-paid-amount').attr('max', currentBillingTotal);
                        updateDueBalance();
                    }
                });
            }

            // When variant is selected, fetch and show billing summary
            $variantSelect.on('change', function () {
                var variantKey = $(this).val();
                var serviceId = $serviceSelect.val();
                var zoneId = $zoneSelect.val();
                $('#billing-summary-box').hide();
                currentBillingTotal = null;
                $('#advance-paid-amount').removeAttr('max');
                $('#due-balance-row').hide();

                if (!variantKey || !serviceId || !zoneId) return;

                fetchBillingSummary();
            });

            $('#advance-paid-amount').on('input change', function () {
                updateDueBalance();
            });

            $('#extra-fee-input').on('input change', function () {
                if ($variantSelect.val() && $serviceSelect.val() && $zoneSelect.val()) {
                    fetchBillingSummary();
                }
            });

            // Provider section
            const $providerSelect = $('#provider-select');
            const $serviceLocationSection = $('#service-location-section');
            const $serviceLocationCustomer = $('#service-location-customer');
            const $serviceLocationProvider = $('#service-location-provider');
            const $serviceLocationTextRow = $('input[name="service_location"]').closest('.row');

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

            // Load providers when subcategory changes
            $subCategorySelect.on('change', function () {
                const subCategoryId = $(this).val();
                if (!subCategoryId) {
                    ajaxBookingSubcatProvidersGen++;
                    resetBookingServiceInfoAlert();
                    $providerSelect.prop('disabled', true);
                    $providerSelect.empty().append(
                        new Option('{{ translate('Select_Provider') }}', '', true, true)
                    );
                    if ($providerSelect.hasClass('select2-hidden-accessible')) {
                        $providerSelect.select2('destroy');
                        $providerSelect.select2();
                    }
                    return;
                }

                serviceInfoAlertState.providers = null;
                serviceInfoAlertState.providerLoadError = null;
                renderBookingServiceInfoAlert();

                var subcatProvidersGen = ++ajaxBookingSubcatProvidersGen;
                $providerSelect.prop('disabled', false);
                
                // Destroy and reinitialize Select2 with templates BEFORE adding options
                if ($providerSelect.hasClass('select2-hidden-accessible')) {
                    $providerSelect.select2('destroy');
                }
                
                $providerSelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );
                
                // Initialize Select2 with templates
                initializeProviderSelect2();

                let route = '{{ route('admin.booking.service.ajax-get-providers') }}';
                $.get(route, {sub_category_id: subCategoryId}, function (response) {
                    if (subcatProvidersGen !== ajaxBookingSubcatProvidersGen) {
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
                                const option = $('<option></option>')
                                    .attr('value', provider.id)
                                    .attr('data-company-name', provider.company_name || '')
                                    .attr('data-contact-name', provider.contact_person_name || '')
                                    .attr('data-contact-phone', provider.contact_person_phone || '')
                                    .text(provider.company_name || 'Provider #' + provider.id);
                                $providerSelect.append(option);
                            });
                            
                            // Destroy and reinitialize Select2 to refresh with new options
                            $providerSelect.select2('destroy');
                            initializeProviderSelect2();
                            
                            // Show service location section when providers are loaded
                            $serviceLocationSection.show();
                            var wantProv = oldValues.provider_id;
                            var provMatched = wantProv && response.content.some(function (p) {
                                return String(p.id) === String(wantProv);
                            });
                            if (provMatched) {
                                $providerSelect.val(String(wantProv)).trigger('change');
                            } else if (response.content.length === 1) {
                                $providerSelect.val(response.content[0].id).trigger('change');
                            }
                        } else {
                            serviceInfoAlertState.providers = '{{ translate('No_providers_found_for_this_subcategory') }}';
                            renderBookingServiceInfoAlert();
                            $serviceLocationSection.hide();
                        }
                    }
                }).fail(function(xhr) {
                    if (subcatProvidersGen !== ajaxBookingSubcatProvidersGen) {
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
            });
            
            // Handle provider selection change
            $providerSelect.on('change', function() {
                const providerId = $(this).val();
                if (providerId) {
                    $serviceLocationSection.show();
                } else {
                    $serviceLocationSection.hide();
                }
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
                        }
                    }
                } else {
                    // Hide address fields for provider location
                    $addressRow.hide();
                    $addressSelect.val('').prop('disabled', true);
                    $addAddressBtn.prop('disabled', true);
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
                if (!$subCategorySelect.val()) {
                    pushError('{{ translate('Sub_Category') }}', req, $subCategorySelect);
                }
                if (!$serviceSelect.val()) {
                    pushError('{{ translate('Service') }}', req, $serviceSelect);
                }
                if (!$variantSelect.val()) {
                    pushError('{{ translate('Select_Service_Variant') }}', req, $variantSelect);
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
                    var txnId = ($('#advance-transaction-id').val() || '').trim();
                    if (!txnId) {
                        pushError('{{ translate('Advance_Payment_Transaction_ID') }}', '{{ translate('Transaction_ID_is_required_when_advance_paid_is_greater_than_zero') }}', $('#advance-transaction-id'));
                    }
                }

                if ($('#extra-fee-input').length) {
                    var feeRaw = ($('#extra-fee-input').val() || '').trim();
                    if (feeRaw !== '') {
                        var fee = parseFloat(feeRaw);
                        if (isNaN(fee) || fee < 0) {
                            pushError(@json($additionalChargeLabel), @json(translate('Enter_a_valid_non_negative_number')), $('#extra-fee-input'));
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
                $(this).find('button[type="submit"], input[type="submit"]').prop('disabled', true);
                this.submit();
            });
        });
    </script>
@endpush

