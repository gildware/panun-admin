<?php

namespace Modules\ProviderManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BookingModule\Entities\Booking;
use Modules\UserManagement\Entities\User;

class ProviderIncident extends Model
{
    protected $table = 'provider_incidents';

    protected $fillable = [
        'provider_id',
        'booking_id',
        'action_type',
        'incident_type',
        'tags',
        'score_delta',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'tags' => 'array',
        'score_delta' => 'integer',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

