<?php

namespace App\Lib;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommissionEntitySetup
{
    /**
     * Tier form fields for admin UI (company snapshot when not custom or empty).
     *
     * @return array{tierService: array, tierSpare: array, previewCurrencySymbol: string, previewCurrencyCode: string}
     */
    public static function tierFormContext(?array $stored, bool $useCustom): array
    {
        $companyRow = business_config('commission_tier_setup', 'business_information');
        $companyLive = is_array($companyRow?->live_values) ? $companyRow->live_values : [];
        $defaultCommissionPct = (float) (optional(business_config('default_commission', 'business_information'))->live_values ?? 10);
        $stored = is_array($stored) ? $stored : [];
        $hasOwn = $useCustom && $stored !== [] && (isset($stored['service']) || isset($stored['spare_parts']));
        $source = $hasOwn ? $stored : $companyLive;
        $tierService = normalize_commission_tier_group_for_ui($source['service'] ?? null, $defaultCommissionPct);
        $tierSpare = normalize_commission_tier_group_for_ui($source['spare_parts'] ?? null, 0.0);

        $previewCurrencyCode = optional(business_config('currency_code', 'business_information'))->live_values ?? 'USD';
        $previewCurrencySymbol = '$';
        foreach (CURRENCIES as $_cur) {
            if (($_cur['code'] ?? '') === $previewCurrencyCode) {
                $previewCurrencySymbol = $_cur['symbol'] ?? '$';
                break;
            }
        }

        return compact('tierService', 'tierSpare', 'previewCurrencySymbol', 'previewCurrencyCode');
    }

    /**
     * @throws ValidationException
     */
    public static function applyFromRequestToModel(Request $request, object $model): void
    {
        $mode = $request->input('commission_entity_mode', 'default');
        if ($mode !== 'custom') {
            $model->commission_custom = 0;
            $model->commission_tier_setup = null;

            return;
        }

        $rules = [
            'commission_service_mode' => 'required|in:fixed,tiered',
            'commission_spare_mode' => 'required|in:fixed,tiered',
            'commission_service_fixed_amount' => 'nullable|numeric|min:0',
            'commission_spare_fixed_amount' => 'nullable|numeric|min:0',
            'commission_service_tiers' => 'nullable|array',
            'commission_service_tiers.*.from' => 'nullable|numeric|min:0',
            'commission_service_tiers.*.to' => 'nullable|numeric|min:0',
            'commission_service_tiers.*.amount_type' => 'nullable|in:percentage,fixed',
            'commission_service_tiers.*.amount' => 'nullable|numeric|min:0',
            'commission_spare_tiers' => 'nullable|array',
            'commission_spare_tiers.*.from' => 'nullable|numeric|min:0',
            'commission_spare_tiers.*.to' => 'nullable|numeric|min:0',
            'commission_spare_tiers.*.amount_type' => 'nullable|in:percentage,fixed',
            'commission_spare_tiers.*.amount' => 'nullable|numeric|min:0',
        ];

        Validator::make($request->all(), $rules)
            ->after(fn (\Illuminate\Validation\Validator $v) => CommissionTierPayload::validateGroups($v, $request, CommissionTierPayload::defaultValidationGroups()))
            ->validate();

        $serviceGroup = CommissionTierPayload::normalizeGroupFromRequest(
            $request,
            'commission_service_mode',
            'commission_service_fixed_amount',
            'commission_service_tiers'
        );
        $spareGroup = CommissionTierPayload::normalizeGroupFromRequest(
            $request,
            'commission_spare_mode',
            'commission_spare_fixed_amount',
            'commission_spare_tiers'
        );

        $model->commission_custom = 1;
        $model->commission_tier_setup = [
            'service' => $serviceGroup,
            'spare_parts' => $spareGroup,
        ];
    }
}
