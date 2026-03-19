<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOutboundEnquiry extends Model
{
    protected $table = 'lead_outbound_enquiries';

    protected $fillable = [
        'customer_name',
        'phone_number',
        'contacted_through',
        'remarks',
        'status',
        'status_id',
        'contacted_at',
        'created_by',
        'handled_by',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'created_by', 'id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(\Modules\UserManagement\Entities\User::class, 'handled_by', 'id');
    }

    public function statusConfig(): BelongsTo
    {
        return $this->belongsTo(LeadOutboundEnquiryStatus::class, 'status_id');
    }
}

