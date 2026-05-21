<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiContactProfileResolver;
use Modules\WhatsAppModule\Services\WhatsAppAiRuntimeResolver;

/**
 * Trusted per-user context for mobile in-app AI (no WhatsApp drafts, leads, or booking flows).
 */
class MobileAppAiSessionContextService
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $runtimeResolver,
        protected WhatsAppAiContactProfileResolver $contactProfile,
    ) {}

    public function runtimeAppendixForUser(User $user): string
    {
        $phone = trim((string) $user->phone);
        $tz = $this->runtimeResolver->supportTimezone();
        $now = Carbon::now($tz);

        $lines = [
            'Server clock (authoritative for "today" and scheduling questions): '
                .$now->format('l, j F Y').', '.$now->format('h:i A').' '.$tz,
            'Channel: customer **mobile app** AI chat (logged-in account).',
            'Scope: troubleshooting, how-to, policies, and **read-only** booking lookups for this account. '
                .'Do **not** create CRM leads, WhatsApp booking requests, or provider registration leads. '
                .'To place a new service request, direct the customer to the app booking flow or Help & Support.',
        ];

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        $bits = [];
        if ($name !== '') {
            $bits[] = 'account_name: '.$name;
        }
        if (trim((string) $user->email) !== '') {
            $bits[] = 'account_email: '.$user->email;
        }
        if ($phone !== '') {
            $bits[] = 'account_phone: '.$phone;
        }
        if ($bits !== []) {
            $lines[] = 'Logged-in profile: '.implode('; ', $bits).'.';
        }

        if ($phone !== '') {
            $known = $this->contactProfile->snapshot($phone);
            if ($known['lines_for_prompt'] !== []) {
                $lines[] = '**Known contact profile (merged app data for this phone):**';
                foreach ($known['lines_for_prompt'] as $ln) {
                    $lines[] = $ln;
                }
            }
        }

        return "### Current session context (trusted; this logged-in customer only)\n"
            .implode("\n", $lines);
    }
}
