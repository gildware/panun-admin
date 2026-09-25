<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleDocument extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'original_name',
        'status',
        'uploaded_at',
        'rejection_note',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
