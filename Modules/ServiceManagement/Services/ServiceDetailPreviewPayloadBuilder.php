<?php

namespace Modules\ServiceManagement\Services;

use Illuminate\Support\Collection;
use Modules\ServiceManagement\Entities\Service;

class ServiceDetailPreviewPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Service $service, ?array $resolvedOverviewContent, Collection $faqs): array
    {
        $lowestPrice = null;
        if ($service->relationLoaded('variations') && $service->variations->isNotEmpty()) {
            $lowestPrice = $service->variations->min('price');
        } elseif ($service->variations()->exists()) {
            $lowestPrice = $service->variations()->min('price');
        }
        if ($lowestPrice === null) {
            $lowestPrice = $service->min_bidding_price;
        }

        $variants = [];
        if ($service->relationLoaded('serviceVariants') && $service->serviceVariants->isNotEmpty()) {
            foreach ($service->serviceVariants as $variant) {
                $price = $service->variations
                    ->where('variant_key', $variant->variant_key)
                    ->min('price');
                $variants[] = [
                    'key' => $variant->variant_key,
                    'name' => $variant->title ?: str_replace('-', ' ', (string) $variant->variant_key),
                    'price' => $price !== null ? (float) $price : null,
                ];
            }
        }

        $currencySymbol = '₹';
        if (function_exists('with_currency_symbol')) {
            $currencySymbol = preg_replace('/[\d.,\s\x{00A0}]/u', '', with_currency_symbol(1)) ?: $currencySymbol;
        }

        return [
            'name' => (string) $service->name,
            'shortDescription' => (string) ($service->short_description ?? ''),
            'descriptionHtml' => (string) ($service->description ?? ''),
            'coverUrl' => (string) ($service->cover_image_full_path ?? ''),
            'thumbUrl' => (string) ($service->thumbnail_full_path ?? ''),
            'price' => $lowestPrice !== null ? (float) $lowestPrice : null,
            'currencySymbol' => $currencySymbol,
            'rating' => round((float) ($service->avg_rating ?? 0), 2),
            'ratingCount' => (int) ($service->rating_count ?? 0),
            'overviewContent' => $resolvedOverviewContent,
            'faqs' => $faqs->map(fn ($faq) => [
                'question' => (string) $faq->question,
                'answer' => (string) $faq->answer,
            ])->values()->all(),
            'variants' => $variants,
            'hasFaqs' => $faqs->isNotEmpty(),
        ];
    }
}
