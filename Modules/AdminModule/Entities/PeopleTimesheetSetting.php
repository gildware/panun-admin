<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeopleTimesheetSetting extends Model
{
    use HasUuid;

    protected $fillable = [
        'min_hours',
        'week_off',
    ];

    protected $casts = [
        'min_hours' => 'float',
        'week_off' => 'array',
    ];
}
