<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\LeadManagement\Services\LeadFollowupReminderNotifier;

class SendLeadFollowupReminderNotifications extends Command
{
    protected $signature = 'notifications:send-lead-followup-reminders';

    protected $description = 'Notify lead assignees when follow-ups are due or overdue';

    public function handle(LeadFollowupReminderNotifier $notifier): int
    {
        $sent = $notifier->sendDueReminders();
        $this->info("Lead follow-up reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
