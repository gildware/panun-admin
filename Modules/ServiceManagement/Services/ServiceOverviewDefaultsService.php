<?php

namespace Modules\ServiceManagement\Services;

use Modules\BusinessSettingsModule\Entities\DataSetting;
use Modules\BusinessSettingsModule\Services\BusinessConfigCache;

class ServiceOverviewDefaultsService
{
  public const TYPE = 'service_overview_defaults';

  public const KEY = 'defaults';

  /**
   * @return array<string, mixed>
   */
  public static function defaults(): array
  {
    return [
      'top_icons' => [
        [
          'icon' => 'verified',
          'text' => 'Verified Experts',
          'color' => 'green',
          'sort_order' => 0,
        ],
        [
          'icon' => 'home',
          'text' => 'At Your Home',
          'color' => 'blue',
          'sort_order' => 1,
        ],
        [
          'icon' => 'sparkle',
          'text' => 'Clean Work',
          'color' => 'purple',
          'sort_order' => 2,
        ],
        [
          'icon' => 'warranty',
          'text' => 'Warranty Service',
          'color' => 'orange',
          'sort_order' => 3,
        ],
      ],
      'why_choose' => [
        'title' => 'Why Choose Panun Kaergar',
        'items' => [
          [
            'icon' => 'verified',
            'title' => 'Skilled Experts',
            'description' => 'Experienced & background verified',
            'color' => 'green',
            'sort_order' => 0,
          ],
          [
            'icon' => 'quality',
            'title' => 'Quality Assured',
            'description' => 'Best tools & practices',
            'color' => 'blue',
            'sort_order' => 1,
          ],
          [
            'icon' => 'pricing',
            'title' => 'Transparent Pricing',
            'description' => 'No hidden costs, what you see is what you pay',
            'color' => 'purple',
            'sort_order' => 2,
          ],
          [
            'icon' => 'support',
            'title' => 'Customer First',
            'description' => 'On-time service & complete support',
            'color' => 'orange',
            'sort_order' => 3,
          ],
        ],
      ],
      'terms_and_conditions' => [
        'title' => 'Terms And Conditions',
        'items' => [],
      ],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public static function get(): array
  {
    $row = BusinessConfigCache::dataConfig(self::KEY, self::TYPE);
    if (! $row instanceof DataSetting || blank($row->value)) {
      return self::defaults();
    }

    $decoded = json_decode((string) $row->value, true);
    if (! is_array($decoded)) {
      return self::defaults();
    }

    return self::normalizeDefaults($decoded);
  }

  /**
   * @param  array<string, mixed>  $payload
   * @return array<string, mixed>
   */
  public static function save(array $payload): array
  {
    $normalized = self::normalizeDefaults($payload);

    DataSetting::query()->updateOrCreate(
      ['type' => self::TYPE, 'key' => self::KEY],
      ['value' => json_encode($normalized), 'is_active' => 1]
    );

    BusinessConfigCache::forgetAll();

    return $normalized;
  }

  /**
   * @param  array<string, mixed>  $payload
   * @return array<string, mixed>
   */
  public static function normalizeDefaults(array $payload): array
  {
    $base = self::defaults();

    return [
      'top_icons' => self::normalizeItems($payload['top_icons'] ?? $base['top_icons']),
      'why_choose' => [
        'title' => trim((string) ($payload['why_choose']['title'] ?? $base['why_choose']['title'])),
        'items' => self::normalizeItems($payload['why_choose']['items'] ?? $base['why_choose']['items']),
      ],
      'terms_and_conditions' => [
        'title' => trim((string) ($payload['terms_and_conditions']['title'] ?? $base['terms_and_conditions']['title'])),
        'items' => self::normalizeItems($payload['terms_and_conditions']['items'] ?? $base['terms_and_conditions']['items']),
      ],
    ];
  }

  /**
   * @param  mixed  $items
   * @return list<array<string, mixed>>
   */
  private static function normalizeItems(mixed $items): array
  {
    if (! is_array($items)) {
      return [];
    }

    $normalized = [];
    foreach (array_values($items) as $index => $item) {
      if (! is_array($item)) {
        continue;
      }

      $row = array_filter([
        'icon' => isset($item['icon']) ? trim((string) $item['icon']) : null,
        'icon_image' => isset($item['icon_image']) ? trim((string) $item['icon_image']) : null,
        'text' => isset($item['text']) ? trim((string) $item['text']) : null,
        'title' => isset($item['title']) ? trim((string) $item['title']) : null,
        'description' => isset($item['description']) ? trim((string) $item['description']) : null,
        'image' => isset($item['image']) ? trim((string) $item['image']) : null,
        'color' => isset($item['color']) ? trim((string) $item['color']) : null,
        'sort_order' => isset($item['sort_order']) ? (int) $item['sort_order'] : $index,
      ], fn ($value) => $value !== null && $value !== '');

      if ($row !== []) {
        $normalized[] = $row;
      }
    }

    usort($normalized, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

    return $normalized;
  }
}
