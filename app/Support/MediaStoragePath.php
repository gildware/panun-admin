<?php

namespace App\Support;

use Illuminate\Support\Str;
use Modules\CategoryManagement\Entities\Category;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ServiceManagement\Entities\Service;
use Modules\UserManagement\Entities\User;

/**
 * Structured object-storage paths for R2 / local public disk.
 *
 * Examples:
 *   category/home-appliances/2026-06-25-abc.webp
 *   subcategory/air-conditioner/2026-06-25-abc.webp
 *   service/ac-installation/2026-06-25-thumb.webp
 *   provider/kashmir-cool-tech/logo/2026-06-25.webp
 *   provider/kashmir-cool-tech/showcase/2026-06-25.mp4
 *   customer/ali-ahmad/profile/2026-06-25.webp
 */
class MediaStoragePath
{
    public static function mainCategoryDir(Category|string $categoryOrSlug): string
    {
        $slug = is_string($categoryOrSlug)
            ? self::sanitizeSlug($categoryOrSlug)
            : self::categorySlug($categoryOrSlug);

        return 'category/'.$slug.'/';
    }

    public static function subCategoryDir(Category|string $categoryOrSlug): string
    {
        $slug = is_string($categoryOrSlug)
            ? self::sanitizeSlug($categoryOrSlug)
            : self::categorySlug($categoryOrSlug);

        return 'subcategory/'.$slug.'/';
    }

    public static function categoryDir(Category $category): string
    {
        if ($category->parent_id && $category->parent_id !== '0') {
            return self::subCategoryDir($category);
        }

        return self::mainCategoryDir($category);
    }

    public static function categoryDirFromName(string $name, bool $isSubCategory = false): string
    {
        $slug = self::sanitizeSlug($name);

        return $isSubCategory
            ? self::subCategoryDir($slug)
            : self::mainCategoryDir($slug);
    }

    public static function serviceDir(Service|string $serviceOrSlug): string
    {
        $slug = is_string($serviceOrSlug)
            ? self::sanitizeSlug($serviceOrSlug)
            : self::serviceSlug($serviceOrSlug);

        return 'service/'.$slug.'/';
    }

    public static function serviceOverviewDir(Service|string $serviceOrSlug): string
    {
        return self::serviceDir($serviceOrSlug).'overview/';
    }

    public static function providerDir(Provider $provider): string
    {
        return 'provider/'.self::providerSlug($provider).'/';
    }

    public static function providerSectionDir(Provider $provider, string $section): string
    {
        return self::providerDir($provider).trim($section, '/').'/';
    }

    public static function customerDir(User $user): string
    {
        return 'customer/'.self::customerSlug($user).'/';
    }

    public static function customerProfileDir(User $user): string
    {
        return self::customerDir($user).'profile/';
    }

    public static function customerProfileDirFromName(string $name): string
    {
        return 'customer/'.self::sanitizeSlug($name).'/profile/';
    }

    public static function legacyPrefixForCategory(Category $category): string
    {
        return 'category/';
    }

    public static function legacyPrefixForService(): string
    {
        return 'service/';
    }

    public static function legacyPrefixForProviderShowcase(): string
    {
        return 'provider/showcase/';
    }

    public static function legacyPrefixForAdvertisement(): string
    {
        return 'advertisement/';
    }

    public static function legacyPrefixForCustomerProfile(): string
    {
        return 'user/profile_image/';
    }

    public static function legacyPrefixForProviderLogo(): string
    {
        return 'provider/logo/';
    }

    public static function legacyPrefixForProviderCover(): string
    {
        // Cover uploads use provider/logo/ across admin, provider web, and API branding flows.
        return 'provider/logo/';
    }

    private static function categorySlug(Category $category): string
    {
        if (! empty($category->slug)) {
            return self::sanitizeSlug($category->slug);
        }

        return self::sanitizeSlug($category->name ?? 'category');
    }

    private static function serviceSlug(Service $service): string
    {
        if (! empty($service->slug)) {
            return self::sanitizeSlug($service->slug);
        }

        return self::sanitizeSlug($service->name ?? 'service');
    }

    public static function providerSlug(Provider $provider): string
    {
        $fromCompany = self::sanitizeSlug($provider->company_name ?? '');
        if ($fromCompany !== '' && $fromCompany !== 'provider') {
            return $fromCompany;
        }

        return 'provider-'.substr((string) $provider->id, 0, 8);
    }

    public static function customerSlug(User $user): string
    {
        $label = trim(implode(' ', array_filter([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ])));

        $slug = Str::slug($label, '-');
        if ($slug !== '' && $slug !== 'customer') {
            return $slug;
        }

        if (! empty($user->phone)) {
            return 'customer-'.preg_replace('/\D+/', '', (string) $user->phone);
        }

        return 'customer-'.substr((string) $user->id, 0, 8);
    }

    public static function advertisementDir(?Provider $provider = null): string
    {
        if ($provider) {
            return self::providerSectionDir($provider, 'advertisement');
        }

        return 'advertisement/';
    }

    private static function sanitizeSlug(string $value): string
    {
        $slug = Str::slug(trim($value), '-');

        return $slug !== '' ? $slug : 'item';
    }
}
