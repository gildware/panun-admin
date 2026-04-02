@can('booking_can_manage_status')
    @if((int)($booking->is_repeated ?? 0) === 0 && ($booking->booking_status ?? '') === 'completed')
        <div class="modal fade" id="bookingReopenModal--{{ $booking->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('admin.booking.reopen', $booking->id) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('Reopen_after_completion') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">{{ translate('Reopen_after_completion_help') }}</p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">{{ translate('Resolution') }}</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resolution" id="reopenInPlace--{{ $booking->id }}" value="reopen_in_place" checked>
                                        <label class="form-check-label" for="reopenInPlace--{{ $booking->id }}">{{ translate('Reopen_same_booking') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="resolution" id="reopenNewBooking--{{ $booking->id }}" value="new_booking">
                                        <label class="form-check-label" for="reopenNewBooking--{{ $booking->id }}">{{ translate('Create_new_linked_booking') }}</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3" id="reopenTargetStatusWrap--{{ $booking->id }}">
                                <label class="form-label">{{ translate('Status_after_reopen') }}</label>
                                <select name="target_status" class="form-select">
                                    <option value="accepted">{{ translate('Accepted') }}</option>
                                    <option value="pending">{{ translate('Pending') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ translate('Reopen_reason') }} <span class="text-danger">*</span></label>
                                <select name="booking_hold_reopen_reason_id" class="form-select" required>
                                    <option value="">{{ translate('Select') }}</option>
                                    @foreach($bookingReopenReasons ?? [] as $r)
                                        <option value="{{ $r->id }}" @selected((string) old('booking_hold_reopen_reason_id') === (string) $r->id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">{{ translate('Complaint_or_notes') }}</label>
                                <textarea name="complaint_notes" class="form-control" rows="4" maxlength="5000" placeholder="{{ translate('Describe_the_issue_optional') }}">{{ old('complaint_notes') }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('Confirm') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @push('script')
            <script>
                (function () {
                    const modalId = 'bookingReopenModal--{{ $booking->id }}';
                    const wrapId = 'reopenTargetStatusWrap--{{ $booking->id }}';
                    document.addEventListener('DOMContentLoaded', function () {
                        const modal = document.getElementById(modalId);
                        if (!modal) return;
                        const wrap = document.getElementById(wrapId);
                        const radios = modal.querySelectorAll('input[name="resolution"]');
                        function sync() {
                            const v = modal.querySelector('input[name="resolution"]:checked')?.value;
                            if (wrap) {
                                wrap.classList.toggle('d-none', v === 'new_booking');
                            }
                        }
                        radios.forEach(function (r) { r.addEventListener('change', sync); });
                        sync();
                    });
                })();
            </script>
        @endpush
    @endif
@endcan
