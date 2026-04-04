<?php

namespace Modules\WhatsAppModule\Services;

use Illuminate\Support\Facades\Log;
use Modules\ZoneManagement\Entities\Zone;

/**
 * Customer-safe catalog + business snippets for the AI tool layer (no payments, revenue, or PII of others).
 */
class WhatsAppPublicCatalogService
{
    /**
     * @return array<string, mixed>
     */
    public function buildPublicSnapshot(): array
    {
        $services = $this->safeActiveServiceNames();
        $zones = $this->safeActiveZoneNames();

        $out = [
            'company' => $this->scalarBusinessValue('company_name', 'business_information')
                ?? $this->scalarBusinessValue('business_name', 'business_information'),
            'phone' => config('whatsappmodule.support_phone_display')
                ?: $this->scalarBusinessValue('phone', 'business_information'),
            'email' => $this->scalarBusinessValue('email', 'business_information'),
            'address' => $this->scalarBusinessValue('address', 'business_information'),
            'visiting_charge_note' => $this->scalarBusinessValue('visiting_charge', 'booking_setup')
                ?? $this->scalarBusinessValue('extra_charge', 'booking_setup'),
            'service_area_note' => $this->scalarBusinessValue('service_area', 'business_information'),
            'service_names_sample' => array_slice($services, 0, 40),
            'zone_names_sample' => array_slice($zones, 0, 30),
            'disclaimer' => 'Final pricing depends on the job after inspection. Do not invent amounts not listed here.',
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
}
