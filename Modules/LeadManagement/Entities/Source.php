<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** Canonical name for leads created by the WhatsApp / AI assistant. */
    public const NAME_AI_CHAT = 'AI Chat';

    /** Canonical name for Click-to-WhatsApp Facebook placement inbound chats. */
    public const NAME_FACEBOOK_WHATSAPP_AD = 'Facebook WhatsApp Ad';

    /** Canonical name for Click-to-WhatsApp Instagram placement inbound chats. */
    public const NAME_INSTAGRAM_WHATSAPP_AD = 'Instagram WhatsApp Ad';

    /** Canonical name when CTWA platform cannot be determined from referral. */
    public const NAME_WHATSAPP_AD = 'WhatsApp Ad';

    /** Canonical name for leads created from the marketing website booking form. */
    public const NAME_WEBSITE_DIRECT_BOOKING = 'Website Direct Booking';

    /** Canonical name for leads created from the marketing website partner application form. */
    public const NAME_WEBSITE_PARTNER_APPLICATION = 'Website Partner Application';

    /** Canonical name for leads created from the mobile app custom request form. */
    public const NAME_APP_CUSTOM_REQUEST = 'App Custom Request';

    /** Canonical name for bookings placed by the customer in the mobile app. */
    public const NAME_DIRECT_APP_BOOKING = 'Direct App Booking';

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
            self::NAME_INSTAGRAM_WHATSAPP_AD,
            self::NAME_WHATSAPP_AD,
            self::NAME_WEBSITE_DIRECT_BOOKING,
            self::NAME_WEBSITE_PARTNER_APPLICATION,
            self::NAME_APP_CUSTOM_REQUEST,
            self::NAME_DIRECT_APP_BOOKING,
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
        return self::ensureCtwaPlatformSource('facebook');
    }

    /**
     * CTWA lead Source by placement: facebook | instagram | whatsapp (unknown).
     */
    public static function ensureCtwaPlatformSource(string $platform): self
    {
        $platform = strtolower(trim($platform));
        $name = match ($platform) {
            'instagram' => self::NAME_INSTAGRAM_WHATSAPP_AD,
            'facebook' => self::NAME_FACEBOOK_WHATSAPP_AD,
            default => self::NAME_WHATSAPP_AD,
        };
        $description = match ($platform) {
            'instagram' => 'Leads from Click-to-WhatsApp Instagram ads (CTWA referral).',
            'facebook' => 'Leads from Click-to-WhatsApp Facebook ads (CTWA referral).',
            default => 'Leads from Click-to-WhatsApp ads when placement (FB/IG) is unknown.',
        };

        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => $name,
            'description' => $description,
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

    /**
     * Return the source used for customer checkout bookings from the mobile app; creates it if missing.
     */
    public static function ensureDirectAppBookingSource(): self
    {
        $found = static::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(self::NAME_DIRECT_APP_BOOKING)])
            ->first();

        if ($found) {
            return $found;
        }

        return static::create([
            'name' => self::NAME_DIRECT_APP_BOOKING,
            'description' => 'Bookings placed directly by the customer in the mobile app.',
            'is_active' => true,
        ]);
    }
}
