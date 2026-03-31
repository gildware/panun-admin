<?php

use Illuminate\Database\Migrations\Migration;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

return new class extends Migration
{
    /**
     * Default tiered commission structure (settings only; booking logic uses this in a later phase).
     */
    public function up(): void
    {
        $live = [
            'service' => [
                'threshold' => 0,
                'fixed_below_threshold' => 0,
                'percentage_above_threshold' => 10,
            ],
            'spare_parts' => [
                'threshold' => 0,
                'fixed_below_threshold' => 0,
                'percentage_above_threshold' => 0,
            ],
        ];

        BusinessSettings::updateOrCreate(
            ['key_name' => 'commission_tier_setup', 'settings_type' => 'business_information'],
            [
                'key_name' => 'commission_tier_setup',
                'live_values' => $live,
                'test_values' => $live,
                'settings_type' => 'business_information',
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    public function down(): void
    {
        BusinessSettings::where('key_name', 'commission_tier_setup')
            ->where('settings_type', 'business_information')
            ->delete();
    }
};
