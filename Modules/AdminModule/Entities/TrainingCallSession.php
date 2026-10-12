<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingCallSession extends Model
{
    use HasUuid;

    protected $fillable = [
        'training_call_assignment_id',
        'scenario',
        'transcript',
        'scores',
        'overall',
        'passed',
        'summary',
        'scored_by',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'transcript' => 'array',
        'scores' => 'array',
        'overall' => 'integer',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TrainingCallAssignment::class, 'training_call_assignment_id');
    }
}
