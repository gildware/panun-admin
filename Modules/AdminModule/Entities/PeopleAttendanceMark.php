<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleAttendanceMark extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'marked_on',
        'status',
        'marked_by',
    ];

    protected $casts = [
        'marked_on' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
