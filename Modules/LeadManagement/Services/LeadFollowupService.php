<?php

namespace Modules\LeadManagement\Services;

use Carbon\Carbon;
use Modules\LeadManagement\Entities\Lead;

class LeadFollowupService
{
    /**
     * Default next follow-up: tomorrow at 10:00 (app timezone).
     */
    public function defaultNextFollowupAt(?Carbon $from = null): Carbon
    {
        $base = $from ?? Carbon::now();

        return $base->copy()->addDay()->setTime(10, 0, 0);
    }

    public function leadTypeRequiresMandatoryFollowup(string $leadType): bool
    {
        return ! in_array($leadType, [Lead::TYPE_INVALID, Lead::TYPE_FUTURE_CUSTOMER], true);
    }

    /**
     * Set {@see Lead::$next_followup_at} for new open-type leads (unknown, customer, provider).
     */
    public function applyInitialNextFollowupOnLeadCreate(Lead $lead, ?string $leadType = null): void
    {
        $type = (string) ($leadType ?? $lead->lead_type);
        if (! $this->leadTypeRequiresMandatoryFollowup($type)) {
            return;
        }

        $lead->next_followup_at = $this->defaultNextFollowupAt();
        $lead->save();
    }
}
