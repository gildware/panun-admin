<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeopleLeaveType extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'short_name',
        'code',
        'tracks_balance',
        'sort',
    ];

    protected $casts = [
        'tracks_balance' => 'boolean',
    ];

    public function policies(): HasMany
    {
        return $this->hasMany(PeopleLeavePolicy::class, 'leave_type_id');
    }
}
