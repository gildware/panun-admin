<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TrainingLesson extends Model
{
    use HasUuid;

    public const TYPES = [
        'text' => 'Text',
        'audio' => 'Audio',
        'video' => 'Video',
        'quiz' => 'Questions',
        'ai' => 'AI test',
    ];

    protected $fillable = [
        'training_section_id',
        'title',
        'type',
        'body',
        'media_kind',
        'media_path',
        'pass_mark',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TrainingLesson $lesson) {
            $lesson->deleteStoredMedia();
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TrainingSection::class, 'training_section_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(TrainingQuestion::class)->orderBy('sort_order');
    }

    public function markingPoints(): HasMany
    {
        return $this->hasMany(TrainingMarkingPoint::class)->orderBy('sort_order');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Text';
    }

    public function hasContent(): bool
    {
        return match ($this->type) {
            'quiz' => $this->questions->isNotEmpty(),
            'ai' => filled($this->body) && $this->markingPoints->isNotEmpty(),
            'audio', 'video' => filled($this->media_path),
            default => filled(trim(strip_tags((string) $this->body))),
        };
    }

    public function embedUrl(): ?string
    {
        if ($this->media_kind !== 'url' || ! filled($this->media_path)) {
            return null;
        }
        $url = (string) $this->media_path;
        if (preg_match('~(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $match)) {
            return 'https://www.youtube-nocookie.com/embed/'.$match[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1];
        }

        return null;
    }

    public function playbackUrl(): ?string
    {
        if ($this->media_kind === 'upload' && filled($this->media_path)) {
            return route('admin.training.media', $this);
        }
        if ($this->media_kind === 'url' && filled($this->media_path) && $this->embedUrl() === null) {
            return (string) $this->media_path;
        }

        return null;
    }

    public function deleteStoredMedia(): void
    {
        if ($this->media_kind === 'upload' && filled($this->media_path) && Storage::disk('local')->exists($this->media_path)) {
            Storage::disk('local')->delete($this->media_path);
        }
    }
}
