<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingLessonProgress extends Model
{
    use HasUuid;

    protected $table = 'training_lesson_progress';

    protected $fillable = [
        'training_assignment_id',
        'training_lesson_id',
        'completed_at',
        'content_seen_at',
        'quiz_correct',
        'quiz_answers',
        'ai_answer',
        'ai_hits',
        'ai_score',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'content_seen_at' => 'datetime',
        'quiz_answers' => 'array',
        'ai_hits' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'training_assignment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'training_lesson_id');
    }
}
