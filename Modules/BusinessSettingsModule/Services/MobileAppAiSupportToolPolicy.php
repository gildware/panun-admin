<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Mobile in-app AI is support-only: troubleshoot, explain, look up — never CRM leads or new bookings.
 */
class MobileAppAiSupportToolPolicy
{
    /** @var list<string> */
    public const ALLOWED_TOOLS = [
        'get_public_business_info',
        'search_support_knowledge',
        'match_zone_from_address',
        'list_my_system_bookings',
        'get_booking_status_by_reference',
        'get_my_booking_details',
        'list_my_booking_summaries',
        'report_unclear_user_intent',
        'request_human_support_handoff',
    ];

    /**
     * @param  list<array<string, mixed>>  $declarations
     * @return list<array<string, mixed>>
     */
    public function filterDeclarations(array $declarations): array
    {
        $allowed = array_flip(self::ALLOWED_TOOLS);

        return array_values(array_filter(
            $declarations,
            static fn (array $decl): bool => isset($allowed[(string) ($decl['name'] ?? '')])
        ));
    }

    public function isAllowed(string $name): bool
    {
        return in_array($name, self::ALLOWED_TOOLS, true);
    }
}
