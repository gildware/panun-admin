{{-- Expects: $booking, $bookingCancellationReasons, $bookingHoldReasons --}}
<div class="modal fade" id="bookingStatusReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">{{ translate('Booking_Status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <div class="modal-body pt-0">
                <input type="hidden" id="bsr-target-status" value="">
                <input type="hidden" id="bsr-previous-status" value="">
                <div id="bsr-group-cancel" class="d-none mb-3">
                    <label class="form-label">{{ translate('Cancellation_reason') }} <span class="text-danger">*</span></label>
                    <select id="bsr-cancellation-reason-id" class="form-select">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach($bookingCancellationReasons ?? [] as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="bsr-group-hold" class="d-none mb-3">
                    <label class="form-label">{{ translate('Hold_reason') }} <span class="text-danger">*</span></label>
                    <select id="bsr-hold-reason-id" class="form-select">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach($bookingHoldReasons ?? [] as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
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

            window.bookingAdminStatusNeedsReason = function (toStatus, fromStatus) {
                const to = String(toStatus);
                if (to === 'canceled') return true;
                if (to === 'on_hold') return true;
                return false;
            };

            function bsrResetGroups() {
                $('#bsr-group-cancel, #bsr-group-hold').addClass('d-none');
                $('#bsr-cancellation-reason-id, #bsr-hold-reason-id').val('');
                $('#bsr-remarks').val('');
            }

            window.bookingAdminOpenStatusReasonModal = function (targetStatus, previousStatus) {
                bsrResetGroups();
                $('#bsr-target-status').val(targetStatus);
                $('#bsr-previous-status').val(previousStatus || '');
                const from = String(previousStatus || '');
                const to = String(targetStatus);
                if (to === 'canceled') {
                    $('#bsr-group-cancel').removeClass('d-none');
                } else if (to === 'on_hold') {
                    $('#bsr-group-hold').removeClass('d-none');
                }
                const el = document.getElementById('bookingStatusReasonModal');
                if (el && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(el).show();
                }
            };

            $('#bsr-confirm-btn').on('click', function () {
                const to = $('#bsr-target-status').val();
                const from = $('#bsr-previous-status').val();
                const data = {
                    _token: bsrCsrf,
                    booking_status: to,
                    status_change_remarks: $('#bsr-remarks').val() || ''
                };
                if (to === 'canceled') {
                    data.booking_cancellation_reason_id = $('#bsr-cancellation-reason-id').val();
                    if (!data.booking_cancellation_reason_id) {
                        toastr.error('{{ translate('Cancellation_reason') }} {{ translate('is_required') }}');
                        return;
                    }
                } else if (to === 'on_hold') {
                    data.booking_hold_reopen_reason_id = $('#bsr-hold-reason-id').val();
                    if (!data.booking_hold_reopen_reason_id) {
                        toastr.error('{{ translate('Hold_reason') }} {{ translate('is_required') }}');
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
                    if (to === 'canceled' || to === 'completed' || to === 'cancelled') {
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
