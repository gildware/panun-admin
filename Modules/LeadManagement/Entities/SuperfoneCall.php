<?php

namespace Modules\LeadManagement\Entities;

use App\Support\StoragePathPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\BookingModule\Entities\Booking;
use Modules\ProviderManagement\Entities\Provider;
use Modules\UserManagement\Entities\User;

class SuperfoneCall extends Model
{
    protected $fillable = [
        'cdr_uuid',
        'cdr_id',
        'cdr_request_uuid',
        'last_event',
        'direction',
        'disposition',
        'outcome',
        'duration_seconds',
        'ringing_seconds',
        'started_at',
        'answered_at',
        'ended_at',
        'customer_phone',
        'customer_phone_digits',
        'superfone_number',
        'organization_name',
        'staff_first_name',
        'staff_last_name',
        'staff_phone',
        'staff_name',
        'employee_user_id',
        'lead_id',
        'lead_followup_id',
        'provider_id',
        'customer_user_id',
        'booking_id',
        'booking_followup_id',
        'manual_unmatched',
        'called_party_type',
        'recording_url',
        'recording_url_expires_at',
        'recording_file_expires_at',
        'recording_path',
        'recording_disk',
        'recording_mime',
        'recording_original_name',
        'recording_error',
        'summary_text',
        'summary_insights',
        'payloads',
    ];

    protected $casts = [
        'cdr_id' => 'integer',
        'duration_seconds' => 'integer',
        'ringing_seconds' => 'integer',
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'recording_url_expires_at' => 'datetime',
        'recording_file_expires_at' => 'datetime',
        'summary_insights' => 'array',
        'payloads' => 'array',
        'manual_unmatched' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function followup(): BelongsTo
    {
        return $this->belongsTo(LeadFollowup::class, 'lead_followup_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function needsLeadLink(): bool
    {
        return $this->lead_id === null && $this->customer_user_id === null;
    }

    /**
     * Inbound (and direction-unknown) calls tagged missed. Outbound misses are unanswered.
     */
    public function scopeMissed($query)
    {
        return $query->where('outcome', 'missed')->where(function ($inner) {
            $inner->whereNull('direction')->orWhere('direction', '!=', 'OUTBOUND');
        });
    }

    public function hasRecording(): bool
    {
        return ! empty($this->recording_path);
    }

    public function durationLabel(): string
    {
        if ($this->duration_seconds === null) {
            return '—';
        }

        $seconds = max(0, (int) $this->duration_seconds);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function outcomeLabel(): string
    {
        return match ($this->outcome) {
            'answered' => translate('Answered'),
            'unanswered' => translate('Unanswered'),
            'missed' => $this->direction === 'OUTBOUND' ? translate('Unanswered') : translate('Missed'),
            'other' => $this->disposition ?: translate('Other'),
            default => translate('Unknown'),
        };
    }

    public function directionLabel(): string
    {
        return match ($this->direction) {
            'INBOUND' => translate('Inbound'),
            'OUTBOUND' => translate('Outbound'),
            default => $this->direction ?: '—',
        };
    }

    public function recordingStoragePath(): ?string
    {
        if (! $this->hasRecording()) {
            return null;
        }

        return StoragePathPrefix::apply('lead-followups/'.$this->recording_path);
    }

    public function recordingDisk(): string
    {
        return $this->recording_disk ?: getDisk();
    }

    public function recordingUrl(): ?string
    {
        $path = $this->recordingStoragePath();
        if ($path === null) {
            return null;
        }

        try {
            return Storage::disk($this->recordingDisk())->url($path);
        } catch (\Throwable) {
            return null;
        }
    }
};
