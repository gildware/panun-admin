<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\BusinessSettingsModule\Entities\MobileAppAiConversation;
use Modules\UserManagement\Entities\User;
use Modules\WhatsAppModule\Services\WhatsAppAiContactProfileResolver;
use Modules\WhatsAppModule\Services\WhatsAppAiRuntimeResolver;

/**
 * Trusted per-user context for mobile in-app AI.
 */
class MobileAppAiSessionContextService
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $runtimeResolver,
        protected WhatsAppAiContactProfileResolver $contactProfile,
        protected MobileAppAiCatalogSearchService $catalogSearch,
        protected MobileAppAiCustomerSnapshotService $customerSnapshot,
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
            'You act as the customer\'s in-app agent: use their live cart and bookings below; call tools to change cart, book, or check status on their behalf.',
        ];

        $stats = $this->catalogSearch->catalogStatsSnapshot();
        $lines[] = 'App catalog (live): '.$stats['active_service_count'].' active bookable services across '
            .$stats['active_zone_count'].' service zones.';
        if (($stats['category_summaries'] ?? []) !== []) {
            $lines[] = 'Categories (sample): '.implode('; ', array_slice($stats['category_summaries'], 0, 8)).'.';
        }

        $conversation = MobileAppAiConversation::query()->where('user_id', $user->id)->first();
        if ($conversation && is_array($conversation->booking_draft)) {
            $step = (string) ($conversation->booking_draft['step'] ?? 'idle');
            if ($step !== '' && $step !== 'idle' && $step !== 'done') {
                $lines[] = '**Booking wizard in progress** — step: '.$step.'. Continue with manage_app_booking (pick/time/confirm), not a new start unless they ask to cancel. Do not switch to cart tools unless they explicitly mention cart.';
            }
        }

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
                $lines[] = '**Known contact profile (merged):**';
                foreach (array_slice($known['lines_for_prompt'], 0, 6) as $ln) {
                    $lines[] = $ln;
                }
            }
        }

        $lines[] = '';
        $lines[] = $this->customerSnapshot->promptBlockForUser($user);

        return "### Current session context (trusted; this logged-in customer only)\n"
            .implode("\n", $lines);
    }
}
