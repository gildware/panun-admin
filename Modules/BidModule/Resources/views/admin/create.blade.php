@extends('adminmodule::layouts.master')

@section('title', translate('Add_New_Bidding'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">{{ translate('Add_New_Bidding') }}</h2>
            <a href="{{ route('admin.booking.post.list', ['type' => 'all']) }}" class="btn btn-secondary">
                {{ translate('Back_to_Customized_Requests') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.booking.post.preview') }}" method="POST" id="bidding-form">
                    @csrf

                    {{-- 1. Customer --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Customer_information') }}</h4>
                        <div class="row">
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Customer') }} *</label>
                                    <select name="customer_id" class="form-control js-select" id="customer-select" required>
                                        <option value="">{{ translate('Select_Customer') }}</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->first_name }} {{ $customer->last_name }} - {{ $customer->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')<span class="text-danger">{{ $message }}</span>@enderror
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
                            $bidCanEditCustomerAddress = auth()->user()->can('customer_add') || auth()->user()->can('customer_update');
                        @endphp
                        <div class="row">
                            <div class="col-md-{{ $bidCanEditCustomerAddress ? 8 : 10 }}">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Service_Address') }} *</label>
                                    <select name="service_address_id" id="customer-address-select" class="form-control js-select" disabled required>
                                        <option value="">{{ translate('Select_Address') }}</option>
                                    </select>
                                    @error('service_address_id')<span class="text-danger">{{ $message }}</span>@enderror
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
                            @if($bidCanEditCustomerAddress)
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
                    </div>

                    {{-- 2. Zone, Category, Sub Category, Service --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Service_information') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Zone') }} *</label>
                                    <select name="zone_id" id="bidding-zone-select" class="form-control js-select" required>
                                        <option value="">{{ translate('Select_Zone') }}</option>
                                        @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}" {{ old('zone_id', request('zone_id')) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('zone_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Category') }} *</label>
                                    <select name="category_id" id="bidding-category-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select_Category') }}</option>
                                    </select>
                                    @error('category_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Sub_Category') }} *</label>
                                    <select name="sub_category_id" id="bidding-subcategory-select" class="form-control js-select" required disabled>
                                        <option value="">{{ translate('Select_Sub_Category') }}</option>
                                    </select>
                                    @error('sub_category_id')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Service') }} ({{ translate('optional') }})</label>
                                    <select name="service_id" id="bidding-service-select" class="form-control js-select" disabled>
                                        <option value="">{{ translate('Select_Service_or_leave_for_custom') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Custom_Service_Description') }} *</label>
                                    <textarea name="service_description" class="form-control" rows="4" required placeholder="{{ translate('Describe_the_service_needed_in_detail') }}">{{ old('service_description', request('service_description')) }}</textarea>
                                    @error('service_description')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row" id="additional-instructions-row">
                            <div class="col-md-12">
                                <label class="form-label">{{ translate('Additional_Instructions') }} ({{ translate('optional') }})</label>
                                <div id="additional-instructions-container">
                                    @foreach(array_filter((array)old('additional_instructions', request('additional_instructions', []))) as $instruction)
                                        <div class="input-group mb-2">
                                            <input type="text" name="additional_instructions[]" class="form-control" placeholder="{{ translate('Add_instruction') }}" value="{{ is_string($instruction) ? e($instruction) : '' }}">
                                            <button type="button" class="btn btn-outline-danger btn-remove-instruction">−</button>
                                        </div>
                                    @endforeach
                                    <div class="input-group mb-2">
                                        <input type="text" name="additional_instructions[]" class="form-control" placeholder="{{ translate('Add_instruction') }}">
                                        <button type="button" class="btn btn-outline-secondary btn-add-instruction" title="{{ translate('Add_another') }}">+</button>
                                    </div>
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
                                    <label class="form-label">{{ translate('Service_Schedule') }} *</label>
                                    <input type="datetime-local" name="booking_schedule" class="form-control" value="{{ old('booking_schedule', request('booking_schedule')) }}" required>
                                    @error('booking_schedule')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Source --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Source') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Booking_Source') }} *</label>
                                    <select name="booking_source" class="form-control" required>
                                        <option value="">{{ translate('Select_Source') }}</option>
                                        <option value="whatsapp" {{ old('booking_source', request('booking_source')) == 'whatsapp' ? 'selected' : '' }}>{{ translate('Whatsapp') }}</option>
                                        <option value="call" {{ old('booking_source', request('booking_source')) == 'call' ? 'selected' : '' }}>{{ translate('Call') }}</option>
                                        <option value="social_media" {{ old('booking_source', request('booking_source')) == 'social_media' ? 'selected' : '' }}>{{ translate('Social_Media') }}</option>
                                    </select>
                                    @error('booking_source')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Assignment --}}
                    <div class="mb-4 border rounded-3 p-3">
                        <h4 class="mb-3">{{ translate('Assignment') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('Assignee') }}</label>
                                    <select name="assignee_id" class="form-control js-select">
                                        <option value="">{{ translate('Select_Assignee') }}</option>
                                        @foreach($assignees as $assignee)
                                            <option value="{{ $assignee->id }}" {{ old('assignee_id', request('assignee_id', auth()->id())) == $assignee->id ? 'selected' : '' }}>
                                                {{ $assignee->first_name }} {{ $assignee->last_name }} ({{ $assignee->user_type === 'super-admin' ? translate('Admin') : translate('Employee') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">{{ translate('Continue_to_Preview') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add New Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
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
                        <p class="text-muted mb-0">{{ translate('A_default_password_will_be_set_for_this_customer_and_they_can_change_it_later.') }}</p>
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
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">{{ translate('Add_Customer_Address') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
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

            const $customerSelect = $('#customer-select');
            const $addressSelect = $('#customer-address-select');
            const $addAddressBtn = $('#open-add-address');
            const $editAddressBtn = $('#open-edit-address');
            const $quickAddressForm = $('#quick-address-form');

            function bidToastError(msg) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                }
            }

            function syncBidEditAddressButtonState() {
                if (!$editAddressBtn.length) {
                    return;
                }
                if ($addressSelect.prop('disabled') || !$addressSelect.val()) {
                    $editAddressBtn.prop('disabled', true);
                } else {
                    $editAddressBtn.prop('disabled', false);
                }
            }

            $('#addAddressModal').on('hidden.bs.modal', function () {
                const f = $('#quick-address-form')[0];
                if (f) {
                    f.reset();
                }
                $('#quick-address-edit-id').val('');
                $('#addAddressModalLabel').text('{{ translate('Add_Customer_Address') }}');
            });

            $('#open-add-customer').on('click', function () { $('#addCustomerModal').modal('show'); });

            $('#open-add-address').on('click', function () {
                if (!$customerSelect.val()) {
                    bidToastError('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                $('#quick-address-edit-id').val('');
                $('#addAddressModalLabel').text('{{ translate('Add_Customer_Address') }}');
                if ($quickAddressForm[0]) {
                    $quickAddressForm[0].reset();
                }
                $('#addAddressModal').modal('show');
            });

            $editAddressBtn.on('click', function () {
                const customerId = $customerSelect.val();
                const addressId = $addressSelect.val();
                if (!customerId) {
                    bidToastError('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                if (!addressId) {
                    bidToastError('{{ translate('Select_Address') }}');
                    return;
                }
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
                    bidToastError(xhr.status === 404 ? '{{ translate('not_found') }}' : '{{ translate('Something_went_wrong') }}');
                });
            });

            $addressSelect.on('change', function () {
                syncBidEditAddressButtonState();
            });

            $customerSelect.on('change', function () {
                const customerId = $(this).val();
                $addressSelect.empty().append(new Option('{{ translate('Select_Address') }}', '', true, true));
                if (!customerId) {
                    $addressSelect.prop('disabled', true);
                    $addAddressBtn.prop('disabled', true);
                    if ($editAddressBtn.length) {
                        $editAddressBtn.prop('disabled', true);
                    }
                    return;
                }
                $addressSelect.prop('disabled', false);
                $addAddressBtn.prop('disabled', false);
                const route = '{{ route('admin.customer.addresses', ['id' => ':id']) }}'.replace(':id', customerId);
                $.get(route, function (addresses) {
                    addresses.forEach(function (addr) {
                        const text = (addr.address_label ? addr.address_label + ' - ' : '') + (addr.address || '');
                        $addressSelect.append(new Option(text, addr.id, false, false));
                    });
                }).always(function () {
                    syncBidEditAddressButtonState();
                });
            });
            if ($customerSelect.val()) $customerSelect.trigger('change');

            $('#save-customer-btn').on('click', function () {
                const $form = $('#quick-customer-form');
                $.ajax({
                    url: '{{ route('admin.customer.quick-store') }}',
                    method: 'POST',
                    data: $form.serialize(),
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function (response) {
                        const newOption = new Option(response.name + ' - ' + response.phone, response.id, true, true);
                        $customerSelect.append(newOption).trigger('change');
                        $('#addCustomerModal').modal('hide');
                        $form[0].reset();
                    },
                    error: function (xhr) {
                        if (typeof toastr === 'undefined') return;
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            let messages = [];
                            Object.values(xhr.responseJSON.errors).forEach(function (errs) { messages = messages.concat(errs); });
                            toastr.error(messages.join(' '));
                        } else {
                            toastr.error('{{ translate('Something_went_wrong') }}');
                        }
                    }
                });
            });

            $('#save-address-btn').on('click', function () {
                const $form = $('#quick-address-form');
                const customerId = $customerSelect.val();
                const editId = $('#quick-address-edit-id').val();
                if (!customerId) {
                    bidToastError('{{ translate('Please_select_a_customer_first') }}');
                    return;
                }
                let route;
                let payload;
                if (editId) {
                    route = '{{ route('admin.customer.address-quick-update', ['id' => '__CID__', 'addressId' => '__AID__']) }}';
                    route = route.replace('__CID__', encodeURIComponent(customerId)).replace('__AID__', encodeURIComponent(editId));
                    payload = $form.serialize() + '&_method=PUT';
                } else {
                    route = '{{ route('admin.customer.address-quick-store', ['id' => ':id']) }}'.replace(':id', customerId);
                    payload = $form.serialize();
                }
                $.ajax({
                    url: route,
                    method: 'POST',
                    data: payload,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function (response) {
                        if (editId) {
                            const summaryLine = (response.label ? response.label + ' - ' : '') +
                                ($form.find('textarea[name="address"]').val() || '');
                            const $opt = $addressSelect.find('option[value="' + response.id + '"]');
                            if ($opt.length) {
                                $opt.text(summaryLine);
                            }
                            $addressSelect.val(String(response.id)).trigger('change');
                        } else {
                            const option = new Option(response.label + ' - ' + response.full_address, response.id, true, true);
                            $addressSelect.append(option).trigger('change');
                        }
                        $('#addAddressModal').modal('hide');
                        $form[0].reset();
                    },
                    error: function (xhr) {
                        if (typeof toastr === 'undefined') return;
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            let messages = [];
                            Object.values(xhr.responseJSON.errors).forEach(function (errs) { messages = messages.concat(errs); });
                            toastr.error(messages.join(' '));
                        } else {
                            toastr.error('{{ translate('Something_went_wrong') }}');
                        }
                    }
                });
            });

            // Zone -> Category -> SubCategory -> Service
            const $zoneSelect = $('#bidding-zone-select');
            const $categorySelect = $('#bidding-category-select');
            const $subCategorySelect = $('#bidding-subcategory-select');
            const $serviceSelect = $('#bidding-service-select');

            function loadCategories() {
                const zoneId = $zoneSelect.val();
                if (!zoneId) {
                    $categorySelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Category') }}', '', true, true));
                    $subCategorySelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    $serviceSelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                    return;
                }
                $categorySelect.prop('disabled', false).empty().append(new Option('{{ translate('Loading...') }}', '', true, true));
                $.get('{{ route('admin.booking.service.ajax-get-categories') }}', { zone_id: zoneId }, function (res) {
                    $categorySelect.empty().append(new Option('{{ translate('Select_Category') }}', '', true, true));
                    (res.content || []).forEach(function (c) { $categorySelect.append(new Option(c.name, c.id, false, false)); });
                });
                $subCategorySelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                $serviceSelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
            }

            $categorySelect.on('change', function () {
                const categoryId = $(this).val();
                if (!categoryId) {
                    $subCategorySelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    $serviceSelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                    return;
                }
                $subCategorySelect.prop('disabled', false).empty().append(new Option('{{ translate('Loading...') }}', '', true, true));
                $.get('{{ route('admin.booking.service.ajax-get-subcategories') }}', { category_id: categoryId }, function (res) {
                    $subCategorySelect.empty().append(new Option('{{ translate('Select_Sub_Category') }}', '', true, true));
                    (res.content || []).forEach(function (c) { $subCategorySelect.append(new Option(c.name, c.id, false, false)); });
                });
                $serviceSelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
            });

            $subCategorySelect.on('change', function () {
                const subCategoryId = $(this).val();
                const zoneId = $zoneSelect.val();
                if (!subCategoryId || !zoneId) {
                    $serviceSelect.prop('disabled', true).empty().append(new Option('{{ translate('Select_Service') }}', '', true, true));
                    return;
                }
                $serviceSelect.prop('disabled', false).empty().append(new Option('{{ translate('Loading...') }}', '', true, true));
                $.get('{{ route('admin.booking.service.ajax-get-services') }}', { sub_category_id: subCategoryId, zone_id: zoneId }, function (res) {
                    $serviceSelect.empty().append(new Option('{{ translate('Select_Service_or_leave_for_custom') }}', '', true, true));
                    (res.content || []).forEach(function (c) { $serviceSelect.append(new Option(c.name, c.id, false, false)); });
                });
            });

            $zoneSelect.on('change', loadCategories);
            if ($zoneSelect.val()) loadCategories();

            // Additional instructions
            $(document).on('click', '.btn-add-instruction', function () {
                const html = '<div class="input-group mb-2"><input type="text" name="additional_instructions[]" class="form-control" placeholder="{{ translate('Add_instruction') }}"><button type="button" class="btn btn-outline-danger btn-remove-instruction">−</button></div>';
                $('#additional-instructions-container').append(html);
            });
            $(document).on('click', '.btn-remove-instruction', function () { $(this).closest('.input-group').remove(); });
        });
    </script>
@endpush
