@php
    $canConvertToRepeat = app(\Modules\BookingModule\Services\AdminRepeatBookingScheduleService::class)->canConvert($booking);
@endphp
@can('booking_edit')
@if($canConvertToRepeat)
<div class="modal fade" id="convertToRepeatModal--{{ $booking->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.booking.convert_to_repeat', $booking->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Convert_to_repeat_booking') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">{{ translate('Convert_to_repeat_help') }}</p>
                    <p class="mb-3">
                        <strong>{{ translate('This_booking_becomes_visit_1') }}:</strong>
                        {{ \Carbon\Carbon::parse($booking->service_schedule)->format('d M Y, h:i A') }}
                    </p>
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Repeat_type') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach(['daily' => translate('Daily'), 'weekly' => translate('Weekly'), 'monthly' => translate('Monthly'), 'yearly' => translate('Yearly')] as $typeKey => $typeLabel)
                                <div class="form-check">
                                    <input class="form-check-input js-convert-repeat-type" type="radio"
                                           name="repeat_booking_type" id="convert-repeat-{{ $typeKey }}-{{ $booking->id }}"
                                           value="{{ $typeKey }}" {{ $typeKey === 'monthly' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="convert-repeat-{{ $typeKey }}-{{ $booking->id }}">{{ $typeLabel }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label js-convert-visits-label" for="convert-planned-visits-{{ $booking->id }}">{{ translate('Visits_per_month') }}</label>
                        <input type="number" min="1" max="31" name="repeat_planned_visits"
                               id="convert-planned-visits-{{ $booking->id }}" class="form-control js-convert-planned-visits-input"
                               value="{{ old('repeat_planned_visits', 1) }}">
                        <small class="text-muted d-block mt-1">{{ translate('Visits_per_period_help') }}</small>
                        @error('repeat_planned_visits')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="convert-end-date-{{ $booking->id }}">{{ translate('Repeat_until_date') }}</label>
                        <input type="date" name="repeat_end_date" id="convert-end-date-{{ $booking->id }}" class="form-control"
                               value="{{ old('repeat_end_date') }}">
                        <small class="text-muted d-block mt-1">{{ translate('Repeat_end_date_optional_help') }}</small>
                        @error('repeat_end_date')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Convert_to_repeat_booking') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('convertToRepeatModal--{{ $booking->id }}');
        if (!modal) {
            return;
        }
        var labels = {
            daily: '{{ translate('Visits_per_day') }}',
            weekly: '{{ translate('Visits_per_week') }}',
            monthly: '{{ translate('Visits_per_month') }}',
            yearly: '{{ translate('Visits_per_year') }}'
        };
        var maxByType = { daily: 8, weekly: 14, monthly: 31, yearly: 52 };
        function syncConvertFields() {
            var type = (modal.querySelector('input[name="repeat_booking_type"]:checked') || {}).value || 'monthly';
            modal.querySelectorAll('.js-convert-visits-label').forEach(function (el) {
                el.textContent = labels[type] || labels.monthly;
            });
            var input = modal.querySelector('.js-convert-planned-visits-input');
            if (input) {
                input.setAttribute('max', String(maxByType[type] || 31));
            }
        }
        modal.querySelectorAll('.js-convert-repeat-type').forEach(function (el) {
            el.addEventListener('change', syncConvertFields);
        });
        syncConvertFields();
        @if($errors->has('repeat_planned_visits') || $errors->has('repeat_booking_type') || $errors->has('repeat_end_date'))
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        @endif
    });
</script>
@endpush
@endif
@endcan
