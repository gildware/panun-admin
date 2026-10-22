<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Lead extends Model
{
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_PROVIDER = 'provider';
    public const TYPE_INVALID = 'invalid';
    public const TYPE_FUTURE_CUSTOMER = 'future_customer';

    public const TYPE_FUTURE_PROVIDER = 'future_provider';

    /** Stored in `handled_by` when the WhatsApp / AI pipeline owns the thread (not an employee). */
    public const HANDLED_BY_AI = 'AI';

    /** Filter value for "not assigned to an employee" (AI, empty, or null). */
    public const FILTER_UNASSIGNED_VALUE = '__unassigned__';

    public const HUNTING_OFF = 'off';

    public const HUNTING_PUBLISHED = 'published';

    public const HUNTING_UNPUBLISHED = 'unpublished';

    public const TEMPERATURE_HOTTER = 'hotter';

    public const TEMPERATURE_HOT = 'hot';

    public const TEMPERATURE_WARM = 'warm';

    public const TEMPERATURE_COLD = 'cold';

    public const TEMPERATURES = [
        self::TEMPERATURE_HOTTER,
        self::TEMPERATURE_HOT,
        self::TEMPERATURE_WARM,
        self::TEMPERATURE_COLD,
    ];

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
            self::TYPE_FUTURE_PROVIDER => 'Future Provider',
        ];
    }

    /**
     * How ready a customer or provider lead is to convert.
     *
     * @return array<string, string>
     */
    public static function leadTemperatures(): array
    {
        return [
            self::TEMPERATURE_HOTTER => 'Hotter_Lead',
            self::TEMPERATURE_HOT => 'Hot_Lead',
            self::TEMPERATURE_WARM => 'Warm_Lead',
            self::TEMPERATURE_COLD => 'Cold_Lead',
        ];
    }

    public static function leadTemperatureLabel(?string $temperature): string
    {
        if ($temperature === null || $temperature === '') {
            return '—';
        }

        $key = self::leadTemperatures()[$temperature] ?? null;

        return $key ? translate($key) : '—';
    }

    public static function leadTemperatureShortLabel(?string $temperature): string
    {
        return match ($temperature) {
            self::TEMPERATURE_HOTTER => translate('Hotter'),
            self::TEMPERATURE_HOT => translate('Hot'),
            self::TEMPERATURE_COLD => translate('Cold'),
            default => translate('Warm'),
        };
    }

    public static function leadTemperatureIcon(?string $temperature): string
    {
        return match ($temperature) {
            self::TEMPERATURE_HOTTER => 'local_fire_department',
            self::TEMPERATURE_HOT => 'whatshot',
            self::TEMPERATURE_COLD => 'ac_unit',
            default => 'wb_sunny',
        };
    }

    protected $fillable = [
        'name',
        'phone_number',
        'source_id',
        'lead_type',
        'lead_temperature',
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
        'non_responsive_at',
        'non_responsive_by',
        'non_responsive_remarks',
        'hunting_status',
        'hunting_started_at',
        'hunting_started_by',
        'hunting_unpublished_at',
        'hunting_unpublished_by',
        'hunting_unpublish_reason',
        'hunting_unpublish_notes',
        'created_by',
    ];

    protected $casts = [
        'date_time_of_lead_received' => 'datetime',
        'next_followup_at' => 'datetime',
        'non_responsive_at' => 'datetime',
        'hunting_started_at' => 'datetime',
        'hunting_unpublished_at' => 'datetime',
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

        return route('admin.lead.initial-call-recording.play', $this);
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

    public function huntingInterests(): HasMany
    {
        return $this->hasMany(LeadHuntingInterest::class);
    }

    public function huntingStartedByUser(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'hunting_started_by', 'id');
    }

    public function isHuntingPublished(): bool
    {
        return ($this->hunting_status ?? self::HUNTING_OFF) === self::HUNTING_PUBLISHED;
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
     * Unknown, customer, and provider leads can be closed as non-responsive without changing type.
     */
    public function supportsNonResponsiveClose(): bool
    {
        return in_array($this->lead_type, [self::TYPE_UNKNOWN, self::TYPE_CUSTOMER, self::TYPE_PROVIDER], true);
    }

    /**
     * Closed because nobody responded. The lead type stays unknown, customer, or provider.
     */
    public function isNonResponsive(): bool
    {
        return $this->supportsNonResponsiveClose() && $this->non_responsive_at !== null;
    }

    /**
     * Simplified flag for unknown and customer leads: open unless closed as non-responsive.
     * Provider open/closed state is decided from the provider status plus this close.
     */
    public function getIsOpenAttribute(): bool
    {
        if (in_array($this->lead_type, [self::TYPE_UNKNOWN, self::TYPE_CUSTOMER], true)) {
            return ! $this->isNonResponsive();
        }

        return false;
    }
}
