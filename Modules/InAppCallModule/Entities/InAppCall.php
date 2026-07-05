<?php

namespace Modules\InAppCallModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ChattingModule\Entities\ChannelList;
use Modules\UserManagement\Entities\User;

class InAppCall extends Model
{
    use HasUuid;

    public const STATUS_RINGING = 'ringing';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_MISSED = 'missed';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'in_app_calls';

    protected $fillable = [
        'channel_id',
        'caller_user_id',
        'callee_user_id',
        'agora_channel_name',
        'status',
        'reference_id',
        'reference_type',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'end_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelList::class, 'channel_id');
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_user_id');
    }

    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_user_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_DECLINED,
            self::STATUS_MISSED,
            self::STATUS_ENDED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
