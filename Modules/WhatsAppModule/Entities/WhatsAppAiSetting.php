<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAiSetting extends Model
{
    protected $table = 'whatsapp_ai_settings';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'use_full_custom_prompt',
        'assistant_persona',
        'custom_system_prompt',
        'allowed_policy',
        'forbidden_policy',
        'prompt_addendum',
        'flow_mermaid',
        'tools_config',
        'handoff_message_in_hours',
        'handoff_message_out_hours',
        'booking_provider_escalation_message',
        'placeholder_schedule',
        'placeholder_phone',
        'placeholder_brand',
        'placeholder_email',
        'placeholder_website',
        'placeholder_address',
        'placeholder_tagline',
        'placeholder_custom_1',
        'placeholder_custom_2',
        'db_ai_support_enabled',
        'db_gemini_model',
        'db_greeting_buttons',
        'db_support_hours_start',
        'db_support_hours_end',
        'db_support_timezone',
        'db_support_phone_display',
        'db_ai_dispatch_sync',
        'db_queue_connection',
    ];

    protected $casts = [
        'use_full_custom_prompt' => 'boolean',
        'tools_config' => 'array',
    ];

    public static function singleton(): self
    {
        $row = static::query()->find(1);
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'id' => 1,
            'use_full_custom_prompt' => false,
        ]);
    }
}
