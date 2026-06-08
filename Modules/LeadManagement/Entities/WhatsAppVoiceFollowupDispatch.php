<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppVoiceFollowupDispatch extends Model
{
    protected $table = 'whatsapp_voice_followup_dispatches';

    protected $fillable = [
        'wa_phone',
        'to_number_e164',
        'lead_id',
        'lead_type',
        'omnidim_campaign_id',
        'omnidim_request_id',
        'call_status',
        'call_context',
        'source',
        'dispatched_by',
        'automation_run_id',
    ];

    protected $casts = [
        'lead_id' => 'integer',
        'omnidim_campaign_id' => 'integer',
        'omnidim_request_id' => 'integer',
        'call_context' => 'array',
        'automation_run_id' => 'integer',
    ];

    public function automationRun(): BelongsTo
    {
        return $this->belongsTo(WhatsAppVoiceFollowupAutomationRun::class, 'automation_run_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return array<string, \Carbon\Carbon|null>
     */
    public static function latestAttemptAtByWaPhone(array $waPhones): array
    {
        if ($waPhones === []) {
            return [];
        }

        $rows = static::query()
            ->whereIn('wa_phone', $waPhones)
            ->orderByDesc('created_at')
            ->get(['wa_phone', 'created_at', 'call_status']);

        $map = [];
        foreach ($rows as $row) {
            $phone = (string) $row->wa_phone;
            if (!isset($map[$phone])) {
                $map[$phone] = $row->created_at;
            }
        }

        return $map;
    }
}
