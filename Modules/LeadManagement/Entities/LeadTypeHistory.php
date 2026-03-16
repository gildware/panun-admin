<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadTypeHistory extends Model
{
    protected $fillable = [
        'lead_id',
        'type',
        'data',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

