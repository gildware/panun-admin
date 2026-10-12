<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSection extends Model
{
    use HasUuid;

    protected $fillable = [
        'training_program_id',
        'title',
        'sort_order',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class)->orderBy('sort_order');
    }
}
