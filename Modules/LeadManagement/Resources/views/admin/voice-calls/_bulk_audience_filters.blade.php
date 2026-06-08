@php
    use Modules\LeadManagement\Services\VoiceBulkAudienceService;
    $oldKind = old('recipient_kind', '');
    $oldLeadTypes = (array) old('lead_types', []);
    $bookingStatuses = ['pending', 'accepted', 'ongoing', 'on_hold', 'completed', 'canceled', 'refunded'];
@endphp

<div class="col-12">
    @include('leadmanagement::admin.voice-calls._form_field_label', [
        'label' => translate('Voice_bulk_recipient_kind'),
        'required' => true,
        'hint' => translate('Voice_field_hint_recipient_kind'),
    ])
    <select class="form-select js-select @error('recipient_kind') is-invalid @enderror" name="recipient_kind" id="voice_bulk_recipient_kind">
        <option value="">{{ translate('Select') }}</option>
        <option value="customer" {{ $oldKind === 'customer' ? 'selected' : '' }}>{{ translate('Customer') }}</option>
        <option value="provider" {{ $oldKind === 'provider' ? 'selected' : '' }}>{{ translate('Provider') }}</option>
        <option value="lead" {{ $oldKind === 'lead' ? 'selected' : '' }}>{{ translate('Lead') }}</option>
        <option value="csv_import" {{ $oldKind === 'csv_import' ? 'selected' : '' }}>{{ translate('Import_Contacts_CSV') }}</option>
    </select>
    @error('recipient_kind')
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div id="voice_bulk_customer_filters" class="col-12 {{ $oldKind === 'customer' ? '' : 'd-none' }}">
    <div class="border rounded p-3 bg-white">
        <h6 class="mb-3">{{ translate('Customer') }} {{ translate('filters') }}</h6>
        <div class="row g-3">
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Account_status'),
                    'hint' => translate('Voice_field_hint_customer_active'),
                ])
                <select class="form-select" name="customer_active">
                    <option value="all" {{ old('customer_active', 'active') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                    <option value="active" {{ old('customer_active', 'active') === 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                    <option value="inactive" {{ old('customer_active') === 'inactive' ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_has_booking'),
                    'hint' => translate('Voice_field_hint_has_booking'),
                ])
                <select class="form-select" name="customer_has_booking">
                    <option value="all" {{ old('customer_has_booking', 'all') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                    <option value="yes" {{ old('customer_has_booking') === 'yes' ? 'selected' : '' }}>{{ translate('Yes') }}</option>
                    <option value="no" {{ old('customer_has_booking') === 'no' ? 'selected' : '' }}>{{ translate('No') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('category'),
                    'hint' => translate('Voice_field_hint_customer_booking_category'),
                ])
                <select class="form-select js-select" name="customer_category_id">
                    <option value="">{{ translate('All') }}</option>
                    @foreach(($categories ?? []) as $cat)
                        <option value="{{ $cat->id }}" {{ (string) old('customer_category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('sub_category'),
                    'hint' => translate('Voice_field_hint_customer_booking_subcategory'),
                ])
                <select class="form-select js-select" name="customer_sub_category_id">
                    <option value="">{{ translate('All') }}</option>
                    @foreach(($subCategories ?? []) as $cat)
                        <option value="{{ $cat->id }}" {{ (string) old('customer_sub_category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('zone'),
                    'hint' => translate('Voice_field_hint_customer_booking_zone'),
                ])
                <select class="form-select js-select" name="customer_zone_id">
                    <option value="">{{ translate('All') }}</option>
                    @foreach(($zones ?? []) as $zone)
                        <option value="{{ $zone->id }}" {{ (string) old('customer_zone_id') === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_last_booking_status'),
                    'hint' => translate('Voice_field_hint_last_booking_status'),
                ])
                <select class="form-select" name="customer_booking_status">
                    <option value="">{{ translate('All') }}</option>
                    @foreach($bookingStatuses as $status)
                        <option value="{{ $status }}" {{ old('customer_booking_status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_last_booking_on'),
                    'hint' => translate('Voice_field_hint_last_booking_on'),
                ])
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control" name="customer_last_booking_from" value="{{ old('customer_last_booking_from') }}">
                    </div>
                    <div class="col-6">
                        <input type="date" class="form-control" name="customer_last_booking_to" value="{{ old('customer_last_booking_to') }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Registration_date'),
                    'hint' => translate('Voice_field_hint_customer_registered'),
                ])
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control" name="customer_registered_from" value="{{ old('customer_registered_from') }}">
                    </div>
                    <div class="col-6">
                        <input type="date" class="form-control" name="customer_registered_to" value="{{ old('customer_registered_to') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="voice_bulk_provider_filters" class="col-12 {{ $oldKind === 'provider' ? '' : 'd-none' }}">
    <div class="border rounded p-3 bg-white">
        <h6 class="mb-3">{{ translate('Provider') }} {{ translate('filters') }}</h6>
        <div class="row g-3">
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('category'),
                    'hint' => translate('Voice_field_hint_provider_subscribed_category'),
                ])
                <select class="form-select js-select" name="provider_category_id">
                    <option value="">{{ translate('All') }}</option>
                    @foreach(($categories ?? []) as $cat)
                        <option value="{{ $cat->id }}" {{ (string) old('provider_category_id') === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                            ({{ (int) ($categoryRecipientCounts[(string) $cat->id] ?? 0) }} {{ translate('providers') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('zone'),
                    'hint' => translate('Voice_field_hint_provider_zone'),
                ])
                <select class="form-select js-select" name="provider_zone_id">
                    <option value="">{{ translate('All') }}</option>
                    @foreach(($zones ?? []) as $zone)
                        <option value="{{ $zone->id }}" {{ (string) old('provider_zone_id') === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_has_booking'),
                    'hint' => translate('Voice_field_hint_provider_has_booking'),
                ])
                <select class="form-select" name="provider_has_booking">
                    <option value="all" {{ old('provider_has_booking', 'all') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                    <option value="yes" {{ old('provider_has_booking') === 'yes' ? 'selected' : '' }}>{{ translate('Yes') }}</option>
                    <option value="no" {{ old('provider_has_booking') === 'no' ? 'selected' : '' }}>{{ translate('No') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_last_booking_category'),
                    'hint' => translate('Voice_field_hint_provider_last_booking_category'),
                ])
                <select class="form-select js-select" name="provider_booking_category_id">
                    <option value="">{{ translate('All') }}</option>
                    @foreach(($categories ?? []) as $cat)
                        <option value="{{ $cat->id }}" {{ (string) old('provider_booking_category_id') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_last_booking_status'),
                    'hint' => translate('Voice_field_hint_last_booking_status'),
                ])
                <select class="form-select" name="provider_booking_status">
                    <option value="">{{ translate('All') }}</option>
                    @foreach($bookingStatuses as $status)
                        <option value="{{ $status }}" {{ old('provider_booking_status') === $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Voice_bulk_last_booking_on'),
                    'hint' => translate('Voice_field_hint_last_booking_on'),
                ])
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="date" class="form-control" name="provider_last_booking_from" value="{{ old('provider_last_booking_from') }}">
                    </div>
                    <div class="col-md-6">
                        <input type="date" class="form-control" name="provider_last_booking_to" value="{{ old('provider_last_booking_to') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="voice_bulk_lead_filters" class="col-12 {{ $oldKind === 'lead' ? '' : 'd-none' }}">
    <div class="border rounded p-3 bg-white">
        <h6 class="mb-3">{{ translate('Lead') }} {{ translate('filters') }}</h6>
        <div class="row g-3">
            <div class="col-md-6">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Lead_type'),
                    'hint' => translate('Voice_field_hint_bulk_lead_types'),
                ])
                <select class="form-select js-select" name="lead_types[]" id="voice_bulk_lead_types" multiple>
                    @foreach(VoiceBulkAudienceService::LEAD_KIND_TYPES as $type)
                        @php $label = \Modules\LeadManagement\Entities\Lead::leadTypes()[$type] ?? $type; @endphp
                        <option value="{{ $type }}" {{ in_array($type, $oldLeadTypes, true) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Lead') . ' ' . translate('Status'),
                    'hint' => translate('Voice_field_hint_lead_open'),
                ])
                <select class="form-select" name="lead_open_status">
                    <option value="all" {{ old('lead_open_status', 'all') === 'all' ? 'selected' : '' }}>{{ translate('All') }}</option>
                    <option value="open" {{ old('lead_open_status') === 'open' ? 'selected' : '' }}>{{ translate('Open') }}</option>
                    <option value="closed" {{ old('lead_open_status') === 'closed' ? 'selected' : '' }}>{{ translate('Closed') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('source'),
                    'hint' => translate('Voice_field_hint_lead_source'),
                ])
                <select class="form-select js-select" name="lead_source_ids[]" multiple>
                    @foreach(($leadSources ?? []) as $source)
                        <option value="{{ $source->id }}" {{ in_array((string) $source->id, array_map('strval', (array) old('lead_source_ids', [])), true) ? 'selected' : '' }}>{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Ad_source'),
                    'hint' => translate('Voice_field_hint_lead_ad_source'),
                ])
                <select class="form-select js-select" name="lead_ad_source_ids[]" multiple>
                    @foreach(($leadAdSources ?? []) as $source)
                        <option value="{{ $source->id }}" {{ in_array((string) $source->id, array_map('strval', (array) old('lead_ad_source_ids', [])), true) ? 'selected' : '' }}>{{ $source->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Handled_By'),
                    'hint' => translate('Voice_field_hint_lead_handled_by'),
                ])
                <select class="form-select js-select" name="lead_handled_by[]" multiple>
                    <option value="{{ \Modules\LeadManagement\Entities\Lead::FILTER_UNASSIGNED_VALUE }}"
                            {{ in_array(\Modules\LeadManagement\Entities\Lead::FILTER_UNASSIGNED_VALUE, (array) old('lead_handled_by', []), true) ? 'selected' : '' }}>
                        {{ translate('Unassigned') }} / AI
                    </option>
                    @foreach(($employees ?? []) as $employee)
                        @php $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')); @endphp
                        <option value="{{ $employee->id }}" {{ in_array((string) $employee->id, array_map('strval', (array) old('lead_handled_by', [])), true) ? 'selected' : '' }}>
                            {{ $fullName ?: $employee->email }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                @include('leadmanagement::admin.voice-calls._form_field_label', [
                    'label' => translate('Lead_received_date'),
                    'hint' => translate('Voice_field_hint_lead_received_date'),
                ])
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control" name="lead_received_from" value="{{ old('lead_received_from') }}">
                    </div>
                    <div class="col-6">
                        <input type="date" class="form-control" name="lead_received_to" value="{{ old('lead_received_to') }}">
                    </div>
                </div>
            </div>

            <div id="voice_bulk_lead_customer_subfilters" class="col-12 {{ in_array('customer', $oldLeadTypes, true) ? '' : 'd-none' }}">
                <hr class="my-2">
                <div class="small fw-medium mb-2">{{ translate('Voice_bulk_lead_customer_filters') }}</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('Lead_Status'),
                            'hint' => translate('Voice_field_hint_customer_lead_status'),
                        ])
                        <select class="form-select js-select" name="customer_lead_status_ids[]" multiple>
                            @foreach(($customerLeadStatuses ?? []) as $status)
                                <option value="{{ $status->id }}" {{ in_array((string) $status->id, array_map('strval', (array) old('customer_lead_status_ids', [])), true) ? 'selected' : '' }}>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('zone'),
                            'hint' => translate('Voice_field_hint_customer_lead_zone'),
                        ])
                        <select class="form-select js-select" name="customer_lead_zone_ids[]" multiple>
                            @foreach(($zones ?? []) as $zone)
                                <option value="{{ $zone->id }}" {{ in_array((string) $zone->id, array_map('strval', (array) old('customer_lead_zone_ids', [])), true) ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('Customer_lead_tags'),
                            'hint' => translate('Voice_field_hint_customer_lead_tags'),
                        ])
                        <select class="form-select js-select" name="customer_lead_tag_ids[]" multiple>
                            @foreach(($customerLeadTags ?? []) as $tag)
                                <option value="{{ $tag['id'] }}" {{ in_array((string) $tag['id'], array_map('strval', (array) old('customer_lead_tag_ids', [])), true) ? 'selected' : '' }}>{{ $tag['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('Service_Category'),
                            'hint' => translate('Voice_field_hint_customer_lead_category'),
                        ])
                        <select class="form-select js-select" name="customer_lead_category_ids[]" multiple>
                            @foreach(($categories ?? []) as $cat)
                                <option value="{{ $cat->id }}" {{ in_array((string) $cat->id, array_map('strval', (array) old('customer_lead_category_ids', [])), true) ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('sub_category'),
                            'hint' => translate('Voice_field_hint_customer_lead_subcategory'),
                        ])
                        <select class="form-select js-select" name="customer_lead_sub_category_ids[]" multiple>
                            @foreach(($subCategories ?? []) as $cat)
                                <option value="{{ $cat->id }}" {{ in_array((string) $cat->id, array_map('strval', (array) old('customer_lead_sub_category_ids', [])), true) ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('Estimated_service_date'),
                            'hint' => translate('Voice_field_hint_estimated_service_date'),
                        ])
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="date" class="form-control" name="estimated_service_from" value="{{ old('estimated_service_from') }}">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" name="estimated_service_to" value="{{ old('estimated_service_to') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="voice_bulk_lead_invalid_subfilters" class="col-12 {{ in_array('invalid', $oldLeadTypes, true) ? '' : 'd-none' }}">
                <hr class="my-2">
                <div class="small fw-medium mb-2">{{ translate('Voice_bulk_lead_invalid_filters') }}</div>
                <div class="col-md-6">
                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                        'label' => translate('Invalid_reason'),
                        'hint' => translate('Voice_field_hint_invalid_reason'),
                    ])
                    <select class="form-select js-select" name="invalid_reason_ids[]" multiple>
                        @foreach(($invalidReasons ?? []) as $reason)
                            <option value="{{ $reason->id }}" {{ in_array((string) $reason->id, array_map('strval', (array) old('invalid_reason_ids', [])), true) ? 'selected' : '' }}>{{ $reason->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="voice_bulk_lead_future_subfilters" class="col-12 {{ in_array('future_customer', $oldLeadTypes, true) ? '' : 'd-none' }}">
                <hr class="my-2">
                <div class="small fw-medium mb-2">{{ translate('Voice_bulk_lead_future_filters') }}</div>
                <div class="col-md-6">
                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                        'label' => translate('Future_customer_reason'),
                        'hint' => translate('Voice_field_hint_future_customer_reason'),
                    ])
                    <select class="form-select js-select" name="future_customer_reason_ids[]" multiple>
                        @foreach(($futureCustomerReasons ?? []) as $reason)
                            <option value="{{ $reason->id }}" {{ in_array((string) $reason->id, array_map('strval', (array) old('future_customer_reason_ids', [])), true) ? 'selected' : '' }}>{{ $reason->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
