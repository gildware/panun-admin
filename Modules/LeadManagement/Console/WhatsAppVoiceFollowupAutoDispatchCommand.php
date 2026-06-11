<?php

namespace Modules\LeadManagement\Console;

use Illuminate\Console\Command;
use Modules\LeadManagement\Services\VoiceCallTabCache;
use Modules\LeadManagement\Services\WhatsAppFollowupCandidateQueryService;
use Modules\LeadManagement\Services\WhatsAppVoiceFollowupAutomationRunner;

class WhatsAppVoiceFollowupAutoDispatchCommand extends Command
{
    protected $signature = 'voice:whatsapp-followup-auto-dispatch
                            {--rule= : Run only this automation rule ID}
                            {--force : Ignore interval and run enabled rules now}';

    protected $description = 'Run WhatsApp voice follow-up automation rules and dispatch OmniDimension calls';

    public function handle(
        WhatsAppVoiceFollowupAutomationRunner $runner,
        VoiceCallTabCache $tabCache
    ): int {
        $ruleId = $this->option('rule') !== null ? (int) $this->option('rule') : null;
        $force = (bool) $this->option('force');

        $stats = $runner->runDueRules($force, $ruleId > 0 ? $ruleId : null);

        if ($stats['processed'] > 0) {
            WhatsAppFollowupCandidateQueryService::clearSearchCache();
            WhatsAppFollowupCandidateQueryService::clearOtherCronPhonesCache();
            $tabCache->forget(VoiceCallTabCache::TAB_VOICE_CRON);
        }

        $this->info(sprintf(
            'Processed %d rule(s): %d dispatched, %d skipped (empty), %d failed.',
            $stats['processed'],
            $stats['dispatched'],
            $stats['skipped'],
            $stats['failed']
        ));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
