<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppVoiceFollowupAutomationRun extends Model
{
    protected $table = 'whatsapp_voice_followup_automation_runs';

    public const TRIGGER_CRON = 'cron';

    public const TRIGGER_MANUAL = 'manual';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    protected $fillable = [
        'rule_id',
        'status',
        'contacts_matched',
        'contacts_dispatched',
        'campaign_ids',
        'pending_candidates',
        'trigger',
        'duration_ms',
        'message',
        'error',
        'started_at',
        'finished_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'rule_id' => 'integer',
        'contacts_matched' => 'integer',
        'contacts_dispatched' => 'integer',
        'campaign_ids' => 'array',
        'pending_candidates' => 'array',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(WhatsAppVoiceFollowupAutomationRule::class, 'rule_id');
    }

    public function dispatches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WhatsAppVoiceFollowupDispatch::class, 'automation_run_id');
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }
}
