<?php

namespace Modules\LeadManagement\Entities;

use App\Support\StoragePathPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LeadFollowup extends Model
{
    public const URGENCY_HIGH = 'high';
    public const URGENCY_MEDIUM = 'medium';
    public const URGENCY_LOW = 'low';

    public const CHANNEL_CALL = 'call';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_TAKEN = 'taken';
    public const STATUS_RESCHEDULE = 'reschedule';

    public const URGENCIES = [
        self::URGENCY_HIGH,
        self::URGENCY_MEDIUM,
        self::URGENCY_LOW,
    ];

    public const CONTACT_CHANNELS = [
        self::CHANNEL_CALL,
        self::CHANNEL_WHATSAPP,
    ];

    public const FOLLOWUP_STATUSES = [
        self::STATUS_TAKEN,
        self::STATUS_RESCHEDULE,
    ];

    public const CALLED_PARTY_CUSTOMER = 'customer';
    public const CALLED_PARTY_PROVIDER = 'provider';
    public const CALLED_PARTY_OTHER = 'other';

    public const CALLED_PARTY_TYPES = [
        self::CALLED_PARTY_CUSTOMER,
        self::CALLED_PARTY_PROVIDER,
        self::CALLED_PARTY_OTHER,
    ];

    protected $attributes = [
        'urgency' => self::URGENCY_MEDIUM,
    ];

    protected $fillable = [
        'lead_id',
        'followup_at',
        'due_followup_at',
        'remarks',
        'contact_channel',
        'called_party_type',
        'called_name',
        'called_number',
        'called_provider_id',
        'followup_status',
        'recording_path',
        'recording_disk',
        'recording_mime',
        'recording_original_name',
        'recording_transcript',
        'recording_summary',
        'transcribed_at',
        'urgency',
        'next_followup_at',
        'created_by',
    ];

    protected $casts = [
        'followup_at' => 'datetime',
        'due_followup_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'transcribed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'created_by');
    }

    public function calledProvider(): BelongsTo
    {
        return $this->belongsTo(\Modules\ProviderManagement\Entities\Provider::class, 'called_provider_id');
    }

    public function calledPartyTypeLabel(): ?string
    {
        return match ($this->called_party_type) {
            self::CALLED_PARTY_CUSTOMER => translate('Customer'),
            self::CALLED_PARTY_PROVIDER => translate('Provider'),
            self::CALLED_PARTY_OTHER => translate('Other'),
            default => null,
        };
    }

    public function calledPartyDisplay(): string
    {
        $name = trim((string) ($this->called_name ?? ''));
        $number = trim((string) ($this->called_number ?? ''));

        if ($name !== '' && $number !== '') {
            return $name.' ('.$number.')';
        }

        if ($name !== '') {
            return $name;
        }

        return $number !== '' ? $number : '—';
    }

    public function hasRecording(): bool
    {
        return ! empty($this->recording_path);
    }

    public function hasTranscript(): bool
    {
        return ! empty($this->recording_transcript);
    }

    public function getRecordingUrlAttribute(): ?string
    {
        if (! $this->hasRecording()) {
            return null;
        }

        $path = StoragePathPrefix::apply('lead-followups/'.$this->recording_path);

        try {
            return Storage::disk($this->recording_disk ?: getDisk())->url($path);
        } catch (\Throwable) {
            return asset('storage/'.$path);
        }
    }

    public function contactChannelLabel(): ?string
    {
        return match ($this->contact_channel) {
            self::CHANNEL_CALL => translate('Call'),
            self::CHANNEL_WHATSAPP => translate('WhatsApp'),
            default => null,
        };
    }

    public function isRescheduled(): bool
    {
        return $this->followup_status === self::STATUS_RESCHEDULE;
    }

    public function followupStatusLabel(): string
    {
        return match ($this->followup_status) {
            self::STATUS_RESCHEDULE => translate('Reschedule'),
            default => translate('Taken'),
        };
    }

    /**
     * @return list<string>
     */
    public static function parseTranscriptLines(string $transcript): array
    {
        $transcript = trim($transcript);
        if ($transcript === '') {
            return [];
        }

        $transcript = preg_replace("/\r\n|\r|\n/", "\n", $transcript) ?? $transcript;
        $transcript = preg_replace('/\s*(?=Support:|Customer:|User:)/i', "\n", $transcript) ?? $transcript;

        $chunks = preg_split('/(?=(?:Support:|Customer:|User:))/i', $transcript) ?: [];
        $lines = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            $chunk = preg_replace('/^Customer:/i', 'User:', $chunk) ?? $chunk;
            $lines[] = $chunk;
        }

        return $lines;
    }

    public static function formatTranscript(string $transcript): string
    {
        return implode("\n", self::parseTranscriptLines($transcript));
    }

    public static function transcriptLineClass(string $line): string
    {
        if (preg_match('/^User:/i', trim($line))) {
            return 'voice-call-transcript-line--user';
        }

        if (preg_match('/^Support:/i', trim($line))) {
            return 'voice-call-transcript-line--llm';
        }

        return '';
    }
}

