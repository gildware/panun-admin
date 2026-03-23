<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;

return new class extends Migration
{
    /**
     * Refresh default template copy (customer: service + provider; provider: customer + service;
     * status: booking id + previous → new status) while preserving enabled flag and phone prefix.
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

        foreach (BookingWhatsAppNotificationService::defaultTemplateBodies() as $key => $body) {
            $live[$key] = $body;
        }

        DB::table('business_settings')
            ->where('id', $row->id)
            ->update([
                'live_values' => json_encode($live),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Non-reversible; admins may have edited templates after this migration.
    }
};
