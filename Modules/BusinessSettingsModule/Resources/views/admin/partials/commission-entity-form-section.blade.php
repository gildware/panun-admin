{{-- Expects $commissionEntityUseCustom (bool), $tierService, $tierSpare --}}
<div class="col-12">
    <div class="card border rounded p-20 mb-30 bg-white">
        <h5 class="mb-2 text-dark">{{ translate('Commission_Settings') }}</h5>
        <p class="fz-12 text-muted mb-3">{{ translate('Commission_entity_priority_hint') }}</p>
        <div class="d-flex flex-wrap align-items-start gap-4 mb-3">
            <div class="custom-radio">
                <input type="radio" name="commission_entity_mode" id="commission_entity_mode_default" value="default"
                       {{ ! $commissionEntityUseCustom ? 'checked' : '' }}>
                <label for="commission_entity_mode_default" class="d-block">{{ translate('Commission_use_company_default') }}</label>
                <span class="d-block text-muted fz-12 mt-1 ms-4">{{ translate('Commission_entity_inherit_help') }}</span>
            </div>
            <div class="custom-radio">
                <input type="radio" name="commission_entity_mode" id="commission_entity_mode_custom" value="custom"
                       {{ $commissionEntityUseCustom ? 'checked' : '' }}>
                <label for="commission_entity_mode_custom" class="d-block">{{ translate('Commission_entity_custom_here') }}</label>
                <span class="d-block text-muted fz-12 mt-1 ms-4">{{ translate('Commission_entity_custom_here_help') }}</span>
            </div>
        </div>
        <div id="entity-custom-commission-wrap" class="{{ $commissionEntityUseCustom ? '' : 'd-none' }}">
            <p class="fz-12 text-muted mb-3">{{ translate('Provider_custom_commission_tier_hint') }}</p>
            <div id="commission-tier-settings">
                @include('businesssettingsmodule::admin.partials.commission-tier-setup-fields', ['tierService' => $tierService, 'tierSpare' => $tierSpare])
            </div>
        </div>
    </div>
</div>
