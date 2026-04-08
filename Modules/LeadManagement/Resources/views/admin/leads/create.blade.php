@extends('adminmodule::layouts.new-master')

@section('title', translate('Add_New_Lead'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{ translate('Add_New_Lead') }}</h2>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <form action="{{ route('admin.lead.store') }}" method="post" id="lead-add-form">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Name') }}</label>
                                            <input type="text" class="form-control" name="name"
                                                   placeholder="{{ translate('Name') }}"
                                                   value="{{ old('name') }}">
                                            @error('name')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Phone_Number') }} *</label>
                                            <input type="text" class="form-control js-lead-create-phone" name="phone_number"
                                                   placeholder="{{ translate('Phone_Number') }} *"
                                                   required value="{{ old('phone_number') }}">
                                            @error('phone_number')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Source') }}</label>
                                            <select class="form-select js-select" name="source_id">
                                                <option value="">{{ translate('Select_Source') }}</option>
                                                @foreach($sources as $source)
                                                    <option value="{{ $source->id }}" {{ old('source_id') == $source->id ? 'selected' : '' }}>
                                                        {{ $source->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('source_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Lead_Type') }} *</label>
                                            <select class="form-select js-select" name="lead_type" id="lead-create-type-select" required>
                                                @foreach(\Modules\LeadManagement\Entities\Lead::leadTypes() as $value => $label)
                                                    <option value="{{ $value }}" {{ old('lead_type', 'unknown') == $value ? 'selected' : '' }}>
                                                        {{ translate($label) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('lead_type')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Date_Time_Of_Lead_Received') }}</label>
                                            <input type="datetime-local" class="form-control" name="date_time_of_lead_received"
                                                   value="{{ old('date_time_of_lead_received', now()->format('Y-m-d\TH:i')) }}">
                                            @error('date_time_of_lead_received')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Leads_Ad_Source') }}</label>
                                            <select class="form-select js-select" name="ad_source_id" id="lead-ad-source-select">
                                                <option value="">{{ translate('Select_Ad_Source') }}</option>
                                                @foreach($adSources as $adSource)
                                                    <option value="{{ $adSource->id }}"
                                                            data-image="{{ $adSource->image ? asset('storage/ad-source/' . $adSource->image) : asset('assets/placeholder.png') }}"
                                                            {{ old('ad_source_id') == $adSource->id ? 'selected' : '' }}>
                                                        {{ $adSource->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('ad_source_id')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Handled_By') }} ({{ translate('name_of_employee') }})</label>
                                            <select class="form-select js-select" name="handled_by" id="lead-handled-by-select">
                                                <option value="">{{ translate('Select_employee') }}</option>
                                                @foreach($employees as $employee)
                                                    @php
                                                        $fullName = trim($employee->first_name . ' ' . $employee->last_name);
                                                        $label = $fullName ?: $employee->email;
                                                    @endphp
                                                    <option value="{{ $employee->id }}"
                                                            {{ old('handled_by', $currentEmployeeId) == $employee->id ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('handled_by')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30" id="lead-create-followup-wrap">
                                            <label class="form-label">{{ translate('Next_Follow_up_Date') }}</label>
                                            <input type="datetime-local" class="form-control" name="next_followup_at"
                                                   id="lead-create-next-followup-input"
                                                   value="{{ old('next_followup_at', now()->addDay()->format('Y-m-d\TH:i')) }}">
                                            @error('next_followup_at')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-30 d-none" id="lead-create-invalid-fields">
                                            <div class="mb-30">
                                                <label class="form-label">{{ translate('Reason') }}</label>
                                                <select name="invalid_reason_id" class="form-select js-select" id="lead-create-invalid-reason">
                                                    <option value="">{{ translate('Select_Reason') }}</option>
                                                    @foreach($invalidReasons as $reason)
                                                        <option value="{{ $reason->id }}" {{ (string) old('invalid_reason_id') === (string) $reason->id ? 'selected' : '' }}>
                                                            {{ $reason->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('invalid_reason_id')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label">{{ translate('Remarks') }}</label>
                                                <textarea name="invalid_remarks" class="form-control" rows="3" placeholder="{{ translate('Remarks') }}">{{ old('invalid_remarks') }}</textarea>
                                                @error('invalid_remarks')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-30 d-none" id="lead-create-future-fields">
                                            <div class="mb-30">
                                                <label class="form-label">{{ translate('Reason') }}</label>
                                                <select name="future_customer_reason_id" class="form-select js-select" id="lead-create-future-reason">
                                                    <option value="">{{ translate('Select_Reason') }}</option>
                                                    @foreach($futureCustomerReasons as $reason)
                                                        <option value="{{ $reason->id }}" {{ (string) old('future_customer_reason_id') === (string) $reason->id ? 'selected' : '' }}>
                                                            {{ $reason->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('future_customer_reason_id')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mb-0">
                                                <label class="form-label">{{ translate('Remarks') }}</label>
                                                <textarea name="future_customer_remarks" class="form-control" rows="3" placeholder="{{ translate('Remarks') }}">{{ old('future_customer_remarks') }}</textarea>
                                                @error('future_customer_remarks')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-30" id="lead-create-general-remarks-wrap">
                                            <label class="form-label">{{ translate('Remarks') }}</label>
                                            <textarea class="form-control" name="remarks" id="lead-create-remarks" rows="3" placeholder="{{ translate('Remarks') }}">{{ old('remarks') }}</textarea>
                                            @error('remarks')
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30 d-none" data-lead-open-duplicates-alert></div>

                                        <div class="d-flex justify-content-end gap-20 mt-30">
                                            <a href="{{ route('admin.lead.index') }}" class="btn btn--secondary">
                                                {{ translate('Cancel') }}
                                            </a>
                                            <button class="btn btn--primary" type="submit">
                                                {{ translate('Submit') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        $(document).ready(function () {
            function formatAdSource(option) {
                if (!option.id) {
                    return option.text;
                }
                const $element = $(option.element);
                const image = $element.data('image');
                if (!image) {
                    return option.text;
                }
                return $(
                    '<span class="d-flex align-items-center gap-2">' +
                        '<img src="' + image + '" alt="" style="width:24px;height:24px;object-fit:cover;border-radius:4px;" onerror="this.src=\'{{ asset('assets/placeholder.png') }}\'">' +
                        '<span>' + option.text + '</span>' +
                    '</span>'
                );
            }

            const $adSourceSelect = $('#lead-ad-source-select');
            if ($adSourceSelect.length) {
                $adSourceSelect.select2('destroy');
                $adSourceSelect.select2({
                    templateResult: formatAdSource,
                    templateSelection: formatAdSource,
                    width: '100%',
                    escapeMarkup: function (m) { return m; }
                });
            }

            const TYPE_INVALID = '{{ \Modules\LeadManagement\Entities\Lead::TYPE_INVALID }}';
            const TYPE_FUTURE = '{{ \Modules\LeadManagement\Entities\Lead::TYPE_FUTURE_CUSTOMER }}';
            const $typeSelect = $('#lead-create-type-select');
            const $followWrap = $('#lead-create-followup-wrap');
            const $followInput = $('#lead-create-next-followup-input');
            const $invalidBlock = $('#lead-create-invalid-fields');
            const $futureBlock = $('#lead-create-future-fields');
            const $generalRemarksWrap = $('#lead-create-general-remarks-wrap');
            const $generalRemarks = $('#lead-create-remarks');
            const $invalidReason = $('#lead-create-invalid-reason');
            const $futureReason = $('#lead-create-future-reason');

            function leadCreateApplyTypeUi() {
                const t = $typeSelect.val();
                const isInvalid = t === TYPE_INVALID;
                const isFuture = t === TYPE_FUTURE;

                $followWrap.toggleClass('d-none', isInvalid || isFuture);
                $invalidBlock.toggleClass('d-none', !isInvalid);
                $futureBlock.toggleClass('d-none', !isFuture);
                $generalRemarksWrap.toggleClass('d-none', isInvalid || isFuture);

                $followInput.prop('disabled', isInvalid || isFuture);
                $generalRemarks.prop('disabled', isInvalid || isFuture);

                if ($invalidReason.length) {
                    $invalidReason.prop('required', isInvalid);
                }
                if ($futureReason.length) {
                    $futureReason.prop('required', isFuture);
                }
            }

            $typeSelect.on('change select2:select', leadCreateApplyTypeUi);
            leadCreateApplyTypeUi();
        });
    </script>
    @include('leadmanagement::admin.leads.partials._lead_open_phone_check_script')
@endpush
