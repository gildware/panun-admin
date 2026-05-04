<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\CategoryManagement\Entities\Category;
use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Entities\Variation;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Customer-safe catalog + business snippets for the AI tool layer (no payments, revenue, or PII of others).
 */
class WhatsAppPublicCatalogService
{
    public function __construct(
        protected WhatsAppAiRuntimeResolver $runtimeResolver
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildPublicSnapshot(): array
    {
        $services = $this->safeActiveServiceNames();
        $zones = $this->safeActiveZoneNames();
        $zonesWithIds = $this->safeActiveZonesWithIds();

        $coverageNote = config('whatsapp_ai_support.service_coverage_policy_note');
        $coverageNote = is_string($coverageNote) ? trim($coverageNote) : '';

        $out = [
            'company' => $this->scalarBusinessValue('company_name', 'business_information')
                ?? $this->scalarBusinessValue('business_name', 'business_information'),
            'phone' => $this->runtimeResolver->supportPhoneDisplay()
                ?: $this->scalarBusinessValue('phone', 'business_information'),
            'email' => $this->scalarBusinessValue('email', 'business_information'),
            'address' => $this->scalarBusinessValue('address', 'business_information'),
            'visiting_charge_note' => $this->resolveVisitingChargeNote(),
            'service_coverage_policy_note' => $coverageNote !== '' ? $coverageNote : null,
            'service_area_note' => $this->scalarBusinessValue('service_area', 'business_information'),
            'service_names_sample' => array_slice($services, 0, 40),
            'zone_names_sample' => array_slice($zones, 0, 30),
            'zones_for_ai' => $zonesWithIds,
            'zones_for_address_matching' => $this->zonesDetailForAddressMatching(),
            'disclaimer' => 'Final pricing depends on the job after inspection. Do not invent amounts not listed here.',
            'service_hints' => $this->buildServiceHintsForAi(),
            'zone_from_address_hint' => 'Use full address only — never ask the customer for region or district. After you have address text, call match_zone_from_address (or rely on upsert_my_draft_booking which auto-matches when confident). Zone descriptions list local areas; only set zone when match is high.',
        ];

        $extras = config('whatsapp_ai_support.extra_public_business_config', []);
        if (is_array($extras)) {
            foreach ($extras as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $k = trim((string) ($row['key'] ?? ''));
                $type = trim((string) ($row['settings_type'] ?? ''));
                $sk = trim((string) ($row['snapshot_key'] ?? ''));
                if ($k === '' || $type === '' || $sk === '') {
                    continue;
                }
                $val = $this->scalarBusinessValue($k, $type);
                if ($val !== null && $val !== '') {
                    $out[$sk] = $val;
                }
            }
        }

        return $out;
    }

    private function resolveVisitingChargeNote(): string
    {
        $fromDb = $this->scalarBusinessValue('visiting_charge', 'booking_setup')
            ?? $this->scalarBusinessValue('extra_charge', 'booking_setup');
        $trimmed = $fromDb !== null ? trim($fromDb) : '';
        if ($trimmed !== '') {
            return $trimmed;
        }

        $fallback = config('whatsapp_ai_support.default_visiting_charge_note');
        if (is_string($fallback)) {
            $t = trim($fallback);

            return $t !== '' ? $t : '';
        }

        return '';
    }

    private function scalarBusinessValue(string $key, string $type): ?string
    {
        try {
            $row = business_config($key, $type);
            if (!$row) {
                return null;
            }
            $v = $row->live_values ?? null;
            if (is_array($v)) {
                return json_encode($v, JSON_UNESCAPED_UNICODE) ?: null;
            }
            if (is_string($v) || is_numeric($v)) {
                return (string) $v;
            }
        } catch (\Throwable $e) {
            Log::debug('WhatsAppPublicCatalogService: business_config miss', ['key' => $key, 'type' => $type]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function safeActiveServiceNames(): array
    {
        try {
            if (!class_exists(\Modules\CategoryManagement\Entities\Category::class)) {
                return [];
            }

            return \Modules\CategoryManagement\Entities\Category::query()
                ->where('is_active', 1)
                ->where('position', 2)
                ->orderBy('name')
                ->limit(60)
                ->pluck('name')
                ->filter()
                ->map(fn ($n) => (string) $n)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppPublicCatalogService: categories', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<string>
     */
    /**
     * Compact category / service UUID hints so the model can prefill admin booking when the customer picks a known service.
     *
     * @return array<string, mixed>
     */
    private function buildServiceHintsForAi(): array
    {
        try {
            if (!class_exists(Category::class) || !class_exists(Service::class)) {
                return [];
            }

            $categories = Category::query()
                ->where('position', 1)
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(22)
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->values()
                ->all();

            $subcategories = Category::query()
                ->where('position', 2)
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(40)
                ->get(['id', 'name', 'parent_id'])
                ->map(fn ($c) => [
                    'id' => (string) $c->id,
                    'name' => (string) $c->name,
                    'parent_category_id' => (string) $c->parent_id,
                ])
                ->values()
                ->all();

            $services = Service::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(28)
                ->get(['id', 'name', 'category_id', 'sub_category_id', 'variation_pricing']);

            $servicesOut = [];
            foreach ($services as $s) {
                $vk = null;
                $vp = (array) ($s->variation_pricing ?? []);
                $keys = array_keys($vp);
                sort($keys);
                if ($keys !== []) {
                    $vk = (string) $keys[0];
                }
                if ($vk === null || $vk === '') {
                    $vk = Variation::query()->where('service_id', $s->id)->orderBy('variant_key')->value('variant_key');
                }
                if ($vk === null || $vk === '') {
                    continue;
                }
                $servicesOut[] = [
                    'service_id' => (string) $s->id,
                    'name' => (string) $s->name,
                    'category_id' => (string) $s->category_id,
                    'sub_category_id' => (string) $s->sub_category_id,
                    'variant_key' => (string) $vk,
                ];
            }

            return [
                'categories' => $categories,
                'subcategories' => $subcategories,
                'services_sample' => $servicesOut,
                'hint' => 'When the customer clearly matches a listed service, pass service_id, variant_key, category_id, sub_category_id, and zone_id (if known) into upsert_my_draft_booking so staff see prefilled admin data.',
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsAppPublicCatalogService: service_hints', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function safeActiveZoneNames(): array
    {
        try {
            return Zone::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(40)
                ->pluck('name')
                ->filter()
                ->map(fn ($n) => (string) $n)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppPublicCatalogService: zones', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function safeActiveZonesWithIds(): array
    {
        try {
            return Zone::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(35)
                ->get(['id', 'name'])
                ->map(fn ($z) => ['id' => (string) $z->id, 'name' => (string) $z->name])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppPublicCatalogService: zones_with_ids', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Full zone list with descriptions (area lists) for AI address → zone matching.
     *
     * @return list<array{id: string, name: string, description: string}>
     */
    private function zonesDetailForAddressMatching(): array
    {
        try {
            return Zone::query()
                ->where('is_active', 1)
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name', 'description'])
                ->map(function ($z) {
                    $desc = (string) ($z->description ?? '');
                    if ($desc !== '' && mb_strlen($desc) > 2000) {
                        $desc = mb_substr($desc, 0, 2000).'…';
                    }

                    return [
                        'id' => (string) $z->id,
                        'name' => (string) $z->name,
                        'description' => $desc,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppPublicCatalogService: zones_for_address_matching', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
