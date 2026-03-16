<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadProviderChecklist extends Model
{
    protected $table = 'lead_provider_checklist';

    protected $fillable = [
        'lead_id',
        'provider_checklist_item_id',
        'is_done',
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function providerChecklistItem(): BelongsTo
    {
        return $this->belongsTo(ProviderChecklistItem::class, 'provider_checklist_item_id');
    }
}
