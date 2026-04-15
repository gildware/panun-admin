<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\WhatsAppModule\Entities\Concerns\HasSocialInboxChannelScope;
use Modules\WhatsAppModule\Support\SocialInboxChannel;
use Modules\WhatsAppModule\Support\WhatsAppActiveChatsListCache;

/**
 * User table in the WhatsApp (Neon) database only.
 * Not related to the main app's User model. Columns: id, phone, name, email, created_at, updated_at, alternate_phone, address, type (CUSTOMER/PROVIDER).
 */
class WhatsAppUser extends Model
{
    use HasSocialInboxChannelScope;

    public function getTable()
    {
        return config('whatsappmodule.tables.users', 'whatsapp_users');
    }

    protected $fillable = [
        'channel',
        'phone',
        'name',
        'email',
        'alternate_phone',
        'address',
        'type',
        'handled_by',
        'human_support_requested_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'human_support_requested_at' => 'datetime',
    ];

    /**
     * Customer asked for a human; show in admin “Human support” tab until staff takes the chat or returns to AI.
     */
    public static function markHumanSupportRequested(string $phone): void
    {
        if ($phone === '') {
            return;
        }
        $u = static::firstOrNew([
            'phone' => $phone,
            'channel' => SocialInboxChannel::current(),
        ]);
        if (empty($u->channel)) {
            $u->channel = SocialInboxChannel::current();
        }
        $u->human_support_requested_at = now();
        $u->save();
        WhatsAppActiveChatsListCache::forgetAll();
    }

    public static function clearHumanSupportRequest(string $phone): void
    {
        if ($phone === '') {
            return;
        }
        static::query()->where('phone', $phone)->update(['human_support_requested_at' => null]);
        WhatsAppActiveChatsListCache::forgetAll();
    }
}
