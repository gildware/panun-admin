<?php

namespace Modules\BusinessSettingsModule\Services;

use Modules\UserManagement\Entities\User;

class MobileAppAiServiceDetailsService
{
    public function __construct(
        protected MobileAppAiCatalogSearchService $catalogSearch,
    ) {}

    public static function looksLikeServiceDetailsIntent(string $text): bool
    {
        $t = mb_strtolower(trim($text));

        return (bool) preg_match(
            '/\b(service\s+details?|tell\s+me\s+about|what\s+is\s+included|what\s+does\s+.+\s+include|price\s+of|how\s+much\s+for|details\s+for)\b/iu',
            $t
        );
    }

    /**
     * @return array{ok: bool, customer_message: string, ui?: array<string, mixed>}
     */
    public function describeService(User $user, string $query): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['ok' => false, 'customer_message' => 'Which service should I look up? For example: *AC repair* or *tap leak*.'];
        }

        $zoneId = $this->catalogSearch->resolveCustomerZoneId($user);
        $results = $this->catalogSearch->searchServices($q, 5, null, null, $user);
        $items = $results['items'] ?? [];
        if ($items === []) {
            return [
                'ok' => false,
                'customer_message' => 'I could not find **'.$q.'** in our catalog. Try another name or say **book a service**.',
            ];
        }

        $best = $items[0];
        $serviceId = (string) ($best['id'] ?? '');
        if ($serviceId === '') {
            return ['ok' => false, 'customer_message' => 'Could not load that service.'];
        }

        $zoneId = $this->catalogSearch->resolveCustomerZoneId($user);
        $details = $this->catalogSearch->getServiceDetails($serviceId, $zoneId);
        if (($details['ok'] ?? false) !== true) {
            return ['ok' => false, 'customer_message' => 'Service details unavailable in your area.'];
        }

        $svc = is_array($details['service'] ?? null) ? $details['service'] : [];
        $name = (string) ($svc['name'] ?? $best['name'] ?? 'Service');
        $category = (string) ($svc['category'] ?? '');
        $short = (string) ($svc['short_description'] ?? '');
        $lines = ["**{$name}**"];
        if ($category !== '') {
            $lines[] = 'Category: '.$category;
        }
        if ($short !== '') {
            $lines[] = $short;
        }

        $variants = $svc['variants'] ?? [];
        if (is_array($variants) && $variants !== []) {
            $lines[] = "\n**Pricing (your zone):**";
            foreach (array_slice($variants, 0, 6) as $v) {
                $label = (string) ($v['label'] ?? $v['variant_key'] ?? 'Standard');
                $price = $v['price_in_zone'] ?? null;
                if ($price !== null && (float) $price > 0) {
                    $lines[] = '• '.$label.' — **'.with_currency_symbol((float) $price).'**';
                }
            }
        }

        $lines[] = "\nSay **book ".$name.'** to add it to your cart, or **show my cart** for current charges.';

        return [
            'ok' => true,
            'customer_message' => implode("\n", $lines),
            'ui' => [
                'type' => 'service_details',
                'service_id' => $serviceId,
                'layout' => 'actions',
                'actions' => [
                    ['action' => 'start_booking', 'label' => 'Book this service', 'style' => 'primary', 'icon' => 'add'],
                    ['action' => 'open_cart', 'label' => 'Open cart', 'style' => 'outline', 'icon' => 'shopping_cart'],
                ],
            ],
        ];
    }
}
