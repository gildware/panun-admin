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
                <form action="{{ route('admin.booking.preview') }}" method="POST" id="booking-form"
                      data-currency="{{ currency_symbol() ?? '' }}">
                    @csrf

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
                                                {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>
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
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}"
                                                {{ old('zone_id') == $zone->id ? 'selected' : '' }}>
                                                {{ $zone->name }}
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
                                        <option value="app" {{ old('booking_source', request('booking_source')) == 'app' ? 'selected' : '' }}>
                                            {{ translate('App_(Default_for_API)') }}
                                        </option>
                                        <option value="call" {{ old('booking_source', request('booking_source')) == 'call' ? 'selected' : '' }}>
                                            {{ translate('Call') }}
                                        </option>
                                        <option value="whatsapp" {{ old('booking_source', request('booking_source')) == 'whatsapp' ? 'selected' : '' }}>
                                            {{ translate('Whatsapp') }}
                                        </option>
                                        <option value="social_media" {{ old('booking_source', request('booking_source')) == 'social_media' ? 'selected' : '' }}>
                                            {{ translate('Social_Media') }}
                                        </option>
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
                                <tr>
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
                                           class="form-control" value="{{ old('advance_paid_amount', request('advance_paid_amount', 0)) }}"
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
                                    <label class="form-label">{{ translate('Advance_Payment_Transaction_ID_(Optional)') }}</label>
                                    <input type="text" name="advance_transaction_id" class="form-control"
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
                                    <label class="form-label">{{ translate('email') }}</label>
                                    <input type="email" name="email" class="form-control" required>
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

            const $customerSelect = $('#customer-select');
            const $addressSelect = $('#customer-address-select');
            const $addAddressBtn = $('#open-add-address');

            // Function to enable/disable address controls
            function toggleAddressControls(enabled) {
                $addressSelect.prop('disabled', !enabled);
                $addAddressBtn.prop('disabled', !enabled);
            }

            // Initially disable address controls
            toggleAddressControls(false);

            // If customer is pre-selected (e.g., from validation errors), enable address controls
            if ($customerSelect.val()) {
                $customerSelect.trigger('change');
            }

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
                    alert('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                $('#addAddressModal').modal('show');
            });

            $('#save-customer-btn').on('click', function () {
                const $form = $('#quick-customer-form');
                const formData = $form.serialize();

                $.ajax({
                    url: '{{ route('admin.customer.quick-store') }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function (response) {
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
                            alert(messages.join('\n'));
                        } else {
                            alert('{{ translate('Something_went_wrong') }}');
                        }
                    }
                });
            });

            $('#save-address-btn').on('click', function () {
                const $form = $('#quick-address-form');
                const customerId = $customerSelect.val();
                if (!customerId) {
                    alert('{{ translate('Please_select_a_customer_first') }}');
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
                            alert(messages.join('\n'));
                        } else {
                            alert('{{ translate('Something_went_wrong') }}');
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
                    toggleServiceControls(0);
                    return;
                }

                toggleServiceControls(0);
                $categorySelect.prop('disabled', false);
                $categorySelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );
                
                reinitializeSelect2($categorySelect);

                let route = '{{ route('admin.booking.service.ajax-get-categories') }}';
                $.get(route, {zone_id: zoneId}, function (response) {
                    $categorySelect.empty().append(
                        new Option('{{ translate('Select_Category') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            response.content.forEach(function (category) {
                                $categorySelect.append(
                                    new Option(category.name, category.id, false, false)
                                );
                            });
                            reinitializeSelect2($categorySelect);
                        } else {
                            alert('{{ translate('No_categories_found_for_this_zone') }}');
                        }
                    }
                }).fail(function(xhr) {
                    console.error('Failed to load categories:', xhr);
                    $categorySelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    alert('{{ translate('Failed_to_load_categories') }}');
                });
            });

            // Load subcategories when category changes
            $categorySelect.on('change', function () {
                const categoryId = $(this).val();
                if (!categoryId) {
                    toggleServiceControls(1);
                    return;
                }

                toggleServiceControls(1);
                $subCategorySelect.prop('disabled', false);
                $subCategorySelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );
                
                reinitializeSelect2($subCategorySelect);

                let route = '{{ route('admin.booking.service.ajax-get-subcategories') }}';
                $.get(route, {category_id: categoryId}, function (response) {
                    $subCategorySelect.empty().append(
                        new Option('{{ translate('Select_Sub_Category') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            response.content.forEach(function (subCategory) {
                                $subCategorySelect.append(
                                    new Option(subCategory.name, subCategory.id, false, false)
                                );
                            });
                            reinitializeSelect2($subCategorySelect);
                        } else {
                            alert('{{ translate('No_subcategories_found_for_this_category') }}');
                        }
                    }
                }).fail(function(xhr) {
                    console.error('Failed to load subcategories:', xhr);
                    $subCategorySelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    alert('{{ translate('Failed_to_load_subcategories') }}');
                });
            });

            // Load services when subcategory changes
            $subCategorySelect.on('change', function () {
                const subCategoryId = $(this).val();
                if (!subCategoryId) {
                    toggleServiceControls(2);
                    return;
                }

                toggleServiceControls(2);
                $serviceSelect.prop('disabled', false);
                $serviceSelect.empty().append(
                    new Option('{{ translate('Loading...') }}', '', true, true)
                );

                reinitializeSelect2($serviceSelect);

                let route = '{{ route('admin.booking.service.ajax-get-services') }}';
                $.get(route, {sub_category_id: subCategoryId}, function (response) {
                    $serviceSelect.empty().append(
                        new Option('{{ translate('Select_Service') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            response.content.forEach(function (service) {
                                $serviceSelect.append(
                                    new Option(service.name, service.id, false, false)
                                );
                            });
                            reinitializeSelect2($serviceSelect);
                        } else {
                            alert('{{ translate('No_services_found_for_this_subcategory') }}');
                        }
                    }
                }).fail(function(xhr) {
                    console.error('Failed to load services:', xhr);
                    $serviceSelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    alert('{{ translate('Failed_to_load_services') }}');
                });
            });

            // Load variants when service changes
            $serviceSelect.on('change', function () {
                const serviceId = $(this).val();
                const zoneId = $zoneSelect.val();

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
                    $variantSelect.empty().append(
                        new Option('{{ translate('Select Service Variant') }}', '', true, true)
                    );

                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
                            response.content.forEach(function (variation) {
                                var label = variation.variant + ' — ' + formatPrice(variation.price);
                                $variantSelect.append(
                                    new Option(label, variation.variant_key, false, false)
                                );
                            });
                            reinitializeSelect2($variantSelect);
                            toggleServiceControls(4);
                        } else {
                            alert('{{ translate('No_services_found_for_this_subcategory') }}');
                        }
                    }
                }).fail(function(xhr) {
                    console.error('Failed to load variants:', xhr);
                    $variantSelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    alert('{{ translate('Failed_to_load_services') }}');
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
                        $('#billing-tax').text(formatPrice(res.content.tax_amount));
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
            const $serviceLocationHidden = $('#service-location-hidden');
            const $addressRow = $('#customer-address-select').closest('.row');
            const $addressAddButton = $('#open-add-address');
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
                    $providerSelect.empty().append(
                        new Option('{{ translate('Select_Provider') }}', '', true, true)
                    );
                    
                    if (response.content && Array.isArray(response.content)) {
                        if (response.content.length > 0) {
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
                        } else {
                            alert('{{ translate('No_providers_found_for_this_subcategory') }}');
                            $serviceLocationSection.hide();
                        }
                    }
                }).fail(function(xhr) {
                    console.error('Failed to load providers:', xhr);
                    $providerSelect.empty().append(
                        new Option('{{ translate('Failed_to_load') }}', '', true, true)
                    );
                    alert('{{ translate('Failed_to_load_providers') }}');
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
                            $addressAddButton.prop('disabled', false);
                        }
                    }
                } else {
                    // Hide address fields for provider location
                    $addressRow.hide();
                    $addressSelect.val('').prop('disabled', true);
                    $addressAddButton.prop('disabled', true);
                }
            });
            

            // Restore old values from validation errors or query parameters
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                const results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }

            const oldValues = {
                customer_id: @json(old('customer_id')) || getUrlParameter('customer_id'),
                zone_id: @json(old('zone_id')) || getUrlParameter('zone_id'),
                category_id: @json(old('category_id')) || getUrlParameter('category_id'),
                sub_category_id: @json(old('sub_category_id')) || getUrlParameter('sub_category_id'),
                service_id: @json(old('service_id')) || getUrlParameter('service_id'),
                variant_key: @json(old('variant_key')) || getUrlParameter('variant_key'),
                provider_id: @json(old('provider_id')) || getUrlParameter('provider_id'),
                service_address_id: @json(old('service_address_id')) || getUrlParameter('service_address_id'),
                service_location: @json(old('service_location')) || getUrlParameter('service_location') || 'customer',
                service_schedule: @json(old('service_schedule')) || getUrlParameter('service_schedule'),
                advance_paid_amount: @json(old('advance_paid_amount')) || getUrlParameter('advance_paid_amount'),
                assignee_id: @json(old('assignee_id')) || getUrlParameter('assignee_id')
            };
            
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

            // Restore customer selection
            if (oldValues.customer_id) {
                $customerSelect.val(oldValues.customer_id).trigger('change');
            }

            // Restore zone and cascade
            if (oldValues.zone_id) {
                $zoneSelect.val(oldValues.zone_id).trigger('change');
                
                // Wait for categories to load, then restore category
                setTimeout(function() {
                    if (oldValues.category_id) {
                        $categorySelect.val(oldValues.category_id).trigger('change');
                        
                        // Wait for subcategories to load, then restore subcategory
                        setTimeout(function() {
                            if (oldValues.sub_category_id) {
                                $subCategorySelect.val(oldValues.sub_category_id).trigger('change');
                                
                                // Wait for services to load, then restore service
                                setTimeout(function() {
                                    if (oldValues.service_id) {
                                        $serviceSelect.val(oldValues.service_id).trigger('change');
                                    }
                                    
                                    // Wait for providers to load, then restore provider
                                    setTimeout(function() {
                                        if (oldValues.provider_id) {
                                            $providerSelect.val(oldValues.provider_id).trigger('change');
                                        }
                                    }, 500);
                                }, 500);
                            }
                        }, 500);
                    }
                }, 500);
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

            // Restore variant selection (after services & variants are loaded)
            if (oldValues.variant_key) {
                setTimeout(function () {
                    $('#service-variant-select').val(oldValues.variant_key).trigger('change');
                }, 1500);
            }

            // Restore assignee selection
            if (oldValues.assignee_id) {
                $('#assignee-select').val(oldValues.assignee_id).trigger('change');
            }
        });
    </script>
@endpush

