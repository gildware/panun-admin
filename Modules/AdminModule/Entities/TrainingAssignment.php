<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\UserManagement\Entities\User;

class TrainingAssignment extends Model
{
    use HasUuid;

    protected $fillable = [
        'training_program_id',
        'user_id',
        'assigned_by',
        'due_at',
        'assigned_scope',
        'department_name',
    ];

    protected $casts = [
        'due_at' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progress(): HasOne
    {
        return $this->hasOne(TrainingProgress::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(TrainingLessonProgress::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TrainingAttempt::class)->orderBy('attempt_number');
    }
}
