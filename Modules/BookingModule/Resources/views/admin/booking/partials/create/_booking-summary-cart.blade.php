{{-- Booking summary + edit-services / extra-services modals for admin create booking --}}
@php
    $createCartJsonPrefill = old('booking_create_cart_json', request('booking_create_cart_json', ''));
    $createExtrasJsonPrefill = old('booking_create_extra_services_json', request('booking_create_extra_services_json', ''));
    $createServiceQtyPrefill = old('service_quantity', request('service_quantity', 1));
    $createAcSeed = old('ac_line_amount', request('ac_line_amount', []));
    if (! is_array($createAcSeed)) {
        $createAcSeed = [];
    }
@endphp
<input type="hidden" name="booking_create_cart_json" id="booking-create-cart-json" value="{{ $createCartJsonPrefill }}">
<input type="hidden" name="booking_create_extra_services_json" id="booking-create-extra-services-json" value="{{ $createExtrasJsonPrefill }}">
<input type="hidden" name="service_quantity" id="service-quantity-field" value="{{ $createServiceQtyPrefill }}">
<div id="booking-create-ac-seed" class="d-none" aria-hidden="true">
    @foreach($createAcSeed as $acTypeId => $acAmt)
        <input type="hidden" class="js-ac-seed" data-ac-type-id="{{ $acTypeId }}" value="{{ $acAmt }}">
    @endforeach
</div>

<div class="mb-4 border rounded-3 p-3" id="booking-create-summary-section">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2" id="booking-create-summary-header-row">
        <h4 class="mb-0">{{ translate('Booking_Summary') }}</h4>
        <div class="d-flex flex-wrap gap-2 d-none" id="booking-create-summary-actions">
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" id="btn-open-create-service-modal">
                <span class="material-symbols-outlined align-middle" style="font-size: 18px;">edit</span>
                {{ translate('Edit Services') }}
            </button>
            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" id="btn-open-create-extra-modal"
                    data-bs-toggle="modal" data-bs-target="#addExtraServiceModal--create">
                <span class="material-symbols-outlined align-middle" style="font-size: 18px;">add</span>
                {{ translate('Add_Extra_Service') }}
            </button>
        </div>
    </div>
    <div id="booking-create-service-modal-error" class="text-danger small mb-3 d-none" role="alert"></div>

    <div id="booking-create-summary-empty-cta" class="text-center py-5 px-3 border rounded bg-light">
        <p class="text-muted mb-3" id="booking-create-summary-empty-cta-text">{{ translate('Booking_summary_no_services_yet') }}</p>
        <button type="button" class="btn btn-primary btn-lg" id="btn-booking-create-add-service">
            <span class="material-symbols-outlined align-middle" style="font-size: 22px;">add</span>
            {{ translate('Add Service') }}
        </button>
        <div id="booking-create-add-service-error" class="text-danger small mt-3 d-none" role="alert"></div>
    </div>

    <div class="table-responsive border rounded d-none" id="booking-create-summary-table-wrap">
        <table class="table text-nowrap align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th class="ps-3">{{ translate('Service') }}</th>
                <th>{{ translate('Price') }}</th>
                <th>{{ translate('Qty') }}</th>
                <th>{{ translate('Discount') }}</th>
                <th id="booking-create-summary-tax-head" class="d-none">{{ company_default_tax_label() }}</th>
                <th class="text-end pe-3">{{ translate('Total') }}</th>
            </tr>
            </thead>
            <tbody id="booking-create-summary-tbody">
            <tr id="booking-create-summary-empty">
                <td colspan="6" class="text-muted text-center py-4"></td>
            </tr>
            </tbody>
        </table>
    </div>
    <p class="small text-muted mb-0 mt-2" id="booking-create-summary-hint"></p>
</div>

{{-- Update booking list modal (create flow — no booking id) --}}
<div class="modal fade" id="serviceUpdateModal--create" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title text-capitalize mb-0">{{ translate('update_booking_list') }}</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">{{ translate('Add_or_remove_services') }}</p>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">{{ translate('category') }}</label>
                        <select class="form-control" id="create-modal-category"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">{{ translate('Sub_Category') }}</label>
                        <select class="form-control" id="create-modal-subcategory"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">{{ translate('service') }}</label>
                        <select class="form-control" id="create-modal-service"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">{{ translate('variant') }}</label>
                        <select class="form-control" id="create-modal-variant"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">{{ translate('service_quantity') }}</label>
                        <input type="number" class="form-control" id="create-modal-qty" min="1" step="1" value="1">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" id="create-modal-add-line" disabled>{{ translate('Add Service') }}</button>
                    </div>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table text-nowrap align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>{{ translate('Service') }}</th>
                            <th>{{ translate('variant') }}</th>
                            <th>{{ translate('Price') }}</th>
                            <th>{{ translate('Qty') }}</th>
                            <th>{{ translate('Discount') }}</th>
                            <th>{{ translate('Who_bears_discount_default_no_one') }}</th>
                            <th>{{ translate('Total') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody id="create-modal-lines-tbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="create-modal-save-cart">{{ translate('update_cart') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addExtraServiceModal--create" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Add_Extra_Service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="create-extra-title" maxlength="255" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ translate('Details_of_Service') }}</label>
                    <textarea class="form-control" id="create-extra-details" rows="2" maxlength="2000"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
                    <select class="form-control" id="create-extra-type">
                        <option value="service">{{ translate('Service') }}</option>
                        <option value="spare_part">{{ translate('Spare_Part') }}</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('Qty') }} <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="create-extra-qty" min="1" step="1" value="1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="create-extra-price" min="0" step="0.01" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ translate('Discount') }}</label>
                        <input type="number" class="form-control" id="create-extra-discount" min="0" step="0.01" value="0">
                    </div>
                </div>
                <p class="small text-muted mb-0">{{ translate('Total') }} = ({{ translate('Qty') }} × {{ translate('Price') }}) − {{ translate('Discount') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="create-extra-add-btn">{{ translate('Add') }}</button>
            </div>
        </div>
    </div>
</div>
