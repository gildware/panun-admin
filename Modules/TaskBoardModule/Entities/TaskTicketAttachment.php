<?php

namespace Modules\TaskBoardModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\UserManagement\Entities\User;

class TaskTicketAttachment extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'comment_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'file_type',
        'disk',
    ];

    protected $appends = ['url'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TaskTicket::class, 'ticket_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskTicketComment::class, 'comment_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        $path = 'task-board/'.$this->stored_name;

        try {
            return Storage::disk($this->disk ?: getDisk())->url($path);
        } catch (\Throwable) {
            return asset('storage/'.$path);
        }
    }
}
