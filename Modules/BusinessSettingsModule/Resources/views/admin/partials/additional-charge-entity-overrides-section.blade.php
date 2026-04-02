{{-- Expects $additionalChargeOverrideRows from AdditionalChargeEntityOverrides::rowsForEntity(), $formSelector --}}
@php($chargeSectionShell = $chargeSectionShell ?? false)
@if(!$chargeSectionShell)
<div class="col-12">
    <div class="mb-30">
        <h5 class="mb-2 text-dark">{{ translate('Additional_charges') }}</h5>
        <p class="fz-12 text-muted mb-3">{{ translate('Additional_charges_entity_hint') }}</p>
@else
<div class="mb-3 pb-3 border-bottom border-light">
    <h5 class="mb-0 text-dark">{{ translate('Additional_charges') }}</h5>
    <p class="text-muted fz-12 mb-0 mt-2">{{ translate('Additional_charges_entity_hint') }}</p>
</div>
<div>
@endif

        @forelse($additionalChargeOverrideRows as $row)
            @php($type = $row['type'])
            @php($tid = $type->id)
            @php($suffix = '['.$tid.']')
            <div class="ac-entity-type-block mb-4">
                <div class="form-check mb-3">
                    <input class="form-check-input ac-custom-check" type="checkbox" name="ac_custom[{{ $tid }}]" value="1" id="ac_custom_{{ $tid }}"
                        {{ !empty($row['use_custom']) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="ac_custom_{{ $tid }}">{{ $type->name }}</label>
                    <span class="d-block text-muted fz-12 ms-4">{{ translate('Additional_charge_use_custom_for_entity') }}</span>
                </div>
                <div class="ac-custom-fields {{ !empty($row['use_custom']) ? '' : 'd-none' }}">
                    @include('businesssettingsmodule::admin.partials.additional-charge-setup-fields', [
                        'tier' => $row['tier'],
                        'fieldSuffix' => $suffix,
                    ])
                </div>
            </div>
        @empty
            <p class="fz-13 text-muted mb-0">{{ translate('No_additional_charge_types_yet') }}</p>
        @endforelse
@if($chargeSectionShell)
</div>
@else
    </div>
</div>
@endif

{{-- Scripts must run after layout loads jQuery (@yield content is before jQuery in master). --}}
@isset($formSelector)
    @push('script')
        @include('businesssettingsmodule::admin.partials.additional-charge-form-scripts', ['formSelector' => $formSelector])
    @endpush
@endisset
