<?php

namespace App\Lib;

use Modules\BusinessSettingsModule\Entities\BusinessSettings;

/**
 * Who bears a line discount for settlement: affects discount_by_admin / discount_by_provider.
 * Line-level NONE: neither admin nor provider bears the line discount for settlement splits; commission uses payable totals.
 * Business promotional_setup "none" for discount/campaign/coupon:
 * no admin/provider promo split from those amounts (commission tier still follows payable total unless line bearer is none).
 */
final class DiscountCostBearer
{
    public const BOTH = 'both';

    public const ADMIN = 'admin';

    public const PROVIDER = 'provider';

    public const NONE = 'none';

    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return self::NONE;
        }
        $v = strtolower(trim($value));
        if ($v === '') {
            return self::NONE;
        }
        if (in_array($v, [self::ADMIN, self::PROVIDER, self::NONE, self::BOTH], true)) {
            return $v;
        }

        return self::NONE;
    }

    /**
     * @return array{admin: float, provider: float}
     */
    public static function splitLineDiscount(float $discount, string $bearer): array
    {
        $discount = max(0.0, round($discount, 2));
        if ($discount <= 0) {
            return ['admin' => 0.0, 'provider' => 0.0];
        }

        switch (self::normalize($bearer)) {
            case self::ADMIN:
                return ['admin' => $discount, 'provider' => 0.0];
            case self::PROVIDER:
                return ['admin' => 0.0, 'provider' => $discount];
            case self::NONE:
                return ['admin' => 0.0, 'provider' => 0.0];
            default:
                return self::splitFromBusinessDiscountSettings($discount);
        }
    }

    /**
     * Split basic + campaign discounts using the same bearer (proportional admin/provider totals).
     *
     * @return array{discount_by_admin: float, discount_by_provider: float, campaign_discount_by_admin: float, campaign_discount_by_provider: float}
     */
    public static function splitBasicAndCampaign(float $basicDiscount, float $campaignDiscount, string $bearer): array
    {
        $basicDiscount = max(0.0, round($basicDiscount, 2));
        $campaignDiscount = max(0.0, round($campaignDiscount, 2));
        $total = round($basicDiscount + $campaignDiscount, 2);
        if ($total <= 0) {
            return [
                'discount_by_admin' => 0.0,
                'discount_by_provider' => 0.0,
                'campaign_discount_by_admin' => 0.0,
                'campaign_discount_by_provider' => 0.0,
            ];
        }

        $bearer = self::normalize($bearer);
        if ($bearer === self::NONE) {
            return [
                'discount_by_admin' => 0.0,
                'discount_by_provider' => 0.0,
                'campaign_discount_by_admin' => 0.0,
                'campaign_discount_by_provider' => 0.0,
            ];
        }

        if ($bearer === self::ADMIN) {
            return [
                'discount_by_admin' => $basicDiscount,
                'discount_by_provider' => 0.0,
                'campaign_discount_by_admin' => $campaignDiscount,
                'campaign_discount_by_provider' => 0.0,
            ];
        }

        if ($bearer === self::PROVIDER) {
            return [
                'discount_by_admin' => 0.0,
                'discount_by_provider' => $basicDiscount,
                'campaign_discount_by_admin' => 0.0,
                'campaign_discount_by_provider' => $campaignDiscount,
            ];
        }

        // BOTH: legacy — use business settings per type
        $basicSplit = self::splitFromBusinessDiscountSettings($basicDiscount);
        $campaignSplit = self::splitFromBusinessCampaignSettings($campaignDiscount);

        return [
            'discount_by_admin' => $basicSplit['admin'],
            'discount_by_provider' => $basicSplit['provider'],
            'campaign_discount_by_admin' => $campaignSplit['admin'],
            'campaign_discount_by_provider' => $campaignSplit['provider'],
        ];
    }

    /**
     * Split an amount using Business → Promotional setup cost bearer (discount_cost_bearer, campaign_cost_bearer, coupon_cost_bearer).
     *
     * @return array{admin: float, provider: float}
     */
    public static function splitFromBusinessSettingKey(float $discount, string $keyName): array
    {
        $discount = max(0.0, round($discount, 2));
        if ($discount <= 0) {
            return ['admin' => 0.0, 'provider' => 0.0];
        }

        $data = BusinessSettings::where('settings_type', 'promotional_setup')->where('key_name', $keyName)->first();
        if (! $data) {
            return ['admin' => 0.0, 'provider' => 0.0];
        }

        $live = $data->live_values ?? [];
        if (! is_array($live)) {
            $live = (array) $live;
        }

        $bearer = strtolower((string) ($live['bearer'] ?? 'both'));
        if ($bearer === 'none') {
            return ['admin' => 0.0, 'provider' => 0.0];
        }
        if ($bearer === 'admin') {
            return ['admin' => $discount, 'provider' => 0.0];
        }
        if ($bearer === 'provider') {
            return ['admin' => 0.0, 'provider' => $discount];
        }

        $ap = (float) ($live['admin_percentage'] ?? 0);
        $pp = (float) ($live['provider_percentage'] ?? 0);
        $adminPart = $ap == 0.0 ? 0.0 : ($discount * $ap) / 100;
        $providerPart = $pp == 0.0 ? 0.0 : ($discount * $pp) / 100;

        return ['admin' => round($adminPart, 2), 'provider' => round($providerPart, 2)];
    }

    /**
     * @return array{admin: float, provider: float}
     */
    private static function splitFromBusinessDiscountSettings(float $discount): array
    {
        return self::splitFromBusinessSettingKey($discount, 'discount_cost_bearer');
    }

    /**
     * @return array{admin: float, provider: float}
     */
    private static function splitFromBusinessCampaignSettings(float $discount): array
    {
        return self::splitFromBusinessSettingKey($discount, 'campaign_cost_bearer');
    }
}
