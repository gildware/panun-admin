<?php

namespace Modules\InAppCallModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class InAppCallSignal extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    public const TYPE_OFFER = 'offer';

    public const TYPE_ANSWER = 'answer';

    public const TYPE_ICE = 'ice';

    protected $table = 'in_app_call_signals';

    protected $fillable = [
        'call_id',
        'sender_user_id',
        'signal_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(InAppCall::class, 'call_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
