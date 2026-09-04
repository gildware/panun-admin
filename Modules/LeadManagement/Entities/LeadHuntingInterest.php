<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ProviderManagement\Entities\Provider;

class LeadHuntingInterest extends Model
{
    public const STATUS_INTERESTED = 'interested';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'lead_id',
        'provider_id',
        'status',
        'note',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}
