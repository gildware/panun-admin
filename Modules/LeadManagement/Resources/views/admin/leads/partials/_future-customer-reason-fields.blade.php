@php
    $selectId = $selectId ?? 'future-customer-reason';
    $optionSelectId = $optionSelectId ?? $selectId . '-option';
    $optionWrapId = $optionWrapId ?? $selectId . '-option-wrap';
    $sourceId = $selectId . '-followups';
    $selectClass = $selectClass ?? 'form-select';
    $marginClass = $marginClass ?? 'mb-3';
    $reasonField = $reasonField ?? 'future_customer_reason_id';
    $optionField = $optionField ?? 'future_customer_reason_option_id';
    $reasonLabel = $reasonLabel ?? translate('Reason');
    $reasons = $reasons ?? ($futureCustomerReasons ?? collect());
    $followups = $reasons->mapWithKeys(function ($reason) {
        $options = $reason->options ?? collect();
        return [
            (string) $reason->id => [
                'label' => $reason->followup_label ?: translate('Details'),
                'options' => $options->map(fn ($option) => ['id' => $option->id, 'name' => $option->name])->values(),
            ],
        ];
    });
    $selectedReason = (string) old($reasonField, $selectedReasonId ?? '');
    $selectedOption = (string) old($optionField, $selectedOptionId ?? '');
@endphp

<div class="{{ $marginClass }}">
    <label class="form-label" for="{{ $selectId }}">{{ $reasonLabel }}</label>
    <select name="{{ $reasonField }}"
            id="{{ $selectId }}"
            class="{{ $selectClass }} js-future-customer-reason"
            required
            data-followup-source="{{ $sourceId }}"
            data-option-wrap="{{ $optionWrapId }}"
            data-option-select="{{ $optionSelectId }}">
        <option value="">{{ translate('Select_Reason') }}</option>
        @foreach($reasons as $reason)
            <option value="{{ $reason->id }}" {{ $selectedReason === (string) $reason->id ? 'selected' : '' }}>
                {{ $reason->name }}
            </option>
        @endforeach
    </select>
    @error($reasonField)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
<div class="{{ $marginClass }} d-none" id="{{ $optionWrapId }}">
    <label class="form-label" for="{{ $optionSelectId }}" data-followup-label>{{ translate('Details') }}</label>
    <select name="{{ $optionField }}"
            id="{{ $optionSelectId }}"
            class="form-select js-reason-followup-option"
            data-placeholder="{{ translate('Select') }}"
            data-selected="{{ $selectedOption }}"
            disabled>
        <option value="">{{ translate('Select') }}</option>
    </select>
    @error($optionField)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
<script type="application/json" id="{{ $sourceId }}">@json($followups)</script>

@once
    @push('script')
        <script>
            window.applyFutureCustomerReasonFollowup = function (select) {
                if (!select) return;
                const source = document.getElementById(select.getAttribute('data-followup-source'));
                const wrap = document.getElementById(select.getAttribute('data-option-wrap'));
                const optionSelect = document.getElementById(select.getAttribute('data-option-select'));
                if (!source || !wrap || !optionSelect) return;

                let map = {};
                try {
                    map = JSON.parse(source.textContent || '{}');
                } catch (e) {
                    map = {};
                }

                const entry = map[String(select.value || '')] || null;
                const options = entry && Array.isArray(entry.options) ? entry.options : [];
                const previous = optionSelect.value || optionSelect.getAttribute('data-selected') || '';
                optionSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = optionSelect.getAttribute('data-placeholder') || 'Select';
                optionSelect.appendChild(placeholder);
                options.forEach(function (opt) {
                    const el = document.createElement('option');
                    el.value = String(opt.id);
                    el.textContent = opt.name;
                    if (String(opt.id) === String(previous)) {
                        el.selected = true;
                    }
                    optionSelect.appendChild(el);
                });

                const label = wrap.querySelector('[data-followup-label]');
                if (label) {
                    label.textContent = entry && entry.label ? entry.label : @json(translate('Details'));
                }

                const show = options.length > 0;
                wrap.classList.toggle('d-none', !show);
                optionSelect.required = show;
                optionSelect.disabled = !show;
                if (!show) {
                    optionSelect.value = '';
                }
            };

            $(document).on('change select2:select select2:clear', '.js-future-customer-reason', function () {
                const next = String(this.value || '');
                const prev = this.getAttribute('data-current-reason');
                if (prev !== null && next !== prev) {
                    const optionSelect = document.getElementById(this.getAttribute('data-option-select'));
                    if (optionSelect) {
                        optionSelect.removeAttribute('data-selected');
                        optionSelect.value = '';
                    }
                }
                this.setAttribute('data-current-reason', next);
                window.applyFutureCustomerReasonFollowup(this);
            });

            $(function () {
                document.querySelectorAll('.js-future-customer-reason').forEach(function (select) {
                    window.applyFutureCustomerReasonFollowup(select);
                });
            });
        </script>
    @endpush
@endonce
