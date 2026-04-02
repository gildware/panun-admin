<?php

namespace App\Lib;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\BusinessSettingsModule\Entities\AdditionalChargeType;

class AdditionalChargeEntityOverrides
{
    /**
     * @return list<array{type: AdditionalChargeType, use_custom: bool, tier: array}>
     */
    public static function rowsForEntity(?array $storedOverrides, bool $onlyActiveTypes = false): array
    {
        $q = AdditionalChargeType::query()->ordered();
        if ($onlyActiveTypes) {
            $q->active();
        }
        $types = $q->get();
        $storedOverrides = is_array($storedOverrides) ? $storedOverrides : [];
        $rows = [];

        foreach ($types as $type) {
            $tid = (string) $type->id;
            $useCustom = array_key_exists($tid, $storedOverrides) && is_array($storedOverrides[$tid]);
            $source = $useCustom ? $storedOverrides[$tid] : $type->charge_setup;
            $rows[] = [
                'type' => $type,
                'use_custom' => $useCustom,
                'tier' => normalize_commission_tier_group_for_ui(is_array($source) ? $source : null, 0.0),
            ];
        }

        return $rows;
    }

    /**
     * @throws ValidationException
     */
    public static function applyFromRequestToModel(Request $request, object $model): void
    {
        $types = AdditionalChargeType::query()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $acCustom = $request->input('ac_custom', []);
        $acMode = $request->input('ac_mode', []);
        $acFixed = $request->input('ac_fixed', []);
        $acTiers = $request->input('ac_tiers', []);
        $out = [];

        foreach ($types as $tid) {
            $enabled = ! empty($acCustom[$tid]);
            if (! $enabled) {
                continue;
            }

            $mode = $acMode[$tid] ?? null;
            if (! in_array($mode, ['fixed', 'tiered'], true)) {
                throw ValidationException::withMessages([
                    "ac_mode.{$tid}" => [translate('validation.in', ['attribute' => 'mode'])],
                ]);
            }

            $fake = Request::create('/', 'POST', [
                '_ac_mode' => $mode,
                '_ac_fixed' => $acFixed[$tid] ?? 0,
                '_ac_tiers' => is_array($acTiers[$tid] ?? null) ? $acTiers[$tid] : [],
            ]);

            $inner = Validator::make(['_ok' => true], ['_ok' => 'accepted']);
            $inner->after(function (\Illuminate\Validation\Validator $v) use ($fake) {
                CommissionTierPayload::validateGroups($v, $fake, [
                    ['_ac_mode', '_ac_fixed', '_ac_tiers'],
                ]);
            });
            $inner->validate();

            $group = CommissionTierPayload::normalizeGroupFromRequest(
                $fake,
                '_ac_mode',
                '_ac_fixed',
                '_ac_tiers'
            );
            $out[$tid] = $group;
        }

        $model->additional_charge_overrides = count($out) > 0 ? $out : null;
    }
}
