<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\LeadManagement\Entities\Lead;
use Modules\UserManagement\Entities\User;

class AppCustomRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    /** @deprecated Use STATUS_PENDING */
    public const STATUS_PENDING_REVIEW = 'pending';

    /** @deprecated Use STATUS_ACCEPTED */
    public const STATUS_CONVERTED = 'accepted';

    /** @deprecated Use STATUS_REJECTED */
    public const STATUS_CANCELLED = 'rejected';

    protected $fillable = [
        'reference_id',
        'customer_id',
        'name',
        'phone',
        'category_id',
        'category_name',
        'description',
        'status',
        'customer_last_read_at',
        'lead_id',
    ];

    protected $casts = [
        'customer_last_read_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AppCustomRequestMessage::class)->orderBy('id');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => translate('Pending'),
            self::STATUS_ACCEPTED => translate('Accepted'),
            self::STATUS_REJECTED => translate('Rejected'),
        ];
    }
}
