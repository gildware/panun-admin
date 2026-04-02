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
