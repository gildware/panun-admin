<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;

return new class extends Migration
{
    public function up(): void
    {
        $rows = BusinessSettings::query()
            ->where('key_name', 'referral_share_message_template')
            ->where('settings_type', 'customer_config')
            ->get();

        foreach ($rows as $row) {
            $raw = $row->getRawOriginal('live_values');
            if (!is_string($raw) || $raw === '') {
                continue;
            }

            if ($row->live_values !== null) {
                continue;
            }

            $row->live_values = $raw;
            $row->test_values = $row->getRawOriginal('test_values') ?: $raw;
            $row->save();
        }

        $keepId = DB::table('business_settings')
            ->where('key_name', 'referral_share_message_template')
            ->where('settings_type', 'customer_config')
            ->orderByDesc('updated_at')
            ->value('id');

        $rowsToKeep = $keepId ? collect([$keepId]) : collect();

        if ($rowsToKeep->isNotEmpty()) {
            DB::table('business_settings')
                ->where('key_name', 'referral_share_message_template')
                ->where('settings_type', 'customer_config')
                ->whereNotIn('id', $rowsToKeep)
                ->delete();
        }
    }

    public function down(): void
    {
        // Data normalization is not reversed.
    }
};
