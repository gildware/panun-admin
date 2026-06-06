<?php

namespace Modules\BusinessSettingsModule\Services;

/**
 * Tools allowed for customer mobile in-app AI.
 */
class MobileAppAiSupportToolPolicy
{
    /** @var list<string> */
    public const ALLOWED_TOOLS = [
        'get_public_business_info',
        'list_service_areas',
        'list_service_categories',
        'list_full_service_catalog',
        'search_catalog_services',
        'manage_app_booking',
        'match_zone_from_address',
        'search_support_knowledge',
        'get_customer_cart_summary',
        'get_customer_account_snapshot',
        'manage_customer_cart',
        'list_my_saved_addresses',
        'list_my_system_bookings',
        'get_booking_status_by_reference',
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

    /**
     * @param  list<array<string, mixed>>  $declarations
     * @return list<array<string, mixed>>
     */
    public function filterDeclarationsForDomain(array $declarations, string $domain): array
    {
        $allowed = array_flip(MobileAppAiIntentDomainCatalog::toolsForDomain($domain));

        return array_values(array_filter(
            $declarations,
            static fn (array $decl): bool => isset($allowed[(string) ($decl['name'] ?? '')])
        ));
    }
}
