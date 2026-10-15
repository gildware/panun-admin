<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCallTest extends Model
{
    use HasUuid;

    public const DIFFICULTIES = [
        'foundation' => 'Foundation',
        'standard' => 'Standard',
        'advanced' => 'Advanced',
    ];

    public const TONES = [
        'calm' => 'Calm',
        'warm' => 'Warm',
        'firm' => 'Firm',
        'upset' => 'Upset',
    ];

    public const SPEEDS = [
        'slow' => 'Slow',
        'normal' => 'Normal',
        'fast' => 'Fast',
    ];

    public const LANGUAGES = [
        'en' => 'English',
        'hi' => 'Hindi',
        'ur' => 'Urdu',
    ];

    public const STARTERS = [
        'caller' => 'Caller',
        'employee' => 'Employee',
    ];

    protected $fillable = [
        'name',
        'status',
        'brief',
        'prompt',
        'scenario_prompts',
        'scenarios',
        'skills',
        'skill_marks',
        'pass_mark',
        'difficulty',
        'tone',
        'speed',
        'default_language',
        'starter',
        'max_minutes',
        'created_by',
    ];

    protected $casts = [
        'scenarios' => 'array',
        'scenario_prompts' => 'array',
        'skills' => 'array',
        'skill_marks' => 'array',
        'pass_mark' => 'integer',
        'max_minutes' => 'integer',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingCallAssignment::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
