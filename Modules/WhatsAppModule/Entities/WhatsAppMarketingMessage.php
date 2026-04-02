<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMarketingMessage extends Model
{
    protected $table = 'whatsapp_marketing_messages';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READ = 'read';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REPLIED = 'replied';

    protected $fillable = [
        'whatsapp_marketing_campaign_id',
        'recipient_name',
        'phone_e164',
        'status',
        'wa_message_id',
        'failure_reason',
        'sent_at',
        'delivered_at',
        'read_at',
        'replied_at',
        'body_parameters',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'body_parameters' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMarketingCampaign::class, 'whatsapp_marketing_campaign_id');
    }
}
