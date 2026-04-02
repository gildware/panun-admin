{{-- Expects $additionalChargeOverrideRows from AdditionalChargeEntityOverrides::rowsForEntity(), $formSelector --}}
<div class="col-12">
    <div class="card border rounded p-20 mb-30 bg-white">
        <h5 class="mb-2 text-dark">{{ translate('Additional_charges') }}</h5>
        <p class="fz-12 text-muted mb-3">{{ translate('Additional_charges_entity_hint') }}</p>

        @forelse($additionalChargeOverrideRows as $row)
            @php($type = $row['type'])
            @php($tid = $type->id)
            @php($suffix = '['.$tid.']')
            <div class="card border rounded p-15 mb-15 ac-entity-type-block bg-light">
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
    </div>
</div>

@isset($formSelector)
    @include('businesssettingsmodule::admin.partials.additional-charge-form-scripts', ['formSelector' => $formSelector])
@endisset
