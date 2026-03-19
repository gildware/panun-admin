<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_PROVIDER = 'provider';
    public const TYPE_INVALID = 'invalid';
    public const TYPE_FUTURE_CUSTOMER = 'future_customer';

    public static function leadTypes(): array
    {
        return [
            self::TYPE_UNKNOWN => 'Unknown',
            self::TYPE_CUSTOMER => 'Customer',
            self::TYPE_PROVIDER => 'Provider',
            self::TYPE_INVALID => 'Invalid',
            self::TYPE_FUTURE_CUSTOMER => 'Future Customer',
        ];
    }

    protected $fillable = [
        'name',
        'phone_number',
        'source_id',
        'lead_type',
        'date_time_of_lead_received',
        'ad_source_id',
        'handled_by',
        'remarks',
        'next_followup_at',
        'created_by',
    ];

    protected $casts = [
        'date_time_of_lead_received' => 'datetime',
        'next_followup_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function adSource(): BelongsTo
    {
        return $this->belongsTo(AdSource::class, 'ad_source_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'created_by', 'id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class)->latest('followup_at');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(LeadChangeLog::class)->latest('created_at');
    }

    public function providerChecklist(): HasMany
    {
        return $this->hasMany(LeadProviderChecklist::class);
    }

    public function customerLeadTags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerLeadTag::class, 'lead_customer_tag', 'lead_id', 'customer_lead_tag_id')
            ->withTimestamps();
    }

    public function scopeOfType($query, ?string $type)
    {
        if ($type && $type !== 'all') {
            return $query->where('lead_type', $type);
        }
        return $query;
    }

    /**
     * Computed status flag: a lead is considered "open"
     * when its type is unknown or customer.
     */
    public function getIsOpenAttribute(): bool
    {
        return in_array($this->lead_type, [
            self::TYPE_UNKNOWN,
            self::TYPE_CUSTOMER,
        ], true);
    }
}
