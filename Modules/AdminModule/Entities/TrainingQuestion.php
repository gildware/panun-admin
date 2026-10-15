<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingQuestion extends Model
{
    use HasUuid;

    protected $fillable = [
        'training_program_id',
        'training_lesson_id',
        'prompt',
        'options',
        'correct_index',
        'explanation',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_index' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'training_lesson_id');
    }
}
