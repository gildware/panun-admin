{{-- Expects: $booking, $bookingCancellationReasons, $bookingHoldReasons --}}
@php
    use Carbon\Carbon;
    $bsrHoldByResp = ($bookingHoldReasons ?? collect())->groupBy('responsible')->map(fn ($rows) => $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values())->toArray();
    $bsrCancelByResp = ($bookingCancellationReasons ?? collect())->groupBy('responsible')->map(fn ($rows) => $rows->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values())->toArray();
    $bsrRespOptions = \Modules\BookingModule\Entities\BookingCancellationReason::responsibleOptions();
    $bsrRespLabels = ['customer' => translate('Customer'), 'provider' => translate('Provider'), 'staff' => translate('Staff'), 'no_one' => translate('No_one')];
    $bsrHoldScheduleDefault = '';
    if (isset($booking) && ($booking->service_schedule ?? null)) {
        try {
            $bsrHoldScheduleDefault = Carbon::parse($booking->service_schedule)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            $bsrHoldScheduleDefault = '';
        }
    }
@endphp
<div class="modal fade" id="bookingStatusReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="bsr-modal-title">{{ translate('Booking_Status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body pt-0">
                <input type="hidden" id="bsr-target-status" value="">
                <input type="hidden" id="bsr-previous-status" value="">
                <div class="mb-3">
                    <label class="form-label">{{ translate('Who_is_responsible') }} <span class="text-danger">*</span></label>
                    <select id="bsr-responsible" class="form-select" autocomplete="off">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach($bsrRespOptions as $resp)
                            <option value="{{ $resp }}">{{ $bsrRespLabels[$resp] ?? $resp }}</option>
                        @endforeach
                    </select>
                    <div class="form-text small text-muted">{{ translate('Responsible_then_reason_hint') }}</div>
                </div>
                <div id="bsr-group-hold" class="d-none mb-3">
                    <label class="form-label">{{ translate('Booking_hold_reasons') }} <span class="text-danger">*</span></label>
                    <select id="bsr-hold-reason-id" class="form-select" disabled>
                        <option value="">{{ translate('Select_responsible_first') }}</option>
                    </select>
                </div>
                <div id="bsr-group-hold-schedule" class="d-none mb-3">
                    <label class="form-label" for="bsr-hold-estimated-schedule">{{ translate('Hold_estimated_service_schedule') }} <span class="text-danger">*</span></label>
                    <input type="datetime-local" id="bsr-hold-estimated-schedule" class="form-control" value="{{ $bsrHoldScheduleDefault }}" autocomplete="off">
                    <div class="form-text small text-muted">{{ translate('Hold_estimated_service_schedule_hint') }}</div>
                </div>
                <div id="bsr-group-cancel" class="d-none mb-3">
                    <label class="form-label">{{ translate('Booking_cancellation_reasons') }} <span class="text-danger">*</span></label>
                    <select id="bsr-cancel-reason-id" class="form-select" disabled>
                        <option value="">{{ translate('Select_responsible_first') }}</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label">{{ translate('Status_change_remarks') }}</label>
                    <textarea id="bsr-remarks" class="form-control" rows="3" maxlength="2000" placeholder="{{ translate('Optional') }}"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn--primary" id="bsr-confirm-btn">{{ translate('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        (function () {
            const bsrStatusUpdateUrl = @json(route('admin.booking.status_update', [$booking->id]));
            const bsrCsrf = $('meta[name="csrf-token"]').attr('content');
            const bsrHoldByResp = @json($bsrHoldByResp);
            const bsrCancelByResp = @json($bsrCancelByResp);
            const bsrTitles = {
                on_hold: @json(translate('Put_on_hold')),
                canceled: @json(translate('Cancel_Booking')),
            };
            const bsrDefaultHoldSchedule = @json($bsrHoldScheduleDefault);

            function bsrFillReasonSelect($select, items, placeholder) {
                $select.empty();
                $select.append($('<option>', { value: '', text: placeholder || '{{ translate('Select') }}' }));
                (items || []).forEach(function (row) {
                    $select.append($('<option>', { value: String(row.id), text: row.name }));
                });
            }

            window.bookingAdminStatusNeedsReason = function (toStatus, fromStatus) {
                const to = String(toStatus);
                return to === 'on_hold' || to === 'canceled';
            };

            function bsrResetGroups() {
                $('#bsr-responsible').val('');
                $('#bsr-group-hold').addClass('d-none');
                $('#bsr-group-hold-schedule').addClass('d-none');
                $('#bsr-group-cancel').addClass('d-none');
                $('#bsr-hold-reason-id').prop('disabled', true).val('');
                $('#bsr-cancel-reason-id').prop('disabled', true).val('');
                bsrFillReasonSelect($('#bsr-hold-reason-id'), [], '{{ translate('Select_responsible_first') }}');
                bsrFillReasonSelect($('#bsr-cancel-reason-id'), [], '{{ translate('Select_responsible_first') }}');
                $('#bsr-hold-estimated-schedule').val(bsrDefaultHoldSchedule || '');
                $('#bsr-remarks').val('');
            }

            $('#bsr-responsible').on('change', function () {
                const resp = String($(this).val() || '');
                const to = String($('#bsr-target-status').val() || '');
                if (!resp) {
                    if (to === 'on_hold') {
                        $('#bsr-hold-reason-id').prop('disabled', true).val('');
                        bsrFillReasonSelect($('#bsr-hold-reason-id'), [], '{{ translate('Select_responsible_first') }}');
                    }
                    if (to === 'canceled') {
                        $('#bsr-cancel-reason-id').prop('disabled', true).val('');
                        bsrFillReasonSelect($('#bsr-cancel-reason-id'), [], '{{ translate('Select_responsible_first') }}');
                    }
                    return;
                }
                if (to === 'on_hold') {
                    const rows = bsrHoldByResp[resp] || [];
                    $('#bsr-hold-reason-id').prop('disabled', rows.length === 0);
                    bsrFillReasonSelect($('#bsr-hold-reason-id'), rows, rows.length ? '{{ translate('Select') }}' : '{{ translate('No_reasons_for_this_party') }}');
                }
                if (to === 'canceled') {
                    const rows = bsrCancelByResp[resp] || [];
                    $('#bsr-cancel-reason-id').prop('disabled', rows.length === 0);
                    bsrFillReasonSelect($('#bsr-cancel-reason-id'), rows, rows.length ? '{{ translate('Select') }}' : '{{ translate('No_reasons_for_this_party') }}');
                }
            });

            window.bookingAdminOpenStatusReasonModal = function (targetStatus, previousStatus) {
                bsrResetGroups();
                const to = String(targetStatus);
                $('#bsr-target-status').val(to);
                $('#bsr-previous-status').val(previousStatus || '');
                let bsrTitle = bsrTitles[to] || '{{ translate('Booking_Status') }}';
                if (to === 'on_hold' && String(previousStatus || '') === 'ongoing') {
                    bsrTitle = @json(translate('Hold_after_visit'));
                }
                $('#bsr-modal-title').text(bsrTitle);
                if (to === 'on_hold') {
                    $('#bsr-group-hold').removeClass('d-none');
                    $('#bsr-group-hold-schedule').removeClass('d-none');
                }
                if (to === 'canceled') {
                    $('#bsr-group-cancel').removeClass('d-none');
                }
                const el = document.getElementById('bookingStatusReasonModal');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                }
            };

            $('#bsr-confirm-btn').on('click', function () {
                const to = $('#bsr-target-status').val();
                const from = $('#bsr-previous-status').val();
                const resp = String($('#bsr-responsible').val() || '');
                if (!resp) {
                    toastr.error('{{ translate('Who_is_responsible') }} {{ translate('is_required') }}');
                    return;
                }
                const data = {
                    _token: bsrCsrf,
                    booking_status: to,
                    reason_responsible: resp,
                    status_change_remarks: $('#bsr-remarks').val() || ''
                };
                if (to === 'on_hold') {
                    data.booking_hold_reopen_reason_id = $('#bsr-hold-reason-id').val();
                    if (!data.booking_hold_reopen_reason_id) {
                        toastr.error('{{ translate('Booking_hold_reasons') }} {{ translate('is_required') }}');
                        return;
                    }
                    data.hold_estimated_service_schedule = $('#bsr-hold-estimated-schedule').val();
                    if (!data.hold_estimated_service_schedule) {
                        toastr.error('{{ translate('Hold_estimated_service_schedule') }} {{ translate('is_required') }}');
                        return;
                    }
                }
                if (to === 'canceled') {
                    data.booking_cancellation_reason_id = $('#bsr-cancel-reason-id').val();
                    if (!data.booking_cancellation_reason_id) {
                        toastr.error('{{ translate('Booking_cancellation_reasons') }} {{ translate('is_required') }}');
                        return;
                    }
                }

                const $btn = $(this);
                $btn.prop('disabled', true);
                $.ajax({
                    url: bsrStatusUpdateUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).done(function (res) {
                    const modalEl = document.getElementById('bookingStatusReasonModal');
                    if (modalEl && bootstrap.Modal) {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    if (res && res.message && typeof toastr !== 'undefined') {
                        toastr.success(res.message, { CloseButton: true, ProgressBar: true });
                    }
                    var finish = function () {
                        if (to === 'completed' || to === 'canceled') {
                            var bookingCurrentProviderId = typeof window.bookingCurrentProviderId !== 'undefined' ? window.bookingCurrentProviderId : null;
                            if (bookingCurrentProviderId && typeof openProviderPerformanceFeedbackModal === 'function') {
                                if (typeof pendingPostFeedbackAction !== 'undefined') {
                                    window.pendingPostFeedbackAction = 'reload';
                                }
                                openProviderPerformanceFeedbackModal(bookingCurrentProviderId, to === 'canceled' ? 'canceled' : 'completed');
                                $btn.prop('disabled', false);
                                return;
                            }
                        }
                        location.reload();
                    };
                    if (typeof window.waAdminAfterAjaxWithOptionalWhatsAppPrompt === 'function') {
                        window.waAdminAfterAjaxWithOptionalWhatsAppPrompt(res, finish);
                    } else {
                        finish();
                    }
                }).fail(function (xhr) {
                    $btn.prop('disabled', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ translate('Something went wrong. Please try again.') }}';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errs = xhr.responseJSON.errors;
                        if (typeof errs === 'object' && !Array.isArray(errs)) {
                            var first = Object.values(errs)[0];
                            if (Array.isArray(first) && first[0]) {
                                msg = first[0];
                            }
                        }
                    }
                    toastr.error(msg, { CloseButton: true, ProgressBar: true });
                });
            });
        })();
    </script>
@endpush
