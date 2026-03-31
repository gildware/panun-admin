{{-- Expects $previewCurrencySymbol, $previewCurrencyCode, $formSelector e.g. #category-form --}}
@include('businesssettingsmodule::admin.partials.commission-tier-setup-scripts', [
    'previewCurrencySymbol' => $previewCurrencySymbol,
    'previewCurrencyCode' => $previewCurrencyCode,
    'commissionTierBindBusinessCheckbox' => false,
])
<script>
    $(function () {
        var formSel = @json($formSelector);

        function syncEntityCommissionVisibility() {
            var custom = $('input[name="commission_entity_mode"]:checked').val() === 'custom';
            $('#entity-custom-commission-wrap').toggleClass('d-none', !custom);
        }

        $('input[name="commission_entity_mode"]').on('change click', syncEntityCommissionVisibility);
        syncEntityCommissionVisibility();

        $(document).on('submit', formSel, function () {
            var custom = $('input[name="commission_entity_mode"]:checked').val() === 'custom';
            $('#commission-tier-settings').find('input, select').prop('disabled', !custom);
        });
    });
</script>
