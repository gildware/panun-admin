<?php

namespace Modules\CallCenterModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class Voicemail extends Model
{
    protected $table = 'call_center_voicemails';

    protected $fillable = [
        'external_id',
        'call_id',
        'call_external_id',
        'customer_profile_id',
        'user_id',
        'from_number',
        'to_number',
        'recording_url',
        'duration_seconds',
        'status',
        'received_at',
        'listened_at',
        'returned_call_external_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'listened_at' => 'datetime',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function customerProfile(): BelongsTo
    {
        return $this->belongsTo(CustomerProfile::class, 'customer_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
