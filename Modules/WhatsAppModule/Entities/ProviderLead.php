<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Provider lead in the WhatsApp PostgreSQL database.
 * Columns: lead_id, phone, name, address, service, form_sent, status, created_at
 */
class ProviderLead extends Model
{
    // Table has only created_at, no updated_at column.
    public const UPDATED_AT = null;

    public function getTable()
    {
        return config('whatsappmodule.tables.provider_lead', 'whatsapp_provider_leads');
    }

    protected $primaryKey = 'lead_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_TENTATIVE_PENDING_HUMAN = 'TENTATIVE_PENDING_HUMAN';

    public const STATUS_HUMAN_CONFIRMED = 'HUMAN_CONFIRMED';

    protected $fillable = [
        'lead_id',
        'phone',
        'name',
        'address',
        'service',
        'form_sent',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
