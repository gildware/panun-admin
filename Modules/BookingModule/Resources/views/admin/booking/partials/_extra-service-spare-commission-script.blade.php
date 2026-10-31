{{-- Expects $rootSelector (form or modal element CSS selector). --}}
<script>
(function () {
    var root = document.querySelector(@json($rootSelector));
    if (!root) {
        return;
    }

    function typeSelect() {
        return root.querySelector('select[name="type"], select.js-extra-type, #create-extra-type, #visit-extra-type');
    }

    function commissionRoot() {
        return root.querySelector('[data-spare-commission-root]');
    }

    function syncSpareCommissionUi() {
        var typeEl = typeSelect();
        var block = commissionRoot();
        if (!block) {
            return;
        }
        var isSpare = typeEl && typeEl.value === 'spare_part';
        block.classList.toggle('d-none', !isSpare);

        var includeChecked = block.querySelector('.js-spare-commission-include[value="include"]');
        var include = !!(includeChecked && includeChecked.checked);
        var valueWrap = block.querySelector('.js-spare-commission-value-wrap');
        if (valueWrap) {
            valueWrap.classList.toggle('d-none', !isSpare || !include);
        }

        var amountTypeEl = block.querySelector('.js-spare-commission-amount-type:checked');
        var isAmount = amountTypeEl && amountTypeEl.value === 'amount';
        var hint = block.querySelector('.js-spare-commission-amount-hint');
        var label = block.querySelector('.js-spare-commission-amount-label');
        var amountInput = block.querySelector('.js-spare-commission-amount');
        if (hint) {
            hint.textContent = isAmount
                ? @json(translate('Commission_fixed_amount_on_spare_part'))
                : @json(translate('Commission_percentage_of_spare_part_total'));
        }
        if (label) {
            label.textContent = isAmount
                ? @json(translate('Commission_amount'))
                : @json(translate('Commission_percentage'));
        }
        if (amountInput) {
            amountInput.max = isAmount ? '' : '100';
        }
    }

    function setRadio(fieldKey, value) {
        var radios = root.querySelectorAll('.js-spare-commission-include, .js-spare-commission-amount-type');
        radios.forEach(function (r) {
            if (r.getAttribute('data-field') === fieldKey && r.value === value) {
                r.checked = true;
            }
        });
    }

    window.__spareCommissionUi = window.__spareCommissionUi || {};
    window.__spareCommissionUi[@json($rootSelector)] = {
        sync: syncSpareCommissionUi,
        reset: function () {
            setRadio('commission_included', 'exclude');
            setRadio('commission_amount_type', 'percentage');
            var amount = root.querySelector('.js-spare-commission-amount');
            if (amount) {
                amount.value = '0';
            }
            syncSpareCommissionUi();
        },
        fill: function (opts) {
            opts = opts || {};
            setRadio('commission_included', opts.commission_included === 'include' ? 'include' : 'exclude');
            setRadio('commission_amount_type', opts.commission_amount_type === 'amount' ? 'amount' : 'percentage');
            var amount = root.querySelector('.js-spare-commission-amount');
            if (amount) {
                amount.value = opts.commission_amount != null ? String(opts.commission_amount) : '0';
            }
            syncSpareCommissionUi();
        },
        read: function () {
            var includeEl = root.querySelector('.js-spare-commission-include:checked');
            var typeEl = root.querySelector('.js-spare-commission-amount-type:checked');
            var amountEl = root.querySelector('.js-spare-commission-amount');
            return {
                commission_included: includeEl && includeEl.value === 'include' ? 'include' : 'exclude',
                commission_amount_type: typeEl && typeEl.value === 'amount' ? 'amount' : 'percentage',
                commission_amount: amountEl ? (parseFloat(amountEl.value) || 0) : 0
            };
        }
    };

    root.addEventListener('change', function (e) {
        if (!e.target) {
            return;
        }
        if (
            e.target.matches('select[name="type"], select.js-extra-type, #create-extra-type, #visit-extra-type')
            || e.target.classList.contains('js-spare-commission-include')
            || e.target.classList.contains('js-spare-commission-amount-type')
        ) {
            syncSpareCommissionUi();
        }
    });

    syncSpareCommissionUi();
})();
</script>
