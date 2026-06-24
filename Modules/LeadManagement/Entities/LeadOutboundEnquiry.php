<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOutboundEnquiry extends Model
{
    protected $table = 'lead_outbound_enquiries';

    protected $fillable = [
        'lead_id',
        'related_lead_id',
        'booking_id',
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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function relatedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'related_lead_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(\Modules\BookingModule\Entities\Booking::class);
    }

    public function isFromFutureCustomerLead(): bool
    {
        return $this->lead?->lead_type === Lead::TYPE_FUTURE_CUSTOMER;
    }

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
