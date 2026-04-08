<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** Canonical name for leads created by the WhatsApp / AI assistant. */
    public const NAME_AI_CHAT = 'AI Chat';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Return the lead source used for AI-created leads; creates it in configuration if missing.
     */
    public static function ensureAiChatSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_AI_CHAT)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_AI_CHAT,
            'description' => null,
            'is_active' => true,
        ]);
    }
}
