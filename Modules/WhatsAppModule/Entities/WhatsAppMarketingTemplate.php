<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMarketingTemplate extends Model
{
    /** Laravel would guess `whats_app_marketing_templates` from the class name; DB table is `whatsapp_*`. */
    protected $table = 'whatsapp_marketing_templates';

    protected $fillable = [
        'meta_template_id',
        'name',
        'language',
        'category',
        'status',
        'body_parameter_count',
        'components',
        'preview_text',
        'synced_at',
    ];

    protected $casts = [
        'components' => 'array',
        'synced_at' => 'datetime',
        'body_parameter_count' => 'integer',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(WhatsAppMarketingCampaign::class, 'whatsapp_marketing_template_id');
    }

    public function scopeApproved($query)
    {
        return $query->whereRaw('UPPER(status) = ?', ['APPROVED']);
    }
}
