<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAttempt extends Model
{
    use HasUuid;

    protected $fillable = [
        'training_assignment_id',
        'attempt_number',
        'started_at',
        'content_done_at',
        'certified_at',
        'restarted_at',
        'quiz_correct',
        'quiz_answers',
        'ai_answer',
        'ai_hits',
        'ai_score',
        'lessons',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'content_done_at' => 'datetime',
        'certified_at' => 'datetime',
        'restarted_at' => 'datetime',
        'quiz_answers' => 'array',
        'ai_hits' => 'array',
        'lessons' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingAssignment::class, 'training_assignment_id');
    }
}
