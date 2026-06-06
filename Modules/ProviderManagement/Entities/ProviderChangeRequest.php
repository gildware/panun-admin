<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class ProviderChangeRequest extends Model
{
    use HasUuid;

    public const STATUS_DENIED = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_PENDING = 2;

    protected $table = 'provider_change_requests';

    protected $casts = [
        'payload' => 'array',
        'status' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    protected $fillable = [
        'provider_id',
        'change_type',
        'status',
        'payload',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOfStatus($query, int $status)
    {
        return $query->where('status', $status);
    }
}
