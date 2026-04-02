{{-- Expects $tier (normalized group), optional $fieldSuffix e.g. '' or '[uuid]' for nested names --}}
@php($s = $fieldSuffix ?? '')
@php($acModeIdBase = 'ac_charge_mode' . ($s === '' ? '_main' : '_' . trim($s, '[]')))
<div class="js-ac-block">
    <div class="d-flex flex-wrap gap-3 mb-3">
        <div class="form-check">
            <input class="form-check-input js-ac-mode-radio" type="radio" name="ac_mode{{ $s }}" id="{{ $acModeIdBase }}_fixed" value="fixed"
                {{ ($tier['mode'] ?? '') === 'fixed' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $acModeIdBase }}_fixed">{{ translate('Fixed_commission') }}</label>
        </div>
        <div class="form-check">
            <input class="form-check-input js-ac-mode-radio" type="radio" name="ac_mode{{ $s }}" id="{{ $acModeIdBase }}_tiered" value="tiered"
                {{ ($tier['mode'] ?? 'tiered') === 'tiered' ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $acModeIdBase }}_tiered">{{ translate('Tiered_commission') }}</label>
        </div>
    </div>
    <div class="js-ac-fixed-wrap {{ ($tier['mode'] ?? '') === 'fixed' ? '' : 'd-none' }}">
        <label class="form-label">{{ translate('Fixed_commission_amount') }} <span class="text-danger">*</span></label>
        <input type="number" class="form-control max-w-320" name="ac_fixed{{ $s }}" min="0" step="any"
               value="{{ $tier['fixed_amount'] ?? 0 }}"
               placeholder="{{ translate('ex: 2') }}">
    </div>
    <div class="js-ac-tiered-wrap {{ ($tier['mode'] ?? 'tiered') === 'tiered' ? '' : 'd-none' }}">
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
                <tbody class="js-ac-tiers-tbody" data-field-suffix="{{ $s }}">
                    @php($tiersList = count($tier['tiers'] ?? []) > 0 ? $tier['tiers'] : [['from' => 0, 'to' => null, 'amount_type' => 'percentage', 'amount' => 0]])
                    @foreach($tiersList as $ti => $tr)
                        <?php $toInf = ($tr['to'] ?? null) === null || ($tr['to'] ?? '') === ''; ?>
                        <tr class="js-ac-tier-row">
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-from-input" name="ac_tiers{{ $s }}[{{ $ti }}][from]" min="0" step="any" value="{{ $tr['from'] ?? 0 }}">
                            </td>
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-to-input" name="ac_tiers{{ $s }}[{{ $ti }}][to]" min="0" step="any" value="{{ $toInf ? '' : $tr['to'] }}" {{ $toInf ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <label class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input js-tier-to-infinite" value="1" {{ $toInf ? 'checked' : '' }}>
                                    <span class="form-check-label fz-12">{{ translate('Infinite') }}</span>
                                </label>
                            </td>
                            <td style="min-width: 14rem;">
                                <select class="form-select form-select-sm" name="ac_tiers{{ $s }}[{{ $ti }}][amount_type]">
                                    <option value="percentage" {{ ($tr['amount_type'] ?? '') === 'percentage' ? 'selected' : '' }}>{{ translate('Percentage') }}</option>
                                    <option value="fixed" {{ ($tr['amount_type'] ?? '') === 'fixed' ? 'selected' : '' }}>{{ translate('Fixed_amount') }}</option>
                                </select>
                            </td>
                            <td style="min-width:7rem">
                                <input type="number" class="form-control form-control-sm js-tier-amount-input" name="ac_tiers{{ $s }}[{{ $ti }}][amount]" min="0" step="any" value="{{ $tr['amount'] ?? 0 }}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger js-ac-remove-tier" {{ count($tiersList) < 2 ? 'disabled' : '' }}>&times;</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn--secondary js-ac-add-tier" data-field-suffix="{{ $s }}">{{ translate('Add_tier') }}</button>
    </div>
</div>
