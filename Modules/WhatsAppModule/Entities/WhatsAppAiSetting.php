<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

class WhatsAppAiSetting extends Model
{
    use HasSocialInboxChannelScope;

    protected $table = 'whatsapp_ai_settings';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'channel',
        'use_full_custom_prompt',
        'assistant_persona',
        'custom_system_prompt',
        'allowed_policy',
        'forbidden_policy',
        'prompt_addendum',
        'tools_config',
        'handoff_message_in_hours',
        'db_handoff_in_buttons_json',
        'handoff_message_out_hours',
        'db_non_text_inbound_message',
        'db_non_text_buttons_json',
        'db_handoff_out_buttons_json',
        'booking_provider_escalation_message',
        'db_booking_escalation_buttons_json',
        'placeholder_schedule',
        'placeholder_phone',
        'placeholder_brand',
        'placeholder_email',
        'placeholder_website',
        'placeholder_address',
        'placeholder_tagline',
        'placeholder_provider_onboarding',
        'db_ai_support_enabled',
        'db_gemini_model',
        'db_greeting_buttons',
        'db_greeting_message',
        'db_greeting_buttons_json',
        'db_support_hours_start',
        'db_support_hours_end',
        'db_support_days',
        'db_support_timezone',
        'db_support_phone_display',
        'db_ai_dispatch_sync',
        'db_queue_connection',
    ];

    protected $casts = [
        'use_full_custom_prompt' => 'boolean',
        'tools_config' => 'array',
        'db_support_days' => 'array',
        'db_greeting_buttons_json' => 'array',
        'db_handoff_in_buttons_json' => 'array',
        'db_non_text_buttons_json' => 'array',
        'db_handoff_out_buttons_json' => 'array',
        'db_booking_escalation_buttons_json' => 'array',
    ];

    public static function singleton(): self
    {
        $ch = SocialInboxChannel::current();
        $row = static::query()->where('channel', $ch)->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'channel' => $ch,
            'use_full_custom_prompt' => false,
        ]);
    }
}
