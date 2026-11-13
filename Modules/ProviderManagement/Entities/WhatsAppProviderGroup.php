<?php

namespace Modules\ProviderManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppProviderGroup extends Model
{
    use HasUuid;

    protected $table = 'whatsapp_provider_groups';

    protected $fillable = [
        'name',
        'color',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(WhatsAppProviderGroupContact::class, 'group_id')
            ->orderBy('sort_order')
            ->orderBy('phone');
    }
}
