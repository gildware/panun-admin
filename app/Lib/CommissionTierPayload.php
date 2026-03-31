<?php

namespace App\Lib;

use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class CommissionTierPayload
{
    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $groups  [modeKey, fixedKey, tiersKey]
     */
    public static function validateGroups(Validator $validator, Request $request, array $groups): void
    {
        foreach ($groups as [$modeKey, $fixedKey, $tiersKey]) {
            $mode = $request->input($modeKey);
            if ($mode === 'fixed') {
                $fv = $request->input($fixedKey);
                if ($fv === null || $fv === '') {
                    $validator->errors()->add($fixedKey, translate('Fixed_commission_amount_is_required'));
                }

                continue;
            }

            $rows = $request->input($tiersKey, []);
            if (! is_array($rows) || count(array_filter($rows, 'is_array')) < 1) {
                $validator->errors()->add($tiersKey, translate('At_least_one_commission_tier_is_required'));

                continue;
            }

            foreach (array_values($rows) as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $from = $row['from'] ?? '';
                if ($from === '' || ! is_numeric($from)) {
                    $validator->errors()->add("{$tiersKey}.{$i}.from", translate('Tier_range_from_is_required'));
                }
                $infinite = ! empty($row['to_infinite']);
                if (! $infinite) {
                    $to = $row['to'] ?? '';
                    if ($to === '' || ! is_numeric($to)) {
                        $validator->errors()->add("{$tiersKey}.{$i}.to", translate('Enter_upper_bound_or_mark_unlimited'));
                    } elseif (is_numeric($from) && (float) $to < (float) $from) {
                        $validator->errors()->add("{$tiersKey}.{$i}.to", translate('Commission_tier_to_must_be_greater_than_or_equal_from'));
                    }
                }
                $type = $row['amount_type'] ?? '';
                if (! in_array($type, ['percentage', 'fixed'], true)) {
                    $validator->errors()->add("{$tiersKey}.{$i}.amount_type", translate('Select_percentage_or_fixed'));
                }
                $amount = $row['amount'] ?? '';
                if ($amount === '' || ! is_numeric($amount)) {
                    $validator->errors()->add("{$tiersKey}.{$i}.amount", translate('Commission_amount_is_required'));
                } elseif ($type === 'percentage' && (float) $amount > 100) {
                    $validator->errors()->add("{$tiersKey}.{$i}.amount", translate('Percentage_commission_cannot_exceed_100'));
                }
            }
        }
    }

    /**
     * @return array{mode: string, fixed_amount: float, tiers: list<array{from: float, to: float|null, amount_type: string, amount: float}>}
     */
    public static function normalizeGroupFromRequest(Request $request, string $modeKey, string $fixedKey, string $tiersKey): array
    {
        if ($request->input($modeKey) === 'fixed') {
            return [
                'mode' => 'fixed',
                'fixed_amount' => (float) ($request->input($fixedKey, 0) ?? 0),
                'tiers' => [],
            ];
        }

        $raw = $request->input($tiersKey, []);
        $tiers = [];
        foreach (array_values(is_array($raw) ? $raw : []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $infinite = ! empty($row['to_infinite']);
            $from = isset($row['from']) && $row['from'] !== '' ? (float) $row['from'] : 0.0;
            $to = $infinite ? null : (float) ($row['to'] ?? 0);
            $type = $row['amount_type'] ?? 'percentage';
            if (! in_array($type, ['percentage', 'fixed'], true)) {
                $type = 'percentage';
            }
            $tiers[] = [
                'from' => $from,
                'to' => $to,
                'amount_type' => $type,
                'amount' => isset($row['amount']) && $row['amount'] !== '' ? (float) $row['amount'] : 0.0,
            ];
        }

        return [
            'mode' => 'tiered',
            'fixed_amount' => 0.0,
            'tiers' => $tiers,
        ];
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function defaultValidationGroups(): array
    {
        return [
            ['commission_service_mode', 'commission_service_fixed_amount', 'commission_service_tiers'],
            ['commission_spare_mode', 'commission_spare_fixed_amount', 'commission_spare_tiers'],
        ];
    }
}
