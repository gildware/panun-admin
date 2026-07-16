<?php

use Illuminate\Database\Migrations\Migration;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

return new class extends Migration
{
    public function up(): void
    {
        BusinessSettings::firstOrCreate(
            [
                'key_name' => 'booking_confirmation_amount_per_service',
                'settings_type' => 'booking_setup',
            ],
            [
                'key_name' => 'booking_confirmation_amount_per_service',
                'live_values' => 100,
                'test_values' => 100,
                'settings_type' => 'booking_setup',
                'mode' => 'live',
                'is_active' => 1,
            ]
        );
    }

    public function down(): void
    {
        BusinessSettings::where('key_name', 'booking_confirmation_amount_per_service')
            ->where('settings_type', 'booking_setup')
            ->delete();
    }
};
