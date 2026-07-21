<?php

namespace Modules\TaskBoardModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskColumn extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(TaskTicket::class, 'column_id')
            ->orderBy('position')
            ->orderBy('created_at');
    }
}
