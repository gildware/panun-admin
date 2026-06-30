<?php

namespace Modules\UserManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFcmDevice extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'device_id',
        'fcm_token',
        'platform',
        'device_model',
        'device_manufacturer',
        'os_version',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
