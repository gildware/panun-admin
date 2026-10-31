{{-- Spare-part commission options. $named: true when posting via form; false for JS cart UIs (still uses radio name groups). --}}
@php
    $named = $named ?? true;
    $uid = $uid ?? 'extra';
    $includeName = $named ? 'commission_included' : ($uid.'_commission_included');
    $amountTypeName = $named ? 'commission_amount_type' : ($uid.'_commission_amount_type');
    $amountName = $named ? 'commission_amount' : null;
    $defaultIncluded = old('commission_included', $defaultIncluded ?? 'exclude');
    $defaultAmountType = old('commission_amount_type', $defaultAmountType ?? 'percentage');
    $defaultAmount = old('commission_amount', $defaultAmount ?? 0);
@endphp
<div class="js-spare-commission-fields d-none" data-spare-commission-root>
    <div class="mb-3">
        <label class="form-label">{{ translate('Spare_part_commission') }}</label>
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input js-spare-commission-include"
                       type="radio"
                       name="{{ $includeName }}"
                       id="{{ $uid }}-commission-exclude"
                       value="exclude"
                       data-field="commission_included"
                       {{ $defaultIncluded !== 'include' ? 'checked' : '' }}>
                <label class="form-check-label" for="{{ $uid }}-commission-exclude">{{ translate('Exclude_from_commission') }}</label>
            </div>
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input js-spare-commission-include"
                       type="radio"
                       name="{{ $includeName }}"
                       id="{{ $uid }}-commission-include"
                       value="include"
                       data-field="commission_included"
                       {{ $defaultIncluded === 'include' ? 'checked' : '' }}>
                <label class="form-check-label" for="{{ $uid }}-commission-include">{{ translate('Include_in_commission') }}</label>
            </div>
        </div>
    </div>
    <div class="js-spare-commission-value-wrap d-none">
        <div class="mb-3">
            <label class="form-label">{{ translate('Commission_type') }}</label>
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input js-spare-commission-amount-type"
                           type="radio"
                           name="{{ $amountTypeName }}"
                           id="{{ $uid }}-commission-type-percentage"
                           value="percentage"
                           data-field="commission_amount_type"
                           {{ $defaultAmountType !== 'amount' ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $uid }}-commission-type-percentage">{{ translate('Percentage') }}</label>
                </div>
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input js-spare-commission-amount-type"
                           type="radio"
                           name="{{ $amountTypeName }}"
                           id="{{ $uid }}-commission-type-amount"
                           value="amount"
                           data-field="commission_amount_type"
                           {{ $defaultAmountType === 'amount' ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $uid }}-commission-type-amount">{{ translate('Amount') }}</label>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label js-spare-commission-amount-label" for="{{ $uid }}-commission-amount">{{ translate('Commission_value') }}</label>
            <input type="number"
                   class="form-control js-spare-commission-amount"
                   @if($amountName) name="{{ $amountName }}" @endif
                   id="{{ $uid }}-commission-amount"
                   value="{{ $defaultAmount }}"
                   min="0"
                   step="0.01"
                   data-field="commission_amount">
            <p class="mb-0 small text-muted js-spare-commission-amount-hint">{{ translate('Commission_percentage_of_spare_part_total') }}</p>
        </div>
    </div>
</div>
