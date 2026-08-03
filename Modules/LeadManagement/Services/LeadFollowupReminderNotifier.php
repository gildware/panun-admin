<?php

namespace Modules\LeadManagement\Services;

use Modules\LeadManagement\Entities\Lead;

class LeadFollowupReminderNotifier
{
    public function __construct(
        private readonly LeadOpenStatusService $openStatusService,
        private readonly LeadFollowupService $followupService,
    ) {
    }

    public function sendDueReminders(): int
    {
        $sent = 0;

        Lead::query()
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', now())
            ->whereIn('lead_type', [
                Lead::TYPE_UNKNOWN,
                Lead::TYPE_CUSTOMER,
                Lead::TYPE_PROVIDER,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($leads) use (&$sent) {
                $meta = $this->openStatusService->buildLeadStatusMeta($leads);

                foreach ($leads as $lead) {
                    if (! ($meta[(int) $lead->id]['is_open'] ?? false)) {
                        continue;
                    }

                    if (! Lead::assigneeIsHuman($lead->handled_by ?? null)) {
                        continue;
                    }

                    if (! $this->followupService->leadTypeRequiresMandatoryFollowup((string) $lead->lead_type)) {
                        continue;
                    }

                    if (! function_exists('admin_inbox_notify_lead_followup_due')) {
                        continue;
                    }

                    $dueAt = $lead->next_followup_at;
                    $isOverdue = $this->followupService->pendingFollowupIsOverdue($dueAt);

                    $notification = admin_inbox_notify_lead_followup_due($lead, $isOverdue);
                    if ($notification !== null) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
