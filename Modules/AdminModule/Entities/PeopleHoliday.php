<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeopleHoliday extends Model
{
    use HasUuid;

    protected $fillable = [
        'holiday_on',
        'name',
        'description',
    ];

    protected $casts = [
        'holiday_on' => 'date',
    ];
}
