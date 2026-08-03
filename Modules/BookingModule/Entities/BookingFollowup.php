<?php

namespace Modules\BookingModule\Entities;

use App\Support\StoragePathPrefix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\UserManagement\Entities\User;

class BookingFollowup extends Model
{
    public const URGENCY_HIGH = 'high';
    public const URGENCY_MEDIUM = 'medium';
    public const URGENCY_LOW = 'low';

    public const CHANNEL_CALL = 'call';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const ACTION_TAKEN = 'taken';
    public const ACTION_RESCHEDULE = 'reschedule';

    public const URGENCIES = [
        self::URGENCY_HIGH,
        self::URGENCY_MEDIUM,
        self::URGENCY_LOW,
    ];

    public const CONTACT_CHANNELS = [
        self::CHANNEL_CALL,
        self::CHANNEL_WHATSAPP,
    ];

    public const FOLLOWUP_ACTIONS = [
        self::ACTION_TAKEN,
        self::ACTION_RESCHEDULE,
    ];

    protected $attributes = [
        'urgency' => self::URGENCY_MEDIUM,
    ];

    protected $fillable = [
        'booking_id',
        'date',
        'followup_at',
        'due_followup_at',
        'next_followup_at',
        'reason',
        'for',
        'status',
        'remarks',
        'contact_channel',
        'recording_path',
        'recording_disk',
        'recording_mime',
        'recording_original_name',
        'recording_transcript',
        'recording_summary',
        'transcribed_at',
        'urgency',
        'reschedule_reason',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'followup_at' => 'datetime',
        'due_followup_at' => 'datetime',
        'next_followup_at' => 'datetime',
        'transcribed_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

        $path = StoragePathPrefix::apply('booking-followups/'.$this->recording_path);

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
        return $this->status === 'rescheduled';
    }

    public function isTaken(): bool
    {
        return $this->status === 'completed';
    }

    public function followupStatusLabel(): string
    {
        return match ($this->status) {
            'rescheduled' => translate('Reschedule'),
            'completed' => translate('Taken'),
            'scheduled' => translate('Scheduled'),
            'cancelled' => translate('Cancelled'),
            default => translate(ucfirst((string) $this->status)),
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
