@can('booking_edit')
    @php
        $visitCartPrefill = [];
        $sourceDetails = $booking->detail ?? collect();
        if (count($sourceDetails) === 0) {
            $firstRepeat = $booking->repeat->first() ?? null;
            $sourceDetails = $firstRepeat ? ($firstRepeat->detail ?? collect()) : collect();
        }
        foreach ($sourceDetails as $detailRow) {
            $svc = $detailRow->service ?? null;
            $qty = max(1, (int) ($detailRow->quantity ?? 1));
            $unit = (float) ($detailRow->service_cost ?? 0);
            $visitCartPrefill[] = [
                'service_id' => (string) ($detailRow->service_id ?? ''),
                'variant_key' => (string) ($detailRow->variant_key ?? ''),
                'quantity' => $qty,
                'service_name' => (string) ($detailRow->service_name ?? ($svc->name ?? '')),
                'variant_label' => (string) ($detailRow->variant_key ?? ''),
                'category_id' => $svc && ! empty($svc->category_id) ? (string) $svc->category_id : null,
                'sub_category_id' => $svc && ! empty($svc->sub_category_id) ? (string) $svc->sub_category_id : null,
                'unit_price' => $unit > 0 ? $unit : null,
                'line_discount' => (float) ($detailRow->discount_amount ?? 0),
                'line_discount_cost_bearer' => 'none',
                'catalog_unit_price' => $unit,
            ];
        }
        $visitZoneId = (string) ($booking->zone_id ?? '');
        $visitProviderId = (string) ($booking->provider_id ?? '');
        $visitCurrencySymbol = currency_symbol();
    @endphp
    <div class="modal fade repeat-visit-modal" id="addRepeatVisitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.booking.extend_repeat', $booking->id) }}" id="add-repeat-visit-form">
                    @csrf
                    <input type="hidden" name="web_page" value="{{ $webPage ?? 'service_log' }}">
                    <input type="hidden" name="booking_create_cart_json" id="visit-cart-json" value="">
                    <input type="hidden" name="booking_create_extra_services_json" id="visit-extra-json" value="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="add-repeat-visit-title">{{ translate('Add_visit') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">{{ translate('Add_visit_help') }}</p>
                        <div class="d-flex flex-column flex-md-row gap-3 mb-3 align-items-md-start repeat-visit-modal__when-kind">
                            <div class="repeat-visit-modal__when flex-grow-1">
                                <label class="form-label" for="add-repeat-visit-schedule">{{ translate('Visit_date_and_time') }}</label>
                                <input type="datetime-local" name="service_schedule" id="add-repeat-visit-schedule"
                                       class="form-control" value="{{ now()->format('Y-m-d\\TH:i') }}" required>
                            </div>
                            <div class="repeat-visit-modal__kind flex-grow-1">
                                <span class="form-label d-block" id="visit-kind-label">{{ translate('Visit_kind') }}</span>
                                <div class="repeat-visit-kind-toggle" role="radiogroup" aria-labelledby="visit-kind-label">
                                    <label class="repeat-visit-kind-toggle__option">
                                        <input type="radio" name="visit_kind" id="visit-kind-scheduled" value="scheduled" checked>
                                        <span class="repeat-visit-kind-toggle__face">
                                            <span class="repeat-visit-kind-toggle__title">{{ translate('Visit_kind_scheduled') }}</span>
                                            <span class="repeat-visit-kind-toggle__hint">{{ translate('Visit_kind_scheduled_hint') }}</span>
                                        </span>
                                    </label>
                                    <label class="repeat-visit-kind-toggle__option">
                                        <input type="radio" name="visit_kind" id="visit-kind-attended" value="attended">
                                        <span class="repeat-visit-kind-toggle__face">
                                            <span class="repeat-visit-kind-toggle__title">{{ translate('Visit_kind_attended') }}</span>
                                            <span class="repeat-visit-kind-toggle__hint">{{ translate('Visit_kind_attended_hint') }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 border rounded-3 p-3" id="visit-summary-section">
                            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                <h4 class="mb-0">{{ translate('Booking_Summary') }}</h4>
                                <div class="d-flex flex-wrap gap-2" id="visit-summary-actions">
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-visit-open-service-modal">
                                        <span class="material-symbols-outlined align-middle" style="font-size: 18px;">edit</span>
                                        {{ translate('Edit Services') }}
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-visit-open-extra-modal">
                                        <span class="material-symbols-outlined align-middle" style="font-size: 18px;">add</span>
                                        {{ translate('Add_Extra_Service') }}
                                    </button>
                                </div>
                            </div>
                            <p class="small text-muted">{{ translate('Visit_summary_help') }}</p>
                            <div id="visit-summary-empty-cta" class="text-center py-4 px-3 border rounded bg-light d-none">
                                <p class="text-muted mb-3">{{ translate('Booking_summary_no_services_yet') }}</p>
                                <button type="button" class="btn btn-primary" id="btn-visit-add-service">
                                    <span class="material-symbols-outlined align-middle" style="font-size: 22px;">add</span>
                                    {{ translate('Add Service') }}
                                </button>
                            </div>
                            <div class="table-responsive border rounded" id="visit-summary-table-wrap">
                                <table class="table text-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">{{ translate('Service') }}</th>
                                        <th>{{ translate('Price') }}</th>
                                        <th>{{ translate('Qty') }}</th>
                                        <th>{{ translate('Discount') }}</th>
                                        <th id="visit-summary-tax-head" class="d-none">{{ company_default_tax_label() }}</th>
                                        <th class="text-end pe-3">{{ translate('Total') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody id="visit-summary-tbody"></tbody>
                                </table>
                            </div>
                            <p class="small text-muted mb-0 mt-2" id="visit-summary-hint"></p>
                            <div id="visit-summary-error" class="text-danger small mt-2 d-none" role="alert"></div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="add-repeat-visit-remarks">{{ translate('Visit_remarks') }}</label>
                            <textarea name="visit_remarks" id="add-repeat-visit-remarks" class="form-control" rows="3"
                                      maxlength="2000" placeholder="{{ translate('Visit_remarks_placeholder') }}"></textarea>
                            <p class="form-text mb-0">{{ translate('Visit_remarks_help') }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn--primary" id="add-repeat-visit-submit">{{ translate('Add_visit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serviceUpdateModal--visit" tabindex="-1" aria-hidden="true">
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
                            <select class="form-control" id="visit-modal-category"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ translate('Sub_Category') }}</label>
                            <select class="form-control" id="visit-modal-subcategory"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ translate('service') }}</label>
                            <select class="form-control" id="visit-modal-service"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ translate('variant') }}</label>
                            <select class="form-control" id="visit-modal-variant"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ translate('service_quantity') }}</label>
                            <input type="number" class="form-control" id="visit-modal-qty" min="1" step="1" value="1">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" id="visit-modal-add-line" disabled>{{ translate('Add Service') }}</button>
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
                            <tbody id="visit-modal-lines-tbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="visit-modal-save-cart">{{ translate('update_cart') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addExtraServiceModal--visit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Add_Extra_Service') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Title') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="visit-extra-title" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Details_of_Service') }}</label>
                        <textarea class="form-control" id="visit-extra-details" rows="2" maxlength="2000"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Type') }} <span class="text-danger">*</span></label>
                        <select class="form-control" id="visit-extra-type">
                            <option value="service">{{ translate('Service') }}</option>
                            <option value="spare_part">{{ translate('Spare_Part') }}</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ translate('Qty') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="visit-extra-qty" min="1" step="1" value="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ translate('Price') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="visit-extra-price" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ translate('Discount') }}</label>
                            <input type="number" class="form-control" id="visit-extra-discount" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <p class="small text-muted mb-0">{{ translate('Total') }} = ({{ translate('Qty') }} × {{ translate('Price') }}) − {{ translate('Discount') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="visit-extra-add-btn">{{ translate('Add') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="visit-cart-prefill">@json($visitCartPrefill)</script>
    <script>
        (function () {
            var modal = document.getElementById('addRepeatVisitModal');
            if (!modal || typeof jQuery === 'undefined') {
                return;
            }
            var $ = jQuery;
            var zoneId = @json($visitZoneId);
            var providerId = @json($visitProviderId);
            var moneyPrefix = @json($visitCurrencySymbol);
            var cartSummaryUrl = @json(route('admin.booking.service.ajax-create-booking-cart-summary'));
            var categoriesUrl = @json(route('admin.booking.service.ajax-get-categories'));
            var subcategoriesUrl = @json(route('admin.booking.service.ajax-get-subcategories'));
            var servicesUrl = @json(route('admin.booking.service.ajax-get-services'));
            var variantsUrl = @json(route('admin.booking.service.ajax-get-variant'));
            var csrf = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
            var bearerNone = @json(translate('Discount_bearer_label_no_one'));
            var bearerAdmin = @json(translate('Discount_bearer_admin'));
            var bearerProvider = @json(translate('Discount_bearer_provider'));
            var bearerBoth = @json(translate('Discount_bearer_both'));
            var removeLabel = @json(translate('Remove'));
            var extraServiceLabel = @json(translate('Service'));
            var sparePartLabel = @json(translate('Spare_Part'));
            var extraChargesLabel = @json(translate('Additional_charges'));
            var extraServicesLabel = @json(translate('Extra_Services'));
            var grandTotalLabel = @json(translate('Grand_Total'));
            var selectOneServiceLabel = @json(translate('Select_at_least_one_service'));
            var scheduleVisitLabel = @json(translate('Schedule_visit'));
            var addVisitLabel = @json(translate('Add_visit'));
            var visitCart = { lines: [], extras: [] };

            try {
                var rawPrefill = document.getElementById('visit-cart-prefill');
                var parsed = rawPrefill ? JSON.parse(rawPrefill.textContent || '[]') : [];
                if (Array.isArray(parsed)) {
                    visitCart.lines = parsed.filter(function (ln) {
                        return ln && ln.service_id && ln.variant_key;
                    });
                }
            } catch (e) {
                visitCart.lines = [];
            }

            function formatMoney(n) {
                var x = parseFloat(n);
                if (isNaN(x)) {
                    x = 0;
                }
                return moneyPrefix + Number(x).toFixed(1);
            }

            function persistCart() {
                $('#visit-cart-json').val(JSON.stringify(visitCart.lines));
                $('#visit-extra-json').val(visitCart.extras.length ? JSON.stringify(visitCart.extras) : '');
            }

            function showStackedModal(id) {
                var el = document.getElementById(id);
                if (!el) {
                    return;
                }
                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                } else {
                    $('#' + id).modal('show');
                }
            }

            function hideStackedModal(id) {
                var el = document.getElementById(id);
                if (!el) {
                    return;
                }
                if (window.bootstrap && bootstrap.Modal) {
                    var inst = bootstrap.Modal.getInstance(el);
                    if (inst) {
                        inst.hide();
                    }
                } else {
                    $('#' + id).modal('hide');
                }
            }

            function keepParentOpen() {
                if ($('#addRepeatVisitModal').hasClass('show')) {
                    $('body').addClass('modal-open');
                }
            }

            function updateVisitAddLineEnabled() {
                var ok = !!$('#visit-modal-category').val()
                    && !!$('#visit-modal-subcategory').val()
                    && !!$('#visit-modal-service').val()
                    && !!$('#visit-modal-variant').val()
                    && (parseInt($('#visit-modal-qty').val(), 10) >= 1);
                $('#visit-modal-add-line').prop('disabled', !ok);
            }

            function updateVisitRowSubtotal($tr) {
                var catalog = parseFloat($tr.attr('data-catalog-unit-price')) || 0;
                var upRaw = ($tr.find('.visit-modal-unit-price').val() || '').trim();
                var up = upRaw === '' ? catalog : (parseFloat(upRaw) || 0);
                var qty = Math.max(1, parseInt($tr.find('.visit-modal-line-qty').val(), 10) || 1);
                var disc = Math.max(0, parseFloat($tr.find('.visit-modal-line-discount').val()) || 0);
                $tr.find('.visit-modal-row-subtotal').text(formatMoney(Math.max(0, up * qty - disc)));
            }

            function readVisitModalLinesIntoCart() {
                var next = [];
                $('#visit-modal-lines-tbody tr').each(function () {
                    var $tr = $(this);
                    var sid = $tr.attr('data-service-id');
                    var vk = $tr.attr('data-variant-key');
                    if (!sid || !vk) {
                        return;
                    }
                    var catalog = parseFloat($tr.attr('data-catalog-unit-price')) || 0;
                    var qty = Math.max(1, parseInt($tr.find('.visit-modal-line-qty').val(), 10) || 1);
                    var upRaw = ($tr.find('.visit-modal-unit-price').val() || '').trim();
                    var upNum = upRaw === '' ? null : parseFloat(upRaw);
                    var unitPrice = null;
                    if (upNum != null && !isNaN(upNum) && upNum > 0 && (catalog <= 0 || Math.abs(upNum - catalog) > 0.0001)) {
                        unitPrice = upNum;
                    }
                    var disc = Math.max(0, parseFloat($tr.find('.visit-modal-line-discount').val()) || 0);
                    var bear = ($tr.find('.visit-modal-line-discount-bearer').val() || 'none').toLowerCase();
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
                visitCart.lines = next;
            }

            function renderVisitModalLines() {
                var $tb = $('#visit-modal-lines-tbody').empty();
                visitCart.lines.forEach(function (line, idx) {
                    var sn = line.service_name || line.service_id;
                    var vn = line.variant_label || line.variant_key;
                    var catalog = parseFloat(line.catalog_unit_price) || 0;
                    var showUnit = (line.unit_price != null && line.unit_price !== '')
                        ? String(line.unit_price)
                        : (catalog > 0 ? String(catalog) : '');
                    var disc = Math.max(0, parseFloat(line.line_discount) || 0);
                    var bearVal = String(line.line_discount_cost_bearer || 'none').toLowerCase();
                    var $tr = $('<tr/>')
                        .attr('data-idx', idx)
                        .attr('data-service-id', line.service_id)
                        .attr('data-variant-key', line.variant_key)
                        .attr('data-service-name', sn)
                        .attr('data-variant-label', vn)
                        .attr('data-category-id', line.category_id || '')
                        .attr('data-sub-category-id', line.sub_category_id || '')
                        .attr('data-catalog-unit-price', catalog > 0 ? String(catalog) : '0');
                    $tr.append($('<td/>').addClass('text-wrap').text(sn));
                    $tr.append($('<td/>').addClass('text-wrap').text(vn));
                    $tr.append($('<td style="min-width:7rem"/>').append(
                        $('<input type="number" class="form-control form-control-sm visit-modal-unit-price" min="0" step="0.01"/>').val(showUnit)
                    ));
                    $tr.append($('<td style="min-width:5rem"/>').append(
                        $('<input type="number" class="form-control form-control-sm visit-modal-line-qty" min="1" step="1"/>').val(String(Math.max(1, parseInt(line.quantity, 10) || 1)))
                    ));
                    $tr.append($('<td style="min-width:6rem"/>').append(
                        $('<input type="number" class="form-control form-control-sm visit-modal-line-discount" min="0" step="0.01"/>').val(disc > 0 ? String(disc) : '')
                    ));
                    var $bear = $('<select class="form-control form-control-sm visit-modal-line-discount-bearer"/>');
                    [
                        { v: 'none', t: bearerNone },
                        { v: 'admin', t: bearerAdmin },
                        { v: 'provider', t: bearerProvider },
                        { v: 'both', t: bearerBoth }
                    ].forEach(function (o) {
                        $bear.append($('<option/>').val(o.v).text(o.t).prop('selected', o.v === bearVal));
                    });
                    $tr.append($('<td style="min-width:10rem"/>').append($bear));
                    $tr.append($('<td class="visit-modal-row-subtotal text-nowrap"/>'));
                    var $rm = $('<button type="button" class="btn btn-sm btn-outline-danger"/>').text(removeLabel);
                    $rm.on('click', function () {
                        var i = parseInt($(this).closest('tr').attr('data-idx'), 10);
                        if (!isNaN(i)) {
                            visitCart.lines.splice(i, 1);
                            renderVisitModalLines();
                        }
                    });
                    $tr.append($('<td class="text-center"/>').append($rm));
                    $tb.append($tr);
                    updateVisitRowSubtotal($tr);
                });
            }

            function renderVisitSummaryLocal() {
                var $tb = $('#visit-summary-tbody').empty();
                var hasRows = visitCart.lines.length > 0 || visitCart.extras.length > 0;
                $('#visit-summary-empty-cta').toggleClass('d-none', hasRows);
                $('#visit-summary-table-wrap').toggleClass('d-none', !hasRows);
                $('#visit-summary-actions').toggleClass('d-none', visitCart.lines.length === 0);
                visitCart.lines.forEach(function (ln) {
                    var unit = ln.unit_price != null ? ln.unit_price : (ln.catalog_unit_price || 0);
                    var qty = Math.max(1, parseInt(ln.quantity, 10) || 1);
                    var disc = Math.max(0, parseFloat(ln.line_discount) || 0);
                    var total = Math.max(0, unit * qty - disc);
                    $tb.append(
                        '<tr><td class="ps-3 text-wrap"><div class="fw-semibold"></div><div class="small text-muted"></div></td>' +
                        '<td></td><td></td><td></td><td class="text-end pe-3"></td></tr>'
                    );
                    var $tr = $tb.children().last();
                    $tr.find('div.fw-semibold').text(ln.service_name || '');
                    $tr.find('div.small').text(ln.variant_label || '');
                    $tr.children().eq(1).text(formatMoney(unit));
                    $tr.children().eq(2).text(String(qty));
                    $tr.children().eq(3).text(formatMoney(disc));
                    $tr.children().eq(4).text(formatMoney(total));
                });
                visitCart.extras.forEach(function (ex, exIdx) {
                    var qty = Math.max(1, parseInt(ex.quantity, 10) || 1);
                    var price = Math.max(0, parseFloat(ex.price) || 0);
                    var disc = Math.max(0, parseFloat(ex.discount) || 0);
                    var total = Math.max(0, qty * price - disc);
                    var typeLabel = ex.type === 'spare_part' ? sparePartLabel : extraServiceLabel;
                    var $tr = $('<tr class="table-light"/>');
                    $tr.append($('<td class="ps-3 text-wrap"/>').append(
                        $('<div class="fw-semibold"/>').text(ex.title || ''),
                        $('<span class="badge bg-secondary"/>').text(typeLabel)
                    ));
                    $tr.append($('<td/>').text(formatMoney(price)));
                    $tr.append($('<td/>').text(String(qty)));
                    $tr.append($('<td/>').text(formatMoney(disc)));
                    $tr.append($('<td class="text-end pe-3"/>').append(
                        document.createTextNode(formatMoney(total) + ' '),
                        $('<button type="button" class="btn btn-link btn-sm text-danger p-0 js-visit-remove-extra"/>').attr('data-idx', exIdx).text(removeLabel)
                    ));
                    $tb.append($tr);
                });
            }

            function refreshVisitSummary() {
                persistCart();
                renderVisitSummaryLocal();
                $('#visit-summary-error').addClass('d-none').text('');
                if (!zoneId || !providerId || !visitCart.lines.length) {
                    return;
                }
                var payload = {
                    zone_id: zoneId,
                    provider_id: providerId,
                    lines: visitCart.lines.map(function (ln) {
                        var row = {
                            service_id: ln.service_id,
                            variant_key: ln.variant_key,
                            quantity: ln.quantity,
                            line_discount: Math.max(0, parseFloat(ln.line_discount) || 0),
                            line_discount_cost_bearer: ln.line_discount_cost_bearer || 'none'
                        };
                        if (ln.unit_price != null && ln.unit_price !== '' && !isNaN(parseFloat(ln.unit_price)) && parseFloat(ln.unit_price) > 0) {
                            row.unit_price = parseFloat(ln.unit_price);
                        }
                        return row;
                    }),
                    extras: visitCart.extras.map(function (ex) {
                        return {
                            title: ex.title,
                            details: ex.details,
                            type: ex.type === 'spare_part' ? 'spare_part' : 'service',
                            quantity: ex.quantity,
                            price: ex.price,
                            discount: ex.discount
                        };
                    })
                };
                $.ajax({
                    url: cartSummaryUrl,
                    method: 'POST',
                    contentType: 'application/json; charset=UTF-8',
                    dataType: 'json',
                    data: JSON.stringify(payload),
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json'
                    },
                    success: function (res) {
                        if (!res || res.response_code !== 'default_200' || !res.content) {
                            return;
                        }
                        var content = res.content;
                        var showTax = false;
                        (content.lines || []).forEach(function (ln, idx) {
                            if (parseFloat(ln.tax_amount) > 0.0001) {
                                showTax = true;
                            }
                            if (visitCart.lines[idx]) {
                                visitCart.lines[idx].service_name = ln.service_name || visitCart.lines[idx].service_name;
                                visitCart.lines[idx].variant_label = ln.variant_label || visitCart.lines[idx].variant_label;
                                if (ln.catalog_unit_price != null) {
                                    visitCart.lines[idx].catalog_unit_price = parseFloat(ln.catalog_unit_price);
                                }
                            }
                        });
                        persistCart();
                        $('#visit-summary-tax-head').toggleClass('d-none', !showTax);
                        var $tb = $('#visit-summary-tbody').empty();
                        (content.lines || []).forEach(function (ln) {
                            var taxCell = showTax ? '<td>' + formatMoney(ln.tax_amount) + '</td>' : '';
                            $tb.append(
                                '<tr><td class="ps-3 text-wrap"><div class="fw-semibold"></div><div class="small text-muted"></div></td>' +
                                '<td></td><td></td><td></td>' + taxCell + '<td class="text-end pe-3"></td></tr>'
                            );
                            var $tr = $tb.children().last();
                            $tr.find('div.fw-semibold').text(ln.service_name || '');
                            $tr.find('div.small').text(ln.variant_label || '');
                            $tr.children().eq(1).text(formatMoney(ln.unit_price));
                            $tr.children().eq(2).text(String(ln.quantity));
                            $tr.children().eq(3).text(formatMoney(ln.discount_total));
                            $tr.children().last().text(formatMoney(ln.line_total));
                        });
                        (content.extras || []).forEach(function (ex, exIdx) {
                            var typeLabel = ex.type === 'spare_part' ? sparePartLabel : extraServiceLabel;
                            var $tr = $('<tr class="table-light"/>');
                            $tr.append($('<td class="ps-3 text-wrap"/>').append(
                                $('<div class="fw-semibold"/>').text(ex.title || ''),
                                $('<span class="badge bg-secondary ms-1"/>').text(typeLabel)
                            ));
                            $tr.append($('<td/>').text(formatMoney(ex.price)));
                            $tr.append($('<td/>').text(String(ex.quantity)));
                            $tr.append($('<td/>').text(formatMoney(ex.discount)));
                            if (showTax) {
                                $tr.append($('<td/>').text('—'));
                            }
                            $tr.append($('<td class="text-end pe-3"/>').append(
                                document.createTextNode(formatMoney(ex.total) + ' '),
                                $('<button type="button" class="btn btn-link btn-sm text-danger p-0 js-visit-remove-extra"/>').attr('data-idx', exIdx).text(removeLabel)
                            ));
                            $tb.append($tr);
                        });
                        var hint = [];
                        if (parseFloat(content.extra_fee) > 0) {
                            hint.push(extraChargesLabel + ': ' + formatMoney(content.extra_fee));
                        }
                        if (parseFloat(content.extras_total) > 0) {
                            hint.push(extraServicesLabel + ': ' + formatMoney(content.extras_total));
                        }
                        if (parseFloat(content.grand_total) >= 0) {
                            hint.push(grandTotalLabel + ': ' + formatMoney(content.grand_total));
                        }
                        $('#visit-summary-hint').text(hint.join(' · '));
                        $('#visit-summary-empty-cta').addClass('d-none');
                        $('#visit-summary-table-wrap').removeClass('d-none');
                        $('#visit-summary-actions').removeClass('d-none');
                    }
                });
            }

            function loadVisitCategories() {
                if (!zoneId) {
                    return;
                }
                var params = { zone_id: zoneId };
                if (providerId) {
                    params.provider_id = providerId;
                }
                $.get(categoriesUrl, params, function (res) {
                    var $c = $('#visit-modal-category');
                    $c.empty().append(new Option(@json(translate('Select_Category')), '', true, true));
                    (res.content || []).forEach(function (row) {
                        $c.append(new Option(row.name, row.id, false, false));
                    });
                    updateVisitAddLineEnabled();
                });
            }

            function openVisitServiceModal() {
                loadVisitCategories();
                renderVisitModalLines();
                showStackedModal('serviceUpdateModal--visit');
                updateVisitAddLineEnabled();
            }

            $('#visit-summary-tbody').on('click', '.js-visit-remove-extra', function () {
                var i = parseInt($(this).attr('data-idx'), 10);
                if (!isNaN(i)) {
                    visitCart.extras.splice(i, 1);
                    refreshVisitSummary();
                }
            });

            $('#visit-modal-lines-tbody').on('input change', '.visit-modal-unit-price, .visit-modal-line-qty, .visit-modal-line-discount, .visit-modal-line-discount-bearer', function () {
                updateVisitRowSubtotal($(this).closest('tr'));
            });

            $('#visit-modal-category').on('change', function () {
                var cid = $(this).val();
                $('#visit-modal-subcategory').empty().append(new Option(@json(translate('Select_Sub_Category')), '', true, true));
                $('#visit-modal-service').empty().append(new Option(@json(translate('Select_Service')), '', true, true));
                $('#visit-modal-variant').empty().append(new Option(@json(translate('Select Service Variant')), '', true, true));
                if (!cid) {
                    updateVisitAddLineEnabled();
                    return;
                }
                $.get(subcategoriesUrl, { category_id: cid, provider_id: providerId || '' }, function (res) {
                    (res.content || []).forEach(function (row) {
                        $('#visit-modal-subcategory').append(new Option(row.name, row.id, false, false));
                    });
                    updateVisitAddLineEnabled();
                });
            });

            $('#visit-modal-subcategory').on('change', function () {
                var sid = $(this).val();
                $('#visit-modal-service').empty().append(new Option(@json(translate('Select_Service')), '', true, true));
                $('#visit-modal-variant').empty().append(new Option(@json(translate('Select Service Variant')), '', true, true));
                if (!sid) {
                    updateVisitAddLineEnabled();
                    return;
                }
                $.get(servicesUrl, { sub_category_id: sid }, function (res) {
                    (res.content || []).forEach(function (row) {
                        $('#visit-modal-service').append(new Option(row.name, row.id, false, false));
                    });
                    updateVisitAddLineEnabled();
                });
            });

            $('#visit-modal-service').on('change', function () {
                var svcId = $(this).val();
                $('#visit-modal-variant').empty().append(new Option(@json(translate('Select Service Variant')), '', true, true));
                if (!svcId || !zoneId) {
                    updateVisitAddLineEnabled();
                    return;
                }
                $.get(variantsUrl, { service_id: svcId, zone_id: zoneId }, function (res) {
                    (res.content || []).forEach(function (v) {
                        var label = (v.variant || v.variant_key) + ' — ' + formatMoney(v.price);
                        var $opt = $('<option/>').val(v.variant_key).text(label).attr('data-catalog-price', v.price != null ? String(v.price) : '0');
                        $('#visit-modal-variant').append($opt);
                    });
                    updateVisitAddLineEnabled();
                });
            });

            $('#visit-modal-variant, #visit-modal-qty').on('change input', updateVisitAddLineEnabled);

            $('#visit-modal-add-line').on('click', function () {
                if ($(this).prop('disabled')) {
                    return;
                }
                var sid = $('#visit-modal-service').val();
                var vk = $('#visit-modal-variant').val();
                var qty = Math.max(1, parseInt($('#visit-modal-qty').val(), 10) || 1);
                if (!sid || !vk) {
                    return;
                }
                var catPrice = parseFloat($('#visit-modal-variant option:selected').attr('data-catalog-price')) || 0;
                visitCart.lines.push({
                    service_id: String(sid),
                    variant_key: String(vk),
                    quantity: qty,
                    service_name: ($('#visit-modal-service option:selected').text() || '').trim(),
                    variant_label: ($('#visit-modal-variant option:selected').text() || '').trim(),
                    category_id: $('#visit-modal-category').val() || null,
                    sub_category_id: $('#visit-modal-subcategory').val() || null,
                    unit_price: null,
                    line_discount: 0,
                    line_discount_cost_bearer: 'none',
                    catalog_unit_price: catPrice > 0 ? catPrice : null
                });
                renderVisitModalLines();
                $('#visit-modal-subcategory').val('').trigger('change');
                $('#visit-modal-qty').val(1);
                updateVisitAddLineEnabled();
            });

            $('#visit-modal-save-cart').on('click', function () {
                readVisitModalLinesIntoCart();
                persistCart();
                refreshVisitSummary();
                hideStackedModal('serviceUpdateModal--visit');
            });

            $('#btn-visit-open-service-modal, #btn-visit-add-service').on('click', function (e) {
                e.preventDefault();
                openVisitServiceModal();
            });

            $('#btn-visit-open-extra-modal').on('click', function (e) {
                e.preventDefault();
                showStackedModal('addExtraServiceModal--visit');
            });

            $('#visit-extra-add-btn').on('click', function () {
                var title = ($('#visit-extra-title').val() || '').trim();
                if (!title) {
                    return;
                }
                visitCart.extras.push({
                    title: title,
                    details: ($('#visit-extra-details').val() || '').trim() || null,
                    type: $('#visit-extra-type').val() === 'spare_part' ? 'spare_part' : 'service',
                    quantity: Math.max(1, parseInt($('#visit-extra-qty').val(), 10) || 1),
                    price: Math.max(0, parseFloat($('#visit-extra-price').val()) || 0),
                    discount: Math.max(0, parseFloat($('#visit-extra-discount').val()) || 0)
                });
                $('#visit-extra-title').val('');
                $('#visit-extra-details').val('');
                $('#visit-extra-qty').val('1');
                $('#visit-extra-price').val('0');
                $('#visit-extra-discount').val('0');
                persistCart();
                refreshVisitSummary();
                hideStackedModal('addExtraServiceModal--visit');
            });

            $('#serviceUpdateModal--visit, #addExtraServiceModal--visit').on('shown.bs.modal', function () {
                $('.modal-backdrop').last().css('z-index', 1060);
            }).on('hidden.bs.modal', keepParentOpen);

            modal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var kind = button && button.getAttribute('data-visit-kind') ? button.getAttribute('data-visit-kind') : 'scheduled';
                var radio = this.querySelector('input[name="visit_kind"][value="' + kind + '"]');
                if (radio) {
                    radio.checked = true;
                }
                var title = document.getElementById('add-repeat-visit-title');
                var submit = document.getElementById('add-repeat-visit-submit');
                if (kind === 'scheduled') {
                    if (title) {
                        title.textContent = scheduleVisitLabel;
                    }
                    if (submit) {
                        submit.textContent = scheduleVisitLabel;
                    }
                } else {
                    if (title) {
                        title.textContent = addVisitLabel;
                    }
                    if (submit) {
                        submit.textContent = addVisitLabel;
                    }
                }
                persistCart();
                refreshVisitSummary();
            });

            $('#add-repeat-visit-form').on('submit', function (e) {
                persistCart();
                if (!visitCart.lines.length) {
                    e.preventDefault();
                    $('#visit-summary-error').removeClass('d-none').text(selectOneServiceLabel);
                    $('#visit-summary-empty-cta').removeClass('d-none');
                }
            });
        })();
    </script>
@endcan
