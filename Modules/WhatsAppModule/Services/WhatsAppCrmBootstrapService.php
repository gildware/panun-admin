<?php

namespace Modules\WhatsAppModule\Services;

use Modules\WhatsAppModule\Entities\WhatsAppUser;

/**
 * Ensures WhatsAppUser + unknown CRM Lead exist for inbound chat traffic.
 */
class WhatsAppCrmBootstrapService
{
    public function __construct(
        protected WhatsAppLeadLifecycleService $leadLifecycle
    ) {}

    public function bootstrapInboundThread(string $phone): void
    {
        $waUser = WhatsAppUser::where('phone', $phone)->first();
        if (!$waUser) {
            $waUser = WhatsAppUser::create([
                'phone' => $phone,
                'name' => null,
                'handled_by' => 'AI',
            ]);
        } elseif (empty($waUser->handled_by)) {
            $waUser->handled_by = 'AI';
            $waUser->save();
        }

        $this->leadLifecycle->ensureUnknownLeadForPhone($phone, $waUser->name ?? null);
    }
}
