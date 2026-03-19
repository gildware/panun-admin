@extends('adminmodule::layouts.new-master')

@section('title', translate('Add_Outbound_Enquiry'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-3">
                        <h2 class="page-title">{{ translate('Add_Outbound_Enquiry') }}</h2>
                        <a href="{{ route('admin.lead.outbound-enquiry.index') }}" class="btn btn--secondary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <form action="{{ route('admin.lead.outbound-enquiry.store') }}" method="post">
                                @csrf

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Customer_Name') }} *</label>
                                            <input type="text"
                                                   class="form-control"
                                                   name="customer_name"
                                                   required
                                                   value="{{ old('customer_name') }}"
                                                   placeholder="{{ translate('Customer_Name') }} *">
                                            @error('customer_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Phone_Number') }} *</label>
                                            <input type="text"
                                                   class="form-control"
                                                   name="phone_number"
                                                   required
                                                   value="{{ old('phone_number') }}"
                                                   placeholder="{{ translate('Phone_Number') }} *">
                                            @error('phone_number')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Contacted_Through') }} *</label>
                                            <select class="form-select js-select" name="contacted_through" required>
                                                <option value="message" {{ old('contacted_through', 'message') === 'message' ? 'selected' : '' }}>
                                                    {{ translate('Message') }}
                                                </option>
                                                <option value="call" {{ old('contacted_through') === 'call' ? 'selected' : '' }}>
                                                    {{ translate('Call') }}
                                                </option>
                                            </select>
                                            @error('contacted_through')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Handled_By') }} ({{ translate('name_of_employee') }}) *</label>
                                            <select class="form-select js-select" name="handled_by" required>
                                                <option value="">{{ translate('Select_employee') }}</option>
                                                @foreach(($employees ?? []) as $employee)
                                                    @php
                                                        $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                                        $label = $fullName ?: $employee->email;
                                                    @endphp
                                                    <option value="{{ $employee->id }}"
                                                            {{ old('handled_by', $currentEmployeeId ?? null) == $employee->id ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('handled_by')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Status') }} *</label>
                                            <select class="form-select js-select" name="status_id" required>
                                                <option value="">{{ translate('Select_Status') }}</option>
                                                @foreach(($statuses ?? []) as $status)
                                                    <option value="{{ $status->id }}" {{ (string) old('status_id') === (string) $status->id ? 'selected' : '' }}>
                                                        {{ $status->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Date_Time') }} *</label>
                                            <input type="datetime-local"
                                                   class="form-control"
                                                   name="contacted_at"
                                                   required
                                                   value="{{ old('contacted_at', now()->format('Y-m-d\TH:i')) }}">
                                            @error('contacted_at')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-30">
                                            <label class="form-label">{{ translate('Remarks') }}</label>
                                            <textarea class="form-control"
                                                      name="remarks"
                                                      rows="4"
                                                      placeholder="{{ translate('Remarks') }}">{{ old('remarks') }}</textarea>
                                            @error('remarks')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-20 mt-10">
                                            <a href="{{ route('admin.lead.outbound-enquiry.index') }}" class="btn btn--secondary">
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

