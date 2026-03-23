<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\WhatsAppModule\Services\BookingWhatsAppNotificationService;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('business_settings')
            ->where('key_name', 'whatsapp_booking_templates')
            ->where('settings_type', 'whatsapp')
            ->exists();

        if ($exists) {
            return;
        }

        $defaults = array_merge([
            'enabled' => false,
            'default_phone_prefix' => '',
        ], BookingWhatsAppNotificationService::defaultTemplateBodies());

        DB::table('business_settings')->insert([
            'id' => (string) Str::uuid(),
            'key_name' => 'whatsapp_booking_templates',
            'live_values' => json_encode($defaults),
            'test_values' => json_encode([]),
            'settings_type' => 'whatsapp',
            'mode' => 'live',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('business_settings')
            ->where('key_name', 'whatsapp_booking_templates')
            ->where('settings_type', 'whatsapp')
            ->delete();
    }
};
