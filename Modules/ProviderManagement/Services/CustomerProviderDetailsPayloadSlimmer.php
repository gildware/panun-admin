<?php

namespace Modules\ProviderManagement\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\CustomerModule\Services\CustomerServicePayloadSlimmer;
use Modules\ReviewModule\Entities\Review;

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
                'file_name' => $row['file_name'] ?? null,
                'media_full_path' => $row['media_full_path'] ?? null,
                'sort_order' => $row['sort_order'] ?? null,
            ], fn ($value) => $value !== null);
        }

        return $slim;
    }

    /**
     * @return array{reviews: array<string, mixed>, rating: array<string, mixed>}
     */
    public static function slimPaginatedReviews(LengthAwarePaginator $reviews, array $rating): array
    {
        $reviewRows = [];
        foreach ($reviews->getCollection() as $review) {
            if ($review instanceof Review) {
                $reviewRows[] = self::slimReviewModel($review);
                continue;
            }

            if (is_array($review)) {
                $reviewRows[] = self::slimReviewItem($review);
            }
        }

        return [
            'reviews' => [
                'current_page' => $reviews->currentPage(),
                'data' => $reviewRows,
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'rating' => $rating,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function slimReviewModel(Review $review): array
    {
        $customer = null;
        if ($review->relationLoaded('customer') && $review->customer) {
            if (empty($review->customer->user_type)) {
                $review->customer->user_type = 'customer';
            }
            $review->customer->loadMissing('storage');
            $review->customer->setAppends(['profile_image_full_path']);
            $customer = array_filter([
                'first_name' => $review->customer->first_name,
                'last_name' => $review->customer->last_name,
                'profile_image_full_path' => $review->customer->profile_image_full_path,
            ], fn ($value) => $value !== null);
        }

        $booking = null;
        if ($review->relationLoaded('booking') && $review->booking) {
            $detail = null;
            if ($review->booking->relationLoaded('detail')) {
                $detail = $review->booking->detail
                    ->map(fn ($row) => array_filter([
                        'service_id' => $row->service_id ?? null,
                        'variant_key' => $row->variant_key ?? null,
                    ], fn ($value) => $value !== null))
                    ->values()
                    ->all();
            }

            $booking = array_filter([
                'id' => $review->booking->id,
                'readable_id' => $review->booking->readable_id !== null
                    ? (string) $review->booking->readable_id
                    : null,
                'detail' => $detail,
            ], fn ($value) => $value !== null);
        }

        $service = null;
        if ($review->relationLoaded('service') && $review->service) {
            $service = array_filter([
                'id' => $review->service->id,
                'name' => $review->service->name,
            ], fn ($value) => $value !== null);
        }

        $reply = null;
        if ($review->relationLoaded('reviewReply') && $review->reviewReply) {
            $reply = array_filter([
                'reply' => $review->reviewReply->reply,
                'updated_at' => self::serializeDateTime($review->reviewReply->updated_at),
            ], fn ($value) => $value !== null);
        }

        return array_filter([
            'id' => $review->id,
            'readable_id' => $review->readable_id,
            'booking_id' => $review->booking_id,
            'booking_readable_id' => $review->booking?->readable_id,
            'service_id' => $review->service_id,
            'is_active' => $review->is_active,
            'review_rating' => $review->review_rating,
            'review_comment' => $review->review_comment,
            'updated_at' => self::serializeDateTime($review->updated_at),
            'customer' => $customer,
            'booking' => $booking,
            'service' => $service,
            'review_reply' => $reply,
        ], fn ($value) => $value !== null);
    }

    private static function serializeDateTime(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s.000000\Z');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $review
     * @return array<string, mixed>
     */
    public static function slimReviewItem(array $review): array
    {
        $customer = is_array($review['customer'] ?? null) ? $review['customer'] : null;
        $reply = is_array($review['review_reply'] ?? null) ? $review['review_reply'] : null;
        $booking = is_array($review['booking'] ?? null) ? $review['booking'] : null;
        $service = is_array($review['service'] ?? null) ? $review['service'] : null;

        return array_filter([
            'id' => $review['id'] ?? null,
            'readable_id' => $review['readable_id'] ?? null,
            'booking_id' => $review['booking_id'] ?? null,
            'booking_readable_id' => $review['booking_readable_id']
                ?? (is_array($booking) ? ($booking['readable_id'] ?? null) : null),
            'service_id' => $review['service_id'] ?? null,
            'is_active' => $review['is_active'] ?? null,
            'review_rating' => $review['review_rating'] ?? null,
            'review_comment' => $review['review_comment'] ?? null,
            'updated_at' => $review['updated_at'] ?? null,
            'customer' => $customer === null ? null : array_filter([
                'first_name' => $customer['first_name'] ?? null,
                'last_name' => $customer['last_name'] ?? null,
                'profile_image_full_path' => $customer['profile_image_full_path'] ?? null,
            ], fn ($value) => $value !== null),
            'booking' => $booking === null ? null : array_filter([
                'id' => $booking['id'] ?? null,
                'readable_id' => $booking['readable_id'] ?? null,
                'detail' => isset($booking['detail']) && is_array($booking['detail'])
                    ? array_map(
                        fn ($detail) => is_array($detail) ? array_filter([
                            'service_id' => $detail['service_id'] ?? null,
                            'variant_key' => $detail['variant_key'] ?? null,
                        ], fn ($value) => $value !== null) : [],
                        $booking['detail']
                    )
                    : null,
            ], fn ($value) => $value !== null),
            'service' => $service === null ? null : array_filter([
                'id' => $service['id'] ?? null,
                'name' => $service['name'] ?? null,
            ], fn ($value) => $value !== null),
            'review_reply' => $reply === null ? null : array_filter([
                'reply' => $reply['reply'] ?? null,
                'updated_at' => $reply['updated_at'] ?? null,
            ], fn ($value) => $value !== null),
        ], fn ($value) => $value !== null);
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

            $reviewRows[] = self::slimReviewItem($review);
        }

        $reviews['data'] = $reviewRows;

        return [
            'reviews' => $reviews,
            'rating' => $rating,
        ];
    }
}
