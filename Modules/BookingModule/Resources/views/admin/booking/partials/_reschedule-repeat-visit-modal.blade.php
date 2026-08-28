@php
    $rescheduleModalId = $rescheduleModalId ?? ('reschedule-' . $repeatId);
    $__rescheduleValue = '';
    try {
        $__rescheduleValue = \Carbon\Carbon::parse($schedule)->format('Y-m-d\TH:i');
    } catch (\Throwable $e) {
        $__rescheduleValue = '';
    }
@endphp
<div class="modal fade" id="{{ $rescheduleModalId }}" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Reschedule_visit') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
            </div>
            <form action="{{ route('admin.booking.up_coming_booking_schedule_update', [$repeatId]) }}" method="post">
                @csrf
                <div class="modal-body">
                    <p class="text-muted">{{ translate('Reschedule_visit_help') }}</p>
                    <label class="form-label" for="{{ $rescheduleModalId }}-schedule">{{ translate('Visit_date_and_time') }}</label>
                    <input type="datetime-local" class="form-control h-45" name="service_schedule"
                           id="{{ $rescheduleModalId }}-schedule" value="{{ $__rescheduleValue }}" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Save changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
