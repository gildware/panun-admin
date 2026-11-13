<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppProviderGroupContact extends Model
{
    use HasUuid;

    protected $table = 'whatsapp_provider_group_contacts';

    protected $fillable = [
        'group_id',
        'phone',
        'name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsAppProviderGroup::class, 'group_id');
    }
}
