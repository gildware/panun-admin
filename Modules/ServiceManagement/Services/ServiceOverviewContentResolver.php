<?php

namespace Modules\ServiceManagement\Services;

use Modules\ServiceManagement\Entities\Service;
use Modules\ServiceManagement\Support\ServiceOverviewIconPresets;

class ServiceOverviewContentResolver
{
  /**
   * @return array<string, mixed>|null
   */
  public static function resolveForService(Service $service): ?array
  {
    $raw = $service->getAttributes()['overview_content'] ?? null;
    if (is_string($raw)) {
      $raw = json_decode($raw, true);
    }

    $serviceContent = is_array($raw) ? self::normalizeServiceContent($raw) : [];
    $defaults = ServiceOverviewDefaultsService::get();

    $hasServiceSections = self::hasAnyServiceSection($serviceContent);
    $overrideTopIcons = (bool) ($serviceContent['override_top_icons'] ?? false);
    $overrideWhyChoose = (bool) ($serviceContent['override_why_choose'] ?? false);

    $topIcons = $overrideTopIcons
      ? ($serviceContent['top_icons'] ?? [])
      : ($defaults['top_icons'] ?? []);

    $cardHighlights = self::resolveCardHighlights($serviceContent);

    $whyChoose = $overrideWhyChoose
      ? ($serviceContent['why_choose'] ?? ['title' => '', 'items' => []])
      : ($defaults['why_choose'] ?? ['title' => '', 'items' => []]);

    if (! $hasServiceSections && $topIcons === [] && $cardHighlights === [] && ($whyChoose['items'] ?? []) === []) {
      return null;
    }

    return [
      'intro' => $serviceContent['intro'] ?? null,
      'top_icons' => $topIcons,
      'card_highlights' => $cardHighlights,
      'service_process' => $serviceContent['service_process'] ?? null,
      'perfect_for' => $serviceContent['perfect_for'] ?? null,
      'whats_included' => $serviceContent['whats_included'] ?? null,
      'whats_not_included' => $serviceContent['whats_not_included'] ?? null,
      'good_to_know' => $serviceContent['good_to_know'] ?? null,
      'why_choose' => $whyChoose,
    ];
  }

  /**
   * @param  array<string, mixed>  $payload
   * @return array<string, mixed>
   */
  public static function normalizeServiceContent(array $payload): array
  {
    return [
      'intro' => trim((string) ($payload['intro'] ?? '')),
      'override_top_icons' => (bool) ($payload['override_top_icons'] ?? false),
      'override_why_choose' => (bool) ($payload['override_why_choose'] ?? false),
      'top_icons' => self::normalizeItems($payload['top_icons'] ?? []),
      'card_highlights' => self::normalizeItems($payload['card_highlights'] ?? []),
      'why_choose' => [
        'title' => trim((string) ($payload['why_choose']['title'] ?? '')),
        'items' => self::normalizeItems($payload['why_choose']['items'] ?? []),
      ],
      'service_process' => self::normalizeSection($payload['service_process'] ?? null, 'Service Process'),
      'perfect_for' => self::normalizeSection($payload['perfect_for'] ?? null, 'Perfect For'),
      'whats_included' => self::normalizeSection($payload['whats_included'] ?? null, "What's Included"),
      'whats_not_included' => self::normalizeSection($payload['whats_not_included'] ?? null, 'Not Included'),
      'good_to_know' => self::normalizeSection($payload['good_to_know'] ?? null, 'Good To Know'),
    ];
  }

  /**
   * @param  mixed  $section
   * @return array{title: string, items: list<array<string, mixed>>}|null
   */
  private static function normalizeSection(mixed $section, string $defaultTitle): ?array
  {
    if (! is_array($section)) {
      return null;
    }

    $items = self::normalizeItems($section['items'] ?? []);
    if ($items === []) {
      return null;
    }

    return [
      'title' => trim((string) ($section['title'] ?? $defaultTitle)),
      'items' => $items,
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

      $icon = isset($item['icon']) ? trim((string) $item['icon']) : '';
      if ($icon !== '' && ! in_array($icon, ServiceOverviewIconPresets::keys(), true)) {
        $icon = '';
      }

      $row = array_filter([
        'icon' => $icon !== '' ? $icon : null,
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

  /**
   * Service-card highlights are per-service only (not global defaults).
   *
   * @param  array<string, mixed>  $serviceContent
   * @return list<array<string, mixed>>
   */
  private static function resolveCardHighlights(array $serviceContent): array
  {
    $cardHighlights = self::normalizeItems($serviceContent['card_highlights'] ?? []);
    if ($cardHighlights !== []) {
      return $cardHighlights;
    }

    // Backward compatibility: basic-form highlights were previously stored in top_icons.
    if ((bool) ($serviceContent['override_top_icons'] ?? false)) {
      return self::normalizeItems($serviceContent['top_icons'] ?? []);
    }

    return [];
  }

  /**
   * @param  array<string, mixed>  $content
   */
  private static function hasAnyServiceSection(array $content): bool
  {
    foreach (['service_process', 'perfect_for', 'whats_included', 'whats_not_included', 'good_to_know'] as $key) {
      $section = $content[$key] ?? null;
      if (is_array($section) && ! empty($section['items'])) {
        return true;
      }
    }

    return trim((string) ($content['intro'] ?? '')) !== '';
  }
}
