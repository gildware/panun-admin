<?php

namespace Modules\ServiceManagement\Support;

class ServiceOverviewIconPresets
{
  /**
   * @return list<array{key: string, label: string}>
   */
  public static function options(): array
  {
    return [
      ['key' => 'verified', 'label' => 'Verified'],
      ['key' => 'home', 'label' => 'At Home'],
      ['key' => 'sparkle', 'label' => 'Clean Work'],
      ['key' => 'warranty', 'label' => 'Warranty'],
      ['key' => 'calendar', 'label' => 'Calendar'],
      ['key' => 'location', 'label' => 'Location'],
      ['key' => 'tools', 'label' => 'Tools'],
      ['key' => 'check', 'label' => 'Check'],
      ['key' => 'door', 'label' => 'Door'],
      ['key' => 'building', 'label' => 'Building'],
      ['key' => 'shop', 'label' => 'Shop'],
      ['key' => 'wood', 'label' => 'Wood'],
      ['key' => 'quality', 'label' => 'Quality'],
      ['key' => 'pricing', 'label' => 'Pricing'],
      ['key' => 'support', 'label' => 'Support'],
    ];
  }

  /**
   * @return list<string>
   */
  public static function keys(): array
  {
    return array_column(self::options(), 'key');
  }
}
