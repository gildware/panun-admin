<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadFutureCustomerReason extends Model
{
    protected $fillable = [
        'name',
        'description',
        'followup_label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(LeadFutureCustomerReasonOption::class)->orderBy('name');
    }
}

