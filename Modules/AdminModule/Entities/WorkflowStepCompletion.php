<?php

namespace Modules\AdminModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepCompletion extends Model
{
    public const ENTITY_LEAD = 'lead';

    public const ENTITY_BOOKING = 'booking';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'step_key',
        'is_done',
        'done_by',
        'done_at',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function doneByUser(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'done_by', 'id');
    }
}
