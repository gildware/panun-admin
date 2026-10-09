<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeopleTimesheetSetting extends Model
{
    use HasUuid;

    protected $fillable = [
        'min_hours',
        'min_hours_full_time',
        'min_hours_part_time',
        'week_off',
        'starts_on',
    ];

    protected $casts = [
        'min_hours' => 'float',
        'min_hours_full_time' => 'float',
        'min_hours_part_time' => 'float',
        'week_off' => 'array',
        'starts_on' => 'date',
    ];
}
