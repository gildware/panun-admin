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
    $allowCustomOption = ! empty($allowCustomOption);
    $optionPlaceholder = $allowCustomOption ? translate('Select_or_type_to_add') : translate('Select');
    $reasons = $reasons ?? ($futureCustomerReasons ?? collect());
    $followups = $reasons->mapWithKeys(function ($reason) {
        $options = $reason->options ?? collect();
        return [
            (string) $reason->id => [
                'label' => $reason->followup_label ?: translate('Details'),
                'asks' => filled($reason->followup_label),
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
            data-placeholder="{{ $optionPlaceholder }}"
            data-allow-custom="{{ $allowCustomOption ? '1' : '0' }}"
            data-selected="{{ $selectedOption }}"
            disabled>
        <option value="">{{ $optionPlaceholder }}</option>
    </select>
    @if($allowCustomOption)
        <div class="text-muted small mt-1">{{ translate('Future_customer_followup_custom_help') }}</div>
    @endif
    @error($optionField)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
<script type="application/json" id="{{ $sourceId }}">@json($followups)</script>

@once
    @push('script')
        <script>
            function destroyFollowupSelect(optionSelect) {
                if (!window.jQuery || !window.jQuery.fn.select2) return;
                const $el = window.jQuery(optionSelect);
                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
            }

            function initFollowupSelect(optionSelect) {
                if (optionSelect.getAttribute('data-allow-custom') !== '1') return;
                if (!window.jQuery || !window.jQuery.fn.select2) return;
                const $el = window.jQuery(optionSelect);
                const opts = {
                    width: '100%',
                    tags: true,
                    placeholder: optionSelect.getAttribute('data-placeholder') || 'Select or type to add',
                    allowClear: true,
                    createTag: function (params) {
                        const term = (params.term || '').trim();
                        if (!term || term.length > 255) return null;
                        return { id: term, text: term, newTag: true };
                    }
                };
                const modal = optionSelect.closest('.modal');
                if (modal) {
                    opts.dropdownParent = window.jQuery(modal);
                }
                $el.select2(opts);
            }

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
                const allowCustom = optionSelect.getAttribute('data-allow-custom') === '1';
                const previous = optionSelect.value || optionSelect.getAttribute('data-selected') || '';
                destroyFollowupSelect(optionSelect);
                optionSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = optionSelect.getAttribute('data-placeholder') || 'Select';
                optionSelect.appendChild(placeholder);
                let matched = false;
                options.forEach(function (opt) {
                    const el = document.createElement('option');
                    el.value = String(opt.id);
                    el.textContent = opt.name;
                    if (String(opt.id) === String(previous)) {
                        el.selected = true;
                        matched = true;
                    }
                    optionSelect.appendChild(el);
                });
                if (!matched && previous && allowCustom) {
                    const custom = document.createElement('option');
                    custom.value = previous;
                    custom.textContent = previous;
                    custom.selected = true;
                    optionSelect.appendChild(custom);
                }

                const label = wrap.querySelector('[data-followup-label]');
                if (label) {
                    label.textContent = entry && entry.label ? entry.label : @json(translate('Details'));
                }

                const show = options.length > 0 || (allowCustom && !!(entry && entry.asks));
                wrap.classList.toggle('d-none', !show);
                optionSelect.required = show;
                optionSelect.disabled = !show;
                if (!show) {
                    optionSelect.value = '';
                } else {
                    initFollowupSelect(optionSelect);
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

            $(document).on('shown.bs.modal', '.modal', function () {
                this.querySelectorAll('.js-future-customer-reason').forEach(function (select) {
                    window.applyFutureCustomerReasonFollowup(select);
                });
            });

            $(function () {
                document.querySelectorAll('.js-future-customer-reason').forEach(function (select) {
                    window.applyFutureCustomerReasonFollowup(select);
                });
            });
        </script>
    @endpush
@endonce
