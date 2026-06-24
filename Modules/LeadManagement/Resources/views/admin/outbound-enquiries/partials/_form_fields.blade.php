@php
    $formId = $formId ?? 'outbound-enquiry-form';
    $formPrefix = $formPrefix ?? 'outbound';
    $defaultCustomerName = $defaultCustomerName ?? old('customer_name');
    $defaultPhoneNumber = $defaultPhoneNumber ?? old('phone_number');
    $defaultRelatedLeadId = $defaultRelatedLeadId ?? old('related_lead_id');
    $defaultBookingId = $defaultBookingId ?? old('booking_id');
    $defaultContactedThrough = old('contacted_through', 'call');
    $defaultHandledBy = old('handled_by', $currentEmployeeId ?? auth()->id());
    $defaultStatusId = old('status_id');
    $defaultContactedAt = old('contacted_at', now()->format('Y-m-d\TH:i'));
    $defaultRemarks = old('remarks');
    $statusLinkTypes = collect($statuses ?? [])->mapWithKeys(fn ($status) => [
        (string) $status->id => $status->effectiveLinkType(),
    ])->all();
@endphp

<div class="row g-3 outbound-enquiry-form-fields" data-form-prefix="{{ $formPrefix }}">
    <div class="col-md-6">
        <label class="form-label">{{ translate('Customer_Name') }} *</label>
        <input type="text"
               class="form-control"
               name="customer_name"
               required
               value="{{ $defaultCustomerName }}"
               placeholder="{{ translate('Customer_Name') }} *">
        @error('customer_name')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ translate('Phone_Number') }} *</label>
        <input type="text"
               class="form-control"
               name="phone_number"
               required
               value="{{ $defaultPhoneNumber }}"
               placeholder="{{ translate('Phone_Number') }} *">
        @error('phone_number')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ translate('Contacted_Through') }} *</label>
        <select class="form-select js-select" name="contacted_through" required>
            <option value="call" {{ $defaultContactedThrough === 'call' ? 'selected' : '' }}>
                {{ translate('Call') }}
            </option>
            <option value="message" {{ $defaultContactedThrough === 'message' ? 'selected' : '' }}>
                {{ translate('Message') }}
            </option>
        </select>
        @error('contacted_through')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ translate('Handled_By') }} ({{ translate('name_of_employee') }}) *</label>
        <select class="form-select js-select" name="handled_by" required>
            <option value="">{{ translate('Select_employee') }}</option>
            @foreach(($employees ?? []) as $employee)
                @php
                    $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                    $label = $fullName ?: $employee->email;
                @endphp
                <option value="{{ $employee->id }}" {{ (string) $defaultHandledBy === (string) $employee->id ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('handled_by')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ translate('Status') }} *</label>
        <select class="form-select js-select outbound-enquiry-status-select"
                name="status_id"
                id="{{ $formPrefix }}-status-id"
                data-status-link-types='@json($statusLinkTypes)'
                required>
            <option value="">{{ translate('Select_Status') }}</option>
            @foreach(($statuses ?? []) as $status)
                <option value="{{ $status->id }}"
                        data-link-type="{{ $status->effectiveLinkType() }}"
                        {{ (string) $defaultStatusId === (string) $status->id ? 'selected' : '' }}>
                    {{ $status->name }}
                </option>
            @endforeach
        </select>
        @error('status_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ translate('Date_Time') }} *</label>
        <input type="datetime-local"
               class="form-control"
               name="contacted_at"
               required
               value="{{ $defaultContactedAt }}">
        @error('contacted_at')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 outbound-enquiry-lead-link-wrap d-none">
        <label class="form-label">{{ translate('Link_Lead') }} *</label>
        <select class="form-control outbound-enquiry-lead-select"
                name="related_lead_id"
                id="{{ $formPrefix }}-related-lead-id"
                data-selected="{{ $defaultRelatedLeadId }}"
                data-placeholder="{{ translate('Search_lead_by_name_phone_or_id') }}">
            @if($defaultRelatedLeadId)
                <option value="{{ $defaultRelatedLeadId }}" selected>{{ translate('Selected') }} #{{ $defaultRelatedLeadId }}</option>
            @endif
        </select>
        @error('related_lead_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 outbound-enquiry-booking-link-wrap d-none">
        <label class="form-label">{{ translate('Booking_ID') }} *</label>
        <select class="form-control outbound-enquiry-booking-select"
                name="booking_id"
                id="{{ $formPrefix }}-booking-id"
                data-selected="{{ $defaultBookingId }}"
                data-placeholder="{{ translate('Search_booking_by_id_customer_or_phone') }}">
            @if($defaultBookingId)
                <option value="{{ $defaultBookingId }}" selected>{{ translate('Selected') }} {{ $defaultBookingId }}</option>
            @endif
        </select>
        @error('booking_id')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <label class="form-label">{{ translate('Remarks') }}</label>
        <textarea class="form-control"
                  name="remarks"
                  rows="{{ $remarksRows ?? 3 }}"
                  placeholder="{{ translate('Remarks') }}">{{ $defaultRemarks }}</textarea>
        @error('remarks')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>
