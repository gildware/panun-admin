<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeopleTimesheetTask extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'sort',
    ];
}
