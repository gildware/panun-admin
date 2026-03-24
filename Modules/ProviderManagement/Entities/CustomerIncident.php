<?php

namespace Modules\ProviderManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;

class CustomerIncident extends Model
{
    protected $table = 'customer_incidents';

    protected $fillable = [
        'customer_id',
        'booking_id',
        'action_type',
        'incident_type',
        'tags',
        'score_delta',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'score_delta' => 'integer',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

