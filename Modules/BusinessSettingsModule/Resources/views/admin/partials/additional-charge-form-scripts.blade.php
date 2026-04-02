{{-- Optional $formSelector e.g. #category-form --}}
@php($formSelector = $formSelector ?? '#additional-charge-type-form')
<script>
    $(function () {
        var formSel = @json($formSelector);

        function syncAcModeBlock($block) {
            var $checked = $block.find('.js-ac-mode-radio:checked');
            if (!$checked.length) {
                return;
            }
            var fixed = $checked.val() === 'fixed';
            $block.find('.js-ac-fixed-wrap').toggleClass('d-none', !fixed);
            $block.find('.js-ac-tiered-wrap').toggleClass('d-none', fixed);
        }

        $(document).on('change', '.js-ac-mode-radio', function () {
            syncAcModeBlock($(this).closest('.js-ac-block'));
        });

        $(function () {
            $('.js-ac-block').each(function () {
                syncAcModeBlock($(this));
            });
        });

        function reindexAcTiers($tbody) {
            var suf = $tbody.data('field-suffix') || '';
            $tbody.find('.js-ac-tier-row').each(function (idx) {
                $(this).find('.js-tier-from-input').attr('name', 'ac_tiers' + suf + '[' + idx + '][from]');
                $(this).find('.js-tier-to-input').attr('name', 'ac_tiers' + suf + '[' + idx + '][to]');
                $(this).find('select').attr('name', 'ac_tiers' + suf + '[' + idx + '][amount_type]');
                $(this).find('.js-tier-amount-input').attr('name', 'ac_tiers' + suf + '[' + idx + '][amount]');
            });
            var cnt = $tbody.find('.js-ac-tier-row').length;
            $tbody.find('.js-ac-remove-tier').prop('disabled', cnt < 2);
        }

        $(document).on('click', '.js-ac-add-tier', function () {
            var suf = $(this).data('field-suffix') || '';
            var $tbody = $(this).closest('.js-ac-tiered-wrap').find('.js-ac-tiers-tbody');
            var idx = $tbody.find('.js-ac-tier-row').length;
            var row = '<tr class="js-ac-tier-row">' +
                '<td><input type="number" class="form-control form-control-sm js-tier-from-input" name="ac_tiers' + suf + '[' + idx + '][from]" min="0" step="any" value="0"></td>' +
                '<td><input type="number" class="form-control form-control-sm js-tier-to-input" name="ac_tiers' + suf + '[' + idx + '][to]" min="0" step="any" value="" disabled></td>' +
                '<td><label class="form-check mb-0"><input type="checkbox" class="form-check-input js-tier-to-infinite" value="1" checked><span class="form-check-label fz-12">{{ translate('Infinite') }}</span></label></td>' +
                '<td><select class="form-select form-select-sm" name="ac_tiers' + suf + '[' + idx + '][amount_type]"><option value="percentage">{{ translate('Percentage') }}</option><option value="fixed">{{ translate('Fixed_amount') }}</option></select></td>' +
                '<td><input type="number" class="form-control form-control-sm js-tier-amount-input" name="ac_tiers' + suf + '[' + idx + '][amount]" min="0" step="any" value="0"></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger js-ac-remove-tier">&times;</button></td></tr>';
            $tbody.append(row);
            reindexAcTiers($tbody);
        });

        $(document).on('click', '.js-ac-remove-tier', function () {
            var $tbody = $(this).closest('.js-ac-tiers-tbody');
            if ($tbody.find('.js-ac-tier-row').length < 2) return;
            $(this).closest('.js-ac-tier-row').remove();
            reindexAcTiers($tbody);
        });

        $(document).on('change', '.js-tier-to-infinite', function () {
            var $tr = $(this).closest('tr');
            var on = $(this).prop('checked');
            $tr.find('.js-tier-to-input').prop('disabled', on);
            if (on) $tr.find('.js-tier-to-input').val('');
        });

        $(document).on('submit', formSel, function () {
            var $form = $(this);
            $form.find('.ac-entity-type-block').each(function () {
                var on = $(this).find('.ac-custom-check').prop('checked');
                $(this).find('.ac-custom-fields input, .ac-custom-fields select').prop('disabled', !on);
            });
            $form.find('.js-ac-block').each(function () {
                var $b = $(this);
                var $entity = $b.closest('.ac-entity-type-block');
                if ($entity.length && !$entity.find('.ac-custom-check').prop('checked')) {
                    return;
                }
                var fixed = $b.find('.js-ac-mode-radio:checked').val() === 'fixed';
                $b.find('.js-ac-tiered-wrap').find('input, select').prop('disabled', fixed);
                $b.find('.js-ac-fixed-wrap').find('input').prop('disabled', !fixed);
            });
        });

        $('.ac-entity-type-block .ac-custom-check').each(function () {
            var on = $(this).prop('checked');
            $(this).closest('.ac-entity-type-block').find('.ac-custom-fields input, .ac-custom-fields select').prop('disabled', !on);
        });

        $(document).on('change', '.ac-custom-check', function () {
            var on = $(this).prop('checked');
            var $block = $(this).closest('.ac-entity-type-block');
            $block.find('.ac-custom-fields').toggleClass('d-none', !on);
            $block.find('.ac-custom-fields input, .ac-custom-fields select').prop('disabled', !on);
            if (on) {
                $block.find('.js-ac-block').each(function () {
                    syncAcModeBlock($(this));
                });
            }
        });
    });
</script>
