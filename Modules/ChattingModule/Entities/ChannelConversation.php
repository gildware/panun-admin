<?php

namespace Modules\ChattingModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class ChannelConversation extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'channel_id',
        'message',
        'user_id',
        'reply_to_conversation_id',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_conversation_id');
    }

    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    //relation
    public function conversationFiles(): HasMany
    {
        return $this->hasMany(ConversationFile::class, 'conversation_id', 'id');
    }
    public function conversationLastFiles(): HasMany
    {
        return $this->hasMany(ConversationFile::class, 'conversation_id', 'id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ConversationReaction::class, 'conversation_id', 'id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChannelList::class);
    }

    public function channel_users(): HasMany
    {
        return $this->hasMany(ChannelUser::class, 'channel_id', 'channel_id');
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            // ... code here
        });

        self::created(function ($model) {
            $model->channel_users
                ->where('user_id', '!=', $model->user_id)
                ->pluck('user_id')
                ->each(function ($item) use ($model) {
                    $to_user = User::with(['provider', 'serviceman'])->find($item);
                    $user = User::with(['provider', 'serviceman'])->find($model->user_id);
                    if (!$to_user || !$user) return;

                    $user_name = null;
                    $user_phone = null;
                    $user_image = null;
                    $user_type = null;

                    if ($user->user_type == USER_TYPES[0]['value']) {
                        //if admin
                        $user_name = business_config('business_name', 'business_information')?->live_values;
                        $user_phone = business_config('business_phone', 'business_information')?->live_values;
                        $user_image = asset('storage/app/public/business') . '/' . business_config('business_favicon', 'business_information')?->live_values;
                        $user_type = USER_TYPES[0]['value'];
                    } else if ($user->user_type == USER_TYPES[1]['value']) {
                        //if admin employee
                        $user_name = business_config('business_name', 'business_information')?->live_values;
                        $user_phone = business_config('business_phone', 'business_information')?->live_values;
                        $user_image = asset('storage/app/public/business') . '/' . business_config('business_favicon', 'business_information')?->live_values;
                        $user_type = USER_TYPES[1]['value'];
                    } elseif (is_provider_org_chat_user($user)) {
                        $providerOrg = Provider::query()->find(resolve_provider_org_id_for_user($user));
                        if (! $providerOrg) {
                            return;
                        }

                        $user_name = $providerOrg->company_name;
                        $user_phone = $providerOrg->company_phone;
                        $user_image = asset('storage/app/public/provider/logo') . '/' . $providerOrg->logo;
                        $user_type = $user->user_type;
                    } else if ($user->user_type == USER_TYPES[3]['value']) {
                        //if serviceman
                        $user_name = $user->first_name . ' ' . $user->last_name;
                        $user_phone = $user->phone;
                        $user_image = asset('storage/app/public/serviceman/profile') . '/' . $user->profile_image;
                        $user_type = USER_TYPES[3]['value'];
                    } else if ($user->user_type == USER_TYPES[4]['value']) {
                        //if customer
                        $user_name = $user->first_name . ' ' . $user->last_name;
                        $user_phone = $user->phone;
                        $user_image = asset('storage/app/public/user/profile_image') . '/' . $user->profile_image;
                        $user_type = USER_TYPES[4]['value'];
                    } else {
                        return;
                    }

                    if ($user_type) {
                        send_chat_message_push_notification(
                            $to_user,
                            (string) $model->channel_id,
                            $user_name,
                            $user_image,
                            $user_phone,
                            $user_type
                        );
                    }
                });

            if (function_exists('admin_inbox_notify_chat_message')) {
                admin_inbox_notify_chat_message($model);
            }
        });

        self::updating(function ($model) {
            // ... code here
        });

        self::updated(function ($model) {
            // ... code here
        });

        self::deleting(function ($model) {
            // ... code here
        });

        self::deleted(function ($model) {
            // ... code here
        });
    }
}
