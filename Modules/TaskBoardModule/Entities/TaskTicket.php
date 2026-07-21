<?php

namespace Modules\TaskBoardModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\UserManagement\Entities\User;

class TaskTicket extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'column_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'position',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'position' => 'integer',
    ];

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'column_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'task_ticket_assignees',
            'ticket_id',
            'user_id'
        )->withTimestamps();
    }

    public function links(): HasMany
    {
        return $this->hasMany(TaskTicketLink::class, 'ticket_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskTicketComment::class, 'ticket_id')->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskTicketAttachment::class, 'ticket_id')
            ->whereNull('comment_id')
            ->latest();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TaskActivityLog::class, 'ticket_id')->latest();
    }
}
