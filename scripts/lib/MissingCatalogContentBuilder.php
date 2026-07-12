<?php

require_once __DIR__.'/RemainingServiceContentBuilder.php';

class MissingCatalogContentBuilder
{
    public static function build(array $service): array
    {
        $role = self::detectRole($service);
        $payload = array_merge($service, [
            'category' => self::categoryKey($service['category_slug'] ?? ''),
            'role' => $role,
        ]);

        return RemainingServiceContentBuilder::build($payload);
    }

    public static function detectRole(array $service): string
    {
        $slug = (string) ($service['slug'] ?? '');
        if (in_array($slug, ['book-a-carpenter', 'book-a-painter', 'book-a-mason'], true)) {
            return 'hourly-booking';
        }

        $sub = (string) ($service['sub_slug'] ?? '');
        $name = strtolower((string) ($service['name'] ?? ''));
        $cat = (string) ($service['category_slug'] ?? '');

        if (str_contains($cat, 'salon')) {
            return 'salon';
        }

        if ($cat === 'cleaning') {
            return 'cleaning';
        }

        if ($cat === 'laundry') {
            return 'laundry';
        }

        if ($cat === 'home-appliance') {
            if (preg_match('/\b(repair|servic|check|fix|fault|leak|block|uninstall|removal)\b/', $name)) {
                return 'repair';
            }

            return 'install';
        }

        return match ($sub) {
            'carpentry-installation', 'plumbing-installs', 'installation-services' => 'install',
            'carpentry-repairs', 'plumbing-fixtures', 'repairing-services' => 'repair',
            default => str_contains($sub, 'install') ? 'install' : 'repair',
        };
    }

    private static function categoryKey(string $categorySlug): string
    {
        return match ($categorySlug) {
            'electrical' => 'electrical',
            'plumbing' => 'plumbing',
            'cleaning' => 'cleaning',
            'laundry' => 'laundry',
            'mens-salon', 'womens-salon' => 'salon',
            default => 'general',
        };
    }
}
