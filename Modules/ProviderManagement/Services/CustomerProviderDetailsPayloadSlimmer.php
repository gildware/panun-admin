<?php

namespace Modules\ProviderManagement\Services;

use Modules\CustomerModule\Services\CustomerServicePayloadSlimmer;

/**
 * Shrinks provider-details services JSON for faster transfer and mobile parsing.
 */
class CustomerProviderDetailsPayloadSlimmer
{
    /** @var list<string> */
    private const SUB_CATEGORY_KEYS = [
        'id',
        'name',
        'slug',
        'image',
        'services',
    ];

    /**
     * @param  array<int, mixed>  $subCategories
     * @return list<array<string, mixed>>
     */
    public static function slimSubCategories(array $subCategories): array
    {
        $slim = [];

        foreach ($subCategories as $subCategory) {
            $item = self::normalizeItem($subCategory);
            if ($item === null) {
                continue;
            }

            $row = [];
            foreach (self::SUB_CATEGORY_KEYS as $key) {
                if (! array_key_exists($key, $item)) {
                    continue;
                }

                if ($key === 'services') {
                    $row[$key] = array_map(
                        fn ($service) => CustomerServicePayloadSlimmer::slimItem(
                            is_array($service) ? $service : (array) $service
                        ),
                        is_iterable($item[$key]) ? iterator_to_array($item[$key], false) : []
                    );
                    continue;
                }

                $row[$key] = $item[$key];
            }

            $slim[] = $row;
        }

        return $slim;
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

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    public static function slimShowcaseItems(array $items): array
    {
        $slim = [];

        foreach ($items as $item) {
            $row = self::normalizeItem($item);
            if ($row === null) {
                continue;
            }

            $slim[] = array_filter([
                'id' => $row['id'] ?? null,
                'provider_id' => $row['provider_id'] ?? null,
                'title' => $row['title'] ?? null,
                'description' => $row['description'] ?? null,
                'media_type' => $row['media_type'] ?? null,
                'media_full_path' => $row['media_full_path'] ?? null,
                'sort_order' => $row['sort_order'] ?? null,
            ], fn ($value) => $value !== null);
        }

        return $slim;
    }

    /**
     * @return array{reviews: array<string, mixed>, rating: array<string, mixed>}
     */
    public static function slimReviewsPayload(array $reviews, array $rating): array
    {
        $reviewRows = [];
        foreach ($reviews['data'] ?? [] as $review) {
            if (! is_array($review)) {
                continue;
            }

            $customer = is_array($review['customer'] ?? null) ? $review['customer'] : null;
            $reply = is_array($review['review_reply'] ?? null) ? $review['review_reply'] : null;

            $reviewRows[] = array_filter([
                'id' => $review['id'] ?? null,
                'review_rating' => $review['review_rating'] ?? null,
                'review_comment' => $review['review_comment'] ?? null,
                'updated_at' => $review['updated_at'] ?? null,
                'customer' => $customer === null ? null : array_filter([
                    'first_name' => $customer['first_name'] ?? null,
                    'last_name' => $customer['last_name'] ?? null,
                    'profile_image_full_path' => $customer['profile_image_full_path'] ?? null,
                ], fn ($value) => $value !== null),
                'review_reply' => $reply === null ? null : array_filter([
                    'reply' => $reply['reply'] ?? null,
                    'updated_at' => $reply['updated_at'] ?? null,
                ], fn ($value) => $value !== null),
            ], fn ($value) => $value !== null);
        }

        $reviews['data'] = $reviewRows;

        return [
            'reviews' => $reviews,
            'rating' => $rating,
        ];
    }
}
