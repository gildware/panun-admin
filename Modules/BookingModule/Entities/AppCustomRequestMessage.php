<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class AppCustomRequestMessage extends Model
{
    public const SENDER_ADMIN = 'admin';

    public const SENDER_CUSTOMER = 'customer';

    protected $fillable = [
        'app_custom_request_id',
        'sender_type',
        'sender_id',
        'message',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AppCustomRequest::class, 'app_custom_request_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
