<?php

namespace Modules\TaskBoardModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class TaskActivityLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TaskTicket::class, 'ticket_id')->withTrashed();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
