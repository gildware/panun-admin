@php
    $wrapId = $wrapId ?? 'handled-by-employees-wrap';
    $selectId = $selectId ?? 'handled-by-employee-ids';
    $selectedIds = array_map('strval', (array) ($selectedIds ?? []));
@endphp
<div id="{{ $wrapId }}" class="mt-2 d-none">
    @include('leadmanagement::admin.voice-calls._form_field_label', [
        'label' => translate('Select_employee'),
        'hint' => translate('Voice_field_hint_wa_handled_by_employee'),
    ])
    <select class="form-select js-select"
            name="handled_by_employee_ids[]"
            id="{{ $selectId }}"
            multiple
            disabled
            data-placeholder="{{ translate('Select_employee') }}">
        @foreach(($employees ?? []) as $employee)
            @php
                $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                $label = $fullName ?: ($employee->email ?? $employee->id);
            @endphp
            <option value="{{ $employee->id }}" {{ in_array((string) $employee->id, $selectedIds, true) ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>
