@php
    $mode = $mode ?? 'category';
    $taxModel = $taxModel ?? null;
    $percentageName = $mode === 'service' ? 'tax' : 'tax_percentage';
    $idPrefix = $mode === 'service' ? 'service' : 'category';
    $titleKey = $mode === 'service' ? 'service_custom_tax' : 'category_custom_tax';
    $hintKey = $mode === 'service' ? 'service_custom_tax_hint' : 'category_custom_tax_hint';

    $hasOverride = old('tax_override') !== null
        ? (string) old('tax_override') === '1'
        : ($taxModel && (
            $mode === 'service'
                ? ($taxModel->getAttribute('tax') !== null && $taxModel->getAttribute('tax') !== '')
                : ($taxModel->tax_percentage !== null)
        ));

    $percentageDefault = '';
    if ($taxModel) {
        $percentageDefault = $mode === 'service' ? ($taxModel->tax ?? '') : ($taxModel->tax_percentage ?? '');
    }
@endphp
<div class="col-12 mb-30">
    <div class="border rounded p-20">
        <h5 class="c1 mb-2">{{ translate($titleKey) }}</h5>
        <p class="text-muted fz-12 mb-3">{{ translate($hintKey) }}</p>
        <input type="hidden" name="tax_override" value="0">
        <div class="form-check mb-3">
            <input type="checkbox" name="tax_override" value="1" class="form-check-input" id="{{ $idPrefix }}_tax_override_cb"
                {{ $hasOverride ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $idPrefix }}_tax_override_cb">{{ translate('override_company_default_tax') }}</label>
        </div>
        <div id="{{ $idPrefix }}_tax_override_fields" class="{{ $hasOverride ? '' : 'd-none' }}">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating form-floating__icon">
                        <input type="number" step="0.001" min="0" max="100" name="{{ $percentageName }}" id="{{ $idPrefix }}_tax_percentage" class="form-control"
                               value="{{ old($percentageName, $percentageDefault) }}" placeholder="">
                        <label for="{{ $idPrefix }}_tax_percentage">{{ translate('tax_percentage') }} (%)</label>
                        <span class="material-icons">percent</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating__icon">
                        <input type="text" name="tax_label" id="{{ $idPrefix }}_tax_label" class="form-control" maxlength="191"
                               value="{{ old('tax_label', $taxModel?->tax_label ?? '') }}" placeholder="">
                        <label for="{{ $idPrefix }}_tax_label">{{ translate('tax_label_optional') }}</label>
                        <span class="material-icons">label</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('script')
<script>
    (function () {
        var cb = document.getElementById('{{ $idPrefix }}_tax_override_cb');
        var panel = document.getElementById('{{ $idPrefix }}_tax_override_fields');
        if (!cb || !panel) return;
        cb.addEventListener('change', function () {
            panel.classList.toggle('d-none', !this.checked);
        });
    })();
</script>
@endpush
