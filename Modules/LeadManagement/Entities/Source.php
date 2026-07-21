<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** Canonical name for leads created by the WhatsApp / AI assistant. */
    public const NAME_AI_CHAT = 'AI Chat';

    /** Canonical name for Click-to-WhatsApp (Facebook/Instagram ads) inbound chats. */
    public const NAME_FACEBOOK_WHATSAPP_AD = 'Facebook WhatsApp Ad';

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
     * System-created lead sources (not manually entered by an employee in admin).
     *
     * @return list<string>
     */
    public static function automatedSourceNames(): array
    {
        return [
            self::NAME_AI_CHAT,
            self::NAME_FACEBOOK_WHATSAPP_AD,
            self::NAME_WEBSITE_DIRECT_BOOKING,
            self::NAME_WEBSITE_PARTNER_APPLICATION,
            self::NAME_APP_CUSTOM_REQUEST,
        ];
    }

    /**
     * @return list<int>
     */
    public static function automatedSourceIds(): array
    {
        $names = self::automatedSourceNames();
        if ($names === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $bindings = array_map('strtolower', $names);

        return static::query()
            ->whereRaw('LOWER(TRIM(name)) IN ('.$placeholders.')', $bindings)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
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
     * Return the lead source for Click-to-WhatsApp Facebook/Instagram ad traffic; creates if missing.
     */
    public static function ensureFacebookWhatsAppAdSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_FACEBOOK_WHATSAPP_AD)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_FACEBOOK_WHATSAPP_AD,
            'description' => 'Leads from Click-to-WhatsApp Facebook/Instagram ads (CTWA referral).',
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
