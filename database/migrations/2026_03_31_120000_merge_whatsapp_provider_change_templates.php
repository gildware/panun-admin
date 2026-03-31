<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;

return new class extends Migration
{
    /**
     * Add provider-reassignment WhatsApp template keys to existing installs without overwriting other template fields.
     */
    public function up(): void
    {
        $row = DB::table('business_settings')
            ->where('key_name', 'whatsapp_booking_templates')
            ->where('settings_type', 'whatsapp')
            ->first();

        if (!$row) {
            return;
        }

        $live = json_decode($row->live_values, true);
        if (!is_array($live)) {
            $live = [];
        }

        $defaults = BookingWhatsAppNotificationService::defaultTemplateBodies();
        $keys = [
            'provider_change_customer',
            'provider_change_previous_provider',
            'provider_change_new_provider',
        ];

        $changed = false;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $live) && isset($defaults[$key])) {
                $live[$key] = $defaults[$key];
                $changed = true;
            }
        }

        if ($changed) {
            DB::table('business_settings')
                ->where('id', $row->id)
                ->update([
                    'live_values' => json_encode($live),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank: do not strip keys admins may have edited.
    }
};
