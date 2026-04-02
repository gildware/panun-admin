<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppAiExecution extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'whatsapp_ai_executions';

    protected $fillable = [
        'trigger_whatsapp_message_id',
        'phone',
        'status',
        'outcome',
        'summary',
        'outbound_whatsapp_message_id',
        'error_message',
        'steps',
        'meta',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'steps' => 'array',
        'meta' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
