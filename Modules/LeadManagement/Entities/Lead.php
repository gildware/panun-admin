<?php

namespace Modules\LeadManagement\Entities;

use App\Support\StoragePathPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Lead extends Model
{
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_PROVIDER = 'provider';
    public const TYPE_INVALID = 'invalid';
    public const TYPE_FUTURE_CUSTOMER = 'future_customer';

    /** Stored in `handled_by` when the WhatsApp / AI pipeline owns the thread (not an employee). */
    public const HANDLED_BY_AI = 'AI';

    /** Filter value for "not assigned to an employee" (AI, empty, or null). */
    public const FILTER_UNASSIGNED_VALUE = '__unassigned__';

    public static function assigneeIsHuman(?string $handledBy): bool
    {
        return $handledBy !== null && $handledBy !== '' && $handledBy !== self::HANDLED_BY_AI;
    }

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
        'initial_call_recording_path',
        'initial_call_recording_disk',
        'initial_call_recording_mime',
        'initial_call_recording_original_name',
        'initial_call_recording_transcript',
        'initial_call_recording_summary',
        'initial_call_recording_transcribed_at',
        'next_followup_at',
        'created_by',
    ];

    protected $casts = [
        'date_time_of_lead_received' => 'datetime',
        'next_followup_at' => 'datetime',
        'initial_call_recording_transcribed_at' => 'datetime',
    ];

    public function hasInitialCallRecording(): bool
    {
        return ! empty($this->initial_call_recording_path);
    }

    public function hasInitialCallTranscript(): bool
    {
        return ! empty($this->initial_call_recording_transcript);
    }

    public function getInitialCallRecordingUrlAttribute(): ?string
    {
        if (! $this->hasInitialCallRecording()) {
            return null;
        }

        $path = StoragePathPrefix::apply('lead-initial-calls/'.$this->initial_call_recording_path);

        try {
            return Storage::disk($this->initial_call_recording_disk ?: getDisk())->url($path);
        } catch (\Throwable) {
            return asset('storage/'.$path);
        }
    }

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

    public function outboundEnquiries(): HasMany
    {
        return $this->hasMany(LeadOutboundEnquiry::class)->latest('contacted_at');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class)->latest('followup_at');
    }

    public function latestFollowup(): HasOne
    {
        return $this->hasOne(LeadFollowup::class)->latestOfMany('followup_at');
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(LeadChangeLog::class)->latest('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LeadComment::class)
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest('created_at');
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
