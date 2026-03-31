{{-- Expects $tierService, $tierSpare (normalized commission groups for UI). --}}
<p class="fz-12 text-muted mb-3">{{ translate('Commission_service_spare_separate_boxes_hint') }}</p>

<div class="card border rounded cus-shadow p-20 mb-20 bg-white">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div class="flex-grow-1 pe-2">
            <h6 class="text-dark mb-1">{{ translate('Service_charges_commission') }}</h6>
            <p class="fz-12 text-muted mb-0">{{ translate('Service_charges_commission_help') }}</p>
        </div>
        <button type="button" class="btn btn-link text-decoration-none p-2 flex-shrink-0 rounded-circle js-toggle-commission-preview lh-1 border-0"
                title="{{ translate('Show_how_commission_applies') }}"
                aria-expanded="false"
                aria-label="{{ translate('Show_how_commission_applies') }}">
            <i class="material-icons fz-22 text-primary">info</i>
        </button>
    </div>
    <div class="d-flex flex-wrap gap-3 mb-3">
        <div class="form-check">
            <input class="form-check-input js-commission-mode-radio" type="radio" name="commission_service_mode" id="commission_service_mode_fixed" value="fixed" data-group="service"
                   {{ ($tierService['mode'] ?? '') === 'fixed' ? 'checked' : '' }}>
            <label class="form-check-label" for="commission_service_mode_fixed">{{ translate('Fixed_commission') }}</label>
        </div>
        <div class="form-check">
            <input class="form-check-input js-commission-mode-radio" type="radio" name="commission_service_mode" id="commission_service_mode_tiered" value="tiered" data-group="service"
                   {{ ($tierService['mode'] ?? 'tiered') === 'tiered' ? 'checked' : '' }}>
            <label class="form-check-label" for="commission_service_mode_tiered">{{ translate('Tiered_commission') }}</label>
        </div>
    </div>
    <div class="js-commission-fixed-wrap commission-service-fixed {{ ($tierService['mode'] ?? '') === 'fixed' ? '' : 'd-none' }}" data-group="service">
        <label class="form-label">{{ translate('Fixed_commission_amount') }} <span class="text-danger">*</span></label>
        <input type="number" class="form-control max-w-320" name="commission_service_fixed_amount" min="0" step="any"
               value="{{ $tierService['fixed_amount'] ?? 0 }}"
               placeholder="{{ translate('ex: 2') }}">
    </div>
    <div class="js-commission-tiered-wrap commission-service-tiered {{ ($tierService['mode'] ?? 'tiered') === 'tiered' ? '' : 'd-none' }}" data-group="service">
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-2">
                <thead>
                    <tr class="fz-12 text-muted">
                        <th>{{ translate('Range_from') }}</th>
                        <th>{{ translate('Range_to') }}</th>
                        <th>{{ translate('Unlimited_upper') }}</th>
                        <th style="min-width: 14rem;">{{ translate('Amount_type') }}</th>
                        <th>{{ translate('Amount') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="js-commission-tiers-tbody" data-field-prefix="commission_service_tiers">
                    @foreach(($tierService['tiers'] ?? []) as $ti => $tr)
                        <?php $toInf = ($tr['to'] ?? null) === null || ($tr['to'] ?? '') === ''; ?>
                        <tr class="js-commission-tier-row">
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-from-input" name="commission_service_tiers[{{ $ti }}][from]" min="0" step="any" value="{{ $tr['from'] ?? 0 }}">
                            </td>
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-to-input" name="commission_service_tiers[{{ $ti }}][to]" min="0" step="any" value="{{ $toInf ? '' : $tr['to'] }}" {{ $toInf ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <label class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input js-tier-to-infinite" name="commission_service_tiers[{{ $ti }}][to_infinite]" value="1" {{ $toInf ? 'checked' : '' }}>
                                    <span class="form-check-label fz-12">{{ translate('Infinite') }}</span>
                                </label>
                            </td>
                            <td style="min-width: 14rem; width: 14rem;">
                                <select class="form-select form-select-sm w-100" style="min-width: 13rem;" name="commission_service_tiers[{{ $ti }}][amount_type]">
                                    <option value="percentage" {{ ($tr['amount_type'] ?? '') === 'percentage' ? 'selected' : '' }}>{{ translate('Percentage') }}</option>
                                    <option value="fixed" {{ ($tr['amount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>{{ translate('Fixed_amount') }}</option>
                                </select>
                            </td>
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-amount-input" name="commission_service_tiers[{{ $ti }}][amount]" min="0" step="any" value="{{ $tr['amount'] ?? 0 }}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger js-remove-tier-row" {{ count($tierService['tiers'] ?? []) < 2 ? 'disabled' : '' }}>&times;</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn--secondary js-add-tier-row" data-field-prefix="commission_service_tiers">{{ translate('Add_tier') }}</button>
    </div>

    <div class="mt-4 pt-3 border-top commission-preview-block d-none js-commission-preview-panel">
        <p class="fw-semibold text-dark mb-2">{{ translate('Commission_applied_explainer_title') }}</p>
        <p class="fz-13 text-muted mb-0 js-commission-plain-english" data-plain-target="service"></p>
        <p class="fw-semibold text-dark mt-4 mb-2">{{ translate('Commission_preview_examples_title') }}</p>
        <p class="fz-12 text-muted mb-2">{{ translate('Commission_preview_disclaimer') }}</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white fz-12 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ translate('Preview_table_scenario') }}</th>
                        <th class="text-end">{{ translate('Preview_table_line_total') }}</th>
                        <th>{{ translate('Preview_table_tier_band') }}</th>
                        <th>{{ translate('Preview_table_rule') }}</th>
                        <th class="text-end">{{ translate('Preview_table_admin') }}</th>
                        <th class="text-end">{{ translate('Preview_table_provider') }}</th>
                    </tr>
                </thead>
                <tbody class="js-commission-preview-tbody" data-preview-type="service"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border rounded cus-shadow p-20 mb-3 bg-white">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div class="flex-grow-1 pe-2">
            <h6 class="text-dark mb-1">{{ translate('Spare_parts_commission_box') }}</h6>
            <p class="fz-12 text-muted mb-0">{{ translate('Spare_parts_commission_box_help') }}</p>
        </div>
        <button type="button" class="btn btn-link text-decoration-none p-2 flex-shrink-0 rounded-circle js-toggle-commission-preview lh-1 border-0"
                title="{{ translate('Show_how_commission_applies') }}"
                aria-expanded="false"
                aria-label="{{ translate('Show_how_commission_applies') }}">
            <i class="material-icons fz-22 text-primary">info</i>
        </button>
    </div>
    <div class="d-flex flex-wrap gap-3 mb-3">
        <div class="form-check">
            <input class="form-check-input js-commission-mode-radio" type="radio" name="commission_spare_mode" id="commission_spare_mode_fixed" value="fixed" data-group="spare"
                   {{ ($tierSpare['mode'] ?? '') === 'fixed' ? 'checked' : '' }}>
            <label class="form-check-label" for="commission_spare_mode_fixed">{{ translate('Fixed_commission') }}</label>
        </div>
        <div class="form-check">
            <input class="form-check-input js-commission-mode-radio" type="radio" name="commission_spare_mode" id="commission_spare_mode_tiered" value="tiered" data-group="spare"
                   {{ ($tierSpare['mode'] ?? 'tiered') === 'tiered' ? 'checked' : '' }}>
            <label class="form-check-label" for="commission_spare_mode_tiered">{{ translate('Tiered_commission') }}</label>
        </div>
    </div>
    <div class="js-commission-fixed-wrap commission-spare-fixed {{ ($tierSpare['mode'] ?? '') === 'fixed' ? '' : 'd-none' }}" data-group="spare">
        <label class="form-label">{{ translate('Fixed_commission_amount') }} <span class="text-danger">*</span></label>
        <input type="number" class="form-control max-w-320" name="commission_spare_fixed_amount" min="0" step="any"
               value="{{ $tierSpare['fixed_amount'] ?? 0 }}"
               placeholder="{{ translate('ex: 2') }}">
    </div>
    <div class="js-commission-tiered-wrap commission-spare-tiered {{ ($tierSpare['mode'] ?? 'tiered') === 'tiered' ? '' : 'd-none' }}" data-group="spare">
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-2">
                <thead>
                    <tr class="fz-12 text-muted">
                        <th>{{ translate('Range_from') }}</th>
                        <th>{{ translate('Range_to') }}</th>
                        <th>{{ translate('Unlimited_upper') }}</th>
                        <th style="min-width: 14rem;">{{ translate('Amount_type') }}</th>
                        <th>{{ translate('Amount') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="js-commission-tiers-tbody" data-field-prefix="commission_spare_tiers">
                    @foreach(($tierSpare['tiers'] ?? []) as $ti => $tr)
                        <?php $toInfSp = ($tr['to'] ?? null) === null || ($tr['to'] ?? '') === ''; ?>
                        <tr class="js-commission-tier-row">
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-from-input" name="commission_spare_tiers[{{ $ti }}][from]" min="0" step="any" value="{{ $tr['from'] ?? 0 }}">
                            </td>
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-to-input" name="commission_spare_tiers[{{ $ti }}][to]" min="0" step="any" value="{{ $toInfSp ? '' : $tr['to'] }}" {{ $toInfSp ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <label class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input js-tier-to-infinite" name="commission_spare_tiers[{{ $ti }}][to_infinite]" value="1" {{ $toInfSp ? 'checked' : '' }}>
                                    <span class="form-check-label fz-12">{{ translate('Infinite') }}</span>
                                </label>
                            </td>
                            <td style="min-width: 14rem; width: 14rem;">
                                <select class="form-select form-select-sm w-100" style="min-width: 13rem;" name="commission_spare_tiers[{{ $ti }}][amount_type]">
                                    <option value="percentage" {{ ($tr['amount_type'] ?? '') === 'percentage' ? 'selected' : '' }}>{{ translate('Percentage') }}</option>
                                    <option value="fixed" {{ ($tr['amount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>{{ translate('Fixed_amount') }}</option>
                                </select>
                            </td>
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-amount-input" name="commission_spare_tiers[{{ $ti }}][amount]" min="0" step="any" value="{{ $tr['amount'] ?? 0 }}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger js-remove-tier-row" {{ count($tierSpare['tiers'] ?? []) < 2 ? 'disabled' : '' }}>&times;</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn--secondary js-add-tier-row" data-field-prefix="commission_spare_tiers">{{ translate('Add_tier') }}</button>
    </div>

    <div class="mt-4 pt-3 border-top commission-preview-block d-none js-commission-preview-panel">
        <p class="fw-semibold text-dark mb-2">{{ translate('Commission_applied_explainer_title') }}</p>
        <p class="fz-13 text-muted mb-0 js-commission-plain-english" data-plain-target="spare"></p>
        <p class="fw-semibold text-dark mt-4 mb-2">{{ translate('Commission_preview_examples_title') }}</p>
        <p class="fz-12 text-muted mb-2">{{ translate('Commission_preview_disclaimer') }}</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white fz-12 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ translate('Preview_table_scenario') }}</th>
                        <th class="text-end">{{ translate('Preview_table_line_total') }}</th>
                        <th>{{ translate('Preview_table_tier_band') }}</th>
                        <th>{{ translate('Preview_table_rule') }}</th>
                        <th class="text-end">{{ translate('Preview_table_admin') }}</th>
                        <th class="text-end">{{ translate('Preview_table_provider') }}</th>
                    </tr>
                </thead>
                <tbody class="js-commission-preview-tbody" data-preview-type="spare"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border rounded cus-shadow p-20 mb-3 bg-soft-dark bg-opacity-10">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div class="flex-grow-1 pe-2">
            <h6 class="text-dark mb-1">{{ translate('Commission_preview_overall_title') }}</h6>
        </div>
        <button type="button" class="btn btn-link text-decoration-none p-2 flex-shrink-0 rounded-circle js-toggle-commission-preview lh-1 border-0"
                title="{{ translate('Show_how_commission_applies') }}"
                aria-expanded="false"
                aria-label="{{ translate('Show_how_commission_applies') }}">
            <i class="material-icons fz-22 text-primary">info</i>
        </button>
    </div>
    <div class="d-none js-commission-preview-panel">
        <p class="fz-12 text-muted mb-3">{{ translate('Commission_preview_overall_intro') }}</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white fz-12 mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ translate('Preview_table_scenario') }}</th>
                        <th class="text-end">{{ translate('Preview_mixed_service_part') }}</th>
                        <th class="text-end">{{ translate('Preview_mixed_spare_part') }}</th>
                        <th class="text-end">{{ translate('Preview_mixed_customer_total') }}</th>
                        <th class="text-end">{{ translate('Preview_mixed_total_admin') }}</th>
                        <th class="text-end">{{ translate('Preview_mixed_total_provider') }}</th>
                    </tr>
                </thead>
                <tbody class="js-commission-preview-tbody" data-preview-type="combined"></tbody>
            </table>
        </div>
    </div>
</div>
