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

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const DISPATCH_MODE_AUTO = 'auto';

    public const DISPATCH_MODE_APPROVAL = 'approval';

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
        'dispatch_mode',
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

    /**
     * @return array{value: int, unit: string}
     */
    public function resolvedInterval(): array
    {
        $filters = is_array($this->filters) ? $this->filters : [];
        $minutes = max(1, (int) $this->interval_minutes);

        if (in_array((string) ($filters['interval_unit'] ?? ''), ['minutes', 'hours', 'days'], true)
            && isset($filters['interval_value'])) {
            return [
                'value' => max(1, (int) $filters['interval_value']),
                'unit' => (string) $filters['interval_unit'],
            ];
        }

        return self::minutesToDurationParts($minutes);
    }

    /**
     * @return array{value: int, unit: string}
     */
    public static function minutesToDurationParts(int $minutes): array
    {
        $minutes = max(1, $minutes);

        if ($minutes % (24 * 60) === 0) {
            return ['value' => (int) ($minutes / (24 * 60)), 'unit' => 'days'];
        }

        if ($minutes % 60 === 0) {
            return ['value' => (int) ($minutes / 60), 'unit' => 'hours'];
        }

        return ['value' => $minutes, 'unit' => 'minutes'];
    }

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

        $silentMinUnit = in_array((string) ($filters['silent_min_unit'] ?? ''), ['minutes', 'hours', 'days'], true)
            ? (string) $filters['silent_min_unit']
            : 'hours';
        $silentMinValue = max(0, (int) ($filters['silent_min_value'] ?? 0));

        if (isset($filters['silent_min_unit'])) {
            $silentMinMinutes = match ($silentMinUnit) {
                'days' => $silentMinValue * 24 * 60,
                'hours' => $silentMinValue * 60,
                default => $silentMinValue,
            };
        } elseif (isset($filters['silent_min_minutes']) && $filters['silent_min_minutes'] !== '') {
            $silentMinMinutes = max(0, (int) $filters['silent_min_minutes']);
            $silentMinValue = $silentMinValue > 0 ? $silentMinValue : max(1, (int) floor($silentMinMinutes / 60) ?: 1);
        } else {
            $silentMinMinutes = max(0, (int) ($filters['silent_min_hours'] ?? 2)) * 60;
            $silentMinValue = $silentMinValue > 0 ? $silentMinValue : max(1, (int) ($filters['silent_min_hours'] ?? 2));
            $silentMinUnit = 'hours';
        }

        $humanSupport = (string) ($filters['human_support'] ?? '');
        $excludeHumanSupport = (string) ($filters['exclude_human_support'] ?? '');
        if ($excludeHumanSupport === '' && $humanSupport === 'exclude') {
            $excludeHumanSupport = 'exclude';
            $humanSupport = '';
        }

        return [
            'silent_min_value' => $silentMinValue,
            'silent_min_unit' => $silentMinUnit,
            'silent_min_minutes' => $silentMinMinutes,
            'silent_min_hours' => (int) floor($silentMinMinutes / 60),
            'silent_max_hours' => isset($filters['silent_max_hours']) && $filters['silent_max_hours'] !== ''
                ? max(0, (int) $filters['silent_max_hours'])
                : null,
            'lead_types' => array_values(array_filter((array) ($filters['lead_types'] ?? []))),
            'customer_lead_status_ids' => array_map('intval', array_filter((array) ($filters['customer_lead_status_ids'] ?? []))),
            'provider_lead_status_ids' => array_map('intval', array_filter((array) ($filters['provider_lead_status_ids'] ?? []))),
            'lead_open' => (string) ($filters['lead_open'] ?? ''),
            'wa_chat_bucket' => (string) ($filters['wa_chat_bucket'] ?? ''),
            'wa_chat_tag_ids' => array_map('intval', array_filter((array) ($filters['wa_chat_tag_ids'] ?? []))),
            'customer_lead_tag_ids' => array_map('intval', array_filter((array) ($filters['customer_lead_tag_ids'] ?? []))),
            'handled_by' => (string) ($filters['handled_by'] ?? ''),
            'handled_by_employee_ids' => array_values(array_filter((array) ($filters['handled_by_employee_ids'] ?? []))),
            'human_support' => $humanSupport,
            'wa_ai_flows' => array_values(array_filter((array) ($filters['wa_ai_flows'] ?? []))),
            'exclude_lead_types' => array_values(array_filter((array) ($filters['exclude_lead_types'] ?? []))),
            'exclude_wa_ai_flows' => array_values(array_filter((array) ($filters['exclude_wa_ai_flows'] ?? []))),
            'exclude_customer_lead_status_ids' => array_map('intval', array_filter((array) ($filters['exclude_customer_lead_status_ids'] ?? []))),
            'exclude_provider_lead_status_ids' => array_map('intval', array_filter((array) ($filters['exclude_provider_lead_status_ids'] ?? []))),
            'exclude_lead_open' => (string) ($filters['exclude_lead_open'] ?? ''),
            'exclude_wa_chat_bucket' => (string) ($filters['exclude_wa_chat_bucket'] ?? ''),
            'exclude_wa_chat_tag_ids' => array_map('intval', array_filter((array) ($filters['exclude_wa_chat_tag_ids'] ?? []))),
            'exclude_customer_lead_tag_ids' => array_map('intval', array_filter((array) ($filters['exclude_customer_lead_tag_ids'] ?? []))),
            'exclude_handled_by' => (string) ($filters['exclude_handled_by'] ?? ''),
            'exclude_handled_by_employee_ids' => array_values(array_filter((array) ($filters['exclude_handled_by_employee_ids'] ?? []))),
            'exclude_human_support' => $excludeHumanSupport,
            'exclude_called_within_hours' => max(0, (int) ($filters['exclude_called_within_hours'] ?? 24)),
            'other_cron_job_mode' => in_array((string) ($filters['other_cron_job_mode'] ?? ''), ['include', 'exclude', 'exclude_all_active'], true)
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
