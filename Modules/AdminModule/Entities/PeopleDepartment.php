<?php

namespace Modules\AdminModule\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PeopleDepartment extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
    ];
}
