<?php

namespace Modules\BusinessSettingsModule\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileAppAiSetting extends Model
{
    protected $table = 'mobile_app_ai_settings';

    protected $fillable = [
        'is_enabled',
        'inherit_whatsapp_ai',
        'use_full_custom_prompt',
        'custom_system_prompt',
        'assistant_persona',
        'prompt_addendum',
        'gemini_model',
        'max_history_messages',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'inherit_whatsapp_ai' => 'boolean',
        'use_full_custom_prompt' => 'boolean',
        'max_history_messages' => 'integer',
    ];

    public static function singleton(): self
    {
        $row = static::query()->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'is_enabled' => true,
            'inherit_whatsapp_ai' => false,
            'use_full_custom_prompt' => false,
            'max_history_messages' => 24,
        ]);
    }
}
