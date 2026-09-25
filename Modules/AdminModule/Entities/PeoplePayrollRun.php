<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeoplePayrollRun extends Model
{
    use HasUuid;

    protected $fillable = [
        'period',
        'status',
        'attendance_locked',
        'published_by',
        'published_at',
        'locked_at',
    ];

    protected $casts = [
        'attendance_locked' => 'boolean',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];
}
