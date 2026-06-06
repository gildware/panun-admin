<?php

namespace Modules\BusinessSettingsModule\Services;

use Carbon\Carbon;
use Modules\CartModule\Entities\CartServiceInfo;
use Modules\ProviderManagement\Entities\Provider;
use Modules\WhatsAppModule\Services\WhatsAppAiPromptBuilder;

/**
 * Booking wizard rules aligned with the customer app (variations → schedule → address → provider → cart).
 */
class MobileAppAiBookingFlowService
{
    /**
     * @return array<string, mixed>
     */
    public function wizardRequirements(): array
    {
        $brand = WhatsAppAiPromptBuilder::resolveBrandName();
        $instant = (int) (business_config('instant_booking', 'booking_setup')?->live_values ?? 0);
        $schedule = (int) (business_config('schedule_booking', 'booking_setup')?->live_values ?? 0);

        return [
            'ok' => true,
            'brand_name' => $brand,
            'instant_booking_enabled' => $instant === 1,
            'schedule_booking_enabled' => $schedule === 1,
            'wizard_steps' => [
                ['step' => 1, 'id' => 'service', 'title' => 'Choose service', 'tools' => ['search_catalog_services', 'list_full_service_catalog', 'list_service_categories']],
                ['step' => 2, 'id' => 'variation', 'title' => 'Choose variation (size/type)', 'tools' => ['list_service_variations_for_booking', 'get_catalog_service_details']],
                ['step' => 3, 'id' => 'schedule', 'title' => 'Date & time', 'fields' => ['service_schedule or schedule_type=asap']],
                ['step' => 4, 'id' => 'address', 'title' => 'Service address', 'tools' => ['list_customer_saved_addresses', 'match_zone_from_address']],
                ['step' => 5, 'id' => 'provider', 'title' => 'Provider', 'tools' => ['list_booking_providers'], 'note' => 'Customer may pick a provider or let '.$brand.' choose (provider_id omitted)'],
                ['step' => 6, 'id' => 'cart', 'title' => 'Add to cart', 'tools' => ['add_service_to_customer_cart']],
            ],
            'required_for_add_service_to_customer_cart' => [
                'service_id', 'variant_key', 'category_id', 'sub_category_id', 'zone_id',
                'service_address_id', 'service_schedule',
            ],
            'optional_for_add_service_to_customer_cart' => ['provider_id (omit or null = let '.$brand.' choose)', 'quantity'],
            'schedule_rules' => [
                'asap' => 'Customer wants ASAP → pass schedule_type=asap (server sets visit time to now+2 minutes, same as app)',
                'custom' => 'Pass service_schedule as Y-m-d H:i:s at least 2 hours from now (same minimum as app booking picker)',
            ],
            'new_address_rule' => 'If no saved address fits: tell customer to add one in the app (Home → location → Add new address), then call list_customer_saved_addresses again. Do not call add_service_to_customer_cart without service_address_id.',
            'assistant_instruction' => 'Follow wizard_steps in order. Present numbered selectable_options from tools. Ask ONE step at a time. Only call add_service_to_customer_cart when every required field is collected.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listVariationsForBooking(string $serviceId, ?string $zoneId = null): array
    {
        $serviceId = trim($serviceId);
        if ($serviceId === '') {
            return ['ok' => false, 'error' => 'missing_service_id'];
        }

        $details = app(MobileAppAiCatalogSearchService::class)->getServiceDetails($serviceId, $zoneId);
        if (! ($details['ok'] ?? false)) {
            return $details;
        }

        $service = $details['service'];
        $variants = $service['variants'] ?? [];
        $options = [];
        $n = 1;
        foreach ($variants as $v) {
            $label = (string) ($v['label'] ?? $v['variant_key'] ?? '');
            $price = $v['price_in_zone'] ?? null;
            $priceNote = $price !== null && (float) $price > 0 ? ' — from '.number_format((float) $price, 2) : '';
            $options[] = [
                'option' => $n,
                'variant_key' => (string) ($v['variant_key'] ?? ''),
                'label' => $label,
                'price_in_zone' => $price,
                'bookable_in_zone' => $v['bookable_in_zone'] ?? null,
                'display' => $n.'. '.$label.$priceNote,
            ];
            $n++;
        }

        return [
            'ok' => true,
            'service_id' => $serviceId,
            'service_name' => $service['name'] ?? '',
            'category_id' => $service['category_id'] ?? '',
            'sub_category_id' => $service['sub_category_id'] ?? '',
            'selectable_options' => $options,
            'assistant_instruction' => count($options) === 0
                ? 'No bookable variation — try another service or zone.'
                : (count($options) === 1
                    ? 'Only one variation — confirm with customer then proceed to schedule step.'
                    : 'Show selectable_options as a numbered list. Customer replies with option number or variant name, then continue to date/time.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function listBookingProviders(array $args): array
    {
        $subCategoryId = trim((string) ($args['sub_category_id'] ?? ''));
        $zoneId = trim((string) ($args['zone_id'] ?? ''));
        $schedule = trim((string) ($args['service_schedule'] ?? ''));

        if ($subCategoryId === '' || $zoneId === '') {
            return [
                'ok' => false,
                'error' => 'missing_sub_category_id_or_zone_id',
                'hint' => 'Collect address (zone_id) and service (sub_category_id) before listing providers.',
            ];
        }

        $providers = Provider::query()
            ->with(['owner'])
            ->coveringLeafZone($zoneId)
            ->whereHas('subscribed_services', function ($query) use ($subCategoryId) {
                $query->where('sub_category_id', $subCategoryId)->where('is_subscribed', 1);
            })
            ->where('app_availability', 1)
            ->where('service_availability', 1)
            ->where('is_suspended', 0)
            ->where('is_active', 1)
            ->get();

        $brand = WhatsAppAiPromptBuilder::resolveBrandName();
        $eligible = [];
        foreach ($providers as $provider) {
            if (! nextBookingEligibility($provider->id)) {
                continue;
            }
            if ($schedule !== '' && ! $this->isProviderAvailableAtSchedule($provider, $schedule)) {
                continue;
            }

            $name = trim((string) ($provider->company_name ?? $provider->company_email ?? 'Provider'));
            $eligible[] = [
                'provider_id' => (string) $provider->id,
                'name' => $name,
                'avg_rating' => (float) ($provider->avg_rating ?? 0),
                'rating_count' => (int) ($provider->rating_count ?? 0),
            ];
        }

        usort($eligible, static fn ($a, $b) => ($b['avg_rating'] <=> $a['avg_rating']));

        $options = [
            [
                'option' => 0,
                'provider_id' => null,
                'name' => 'Let '.$brand.' choose for you',
                'display' => '0. Let '.$brand.' choose for you (recommended if unsure)',
            ],
        ];
        $n = 1;
        foreach ($eligible as $p) {
            $options[] = [
                'option' => $n,
                'provider_id' => $p['provider_id'],
                'name' => $p['name'],
                'avg_rating' => $p['avg_rating'],
                'display' => $n.'. '.$p['name'].' (★ '.number_format($p['avg_rating'], 1).')',
            ];
            $n++;
        }

        return [
            'ok' => true,
            'provider_count' => count($eligible),
            'selectable_options' => $options,
            'assistant_instruction' => 'Show every selectable_option. If customer picks 0 or says "'.$brand.' choose", omit provider_id in add_service_to_customer_cart. If they pick a number, pass that provider_id.',
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, schedule?: string, error?: string, missing_steps?: list<string>}
     */
    public function resolveSchedule(array $args): array
    {
        $scheduleType = strtolower(trim((string) ($args['schedule_type'] ?? '')));
        $raw = trim((string) ($args['service_schedule'] ?? $args['when'] ?? $args['datetime'] ?? ''));
        $label = mb_strtolower(trim((string) ($args['schedule_label'] ?? '')));

        if ($scheduleType === '' && ($label !== '' && (str_contains($label, 'asap') || str_contains($label, 'earliest')))) {
            $scheduleType = 'asap';
        }

        if ($scheduleType === 'asap') {
            $dt = Carbon::now()->addMinutes(2);

            return ['ok' => true, 'schedule' => $dt->format('Y-m-d H:i:s'), 'schedule_type' => 'asap'];
        }

        if ($raw === '') {
            return ['ok' => false, 'error' => 'missing_schedule', 'missing_steps' => ['step 3: Ask date and time (ASAP or Y-m-d H:i:s at least 2 hours from now)']];
        }

        if ($scheduleType !== 'custom' && MobileAppAiSchedulePhraseParser::looksLikeSchedulePhrase($raw)) {
            $parsed = MobileAppAiSchedulePhraseParser::parse($raw);
            if ($parsed['ok'] ?? false) {
                return [
                    'ok' => true,
                    'schedule' => $parsed['schedule'],
                    'schedule_type' => $parsed['schedule_type'] ?? 'custom',
                ];
            }
        }

        try {
            $dt = Carbon::parse($raw);
        } catch (\Throwable) {
            $parsed = MobileAppAiSchedulePhraseParser::parse($raw);
            if ($parsed['ok'] ?? false) {
                return [
                    'ok' => true,
                    'schedule' => $parsed['schedule'],
                    'schedule_type' => $parsed['schedule_type'] ?? 'custom',
                ];
            }

            return ['ok' => false, 'error' => 'invalid_schedule_format', 'missing_steps' => ['step 3: Use format Y-m-d H:i:s e.g. 2026-05-22 14:00']];
        }

        if ($dt->lt(Carbon::now()->addHours(2))) {
            if ($scheduleType === 'custom' && $dt->isFuture()) {
                return ['ok' => true, 'schedule' => $dt->format('Y-m-d H:i:s'), 'schedule_type' => 'custom'];
            }

            // Stale ASAP slot (saved as now+2 min) — refresh instead of failing checkout.
            if ($scheduleType === 'asap' || $dt->lte(Carbon::now()->addMinutes(30))) {
                $refreshed = Carbon::now()->addMinutes(2);

                return [
                    'ok' => true,
                    'schedule' => $refreshed->format('Y-m-d H:i:s'),
                    'schedule_type' => 'asap',
                ];
            }

            return [
                'ok' => false,
                'error' => 'schedule_too_soon',
                'missing_steps' => ['step 3: Custom visits must be at least 2 hours from now, or use schedule_type=asap'],
            ];
        }

        return ['ok' => true, 'schedule' => $dt->format('Y-m-d H:i:s'), 'schedule_type' => 'custom'];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function validateCartPayload(array $args): array
    {
        $missing = [];
        $stepHints = [];

        foreach (['service_id' => 'step 1: service', 'variant_key' => 'step 2: variation', 'category_id' => 'step 1', 'sub_category_id' => 'step 1', 'zone_id' => 'step 4: address/zone'] as $key => $hint) {
            if (trim((string) ($args[$key] ?? '')) === '') {
                $missing[] = $hint.' ('.$key.')';
            }
        }

        if (! isset($args['service_address_id']) || (int) $args['service_address_id'] <= 0) {
            $missing[] = 'step 4: service_address_id (list_customer_saved_addresses or new address in app first)';
        }

        $scheduleArgs = $args;
        if (! empty($args['schedule_type'])) {
            $scheduleArgs['schedule_type'] = $args['schedule_type'];
        }

        $scheduleResult = $this->resolveSchedule($scheduleArgs);
        if (! ($scheduleResult['ok'] ?? false)) {
            $missing = array_merge($missing, $scheduleResult['missing_steps'] ?? ['step 3: date and time']);
        }

        if ($missing !== []) {
            return [
                'ok' => false,
                'error' => 'booking_incomplete',
                'missing_steps' => $missing,
                'wizard' => $this->wizardRequirements()['wizard_steps'],
                'assistant_instruction' => 'Do NOT add to cart yet. Complete missing steps one at a time, then retry add_service_to_customer_cart.',
            ];
        }

        return [
            'ok' => true,
            'normalized_schedule' => $scheduleResult['schedule'] ?? null,
            'schedule_type' => $scheduleResult['schedule_type'] ?? null,
        ];
    }

    public function persistCartServiceInfo(string $customerId, string $zoneId, int $addressId, string $schedule): void
    {
        CartServiceInfo::query()->updateOrCreate(
            ['customer_id' => $customerId],
            [
                'zone_id' => $zoneId,
                'service_address_id' => $addressId,
                'service_schedule' => $schedule,
            ]
        );
    }

    private function isProviderAvailableAtSchedule(Provider $provider, string $schedule): bool
    {
        try {
            $dt = Carbon::parse($schedule);
        } catch (\Throwable) {
            return true;
        }

        $weekEnds = provider_config('weekends', 'service_schedule', $provider->id)?->live_values ?? '';
        $weekends = is_string($weekEnds) ? json_decode($weekEnds, true) : (array) $weekEnds;
        $day = strtolower($dt->format('l'));
        if (is_array($weekends) && in_array($day, array_map('strtolower', $weekends), true)) {
            return false;
        }

        $timeSchedule = provider_config('time_schedule', 'service_schedule', $provider->id)?->live_values ?? '';
        $scheduleConfig = is_string($timeSchedule) ? json_decode($timeSchedule, true) : (array) $timeSchedule;
        if (! is_array($scheduleConfig) || empty($scheduleConfig['start_time']) || empty($scheduleConfig['end_time'])) {
            return scheduleBookingEligibility($provider->id);
        }

        $time = $dt->format('H:i:s');
        $start = (string) $scheduleConfig['start_time'];
        $end = (string) $scheduleConfig['end_time'];

        return $time >= $start && $time <= $end && scheduleBookingEligibility($provider->id);
    }
}
