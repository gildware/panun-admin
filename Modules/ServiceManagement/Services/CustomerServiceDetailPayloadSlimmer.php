<?php

namespace Modules\ServiceManagement\Services;

use Modules\CustomerModule\Services\CustomerServicePayloadSlimmer;

/**
 * Slim service-detail shape for customer mobile detail screen.
 */
class CustomerServiceDetailPayloadSlimmer
{
    /** @var list<string> */
    private const DETAIL_KEYS = [
        'id',
        'slug',
        'name',
        'short_description',
        'description',
        'cover_image_full_path',
        'thumbnail_full_path',
        'category_id',
        'sub_category_id',
        'tax',
        'tax_label',
        'avg_rating',
        'rating_count',
        'is_favorite',
        'variations_app_format',
        'service_discount',
        'campaign_discount',
        'category',
        'sub_category',
        'faqs',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function slimDetail(mixed $service): array
    {
        $item = self::normalizeItem($service);
        if ($item === null) {
            return [];
        }

        $slim = [];
        foreach (self::DETAIL_KEYS as $key) {
            if (! array_key_exists($key, $item)) {
                continue;
            }

            if ($key === 'variations_app_format') {
                $slim[$key] = CustomerServicePayloadSlimmer::slimVariationsAppFormat($item[$key]);
                continue;
            }

            if ($key === 'category' || $key === 'sub_category') {
                $slimmed = CustomerServicePayloadSlimmer::slimServiceCategory($item[$key]);
                if ($slimmed !== null) {
                    $slim[$key] = $slimmed;
                }
                continue;
            }

            if ($key === 'faqs') {
                $slim[$key] = self::slimFaqs($item[$key]);
                continue;
            }

            $slim[$key] = $item[$key];
        }

        return $slim;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function slimFaqs(mixed $faqs): array
    {
        if (! is_iterable($faqs)) {
            return [];
        }

        $rows = [];
        foreach ($faqs as $faq) {
            $row = self::normalizeItem($faq);
            if ($row === null) {
                continue;
            }

            $rows[] = array_filter([
                'id' => $row['id'] ?? null,
                'question' => $row['question'] ?? null,
                'answer' => $row['answer'] ?? null,
                'is_active' => $row['is_active'] ?? null,
            ], fn ($value) => $value !== null);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeItem(mixed $item): ?array
    {
        if (is_array($item)) {
            return $item;
        }

        if (is_object($item) && method_exists($item, 'toArray')) {
            return $item->toArray();
        }

        return null;
    }
}
