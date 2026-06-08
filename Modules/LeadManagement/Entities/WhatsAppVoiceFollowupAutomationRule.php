<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppVoiceFollowupAutomationRule extends Model
{
    protected $table = 'whatsapp_voice_followup_automation_rules';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_EMPTY = 'empty';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'name',
        'is_enabled',
        'interval_minutes',
        'filters',
        'campaign_name',
        'max_contacts_per_run',
        'concurrent_call_limit',
        'enabled_reschedule_call',
        'auto_retry',
        'auto_retry_schedule',
        'retry_limit',
        'last_run_at',
        'last_run_contacts',
        'last_run_status',
        'last_run_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'interval_minutes' => 'integer',
        'filters' => 'array',
        'max_contacts_per_run' => 'integer',
        'concurrent_call_limit' => 'integer',
        'enabled_reschedule_call' => 'boolean',
        'auto_retry' => 'boolean',
        'retry_limit' => 'integer',
        'last_run_at' => 'datetime',
        'last_run_contacts' => 'integer',
    ];

    public function isDue(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        if ($this->last_run_at === null) {
            return true;
        }

        return $this->last_run_at->copy()->addMinutes(max(1, (int) $this->interval_minutes))->lte(now());
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedFilters(): array
    {
        $filters = is_array($this->filters) ? $this->filters : [];

        return [
            'silent_min_hours' => max(0, (int) ($filters['silent_min_hours'] ?? 2)),
            'silent_max_hours' => isset($filters['silent_max_hours']) && $filters['silent_max_hours'] !== ''
                ? max(0, (int) $filters['silent_max_hours'])
                : null,
            'lead_types' => array_values(array_filter((array) ($filters['lead_types'] ?? []))),
            'lead_open' => (string) ($filters['lead_open'] ?? ''),
            'wa_chat_bucket' => (string) ($filters['wa_chat_bucket'] ?? ''),
            'wa_chat_tag_ids' => array_map('intval', array_filter((array) ($filters['wa_chat_tag_ids'] ?? []))),
            'customer_lead_tag_ids' => array_map('intval', array_filter((array) ($filters['customer_lead_tag_ids'] ?? []))),
            'handled_by' => (string) ($filters['handled_by'] ?? ''),
            'human_support' => (string) ($filters['human_support'] ?? 'exclude'),
            'exclude_called_within_hours' => max(0, (int) ($filters['exclude_called_within_hours'] ?? 24)),
            'other_cron_job_mode' => in_array((string) ($filters['other_cron_job_mode'] ?? ''), ['include', 'exclude'], true)
                ? (string) $filters['other_cron_job_mode']
                : '',
            'other_cron_job_ids' => array_map('intval', array_filter((array) ($filters['other_cron_job_ids'] ?? []))),
        ];
    }

    public function runs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WhatsAppVoiceFollowupAutomationRun::class, 'rule_id');
    }
};
