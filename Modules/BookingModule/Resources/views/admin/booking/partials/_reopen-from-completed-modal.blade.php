@can('booking_can_manage_status')
    @if((int)($booking->is_repeated ?? 0) === 0 && ($booking->booking_status ?? '') === 'completed' && ! $booking->isLossMakingFinancialSettlement())
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
                                <label class="form-label">{{ translate('Status_after_reopen') }}</label>
                                <select name="target_status" class="form-select">
                                    <option value="accepted" @selected(old('target_status', 'accepted') === 'accepted')>{{ translate('Accepted') }}</option>
                                    <option value="pending" @selected(old('target_status') === 'pending')>{{ translate('Pending') }}</option>
                                </select>
                            </div>
                            @php
                                $__reopenScheduleDefault = $booking->service_schedule
                                    ? \Carbon\Carbon::parse($booking->service_schedule)->format('Y-m-d\TH:i')
                                    : \Carbon\Carbon::now()->format('Y-m-d\TH:i');
                            @endphp
                            <div class="mb-3">
                                <label class="form-label">{{ translate('New_schedule_date_time') }} <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="service_schedule" class="form-control"
                                       value="{{ old('service_schedule', $__reopenScheduleDefault) }}" required>
                                <div class="form-text text-muted small">{{ translate('New_schedule_date_time_reopen_help') }}</div>
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
    @endif
@endcan
