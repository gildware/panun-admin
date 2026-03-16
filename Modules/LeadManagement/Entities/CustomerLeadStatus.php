<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerLeadStatus extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_type',
        'color',
        'is_active',
    ];

    protected $casts = [
        'base_type' => 'string',
        'is_active' => 'boolean',
    ];
}
