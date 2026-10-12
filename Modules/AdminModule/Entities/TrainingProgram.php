<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TrainingProgram extends Model
{
    use HasUuid;

    public const DIFFICULTIES = [
        'foundation' => 'Foundation',
        'standard' => 'Standard',
        'advanced' => 'Advanced',
    ];

    protected $fillable = [
        'name',
        'short_description',
        'status',
        'version',
        'difficulty',
        'text_title',
        'text_body',
        'audio_script',
        'video_note',
        'ai_situation',
        'question_pass_mark',
        'ai_pass_mark',
        'created_by',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(TrainingQuestion::class)->orderBy('sort_order');
    }

    public function markingPoints(): HasMany
    {
        return $this->hasMany(TrainingMarkingPoint::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TrainingSection::class)->orderBy('sort_order');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(TrainingLesson::class, TrainingSection::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function difficultyLabel(): string
    {
        return self::DIFFICULTIES[$this->difficulty] ?? 'Standard';
    }
}
