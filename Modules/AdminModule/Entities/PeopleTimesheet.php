<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class PeopleTimesheet extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'week_starts_on',
        'hours',
        'entries',
        'note',
        'status',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'week_starts_on' => 'date',
        'hours' => 'array',
        'entries' => 'array',
        'decided_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalHours(): float
    {
        return round(array_sum(array_map('floatval', $this->hours ?? [])), 1);
    }
}
