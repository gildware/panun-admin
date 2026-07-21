<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** Canonical name for leads created by the WhatsApp / AI assistant. */
    public const NAME_AI_CHAT = 'AI Chat';

    /** Canonical name for leads created from the marketing website booking form. */
    public const NAME_WEBSITE_DIRECT_BOOKING = 'Website Direct Booking';

    /** Canonical name for leads created from the marketing website partner application form. */
    public const NAME_WEBSITE_PARTNER_APPLICATION = 'Website Partner Application';

    /** Canonical name for leads created from the mobile app custom request form. */
    public const NAME_APP_CUSTOM_REQUEST = 'App Custom Request';

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

    /**
     * Return the lead source used for website booking form submissions; creates it if missing.
     */
    public static function ensureWebsiteDirectBookingSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_WEBSITE_DIRECT_BOOKING)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_WEBSITE_DIRECT_BOOKING,
            'description' => 'Leads created from the marketing website booking form.',
            'is_active' => true,
        ]);
    }

    /**
     * Return the lead source used for website partner application form submissions; creates it if missing.
     */
    public static function ensureWebsitePartnerApplicationSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_WEBSITE_PARTNER_APPLICATION)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_WEBSITE_PARTNER_APPLICATION,
            'description' => 'Leads created from the marketing website become-a-partner form.',
            'is_active' => true,
        ]);
    }

    /**
     * Return the lead source used for mobile app custom request submissions; creates it if missing.
     */
    public static function ensureAppCustomRequestSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_APP_CUSTOM_REQUEST)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_APP_CUSTOM_REQUEST,
            'description' => 'Leads created from the mobile app custom request form.',
            'is_active' => true,
        ]);
    }
}
