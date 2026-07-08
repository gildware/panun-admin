@php
    use Modules\ZoneManagement\Entities\Zone;

    $service->loadMissing(['serviceVariants.zonePrices', 'category.zones', 'variations']);

    $zones = $service->category?->zones ?? collect();
    if ($zones->isEmpty()) {
        $zoneIds = $service->variations->pluck('zone_id')->filter()->unique()->values();
        if ($zoneIds->isNotEmpty()) {
            $zones = Zone::query()->whereIn('id', $zoneIds)->orderBy('name')->get();
        }
    }

    $variants = ($service->serviceVariants ?? collect())->sortBy('sort_order')->values();

    $resolveZonePrice = static function ($service, $variant, $zone) {
        $variantKey = $variant->variant_key;
        $stored = is_array($service->variation_pricing) ? ($service->variation_pricing[$variantKey] ?? null) : null;
        $defaultPrice = is_array($stored)
            ? (float) ($stored['default_price'] ?? 0)
            : (float) ($variant->zonePrices->first()->price ?? 0);

        $zonePrice = $variant->zonePrices->firstWhere('zone_id', $zone->id)
            ?? $service->variations->first(function ($row) use ($variantKey, $zone) {
                return $row->variant_key === $variantKey && (string) $row->zone_id === (string) $zone->id;
            });

        return (float) ($zonePrice->price ?? $defaultPrice);
    };
@endphp

@if($zones->isEmpty() || $variants->isEmpty())
    <div class="text-center text-muted py-4">
        {{ translate('no_data_found') }}
    </div>
@else
    <div class="table-responsive service-detail-price-table">
        <table class="table table-sm table-bordered table-hover align-middle mb-0">
            <thead class="text-nowrap">
            <tr>
                <th class="py-2" style="width: 56px;">{{ translate('image') }}</th>
                <th class="py-2" style="min-width: 180px;">{{ translate('title') }}</th>
                @foreach($zones as $zone)
                    <th class="py-2 text-center">{{ $zone->name }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($variants as $variant)
                <tr>
                    <td class="py-1">
                        <img src="{{ $variant->image_full_path }}"
                             alt="{{ $variant->title }}"
                             class="rounded"
                             width="32"
                             height="32"
                             style="object-fit: cover;">
                    </td>
                    <td class="py-1">
                        <div class="fw-semibold text-dark fs-13">{{ $variant->title }}</div>
                        @if($variant->getRawOriginal('description'))
                            <div class="fs-12 text-muted text-truncate" style="max-width: 200px;">
                                {{ Str::limit($variant->getRawOriginal('description'), 60) }}
                            </div>
                        @endif
                    </td>
                    @foreach($zones as $zone)
                        <td class="py-1 text-center fw-medium c1 text-nowrap fs-13">
                            {{ with_currency_symbol($resolveZonePrice($service, $variant, $zone)) }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
